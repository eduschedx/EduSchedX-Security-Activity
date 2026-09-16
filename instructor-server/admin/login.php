<?php
require dirname(__DIR__) . '/config.php';

if (!empty($_SESSION['admin_authenticated'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lockedUntil = (int) ($_SESSION['admin_locked_until'] ?? 0);
    if ($lockedUntil > time()) {
        $error = 'Too many attempts. Try again shortly.';
    } elseif (!adminCsrfIsValid($_POST['csrf_token'] ?? null)) {
        $error = 'Please refresh and try again.';
    } elseif (password_verify((string) ($_POST['password'] ?? ''), ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        unset($_SESSION['admin_attempts'], $_SESSION['admin_locked_until']);
        header('Location: dashboard.php');
        exit;
    } else {
        $_SESSION['admin_attempts'] = (int) ($_SESSION['admin_attempts'] ?? 0) + 1;
        if ($_SESSION['admin_attempts'] >= 5) {
            $_SESSION['admin_locked_until'] = time() + 30;
            $_SESSION['admin_attempts'] = 0;
        }
        $error = 'Incorrect password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activity Admin | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/instructor-server/style.css" rel="stylesheet">
</head>
<body>
    <header class="brand-header"><span class="brand-link"><img src="/instructor-server/images/eduschedx-logo.svg" alt="" class="brand-logo"><span class="brand-name">EduSched<span>X</span></span></span></header>
    <main class="activity-shell">
        <section class="activity-card admin-login-card">
            <div class="screen-panel">
                <div class="panel-heading"><span class="eyebrow">Activity Admin</span><h1>Instructor Login</h1><p>Sign in to view activity results.</p></div>
                <?php if ($error !== ''): ?><div class="alert alert-danger py-2"><?= adminEscape($error) ?></div><?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
                    <label class="form-label" for="password">Admin Password</label>
                    <input class="form-control mb-4" type="password" id="password" name="password" required autofocus>
                    <button class="btn btn-eduschedx w-100" type="submit"><i class="bi bi-lock me-1"></i> Sign In</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>

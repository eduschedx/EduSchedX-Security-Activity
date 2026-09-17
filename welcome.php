<?php
require __DIR__ . '/config.php';
requireOpenActivity();

$tabToken = (string) ($_GET['tab_token'] ?? '');
$expectedTabToken = (string) ($_SESSION['tab_bootstrap_token'] ?? '');
$authorizeThisTab = $tabToken !== '' && $expectedTabToken !== '' && hash_equals($expectedTabToken, $tabToken);
if ($authorizeThisTab) unset($_SESSION['tab_bootstrap_token']);

$fullName = (string) $_SESSION['full_name'];
$firstName = explode(' ', trim($fullName))[0] ?? $fullName;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <?php if ($authorizeThisTab): ?><script>sessionStorage.setItem('eduschedx_tab_authenticated', '1'); history.replaceState(null, '', 'welcome.php');</script><?php endif; ?>
</head>
<body class="welcome-page">
    <?= studentBrandHeader() ?>
    <main class="activity-shell">
        <section class="activity-card compact-card welcome-card">
            <div class="screen-panel submission-received welcome-panel">
                <a class="welcome-back" href="login.php" aria-label="Back to login"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
                <div class="submission-icon"><i class="bi bi-person-check" aria-hidden="true"></i></div>
                <span class="eyebrow">Identity Verified</span>
                <h1>Welcome, <?= escape($firstName) ?></h1>
                <div class="welcome-assignment">
                    <span>Assigned Station</span>
                    <strong><?= escape($_SESSION['assigned_station']) ?></strong>
                </div>
                <div class="student-summary welcome-identity">
                    <div><small>Student</small><strong><?= escape($fullName) ?></strong></div>
                    <div><small>Student ID</small><strong><?= escape($_SESSION['student_id']) ?></strong></div>
                </div>
                <a class="btn btn-eduschedx btn-label-centered icon-end w-100" href="instructions.php"><span>Continue to Instructions</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </section>
    </main>
</body>
</html>

<?php
require __DIR__ . '/config.php';

if (empty($_SESSION['duplicate_notice'])) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activity Already Submitted | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="already-submitted-page">
    <header class="brand-header"><a href="login.php" class="brand-link"><img src="images/eduschedx-logo.svg" alt="" class="brand-logo"><span class="brand-name">EduSched<span>X</span></span></a></header>
    <main class="activity-shell">
        <section class="activity-card compact-card login-card already-submitted-card">
            <div class="screen-panel login-panel submission-received already-submitted-panel">
                <div class="submission-icon submission-warning"><i class="bi bi-info-lg"></i></div>
                <h1>Activity Already Submitted</h1>
                <p>You have already submitted this activity.</p>
                <a class="btn btn-eduschedx btn-label-centered icon-end w-100 mt-3" href="login.php"><span>Back to Login</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </section>
    </main>
</body>
</html>

<?php
require __DIR__ . '/config.php';
requireOpenActivity();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instructions | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="instructions-page">
    <?= studentBrandHeader() ?>
    <main class="activity-shell">
        <section class="activity-card compact-card instructions-card">
            <div class="screen-panel instructions-panel">
                <a class="page-back" href="welcome.php" aria-label="Back to student information"><i class="bi bi-arrow-left"></i></a>
                <div class="panel-heading"><span class="eyebrow">Instructions</span><h1>Complete the Security Checks</h1><p>Follow each step in order before starting the activity.</p></div>
                <ol class="instruction-list">
                    <li><span class="instruction-number">1</span><i class="bi bi-hand-index-thumb" aria-hidden="true"></i><div><strong>Security Match</strong><p>Complete the drag-and-match security task.</p></div></li>
                    <li><span class="instruction-number">2</span><i class="bi bi-code-square" aria-hidden="true"></i><div><strong>PHP Coding</strong><p>Type the missing PHP code.</p></div></li>
                    <li><span class="instruction-number">3</span><i class="bi bi-shield-exclamation" aria-hidden="true"></i><div><strong>Security Alert Simulator</strong><p>Generate and check the final security alert interface.</p></div></li>
                    <li><span class="instruction-number">4</span><i class="bi bi-bullseye" aria-hidden="true"></i><div><strong>Goal</strong><p>Apply secure access-control rules correctly.</p></div></li>
                </ol>
                <a class="btn btn-eduschedx btn-label-centered icon-end w-100" href="levels.php"><span>Start Activity</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </section>
    </main>
</body>
</html>

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
<body>
    <header class="brand-header"><a href="index.php" class="brand-link"><img src="images/eduschedx-logo.svg" alt="" class="brand-logo"><span class="brand-name">EduSched<span>X</span></span></a></header>
    <main class="activity-shell">
        <section class="activity-card compact-card">
            <?= studentProgress('Instructions') ?>
            <div class="screen-panel">
                <a class="page-back" href="welcome.php" aria-label="Back to student information"><i class="bi bi-arrow-left"></i></a>
                <div class="panel-heading"><span class="eyebrow">Instructions</span><h1>Complete the Security Checks</h1><p>Finish all five PHP challenges.</p></div>
                <ol class="instruction-list">
                    <li><span>1</span>Review the five coding challenges.</li>
                    <li><span>2</span>Type the PHP code, not its letter.</li>
                    <li><span>3</span>Run each challenge, then submit.</li>
                </ol>
                <div class="goal-box"><i class="bi bi-bullseye"></i><div><strong>Goal</strong><p>Apply secure access-control rules.</p></div></div>
                <a class="btn btn-eduschedx btn-label-centered icon-end w-100" href="activity.php"><span>Start Activity</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </section>
    </main>
</body>
</html>

<?php
require __DIR__ . '/config.php';
requireOpenActivity();

$partOneComplete = !empty($_SESSION['security_part_complete']);
$partTwoComplete = !empty($_SESSION['part_two_complete']);
$partThreeComplete = !empty($_SESSION['part_three_complete']);
$showPartOneSuccess = $partOneComplete && !empty($_SESSION['part_one_just_completed']);
unset($_SESSION['part_one_just_completed']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a Level | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="level-page">
    <?= studentBrandHeader() ?>
    <main class="activity-shell">
        <section class="level-board">
            <?php if (!$showPartOneSuccess): ?><a class="level-back" href="instructions.php" aria-label="Back to instructions"><i class="bi bi-arrow-left" aria-hidden="true"></i></a><?php endif; ?>
            <?php if (!$showPartOneSuccess): ?>
                <div class="level-heading">
                    <span class="eyebrow">Security Activity</span>
                    <h1>Complete Each Part</h1>
                    <p>Complete each part to unlock the next challenge.</p>
                </div>
            <?php endif; ?>

            <?php if ($showPartOneSuccess): ?>
                <section class="part-complete-card" role="status" aria-labelledby="part-one-complete-title">
                    <div class="part-complete-icon"><i class="bi bi-trophy-fill" aria-hidden="true"></i></div>
                    <span class="eyebrow">Part 1 Complete</span>
                    <h2 id="part-one-complete-title">Congratulations!</h2>
                    <p>You successfully completed Part 1: Security Matching.</p>
                    <a class="btn btn-eduschedx btn-label-centered icon-end" href="activity.php"><span>Continue to Part 2</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </section>
            <?php else: ?>
            <div class="level-grid">
                <?php if (!$partOneComplete): ?>
                    <a class="level-card is-available" href="activity.php" aria-label="Open Part 1 Security Matching">
                <?php else: ?>
                    <a class="level-card is-complete" href="part-result.php?part=1" aria-label="Review Part 1 Security Matching results">
                <?php endif; ?>
                        <span class="level-number">01</span>
                        <div class="level-icon"><i class="bi bi-grid-3x3-gap-fill" aria-hidden="true"></i></div>
                        <div class="level-copy"><span class="level-state"><i class="bi <?= $partOneComplete ? 'bi-check-circle-fill' : 'bi-play-circle-fill' ?>" aria-hidden="true"></i><?= $partOneComplete ? 'Completed' : 'Ready' ?></span><h2>Security Matching</h2><p>Match and sort</p></div>
                        <span class="level-action" aria-hidden="true"><?= $partOneComplete ? '<i class="bi bi-eye-fill"></i>' : '<i class="bi bi-arrow-right"></i>' ?></span>
                    </a>

                <?php if ($partTwoComplete): ?>
                    <a class="level-card is-complete" href="part-result.php?part=2" aria-label="Review Part 2 PHP Coding results">
                <?php elseif ($partOneComplete): ?>
                    <a class="level-card is-available" href="activity.php" aria-label="Open Part 2 PHP Coding">
                <?php else: ?>
                    <div class="level-card is-locked" aria-disabled="true">
                <?php endif; ?>
                        <span class="level-number">02</span>
                        <div class="level-icon"><i class="bi bi-code-slash" aria-hidden="true"></i></div>
                        <div class="level-copy"><span class="level-state"><i class="bi <?= $partTwoComplete ? 'bi-check-circle-fill' : ($partOneComplete ? 'bi-unlock-fill' : 'bi-lock-fill') ?>" aria-hidden="true"></i><?= $partTwoComplete ? 'Completed' : ($partOneComplete ? 'Unlocked' : 'Locked') ?></span><h2>PHP Coding</h2><p>Type and run code</p></div>
                        <span class="level-action" aria-hidden="true"><i class="bi <?= $partTwoComplete ? 'bi-eye-fill' : ($partOneComplete ? 'bi-arrow-right' : 'bi-lock-fill') ?>"></i></span>
                <?= ($partOneComplete || $partTwoComplete) ? '</a>' : '</div>' ?>

                <?php if ($partTwoComplete): ?>
                <a class="level-card <?= $partThreeComplete ? 'is-complete' : 'is-available' ?>" href="<?= $partThreeComplete ? 'part-result.php?part=3' : 'part3.php' ?>" aria-label="<?= $partThreeComplete ? 'Review' : 'Open' ?> Part 3 Security Alert Simulator">
                <?php else: ?>
                <div class="level-card is-locked is-coming-soon" aria-disabled="true">
                <?php endif; ?>
                    <span class="level-number">03</span>
                    <div class="level-icon"><i class="bi bi-stars" aria-hidden="true"></i></div>
                    <div class="level-copy"><span class="level-state"><i class="bi <?= $partThreeComplete ? 'bi-check-circle-fill' : ($partTwoComplete ? 'bi-unlock-fill' : 'bi-lock-fill') ?>" aria-hidden="true"></i><?= $partThreeComplete ? 'Completed' : ($partTwoComplete ? 'Unlocked' : 'Locked') ?></span><h2>Security Alert Simulator</h2><p><?= $partThreeComplete ? 'Simulation complete' : ($partTwoComplete ? 'Generate the correct alert' : 'Complete Part 2 first') ?></p></div>
                    <span class="level-action"><i class="bi <?= $partThreeComplete ? 'bi-eye-fill' : ($partTwoComplete ? 'bi-arrow-right' : 'bi-lock-fill') ?>"></i></span>
                <?= $partTwoComplete ? '</a>' : '</div>' ?>
            </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

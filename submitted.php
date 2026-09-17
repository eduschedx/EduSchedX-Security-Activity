<?php
require __DIR__ . '/config.php';
$receipt = $_SESSION['submission_receipt'] ?? null;
if (!is_array($receipt)) {
    header('Location: login.php');
    exit;
}
$showPartTwoSuccess = !empty($_SESSION['part_two_just_completed']);
unset($_SESSION['part_two_just_completed']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submission Received | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="submitted-page">
    <?= studentBrandHeader() ?>
    <main class="activity-shell">
        <?php if ($showPartTwoSuccess): ?>
            <section class="part-complete-card" role="status" aria-labelledby="part-two-complete-title">
                <div class="part-complete-icon"><i class="bi bi-trophy-fill" aria-hidden="true"></i></div>
                <span class="eyebrow">Part 2 Complete</span>
                <h2 id="part-two-complete-title">Congratulations!</h2>
                <p>You successfully completed Part 2: PHP Coding.</p>
                <a class="btn btn-eduschedx btn-label-centered icon-end" href="part3.php"><span>Continue to Part 3</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </section>
        <?php else: ?>
        <section class="activity-card compact-card login-card submission-success-card">
            <div class="screen-panel submission-received">
                <div class="submission-icon submission-heart-hand"><span aria-hidden="true">🫰</span></div>
                <h1>Thank You, Classmates and Labmates!</h1>
                <p>Your activity has been successfully submitted.</p>
                <strong class="submission-signature">From the EduSchedX Team</strong>
                <div class="student-summary"><div><strong><?= escape((string) $receipt['full_name']) ?></strong><small><?= escape((string) $receipt['student_id']) ?></small></div><span><?= escape((string) $receipt['assigned_station']) ?></span></div>
                <a class="btn btn-eduschedx btn-label-centered icon-end w-100 mt-3" href="login.php"><span>Return to Login</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </section>
        <?php endif; ?>
    </main>
</body>
</html>

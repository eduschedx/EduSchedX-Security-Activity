<?php
require __DIR__ . '/config.php';
$receipt = $_SESSION['submission_receipt'] ?? null;
if (!is_array($receipt)) {
    header('Location: login.php');
    exit;
}
$feedbackSubmitted = !empty($_SESSION['feedback_submitted']);
$feedbackError = (string) ($_SESSION['feedback_error'] ?? '');
$feedbackDraft = (string) ($_SESSION['feedback_draft'] ?? '');
unset($_SESSION['feedback_error']);
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
<body>
    <header class="brand-header"><a href="index.php" class="brand-link"><img src="images/eduschedx-logo.svg" alt="" class="brand-logo"><span class="brand-name">EduSched<span>X</span></span></a></header>
    <main class="activity-shell">
        <section class="activity-card compact-card">
            <?= studentProgress('Submit') ?>
            <div class="screen-panel submission-received">
                <div class="submission-icon"><i class="bi bi-check-lg"></i></div>
                <h1>Submission successful</h1>
                <p>Your activity has been successfully submitted.</p>
                <div class="student-summary"><div><strong><?= escape((string) $receipt['full_name']) ?></strong><small><?= escape((string) $receipt['student_id']) ?></small></div><span><?= escape((string) $receipt['assigned_station']) ?></span></div>
                <?php if ($feedbackSubmitted): ?>
                    <div class="feedback-thanks" id="feedback-thanks" role="status">
                        <i class="bi bi-emoji-laughing-fill" aria-hidden="true"></i>
                        <h2>Thank you, classmate!</h2>
                        <p>Your feedback means a lot. Lab lots! 💚</p>
                    </div>
                <?php else: ?>
                    <form class="feedback-form" id="feedback" action="feedback.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                        <div>
                            <label class="form-label" for="feedback-comment">Provide feedback</label>
                            <textarea class="form-control" id="feedback-comment" name="feedback" maxlength="500" rows="4" placeholder="Tell us what worked, what was confusing, or what could be improved." required><?= escape($feedbackDraft) ?></textarea>
                            <small>Up to 500 characters</small>
                        </div>
                        <?php if ($feedbackError !== ''): ?><div class="alert alert-warning py-2 mb-0" role="alert"><?= escape($feedbackError) ?></div><?php endif; ?>
                        <button class="btn btn-eduschedx w-100" type="submit"><i class="bi bi-chat-heart me-1"></i> Submit Feedback</button>
                    </form>
                <?php endif; ?>
                <a class="btn btn-eduschedx btn-label-centered icon-end w-100 mt-3" href="login.php"><span>Return to Login</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </section>
    </main>
</body>
</html>

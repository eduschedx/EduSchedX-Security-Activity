<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$receipt = $_SESSION['submission_receipt'] ?? null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_array($receipt) ||
    empty($receipt['submission_id']) || !csrfIsValid($_POST['csrf_token'] ?? null)) {
    header('Location: submitted.php');
    exit;
}

$feedback = preg_replace('/\s+/u', ' ', trim((string) ($_POST['feedback'] ?? ''))) ?? '';
if ($feedback === '' || mb_strlen($feedback) > 500) {
    $_SESSION['feedback_error'] = 'Please enter a comment of 500 characters or fewer.';
    $_SESSION['feedback_draft'] = mb_substr($feedback, 0, 500);
    header('Location: submitted.php#feedback');
    exit;
}

$statement = database()->prepare(
    "UPDATE activity_submissions SET feedback = :feedback
     WHERE submission_id = :submission_id AND (feedback IS NULL OR feedback = '')"
);
$statement->execute([
    'feedback' => $feedback,
    'submission_id' => (string) $receipt['submission_id'],
]);

if ($statement->rowCount() !== 1) {
    $_SESSION['feedback_error'] = 'Feedback has already been submitted for this activity.';
    header('Location: submitted.php#feedback');
    exit;
}

unset($_SESSION['feedback_draft'], $_SESSION['feedback_error']);
$_SESSION['feedback_submitted'] = true;
header('Location: submitted.php#feedback-thanks');
exit;

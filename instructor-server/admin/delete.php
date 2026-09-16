<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !adminCsrfIsValid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid request.');
}

$submissionId = filter_var($_POST['submission_id'] ?? null, FILTER_VALIDATE_INT);
if ($submissionId === false || $submissionId < 1) {
    http_response_code(422);
    exit('Invalid submission.');
}

$pdo = database();
$pdo->beginTransaction();
$lookup = $pdo->prepare('SELECT submission_id FROM activity_submissions WHERE id = :id');
$lookup->execute(['id' => $submissionId]);
$submissionKey = $lookup->fetchColumn();
if ($submissionKey === false) {
    $pdo->rollBack();
    http_response_code(404);
    exit('Submission not found.');
}

$release = $pdo->prepare('DELETE FROM submission_unique_keys WHERE submission_id = :submission_id');
$release->execute(['submission_id' => $submissionKey]);
$statement = $pdo->prepare('DELETE FROM activity_submissions WHERE id = :id');
$statement->execute(['id' => $submissionId]);
$pdo->commit();

if ($statement->rowCount() !== 1) {
    http_response_code(500);
    exit('Submission could not be deleted.');
}

$_SESSION['admin_success'] = 'Submission deleted successfully.';
header('Location: dashboard.php');
exit;

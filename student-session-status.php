<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['student_verified']) || empty($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['active' => false]);
    exit;
}

if (!studentSubmissionStillExists()) {
    finishStudentSession(['reset_notice' => 'Your previous activity was reset. Log in to start again.']);
    http_response_code(409);
    echo json_encode(['active' => false, 'reset' => true]);
    exit;
}

echo json_encode(['active' => true]);

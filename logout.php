<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfIsValid($_POST['csrf_token'] ?? null)) {
    header('Location: login.php');
    exit;
}

finishStudentSession(['logout_notice' => 'You have been logged out successfully.']);
header('Location: login.php');
exit;

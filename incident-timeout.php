<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfIsValid($_POST['csrf_token'] ?? null)) {
    header('Location: part3.php');
    exit;
}
if (empty($_SESSION['part_two_complete'])) {
    header('Location: levels.php');
    exit;
}
$deadline = (int) ($_SESSION['part_three_deadline'] ?? 0);
if ($deadline > 0 && time() >= $deadline) expirePartThree();
header('Location: part3.php');
exit;

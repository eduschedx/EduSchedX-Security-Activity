<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfIsValid($_POST['csrf_token'] ?? null)) {
    header('Location: part3.php');
    exit;
}
if (empty($_SESSION['part_two_complete']) || empty($_SESSION['part_three_ready'])) {
    header('Location: part3.php');
    exit;
}

$score = (int) ($_SESSION['part_three_score'] ?? 0);
$score = $score === 5 ? 5 : 0;
$results = is_array($_SESSION['part_three_results'] ?? null) ? $_SESSION['part_three_results'] : [];

recordActivityPart(3, $score, $results);
$complete = database()->prepare(
    'UPDATE activity_submissions
     SET part3_score=:score, score=part1_score+part2_score+:score, total=15, is_complete=1
     WHERE submission_id=:submission_id'
);
$complete->execute(['score' => $score, 'submission_id' => (string) $_SESSION['submission_id']]);

$_SESSION['part_three_complete'] = true;
$_SESSION['part_three_just_completed'] = true;
unset($_SESSION['part_three_ready']);
header('Location: part3.php');
exit;

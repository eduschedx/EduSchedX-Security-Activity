<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfIsValid($_POST['csrf_token'] ?? null)) {
    header('Location: activity.php');
    exit;
}

if (!empty($_SESSION['submitted'])) {
    header('Location: submitted.php');
    exit;
}

if ((int) ($_SESSION['challenge_step'] ?? 0) !== 4) {
    header('Location: activity.php');
    exit;
}
if (empty($_SESSION['part_two_ready'])) {
    header('Location: activity.php');
    exit;
}

$lastAttempt = (int) ($_SESSION['last_submit_attempt'] ?? 0);
if (time() - $lastAttempt < 5) {
    $_SESSION['submit_error'] = 'Please wait a moment before trying again.';
    header('Location: activity.php');
    exit;
}
$_SESSION['last_submit_attempt'] = time();

require __DIR__ . '/grader.php';
$draft = (array) ($_SESSION['answer_draft'] ?? []);
$answers = [];
foreach (array_keys(challengeDefinitions()) as $challengeId) {
    $answer = trim((string) ($draft[$challengeId] ?? ''));
    $answers[$challengeId] = mb_strlen($answer) <= 180 ? $answer : '';
}
if (!isset($_SESSION['challenge_order'][4]) || !isset($_SESSION['challenge_result'][(int) $_SESSION['challenge_order'][4]])) {
    header('Location: activity.php');
    exit;
}
$lastChallengeId = (int) $_SESSION['challenge_order'][4];
if (empty($_SESSION['challenge_result'][$lastChallengeId]['matches']) && (int) ($_SESSION['challenge_attempts'][$lastChallengeId] ?? 0) < 2) {
    header('Location: activity.php');
    exit;
}
$_SESSION['answer_draft'] = $answers;
if (in_array('', $answers, true)) {
    $_SESSION['submit_error'] = 'Complete all five challenges before submitting.';
    header('Location: activity.php');
    exit;
}
$grade = gradeChallenges($answers);
foreach ($grade['results'] as &$codingResult) {
    $codingChallengeId = (int) ($codingResult['challenge'] ?? 0);
    $codingDefinition = challengeDefinitions()[$codingChallengeId] ?? null;
    $codingPreview = (array) ($_SESSION['challenge_result'][$codingChallengeId] ?? []);
    $codingResult['answer'] = (string) ($answers[$codingChallengeId] ?? '');
    $codingResult['actual'] = (string) ($codingPreview['actual'] ?? 'INVALID CODE');
    $codingResult['expected'] = (string) ($codingDefinition['expected'] ?? '');
    $codingResult['attempts'] = (int) ($_SESSION['challenge_attempts'][$codingChallengeId] ?? 0);
}
unset($codingResult);
$partOneResults = [];
foreach (securityUnlockDefinitions() as $questionId => $definition) {
    $partOneResults[] = [
        'challenge' => $questionId,
        'title' => $definition['title'],
        'passed' => !empty($_SESSION['security_unlock_result'][$questionId]),
        'answer' => (array) ($_SESSION['security_unlock_draft'][$questionId] ?? []),
        'expected' => $definition['answer'],
        'attempts' => (int) ($_SESSION['security_unlock_attempts'][$questionId] ?? 0),
    ];
}
$partOneScore = count(array_filter($partOneResults, static fn (array $result): bool => $result['passed']));
$partTwoScore = (int) $grade['score'];
$partThreeScore = 0;
$overallScore = $partOneScore + $partTwoScore + $partThreeScore;
$studentResults = [
    'parts' => [
        'part1' => ['title' => 'Security Matching', 'score' => $partOneScore, 'total' => 5, 'results' => $partOneResults],
        'part2' => ['title' => 'PHP Coding', 'score' => $partTwoScore, 'total' => 5, 'results' => $grade['results']],
        'part3' => ['title' => 'Security Alert Simulator', 'score' => $partThreeScore, 'total' => 5, 'results' => [], 'status' => 'Locked until Part II is complete'],
    ],
    'overall' => ['score' => $overallScore, 'total' => 15],
];
$ipAddress = observedClientIp();
$pdo = database();

$roster = $pdo->prepare(
    'SELECT student_id, full_name, assigned_station FROM official_roster
     WHERE student_id = :student_id AND name_normalized = :name AND assigned_station = :station LIMIT 1'
);
$roster->execute([
    'student_id' => normalizeStudentId((string) $_SESSION['student_id']),
    'name' => normalizeStudentName((string) $_SESSION['full_name']),
    'station' => canonicalStation((string) $_SESSION['assigned_station']),
]);
$officialStudent = $roster->fetch();
if (!$officialStudent) {
    $_SESSION['submit_error'] = 'Your roster information could not be verified.';
    header('Location: activity.php');
    exit;
}

$submissionId = (string) $_SESSION['submission_id'];
$duplicateChecks = [
    ['student_id', $officialStudent['student_id']],
    ['station_normalized', $officialStudent['assigned_station']],
];
foreach ($duplicateChecks as [$column, $value]) {
    $check = $pdo->prepare("SELECT 1 FROM activity_submissions WHERE {$column} = :value AND submission_id <> :submission_id LIMIT 1");
    $check->execute(['value' => $value, 'submission_id' => $submissionId]);
    if ($check->fetchColumn() !== false) {
        finishStudentSession(['duplicate_notice' => true]);
        header('Location: already-submitted.php');
        exit;
    }
}

try {
    $pdo->beginTransaction();
    $claim = $pdo->prepare(
        'INSERT INTO submission_unique_keys (key_type, key_value, submission_id)
         VALUES (:type, :value, :submission_id)
         ON CONFLICT(key_type, key_value) DO NOTHING'
    );
    foreach ([
        ['SUBMISSION_ID', $submissionId],
        ['STUDENT_ID', $officialStudent['student_id']],
        ['STATION', $officialStudent['assigned_station']],
    ] as [$type, $value]) {
        $claim->execute(['type' => $type, 'value' => $value, 'submission_id' => $submissionId]);
    }

    $insert = $pdo->prepare(
        'INSERT INTO activity_submissions
         (submission_id, student_id, student_name, student_name_normalized, station, station_normalized,
          ip_address, part1_score, part2_score, part3_score, score, total, results_json, is_complete, submitted_at)
         VALUES (:submission_id, :student_id, :student_name, :student_name_normalized, :station,
                 :station_normalized, :ip_address, :part1_score, :part2_score, :part3_score,
                 :score, :total, :results_json, 0, :submitted_at)
         ON CONFLICT(submission_id) DO UPDATE SET
             student_id = excluded.student_id,
             student_name = excluded.student_name,
             student_name_normalized = excluded.student_name_normalized,
             station = excluded.station,
             station_normalized = excluded.station_normalized,
             ip_address = excluded.ip_address,
             part1_score = excluded.part1_score,
             part2_score = excluded.part2_score,
             part3_score = excluded.part3_score,
             score = excluded.score,
             total = excluded.total,
             results_json = excluded.results_json,
             submitted_at = excluded.submitted_at'
    );
    $insert->execute([
        'submission_id' => $submissionId,
        'student_id' => $officialStudent['student_id'],
        'student_name' => $officialStudent['full_name'],
        'student_name_normalized' => normalizeStudentName($officialStudent['full_name']),
        'station' => $officialStudent['assigned_station'],
        'station_normalized' => $officialStudent['assigned_station'],
        'ip_address' => $ipAddress,
        'part1_score' => $partOneScore,
        'part2_score' => $partTwoScore,
        'part3_score' => $partThreeScore,
        'score' => $overallScore,
        'total' => 15,
        'results_json' => json_encode($studentResults, JSON_THROW_ON_ERROR),
        'submitted_at' => gmdate('Y-m-d H:i:s'),
    ]);
    $pdo->commit();
} catch (PDOException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($error->getCode() === '23000') {
        finishStudentSession(['duplicate_notice' => true]);
        header('Location: already-submitted.php');
        exit;
    }
    error_log('EduSchedX hosted submission error: ' . $error->getMessage());
    $_SESSION['submit_error'] = 'The activity could not be submitted. Try again.';
    header('Location: activity.php');
    exit;
}

$receipt = [
    'submission_id' => $submissionId,
    'full_name' => (string) $officialStudent['full_name'],
    'student_id' => (string) $officialStudent['student_id'],
    'assigned_station' => (string) $officialStudent['assigned_station'],
];
$_SESSION['submission_receipt'] = $receipt;
recordActivityPart(2, $partTwoScore, $grade['results']);
$_SESSION['part_two_complete'] = true;
$_SESSION['part_two_just_completed'] = true;
unset($_SESSION['part_two_ready']);
header('Location: submitted.php');
exit;

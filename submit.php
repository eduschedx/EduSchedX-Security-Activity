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
$studentResults = $grade['results'];
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
    ['submission_id', $submissionId],
    ['student_id', $officialStudent['student_id']],
    ['station_normalized', $officialStudent['assigned_station']],
];
foreach ($duplicateChecks as [$column, $value]) {
    $check = $pdo->prepare("SELECT 1 FROM activity_submissions WHERE {$column} = :value LIMIT 1");
    $check->execute(['value' => $value]);
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
         VALUES (:type, :value, :submission_id)'
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
          ip_address, score, total, results_json, submitted_at)
         VALUES (:submission_id, :student_id, :student_name, :student_name_normalized, :station,
                 :station_normalized, :ip_address, :score, :total, :results_json, :submitted_at)'
    );
    $insert->execute([
        'submission_id' => $submissionId,
        'student_id' => $officialStudent['student_id'],
        'student_name' => $officialStudent['full_name'],
        'student_name_normalized' => normalizeStudentName($officialStudent['full_name']),
        'station' => $officialStudent['assigned_station'],
        'station_normalized' => $officialStudent['assigned_station'],
        'ip_address' => $ipAddress,
        'score' => $grade['score'],
        'total' => $grade['total'],
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
finishStudentSession(['submission_receipt' => $receipt]);
header('Location: submitted.php');
exit;

<?php
require dirname(__DIR__) . '/config.php';
requireAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

$statement = database()->prepare('SELECT * FROM activity_submissions WHERE id = :id');
$statement->execute(['id' => $id]);
$submission = $statement->fetch();
if (!$submission) {
    http_response_code(404);
    exit('Submission not found.');
}
$decodedResults = json_decode($submission['results_json'], true) ?: [];
$runtimeState = [];
if (!empty($submission['student_id'])) {
    $runtimeStatement = database()->prepare('SELECT state_json FROM student_activity_runtime WHERE student_id = :student_id');
    $runtimeStatement->execute(['student_id' => (string) $submission['student_id']]);
    $runtimeState = json_decode((string) ($runtimeStatement->fetchColumn() ?: '{}'), true) ?: [];
}
$parts = isset($decodedResults['parts']) && is_array($decodedResults['parts'])
    ? $decodedResults['parts']
    : [
        'part1' => ['title' => 'Security Matching', 'score' => (int) ($submission['part1_score'] ?? 0), 'total' => 5, 'results' => []],
        'part2' => ['title' => 'PHP Coding', 'score' => (int) ($submission['part2_score'] ?? $submission['score']), 'total' => 5, 'results' => $decodedResults],
        'part3' => ['title' => 'Security Challenge', 'score' => (int) ($submission['part3_score'] ?? 0), 'total' => 5, 'results' => [], 'status' => 'Coming Soon'],
    ];

function resultQuestion(string $partKey, array $result): string
{
    if (!empty($result['question'])) return (string) $result['question'];
    $title = (string) ($result['title'] ?? '');
    $questions = [
        'Session Status Check' => 'Sort each session status by whether access should continue.',
        'Faculty Account Access Check' => 'Sort each Faculty account condition based on whether the security check should continue or deny access.',
        'Secure Input Processing' => 'Match each input-handling action as secure or insecure.',
        'Safe Error Messages' => 'Separate safe user messages from technical server details.',
        'Failed Login Severity Check' => 'Match each failed-login attempt to its correct security severity level.',
        'Session Security' => 'Type the PHP condition that denies access for an invalid authenticated session.',
        'Authorization' => 'Type the PHP condition that denies unauthorized roles access to the Admin Dashboard.',
        'Secure Input Validation' => 'Type the PHP condition that rejects input which is not valid.',
        'Safe Error Handling' => 'Type the PHP statement that returns a safe error response.',
        'Security Monitoring' => 'Type the PHP condition that assigns critical severity at the failed-login limit.',
    ];
    if ($partKey === 'part3') return 'Complete the missing PHP condition that blocks a Faculty account after 5 failed login attempts.';
    return $questions[$title] ?? $title;
}

function resultExpected(string $partKey, array $result): mixed
{
    if ($partKey === 'part2') {
        return [
            1 => "\$sessionStatus !== 'VALID'",
            2 => '!in_array($role, $allowedRoles, true)',
            3 => "\$inputStatus !== 'VALID'",
            4 => 'return safeErrorResponse()',
            5 => '$failedAttempts >= 5',
        ][(int) ($result['challenge'] ?? 0)] ?? ($result['expected'] ?? 'Not recorded');
    }
    if ($partKey === 'part3') return '$failedAttempts >= 5';
    return $result['expected'] ?? 'Not recorded';
}

function resultValueHtml(mixed $value): string
{
    if (!is_array($value)) return '<code>' . adminEscape((string) $value) . '</code>';
    if ($value === []) return '<span class="result-not-recorded">Not recorded</span>';
    $items = '';
    foreach ($value as $item => $answer) {
        $items .= '<li><span>' . adminEscape(str_replace('_', ' ', (string) $item)) . '</span><strong>' . adminEscape(str_replace('_', ' ', (string) $answer)) . '</strong></li>';
    }
    return '<ul class="admin-answer-map">' . $items . '</ul>';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Result | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/instructor-server/style.css" rel="stylesheet">
</head>
<body class="admin-body">
    <main class="admin-shell">
        <div class="admin-content-actions"><a class="btn btn-sm btn-glass" href="dashboard.php"><i class="bi bi-arrow-left"></i> Back to Results</a></div>
        <section class="student-result-head">
            <div><span>Student</span><strong><?= adminEscape($submission['student_name']) ?></strong></div>
            <div><span>Station</span><strong><?= adminEscape($submission['station']) ?></strong></div>
            <div><span>Student ID</span><strong><?= adminEscape((string) ($submission['student_id'] ?? '')) ?></strong></div>
            <div><span>IP Address</span><strong><?= adminEscape(formatClientIp($submission['ip_address'] ?? null)) ?></strong></div>
            <div><span>Part I</span><strong><?= (int) ($submission['part1_score'] ?? 0) ?>/5</strong></div>
            <div><span>Part II</span><strong><?= (int) ($submission['part2_score'] ?? 0) ?>/5</strong></div>
            <div><span>Part III</span><strong><?= (int) ($submission['part3_score'] ?? 0) ?>/5</strong></div>
            <div><span>Overall</span><strong><?= (int) $submission['score'] ?>/15</strong></div>
        </section>
        <div class="part-result-list">
            <?php foreach (['part1' => 'Part I', 'part2' => 'Part II', 'part3' => 'Part III'] as $partKey => $partLabel): $part = $parts[$partKey] ?? []; $partResults = is_array($part['results'] ?? null) ? $part['results'] : []; ?>
                <section class="admin-table-card part-result-card">
                    <div class="part-result-heading"><div><span><?= $partLabel ?></span><h2><?= adminEscape((string) ($part['title'] ?? 'Activity')) ?></h2></div><strong><?= (int) ($part['score'] ?? 0) ?>/<?= (int) ($part['total'] ?? 5) ?></strong></div>
                    <?php if ($partResults !== []): ?>
                        <div class="admin-result-details">
                        <?php foreach ($partResults as $result): $passed = !empty($result['passed']); $answer = $result['answer'] ?? ($partKey === 'part3' ? ($runtimeState['part_three_draft'] ?? 'Not recorded') : 'Not recorded'); ?>
                            <details class="admin-result-detail">
                                <summary><span><?= adminEscape((string) ($result['title'] ?? ('Challenge ' . ($result['challenge'] ?? '')))) ?></span><span class="admin-result-status <?= $passed ? 'case-pass' : 'case-fail' ?>"><i class="bi <?= $passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i><?= $passed ? 'Correct' : 'Needs Review' ?></span><i class="bi bi-chevron-down admin-result-chevron" aria-hidden="true"></i></summary>
                                <div class="admin-result-detail-body">
                                    <div class="admin-question-text"><span>Question</span><p><?= adminEscape(resultQuestion($partKey, $result)) ?></p></div>
                                    <div class="admin-answer-column"><span>Student Answer</span><?= resultValueHtml($answer) ?></div>
                                    <div class="admin-answer-column expected"><span>Correct Answer / Code</span><?= resultValueHtml(resultExpected($partKey, $result)) ?></div>
                                    <p class="admin-attempts"><i class="bi bi-arrow-repeat"></i> <?= (int) ($result['attempts'] ?? ($partKey === 'part3' ? ($runtimeState['part_three_attempts'] ?? 0) : 0)) ?> of 2 attempts used</p>
                                </div>
                            </details>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="part-result-empty"><?= adminEscape((string) ($part['status'] ?? 'No detailed results were recorded.')) ?></p>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>

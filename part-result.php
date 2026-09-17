<?php
require __DIR__ . '/config.php';
requireOpenActivity();
require_once __DIR__ . '/grader.php';

$part = filter_input(INPUT_GET, 'part', FILTER_VALIDATE_INT);
$partIsAvailable = match ($part) {
    1 => !empty($_SESSION['security_part_complete']),
    2 => !empty($_SESSION['part_two_complete']),
    3 => !empty($_SESSION['part_three_complete']),
    default => false,
};
if (!$partIsAvailable) {
    header('Location: levels.php');
    exit;
}

$recordStatement = database()->prepare(
    'SELECT score, results_json FROM activity_part_records
     WHERE student_id = :student_id AND part_number = :part_number
     ORDER BY completed_at DESC LIMIT 1'
);
$recordStatement->execute([
    'student_id' => normalizeStudentId((string) $_SESSION['student_id']),
    'part_number' => $part,
]);
$record = $recordStatement->fetch();
$storedResults = $record ? json_decode((string) $record['results_json'], true) : [];
$storedResults = is_array($storedResults) ? $storedResults : [];
$storedScore = $record ? (int) $record['score'] : null;

// Part 2 is also stored inside the submitted coding result. Prefer that
// dedicated section so the review never reuses Part 1 matching data.
if ($part === 2) {
    $submissionStatement = database()->prepare(
        'SELECT part2_score, results_json FROM activity_submissions
         WHERE student_id = :student_id
         ORDER BY submitted_at DESC LIMIT 1'
    );
    $submissionStatement->execute([
        'student_id' => normalizeStudentId((string) $_SESSION['student_id']),
    ]);
    $submission = $submissionStatement->fetch();
    if ($submission) {
        $submissionResults = json_decode((string) $submission['results_json'], true);
        $partTwoResults = $submissionResults['parts']['part2']['results'] ?? null;
        if (is_array($partTwoResults)) {
            $storedResults = $partTwoResults;
            $storedScore = (int) $submission['part2_score'];
        }
    }
}
$definitions = match ($part) {
    1 => securityUnlockDefinitions(),
    2 => challengeDefinitions(),
    3 => [
        1 => ['title' => 'Severity', 'description' => 'The generated severity matches the target interface.'],
        2 => ['title' => 'User Type', 'description' => 'The generated user type matches the Faculty account.'],
        3 => ['title' => 'Failed Attempts', 'description' => 'The generated failed-attempt count matches the incident.'],
        4 => ['title' => 'Access Status', 'description' => 'The generated access decision matches the target.'],
        5 => ['title' => 'Security Response', 'description' => 'The generated message and response match the target.'],
    ],
};
$resultsByChallenge = [];
$detailsByChallenge = [];
foreach ($storedResults as $storedResult) {
    $resultId = (int) ($storedResult['challenge'] ?? 0);
    if ($resultId >= 1 && $resultId <= 5) {
        $resultsByChallenge[$resultId] = !empty($storedResult['passed']);
        $detailsByChallenge[$resultId] = $storedResult;
    }
}
if ($part === 1 && $resultsByChallenge === []) {
    foreach (array_keys($definitions) as $questionId) $resultsByChallenge[$questionId] = !empty($_SESSION['security_unlock_result'][$questionId]);
}
$targetLabel = static function (array $definition, string $target): string {
    return (string) ($definition['result_labels'][$target] ?? $definition['targets'][$target] ?? $target ?: 'Not placed');
};
$score = $storedScore ?? count(array_filter($resultsByChallenge));
$partTitle = [1 => 'Security Matching', 2 => 'PHP Coding', 3 => 'Security Alert Simulator'][$part];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Part <?= $part ?> Results | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="level-page part-review-page">
    <?= studentBrandHeader() ?>
    <main class="activity-shell">
        <section class="part-review-board">
            <a class="level-back" href="levels.php" aria-label="Back to level selection"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
            <div class="part-review-heading">
                <div><span class="eyebrow">Part <?= $part ?> Results</span><h1><?= escape($partTitle) ?></h1><p>Review the result for each completed <?= $part === 1 ? 'security question' : ($part === 2 ? 'coding challenge' : 'interface check') ?>.</p></div>
                <strong><?= $score ?>/5</strong>
            </div>
            <div class="part-review-list">
                <?php foreach ($definitions as $questionId => $question):
                    $passed = !empty($resultsByChallenge[$questionId]);
                    $detail = (array) ($detailsByChallenge[$questionId] ?? []);
                    $studentAnswer = $part === 1 ? (array) ($detail['answer'] ?? $_SESSION['security_unlock_draft'][$questionId] ?? []) : (string) ($detail['answer'] ?? $_SESSION['answer_draft'][$questionId] ?? '');
                    $expectedAnswer = $part === 1 ? (array) ($detail['expected'] ?? $question['answer']) : ($part === 2 ? (string) ($detail['expected'] ?? $question['expected']) : '');
                    $attemptCount = (int) ($detail['attempts'] ?? ($part === 1 ? ($_SESSION['security_unlock_attempts'][$questionId] ?? 0) : ($_SESSION['challenge_attempts'][$questionId] ?? 0)));
                ?>
                    <details class="part-review-item <?= $passed ? 'is-correct' : 'is-incorrect' ?>">
                        <summary>
                            <span class="part-review-number"><?= $questionId ?></span>
                            <div><h2><?= escape($question['title']) ?></h2><p><?= escape($question['description']) ?></p></div>
                            <span class="part-review-status"><i class="bi <?= $passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>" aria-hidden="true"></i><?= $part === 2 ? ($passed ? 'PHP Check Passed' : 'PHP Check Failed') : ($passed ? 'Correct' : 'Needs Review') ?></span>
                            <i class="bi bi-chevron-down part-review-chevron" aria-hidden="true"></i>
                        </summary>
                        <div class="part-review-details">
                            <?php if ($part !== 3): ?><p class="part-review-attempts"><?= max(0, $attemptCount) ?> of 2 attempts used</p><?php endif; ?>
                            <?php if ($part === 1): ?>
                            <div class="review-answer-grid">
                                <section><h3>Your Answer</h3><?php foreach ($question['cards'] as $cardId => $cardLabel): $chosenTarget = (string) ($studentAnswer[$cardId] ?? ''); $itemCorrect = $chosenTarget !== '' && $chosenTarget === (string) ($expectedAnswer[$cardId] ?? ''); ?><div class="review-answer-line <?= $itemCorrect ? 'is-right' : 'is-wrong' ?>"><span><?= escape($cardLabel) ?></span><strong><?= escape($targetLabel($question, $chosenTarget)) ?></strong><i class="bi <?= $itemCorrect ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>" aria-hidden="true"></i></div><?php endforeach; ?></section>
                                <section><h3>Correct Answer</h3><?php foreach ($question['cards'] as $cardId => $cardLabel): ?><div class="review-answer-line is-right"><span><?= escape($cardLabel) ?></span><strong><?= escape($targetLabel($question, (string) ($expectedAnswer[$cardId] ?? ''))) ?></strong><i class="bi bi-check-circle-fill" aria-hidden="true"></i></div><?php endforeach; ?></section>
                            </div>
                            <?php elseif ($part === 2): ?>
                            <div class="review-code-answer"><span>Your submitted PHP code</span><code><?= escape($studentAnswer !== '' ? $studentAnswer : 'No answer recorded') ?></code></div>
                            <div class="review-output-grid"><div><span>Actual Result</span><strong><?= escape((string) ($detail['actual'] ?? 'Not recorded')) ?></strong></div><div><span>Expected Result</span><strong><?= escape($expectedAnswer) ?></strong></div></div>
                            <?php else: ?>
                            <div class="review-output-grid"><div><span>Your Result</span><strong><?= $passed ? 'Matched target interface' : 'Did not match target interface' ?></strong></div><div><span>Expected Result</span><strong>Match required</strong></div></div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>

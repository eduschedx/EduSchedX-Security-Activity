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
$parts = isset($decodedResults['parts']) && is_array($decodedResults['parts'])
    ? $decodedResults['parts']
    : [
        'part1' => ['title' => 'Security Matching', 'score' => (int) ($submission['part1_score'] ?? 0), 'total' => 5, 'results' => []],
        'part2' => ['title' => 'PHP Coding', 'score' => (int) ($submission['part2_score'] ?? $submission['score']), 'total' => 5, 'results' => $decodedResults],
        'part3' => ['title' => 'Security Challenge', 'score' => (int) ($submission['part3_score'] ?? 0), 'total' => 5, 'results' => [], 'status' => 'Coming Soon'],
    ];
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
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Challenge</th><th>Result</th></tr></thead>
                                <tbody>
                                <?php foreach ($partResults as $result): $passed = !empty($result['passed']); ?>
                                    <tr><td><?= adminEscape((string) ($result['title'] ?? ('Challenge ' . ($result['challenge'] ?? '')))) ?></td><td class="<?= $passed ? 'case-pass' : 'case-fail' ?>"><i class="bi <?= $passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
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

<?php
require dirname(__DIR__) . '/config.php';
requireAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

$statement = database()->prepare('SELECT * FROM activity_part_records WHERE id = :id');
$statement->execute(['id' => $id]);
$record = $statement->fetch();
if (!$record) {
    http_response_code(404);
    exit('Part record not found.');
}

$results = json_decode((string) $record['results_json'], true) ?: [];
$romanPart = ['I', 'II', 'III'][(int) $record['part_number'] - 1] ?? (string) $record['part_number'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Part <?= adminEscape($romanPart) ?> Result | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/instructor-server/style.css" rel="stylesheet">
</head>
<body class="admin-body">
    <main class="admin-shell">
        <div class="admin-content-actions"><a class="btn btn-sm btn-glass" href="dashboard.php"><i class="bi bi-arrow-left"></i> Back to Results</a></div>
        <section class="student-result-head">
            <div><span>Student</span><strong><?= adminEscape((string) $record['student_name']) ?></strong></div>
            <div><span>Student ID</span><strong><?= adminEscape((string) $record['student_id']) ?></strong></div>
            <div><span>Station</span><strong><?= adminEscape((string) $record['station']) ?></strong></div>
            <div><span>Completed Part</span><strong>Part <?= adminEscape($romanPart) ?></strong></div>
            <div><span>Score</span><strong><?= (int) $record['score'] ?>/<?= (int) $record['total'] ?></strong></div>
            <div><span>Completed</span><strong><?= adminEscape(formatSubmissionTime((string) $record['completed_at'])) ?></strong></div>
        </section>
        <section class="admin-table-card part-result-card">
            <div class="part-result-heading"><div><span>Part <?= adminEscape($romanPart) ?></span><h2>Item Results</h2></div><strong><?= (int) $record['score'] ?>/<?= (int) $record['total'] ?></strong></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Item</th><th>Result</th></tr></thead>
                    <tbody>
                    <?php foreach ($results as $result): $passed = !empty($result['passed']); ?>
                        <tr><td><?= adminEscape((string) ($result['title'] ?? ('Item ' . ($result['challenge'] ?? '')))) ?></td><td class="<?= $passed ? 'case-pass' : 'case-fail' ?>"><i class="bi <?= $passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>

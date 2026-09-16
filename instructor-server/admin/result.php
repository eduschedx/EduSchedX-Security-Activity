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
$results = json_decode($submission['results_json'], true) ?: [];
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
            <div><span>Final Score</span><strong><?= (int) $submission['score'] ?>/<?= (int) $submission['total'] ?></strong></div>
        </section>
        <section class="admin-table-card feedback-detail">
            <h2>Student Feedback / Comments</h2>
            <p><?= !empty($submission['feedback']) ? adminEscape((string) $submission['feedback']) : 'No feedback submitted.' ?></p>
        </section>
        <section class="admin-table-card">
            <h2 class="fs-6 fw-bold px-3 pt-3 mb-2">Challenge Results</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Challenge</th><th>Result</th></tr></thead>
                    <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr><td><?= adminEscape((string) ($result['title'] ?? ('Challenge ' . ($result['challenge'] ?? '')))) ?></td><td class="<?= $result['passed'] ? 'case-pass' : 'case-fail' ?>"><i class="bi <?= $result['passed'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>

<?php
require dirname(__DIR__) . '/config.php';
requireAdmin();

$success = (string) ($_SESSION['admin_success'] ?? '');
unset($_SESSION['admin_success']);

$search = trim((string) ($_GET['search'] ?? ''));
$pdo = database();

if ($search !== '') {
    $statement = $pdo->prepare(
        'SELECT * FROM activity_submissions
         WHERE student_name LIKE :search OR student_id LIKE :search OR station LIKE :search OR feedback LIKE :search
         ORDER BY submitted_at DESC'
    );
    $statement->execute(['search' => '%' . $search . '%']);
    $submissions = $statement->fetchAll();
} else {
    $submissions = $pdo->query('SELECT * FROM activity_submissions ORDER BY submitted_at DESC')->fetchAll();
}

$summary = $pdo->query('SELECT COUNT(*) AS total_submitted, AVG(score) AS average_score FROM activity_submissions')->fetch();
$highest = $pdo->query('SELECT score, total FROM activity_submissions ORDER BY score DESC, submitted_at ASC LIMIT 1')->fetch();
$lowest = $pdo->query('SELECT score, total FROM activity_submissions ORDER BY score ASC, submitted_at ASC LIMIT 1')->fetch();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coding Activity Results | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/instructor-server/style.css" rel="stylesheet">
</head>
<body class="admin-body">
    <main class="admin-shell">
        <div class="admin-title-row">
            <div class="admin-title"><span class="eyebrow">Activity Admin</span><h1>Coding Activity Results</h1></div>
            <form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>"><button class="btn btn-sm btn-glass" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button></form>
        </div>
        <?php if ($success !== ''): ?><div class="alert alert-success admin-alert" role="status" data-auto-dismiss="60000"><i class="bi bi-check-circle"></i><?= adminEscape($success) ?></div><?php endif; ?>
        <section class="summary-grid">
            <div><span>Total Submitted</span><strong><?= (int) $summary['total_submitted'] ?></strong></div>
            <div><span>Average Score</span><strong><?= $summary['average_score'] === null ? '—' : number_format((float) $summary['average_score'], 1) ?></strong></div>
            <div><span>Highest Score</span><strong><?= !$highest ? '—' : (int) $highest['score'] . '/' . (int) $highest['total'] ?></strong></div>
            <div><span>Lowest Score</span><strong><?= !$lowest ? '—' : (int) $lowest['score'] . '/' . (int) $lowest['total'] ?></strong></div>
        </section>
        <section class="admin-table-card">
            <form class="search-form" method="get"><i class="bi bi-search"></i><input class="form-control" name="search" value="<?= adminEscape($search) ?>" placeholder="Search student, ID, or station"><button class="btn btn-eduschedx" type="submit">Search</button></form>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Student</th><th>Student ID</th><th>Station</th><th>IP Address</th><th>Score</th><th>Feedback / Comments</th><th>Submitted</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if ($submissions === []): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No submissions found.</td></tr>
                    <?php else: foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= adminEscape($submission['student_name']) ?></td>
                            <td><?= adminEscape((string) ($submission['student_id'] ?? '')) ?></td>
                            <td><?= adminEscape($submission['station']) ?></td>
                            <td><?= adminEscape(formatClientIp($submission['ip_address'] ?? null)) ?></td>
                            <td><strong><?= (int) $submission['score'] ?>/<?= (int) $submission['total'] ?></strong></td>
                            <td class="feedback-cell"><?= !empty($submission['feedback']) ? adminEscape((string) $submission['feedback']) : '&mdash;' ?></td>
                            <td><?= adminEscape(formatSubmissionTime((string) $submission['submitted_at'])) ?></td>
                            <td><div class="table-actions">
                                <a class="btn btn-sm btn-outline-success" href="result.php?id=<?= (int) $submission['id'] ?>"><i class="bi bi-eye"></i> View</a>
                                <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteModal" data-submission-id="<?= (int) $submission['id'] ?>"><i class="bi bi-trash"></i> Delete</button>
                            </div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content admin-modal">
            <form action="delete.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
                <input type="hidden" name="submission_id" value="">
                <div class="modal-header"><h2 class="modal-title fs-5" id="deleteModalTitle">Delete this submission?</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">This will permanently remove the student's submitted result.</div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Yes, Delete</button></div>
            </form>
        </div></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/instructor-server/admin/admin.js"></script>
</body>
</html>

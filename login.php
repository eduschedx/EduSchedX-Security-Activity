<?php
require __DIR__ . '/config.php';

$error = '';
$studentId = '';
unset($_SESSION['submission_receipt'], $_SESSION['duplicate_notice']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = strtoupper(trim((string) ($_POST['student_id'] ?? '')));
    $activityCode = strtoupper(trim((string) ($_POST['activity_code'] ?? '')));

    if (!csrfIsValid($_POST['csrf_token'] ?? null)) {
        $error = 'Please refresh and try again.';
    } elseif (!preg_match('/^\d{2}-\d-\d{5}$/', $studentId)
        || !preg_match('/^[A-HJ-NP-Z2-9]{6}$/', $activityCode)) {
        $error = 'Student information could not be verified.';
    } else {
        $rosterStudent = verifyRosterStudent($studentId, $activityCode);
        if ($rosterStudent === null) {
            $error = 'Student information could not be verified.';
        } else {
            $duplicate = database()->prepare(
                'SELECT 1 FROM activity_submissions
                 WHERE student_id = :student_id OR station_normalized = :station LIMIT 1'
            );
            $duplicate->execute([
                'student_id' => $rosterStudent['student_id'],
                'station' => $rosterStudent['assigned_station'],
            ]);
            if ($duplicate->fetchColumn() !== false) {
                finishStudentSession(['duplicate_notice' => true]);
                header('Location: already-submitted.php');
                exit;
            }

            session_regenerate_id(true);
            $_SESSION = [];
            $studentId = (string) $rosterStudent['student_id'];
            $_SESSION['student_id'] = $studentId;
            $_SESSION['full_name'] = (string) $rosterStudent['full_name'];
            $_SESSION['assigned_station'] = (string) $rosterStudent['assigned_station'];
            $_SESSION['student_verified'] = true;
            $_SESSION['submission_id'] = bin2hex(random_bytes(16));
            unset($_SESSION['student_name'], $_SESSION['station'], $_SESSION['student_code'], $_SESSION['submitted'], $_SESSION['already_submitted'], $_SESSION['manual_result']);
            header('Location: welcome.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Information | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <header class="brand-header">
        <a href="index.php" class="brand-link"><img src="images/eduschedx-logo.svg" alt="" class="brand-logo"><span class="brand-name">EduSched<span>X</span></span></a>
    </header>
    <main class="activity-shell">
        <section class="activity-card compact-card">
            <?= studentProgress('Student Info') ?>
            <div class="screen-panel">
                <div class="panel-heading">
                    <h1>Student Information</h1>
                    <p>Enter your details to begin.</p>
                </div>
                <?php if ($error !== ''): ?><div class="alert alert-danger py-2" data-auto-dismiss="60000"><?= escape($error) ?></div><?php endif; ?>
                    <form method="post" class="student-info-form">
                        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                        <div><label class="form-label" for="student_id">Student ID</label><input class="form-control" id="student_id" name="student_id" maxlength="10" value="<?= escape($studentId) ?>" placeholder="XX-X-XXXXX" pattern="[0-9]{2}-[0-9]-[0-9]{5}" autocomplete="off" data-student-id required autofocus></div>
                        <div><label class="form-label" for="activity_code">Activity Code</label><input class="form-control activity-code-input" id="activity_code" name="activity_code" maxlength="6" minlength="6" pattern="[A-HJ-NP-Z2-9]{6}" placeholder="XXXXXX" autocomplete="one-time-code" data-activity-code required></div>
                        <button class="btn btn-eduschedx w-100" type="submit">Verify &amp; Continue <i class="bi bi-arrow-right ms-1"></i></button>
                    </form>
            </div>
        </section>
    </main>
    <script src="app.js"></script>
</body>
</html>

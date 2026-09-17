<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && empty($_GET['tab_required'])
    && !empty($_SESSION['student_verified'])) {
    finishStudentSession();
}

$error = '';
$studentId = '';
$logoutNotice = (string) ($_SESSION['logout_notice'] ?? '');
unset($_SESSION['logout_notice']);
unset($_SESSION['submission_receipt'], $_SESSION['duplicate_notice']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentIdDigits = preg_replace('/\D+/', '', (string) ($_POST['student_id'] ?? '')) ?? '';
    $studentId = strlen($studentIdDigits) === 8
        ? substr($studentIdDigits, 0, 2) . '-' . substr($studentIdDigits, 2, 1) . '-' . substr($studentIdDigits, 3, 5)
        : trim((string) ($_POST['student_id'] ?? ''));
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
            $progressStatement = database()->prepare(
                'SELECT submission_id FROM activity_submissions
                 WHERE student_id = :student_id
                 ORDER BY submitted_at DESC LIMIT 1'
            );
            $progressStatement->execute([
                'student_id' => $rosterStudent['student_id'],
            ]);
            $existingSubmissionId = $progressStatement->fetchColumn();

            $partStatement = database()->prepare(
                'SELECT part_number, score, results_json FROM activity_part_records
                 WHERE student_id = :student_id ORDER BY completed_at ASC'
            );
            $partStatement->execute(['student_id' => $rosterStudent['student_id']]);
            $completedParts = $partStatement->fetchAll();

            session_regenerate_id(true);
            $_SESSION = [];
            $studentId = (string) $rosterStudent['student_id'];
            $_SESSION['student_id'] = $studentId;
            $_SESSION['full_name'] = (string) $rosterStudent['full_name'];
            $_SESSION['assigned_station'] = (string) $rosterStudent['assigned_station'];
            $_SESSION['student_verified'] = true;
            $_SESSION['submission_id'] = is_string($existingSubmissionId) && $existingSubmissionId !== '' ? $existingSubmissionId : bin2hex(random_bytes(16));
            foreach ($completedParts as $completedPart) {
                $partNumber = (int) $completedPart['part_number'];
                $partResults = json_decode((string) $completedPart['results_json'], true) ?: [];
                if ($partNumber === 1) {
                    $_SESSION['security_part_complete'] = true;
                    $_SESSION['security_part_step'] = 5;
                    $_SESSION['challenge_version'] = 6;
                    foreach ($partResults as $partResult) {
                        $questionId = (int) ($partResult['challenge'] ?? 0);
                        if ($questionId >= 1 && $questionId <= 5) $_SESSION['security_unlock_result'][$questionId] = !empty($partResult['passed']);
                    }
                } elseif ($partNumber === 2) {
                    $_SESSION['part_two_complete'] = true;
                } elseif ($partNumber === 3) {
                    $_SESSION['part_three_complete'] = true;
                }
            }
            unset($_SESSION['student_name'], $_SESSION['station'], $_SESSION['student_code'], $_SESSION['submitted'], $_SESSION['already_submitted'], $_SESSION['manual_result']);
            $_SESSION['tab_bootstrap_token'] = bin2hex(random_bytes(24));
            header('Location: welcome.php?tab_token=' . rawurlencode($_SESSION['tab_bootstrap_token']));
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
    <script>sessionStorage.removeItem('eduschedx_tab_authenticated');</script>
</head>
<body class="login-page">
    <header class="brand-header">
        <a href="index.php" class="brand-link"><img src="images/eduschedx-logo.svg" alt="" class="brand-logo"><span class="brand-name">EduSched<span>X</span></span></a>
    </header>
    <main class="activity-shell">
        <section class="activity-card compact-card login-card">
            <div class="screen-panel login-panel">
                <div class="login-symbol"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i></div>
                <div class="panel-heading login-heading">
                    <span class="eyebrow">Secure Activity Access</span>
                    <h1>Student Information</h1>
                    <p>Verify your student details to begin the security activity.</p>
                </div>
                <?php if ($logoutNotice !== ''): ?><div class="alert alert-success login-alert" role="status" data-auto-dismiss="10000"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><?= escape($logoutNotice) ?></div><?php endif; ?>
                <?php if ($error !== ''): ?><div class="alert alert-danger py-2" data-auto-dismiss="60000"><?= escape($error) ?></div><?php endif; ?>
                    <form method="post" class="student-info-form">
                        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                        <div><label class="form-label" for="student_id">Student ID</label><div class="login-field"><i class="bi bi-person-vcard" aria-hidden="true"></i><input class="form-control" id="student_id" name="student_id" maxlength="10" value="<?= escape($studentId) ?>" placeholder="XX-X-XXXXX" pattern="[0-9]{2}-[0-9]-[0-9]{5}" inputmode="numeric" autocomplete="off" data-student-id required autofocus></div></div>
                        <div><label class="form-label" for="activity_code">Activity Code</label><div class="login-field"><i class="bi bi-key-fill" aria-hidden="true"></i><input class="form-control activity-code-input" id="activity_code" name="activity_code" maxlength="6" minlength="6" pattern="[A-HJ-NP-Z2-9]{6}" placeholder="XXXXXX" autocomplete="one-time-code" data-activity-code required></div></div>
                        <button class="btn btn-eduschedx w-100" type="submit">Verify &amp; Continue <i class="bi bi-arrow-right ms-1"></i></button>
                    </form>
                    <p class="login-privacy"><i class="bi bi-lock-fill" aria-hidden="true"></i> Your assigned activity details are verified securely.</p>
            </div>
        </section>
    </main>
    <script src="app.js"></script>
</body>
</html>

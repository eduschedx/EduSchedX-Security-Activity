<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
requireStudent();
require_once __DIR__ . '/grader.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfIsValid($_POST['csrf_token'] ?? null)) { header('Location: part3.php'); exit; }
if (empty($_SESSION['part_two_complete'])) { header('Location: levels.php'); exit; }
if (time() >= (int) ($_SESSION['part_three_deadline'] ?? 0)) { expirePartThree(); header('Location: part3.php'); exit; }
if (!empty($_SESSION['part_three_complete']) || !empty($_SESSION['part_three_ready']) || (int) ($_SESSION['part_three_attempts'] ?? 0) >= 2) { header('Location: part3.php'); exit; }

$condition = trim((string) ($_POST['condition'] ?? ''));
if ($condition === '') {
    $_SESSION['part_three_error'] = 'Type the missing PHP condition before running the security check.';
    header('Location: part3.php'); exit;
}
if (mb_strlen($condition) > 180) {
    $_SESSION['part_three_error'] = 'The PHP condition is too long.';
    header('Location: part3.php'); exit;
}

try {
    // Part III is intentionally all-or-nothing: only the exact required
    // expression (ignoring whitespace) can generate the target interface.
    $isExactAnswer = hash_equals('$failedAttempts>=5', normalizePhpAnswer($condition));
    if ($isExactAnswer) {
        $generated = ['recognized'=>true,'critical'=>true,'severity'=>'CRITICAL','user_type'=>'Faculty Account','failed_attempts'=>5,'access'=>'BLOCKED','message'=>'Suspicious login activity detected.','response'=>'The account has been temporarily restricted. The event has been logged and the Superadmin has been notified.'];
    } else {
        $generated = ['recognized'=>false,'critical'=>false,'severity'=>'UNRECOGNIZED','user_type'=>'Unknown','failed_attempts'=>0,'access'=>'UNKNOWN','message'=>'The PHP condition was not recognized.','response'=>'No security interface could be generated.'];
    }

    $checks = array_fill_keys(['Severity', 'User Type', 'Failed Attempts', 'Access Status', 'Security Response'], $isExactAnswer);
    $results = [];
    foreach ($checks as $title => $passed) $results[] = ['challenge'=>count($results)+1,'title'=>$title,'passed'=>$passed];
    $score = $isExactAnswer ? 5 : 0;
    $newAttemptCount = (int) ($_SESSION['part_three_attempts'] ?? 0) + 1;
    $isFinal = $score === 5 || $newAttemptCount >= 2;

    $_SESSION['part_three_attempts'] = $newAttemptCount;
    $_SESSION['part_three_draft'] = $condition;
    $_SESSION['part_three_generated'] = $generated;
    $_SESSION['part_three_results'] = $results;
    $_SESSION['part_three_score'] = $score;
    if ($isFinal) $_SESSION['part_three_ready'] = true;
} catch (Throwable $error) {
    error_log('EduSchedX Part III grading error: ' . $error->getMessage());
    $_SESSION['part_three_error'] = 'The security check could not be completed. Your attempt was not used.';
}

header('Location: part3.php');
exit;

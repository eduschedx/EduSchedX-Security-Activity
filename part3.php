<?php
require __DIR__ . '/config.php';
requireStudent();
if (empty($_SESSION['part_two_complete'])) { header('Location: levels.php'); exit; }

if (!isset($_SESSION['part_three_deadline']) || (int) $_SESSION['part_three_deadline'] > time() + 60) {
    $_SESSION['part_three_deadline'] = time() + 60;
}
$deadline = (int) $_SESSION['part_three_deadline'];
if (empty($_SESSION['part_three_complete']) && empty($_SESSION['part_three_ready']) && time() >= $deadline) expirePartThree();

$attempts = max(0, min(2, (int) ($_SESSION['part_three_attempts'] ?? 0)));
$attemptsRemaining = max(0, 2 - $attempts);
$completed = !empty($_SESSION['part_three_complete']);
$readyToSubmit = !empty($_SESSION['part_three_ready']);
$showThankYou = $completed && !empty($_SESSION['part_three_just_completed']);
unset($_SESSION['part_three_just_completed']);
$locked = $completed || $readyToSubmit || $attemptsRemaining === 0;
$answer = (string) ($_SESSION['part_three_draft'] ?? '');
if (!empty($_SESSION['part_three_timed_out']) && trim($answer) === '') unset($_SESSION['part_three_generated']);
$generated = is_array($_SESSION['part_three_generated'] ?? null) ? $_SESSION['part_three_generated'] : null;
$score = (int) ($_SESSION['part_three_score'] ?? 0);
$error = (string) ($_SESSION['part_three_error'] ?? '');
unset($_SESSION['part_three_error']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Security Alert Simulator | EduSchedX</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet"><link href="style.css" rel="stylesheet"><script src="app.js" defer></script></head>
<body class="incident-page alert-simulator-page <?= $showThankYou ? 'part-three-submitted' : '' ?>"><?= studentBrandHeader() ?><main class="incident-shell alert-simulator-shell">
<a class="incident-back" href="levels.php" aria-label="Back to levels"><i class="bi bi-arrow-left"></i></a>
<header class="sim-heading"><span class="eyebrow">Final Practical Activity</span><h1>Security Alert Simulator</h1><p>Complete the PHP condition and generate the correct EduSchedX security response.</p></header>

<div class="sim-layout">
<div class="sim-left">
<section class="sim-card sim-scenario"><div class="sim-card-title"><span>Scenario</span><strong>Suspicious Faculty Login</strong></div><dl><div><dt>User Type</dt><dd>Faculty Account</dd></div><div><dt>Failed Login Attempts</dt><dd>5</dd></div><div><dt>Time Remaining</dt><dd class="sim-countdown" <?php if (!$locked): ?>data-part-three-timer data-deadline="<?= $deadline ?>"<?php endif; ?>><?= $locked ? '00:00' : '01:00' ?></dd></div></dl><?php if (!$locked): ?><form action="incident-timeout.php" method="post" data-part-three-timeout><input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>"></form><?php endif; ?></section>

<section class="sim-card sim-code-card"><div class="sim-card-title"><span>PHP Coding Area</span><strong>Complete the PHP Code</strong></div>
<form action="incident-submit.php" method="post" data-security-simulator><input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
<div class="sim-code-editor" aria-label="PHP code editor"><code>&lt;?php</code><code>&nbsp;</code><code>$failedAttempts = 5;</code><code>&nbsp;</code><label for="part_three_condition">if (</label><input id="part_three_condition" name="condition" value="<?= escape($answer) ?>" maxlength="180" autocomplete="off" spellcheck="false" data-simulator-code <?= $locked ? 'readonly' : '' ?> required><span>) {</span><code>&nbsp;&nbsp;&nbsp;&nbsp;$severity = 'CRITICAL';</code><code>&nbsp;&nbsp;&nbsp;&nbsp;$access = 'BLOCKED';</code><code>&nbsp;&nbsp;&nbsp;&nbsp;$message = 'Suspicious login activity detected.';</code><code>} else {</code><code>&nbsp;&nbsp;&nbsp;&nbsp;$severity = 'INFO';</code><code>&nbsp;&nbsp;&nbsp;&nbsp;$access = 'ALLOWED';</code><code>&nbsp;&nbsp;&nbsp;&nbsp;$message = 'Normal login activity.';</code><code>}</code></div>
<p class="sim-tip"><i class="bi bi-lightbulb"></i> Type only the missing PHP condition. Copy and paste are disabled.</p>
<?php if ($error !== ''): ?><div class="alert alert-warning sim-error" role="alert"><?= escape($error) ?></div><?php endif; ?>
<div class="sim-code-actions"><button class="btn sim-reset" type="button" data-simulator-reset <?= $locked ? 'disabled' : '' ?>><i class="bi bi-arrow-counterclockwise"></i> Reset Code</button><button class="btn sim-run" type="submit" data-simulator-run <?= $locked ? 'disabled' : '' ?>><i class="bi bi-play-circle"></i> Run Security Check</button></div>
<p class="sim-attempts <?= $attemptsRemaining === 0 ? 'is-empty' : '' ?>"><?= $attemptsRemaining ?> of 2 attempts remaining</p>
</form></section>
</div>

<div class="sim-right">
<section class="sim-card"><div class="sim-card-title"><span>Expected Output</span><strong>Target Interface</strong></div><article class="security-alert-card is-critical"><span class="security-alert-label"><i class="bi bi-shield-exclamation" aria-hidden="true"></i> Security Alert</span><div class="security-profile"><div class="security-profile-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></div><div><strong>Faculty User</strong><span>Faculty Account</span><p>Suspicious login activity detected.</p></div></div><h2>CRITICAL</h2><dl><div><dt><i class="bi bi-key" aria-hidden="true"></i> Failed Login Attempts</dt><dd>5</dd></div><div><dt><i class="bi bi-lock-fill" aria-hidden="true"></i> Access Status</dt><dd>BLOCKED</dd></div><div><dt><i class="bi bi-bell-fill" aria-hidden="true"></i> Response</dt><dd>The account has been temporarily restricted.<br>The event has been logged and the Superadmin has been notified.</dd></div></dl></article></section>

<section class="sim-card sim-result-card"><div class="sim-card-title"><span>Generated Output</span><strong>Your Result</strong></div>
<?php if ($generated === null): ?><div class="sim-empty-result"><i class="bi <?= !empty($_SESSION['part_three_timed_out']) ? 'bi-hourglass-bottom' : 'bi-window' ?>"></i><p><?= !empty($_SESSION['part_three_timed_out']) ? 'Time expired. No security interface was generated because no answer was submitted.' : 'Run your PHP condition to generate the security interface.' ?></p></div>
<?php else: ?><article class="security-alert-card <?= !empty($generated['critical']) ? 'is-critical' : 'is-normal' ?>"><span class="security-alert-label"><i class="bi <?= !empty($generated['critical']) ? 'bi-shield-exclamation' : 'bi-shield-check' ?>" aria-hidden="true"></i> <?= !empty($generated['critical']) ? 'Security Alert' : 'Security Status' ?></span><div class="security-profile"><div class="security-profile-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></div><div><strong>Faculty User</strong><span><?= escape((string) $generated['user_type']) ?></span><p><?= escape((string) $generated['message']) ?></p></div></div><h2><?= escape((string) $generated['severity']) ?></h2><dl><div><dt><i class="bi bi-key" aria-hidden="true"></i> Failed Login Attempts</dt><dd><?= (int) $generated['failed_attempts'] ?></dd></div><div><dt><i class="bi bi-lock-fill" aria-hidden="true"></i> Access Status</dt><dd><?= escape((string) $generated['access']) ?></dd></div><div><dt><i class="bi bi-bell-fill" aria-hidden="true"></i> Response</dt><dd><?= escape((string) $generated['response']) ?></dd></div></dl></article><?php endif; ?>
<?php if ($readyToSubmit): ?><div class="sim-final-result <?= $score === 5 ? 'is-success' : 'is-review' ?>"><span>Final Part 3 Score</span><strong><?= $score === 5 ? 5 : 0 ?>/5</strong><button class="btn btn-eduschedx" type="button" data-part-three-open>Complete Part 3 <i class="bi bi-arrow-right" aria-hidden="true"></i></button></div><?php elseif ($completed && !$showThankYou): ?><a class="btn btn-eduschedx sim-result-continue" href="login.php">Return to Login <i class="bi bi-arrow-right"></i></a><?php endif; ?>
</section>
</div></div>

<?php if ($readyToSubmit): ?>
<dialog class="submit-dialog part-three-dialog" data-part-three-dialog aria-labelledby="part-three-complete-title">
    <div class="part-three-dialog-icon"><i class="bi bi-trophy-fill" aria-hidden="true"></i></div>
    <span class="eyebrow">Part 3 Complete</span>
    <h2 id="part-three-complete-title">Congratulations!</h2>
    <p>You successfully completed the Security Alert Simulator. Submit Part 3 to record your final score.</p>
    <form action="incident-finalize.php" method="post"><input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>"><button class="btn btn-eduschedx" type="submit"><i class="bi bi-send-check-fill" aria-hidden="true"></i> Submit Part 3</button></form>
</dialog>
<?php endif; ?>

<?php if ($showThankYou): ?>
<dialog class="submit-dialog part-three-dialog thank-you-dialog" data-auto-dialog aria-labelledby="part-three-thanks-title">
    <div class="part-three-dialog-icon is-heart"><span aria-hidden="true">🫰</span></div>
    <span class="eyebrow">Activity Submitted</span>
    <h2 id="part-three-thanks-title">Thank you, Classmate! Lab lots! 🫰</h2>
    <p>Your Part 3 result has been recorded. Great work completing the EduSchedX security activity.</p>
    <a class="btn btn-eduschedx" href="login.php?scores=1">Return to Login <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
</dialog>
<?php endif; ?>
</main></body></html>

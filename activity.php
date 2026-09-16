<?php
require __DIR__ . '/config.php';
requireOpenActivity();
require_once __DIR__ . '/grader.php';

$challenges = challengeDefinitions();
if (!isset($_SESSION['challenge_order']) || array_diff(array_keys($challenges), (array) $_SESSION['challenge_order']) !== []) {
    $_SESSION['challenge_order'] = array_keys($challenges);
    shuffle($_SESSION['challenge_order']);
}
$currentStep = max(0, min(4, (int) ($_SESSION['challenge_step'] ?? 0)));
$_SESSION['challenge_step'] = $currentStep;
$challengeId = (int) $_SESSION['challenge_order'][$currentStep];
$challenge = $challenges[$challengeId];
$answers = $_SESSION['answer_draft'] ?? array_fill_keys(array_keys($challenges), '');
$challengeResults = $_SESSION['challenge_result'] ?? [];
$challengeErrors = $_SESSION['challenge_error'] ?? [];
$attemptsUsed = (int) ($_SESSION['challenge_attempts'][$challengeId] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activity | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <script src="app.js" defer></script>
</head>
<body>
    <header class="brand-header"><a href="index.php" class="brand-link"><img src="images/eduschedx-logo.svg" alt="" class="brand-logo"><span class="brand-name">EduSched<span>X</span></span></a></header>
    <main class="activity-shell">
        <section class="activity-card activity-workflow challenge-workflow">
            <?= studentProgress('Activity') ?>
            <div class="screen-panel">
                <a class="page-back" href="instructions.php" aria-label="Back to instructions"><i class="bi bi-arrow-left"></i></a>
                <div class="panel-heading"><span class="eyebrow">Secure Coding Activity</span><h1>Complete the PHP Checks</h1><p>Review choices A to D, then type the PHP code yourself. You have two attempts per challenge.</p></div>
                <?php if (!empty($_SESSION['submit_error'])): ?>
                    <div class="alert alert-warning py-2"><?= escape($_SESSION['submit_error']) ?></div>
                    <?php unset($_SESSION['submit_error']); ?>
                <?php endif; ?>

                <div class="challenge-list">
                        <article class="challenge-card" id="challenge-<?= $challengeId ?>" data-activity-page>
                            <header class="challenge-heading">
                                <span>Challenge <?= $currentStep + 1 ?> of 5</span>
                                <h2><?= escape($challenge['title']) ?></h2>
                            </header>
                            <p class="challenge-description"><?= escape($challenge['description']) ?></p>
                            <div class="challenge-scenario">
                                <?php foreach ($challenge['scenario'] as $label => $value): ?><span><?= escape($label) ?></span><strong><?= escape($value) ?></strong><?php endforeach; ?>
                                <span class="expected-label">Expected Result</span><strong class="expected-value"><i class="bi bi-bullseye" aria-hidden="true"></i> ACCESS DENIED</strong>
                            </div>
                            <div class="challenge-code" aria-label="PHP code with one editable answer">
                                <?php if ($challengeId === 3): ?>
                                    <code>if ($requestedRoute === '/admin/dashboard') {</code>
                                    <code>&nbsp;&nbsp;&nbsp;&nbsp;if (______________________________) {</code>
                                    <code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return false;</code>
                                    <code>&nbsp;&nbsp;&nbsp;&nbsp;}</code>
                                    <code>}</code>
                                <?php elseif ($challengeId === 4): ?>
                                    <code>$allowedRoles = ['SUPERADMIN', 'ADMIN', 'FACULTY'];</code>
                                    <code>&nbsp;</code><code>if (______________________________) {</code><code>&nbsp;&nbsp;&nbsp;&nbsp;return false;</code><code>}</code>
                                <?php elseif ($challengeId === 5): ?>
                                    <code>$user = findUser($studentId);</code>
                                    <code>&nbsp;</code><code>if (!$user) {</code><code>&nbsp;&nbsp;&nbsp;&nbsp;______________________________;</code><code>}</code>
                                <?php else: ?>
                                    <code>if (______________________________) {</code><code>&nbsp;&nbsp;&nbsp;&nbsp;return false;</code><code>}</code>
                                <?php endif; ?>
                            </div>
                            <div class="challenge-body">
                                <form action="grader.php" method="post" class="challenge-run-form">
                                    <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                                    <input type="hidden" name="challenge_id" value="<?= $challengeId ?>">
                                    <div class="code-choices" data-no-copy aria-label="Code choices"><p class="choice-heading">Review the choices, then type the code below</p>
                                        <?php foreach ($challenge['choices'] as $choiceIndex => $choice): $letter = chr(65 + $choiceIndex); ?>
                                            <div class="code-choice"><strong><?= $letter ?>.</strong><code><?= escape($choice) ?></code></div>
                                        <?php endforeach; ?>
                                    </div>
                                    <label class="form-label" for="answer_<?= $challengeId ?>">Type Your Code</label>
                                    <input class="form-control challenge-answer" id="answer_<?= $challengeId ?>" name="answer_<?= $challengeId ?>" data-code-answer value="<?= escape((string) ($answers[$challengeId] ?? '')) ?>" maxlength="180" autocomplete="off" spellcheck="false" <?= !empty($challengeResults[$challengeId]['matches']) || $attemptsUsed >= 2 ? 'readonly' : '' ?> required>
                                    <p class="attempt-count"><?= max(0, 2 - $attemptsUsed) ?> of 2 attempts remaining</p>
                                    <?php if (isset($challengeErrors[$challengeId])): ?>
                                        <div class="alert alert-danger challenge-feedback" role="alert"><?= escape((string) $challengeErrors[$challengeId]) ?></div>
                                    <?php elseif (isset($challengeResults[$challengeId])): $preview = $challengeResults[$challengeId]; ?>
                                        <div class="challenge-result <?= $preview['matches'] ? 'is-match' : 'is-mismatch' ?>" role="status">
                                            <span>Actual Result</span><strong><?= $preview['denied'] ? 'ACCESS DENIED' : 'ACCESS GRANTED' ?></strong>
                                            <p><i class="bi <?= $preview['matches'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i> <?= $preview['matches'] ? 'Matches Expected Result' : 'Does Not Match Expected Result' ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($attemptsUsed < 2 && empty($challengeResults[$challengeId]['matches'])): ?><button class="btn btn-eduschedx" type="submit" data-run-code><i class="bi bi-play-circle"></i> Run Code</button><?php endif; ?>
                                </form>
                                <?php if (isset($challengeResults[$challengeId]) && $currentStep < 4 && ($challengeResults[$challengeId]['matches'] || $attemptsUsed >= 2)): ?>
                                    <form action="grader.php" method="post" class="challenge-next-form">
                                        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                                        <input type="hidden" name="mode" value="next">
                                        <input type="hidden" name="challenge_id" value="<?= $challengeId ?>">
                                        <button class="btn btn-eduschedx w-100" type="submit">Next Challenge <i class="bi bi-arrow-right"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                </div>

                <?php if ($currentStep === 4 && isset($challengeResults[$challengeId]) && ($challengeResults[$challengeId]['matches'] || $attemptsUsed >= 2)): ?>
                <form id="submit-output" action="submit.php" method="post" class="submit-output-form" data-final-submit>
                    <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                    <button class="btn btn-submit-output w-100" type="submit" data-confirm-submit><i class="bi bi-send me-1"></i> Submit Output</button>
                </form>
                <dialog class="submit-dialog" data-submit-dialog aria-labelledby="submit-dialog-title">
                    <div class="submit-dialog-head">
                        <h2 id="submit-dialog-title">Submit Activity?</h2>
                        <button type="button" class="submit-dialog-close" data-submit-cancel aria-label="Close">&times;</button>
                    </div>
                    <p>Are you sure you want to submit your final answers? You can only submit once.</p>
                    <div class="submit-dialog-actions">
                        <button type="button" class="btn btn-light" data-submit-cancel>Cancel</button>
                        <button type="button" class="btn btn-eduschedx" data-submit-confirm><i class="bi bi-send me-1"></i> Yes, Submit</button>
                    </div>
                </dialog>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>

<?php
require __DIR__ . '/config.php';
requireOpenActivity();
require_once __DIR__ . '/grader.php';

if (!empty($_SESSION['part_two_complete'])) {
    header('Location: levels.php');
    exit;
}

$challenges = challengeDefinitions();
if ((int) ($_SESSION['challenge_version'] ?? 0) !== 6) {
    $resetKeys = ['challenge_order', 'challenge_step', 'challenge_attempts', 'answer_draft', 'challenge_result', 'challenge_error'];
    if (empty($_SESSION['security_part_complete'])) {
        $resetKeys = array_merge($resetKeys, ['security_part_ready', 'security_part_step', 'security_unlock_draft', 'security_unlock_error', 'security_unlock_attempts', 'security_unlock_result', 'security_card_order']);
    }
    foreach ($resetKeys as $key) unset($_SESSION[$key]);
    $_SESSION['challenge_version'] = 6;
}
if ((int) ($_SESSION['security_definition_version'] ?? 0) !== 2 && empty($_SESSION['security_part_complete'])) {
    foreach (['security_part_ready', 'security_part_step', 'security_unlock_draft', 'security_unlock_error', 'security_unlock_attempts', 'security_unlock_result', 'security_card_order'] as $key) unset($_SESSION[$key]);
    $_SESSION['security_definition_version'] = 2;
}
$_SESSION['challenge_order'] = studentShuffledOrder(array_keys($challenges), 'part2');
$currentStep = max(0, min(4, (int) ($_SESSION['challenge_step'] ?? 0)));
$_SESSION['challenge_step'] = $currentStep;
$challengeId = (int) $_SESSION['challenge_order'][$currentStep];
$challenge = $challenges[$challengeId];
$partOneComplete = !empty($_SESSION['security_part_complete']);
$partOneReady = !$partOneComplete && !empty($_SESSION['security_part_ready']);
$partTwoReady = $partOneComplete && !empty($_SESSION['part_two_ready']);
$answers = $_SESSION['answer_draft'] ?? array_fill_keys(array_keys($challenges), '');
$challengeResults = $_SESSION['challenge_result'] ?? [];
$challengeErrors = $_SESSION['challenge_error'] ?? [];
$attemptsUsed = (int) ($_SESSION['challenge_attempts'][$challengeId] ?? 0);
$unlockQuestions = securityUnlockDefinitions();
$partOneStep = max(1, min(5, (int) ($_SESSION['security_part_step'] ?? 1)));
$partOneOrder = studentShuffledOrder(array_keys($unlockQuestions), 'part1');
$partOneQuestionId = (int) $partOneOrder[$partOneStep - 1];
$unlock = $unlockQuestions[$partOneQuestionId];
$unlockDraft = (array) ($_SESSION['security_unlock_draft'][$partOneQuestionId] ?? []);
$unlockAttemptsUsed = (int) ($_SESSION['security_unlock_attempts'][$partOneQuestionId] ?? 0);
$unlockLocked = !empty($_SESSION['security_unlock_result'][$partOneQuestionId]) || $unlockAttemptsUsed >= 2;
if (!isset($_SESSION['security_card_order'][$partOneQuestionId])) {
    $_SESSION['security_card_order'][$partOneQuestionId] = array_keys($unlock['cards']);
    shuffle($_SESSION['security_card_order'][$partOneQuestionId]);
}
$cardOrder = array_values(array_intersect((array) $_SESSION['security_card_order'][$partOneQuestionId], array_keys($unlock['cards'])));
if (!empty($unlock['ordered'])) usort($cardOrder, static fn (string $a, string $b): int => ((int) ($unlockDraft[$a] ?? 99)) <=> ((int) ($unlockDraft[$b] ?? 99)));
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
<body class="activity-page <?= $partOneComplete ? 'coding-part' : 'matching-part' ?>">
    <?= studentBrandHeader() ?>
    <main class="activity-shell">
        <section class="activity-card activity-workflow challenge-workflow <?= !$partOneComplete ? 'drag-workflow' : '' ?>">
            <div class="screen-panel">
                <?php if (!$partOneReady && !$partTwoReady): ?><a class="page-back" href="levels.php" aria-label="Back to level selection"><i class="bi bi-arrow-left"></i></a><?php endif; ?>
                <?php if (!$partOneComplete): ?>
                    <?php if ($partOneReady): ?>
                    <section class="part-one-submit-card">
                        <h2>All matching questions are complete</h2>
                        <p>Submit Part 1 to save your score and unlock Part 2.</p>
                        <button class="btn btn-submit-part" type="button" data-part-one-open><i class="bi bi-send-check-fill" aria-hidden="true"></i> Submit Part 1</button>
                    </section>
                    <form action="grader.php" method="post" data-part-one-submit><input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>"><input type="hidden" name="mode" value="submit_part_one"><input type="hidden" name="challenge_id" value="1"></form>
                    <dialog class="submit-dialog" data-part-one-dialog aria-labelledby="part-one-dialog-title"><div class="submit-dialog-head"><h2 id="part-one-dialog-title">Submit Part 1?</h2><button type="button" class="submit-dialog-close" data-part-one-cancel aria-label="Close">&times;</button></div><p>Are you sure you want to submit your final matching answers? You can only submit Part 1 once.</p><div class="submit-dialog-actions"><button type="button" class="btn btn-light" data-part-one-cancel>Cancel</button><button type="button" class="btn btn-eduschedx" data-part-one-confirm><i class="bi bi-send me-1"></i> Yes, Submit</button></div></dialog>
                    <?php else: ?>
                    <div class="panel-heading activity-intro-card"><span class="eyebrow">Part 1 · Security Matching · Question <?= $partOneStep ?> of 5</span><h1><?= escape($unlock['title']) ?></h1><p><?= escape($unlock['description']) ?></p></div>
                    <section class="part-one-workspace" id="security-unlock" data-activity-page>
                        <form action="grader.php" method="post" class="unlock-form <?= $unlockLocked ? 'is-locked' : '' ?>" data-unlock-form data-ordered="<?= !empty($unlock['ordered']) ? 'true' : 'false' ?>" data-locked="<?= $unlockLocked ? 'true' : 'false' ?>">
                            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>"><input type="hidden" name="mode" value="unlock_part"><input type="hidden" name="challenge_id" value="1">
                            <div class="drag-stage">
                                <div class="drag-stage-title"><span>Security Match</span><div class="question-progress"><strong>Question <?= $partOneStep ?> of 5</strong><ol aria-label="Security matching progress"><?php for ($progressStep = 1; $progressStep <= 5; $progressStep++): ?><li class="<?= $progressStep < $partOneStep ? 'is-done' : ($progressStep === $partOneStep ? 'is-current' : '') ?>" <?= $progressStep === $partOneStep ? 'aria-current="step"' : '' ?>><?= $progressStep ?></li><?php endfor; ?></ol></div></div>
                                <?php if (!empty($unlock['ordered'])): ?>
                                    <div class="unlock-target order-target open-order-target" data-unlock-target="order"><strong><?= escape($unlock['targets']['order']) ?></strong>
                                        <?php foreach ($cardOrder as $cardId): ?><div class="unlock-card order-card" draggable="true" tabindex="0" data-unlock-card="<?= escape($cardId) ?>"><span class="order-number"></span><b><?= escape($unlock['cards'][$cardId]) ?></b><input type="hidden" name="unlock_assignment[<?= escape($cardId) ?>]" value=""><div class="order-controls"><button type="button" data-move="up" aria-label="Move up"><i class="bi bi-arrow-up"></i></button><button type="button" data-move="down" aria-label="Move down"><i class="bi bi-arrow-down"></i></button></div></div><?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="unlock-pool open-pool" data-unlock-target="" aria-label="Unplaced cards">
                                        <?php foreach ($cardOrder as $cardId): if (($unlockDraft[$cardId] ?? '') !== '') continue; ?><button class="unlock-card" type="button" draggable="true" data-unlock-card="<?= escape($cardId) ?>"><b><?= escape($unlock['cards'][$cardId]) ?></b><input type="hidden" name="unlock_assignment[<?= escape($cardId) ?>]" value=""></button><?php endforeach; ?>
                                    </div>
                                    <div class="severity-targets target-count-<?= count($unlock['targets']) ?>">
                                        <?php foreach ($unlock['targets'] as $targetId => $targetLabel): ?><div class="unlock-target severity-target severity-<?= escape($targetId) ?>" tabindex="0" role="button" data-unlock-target="<?= escape($targetId) ?>"><strong><?= escape($targetLabel) ?></strong><div class="unlock-drop-cards"><?php foreach ($cardOrder as $cardId): if (($unlockDraft[$cardId] ?? '') !== $targetId) continue; ?><button class="unlock-card" type="button" draggable="true" data-unlock-card="<?= escape($cardId) ?>"><b><?= escape($unlock['cards'][$cardId]) ?></b><input type="hidden" name="unlock_assignment[<?= escape($cardId) ?>]" value="<?= escape($targetId) ?>"></button><?php endforeach; ?></div></div><?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (array_key_exists($partOneQuestionId, (array) ($_SESSION['security_unlock_result'] ?? []))): $unlockPassed = !empty($_SESSION['security_unlock_result'][$partOneQuestionId]); ?>
                            <div class="challenge-result <?= $unlockPassed ? 'is-match' : 'is-mismatch' ?>" role="status">
                                <span>Answer Result</span>
                                <strong><?= $unlockPassed ? 'Correct' : 'Not Correct' ?></strong>
                                <p><i class="bi <?= $unlockPassed ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i> <?= $unlockPassed ? 'Your matching answer is correct.' : 'Your matching answer does not match the security rule.' ?></p>
                            </div>
                            <?php endif; ?>
                            <p class="attempt-count unlock-attempt-count"><?= max(0, 2 - $unlockAttemptsUsed) ?> of 2 attempts remaining</p>
                            <?php if (empty($_SESSION['security_unlock_result'][$partOneQuestionId]) && $unlockAttemptsUsed < 2): ?><button class="btn btn-eduschedx check-drag-button" type="submit" data-check-unlock><i class="bi bi-check2-circle"></i> Check Answer</button><?php endif; ?>
                        </form>
                        <?php if (!empty($_SESSION['security_unlock_result'][$partOneQuestionId]) || $unlockAttemptsUsed >= 2): ?>
                        <form action="grader.php" method="post" class="challenge-next-form part-one-next-form">
                            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>"><input type="hidden" name="mode" value="next_part_one"><input type="hidden" name="challenge_id" value="1">
                            <button class="btn btn-eduschedx w-100" type="submit"><?= $partOneStep >= 5 ? 'Complete Part 1' : 'Next Challenge' ?> <i class="bi bi-arrow-right"></i></button>
                        </form>
                        <?php endif; ?>
                    </section>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($partTwoReady): ?>
                    <section class="part-one-submit-card part-two-submit-card">
                        <h2>All coding questions are complete</h2>
                        <p>Submit Part 2 to save your score and unlock Part 3.</p>
                        <form id="submit-output" action="submit.php" method="post" data-final-submit>
                            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                            <button class="btn btn-submit-part" type="submit" data-confirm-submit><i class="bi bi-send-check-fill" aria-hidden="true"></i> Submit Part 2</button>
                        </form>
                    </section>
                    <dialog class="submit-dialog" data-submit-dialog aria-labelledby="submit-dialog-title"><div class="submit-dialog-head"><h2 id="submit-dialog-title">Submit Part 2?</h2><button type="button" class="submit-dialog-close" data-submit-cancel aria-label="Close">&times;</button></div><p>Are you sure you want to submit your final coding answers? Your Part 2 score will be recorded.</p><div class="submit-dialog-actions"><button type="button" class="btn btn-light" data-submit-cancel>Cancel</button><button type="button" class="btn btn-eduschedx" data-submit-confirm><i class="bi bi-send me-1"></i> Yes, Submit</button></div></dialog>
                    <?php else: ?>
                    <div class="panel-heading activity-intro-card"><span class="eyebrow">Part 2 of 2 · Coding Challenges</span><h1>Complete the PHP Checks</h1><p>Type the missing PHP code. You have two attempts per challenge.</p></div>
                    <?php if (!empty($_SESSION['submit_error'])): ?><div class="alert alert-warning py-2"><?= escape($_SESSION['submit_error']) ?></div><?php unset($_SESSION['submit_error']); endif; ?>
                    <article class="challenge-card" id="challenge-<?= $challengeId ?>" data-activity-page>
                        <div class="challenge-intro-card">
                            <header class="challenge-heading"><div class="question-progress coding-question-progress"><strong>Challenge <?= $currentStep + 1 ?> of 5</strong><ol aria-label="PHP coding challenge progress"><?php for ($progressStep = 1; $progressStep <= 5; $progressStep++): ?><li class="<?= $progressStep < ($currentStep + 1) ? 'is-done' : ($progressStep === ($currentStep + 1) ? 'is-current' : '') ?>" <?= $progressStep === ($currentStep + 1) ? 'aria-current="step"' : '' ?>><?= $progressStep ?></li><?php endfor; ?></ol></div><h2><?= escape($challenge['title']) ?></h2></header>
                            <p class="challenge-description"><?= escape($challenge['description']) ?></p>
                        </div>
                        <div class="challenge-code" aria-label="PHP code with one editable answer">
                            <?php if ($challengeId === 2): ?><code>$allowedRoles = ['SUPERADMIN', 'ADMIN'];</code><code>&nbsp;</code><?php endif; ?>
                            <?php if ($challengeId === 3): ?><code>$inputStatus = normalizeInput($input);</code><code>&nbsp;</code><?php endif; ?>
                            <?php if ($challengeId === 4): ?>
                                <code>catch (Throwable $error) {</code><code>&nbsp;&nbsp;&nbsp;&nbsp;error_log($error-&gt;getMessage());</code><code class="challenge-code-line">&nbsp;&nbsp;&nbsp;&nbsp;<input class="challenge-answer inline-code-answer" id="answer_<?= $challengeId ?>" name="answer_<?= $challengeId ?>" form="challenge-run" data-code-answer value="<?= escape((string) ($answers[$challengeId] ?? '')) ?>" maxlength="180" autocomplete="off" spellcheck="false" aria-label="Type the missing PHP code" <?= !empty($challengeResults[$challengeId]['matches']) || $attemptsUsed >= 2 ? 'readonly' : '' ?> required>;</code><code>}</code>
                            <?php elseif ($challengeId === 5): ?>
                                <code>$severity = 'INFO';</code><code>if ($failedAttempts &gt;= 3) { $severity = 'WARNING'; }</code><code class="challenge-code-line">if (<input class="challenge-answer inline-code-answer" id="answer_<?= $challengeId ?>" name="answer_<?= $challengeId ?>" form="challenge-run" data-code-answer value="<?= escape((string) ($answers[$challengeId] ?? '')) ?>" maxlength="180" autocomplete="off" spellcheck="false" aria-label="Type the missing PHP condition" <?= !empty($challengeResults[$challengeId]['matches']) || $attemptsUsed >= 2 ? 'readonly' : '' ?> required>) { $severity = 'CRITICAL'; }</code>
                            <?php else: ?>
                                <code class="challenge-code-line">if (<input class="challenge-answer inline-code-answer" id="answer_<?= $challengeId ?>" name="answer_<?= $challengeId ?>" form="challenge-run" data-code-answer value="<?= escape((string) ($answers[$challengeId] ?? '')) ?>" maxlength="180" autocomplete="off" spellcheck="false" aria-label="Type the missing PHP condition" <?= !empty($challengeResults[$challengeId]['matches']) || $attemptsUsed >= 2 ? 'readonly' : '' ?> required>) {</code><code>&nbsp;&nbsp;&nbsp;&nbsp;return false;</code><code>}</code>
                            <?php endif; ?>
                        </div>
                        <div class="challenge-body">
                            <div class="challenge-scenario"><span class="expected-label">Expected Result</span><strong class="expected-value"><?= escape($challenge['expected']) ?></strong></div>
                            <form id="challenge-run" action="grader.php" method="post" class="challenge-run-form">
                                <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>"><input type="hidden" name="challenge_id" value="<?= $challengeId ?>">
                                <div class="code-entry-card">
                                    <div class="code-choices" data-no-copy aria-label="Code references"><p class="choice-heading">Type the missing PHP condition using these references</p><?php foreach ($challenge['choices'] as $choiceIndex => $choice): $letter = chr(65 + $choiceIndex); ?><div class="code-choice"><strong><?= $letter ?>.</strong><code><?= escape($choice) ?></code></div><?php endforeach; ?></div>
                                    <label class="visually-hidden" for="answer_<?= $challengeId ?>">Type the Missing PHP Code</label>
                                    <p class="attempt-count"><?= max(0, 2 - $attemptsUsed) ?> of 2 attempts remaining</p>
                                </div>
                                <?php if (isset($challengeErrors[$challengeId])): ?><div class="alert alert-danger challenge-feedback" role="alert"><?= escape((string) $challengeErrors[$challengeId]) ?></div>
                                <?php elseif (isset($challengeResults[$challengeId])): $preview = $challengeResults[$challengeId]; ?><div class="challenge-result <?= $preview['matches'] ? 'is-match' : 'is-mismatch' ?>" role="status"><span>Actual Result</span><strong><?= escape((string) $preview['actual']) ?></strong><p><i class="bi <?= $preview['matches'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i> <?= $preview['matches'] ? 'Matches Expected Result' : 'Does Not Match Expected Result' ?></p></div><?php endif; ?>
                                <?php if ($attemptsUsed < 2 && empty($challengeResults[$challengeId]['matches'])): ?><button class="btn btn-run-code" type="submit" data-run-code><i class="bi bi-play-circle"></i> Run Code</button><?php endif; ?>
                            </form>
                            <?php if (isset($challengeResults[$challengeId]) && ($challengeResults[$challengeId]['matches'] || $attemptsUsed >= 2)): ?><form action="grader.php" method="post" class="challenge-next-form"><input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>"><input type="hidden" name="mode" value="next"><input type="hidden" name="challenge_id" value="<?= $challengeId ?>"><button class="btn btn-eduschedx w-100" type="submit"><?= $currentStep >= 4 ? 'Complete Part 2' : 'Next Challenge' ?> <i class="bi bi-arrow-right"></i></button></form><?php endif; ?>
                        </div>
                    </article>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>

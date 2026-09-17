<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireStudent();

function challengeDefinitions(): array
{
    return [
        1 => ['title' => 'Session Security', 'description' => 'An expired or invalid authenticated session must not continue to protected functions.', 'expected' => 'ACCESS DENIED', 'choices' => ["\$sessionStatus === 'VALID'", "\$sessionStatus !== 'VALID'", "\$sessionStatus === 'EXPIRED'", "\$sessionStatus !== 'EXPIRED'"]],
        2 => ['title' => 'Authorization', 'description' => 'Authorization determines which authenticated roles may open the protected Admin Dashboard.', 'expected' => 'ACCESS DENIED', 'choices' => ['in_array($role, $allowedRoles, true)', '!in_array($role, $allowedRoles, true)', "\$role === 'FACULTY'", "\$role !== 'GUEST'"]],
        3 => ['title' => 'Secure Input Validation', 'description' => 'Validate normalized input before it is processed by the application.', 'expected' => 'ACCESS DENIED', 'choices' => ["\$inputStatus === 'VALID'", "\$inputStatus !== 'VALID'", "\$inputStatus === 'INVALID'", "\$inputStatus !== 'MISSING'"]],
        4 => ['title' => 'Safe Error Handling', 'description' => 'Record technical failure details while returning a safe response.', 'expected' => 'SAFE MESSAGE', 'choices' => ['return $error->getMessage()', 'return safeErrorResponse()', 'throw $error', 'return $error->getTraceAsString()']],
        5 => ['title' => 'Security Monitoring', 'description' => 'Assign a critical severity when failed login attempts reach the security limit.', 'expected' => 'CRITICAL', 'choices' => ['$failedAttempts >= 3', '$failedAttempts >= 4', '$failedAttempts >= 5', '$failedAttempts === 1']],
    ];
}

function securityUnlockDefinitions(): array
{
    return [
        1 => ['title' => 'Session Status Check', 'description' => 'Sort each session status by whether access should continue.', 'cards' => ['VALID'=>'VALID','EXPIRED'=>'EXPIRED','MISSING'=>'MISSING','INVALID'=>'INVALID'], 'targets' => ['allowed'=>'ACCESS ALLOWED','denied'=>'ACCESS DENIED'], 'answer' => ['VALID'=>'allowed','EXPIRED'=>'denied','MISSING'=>'denied','INVALID'=>'denied']],
        2 => ['title' => 'Faculty Account Access Check', 'description' => 'Sort each Faculty account condition based on whether the security check should continue or deny access.', 'cards' => ['APPROVED'=>'APPROVED','NOT_APPROVED'=>'NOT APPROVED','ACTIVE'=>'ACTIVE','INACTIVE'=>'INACTIVE'], 'targets' => ['continue'=>'CONTINUE ACCESS CHECK','denied'=>'DENY ACCESS'], 'result_labels' => ['continue'=>'CONTINUE','denied'=>'DENIED'], 'answer' => ['APPROVED'=>'continue','NOT_APPROVED'=>'denied','ACTIVE'=>'continue','INACTIVE'=>'denied']],
        3 => ['title' => 'Secure Input Processing', 'description' => 'Match each input-handling action as secure or insecure.', 'cards' => ['normalize'=>'Normalize expected values','validate'=>'Validate before processing','raw'=>'Trust raw input','process'=>'Process before validation'], 'targets' => ['secure'=>'SECURE','insecure'=>'INSECURE'], 'answer' => ['normalize'=>'secure','validate'=>'secure','raw'=>'insecure','process'=>'insecure']],
        4 => ['title' => 'Safe Error Messages', 'description' => 'Separate safe user messages from technical server details.', 'cards' => ['stack'=>'Stack trace','database'=>'Database exception','login'=>'Login failed','credentials'=>'Invalid credentials'], 'targets' => ['user'=>'SHOW TO USER','log'=>'STORE IN SERVER LOG'], 'answer' => ['stack'=>'log','database'=>'log','login'=>'user','credentials'=>'user']],
        5 => ['title' => 'Failed Login Severity Check', 'description' => 'Match each failed-login attempt to its correct security severity level.', 'cards' => ['attempt1'=>'Attempt 1','attempt2'=>'Attempt 2','attempt3'=>'Attempt 3','attempt4'=>'Attempt 4','attempt5'=>'Attempt 5'], 'targets' => ['info'=>'INFO','warning'=>'WARNING','critical'=>'CRITICAL'], 'answer' => ['attempt1'=>'info','attempt2'=>'info','attempt3'=>'warning','attempt4'=>'warning','attempt5'=>'critical']],
    ];
}

function readSubmittedAnswers(array $input): array
{
    $answers = [];
    foreach (array_keys(challengeDefinitions()) as $number) {
        $answer = trim((string) ($input['answer_' . $number] ?? ''));
        $answers[$number] = mb_strlen($answer) <= 180 ? $answer : '';
    }
    return $answers;
}

function normalizePhpAnswer(string $answer): string
{
    return preg_replace('/\s+/', '', trim($answer)) ?? '';
}

function evaluateChallengeCondition(int $challengeId, string $answer, array $variables): ?bool
{
    $expression = normalizePhpAnswer($answer);
    while (hasWrappingParentheses($expression)) {
        $expression = substr($expression, 1, -1);
    }
    if ($challengeId === 4) {
        return match (rtrim($expression, ';')) {
            'returnsafeErrorResponse()' => true,
            default => null,
        };
    }
    if ($challengeId === 5) {
        if (preg_match('/^\$failedAttempts(>=|>|===|==)(\d+)$/', $expression, $match) !== 1) return null;
        $attempts = (int) ($variables['failedAttempts'] ?? 0);
        $number = (int) $match[2];
        return match ($match[1]) { '>=' => $attempts >= $number, '>' => $attempts > $number, '===', '==' => $attempts === $number };
    }
    if ($challengeId === 2 && preg_match('/^(!)?in_array\(\$role,\$allowedRoles,true\)$/', $expression, $match) === 1) {
        $found = in_array((string) ($variables['role'] ?? ''), ['SUPERADMIN', 'ADMIN'], true);
        return ($match[1] ?? '') === '!' ? !$found : $found;
    }
    $variable = [1 => 'sessionStatus', 3 => 'inputStatus'][$challengeId] ?? null;
    if ($variable === null) return null;
    $pattern = '/^\$' . $variable . "(===|!==|==|!=)'([^']*)'$/";
    if (preg_match($pattern, $expression, $match) !== 1) return null;
    $left = (string) ($variables[$variable] ?? '');
    return in_array($match[1], ['===', '=='], true) ? $left === $match[2] : $left !== $match[2];
}

function hasWrappingParentheses(string $expression): bool
{
    if (strlen($expression) < 2 || $expression[0] !== '(' || $expression[-1] !== ')') return false;
    $depth = 0;
    for ($index = 0, $length = strlen($expression); $index < $length; $index++) {
        $depth += $expression[$index] === '(' ? 1 : ($expression[$index] === ')' ? -1 : 0);
        if ($depth === 0 && $index < $length - 1) return false;
    }
    return $depth === 0;
}

function challengeHiddenTests(int $challengeId): array
{
    return match ($challengeId) {
        1 => [[['sessionStatus' => 'VALID'], false], [['sessionStatus' => 'EXPIRED'], true], [['sessionStatus' => 'MISSING'], true], [['sessionStatus' => 'INVALID'], true]],
        2 => [[['role' => 'SUPERADMIN'], false], [['role' => 'ADMIN'], false], [['role' => 'FACULTY'], true], [['role' => 'GUEST'], true]],
        3 => [[['inputStatus' => 'VALID'], false], [['inputStatus' => 'INVALID'], true], [['inputStatus' => 'MISSING'], true]],
        4 => [[[], true]],
        5 => [[['failedAttempts' => 1], false], [['failedAttempts' => 2], false], [['failedAttempts' => 3], false], [['failedAttempts' => 4], false], [['failedAttempts' => 5], true], [['failedAttempts' => 6], true]],
        default => [],
    };
}

function passesChallengeTests(int $challengeId, string $answer): bool
{
    $tests = challengeHiddenTests($challengeId);
    if ($tests === []) return false;
    foreach ($tests as [$variables, $expected]) {
        if (evaluateChallengeCondition($challengeId, $answer, $variables) !== $expected) return false;
    }
    return true;
}

function runChallengePreview(int $challengeId, string $answer): ?array
{
    $variables = match ($challengeId) { 1 => ['sessionStatus' => 'EXPIRED'], 2 => ['role' => 'FACULTY'], 3 => ['inputStatus' => 'INVALID'], 4 => [], 5 => ['failedAttempts' => 5], default => null };
    if ($variables === null) return null;
    $matched = evaluateChallengeCondition($challengeId, $answer, $variables);
    if ($matched === null) return null;
    $labels = match ($challengeId) { 4 => [true => 'SAFE MESSAGE', false => 'TECHNICAL DETAILS'], 5 => [true => 'CRITICAL', false => 'NOT CRITICAL'], default => [true => 'ACCESS DENIED', false => 'ACCESS GRANTED'] };
    return ['actual' => $labels[$matched], 'matches' => $matched === true];
}

function securityUnlockIsCorrect(int $questionId, array $submitted): bool
{
    $expected = securityUnlockDefinitions()[$questionId]['answer'] ?? [];
    if (count($submitted) !== count($expected)) return false;
    foreach ($expected as $card => $target) {
        if (($submitted[$card] ?? null) !== $target) return false;
    }
    return true;
}

function gradeChallenges(array $answers): array
{
    $results = [];
    foreach (challengeDefinitions() as $challengeId => $definition) {
        $passed = passesChallengeTests($challengeId, (string) ($answers[$challengeId] ?? ''));
        $results[] = ['challenge' => $challengeId, 'title' => $definition['title'], 'passed' => $passed];
    }
    return ['score' => count(array_filter($results, static fn (array $result): bool => $result['passed'])), 'total' => count($results), 'results' => $results];
}

if (basename($_SERVER['SCRIPT_FILENAME']) === 'grader.php') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfIsValid($_POST['csrf_token'] ?? null)) { header('Location: activity.php'); exit; }
    $challengeId = filter_input(INPUT_POST, 'challenge_id', FILTER_VALIDATE_INT);
    $definitions = challengeDefinitions();
    $mode = (string) ($_POST['mode'] ?? 'run');
    $order = (array) ($_SESSION['challenge_order'] ?? []);
    $currentStep = max(0, min(4, (int) ($_SESSION['challenge_step'] ?? 0)));
    $currentChallengeId = isset($order[$currentStep]) ? (int) $order[$currentStep] : 0;
    if (!in_array($mode, ['unlock_part', 'next_part_one', 'submit_part_one'], true)
        && (!$challengeId || !isset($definitions[$challengeId]) || $challengeId !== $currentChallengeId)) {
        header('Location: activity.php'); exit;
    }

    if ($mode === 'unlock_part') {
        $questionPosition = max(1, min(5, (int) ($_SESSION['security_part_step'] ?? 1)));
        $questionOrder = studentShuffledOrder(array_keys(securityUnlockDefinitions()), 'part1');
        $questionId = (int) $questionOrder[$questionPosition - 1];
        $unlock = securityUnlockDefinitions()[$questionId];
        $unlockAttempts = (int) ($_SESSION['security_unlock_attempts'][$questionId] ?? 0);
        if ($unlockAttempts >= 2) { header('Location: activity.php#security-unlock'); exit; }
        $submitted = [];
        foreach ((array) ($_POST['unlock_assignment'] ?? []) as $card => $target) {
            if (isset($unlock['cards'][(string) $card]) && is_scalar($target)) $submitted[(string) $card] = substr((string) $target, 0, 20);
        }
        $_SESSION['security_unlock_draft'][$questionId] = $submitted;
        $allCardsPlaced = count($submitted) === count($unlock['cards']);
        foreach (array_keys($unlock['cards']) as $card) {
            $target = $submitted[$card] ?? '';
            if (!isset($unlock['targets'][$target]) && empty($unlock['ordered'])) $allCardsPlaced = false;
            if (!empty($unlock['ordered']) && !preg_match('/^[1-5]$/', $target)) $allCardsPlaced = false;
        }
        if (!$allCardsPlaced) {
            $_SESSION['security_unlock_error'][$questionId] = 'Place every card inside a matching area before checking your answer.';
            header('Location: activity.php#security-unlock'); exit;
        }
        $_SESSION['security_unlock_attempts'][$questionId] = ++$unlockAttempts;
        $unlockPassed = securityUnlockIsCorrect($questionId, $submitted);
        $_SESSION['security_unlock_result'][$questionId] = $unlockPassed;
        unset($_SESSION['security_unlock_error'][$questionId]);
        header('Location: activity.php#security-unlock'); exit;
    }
    if ($mode === 'next_part_one') {
        $questionPosition = max(1, min(5, (int) ($_SESSION['security_part_step'] ?? 1)));
        $questionOrder = studentShuffledOrder(array_keys(securityUnlockDefinitions()), 'part1');
        $questionId = (int) $questionOrder[$questionPosition - 1];
        $attempts = (int) ($_SESSION['security_unlock_attempts'][$questionId] ?? 0);
        $hasFinishedQuestion = !empty($_SESSION['security_unlock_result'][$questionId]) || $attempts >= 2;
        if ($hasFinishedQuestion) {
            unset($_SESSION['security_unlock_error'][$questionId]);
            if ($questionPosition >= 5) {
                $_SESSION['security_part_ready'] = true;
                header('Location: activity.php');
                exit;
            }
            $_SESSION['security_part_step'] = $questionPosition + 1;
        }
        header('Location: activity.php#security-unlock'); exit;
    }
    if ($mode === 'submit_part_one') {
        if (empty($_SESSION['security_part_ready'])) {
            header('Location: activity.php');
            exit;
        }
        $partOneResults = [];
        foreach (securityUnlockDefinitions() as $resultQuestionId => $resultDefinition) {
            $partOneResults[] = [
                'challenge' => $resultQuestionId,
                'title' => $resultDefinition['title'],
                'passed' => !empty($_SESSION['security_unlock_result'][$resultQuestionId]),
                'answer' => (array) ($_SESSION['security_unlock_draft'][$resultQuestionId] ?? []),
                'expected' => $resultDefinition['answer'],
                'attempts' => (int) ($_SESSION['security_unlock_attempts'][$resultQuestionId] ?? 0),
            ];
        }
        $partOneScore = count(array_filter($partOneResults, static fn (array $result): bool => $result['passed']));
        recordActivityPart(1, $partOneScore, $partOneResults);
        $_SESSION['security_part_complete'] = true;
        $_SESSION['part_one_just_completed'] = true;
        unset($_SESSION['security_part_ready']);
        header('Location: levels.php');
        exit;
    }
    if ($mode === 'next') {
        if (isset($_SESSION['challenge_result'][$challengeId]) && (!empty($_SESSION['challenge_result'][$challengeId]['matches']) || (int) ($_SESSION['challenge_attempts'][$challengeId] ?? 0) >= 2)) {
            if ($currentStep >= 4) {
                $_SESSION['part_two_ready'] = true;
            } else {
                $_SESSION['challenge_step'] = $currentStep + 1;
                unset($_SESSION['challenge_result'][$challengeId], $_SESSION['challenge_error'][$challengeId]);
            }
        }
        header('Location: activity.php'); exit;
    }
    if (empty($_SESSION['security_part_complete'])) {
        $_SESSION['challenge_error'][$challengeId] = 'Complete Part 1 first.';
        header('Location: activity.php#security-unlock'); exit;
    }
    $attempts = (int) ($_SESSION['challenge_attempts'][$challengeId] ?? 0);
    if ($attempts >= 2) {
        $_SESSION['challenge_error'][$challengeId] = 'You have used both attempts for this challenge.';
        header('Location: activity.php#challenge-' . $challengeId); exit;
    }
    $answer = trim((string) ($_POST['answer_' . $challengeId] ?? ''));
    if ($answer === '' || mb_strlen($answer) > 180) {
        $_SESSION['challenge_error'][$challengeId] = 'Type the PHP code shown by your chosen reference.';
        header('Location: activity.php#challenge-' . $challengeId); exit;
    }
    $_SESSION['challenge_attempts'][$challengeId] = $attempts + 1;
    $_SESSION['answer_draft'][$challengeId] = $answer;
    $preview = runChallengePreview($challengeId, $answer) ?? ['actual' => 'INVALID CODE', 'matches' => false];
    $preview['matches'] = $preview['matches'] && passesChallengeTests($challengeId, $answer);
    $preview['answer'] = $answer;
    $_SESSION['challenge_result'][$challengeId] = $preview;
    unset($_SESSION['challenge_error'][$challengeId]);
    header('Location: activity.php#challenge-' . $challengeId); exit;
}

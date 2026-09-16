<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireStudent();

function challengeDefinitions(): array
{
    return [
        1 => ['title' => 'Account Status', 'description' => 'A disabled student account tries to sign in. Complete the check so only ACTIVE accounts can continue.', 'choices' => ["\$accountStatus === 'ACTIVE'", "\$accountStatus !== 'ACTIVE'", "\$accountStatus === 'VALID'", "\$accountStatus !== 'DISABLED'"], 'scenario' => ['Account Status' => 'DISABLED']],
        2 => ['title' => 'Session Security', 'description' => 'A student opens the activity with an expired session. Complete the check so sessions other than VALID are denied.', 'choices' => ["\$sessionStatus === 'VALID'", "\$sessionStatus !== 'VALID'", "\$sessionStatus === 'ACTIVE'", "\$sessionStatus !== 'EXPIRED'"], 'scenario' => ['Session Status' => 'EXPIRED']],
        3 => ['title' => 'Authorization', 'description' => 'A faculty user requests the admin dashboard. Complete the check so this route is accessible only to the ADMIN role.', 'choices' => ["\$role === 'ADMIN'", "\$role !== 'ADMIN'", "\$role === 'FACULTY'", "\$role !== 'FACULTY'"], 'scenario' => ['Role' => 'FACULTY', 'Requested Route' => '/admin/dashboard']],
        4 => ['title' => 'Role Validation', 'description' => 'A GUEST role is absent from the permitted roles list. Complete the check so roles outside that list are denied.', 'choices' => ['in_array($role, $allowedRoles, true)', '!in_array($role, $allowedRoles, true)', "\$role !== ''", "\$role === 'GUEST'"], 'scenario' => ['Role' => 'GUEST']],
        5 => ['title' => 'Fail Securely', 'description' => 'The requested student record does not exist. Complete the missing statement so the request stops and denies access.', 'choices' => ['return true', 'return false', 'continue', 'return $user'], 'scenario' => ['Student Record' => 'NOT FOUND']],
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

    if ($challengeId === 5) {
        return match (rtrim(strtolower($expression), ';')) {
            'returnfalse' => true,
            'returntrue' => false,
            default => null,
        };
    }

    if ($challengeId === 4 && preg_match('/^(!)?in_array\(\$role,\$allowedRoles,true\)$/', $expression, $match) === 1) {
        $found = in_array((string) ($variables['role'] ?? ''), ['SUPERADMIN', 'ADMIN', 'FACULTY'], true);
        return ($match[1] ?? '') === '!' ? !$found : $found;
    }

    $variable = [1 => 'accountStatus', 2 => 'sessionStatus', 3 => 'role', 4 => 'role'][$challengeId] ?? null;
    if ($variable === null) {
        return null;
    }
    $pattern = '/^\$' . $variable . "(===|!==|==|!=)'([^']*)'$/";
    if (preg_match($pattern, $expression, $match) !== 1) {
        return null;
    }
    $left = (string) ($variables[$variable] ?? '');
    return in_array($match[1], ['===', '=='], true) ? $left === $match[2] : $left !== $match[2];
}

function hasWrappingParentheses(string $expression): bool
{
    if (strlen($expression) < 2 || $expression[0] !== '(' || $expression[-1] !== ')') {
        return false;
    }
    $depth = 0;
    for ($index = 0, $length = strlen($expression); $index < $length; $index++) {
        $depth += $expression[$index] === '(' ? 1 : ($expression[$index] === ')' ? -1 : 0);
        if ($depth === 0 && $index < $length - 1) {
            return false;
        }
    }
    return $depth === 0;
}

function challengeHiddenTests(int $challengeId): array
{
    return match ($challengeId) {
        1 => [[[ 'accountStatus' => 'ACTIVE'], false], [['accountStatus' => 'DISABLED'], true]],
        2 => [[['sessionStatus' => 'VALID'], false], [['sessionStatus' => 'EXPIRED'], true]],
        3 => [[['role' => 'ADMIN'], false], [['role' => 'FACULTY'], true], [['role' => 'SUPERADMIN'], true]],
        4 => [[['role' => 'SUPERADMIN'], false], [['role' => 'ADMIN'], false], [['role' => 'FACULTY'], false], [['role' => 'GUEST'], true]],
        5 => [[[], true]],
        default => [],
    };
}

function runChallengePreview(int $challengeId, string $answer): ?array
{
    $variables = match ($challengeId) {
        1 => ['accountStatus' => 'DISABLED'],
        2 => ['sessionStatus' => 'EXPIRED'],
        3 => ['role' => 'FACULTY'],
        4 => ['role' => 'GUEST'],
        5 => [],
        default => null,
    };
    if ($variables === null) {
        return null;
    }
    $denied = evaluateChallengeCondition($challengeId, $answer, $variables);
    return $denied === null ? null : ['denied' => $denied, 'matches' => $denied === true];
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

function passesChallengeTests(int $challengeId, string $answer): bool
{
    foreach (challengeHiddenTests($challengeId) as [$variables, $expectedDenied]) {
        if (evaluateChallengeCondition($challengeId, $answer, $variables) !== $expectedDenied) {
            return false;
        }
    }
    return challengeHiddenTests($challengeId) !== [];
}

if (basename($_SERVER['SCRIPT_FILENAME']) === 'grader.php') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfIsValid($_POST['csrf_token'] ?? null)) {
        header('Location: activity.php');
        exit;
    }
    $challengeId = filter_input(INPUT_POST, 'challenge_id', FILTER_VALIDATE_INT);
    $order = (array) ($_SESSION['challenge_order'] ?? []);
    $currentStep = max(0, min(4, (int) ($_SESSION['challenge_step'] ?? 0)));
    $currentChallengeId = isset($order[$currentStep]) ? (int) $order[$currentStep] : 0;
    if (!$challengeId || !isset(challengeDefinitions()[$challengeId]) || $challengeId !== $currentChallengeId) {
        header('Location: activity.php');
        exit;
    }
    if (($_POST['mode'] ?? 'run') === 'next') {
        if (isset($_SESSION['challenge_result'][$challengeId]) &&
            (!empty($_SESSION['challenge_result'][$challengeId]['matches']) || (int) ($_SESSION['challenge_attempts'][$challengeId] ?? 0) >= 2) && $currentStep < 4) {
            $_SESSION['challenge_step'] = $currentStep + 1;
            unset($_SESSION['challenge_result'][$challengeId], $_SESSION['challenge_error'][$challengeId]);
        }
        header('Location: activity.php');
        exit;
    }
    $attempts = (int) ($_SESSION['challenge_attempts'][$challengeId] ?? 0);
    if ($attempts >= 2) {
        $_SESSION['challenge_error'][$challengeId] = 'You have used both attempts for this challenge.';
        header('Location: activity.php#challenge-' . $challengeId);
        exit;
    }
    $answer = trim((string) ($_POST['answer_' . $challengeId] ?? ''));
    if ($answer === '' || mb_strlen($answer) > 180) {
        $_SESSION['challenge_error'][$challengeId] = 'Type the PHP code shown by your chosen answer.';
        header('Location: activity.php#challenge-' . $challengeId);
        exit;
    }
    $_SESSION['challenge_attempts'][$challengeId] = $attempts + 1;
    $answers = $_SESSION['answer_draft'] ?? [];
    $answers[$challengeId] = $answer;
    $_SESSION['answer_draft'] = $answers;
    $preview = runChallengePreview($challengeId, $answer) ?? ['denied' => false, 'matches' => false];
    $preview['matches'] = $preview['matches'] && passesChallengeTests($challengeId, $answer);
    $preview['answer'] = $answer;
    $_SESSION['challenge_result'][$challengeId] = $preview;
    unset($_SESSION['challenge_error'][$challengeId]);
    header('Location: activity.php#challenge-' . $challengeId);
    exit;
}

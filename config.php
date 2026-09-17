<?php
declare(strict_types=1);

const ACTIVITY_TOTAL = 5;

if (!defined('EDUSCHEDX_SHARED_CONFIG')) {
    define('EDUSCHEDX_SHARED_CONFIG', true);
}
require_once __DIR__ . '/instructor-server/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionDirectory = __DIR__ . '/storage/sessions';
    if (!is_dir($sessionDirectory)) {
        mkdir($sessionDirectory, 0775, true);
    }
    session_save_path($sessionDirectory);
    session_start();
}

// Protected activity pages must never be restored from the browser's
// back-forward cache after a student returns to the login screen.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfIsValid(?string $token): bool
{
    return is_string($token) && hash_equals(csrfToken(), $token);
}

function finishStudentSession(array $temporaryData = []): void
{
    session_regenerate_id(true);
    $_SESSION = $temporaryData;
}

function verifyRosterStudent(string $studentId, string $activityCode): ?array
{
    $studentId = normalizeStudentId($studentId);
    $activityCode = strtoupper(trim($activityCode));
    if (!preg_match('/^[A-HJ-NP-Z2-9]{6}$/', $activityCode)) {
        return null;
    }
    $statement = database()->prepare(
        'SELECT student_id, full_name, assigned_station, activity_code_hash FROM official_roster
         WHERE student_id = :student_id LIMIT 1'
    );
    $statement->execute(['student_id' => $studentId]);
    $student = $statement->fetch();
    if (!is_array($student) || !password_verify($activityCode, (string) $student['activity_code_hash'])) {
        return null;
    }
    unset($student['activity_code_hash']);
    return $student;
}

function requireStudent(): void
{
    if (empty($_SESSION['student_verified']) || !isset($_SESSION['student_id'], $_SESSION['full_name'], $_SESSION['assigned_station'])) {
        header('Location: login.php');
        exit;
    }
    registerActivityRuntimeSave();
}

function activityRuntimeKeys(): array
{
    return [
        'challenge_version', 'challenge_order', 'challenge_step', 'challenge_attempts',
        'answer_draft', 'challenge_result', 'part_two_ready',
        'security_definition_version', 'security_part_step', 'security_part_ready',
        'security_unlock_draft', 'security_unlock_attempts', 'security_unlock_result',
        'security_card_order', 'part_three_deadline', 'part_three_attempts',
        'part_three_draft', 'part_three_generated', 'part_three_results',
        'part_three_score', 'part_three_ready', 'part_three_timed_out',
    ];
}

function loadActivityRuntime(string $studentId): void
{
    $statement = database()->prepare('SELECT state_json, part3_deadline FROM student_activity_runtime WHERE student_id = :student_id');
    $statement->execute(['student_id' => normalizeStudentId($studentId)]);
    $row = $statement->fetch();
    if (!is_array($row)) return;

    $state = json_decode((string) ($row['state_json'] ?? '{}'), true);
    if (is_array($state)) {
        foreach (activityRuntimeKeys() as $key) {
            if (array_key_exists($key, $state)) $_SESSION[$key] = $state[$key];
        }
    }
    if ((int) ($row['part3_deadline'] ?? 0) > 0) {
        $_SESSION['part_three_deadline'] = (int) $row['part3_deadline'];
    }
}

function saveActivityRuntime(): void
{
    if (empty($_SESSION['student_verified']) || empty($_SESSION['student_id'])) return;
    $state = [];
    foreach (activityRuntimeKeys() as $key) {
        if (array_key_exists($key, $_SESSION)) $state[$key] = $_SESSION[$key];
    }
    $statement = database()->prepare(
        'INSERT INTO student_activity_runtime (student_id, part3_deadline, state_json, updated_at)
         VALUES (:student_id, :deadline, :state_json, :updated_at)
         ON CONFLICT(student_id) DO UPDATE SET
            part3_deadline = COALESCE(excluded.part3_deadline, student_activity_runtime.part3_deadline),
            state_json = excluded.state_json,
            updated_at = excluded.updated_at'
    );
    $deadline = (int) ($_SESSION['part_three_deadline'] ?? 0);
    $statement->execute([
        'student_id' => normalizeStudentId((string) $_SESSION['student_id']),
        'deadline' => $deadline > 0 ? $deadline : null,
        'state_json' => json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'updated_at' => gmdate('Y-m-d H:i:s'),
    ]);
}

function registerActivityRuntimeSave(): void
{
    static $registered = false;
    if ($registered) return;
    $registered = true;
    register_shutdown_function('saveActivityRuntime');
}

function requireOpenActivity(): void
{
    requireStudent();
    if (!empty($_SESSION['submitted']) || !empty($_SESSION['already_submitted'])) {
        header('Location: submitted.php');
        exit;
    }
}

function studentProgress(string $active): string
{
    $steps = ['Student Info', 'Instructions', 'Activity', 'Submit'];
    $html = '<div class="student-progress" aria-label="Activity progress">';

    foreach ($steps as $step) {
        $class = $step === $active ? ' class="is-active" aria-current="step"' : '';
        $html .= '<span' . $class . '>' . escape($step) . '</span>';
    }

    return $html . '</div>';
}

function studentBrandHeader(): string
{
    $name = escape((string) ($_SESSION['full_name'] ?? 'Student'));
    return '<script>document.documentElement.classList.add("student-auth-pending")</script>'
        . '<header class="brand-header student-brand-header">'
        . '<a href="index.php" class="brand-link"><img src="images/eduschedx-logo.svg" alt="" class="brand-logo"><span class="brand-name">EduSched<span>X</span></span></a>'
        . '<details class="student-account"><summary><span class="student-account-name">' . $name . '</span><span class="student-account-toggle"><i class="bi bi-chevron-down" aria-hidden="true"></i></span></summary>'
        . '<div class="student-account-menu"><div><small>Signed in as</small><strong>' . $name . '</strong></div><form id="student-logout-form" action="logout.php" method="post"><input type="hidden" name="csrf_token" value="' . escape(csrfToken()) . '"><button type="button" data-logout-open><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Logout</button></form></div></details>'
        . '</header>'
        . '<dialog class="submit-dialog logout-dialog" data-logout-dialog aria-labelledby="logout-dialog-title"><div class="submit-dialog-head"><h2 id="logout-dialog-title">Logout?</h2><button type="button" class="submit-dialog-close" data-logout-cancel aria-label="Close">&times;</button></div><p>Are you sure you want to log out of your student account?</p><div class="submit-dialog-actions"><button type="button" class="btn btn-light" data-logout-cancel>Cancel</button><button type="button" class="btn btn-danger" data-logout-confirm><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Yes, Logout</button></div></dialog>'
        . '<script src="account.js" defer></script>';
}

function studentShuffledOrder(array $ids, string $part): array
{
    $studentKey = normalizeStudentId((string) ($_SESSION['student_id'] ?? 'student'));
    usort($ids, static function ($left, $right) use ($studentKey, $part): int {
        $leftHash = hash('sha256', $studentKey . '|' . $part . '|' . (string) $left);
        $rightHash = hash('sha256', $studentKey . '|' . $part . '|' . (string) $right);
        return $leftHash <=> $rightHash ?: ((int) $left <=> (int) $right);
    });
    return array_values($ids);
}

function partThreeDeadline(): int
{
    $studentId = normalizeStudentId((string) ($_SESSION['student_id'] ?? ''));
    if ($studentId === '') return 0;

    $statement = database()->prepare('SELECT part3_deadline FROM student_activity_runtime WHERE student_id = :student_id');
    $statement->execute(['student_id' => $studentId]);
    $savedDeadline = (int) ($statement->fetchColumn() ?: 0);

    if ($savedDeadline <= 0) {
        $savedDeadline = time() + 60;
        $statement = database()->prepare(
            'INSERT INTO student_activity_runtime (student_id, part3_deadline, updated_at)
             VALUES (:student_id, :deadline, :updated_at)
             ON CONFLICT(student_id) DO UPDATE SET
                part3_deadline = CASE
                    WHEN student_activity_runtime.part3_deadline IS NULL OR student_activity_runtime.part3_deadline <= 0
                    THEN excluded.part3_deadline ELSE student_activity_runtime.part3_deadline END,
                updated_at = excluded.updated_at'
        );
        $statement->execute([
            'student_id' => $studentId,
            'deadline' => $savedDeadline,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $readBack = database()->prepare('SELECT part3_deadline FROM student_activity_runtime WHERE student_id = :student_id');
        $readBack->execute(['student_id' => $studentId]);
        $savedDeadline = (int) ($readBack->fetchColumn() ?: $savedDeadline);
    }

    $_SESSION['part_three_deadline'] = $savedDeadline;
    return $savedDeadline;
}

function expirePartThree(): void
{
    if (!empty($_SESSION['part_three_complete']) || !empty($_SESSION['part_three_ready'])) return;
    $titles = ['Severity', 'User Type', 'Failed Attempts', 'Access Status', 'Security Response'];
    $_SESSION['part_three_attempts'] = 2;
    if (trim((string) ($_SESSION['part_three_draft'] ?? '')) === '') {
        unset($_SESSION['part_three_generated']);
    }
    $_SESSION['part_three_results'] = array_map(
        static fn (string $title, int $index): array => ['challenge' => $index + 1, 'title' => $title, 'passed' => false],
        $titles,
        array_keys($titles)
    );
    $_SESSION['part_three_score'] = 0;
    $_SESSION['part_three_ready'] = true;
    $_SESSION['part_three_timed_out'] = true;
}

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

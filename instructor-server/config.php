<?php
declare(strict_types=1);

$adminPasswordHash = getenv('EDUSCHEDX_ADMIN_PASSWORD_HASH');
$localPasswordFile = dirname(__DIR__) . '/storage/admin-password.local';
if ((!is_string($adminPasswordHash) || trim($adminPasswordHash) === '') && is_file($localPasswordFile)) {
    $adminPasswordHash = file_get_contents($localPasswordFile);
}
define('ADMIN_PASSWORD_HASH', is_string($adminPasswordHash) ? trim($adminPasswordHash) : '');
define('DATABASE_PATH', dirname(__DIR__) . '/storage/activity.sqlite');
define('DUPLICATE_LOG_PATH', dirname(__DIR__) . '/storage/duplicate-submissions.log');
define('ACTIVITY_TIMEZONE', 'Asia/Manila');

if (!defined('EDUSCHEDX_SHARED_CONFIG') && session_status() !== PHP_SESSION_ACTIVE) {
    $sessionDirectory = dirname(__DIR__) . '/storage/sessions';
    if (!is_dir($sessionDirectory)) {
        mkdir($sessionDirectory, 0775, true);
    }
    session_save_path($sessionDirectory);
    session_name('eduschedx_admin');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

function database(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $directory = dirname(DATABASE_PATH);
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    $pdo = new PDO('sqlite:' . DATABASE_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS activity_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            submission_id TEXT NOT NULL UNIQUE,
            student_name TEXT NOT NULL,
            station TEXT NOT NULL,
            ip_address TEXT NULL,
            score INTEGER NOT NULL,
            total INTEGER NOT NULL,
            results_json TEXT NOT NULL,
            submitted_at TEXT NOT NULL
        )'
    );

    $columns = $pdo->query('PRAGMA table_info(activity_submissions)')->fetchAll();
    $columnNames = array_column($columns, 'name');
    if (!in_array('student_name_normalized', $columnNames, true)) {
        $pdo->exec("ALTER TABLE activity_submissions ADD COLUMN student_name_normalized TEXT NOT NULL DEFAULT ''");
    }
    if (!in_array('station_normalized', $columnNames, true)) {
        $pdo->exec("ALTER TABLE activity_submissions ADD COLUMN station_normalized TEXT NOT NULL DEFAULT ''");
    }
    if (!in_array('student_code', $columnNames, true)) {
        $pdo->exec('ALTER TABLE activity_submissions ADD COLUMN student_code TEXT NULL');
    }
    if (!in_array('student_id', $columnNames, true)) {
        $pdo->exec('ALTER TABLE activity_submissions ADD COLUMN student_id TEXT NULL');
    }
    if (!in_array('ip_address', $columnNames, true)) {
        $pdo->exec('ALTER TABLE activity_submissions ADD COLUMN ip_address TEXT NULL');
    }
    if (!in_array('feedback', $columnNames, true)) {
        $pdo->exec('ALTER TABLE activity_submissions ADD COLUMN feedback TEXT NULL');
    }

    $pdo->exec(
        "UPDATE activity_submissions
         SET student_name_normalized = lower(trim(student_name)),
             station_normalized = upper(trim(station))
         WHERE student_name_normalized = '' OR station_normalized = ''"
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_submissions_student ON activity_submissions(student_name)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_submissions_station ON activity_submissions(station)');
    $pdo->exec('DROP INDEX IF EXISTS idx_submissions_identity');
    $pdo->exec('DROP INDEX IF EXISTS idx_unique_student_name');
    $pdo->exec('DROP INDEX IF EXISTS idx_unique_station');
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_unique_student_code ON activity_submissions(student_code) WHERE student_code IS NOT NULL AND student_code <> ''");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_unique_student_id ON activity_submissions(student_id) WHERE student_id IS NOT NULL AND student_id <> ''");
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS submission_unique_keys (
            key_type TEXT NOT NULL,
            key_value TEXT NOT NULL,
            submission_id TEXT NOT NULL,
            PRIMARY KEY (key_type, key_value)
        )'
    );
    $pdo->exec(
        "INSERT OR IGNORE INTO submission_unique_keys (key_type, key_value, submission_id)
         SELECT 'SUBMISSION_ID', submission_id, submission_id FROM activity_submissions"
    );
    $pdo->exec(
        "INSERT OR IGNORE INTO submission_unique_keys (key_type, key_value, submission_id)
         SELECT 'STUDENT', student_name_normalized, submission_id FROM activity_submissions
         WHERE student_name_normalized <> ''"
    );
    $pdo->exec(
        "INSERT OR IGNORE INTO submission_unique_keys (key_type, key_value, submission_id)
         SELECT 'STATION', station_normalized, submission_id FROM activity_submissions
         WHERE station_normalized <> ''"
    );
    $pdo->exec(
        "INSERT OR IGNORE INTO submission_unique_keys (key_type, key_value, submission_id)
         SELECT 'STUDENT_CODE', student_code, submission_id FROM activity_submissions
         WHERE student_code IS NOT NULL AND student_code <> ''"
    );
    $pdo->exec(
        "INSERT OR IGNORE INTO submission_unique_keys (key_type, key_value, submission_id)
         SELECT 'STUDENT_ID', student_id, submission_id FROM activity_submissions
         WHERE student_id IS NOT NULL AND student_id <> ''"
    );

    importOfficialRoster($pdo);

    return $pdo;
}

function formatSubmissionTime(string $utcTimestamp): string
{
    try {
        $submittedAt = new DateTimeImmutable($utcTimestamp, new DateTimeZone('UTC'));
        return $submittedAt
            ->setTimezone(new DateTimeZone(ACTIVITY_TIMEZONE))
            ->format('M j, g:i A');
    } catch (Exception) {
        return '—';
    }
}

function normalizeStudentId(string $studentId): string
{
    return strtoupper(trim($studentId));
}

function canonicalStation(string $station): string
{
    $station = strtoupper(trim($station));
    if (!preg_match('/^PC-(\d{1,3})$/', $station, $match) || (int) $match[1] < 1) {
        return '';
    }
    return 'PC-' . str_pad((string) ((int) $match[1]), 2, '0', STR_PAD_LEFT);
}

function importOfficialRoster(PDO $pdo): void
{
    $roster = require __DIR__ . '/roster-data.php';
    $studentIds = [];
    $stations = [];
    $names = [];
    $validated = [];

    foreach ($roster as $entry) {
        $studentId = normalizeStudentId((string) ($entry['student_id'] ?? ''));
        $fullName = preg_replace('/\s+/u', ' ', trim((string) ($entry['full_name'] ?? ''))) ?? '';
        $normalizedName = normalizeStudentName($fullName);
        $station = canonicalStation((string) ($entry['assigned_station'] ?? ''));
        $activityCodeHash = trim((string) ($entry['activity_code_hash'] ?? ''));

        if (!preg_match('/^\d{2}-\d-\d{5}$/', $studentId)
            || $fullName === ''
            || $station === ''
            || password_get_info($activityCodeHash)['algoName'] === 'unknown') {
            throw new RuntimeException('Invalid official roster record: ' . $studentId);
        }
        if (isset($studentIds[$studentId])) {
            throw new RuntimeException('Duplicate roster Student ID: ' . $studentId);
        }
        if (isset($stations[$station])) {
            throw new RuntimeException('Duplicate roster station: ' . $station);
        }
        if (isset($names[$normalizedName])) {
            throw new RuntimeException('Conflicting roster assignment: ' . $fullName);
        }

        $studentIds[$studentId] = true;
        $stations[$station] = true;
        $names[$normalizedName] = true;
        $validated[] = [$studentId, $fullName, $normalizedName, $station, $activityCodeHash];
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS official_roster (
            student_id TEXT PRIMARY KEY,
            full_name TEXT NOT NULL,
            name_normalized TEXT NOT NULL UNIQUE,
            assigned_station TEXT NOT NULL UNIQUE,
            activity_code_hash TEXT NOT NULL
        )'
    );

    $rosterColumns = $pdo->query('PRAGMA table_info(official_roster)')->fetchAll();
    if (!in_array('activity_code_hash', array_column($rosterColumns, 'name'), true)) {
        $pdo->exec("ALTER TABLE official_roster ADD COLUMN activity_code_hash TEXT NOT NULL DEFAULT ''");
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM official_roster');
        $insert = $pdo->prepare(
            'INSERT INTO official_roster (student_id, full_name, name_normalized, assigned_station, activity_code_hash)
             VALUES (:student_id, :full_name, :name_normalized, :assigned_station, :activity_code_hash)'
        );
        foreach ($validated as [$studentId, $fullName, $normalizedName, $station, $activityCodeHash]) {
            $insert->execute([
                'student_id' => $studentId,
                'full_name' => $fullName,
                'name_normalized' => $normalizedName,
                'assigned_station' => $station,
                'activity_code_hash' => $activityCodeHash,
            ]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function normalizeStudentName(string $name): string
{
    $name = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);
    return mb_strtolower($name, 'UTF-8');
}

function observedClientIp(): ?string
{
    $address = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    return filter_var($address, FILTER_VALIDATE_IP) !== false ? $address : null;
}

function formatClientIp(?string $address): string
{
    $address = trim((string) $address);
    if (in_array($address, ['127.0.0.1', '::1'], true)) {
        return 'Localhost';
    }
    return $address !== '' ? $address : '—';
}

function logDuplicateSubmission(string $reason, string $submissionId): void
{
    $line = sprintf("[%s] %s submission_id=%s%s", gmdate('c'), $reason, $submissionId, PHP_EOL);
    error_log($line, 3, DUPLICATE_LOG_PATH);
}

function adminEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function adminCsrfToken(): string
{
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

function adminCsrfIsValid(?string $token): bool
{
    return is_string($token) && hash_equals(adminCsrfToken(), $token);
}

function requireAdmin(): void
{
    if (empty($_SESSION['admin_authenticated'])) {
        header('Location: login.php');
        exit;
    }
}

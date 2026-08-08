<?php
/**
 * =====================================================================
 *  ULK ATTENDANCE SYSTEM - Global configuration
 *  Kigali Independent University (ULK)
 * =====================================================================
 *  This file is included by every page / API endpoint. It:
 *    1. Starts a secure PHP session
 *    2. Opens the PDO connection to MySQL
 *    3. Provides RBAC helpers (requireLogin, requireRole, ...)
 *    4. Provides small utilities (e(), jsonResponse, CSRF helpers)
 * =====================================================================
 */

session_start();

/* ------------------------------------------------------------------
 * Database credentials - adjust to match your local MySQL setup.
 * ------------------------------------------------------------------ */
define('DB_HOST', 'localhost');
define('DB_NAME', 'ulk_attendance_db');
define('DB_USER', 'root');
define('DB_PASS', '');

/* Base URL used to reference project links (scripts/styles/navigation).
 * Change this if you deploy the folder under a different name. */
define('BASE_URL', 'http://localhost/attendance');

/* Minimum attendance rate (%) required for exam eligibility. */
define('ELIGIBILITY_THRESHOLD', 85);

/* Kind of attendance marks that count as "present" for the rate. */
define('ATTENDED_STATUSES', serialize(['Present', 'Late']));

/* ------------------------------------------------------------------
 * PDO connection (prepared statements only, exceptions on error).
 * ------------------------------------------------------------------ */
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

/* ------------------------------------------------------------------
 * HTML escaping helper - ALWAYS escape anything echoed to the browser.
 * ------------------------------------------------------------------ */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/* ------------------------------------------------------------------
 * Authentication / RBAC helpers
 * ------------------------------------------------------------------ */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    return isLoggedIn()
        ? ['id' => $_SESSION['user_id'], 'name' => $_SESSION['full_name'], 'email' => $_SESSION['email'], 'role' => $_SESSION['role']]
        : null;
}

function isRole(string $role): bool
{
    return isLoggedIn() && $_SESSION['role'] === $role;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();
    if (!isRole($role)) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

/* Look up the authored student_reg_no for the logged in student. */
function currentStudentRegNo(?PDO $pdo): ?string
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT student_reg_no FROM students WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['student_reg_no'] : null;
}

/* Look up the authored lecturer_id for the logged in lecturer. */
function currentLecturerId(?PDO $pdo): ?int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT lecturer_id FROM lecturers WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int) $row['lecturer_id'] : null;
}

/* ------------------------------------------------------------------
 * CSRF protection
 * ------------------------------------------------------------------ */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/* ------------------------------------------------------------------
 * JSON API helpers
 * ------------------------------------------------------------------ */
function api_json($payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

/* Reads a JSON request body and merges with POST fields. */
function api_input(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

function require_csrf_api(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!verify_csrf($token)) {
        api_json(['ok' => false, 'error' => 'Invalid or missing CSRF token.'], 419);
    }
}

/* ------------------------------------------------------------------
 * Attendance business logic
 *   attendance rate = (Present + Late) / total recorded sessions
 * ------------------------------------------------------------------ */
function attendance_rate(array $row): float
{
    $total = (int) ($row['total_sessions'] ?? 0);
    if ($total <= 0) {
        return 100.0;
    }
    return round(((int) ($row['attended'] ?? 0)) / $total * 100, 1);
}

function is_eligible(array $row): bool
{
    return attendance_rate($row) >= ELIGIBILITY_THRESHOLD;
}

/* Returns Bootstrap/Tailwind-friendly badge colour for a attendance rate. */
function rate_badge(float $rate): string
{
    return $rate >= ELIGIBILITY_THRESHOLD ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700';
}

/* Renders a pill badge for an attendance / session status. */
function status_badge(string $status): string
{
    $map = [
        'Present' => 'badge-present',
        'Absent'  => 'badge-absent',
        'Late'    => 'badge-late',
        'Excused' => 'badge-excused',
        'Open'    => 'badge-open',
        'Closed'  => 'badge-closed',
    ];
    $cls = $map[$status] ?? '';
    return '<span class="badge ' . $cls . '">' . e($status) . '</span>';
}
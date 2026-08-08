<?php
/**
 * ULK ATTENDANCE SYSTEM - API: reports & CSV exports
 *
 *   GET  ?action=students&course_code=CS101  -> per-student report (JSON)
 *   GET  ?course_code=CS101&action=register_csv -> full register (CSV)
 *   GET  ?action=universal_csv               -> per-course roll-up (CSV, admin)
 *
 * Reports are visible to Admins; a Lecturer may download reports but only
 * for courses they teach.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/aggregates.php';
requireLogin();

$action = $_GET['action'] ?? '';

/* Allowed only if admin OR (lecturer assigned to this course). */
function can_view_course(PDO $pdo, string $courseCode): bool
{
    if (isRole('Admin')) return true;
    if (isRole('Lecturer')) {
        $stmt = $pdo->prepare('SELECT 1 FROM lecturer_courses WHERE lecturer_id = ? AND course_code = ?');
        $stmt->execute([currentLecturerId($pdo), $courseCode]);
        return (bool) $stmt->fetchColumn();
    }
    return false;
}

/* Helper: stream a CSV attachment to the browser. */
function emit_csv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $r) {
        fputcsv($out, array_map(fn($v) => $v ?? '', $r));
    }
    fclose($out);
    exit;
}

/* ---- student drill-down (JSON / admin reports page) ---- */
if ($action === 'students') {
    $courseCode = mb_strtoupper(trim($_GET['course_code'] ?? ''));
    if (!can_view_course($pdo, $courseCode)) {
        api_json(['ok' => false, 'error' => 'You cannot view this course.'], 403);
    }
    api_json(['ok' => true, 'rows' => get_student_report_per_course($pdo, $courseCode)]);
}

/* ---- raw register CSV for one course ---- */
if ($action === 'register_csv') {
    $course = mb_strtoupper(trim($_GET['course_code'] ?? ''));
    if (!can_view_course($pdo, $course)) {
        http_response_code(403);
        exit('Not authorised.');
    }
    $rows = get_course_register($pdo, $course);
    emit_csv('attendance_register_' . $course . '.csv', [
        'Course', 'Title', 'Date', 'Start', 'End', 'Session Status',
        'StudentRegNo', 'Status', 'RecordedAt',
    ], $rows);
}

/* ---- per-course roll-up CSV (admin only) ---- */
if ($action === 'universal_csv') {
    if (!isRole('Admin')) {
        http_response_code(403);
        exit('Not authorised.');
    }
    $rows = get_course_list_report($pdo);
    emit_csv('ulk_attendance_summary.csv', [
        'CourseCode', 'Title', 'Credits', 'Department', 'Lecturer(s)',
        'Students', 'Sessions', 'Records', 'AverageRate(%)',
    ], $rows);
}

api_json(['ok' => false, 'error' => 'Unknown report action: ' . $action], 400);
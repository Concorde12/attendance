<?php
/**
 * ULK ATTENDANCE SYSTEM - API: sessions (lecturer only)
 *
 *   POST {action:'create', course_code, session_date, start_time, end_time}
 *   POST {action:'close',  session_id}
 */
require_once __DIR__ . '/../config.php';
requireRole('Lecturer');

$input = api_input();
$input = array_merge($_GET, $input);
$action = $input['action'] ?? '';
$lid = currentLecturerId($pdo);

function lecturer_owns_course(PDO $pdo, int $lecturerId, string $courseCode): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM lecturer_courses WHERE lecturer_id = ? AND course_code = ?');
    $stmt->execute([$lecturerId, $courseCode]);
    return (bool) $stmt->fetchColumn();
}

function lecturer_owns_session(PDO $pdo, int $sessionId, int $lecturerId): bool
{
    $stmt = $pdo->prepare('
        SELECT 1 FROM sessions s
        JOIN lecturer_courses lc ON lc.course_code = s.course_code AND lc.lecturer_id = ?
        WHERE s.session_id = ?');
    $stmt->execute([$lecturerId, $sessionId]);
    return (bool) $stmt->fetchColumn();
}

switch ($action) {

    case 'create': {
        require_csrf_api();
        $course = $input['course_code'] ?? '';
        $date   = $input['session_date'] ?? '';
        $start  = $input['start_time'] ?? '';
        $end    = $input['end_time'] ?? '';

        if ($course === '') {
            api_json(['ok' => false, 'error' => 'Course is required.'], 422);
        }
        if (!lecturer_owns_course($pdo, $lid, $course)) {
            api_json(['ok' => false, 'error' => 'You are not assigned to this course.'], 403);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $start)
            || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $end)) {
            api_json(['ok' => false, 'error' => 'Invalid date/time format.'], 422);
        }

        $today = date('Y-m-d');
        $status = $date <= $today ? 'Closed' : 'Open';

        $stmt = $pdo->prepare('
            INSERT INTO sessions (course_code, lecturer_id, session_date, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$course, $lid, $date, $start, $end, $status]);

        api_json(['ok' => true, 'session_id' => (int) $pdo->lastInsertId(), 'message' => 'Session created.']);
    }

    case 'close': {
        require_csrf_api();
        $sessionId = (int) ($input['session_id'] ?? 0);
        if (!$sessionId || !lecturer_owns_session($pdo, $sessionId, $lid)) {
            api_json(['ok' => false, 'error' => 'Session not found or not assigned to you.'], 403);
        }
        $pdo->prepare("UPDATE sessions SET status = 'Closed' WHERE session_id = ?")
            ->execute([$sessionId]);
        api_json(['ok' => true, 'message' => 'Session closed.']);
    }

    default:
        api_json(['ok' => false, 'error' => 'Unknown action: ' . $action], 400);
}
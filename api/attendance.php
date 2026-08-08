<?php
/**
 * ULK ATTENDANCE SYSTEM - API: attendance records (lecturer only)
 *
 *   GET  ?action=roster&session_id=1   -> session + student roster with marks
 *   POST {action:'save', session_id, records:[{student_reg_no, status}]}
 *                                       -> bulk upsert of marks
 */
require_once __DIR__ . '/../config.php';
requireRole('Lecturer');

$input = api_input();
$input = array_merge($_GET, $input);   // accept query-string params too (e.g. GET roster)
$action = $input['action'] ?? '';

$valid = ['Present', 'Absent', 'Late', 'Excused'];

/* Verify the session belongs to one of the lecturer's courses. */
function lecturer_owns_session(PDO $pdo, int $sessionId, int $lecturerId): ?array
{
    $stmt = $pdo->prepare('
        SELECT s.*, c.title AS course_title
        FROM sessions s
        JOIN lecturer_courses lc ON lc.course_code = s.course_code AND lc.lecturer_id = ?
        JOIN courses c ON c.course_code = s.course_code
        WHERE s.session_id = ?');
    $stmt->execute([$lecturerId, $sessionId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/* Roster = enrolled students of the course + whichever mark each has. */
function session_roster(PDO $pdo, int $sessionId, string $courseCode): array
{
    $sql = '
        SELECT u.full_name, st.student_reg_no, ar.status
        FROM enrollments e
        JOIN students st ON st.student_reg_no = e.student_reg_no
        JOIN users u ON u.user_id = st.user_id
        LEFT JOIN attendance_records ar
               ON ar.session_id = ? AND ar.student_reg_no = e.student_reg_no
        WHERE e.course_code = ?
        ORDER BY u.full_name, st.student_reg_no';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sessionId, $courseCode]);
    return $stmt->fetchAll();
}

switch ($action) {

    case 'roster': {
        $sessionId = (int) ($input['session_id'] ?? 0);
        $session = $sessionId ? lecturer_owns_session($pdo, $sessionId, currentLecturerId($pdo)) : null;

        if (!$session) {
            api_json(['ok' => false, 'error' => 'Session not found or not assigned to you.'], 403);
        }
        api_json([
            'ok'      => true,
            'session' => $session,
            'rows'    => session_roster($pdo, $sessionId, $session['course_code']),
        ]);
    }

    case 'save': {
        require_csrf_api();
        $sessionId = (int) ($input['session_id'] ?? 0);
        $records   = $input['records'] ?? [];

        $lid = currentLecturerId($pdo);
        $session = $sessionId ? lecturer_owns_session($pdo, $sessionId, $lid) : null;
        if (!$session) {
            api_json(['ok' => false, 'error' => 'Session not found or not assigned to you.'], 403);
        }
        if (!is_array($records) || !$records) {
            api_json(['ok' => false, 'error' => 'No records supplied.'], 422);
        }

        // Fetch the set of currently-enrolled registration numbers once.
        $en = $pdo->prepare('SELECT student_reg_no FROM enrollments WHERE course_code = ?');
        $en->execute([$session['course_code']]);
        $enrolled = array_flip($en->fetchAll(PDO::FETCH_COLUMN));

        // Prepare the upsert once, reuse for every student.
        $up = $pdo->prepare('
            INSERT INTO attendance_records (session_id, student_reg_no, status, timestamp)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE status = VALUES(status), timestamp = NOW()');

        $saved     = 0;
        $bad       = 0;
        $skipped   = 0;
        $inTx      = $pdo->beginTransaction();

        foreach ($records as $r) {
            $reg   = $r['student_reg_no'] ?? '';
            $status = $r['status'] ?? '';
            if (!isset($enrolled[$reg])) {
                $skipped++;              // someone not on the roster
                continue;
            }
            if (!in_array($status, $valid, true)) {
                $bad++;
                continue;
            }
            $up->execute([$sessionId, $reg, $status]);
            $saved++;
        }

        if ($inTx && $pdo->inTransaction()) {
            $pdo->commit();
        }

        api_json([
            'ok'     => true,
            'saved'  => $saved,
            'bad'    => $bad,
            'skipped' => $skipped,
            'message' => "Saved $saved mark(s).",
        ]);
    }

    default:
        api_json(['ok' => false, 'error' => 'Unknown action: ' . $action], 400);
}
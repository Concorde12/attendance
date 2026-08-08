<?php
/**
 * ULK ATTENDANCE SYSTEM - API: rosters / enrolments (admin only)
 *
 *   GET  ?course_code=CS101        -> enrolled students + available students
 *   POST {action:'enroll', course_code, student_reg_no}
 *   POST {action:'remove', course_code, student_reg_no}
 */
require_once __DIR__ . '/../config.php';
requireRole('Admin');

$input  = api_input();
$action = $_GET['action'] ?? ($input['action'] ?? '');

switch ($action) {

    case 'list': {
        $course = mb_strtoupper(trim($_GET['course_code'] ?? $input['course_code'] ?? ''));

        // Enrolled students (plus their info).
        $en = $pdo->prepare('
            SELECT s.student_reg_no, u.full_name, s.major
            FROM enrollments e
            JOIN students st ON st.student_reg_no = e.student_reg_no
            JOIN users u ON u.user_id = st.user_id
            WHERE e.course_code = ?
            ORDER BY u.full_name');
        $en->execute([$course]);
        $enrolled = $en->fetchAll();

        // Students not yet enrolled in this course.
        $av = $pdo->prepare('
            SELECT s.student_reg_no, u.full_name
            FROM students s
            JOIN users u ON u.user_id = s.user_id
            WHERE s.student_reg_no NOT IN (
                SELECT student_reg_no FROM enrollments WHERE course_code = ?
            )
            ORDER BY u.full_name');
        $av->execute([$course]);

        api_json(['ok' => true, 'rows' => $enrolled, 'available' => $av->fetchAll()]);
    }

    case 'enroll': {
        require_csrf_api();
        $course = mb_strtoupper(trim($input['course_code'] ?? ''));
        $reg    = trim($input['student_reg_no'] ?? '');

        if ($course === '' || $reg === '') {
            api_json(['ok' => false, 'error' => 'Course and student are required.'], 422);
        }

        $courseOk = $pdo->prepare('SELECT 1 FROM courses WHERE course_code = ?');
        $courseOk->execute([$course]);
        $studentOk = $pdo->prepare('SELECT 1 FROM students WHERE student_reg_no = ?');
        $studentOk->execute([$reg]);
        if (!$courseOk->fetchColumn() || !$studentOk->fetchColumn()) {
            api_json(['ok' => false, 'error' => 'Course or student does not exist.'], 404);
        }

        $already = $pdo->prepare('SELECT 1 FROM enrollments WHERE course_code = ? AND student_reg_no = ?');
        $already->execute([$course, $reg]);
        if ($already->fetchColumn()) {
            api_json(['ok' => false, 'error' => 'Student is already enrolled.'], 409);
        }

        $pdo->prepare('INSERT INTO enrollments (course_code, student_reg_no) VALUES (?, ?)')
            ->execute([$course, $reg]);
        api_json(['ok' => true, 'message' => $reg . ' enrolled in ' . $course . '.']);
    }

    case 'remove': {
        require_csrf_api();
        $course = mb_strtoupper(trim($input['course_code'] ?? ''));
        $reg    = trim($input['student_reg_no'] ?? '');
        $pdo->prepare('DELETE FROM enrollments WHERE course_code = ? AND student_reg_no = ?')
            ->execute([$course, $reg]);
        api_json(['ok' => true, 'message' => 'Removed from roster.']);
    }

    default:
        api_json(['ok' => false, 'error' => 'Unknown action: ' . $action], 400);
}
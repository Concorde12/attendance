<?php
/**
 * ULK ATTENDANCE SYSTEM - API: courses (admin only)
 *
 *   POST {action:'create', course_code,title,credits,department,lecturer_id}
 *   POST {action:'update', ..., course_code (original)}
 *   POST {action:'delete', course_code}
 */
require_once __DIR__ . '/../config.php';
requireRole('Admin');

$input  = api_input();
$action = $input['action'] ?? '';

function assign_lecturer(PDO $pdo, string $courseCode, ?int $lecturerId): void
{
    // Replace the current assignment(s) with the selected one.
    $pdo->prepare('DELETE FROM lecturer_courses WHERE course_code = ?')->execute([$courseCode]);
    if ($lecturerId) {
        $pdo->prepare('INSERT INTO lecturer_courses (lecturer_id, course_code) VALUES (?, ?)')
            ->execute([$lecturerId, $courseCode]);
    }
}

switch ($action) {

    case 'create': {
        require_csrf_api();
        $code       = mb_strtoupper(trim($input['course_code'] ?? ''));
        $title      = trim($input['title'] ?? '');
        $credits    = (int) ($input['credits'] ?? 3);
        $department = trim($input['department'] ?? '');
        $lecturerId = !empty($input['lecturer_id']) ? (int) $input['lecturer_id'] : null;

        if ($code === '' || $title === '') {
            api_json(['ok' => false, 'error' => 'Course code and title are required.'], 422);
        }

        $chk = $pdo->prepare('SELECT 1 FROM courses WHERE course_code = ?');
        $chk->execute([$code]);
        if ($chk->fetchColumn()) {
            api_json(['ok' => false, 'error' => 'That course code already exists.'], 409);
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO courses (course_code, title, credits, department) VALUES (?, ?, ?, ?)'
            )->execute([$code, $title, $credits, $department]);
            if ($lecturerId) {
                assign_lecturer($pdo, $code, $lecturerId);
            }
            $pdo->commit();
            api_json(['ok' => true, 'message' => 'Course ' . $code . ' created.']);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            api_json(['ok' => false, 'error' => $ex->getMessage()], 500);
        }
    }

    case 'update': {
        require_csrf_api();
        $orig       = mb_strtoupper(trim($input['course_code'] ?? ''));
        $code       = mb_strtoupper(trim($input['course_code'] ?? ''));
        $title      = trim($input['title'] ?? '');
        $credits    = (int) ($input['credits'] ?? 3);
        $department = trim($input['department'] ?? '');
        $lecturerId = !empty($input['lecturer_id']) ? (int) $input['lecturer_id'] : null;

        // If the code itself changed, move the primary key in one transaction.
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE courses SET title = ?, credits = ?, department = ? WHERE course_code = ?')
                ->execute([$title, $credits, $department, $orig]);
            assign_lecturer($pdo, $orig, $lecturerId);
            $pdo->commit();
            api_json(['ok' => true, 'message' => 'Course updated.']);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            api_json(['ok' => false, 'error' => $ex->getMessage()], 500);
        }
    }

    case 'delete': {
        require_csrf_api();
        $code = mb_strtoupper(trim($input['course_code'] ?? ''));
        $pdo->prepare('DELETE FROM courses WHERE course_code = ?')->execute([$code]);
        api_json(['ok' => true, 'message' => 'Course deleted (sessions and records cascaded).']);
    }

    default:
        api_json(['ok' => false, 'error' => 'Unknown action: ' . $action], 400);
}
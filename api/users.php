<?php
/**
 * ULK ATTENDANCE SYSTEM - API: users (admin only)
 *
 *   GET  ?action=list&role=Student           -> user list (optional role filter)
 *   POST {action:'create', full_name, email, password, role, [student_reg_no, major]}
 *   POST {action:'reset_password', user_id, password}
 *   POST {action:'delete', user_id}
 */
require_once __DIR__ . '/../config.php';
requireRole('Admin');

$input  = api_input();
$action = $_GET['action'] ?? ($input['action'] ?? '');

switch ($action) {

    case 'list': {
        $role    = $_GET['role'] ?? '';
        $sql     = 'SELECT u.user_id, u.full_name, u.email, u.role, s.student_reg_no
                    FROM users u LEFT JOIN students s ON s.user_id = u.user_id';
        $params  = [];
        if ($role !== '') {
            $sql .= ' WHERE u.role = ?';
            $params[] = $role;
        }
        $sql .= ' ORDER BY u.full_name';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        api_json(['ok' => true, 'rows' => $stmt->fetchAll()]);
    }

    case 'create': {
        require_csrf_api();
        $name     = trim($input['full_name'] ?? '');
        $email    = mb_strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        $role     = $input['role'] ?? '';

        if ($name === '' || $email === '' || $password === '' || $role === '') {
            api_json(['ok' => false, 'error' => 'All fields are required.'], 422);
        }
        if (!in_array($role, ['Student', 'Lecturer', 'Admin'], true)) {
            api_json(['ok' => false, 'error' => 'Invalid role.'], 422);
        }
        if (strlen($password) < 8) {
            api_json(['ok' => false, 'error' => 'Password must be at least 8 characters.'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            api_json(['ok' => false, 'error' => 'Invalid email address.'], 422);
        }

        $chk = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetchColumn()) {
            api_json(['ok' => false, 'error' => 'That email is already registered.'], 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hash, $role]);
            $userId = (int) $pdo->lastInsertId();

            if ($role === 'Student') {
                $reg   = trim($input['student_reg_no'] ?? '');
                $major = trim($input['major'] ?? '');
                if ($reg === '') {
                    throw new Exception('Registration number is required for students.');
                }
                $regChk = $pdo->prepare('SELECT 1 FROM students WHERE student_reg_no = ?');
                $regChk->execute([$reg]);
                if ($regChk->fetchColumn()) {
                    throw new Exception('That registration number is already in use.');
                }
                $pdo->prepare('INSERT INTO students (student_reg_no, user_id, major) VALUES (?, ?, ?)')
                    ->execute([$reg, $userId, $major]);
            } elseif ($role === 'Lecturer') {
                $pdo->prepare('INSERT INTO lecturers (user_id) VALUES (?)')->execute([$userId]);
            }

            $pdo->commit();
            api_json(['ok' => true, 'message' => 'Account created for ' . $name . '.']);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            api_json(['ok' => false, 'error' => $ex->getMessage()], 500);
        }
    }

    case 'reset_password': {
        $userId   = (int) ($input['user_id'] ?? 0);
        $password = $input['password'] ?? '';
        if (strlen($password) < 8) {
            api_json(['ok' => false, 'error' => 'Password must be at least 8 characters.'], 422);
        }
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
        api_json(['ok' => true, 'message' => 'Password updated.']);
    }

    case 'delete': {
        require_csrf_api();
        $userId    = (int) ($input['user_id'] ?? 0);
        $count = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
        $count->execute([$userId]);
        api_json(['ok' => true, 'message' => 'User deleted' . ($count->rowCount() ? ' (and linked records).' : '.')]);
    }

    default:
        api_json(['ok' => false, 'error' => 'Unknown action: ' . $action], 400);
}
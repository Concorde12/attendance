<?php
/**
 * =====================================================================
 *  ULK ATTENDANCE SYSTEM - Login page
 *  Processes the login form (POST) or renders the login view.
 * =====================================================================
 */
require_once __DIR__ . '/config.php';

// Already logged in? Send to dashboard.
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([mb_strtolower($email)]);
            $user = $stmt->fetch();

            // Compare against the stored bcrypt hash (or a dummy hash when
            // the account does not exist to avoid user-enumeration timing).
            $dummy = password_hash('dummy', PASSWORD_DEFAULT);
            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int) $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                header('Location: ' . BASE_URL . '/dashboard.php');
                exit;
            }
            password_verify($password, $dummy);
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ULK Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

<div class="w-full max-w-md">
    <!-- Brand -->
    <div class="text-center mb-8">
        <div class="mx-auto h-16 w-16 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-2xl font-bold shadow-lg mb-4">ULK</div>
        <h1 class="text-2xl font-bold text-slate-800">Attendance Management System</h1>
        <p class="text-sm text-slate-500">Kigali Independent University</p>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <h2 class="text-lg font-semibold text-slate-800 mb-1">Sign in to your account</h2>
        <p class="text-sm text-slate-500 mb-6">Use your staff or student email and password.</p>

        <?php if ($error): ?>
            <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/index.php" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email address</label>
                <input id="email" name="email" type="email" required autofocus
                       value="<?= e($_POST['email'] ?? '') ?>"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                       placeholder="you@ulk.ac.rw">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                       placeholder="••••••••">
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 text-sm transition">
                Sign in
            </button>
        </form>

        <!-- Demo credentials -->
        <div class="mt-6 rounded-lg bg-slate-50 border border-slate-200 p-4 text-xs text-slate-600 leading-6">
            <p class="font-semibold text-slate-700 mb-1">Demo accounts (see seed.sql):</p>
            <ul class="space-y-0.5">
                <li><span class="font-medium">Admin:</span> admin@ulk.ac.rw / Admin@123</li>
                <li><span class="font-medium">Lecturer:</span> a.uwase@ulk.ac.rw / Lect@123</li>
                <li><span class="font-medium">Student:</span> k.ingabire@ulk.ac.rw / Stud@123</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
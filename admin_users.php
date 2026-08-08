<?php
/**
 * ULK ATTENDANCE SYSTEM - Admin: manage users (students & lecturers)
 * List users, add new accounts, reset passwords, delete accounts.
 * All mutations go through api/users.php.
 */
require_once __DIR__ . '/config.php';
requireRole('Admin');

$u = $pdo->query('
    SELECT u.user_id, u.full_name, u.email, u.role, u.created_at,
           st.student_reg_no, st.major, st.intake_year, l.lecturer_id
    FROM users u
    LEFT JOIN students st ON st.user_id = u.user_id
    LEFT JOIN lecturers l  ON l.user_id  = u.user_id
    ORDER BY FIELD(u.role, "Admin", "Lecturer", "Student"), u.full_name')->fetchAll();

$lecturers = $pdo->query('SELECT l.lecturer_id, u.full_name FROM lecturers l JOIN users u ON u.user_id = l.user_id ORDER BY u.full_name')->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/includes/header.php';
?>

<div class="grid gap-6 lg:grid-cols-3">
    <!-- User table -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <p class="text-sm font-medium text-slate-500 mb-4">All accounts</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-slate-400 border-b border-slate-200">
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Email</th>
                        <th class="py-2 pr-4">Role</th>
                        <th class="py-2 pr-4">Student ID</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($u as $row): ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4 font-medium text-slate-800"><?= e($row['full_name']) ?></td>
                            <td class="py-3 pr-4 text-slate-600"><?= e($row['email']) ?></td>
                            <td class="py-3 pr-4">
                                <span class="badge <?= $row['role'] === 'Admin' ? 'badge-present' : ($row['role'] === 'Lecturer' ? 'badge-excused' : 'badge-closed') ?>">
                                    <?= e($row['role']) ?>
                                </span>
                            </td>
                            <td class="py-3 pr-4 font-mono text-xs text-slate-600"><?= e($row['student_reg_no'] ?? $row['lecturer_id'] ?? '—') ?></td>
                            <td class="py-3">
                                <div class="flex gap-2">
                                    <button data-reset-user="<?= (int)$row['user_id'] ?>"
                                            class="rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-1.5 text-xs font-medium">
                                        Reset password
                                    </button>
                                    <button data-delete-user="<?= (int)$row['user_id'] ?>"
                                            data-name="<?= e($row['full_name']) ?>"
                                            class="rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 px-3 py-1.5 text-xs font-medium">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add user form -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 h-fit">
        <p class="text-sm font-medium text-slate-500 mb-4">Add new account</p>
        <form id="userForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                <input name="full_name" required placeholder="e.g. Alphonse Mugisha"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input name="email" type="email" required placeholder="you@ulk.ac.rw"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                    <select name="role" id="roleSelect" required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                        <option value="Student">Student</option>
                        <option value="Lecturer">Lecturer</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input name="password" type="password" required minlength="8"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
            <!-- Student-only fields -->
            <div id="studentFields" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Registration No.</label>
                    <input name="student_reg_no" placeholder="2026-XX-000"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Major</label>
                    <input name="major" placeholder="Software Engineering"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
            <button type="submit"
                    class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 text-sm">
                Create account
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var roleSelect = document.getElementById('roleSelect');
    var studentFields = document.getElementById('studentFields');

    function toggleStudent() {
        studentFields.style.display = roleSelect.value === 'Student' ? '' : 'none';
    }
    roleSelect.addEventListener('change', toggleStudent);
    toggleStudent();

    /* Generic caller for api/users.php */
    function call(body, confirmMsg) {
        if (confirmMsg && !confirm(confirmMsg)) return;
        showLoading(true);
        api('api/users.php', { method: 'POST', body: body })
            .then(function (res) {
                toast(res.message || 'Done.', 'success');
                setTimeout(function () { location.reload(); }, 700);
            })
            .finally(function () { showLoading(false); });
    }

    /* Add account */
    document.getElementById('userForm').addEventListener('submit', function (ev) {
        ev.preventDefault();
        var payload = Object.fromEntries(new FormData(ev.target).entries());
        if (payload.role !== 'Student') { delete payload.student_reg_no; delete payload.major; }
        call({ action: 'create', ...payload });
    });

    /* Reset password */
    document.querySelectorAll('[data-reset-user]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var newPass = prompt('Enter a new password (min. 8 characters):');
            if (!newPass) return;
            if (newPass.length < 8) { toast('Password too short.', 'error'); return; }
            call({ action: 'reset_password', user_id: btn.dataset.resetUser, password: newPass });
        });
    });

    /* Delete account */
    document.querySelectorAll('[data-delete-user]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            call({ action: 'delete', user_id: btn.dataset.deleteUser },
                 'Delete account "' + btn.dataset.name + '"? This cannot be undone.');
        });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
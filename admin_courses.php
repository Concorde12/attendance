<?php
/**
 * ULK ATTENDANCE SYSTEM - Admin: manage courses
 * Create/edit/delete courses and assign the responsible lecturer.
 * Mutations go through api/courses.php.
 */
require_once __DIR__ . '/config.php';
requireRole('Admin');

$courses = $pdo->query('
    SELECT c.course_code, c.title, c.credits, c.department,
           GROUP_CONCAT(u.full_name ORDER BY u.full_name SEPARATOR ", ") AS lecturers,
           (SELECT COUNT(*) FROM enrollments e WHERE e.course_code = c.course_code) AS total_students
    FROM courses c
    LEFT JOIN lecturer_courses lc ON lc.course_code = c.course_code
    LEFT JOIN lecturers l ON l.lecturer_id = lc.lecturer_id
    LEFT JOIN users u ON u.user_id = l.user_id
    GROUP BY c.course_code
    ORDER BY c.course_code')->fetchAll();

$lecturers = $pdo->query('SELECT l.lecturer_id, u.full_name FROM lecturers l JOIN users u ON u.user_id = l.user_id ORDER BY u.full_name')->fetchAll();

$pageTitle = 'Manage Courses';
include __DIR__ . '/includes/header.php';
?>

<div class="grid gap-6 lg:grid-cols-3">
    <!-- Course table -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <p class="text-sm font-medium text-slate-500 mb-4">Course catalogue</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-slate-400 border-b border-slate-200">
                        <th class="py-2 pr-4">Code</th>
                        <th class="py-2 pr-4">Title</th>
                        <th class="py-2 pr-4">Credits</th>
                        <th class="py-2 pr-4">Lecturer(s)</th>
                        <th class="py-2 pr-4">Students</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $c): ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4 font-bold text-indigo-700"><?= e($c['course_code']) ?></td>
                            <td class="py-3 pr-4 text-slate-800"><?= e($c['title']) ?></td>
                            <td class="py-3 pr-4"><?= e($c['credits']) ?></td>
                            <td class="py-3 pr-4 text-slate-600 text-xs"><?= e($c['lecturers'] ?: '—') ?></td>
                            <td class="py-3 pr-4"><?= e($c['total_students']) ?></td>
                            <td class="py-3">
                                <div class="flex gap-2">
                                    <button data-edit-course="<?= e($c['course_code']) ?>"
                                            data-title="<?= e($c['title']) ?>"
                                            data-credits="<?= e($c['credits']) ?>"
                                            data-department="<?= e($c['department'] ?? '') ?>"
                                            class="rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-1.5 text-xs font-medium">
                                        Edit
                                    </button>
                                    <button data-delete-course="<?= e($c['course_code']) ?>"
                                            data-name="<?= e($c['title']) ?>"
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

    <!-- Add / edit form -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 h-fit">
        <p id="formTitle" class="text-sm font-medium text-slate-500 mb-4">Add course</p>
        <form id="courseForm" class="space-y-4">
            <input type="hidden" name="course_code_original">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Course code</label>
                <input name="course_code" required placeholder="CS101"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
                <input name="title" required placeholder="Introduction to Programming"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Credits</label>
                    <input name="credits" type="number" min="1" max="10" value="3" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Department</label>
                    <input name="department" placeholder="Computing"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Assign lecturer</label>
                <select name="lecturer_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                    <option value="">— none —</option>
                    <?php foreach ($lecturers as $l): ?>
                        <option value="<?= (int)$l['lecturer_id'] ?>"><?= e($l['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit"
                    class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 text-sm">
                Save course
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var formTitle = document.getElementById('formTitle');
    var form = document.getElementById('courseForm');

    function call(body) {
        showLoading(true);
        api('api/courses.php', { method: 'POST', body: body })
            .then(function (res) { toast(res.message || 'Done.', 'success'); setTimeout(function(){ location.reload(); }, 700); })
            .finally(function () { showLoading(false); });
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var p = Object.fromEntries(new FormData(form).entries());
        var orig = p.course_code_original;
        if (orig) {
            delete p.course_code_original;
            call({ action: 'update', course_code: orig, ...p });
        } else {
            delete p.course_code_original;
            call({ action: 'create', ...p });
        }
    });

    document.querySelectorAll('[data-edit-course]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            formTitle.textContent = 'Edit course ' + btn.dataset.editCourse;
            form.course_code.value = btn.dataset.editCourse;
            form.course_code.disabled = true;
            form.orig_code_original.value = btn.dataset.editCourse;
            form.title.value = btn.dataset.title;
            form.credits.value = btn.dataset.credits;
            form.department.value = btn.dataset.department;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    document.querySelectorAll('[data-delete-course]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Delete course "' + btn.dataset.name + '" and all its sessions/records?')) return;
            call({ action: 'delete', course_code: btn.dataset.deleteCourse });
        });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
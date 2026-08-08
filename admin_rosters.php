<?php
/**
 * ULK ATTENDANCE SYSTEM - Admin: course rosters
 * Assign students to a course (academic roster). Enrolments drive both
 * the marking screen and the eligibility reports.
 */
require_once __DIR__ . '/config.php';
requireRole('Admin');

$courses = $pdo->query('SELECT course_code, title FROM courses ORDER BY course_code')->fetchAll();
$first = $courses[0]['course_code'] ?? '';

$pageTitle = 'Academic Rosters';
include __DIR__ . '/includes/header.php';
?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm font-medium text-slate-500 mb-1">Select course</p>
            <select id="courseSelect" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                <?php foreach ($courses as $c): ?>
                    <option value="<?= e($c['course_code']) ?>"><?= e($c['course_code']) ?> — <?= e($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <p id="rosterCount" class="text-sm text-slate-500"></p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Enrolled students -->
        <div class="lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-slate-400 border-b border-slate-200">
                            <th class="py-2 pr-4">Registration No.</th>
                            <th class="py-2 pr-4">Student</th>
                            <th class="py-2 pr-4">Major</th>
                            <th class="py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="rosterBody">
                        <tr><td colspan="4" class="py-8 text-center text-slate-400">Select a course…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add student -->
        <aside class="bg-slate-50 rounded-2xl border border-slate-200 p-5 h-fit">
            <p class="text-sm font-medium text-slate-600 mb-3">Add student to roster</p>
            <div class="flex flex-col gap-3">
                <select id="studentSelect" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                    <option value="">— select student —</option>
                </select>
                <button id="addBtn" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-medium">
                    Enrol student
                </button>
            </div>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var courseSelect = document.getElementById('courseSelect');
    var rosterBody = document.getElementById('rosterBody');
    var studentSelect = document.getElementById('studentSelect');
    var rosterCount = document.getElementById('rosterCount');
    var course = courseSelect.value;

    function load() {
        course = courseSelect.value;
        showLoading(true);
        api('api/rosters.php', { method: 'GET', query: { course_code: course } })
            .then(function (res) {
                renderRoster(res.rows || []);
                renderStudents(res.available || []);
            })
            .finally(function () { showLoading(false); });
    }

    function renderRoster(rows) {
        rosterCount.textContent = rows.length + ' enrolled';
        if (!rows.length) {
            rosterBody.innerHTML = '<tr><td colspan="4" class="py-8 text-center text-slate-400">No students enrolled yet.</td></tr>';
            return;
        }
        rosterBody.innerHTML = rows.map(function (r) {
            return '<tr class="border-b border-slate-100 row-in">' +
                '<td class="py-3 pr-4 font-mono text-slate-700">' + esc(r.student_reg_no) + '</td>' +
                '<td class="py-3 pr-4 text-slate-800">' + esc(r.full_name) + '</td>' +
                '<td class="py-3 pr-4 text-slate-600 text-xs">' + esc(r.major || '—') + '</td>' +
                '<td class="py-3">' +
                    '<button data-remove="' + esc(r.student_reg_no) + '" class="rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 px-3 py-1.5 text-xs font-medium">Remove</button>' +
                '</td></tr>';
        }).join('');
        rosterBody.querySelectorAll('[data-remove]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                call('remove', btn.dataset.remove, 'Remove ' + btn.dataset.remove + ' from this course?');
            });
        });
    }

    function renderStudents(rows) {
        studentSelect.innerHTML = '<option value="">— select student —</option>' +
            rows.map(function (r) {
                return '<option value="' + esc(r.student_reg_no) + '">' + esc(r.student_reg_no) + ' · ' + esc(r.full_name) + '</option>';
            }).join('');
    }

    function call(action, regNo, msg) {
        if (msg && !confirm(msg)) return;
        showLoading(true);
        api('api/rosters.php', {
            method: 'POST',
            body: { action: action, course_code: course, student_reg_no: regNo }
        }).then(function (res) {
            toast(res.message || 'Done.', 'success');
            load();
        }).finally(function () { showLoading(false); });
    }

    courseSelect.addEventListener('change', load);
    document.getElementById('addBtn').addEventListener('click', function () {
        var reg = studentSelect.value;
        if (!reg) { toast('Choose a student first.', 'error'); return; }
        call('enroll', reg);
    });

    load();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
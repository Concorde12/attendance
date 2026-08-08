<?php
/**
 * ULK ATTENDANCE SYSTEM - Admin: university reports
 * Overall statistics across all courses, plus a per-course,
 * per-student attendance & eligibility drill-down.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/aggregates.php';
requireRole('Admin');

$totals = [
    'students' => (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
    'lecturers'=> (int) $pdo->query('SELECT COUNT(*) FROM lecturers')->fetchColumn(),
    'courses'  => (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
    'sessions' => (int) $pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn(),
    'records'  => (int) $pdo->query('SELECT COUNT(*) FROM attendance_records')->fetchColumn(),
];

$perCourse = get_course_list_report($pdo);
$courses = $pdo->query('SELECT course_code, title FROM courses ORDER BY course_code')->fetchAll();

$pageTitle = 'University Reports';
include __DIR__ . '/includes/header.php';
?>

<!-- Totals -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <?php $cards = [
        'Students' => $totals['students'],
        'Lecturers' => $totals['lecturers'],
        'Courses' => $totals['courses'],
        'Sessions' => $totals['sessions'],
        'Attendance records' => $totals['records'],
    ]; ?>
    <?php foreach ($cards as $label => $value): ?>
        <div class="rounded-2xl bg-white shadow-sm border border-slate-200 p-4">
            <p class="text-xs text-slate-500"><?= e($label) ?></p>
            <p class="text-2xl font-bold text-slate-800 mt-1"><?= e($value) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <!-- Per-course overview -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-slate-500">Course performance</p>
            <a href="<?= BASE_URL ?>/api/reports.php?action=universal_csv"
               class="text-sm font-medium text-indigo-600 hover:underline">⬇ Download CSV</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-slate-400 border-b border-slate-200">
                        <th class="py-2 pr-4">Course</th>
                        <th class="py-2 pr-4">Lecturer</th>
                        <th class="py-2 pr-4">Students</th>
                        <th class="py-2 pr-4">Sessions</th>
                        <th class="py-2 pr-4">Avg rate</th>
                        <th class="py-2">Drill-down</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($perCourse as $c): ?>
                        <?php $rate = is_numeric($c['avg_rate']) ? (float)$c['avg_rate'] : null; ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4">
                                <p class="font-bold text-indigo-700"><?= e($c['course_code']) ?></p>
                                <p class="text-xs text-slate-500"><?= e($c['title']) ?></p>
                            </td>
                            <td class="py-3 pr-4 text-slate-600 text-xs"><?= e($c['lecturers'] ?: '—') ?></td>
                            <td class="py-3 pr-4"><?= e($c['total_students']) ?></td>
                            <td class="py-3 pr-4"><?= e($c['total_sessions']) ?></td>
                            <td class="py-3 pr-4"><?= $rate !== null ? e($rate) . '%' : '—' ?></td>
                            <td class="py-3">
                                <button data-course="<?= e($c['course_code']) ?>"
                                        class="rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-1.5 text-xs font-medium">
                                    View students
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Per-student drill-down -->
    <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <p class="text-sm font-medium text-slate-500 mb-4">Student drill-down</p>
        <select id="drillCourse" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white mb-4">
            <?php foreach ($courses as $c): ?>
                <option value="<?= e($c['course_code']) ?>"><?= e($c['course_code']) ?> — <?= e($c['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-slate-400 border-b border-slate-200">
                        <th class="py-2 pr-4">Student</th>
                        <th class="py-2 pr-4">Rate</th>
                        <th class="py-2">Eligibility</th>
                    </tr>
                </thead>
                <tbody id="drillBody">
                    <tr><td colspan="3" class="py-8 text-center text-slate-400">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <a id="drillCsv" href="#" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:underline">⬇ Course register CSV</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var course = document.getElementById('drillCourse');
    var body = document.getElementById('drillBody');
    var exportLink = document.getElementById('drillCsv');

    function load() {
        var code = course.value;
        showLoading(true);
        api('api/reports.php', { method: 'GET', query: { action: 'students', course_code: code } })
            .then(function (res) {
                exportLink.href = 'api/reports.php?course_code=' + encodeURIComponent(code) + '&action=register_csv';
                if (!res.rows.length) {
                    body.innerHTML = '<tr><td colspan="3" class="py-8 text-center text-slate-400">No students.</td></tr>';
                    return;
                }
                body.innerHTML = res.rows.map(function (r) {
                    return '<tr class="border-b border-slate-100">' +
                        '<td class="py-3 pr-4"><p class="font-medium text-slate-800">' + esc(r.full_name) + '</p>' +
                        '<p class="text-xs text-slate-400 font-mono">' + esc(r.student_reg_no) + '</p></td>' +
                        '<td class="py-3 pr-4 font-medium">' + r.rate + '%</td>' +
                        '<td class="py-3">' + (r.eligible
                            ? '<span class="badge badge-present">Eligible</span>'
                            : '<span class="badge badge-absent">Below 85%</span>') + '</td></tr>';
                }).join('');
            })
            .finally(function () { showLoading(false); });
    }

    course.addEventListener('change', load);

    /* "View students" buttons in the course table jump to the drill-down. */
    document.querySelectorAll('[data-course]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            course.value = btn.dataset.course;
            load();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    load();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
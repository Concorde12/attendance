<?php
/**
 * ULK ATTENDANCE SYSTEM - Student personal attendance log
 * Lists every recorded mark the student has, newest first, grouped by course.
 */
require_once __DIR__ . '/config.php';
requireRole('Student');

$reg = currentStudentRegNo($pdo);
if (!$reg) {
    $pageTitle = 'My Attendance';
    include __DIR__ . '/includes/header.php';
    echo '<div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">Account is not linked to a student record.</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$courses = $pdo->prepare('
    SELECT c.course_code, c.title
    FROM courses c JOIN enrollments e ON e.course_code = c.course_code
    WHERE e.student_reg_no = ? ORDER BY c.course_code');
$courses->execute([$reg]);
$myCourses = $courses->fetchAll();

$logs = $pdo->prepare('
    SELECT c.course_code, c.title, ss.session_date, ss.start_time, ss.end_time,
           ar.status, ar.timestamp
    FROM attendance_records ar
    JOIN sessions ss ON ss.session_id = ar.session_id
    JOIN courses c   ON c.course_code = ss.course_code
    WHERE ar.student_reg_no = ?
    ORDER BY ss.session_date DESC, ss.start_time DESC');
$logs->execute([$reg]);
$records = $logs->fetchAll();

$pageTitle = 'My Attendance';
include __DIR__ . '/includes/header.php';
?>

<?php if (!$records): ?>
    <div class="rounded-lg bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
        No attendance records yet.
    </div>
<?php else: ?>
    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Recent marks table -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p class="text-sm font-medium text-slate-500 mb-4">Recent session records</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-slate-400 border-b border-slate-200">
                            <th class="py-2 pr-4">Date</th>
                            <th class="py-2 pr-4">Course</th>
                            <th class="py-2 pr-4">Time</th>
                            <th class="py-2 pr-4">Marked at</th>
                            <th class="py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 font-medium text-slate-800"><?= e(date('M j, Y', strtotime($r['session_date']))) ?></td>
                                <td class="py-3 pr-4">
                                    <span class="font-medium text-slate-800"><?= e($r['course_code']) ?></span>
                                    <span class="text-xs text-slate-500"><?= e($r['title']) ?></span>
                                </td>
                                <td class="py-3 pr-4 text-slate-600"><?= e($r['start_time']) ?> – <?= e($r['end_time']) ?></td>
                                <td class="py-3 pr-4 text-slate-400 text-xs"><?= e($r['timestamp']) ?></td>
                                <td class="py-3"><?= status_badge($r['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Legend / filter -->
        <aside class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 h-fit">
            <p class="text-sm font-medium text-slate-500 mb-4">Courses</p>
            <ul class="space-y-1 text-sm">
                <?php foreach ($myCourses as $c): ?>
                    <li class="flex items-center justify-between rounded-lg px-3 py-2 bg-slate-50 border border-slate-100">
                        <span class="font-medium text-slate-700"><?= e($c['course_code']) ?></span>
                        <span class="text-xs text-slate-500"><?= e($c['title']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="mt-6 text-xs text-slate-400 leading-5">
                <span class="badge badge-present">Present</span>
                <span class="badge badge-late">Late</span>
                <span class="badge badge-absent">Absent</span>
                <span class="badge badge-excused">Excused</span><br>
                Late arrivals still count towards your attendance rate.
            </p>
        </aside>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
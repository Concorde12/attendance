<?php
/**
 * ULK ATTENDANCE SYSTEM - Student profile + course summary
 * Shows personal details, per-course attendance rates and the
 * 85% exam-eligibility alert.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/aggregates.php';
requireRole('Student');

$u = currentUser();
$regNo = currentStudentRegNo($pdo);

if (!$regNo) {
    // Account exists but is not linked to a student record.
    $pageTitle = 'My Profile';
    include __DIR__ . '/includes/header.php';
    echo '<div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">'.
         'Your account is not linked to a student record. Please contact the administrator.</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$stmt  = $pdo->prepare('SELECT s.*, u.full_name, u.email FROM students s JOIN users u ON u.user_id = s.user_id WHERE s.student_reg_no = ?');
$stmt->execute([$regNo]);
$student = $stmt->fetch();

$stats    = get_student_course_stats($pdo, $regNo);
$at_risk  = false;
foreach ($stats as $s) {
    if (!is_eligible($s)) $at_risk = true;
}

$pageTitle = 'My Dashboard';
include __DIR__ . '/includes/header.php';
?>

<?php if ($at_risk): ?>
    <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">
        <strong>Attention:</strong> you are below the 85% attendance threshold in one or more courses and may be
        disqualified from the final examination. See the course summary below.
    </div>
<?php endif; ?>

<div class="grid gap-6 md:grid-cols-3">
    <!-- Profile card -->
    <div class="md:col-span-1 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <p class="text-sm font-medium text-slate-500 mb-4">Student profile</p>
        <div class="flex items-center gap-4 mb-6">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-white text-xl font-bold uppercase">
                <?= e(substr($student['full_name'], 0, 1)) ?>
            </span>
            <div>
                <p class="font-semibold text-slate-800"><?= e($student['full_name']) ?></p>
                <p class="text-sm text-slate-500"><?= e($student['student_reg_no']) ?></p>
            </div>
        </div>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="text-slate-800"><?= e($student['email']) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Major</dt><dd class="text-slate-800"><?= e($student['major'] ?? '—') ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Intake year</dt><dd class="text-slate-800"><?= e($student['intake_year'] ?? '—') ?></dd></div>
        </dl>
    </div>

    <!-- Course summary -->
    <div class="md:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <p class="text-sm font-medium text-slate-500 mb-4">Attendance summary per course</p>
        <?php if (!$stats): ?>
            <p class="text-sm text-slate-500">You are not enrolled in any course yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-slate-400 border-b border-slate-200">
                            <th class="py-2 pr-4">Course</th>
                            <th class="py-2 pr-4">Sessions</th>
                            <th class="py-2 pr-4">Present</th>
                            <th class="py-2 pr-4">Late</th>
                            <th class="py-2 pr-4">Absent</th>
                            <th class="py-2 pr-4">Excused</th>
                            <th class="py-2 pr-4">Rate</th>
                            <th class="py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats as $row): ?>
                            <?php
                                $rate = attendance_rate($row);
                                $eligible = is_eligible($row);
                            ?>
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4">
                                    <p class="font-medium text-slate-800"><?= e($row['course_code']) ?></p>
                                    <p class="text-xs text-slate-500"><?= e($row['title']) ?></p>
                                </td>
                                <td class="py-3 pr-4"><?= e($row['total_sessions']) ?></td>
                                <td class="py-3 pr-4 text-emerald-600 font-medium"><?= e($row['present']) ?></td>
                                <td class="py-3 pr-4 text-amber-600 font-medium"><?= e($row['late']) ?></td>
                                <td class="py-3 pr-4 text-rose-600 font-medium"><?= e($row['absent']) ?></td>
                                <td class="py-3 pr-4 text-sky-600 font-medium"><?= e($row['excused']) ?></td>
                                <td class="py-3 pr-4 font-medium text-slate-800"><?= (float)$row['total_sessions'] ? $rate . '%' : '—' ?></td>
                                <td class="py-3">
                                    <span class="badge <?= $eligible ? 'badge-present' : 'badge-absent' ?>">
                                        <?= $eligible ? 'Eligible' : 'Below 85%' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <p class="mt-4 text-xs text-slate-400">Rate = (Present + Late) ÷ recorded sessions. Exams require at least 85% per course.
        View your detailed log: <a class="text-indigo-600 hover:underline" href="<?= BASE_URL ?>/student_attendance.php">My Attendance</a>.</p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
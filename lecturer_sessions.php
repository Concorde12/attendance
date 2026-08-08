<?php
/**
 * ULK ATTENDANCE SYSTEM - Lecturer: sessions of one course
 * Lists class sessions, lets the lecturer open a new session, close
 * ongoing sessions and jump to the marking screen.
 */
require_once __DIR__ . '/config.php';
requireRole('Lecturer');

$lid    = currentLecturerId($pdo);
$course = $_GET['course_code'] ?? '';

// Verify the lecturer is really assigned to this course.
$ok = $pdo->prepare('SELECT 1 FROM lecturer_courses WHERE lecturer_id = ? AND course_code = ?');
$ok->execute([$lid, $course]);
if (!$ok->fetch()) {
    $pageTitle = 'Sessions';
    include __DIR__ . '/includes/header.php';
    echo '<div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">Course not found or not assigned to you.</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$courseRow = $pdo->prepare('SELECT * FROM courses WHERE course_code = ?');
$courseRow->execute([$course]);
$courseRow = $courseRow->fetch();

$s = $pdo->prepare('
    SELECT s.*, COUNT(ar.record_id) AS marked
    FROM sessions s
    LEFT JOIN attendance_records ar ON ar.session_id = s.session_id
    WHERE s.course_code = ?
    GROUP BY s.session_id
    ORDER BY s.session_date DESC, s.start_time DESC');
$s->execute([$course]);
$sessions = $s->fetchAll();

$pageTitle = 'Sessions — ' . $course;
include __DIR__ . '/includes/header.php';
?>

<div class="grid gap-6 lg:grid-cols-3">
    <!-- Session list -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-slate-500">
                Sessions for <span class="text-slate-800 font-semibold"><?= e($courseRow['course_code']) ?></span> · <?= e($courseRow['title']) ?>
            </p>
            <a href="<?= BASE_URL ?>/api/reports.php?course_code=<?= e($course) ?>&action=register_csv"
               class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:underline">
                ⬇ Export register (CSV)
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-slate-400 border-b border-slate-200">
                        <th class="py-2 pr-4">Date</th>
                        <th class="py-2 pr-4">Time</th>
                        <th class="py-2 pr-4">Marks</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $sess): ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4 font-medium text-slate-800"><?= e($sess['session_date']) ?></td>
                            <td class="py-3 pr-4 text-slate-600"><?= e($sess['start_time']) ?> – <?= e($sess['end_time']) ?></td>
                            <td class="py-3 pr-4 text-slate-600"><?= e($sess['marked']) ?></td>
                            <td class="py-3 pr-4"><?= status_badge($sess['status']) ?></td>
                            <td class="py-3">
                                <div class="flex gap-2">
                                    <a href="<?= BASE_URL ?>/lecturer_attendance.php?session_id=<?= (int)$sess['session_id'] ?>"
                                       class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 text-xs font-medium">
                                        Mark / view
                                    </a>
                                    <?php if ($sess['status'] === 'Open'): ?>
                                        <button data-close-session="<?= (int)$sess['session_id'] ?>"
                                                class="rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 px-3 py-1.5 text-xs font-medium">
                                            Close
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$sessions): ?>
                        <tr><td colspan="5" class="py-6 text-center text-slate-400">No sessions available yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create session -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 h-fit">
        <p class="text-sm font-medium text-slate-500 mb-4">Open a new session</p>
        <form id="sessionForm" class="space-y-4">
            <input type="hidden" name="course_code" value="<?= e($course) ?>">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Date</label>
                <input type="date" name="session_date" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Start</label>
                    <input type="time" name="start_time" value="08:00" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">End</label>
                    <input type="time" name="end_time" value="10:00" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
            <button type="submit"
                    class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 text-sm">
                Create session
            </button>
        </form>
        <p class="mt-4 text-xs text-slate-400 leading-relaxed">
            New sessions open with <strong>Open</strong> status and appear immediately on
            <a class="text-indigo-600 hover:underline" href="<?= BASE_URL ?>/lecturer_attendance.php">Mark Attendance</a>.
        </p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
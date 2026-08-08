<?php
/**
 * ULK ATTENDANCE SYSTEM - Lecturer: my courses
 * Lists the courses assigned to the logged-in lecturer with quick stats.
 */
require_once __DIR__ . '/config.php';
requireRole('Lecturer');

$lid = currentLecturerId($pdo);

$stmt = $pdo->prepare('
    SELECT c.course_code, c.title, c.credits, c.department,
           (SELECT COUNT(*) FROM enrollments e WHERE e.course_code = c.course_code)  AS total_students,
           (SELECT COUNT(*) FROM sessions s   WHERE s.course_code = c.course_code)  AS total_sessions,
           (SELECT COUNT(*) FROM sessions s   WHERE s.course_code = c.course_code
                                                 AND s.status = "Open")              AS open_sessions
    FROM courses c
    JOIN lecturer_courses lc ON lc.course_code = c.course_code AND lc.lecturer_id = ?
    ORDER BY c.course_code');
$stmt->execute([$lid]);
$courses = $stmt->fetchAll();

$pageTitle = 'My Courses';
include __DIR__ . '/includes/header.php';
?>

<?php if (!$courses): ?>
    <div class="rounded-lg bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
        No courses have been assigned to you yet. Please contact the administrator.
    </div>
<?php else: ?>
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($courses as $c): ?>
            <a href="<?= BASE_URL ?>/lecturer_sessions.php?course_code=<?= e($c['course_code']) ?>"
               class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col hover:border-indigo-300 hover:shadow-md transition">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-sm font-bold text-indigo-700"><?= e($c['course_code']) ?></p>
                        <h3 class="font-semibold text-slate-800"><?= e($c['title']) ?></h3>
                    </div>
                    <span class="badge <?= $c['open_sessions'] ? 'badge-open' : 'badge-closed' ?>">
                        <?= $c['open_sessions'] ?> open
                    </span>
                </div>
                <p class="text-xs text-slate-500 mb-4"><?= e($c['department']) ?> · <?= (int)$c['credits'] ?> credits</p>

                <dl class="grid grid-cols-3 gap-3 text-center mb-5">
                    <div class="rounded-xl bg-slate-50 py-3">
                        <dt class="text-xs text-slate-500">Students</dt>
                        <dd class="text-lg font-bold text-slate-800"><?= e($c['total_students']) ?></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 py-3">
                        <dt class="text-xs text-slate-500">Sessions</dt>
                        <dd class="text-lg font-bold text-slate-800"><?= e($c['total_sessions']) ?></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 py-3">
                        <dt class="text-xs text-slate-500">Credits</dt>
                        <dd class="text-lg font-bold text-slate-800"><?= e($c['credits']) ?></dd>
                    </div>
                </dl>

                <span class="mt-auto inline-flex items-center gap-2 text-sm font-medium text-indigo-600">
                    Manage sessions & attendance
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
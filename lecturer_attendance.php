<?php
/**
 * ULK ATTENDANCE SYSTEM - Lecturer: mark / edit attendance for one session.
 * The student roster and their current marks are loaded via
 * api/attendance.php and rendered client-side with app.js.
 */
require_once __DIR__ . '/config.php';
requireRole('Lecturer');

$lid       = currentLecturerId($pdo);
$sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;

$session = null;
$course  = null;

if ($sessionId) {
    $stmt = $pdo->prepare('
        SELECT s.*, c.title AS course_title
        FROM sessions s JOIN courses c ON c.course_code = s.course_code
        WHERE s.session_id = ?');
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    if (!$session || (int) $session['lecturer_id'] !== $lid) {
        $session = null;
        $sessionId = 0;
    } else {
        $course = $session['course_code'];
    }
}

$pageTitle = 'Mark Attendance';
include __DIR__ . '/includes/header.php';
?>

<?php if (!$session): ?>
    <!-- Session picker -->
    <?php
        $list = $pdo->prepare('
            SELECT s.session_id, s.course_code, c.title, s.session_date, s.start_time, s.end_time, s.status,
                   COUNT(ar.record_id) AS marked
            FROM sessions s
            JOIN lecturer_courses lc ON lc.course_code = s.course_code AND lc.lecturer_id = ?
            JOIN courses c ON c.course_code = s.course_code
            LEFT JOIN attendance_records ar ON ar.session_id = s.session_id
            GROUP BY s.session_id
            ORDER BY s.status = "Open" DESC, s.session_date DESC, s.start_time DESC');
        $list->execute([$lid]);
        $sessions = $list->fetchAll();
    ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <p class="text-sm font-medium text-slate-500 mb-4">Choose a session to mark</p>
        <?php if (!$sessions): ?>
            <p class="text-sm text-slate-500">You have no sessions yet. Open one from <a class="text-indigo-600 hover:underline" href="<?= BASE_URL ?>/lecturer_courses.php">My Courses</a>.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-slate-400 border-b border-slate-200">
                            <th class="py-2 pr-4">Course</th>
                            <th class="py-2 pr-4">Date</th>
                            <th class="py-2 pr-4">Marks</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $s): ?>
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 font-medium text-slate-800"><?= e($s['course_code']) ?>
                                    <span class="text-xs text-slate-400"> · <?= e($s['title']) ?></span></td>
                                <td class="py-3 pr-4"><?= e($s['session_date']) ?></td>
                                <td class="py-3 pr-4"><?= e($s['marked']) ?> / students</td>
                                <td class="py-3 pr-4"><?= status_badge($s['status']) ?></td>
                                <td class="py-3">
                                    <a href="<?= BASE_URL ?>/lecturer_attendance.php?session_id=<?= (int)$s['session_id'] ?>"
                                       class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 text-xs font-medium">
                                        Open
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Marking screen -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="font-semibold text-slate-800"><?= e($session['course_code']) ?> — <?= e($session['course_title']) ?></h2>
                <p class="text-sm text-slate-500">
                    Session <?= e($session['session_date']) ?> · <?= e($session['start_time']) ?> – <?= e($session['end_time']) ?>
                    &nbsp;·&nbsp; Status: <?= status_badge($session['status']) ?>
                </p>
            </div>
            <div class="flex gap-2">
                <button id="saveAllBtn"
                        class="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm font-medium">
                    Save all changes
                </button>
                <?php if ($session['status'] === 'Open'): ?>
                    <button data-close-session="<?= (int)$session['session_id'] ?>"
                            class="rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 px-4 py-2 text-sm font-medium">
                        Close session
                    </button>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/lecturer_sessions.php?course_code=<?= e($session['course_code']) ?>"
                   class="rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 text-sm font-medium">Back</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-slate-400 border-b border-slate-200">
                        <th class="py-2 pr-4">#</th>
                        <th class="py-2 pr-4">Registration No.</th>
                        <th class="py-2 pr-4">Student</th>
                        <th class="py-2 pr-4">Current status</th>
                        <th class="py-2">Mark as</th>
                    </tr>
                </thead>
                <tbody id="rosterBody">
                    <tr><td colspan="5" class="py-8 text-center text-slate-400">Loading roster…</td></tr>
                </tbody>
            </table>
        </div>
        <p class="mt-4 text-xs text-slate-400">Present / Late count towards the student's attendance rate. Pending changes are highlighted until saved.</p>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var sessionId = <?= json_encode($sessionId) ?>;

    /* Close-session buttons appear on both screens. */
    document.querySelectorAll('[data-close-session]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Close this session? Attendance will be finalised.')) return;
            showLoading(true);
            api('api/sessions.php', { method: 'POST', body: { action: 'close', session_id: btn.dataset.closeSession } })
                .then(function (res) {
                    toast('Session closed.', 'success');
                    setTimeout(function () { location.reload(); }, 600);
                }).finally(function () { showLoading(false); });
        });
    });

    if (!sessionId) return;

    var tbody = document.getElementById('rosterBody');
    var dirty = {};

    function render(rows) {
        tbody.innerHTML = rows.map(function (r, i) {
            return '<tr data-reg="' + esc(r.student_reg_no) + '" ' + (r.status ? '' : 'class="bg-amber-50"') + '>' +
                '<td class="py-3 pr-4 text-slate-500">' + (i + 1) + '</td>' +
                '<td class="py-3 pr-4 font-mono text-slate-700">' + esc(r.student_reg_no) + '</td>' +
                '<td class="py-3 pr-4 text-slate-800">' + esc(r.full_name) + '</td>' +
                '<td class="py-3 pr-4" data-current>' + (r.status ? buildBadge(r.status) : '<span class="badge badge-absent">No mark</span>') + '</td>' +
                '<td class="py-3">' +
                    '<select class="status-select rounded-lg border border-slate-300 px-2 py-1.5 text-sm">' +
                        ['Present','Absent','Late','Excused'].map(function (s) {
                            return '<option value="' + s + '"' + (r.status === s ? ' selected' : '') + '>' + s + '</option>';
                        }).join('') +
                    '</select>' +
                '</td>' +
            '</tr>';
        }).join('');

        tbody.querySelectorAll('.status-select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                var reg = sel.closest('tr').dataset.reg;
                dirty[reg] = sel.value;
                sel.closest('tr').classList.add('bg-amber-50');
            });
        });
    }

    api('api/attendance.php', { method: 'GET', query: { action: 'roster', session_id: sessionId } })
        .then(function (res) { render(res.rows || []); })
        .catch(function () { tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-rose-400">Failed to load roster.</td></tr>'; });

    document.getElementById('saveAllBtn').addEventListener('click', function () {
        var records = [];
        tbody.querySelectorAll('.status-select').forEach(function (sel) {
            records.push({ student_reg_no: sel.closest('tr').dataset.reg, status: sel.value });
        });
        showLoading(true);
        api('api/attendance.php', {
            method: 'POST',
            body: {
                action: 'save',
                session_id: sessionId,
                records: records
            }
        }).then(function (res) {
            toast('Attendance saved for ' + ((res.saved)||0) + ' students.', 'success');
            location.reload();
        }).finally(function () { showLoading(false); });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
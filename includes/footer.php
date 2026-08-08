<?php
/**
 * ULK Attendance System - shared page footer
 * Closes <main> opened in includes/header.php and loads app.js.
 */
?>
</main>

<footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4 text-center text-xs text-slate-400">
        &copy; <?= date('Y') ?> Kigali Independent University (ULK) &mdash; Attendance Management System
    </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
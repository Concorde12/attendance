<?php
/**
 * ULK Attendance System - shared page header / top navigation
 * Expects $pageTitle (string) to be defined before include.
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$cu = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> | ULK Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100">

<nav class="bg-white border-b border-slate-200 sticky top-0 z-30">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">

            <!-- Brand + mobile toggle -->
            <div class="flex items-center gap-3">
                <button id="navToggle" class="lg:hidden rounded-md p-2 text-slate-500 hover:bg-slate-100"
                        aria-label="Toggle navigation">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <a href="<?= BASE_URL ?>/dashboard.php" class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white text-sm font-bold">ULK</span>
                    <span class="hidden sm:block font-semibold text-slate-800">Attendance System</span>
                </a>
            </div>

            <!-- Desktop links -->
            <div class="hidden lg:flex items-center gap-1 text-sm">
                <?php if (isRole('Student')): ?>
                    <a href="<?= BASE_URL ?>/student_profile.php"   class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Profile</a>
                    <a href="<?= BASE_URL ?>/student_attendance.php" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">My Attendance</a>
                <?php elseif (isRole('Lecturer')): ?>
                    <a href="<?= BASE_URL ?>/lecturer_courses.php"   class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">My Courses</a>
                    <a href="<?= BASE_URL ?>/lecturer_attendance.php" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Mark Attendance</a>
                <?php elseif (isRole('Admin')): ?>
                    <a href="<?= BASE_URL ?>/admin_reports.php"   class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Reports</a>
                    <a href="<?= BASE_URL ?>/admin_users.php"     class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Users</a>
                    <a href="<?= BASE_URL ?>/admin_courses.php"   class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Courses</a>
                    <a href="<?= BASE_URL ?>/admin_rosters.php"   class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Rosters</a>
                <?php endif; ?>
            </div>

            <!-- User menu -->
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-medium text-slate-800 leading-4"><?= e($cu['name'] ?? '') ?></p>
                    <p class="text-xs text-slate-500 capitalize"><?= e($cu['role'] ?? '') ?></p>
                </div>
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-slate-600 text-sm font-semibold uppercase">
                    <?= e(substr($cu['name'] ?? '?', 0, 1)) ?>
                </span>
                <a href="<?= BASE_URL ?>/logout.php"
                   class="rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 px-3 py-2 text-sm font-medium">Log out</a>
            </div>
        </div>
    </div>

    <!-- Mobile links -->
    <div id="mobileNav" class="lg:hidden hidden border-t border-slate-200 bg-white px-4 py-2 space-y-1 text-sm">
        <?php if (isRole('Student')): ?>
            <a href="<?= BASE_URL ?>/student_profile.php"     class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Profile</a>
            <a href="<?= BASE_URL ?>/student_attendance.php"  class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">My Attendance</a>
        <?php elseif (isRole('Lecturer')): ?>
            <a href="<?= BASE_URL ?>/lecturer_courses.php"    class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">My Courses</a>
            <a href="<?= BASE_URL ?>/lecturer_attendance.php" class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Mark Attendance</a>
        <?php elseif (isRole('Admin')): ?>
            <a href="<?= BASE_URL ?>/admin_reports.php"       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Reports</a>
            <a href="<?= BASE_URL ?>/admin_users.php"         class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Users</a>
            <a href="<?= BASE_URL ?>/admin_courses.php"       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Courses</a>
            <a href="<?= BASE_URL ?>/admin_rosters.php"       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Rosters</a>
        <?php endif; ?>
    </div>
</nav>

<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-slate-800"><?= e($pageTitle) ?></h1>
    </div>

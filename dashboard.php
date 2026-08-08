<?php
/**
 * ULK Attendance System - Dashboard
 * Authenticated landing page: shows a short role-aware overview and
 * quickly routes visitors to the correct role home.
 */
require_once __DIR__ . '/config.php';
requireLogin();

$u = currentUser();

// Route to the role home immediately when nothing else is targeted.
switch ($u['role']) {
    case 'Student':
        header('Location: ' . BASE_URL . '/student_profile.php');
        exit;
    case 'Lecturer':
        header('Location: ' . BASE_URL . '/lecturer_courses.php');
        exit;
    default:
        header('Location: ' . BASE_URL . '/admin_reports.php');
        exit;
}
<?php
/**
 * ULK Attendance System - shared reporting / aggregation queries
 * Central place for the SQL that computes attendance statistics.
 */

/**
 * Per-course statistics for a single student (used on student pages).
 * Returns rows keyed by course_code with counts of every status.
 */
function get_student_course_stats(PDO $pdo, string $studentRegNo): array
{
    $sql = "
        SELECT c.course_code, c.title, c.credits, c.department,
               COUNT(ar.record_id)                                                AS total_sessions,
               COALESCE(SUM(CASE WHEN ar.status IN ('Present','Late')  THEN 1 ELSE 0 END),0) AS attended,
               COALESCE(SUM(CASE WHEN ar.status = 'Present' THEN 1 ELSE 0 END),0)  AS present,
               COALESCE(SUM(CASE WHEN ar.status = 'Late'    THEN 1 ELSE 0 END),0)  AS late,
               COALESCE(SUM(CASE WHEN ar.status = 'Absent'  THEN 1 ELSE 0 END),0)  AS absent,
               COALESCE(SUM(CASE WHEN ar.status = 'Excused' THEN 1 ELSE 0 END),0)  AS excused
        FROM courses c
        JOIN enrollments e ON e.course_code = c.course_code AND e.student_reg_no = ?
        LEFT JOIN sessions s ON s.course_code = c.course_code
        LEFT JOIN attendance_records ar
               ON ar.session_id = s.session_id AND ar.student_reg_no = e.student_reg_no
        GROUP BY c.course_code
        ORDER BY c.course_code";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$studentRegNo]);
    return $stmt->fetchAll();
}

/**
 * Roll-up statistics per course across the whole university
 * (used on the admin reports page and the lecturer course list).
 */
function get_course_list_report(PDO $pdo): array
{
    $sql = "
        SELECT c.course_code, c.title, c.credits, c.department,
               GROUP_CONCAT(DISTINCT u.full_name ORDER BY u.full_name SEPARATOR ', ')  AS lecturers,
               (SELECT COUNT(*) FROM enrollments e  WHERE e.course_code   = c.course_code)                                               AS total_students,
               (SELECT COUNT(*) FROM sessions s    WHERE s.course_code   = c.course_code)                                               AS total_sessions,
               (SELECT COUNT(*) FROM attendance_records ar JOIN sessions s2 ON s2.session_id = ar.session_id
                 WHERE s2.course_code = c.course_code)                                                                                 AS total_records,
               ROUND(
                 (SELECT COUNT(*) FROM attendance_records a2 JOIN sessions s3 ON s3.session_id = a2.session_id
                   WHERE s3.course_code = c.course_code AND a2.status IN ('Present','Late')) /
                 NULLIF((SELECT COUNT(*) FROM attendance_records a3 JOIN sessions s4 ON s4.session_id = a3.session_id
                   WHERE s4.course_code = c.course_code), 0) * 100, 1)                                                                  AS avg_rate
        FROM courses c
        LEFT JOIN lecturer_courses lc ON lc.course_code = c.course_code
        LEFT JOIN lecturers l ON l.lecturer_id = lc.lecturer_id
        LEFT JOIN users u ON u.user_id = l.user_id
        GROUP BY c.course_code
        ORDER BY c.course_code";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Per-student attendance report for one course
 * (used by admin reports and the lecturer export).
 * @return array rows with attendance_rate + eligible fields added
 */
function get_student_report_per_course(PDO $pdo, string $courseCode): array
{
    $sql = "
        SELECT e.student_reg_no, u.full_name,
               COUNT(ar.record_id)                                                          AS total_sessions,
               COALESCE(SUM(CASE WHEN ar.status IN ('Present','Late')  THEN 1 ELSE 0 END),0) AS attended,
               COALESCE(SUM(CASE WHEN ar.status = 'Present' THEN 1 ELSE 0 END),0)            AS present,
               COALESCE(SUM(CASE WHEN ar.status = 'Late'    THEN 1 ELSE 0 END),0)            AS late,
               COALESCE(SUM(CASE WHEN ar.status = 'Absent'  THEN 1 ELSE 0 END),0)            AS absent,
               COALESCE(SUM(CASE WHEN ar.status = 'Excused' THEN 1 ELSE 0 END),0)            AS excused
        FROM enrollments e
        JOIN students s ON s.student_reg_no = e.student_reg_no
        JOIN users u ON u.user_id = s.user_id
        LEFT JOIN sessions ss ON ss.course_code = e.course_code
        LEFT JOIN attendance_records ar
               ON ar.session_id = ss.session_id AND ar.student_reg_no = e.student_reg_no
        WHERE e.course_code = ?
        GROUP BY s.student_reg_no, u.full_name
        ORDER BY u.full_name, s.student_reg_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$courseCode]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $i => $r) {
        $rows[$i]['rate']     = attendance_rate($r);
        $rows[$i]['eligible'] = is_eligible($r);
    }
    return $rows;
}

/**
 * Raw attendance register: every recorded mark for a course.
 * Used by api/reports.php for course-level CSV downloads.
 */
function get_course_register(PDO $pdo, string $courseCode): array
{
    $sql = "
        SELECT c.course_code, c.title,
               ss.session_id, ss.session_date, ss.start_time, ss.end_time, ss.status AS session_status,
               ar.record_id, ar.student_reg_no, ar.status, ar.timestamp
        FROM attendance_records ar
        JOIN sessions ss ON ss.session_id = ar.session_id
        JOIN courses c ON c.course_code = ss.course_code
        WHERE ss.course_code = ?
        ORDER BY ss.session_date, ar.student_reg_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$courseCode]);
    return $stmt->fetchAll();
}
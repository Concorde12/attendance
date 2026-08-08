-- =====================================================================
--  ULK ATTENDANCE SYSTEM - Database Schema
--  Kigali Independent University (ULK)
--  Engine: MySQL / MariaDB  |  Charset: utf8mb4
--
--  Run this file first:
--      mysql -u root -p < schema.sql
--  then load the sample data:
--      mysql -u root -p < seed.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS ulk_attendance_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ulk_attendance_db;

-- To make the script safely re-runnable we drop dependent tables first.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS attendance_records;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS lecturer_courses;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS lecturers;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. users - single account table for the three roles (RBAC)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  user_id       INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,               -- bcrypt via PHP password_hash()
  role          ENUM('Student','Lecturer','Admin') NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. students - extends a user account with a registration number
-- ---------------------------------------------------------------------
CREATE TABLE students (
  student_reg_no VARCHAR(20) PRIMARY KEY,
  user_id        INT NOT NULL UNIQUE,
  major          VARCHAR(100) DEFAULT NULL,
  intake_year    YEAR DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. lecturers - extends a user account with a staff id
-- ---------------------------------------------------------------------
CREATE TABLE lecturers (
  lecturer_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL UNIQUE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. courses - course catalogue
-- ---------------------------------------------------------------------
CREATE TABLE courses (
  course_code VARCHAR(15) PRIMARY KEY,               -- e.g. 'CS101'
  title       VARCHAR(150) NOT NULL,
  credits     INT NOT NULL DEFAULT 3,
  department  VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. lecturer_courses - which lecturer is assigned to which course
-- ---------------------------------------------------------------------
CREATE TABLE lecturer_courses (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  lecturer_id INT NOT NULL,
  course_code VARCHAR(20) NOT NULL,
  UNIQUE KEY uq_lecturer_course (lecturer_id, course_code),
  FOREIGN KEY (lecturer_id) REFERENCES lecturers(lecturer_id) ON DELETE CASCADE,
  FOREIGN KEY (course_code) REFERENCES courses(course_code)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. enrollments - academic roster: students enrolled on a course
-- ---------------------------------------------------------------------
CREATE TABLE enrollments (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  student_reg_no VARCHAR(20) NOT NULL,
  course_code    VARCHAR(20) NOT NULL,
  UNIQUE KEY uq_enrollment (student_reg_no, course_code),
  FOREIGN KEY (student_reg_no) REFERENCES students(student_reg_no) ON DELETE CASCADE,
  FOREIGN KEY (course_code)    REFERENCES courses(course_code)     ON DELETE CASCADE,
  INDEX idx_enrollments_course (course_code)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. sessions - a class session opened by a lecturer for a course
-- ---------------------------------------------------------------------
CREATE TABLE sessions (
  session_id   INT AUTO_INCREMENT PRIMARY KEY,
  course_code  VARCHAR(20) NOT NULL,
  lecturer_id  INT NOT NULL,
  session_date DATE NOT NULL,
  start_time   TIME NOT NULL,
  end_time     TIME NOT NULL,
  status   ENUM('Open','Closed') NOT NULL DEFAULT 'Open',
  FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE,
  FOREIGN KEY (lecturer_id) REFERENCES lecturers(lecturer_id),
  INDEX idx_sessions_course_date (course_code, session_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. attendance_records - one row per student per session
-- ---------------------------------------------------------------------
CREATE TABLE attendance_records (
  record_id      INT AUTO_INCREMENT PRIMARY KEY,
  session_id     INT NOT NULL,
  student_reg_no VARCHAR(20) NOT NULL,
  status         ENUM('Present','Absent','Late','Excused') NOT NULL DEFAULT 'Absent',
  timestamp      DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_session_student (session_id, student_reg_no),  -- one mark per student/session
  FOREIGN KEY (session_id)     REFERENCES sessions(session_id)          ON DELETE CASCADE,
  FOREIGN KEY (student_reg_no) REFERENCES students(student_reg_no)      ON DELETE CASCADE,
  INDEX idx_attendance_student (student_reg_no, status),
  INDEX idx_attendance_status (status)
) ENGINE=InnoDB;
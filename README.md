# ULK Attendance Management System

A role-based **Web Application** built for **Kigali Independent University (ULK)** using **PHP (PDO)**, **MySQL/MariaDB**, **HTML5**, **Tailwind CSS**, and **JavaScript**.

---

## Features & User Roles

| Role | Core Capabilities |
| :--- | :--- |
| **Student** | Log in, view profile, track per-course attendance logs, receive automatic 85% exam-eligibility warnings. |
| **Lecturer** | Select assigned courses, manage class sessions, mark/edit attendance (Present, Absent, Late, Excused), export course reports to CSV. |
| **Admin** | Manage users (students & lecturers), manage course offerings, assign lecturers, assemble rosters, generate university-wide reports and CSV exports. |

---

## System Requirements

* **Local Web Server:** XAMPP, WAMP, or Laragon
* **PHP Version:** PHP ≥ 8.0 (with PDO extension enabled)
* **Database Engine:** MySQL ≥ 5.7 or MariaDB ≥ 10.3

---

## Local Setup & Installation

### 1. Project Deployment
Clone or move the project directory into your web root directory:
```text
C:\xampp\htdocs\attendance
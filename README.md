# ULK Attendance Management System

A complete role-based **Web Application** built for the **Kigali Independent
University (ULK)** with **PHP (PDO)**, **MySQL/MariaDB**, **HTML5 + Tailwind
CSS + vanilla JavaScript**.

It covers three roles with full RBAC:

| Role     | Capabilities                                                                                   |
|----------|------------------------------------------------------------------------------------------------|
| Student  | Log in, view profile, per-course attendance logs, 85% exam-eligibility alert                    |
| Lecturer | Log in, select assigned courses, open/close class sessions, mark attendance (Present / Absent / Late / Excused), edit logs, export course summaries (CSV) |
| Admin    | Manage users (students & lecturers), manage courses, assign courses to lecturers, build academic rosters, generate university-wide reports & CSV exports |

---

## 1. Requirements

- **XAMPP** (or WAMP / Laragon) with **PHP ≥ 8.0** and **MySQL ≥ 5.7 / MariaDB ≥ 10.3**
- Password hashing uses `password_hash()` (bcrypt) — available in all modern PHP builds.

## 2. Setup (local development)

1. **Copy the project** into your web root:
   - XAMPP → `C:\xampp\htdocs\Default Project`
2. **Start services** in the XAMPP control panel: **Apache** + **MySQL**.
3. **Create the database and tables:**

   ```bash
   cd "C:\xampp\htdocs\Default Project"
   "C:\xampp\mysql\bin\mysql.exe" -u root -p < schema.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root -p < seed.sql
   ```

   (or import both files through **phpMyAdmin** → Import).

4. **Check the database credentials** in `config.php` (`DB_HOST`, `DB_USER`, `DB_PASS`).
   The defaults target `root` with an empty password on `localhost`.
5. **Open the app:**
   <http://localhost/Default Project/>

> The `BASE_URL` constant in `config.php` includes the URL-encoded space
> (`Default%20Project`). If you rename the folder or use a local domain,
> update it accordingly (e.g. `http://localhost/attendance`).

## 3. Demo accounts (from `seed.sql`)

| Role     | Email                     | Password  |
|----------|---------------------------|-----------|
| Admin    | `admin@ulk.ac.rw`         | `Admin@123` |
| Lecturer | `a.uwase@ulk.ac.rw`       | `Lect@123`  |
| Lecturer | `e.niyonzima@ulk.ac.rw`   | `Lect@123`  |
| Lecturer | `c.mukamana@ulk.ac.rw`    | `Lect@123`  |
| Student  | `k.ingabire@ulk.ac.rw`    | `Stud@123`  |
| Student  | `d.uwera@ulk.ac.rw`       | `Stud@123`  |
| Student  | `s.habimana@ulk.ac.rw`    | `Stud@123`  |
| Student  | `a.umutoni@ulk.ac.rw`     | `Stud@123`  |
| Student  | `e.nshimiyimana@ulk.ac.rw`| `Stud@123`  |
| Student  | `g.mukandayisenga@ulk.ac.rw`| `Stud@123` |

Seed data includes 5 courses, 6 enrolled students, closed + 1 **Open** session
today, and 67 attendance records. Samples `Samuel Habimana` (2024-CS-003) and
`Angelique Umutoni` (2024-CS-004) are **below the 85% threshold** on CS101 so
you can see the eligibility alert immediately.

## 4. Project structure

```
├─ schema.sql          # MySQL schema (users, students, lecturers, courses,
│                      #   sessions, attendance_records + join tables, indexes)
├─ seed.sql            # Sample data (bcrypt-hashed passwords)
├─ config.php          # PDO connection, RBAC + CSRF + JSON helpers
├─ index.php           # Login form / authentication
├─ logout.php
├─ dashboard.php       # Role-based entry: routes to the correct dashboard
│
├─ includes/
│   ├─ header.php      # Shared <head>, top navigation (role-aware)
│   ├─ footer.php
│   └─ aggregates.php  # Shared reporting SQL used by pages + API
│
├─ student_profile.php       # Profile + per-course summary + eligibility
├─ student_attendance.php    # Personal attendance log
├─ lecturer_courses.php      # Assigned courses overview
├─ lecturer_sessions.php     # Session list + open/close new session
├─ lecturer_attendance.php   # Mark / edit attendance (AJAX)
├─ admin_users.php           # Manage students & lecturers
├─ admin_courses.php         # Course catalogue + lecturer assignment
├─ admin_rosters.php         # Build academic rosters per course
├─ admin_reports.php         # University reports + drill-down
│
├─ api/                       # JSON API endpoints (all POST writes require CSRF)
│   ├─ attendance.php         # roster + bulk save marks
│   ├─ sessions.php           # create / close sessions
│   ├─ users.php              # admin CRUD for accounts
│   ├─ courses.php            # admin CRUD for courses
│   ├─ rosters.php            # enrolment add/remove
│   └─ reports.php            # drill-down + CSV exports
│
└─ assets/
    ├─ css/app.css
    └─ js/app.js              # fetch wrapper, toasts, badges, loading covers
```

## 5. How it works

- **Authentication** - `index.php` verifies the email against the bcrypt hash
  (`password_verify`), regenerates the session id and stores `user_id`,
  `full_name`, `email`, `role`.
- **Authorization** - every page/API calls `requireLogin()` /
  `requireRole('…')` from `config.php`. Data endpoints re-verify ownership
  (e.g. a lecturer can only save marks on sessions of their own courses).
- **Attendance marking** - the lecturer opens a session, then
  `lecturer_attendance.php` loads the course roster via
  `GET api/attendance.php?action=roster`. Status changes are saved in one
  batch with `INSERT … ON DUPLICATE KEY UPDATE` (one mark per student/session)
  inside a transaction.
- **Eligibility** - attendance rate = (Present + Late) ÷ recorded sessions.
  `< 85%` triggers an alert on the student dashboard and a red badge in the
  admin reports. The threshold lives in `config.php` (ELIGIBILITY_THRESHOLD).
- **CSV exports** - `excel` summary per course (register) and the
  university-level roll-up stream from `api/reports.php`.

## 6. Regenerating the seed data

Passwords in `seed.sql` are real bcrypt hashes. If you want different demo
passwords, regenerate the file (requires Python):

```bash
pip install bcrypt
python tools/generate_seed.py   # recreates seed.sql
```

## 7. Security notes

- All SQL uses PDO prepared statements.
- Passwords are stored as bcrypt hashes (never plaintext).
- All API writes require a validated CSRF token.
- Session cookie regenerated after login.
- Output is escaped with an `e()` helper everywhere.

---

*Built with PHP 8 · MySQL · Tailwind CSS (CDN) · vanilla JavaScript.*
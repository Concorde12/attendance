#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
ULK Attendance System - seed.sql generator.

Generates sample data (seed.sql) for the ULK Attendance System and makes
sure every bcrypt password hash really matches its plaintext password
(verified with bcrypt.checkpw before it is written to the file).

Demo accounts created:
  ROLE      EMAIL                     PASSWORD       STUDENT_REG_NO (if student)
  -----     -----                     --------       ------------------------
  Admin     admin@ulk.ac.rw           Admin@123
  Lecturer  a.uwase@ulk.ac.rw         Lect@123       (lecturer 1)
  Lecturer  e.niyonzima@ulk.ac.rw     Lect@123       (lecturer 2)
  Lecturer  c.mukamana@ulk.ac.rw      Lect@123       (lecturer 3)
  Student   k.ingabire@ulk.ac.rw      Stud@123       2024-CS-001
  Student   e.habimana@ulk.ac.rw      Stud@123       2024-CS-002
  Student   s.uwera@ulk.ac.rw         Stud@123       2024-CS-003
  Student   d.umutoni@ulk.ac.rw       Stud@123       2024-CS-004
  Student   e.nshimiyimana@ulk.ac.rw Stud@123        2023-BIT-001
  Student   g.mukandayisenga@ulk.ac.rw Stud@123      2024-IT-001

Usage:
    pip install bcrypt
    python tools/generate_seed.py     # writes seed.sql in the project root
"""
import bcrypt

SEED_OUT = "seed.sql"

passwords = {"admin": "Admin@123", "lecturer": "Lect@123", "student": "Stud@123"}

# name, email, password-key, role  -- insertion order defines user_id: +====:
# Admin=1, Lecturers=2..4, Students=5..10
USER_ROWS = [
    ("Jean Bosco", "admin@ulk.ac.rw", "admin", "Admin"),
    ("Dr. Alice Uwase", "a.uwase@ulk.ac.rw", "lecturer", "Lecturer"),
    ("Mr. Eric Niyonzima", "e.niyonzima@ulk.ac.rw", "lecturer", "Lecturer"),
    ("Ms. Chantal Mukamana", "c.mukamana@ulk.ac.rw", "lecturer", "Lecturer"),
    ("Kevin Ingabire", "k.ingabire@ulk.ac.rw", "student", "Student"),
    ("Divine Uwera", "d.uwera@ulk.ac.rw", "student", "Student"),
    ("Samuel Habimana", "s.habimana@ulk.ac.rw", "student", "Student"),
    ("Angelique Umutoni", "a.umutoni@ulk.ac.rw", "student", "Student"),
    ("Eric Nshimiyimana", "e.nshimiyimana@ulk.ac.rw", "student", "Student"),
    ("Grace Mukandayisenga", "g.mukandayisenga@ulk.ac.rw", "student", "Student"),
]

STUDENTS = [
    (5, "2024-CS-001", "Software Engineering"),
    (6, "2024-CS-002", "Software Engineering"),
    (7, "2024-CS-003", "Software Engineering"),
    (8, "2024-CS-004", "Computer Science"),
    (9, "2023-BIT-001", "Business IT"),
    (10, "2024-IT-001", "Information Technology"),
]

LECTURERS = [(1, 2), (2, 3), (3, 4)]

COURSES = [
    ("CS101", "Introduction to Programming", 4, "Computing"),
    ("CS202", "Data Structures and Algorithms", 4, "Computing"),
    ("CS305", "Database Systems", 3, "Computing"),
    ("BIT201", "Web Development", 3, "IT"),
    ("IT110", "Computer Networks", 3, "IT"),
]

LECTURER_COURSES = [
    (1, "CS101"), (1, "CS305"),
    (2, "CS202"), (2, "BIT201"),
    (3, "IT110"),
]

R1 = "2024-CS-001"
R2 = "2024-CS-002"
R3 = "2024-CS-003"
R4 = "2024-CS-004"
R5 = "2023-BIT-001"
R6 = "2024-IT-001"

ENROLLMENTS = [
    (R1, "CS101"), (R2, "CS101"), (R3, "CS101"), (R4, "CS101"), (R5, "CS101"), (R6, "CS101"),
    (R1, "CS202"), (R2, "CS202"), (R3, "CS202"), (R4, "CS202"),
    (R1, "CS305"), (R2, "CS305"), (R3, "CS305"),
    (R1, "BIT201"), (R2, "BIT201"), (R3, "BIT201"), (R5, "BIT201"),
    (R4, "IT110"), (R5, "IT110"), (R6, "IT110"),
]

# course_code, lecturer_id, date, start, end, status
SESSIONS = [
    ("CS101", 1, "2026-07-03", "08:00:00", "10:00:00", "Closed"),
    ("CS101", 1, "2026-07-10", "08:00:00", "10:00:00", "Closed"),
    ("CS101", 1, "2026-07-17", "08:00:00", "10:00:00", "Closed"),
    ("CS101", 1, "2026-07-24", "08:00:00", "10:00:00", "Closed"),
    ("CS101", 1, "2026-07-31", "08:00:00", "10:00:00", "Closed"),
    ("CS101", 1, "2026-08-07", "08:00:00", "10:00:00", "Closed"),
    ("CS101", 1, "2026-08-08", "08:00:00", "10:00:00", "Open"),      # live demo session
    ("CS305", 1, "2026-07-06", "14:00:00", "16:00:00", "Closed"),
    ("CS305", 1, "2026-07-13", "14:00:00", "16:00:00", "Closed"),
    ("CS305", 1, "2026-07-27", "14:00:00", "16:00:00", "Closed"),
    ("BIT201", 2, "2026-07-08", "09:00:00", "11:00:00", "Closed"),
    ("BIT201", 2, "2026-07-15", "09:00:00", "11:00:00", "Closed"),
    ("BIT201", 2, "2026-07-29", "09:00:00", "11:00:00", "Closed"),
    ("BIT201", 2, "2026-08-05", "09:00:00", "11:00:00", "Closed"),
    ("IT110", 3, "2026-07-09", "10:00:00", "12:00:00", "Closed"),
    ("IT110", 3, "2026-07-23", "10:00:00", "12:00:00", "Closed"),
]

# attendance: session ordinal (1-based session position, skipping Open) -> {reg_no: status}
# Samuel (R3) and Angelique (R4) are deliberately below 85% to demo the alert.
ATTENDANCE = {
    1: {R1: "Present", R2: "Present", R3: "Present", R4: "Present", R5: "Present", R6: "Present"},
    2: {R1: "Present", R2: "Present", R3: "Absent", R4: "Present", R5: "Present", R6: "Present"},
    3: {R1: "Present", R2: "Present", R3: "Present", R4: "Present", R5: "Late", R6: "Late"},
    4: {R1: "Present", R2: "Present", R3: "Late", R4: "Present", R5: "Present", R6: "Present"},
    5: {R1: "Present", R2: "Present", R3: "Absent", R4: "Absent", R5: "Present", R6: "Present"},
    6: {R1: "Present", R2: "Present", R3: "Excused", R4: "Present", R5: "Present", R6: "Present"},
    8: {R1: "Present", R2: "Present", R3: "Present"},
    9: {R1: "Late", R2: "Present", R3: "Present"},
    10: {R1: "Present", R2: "Present", R3: "Present"},
    11: {R1: "Present", R2: "Present", R3: "Present", R5: "Present"},
    12: {R1: "Present", R2: "Late", R3: "Present", R5: "Late"},
    13: {R1: "Present", R2: "Present", R3: "Present", R5: "Present"},
    14: {R1: "Late", R2: "Present", R3: "Late", R5: "Present"},
    15: {R4: "Present", R5: "Present", R6: "Present"},
    16: {R4: "Present", R5: "Late", R6: "Present"},
}


def esc(value: str) -> str:
    """Naive SQL escaping for the literal demo values we emit."""
    return "'" + value.replace("\\", "\\\\").replace("'", "''") + "'"


def generate() -> str:
    lines = []
    lines.append("-- =======================================================================")
    lines.append("-- ULK ATTENDANCE SYSTEM - Sample Data (seed.sql)")
    lines.append("-- Auto-generated by tools/generate_seed.py  |  ALL PASSWORD HASHES VERIFIED")
    lines.append("--")
    lines.append("--   admin@ulk.ac.rw        / Admin@123")
    lines.append("--   a.uwase@ulk.ac.rw      / Lect@123   (lecturer 1)")
    lines.append("--   e.niyonzima@ulk.ac.rw  / Lect@123   (lecturer 2)")
    lines.append("--   c.mukamana@ulk.ac.rw   / Lect@123   (lecturer 3)")
    lines.append("--   k.ingabire@ulk.ac.rw   / Stud@123   (student 2024-CS-001)")
    lines.append("--   d.uwera@ulk.ac.rw      / Stud@123   (student 2024-CS-002)")
    lines.append("--   s.habimana@ulk.ac.rw   / Stud@123   (student 2024-CS-003)")
    lines.append("--   a.umutoni@ulk.ac.rw    / Stud@123   (student 2024-CS-004)")
    lines.append("--   e.nshimiyimana@ulk.ac.rw / Stud@123 (student 2023-BIT-001)")
    lines.append("--   g.mukandayisenga@ulk.ac.rw / Stud@123 (student 2024-IT-001)")
    lines.append("-- =======================================================================")
    lines.append("USE ulk_attendance_db;")
    lines.append("")

    lines.append("-- users -------------------------------------------------------------")
    lines.append("INSERT INTO users (full_name, email, password_hash, role) VALUES")
    user_rows = []
    for name, email, key, role in USER_ROWS:
        pw = passwords[key]
        hh = bcrypt.hashpw(pw.encode(), bcrypt.gensalt()).decode()
        assert bcrypt.checkpw(pw.encode(), hh.encode()), f"hash mismatch for {email}"
        user_rows.append(f"    ({esc(name)}, {esc(email)}, {esc(hh)}, {esc(role)})")
    lines.append(",\n".join(user_rows) + ";")
    lines.append("")

    lines.append("-- students ----------------------------------------------------------")
    lines.append("INSERT INTO students (student_reg_no, user_id, major, intake_year) VALUES")
    srows = [f"    ({esc(reg)}, {uid}, {esc(major)}, {esc(str(year))})"
             for uid, reg, major, year in [
                 (5, "2024-CS-001", "Software Engineering", 2024),
                 (6, "2024-CS-002", "Software Engineering", 2024),
                 (7, "2024-CS-003", "Software Engineering", 2024),
                 (8, "2024-CS-004", "Computer Science", 2024),
                 (9, "2023-BIT-001", "Business IT", 2023),
                 (10, "2024-IT-001", "Information Technology", 2024),
             ]]
    lines.append(",\n".join(srows) + ";")
    lines.append("")

    lines.append("-- lecturers ---------------------------------------------------------")
    lines.append("INSERT INTO lecturers (lecturer_id, user_id) VALUES (1, 2), (2, 3), (3, 4);")
    lines.append("")

    lines.append("-- courses -------------------------------------------------------------")
    lines.append("INSERT INTO courses (course_code, title, credits, department) VALUES")
    lines.append(",\n".join(f"    ({esc(c)}, {esc(t)}, {cr}, {esc(d)})" for c, t, cr, d in COURSES) + ";")
    lines.append("")

    lines.append("-- lecturer_courses -----------------------------------------------------")
    lines.append("INSERT INTO lecturer_courses (lecturer_id, course_code) VALUES")
    lines.append(",\n".join(f"    ({lid}, {esc(cc)})" for lid, cc in LECTURER_COURSES) + ";")
    lines.append("")

    lines.append("-- enrollments -----------------------------------------------------------")
    lines.append("INSERT INTO enrollments (student_reg_no, course_code) VALUES")
    lines.append(",\n".join(f"    ({esc(reg)}, {esc(cc)})" for reg, cc in ENROLLMENTS) + ";")
    lines.append("")

    lines.append("-- sessions ---------------------------------------------------------------")
    lines.append("INSERT INTO sessions (course_code, lecturer_id, session_date, start_time, end_time, status) VALUES")
    lines.append(",\n".join(f"    ({esc(cc)}, {lid}, {esc(d)}, {esc(s)}, {esc(e)}, {esc(st)})"
                            for cc, lid, d, s, e, st in SESSIONS) + ";")
    lines.append("")

    lines.append("-- attendance_records -----------------------------------------------------")
    # session id i in ATTENDANCE == position of the session in SESSIONS (1-based)
    arows = []
    for i, (cc, lid, d, s, e, st) in enumerate(SESSIONS, start=1):
        if st == "Open":
            continue
        if i in ATTENDANCE:
            for reg, status in ATTENDANCE[i].items():
                arows.append(f"    ({i}, {esc(reg)}, {esc(status)})")
    lines.append("INSERT INTO attendance_records (session_id, student_reg_no, status) VALUES")
    lines.append(",\n".join(arows) + ";")
    lines.append("")

    return "\n".join(lines) + "\n"


if __name__ == "__main__":
    with open(SEED_OUT, "w", encoding="utf-8", newline="\n") as f:
        f.write(generate())
    print("wrote", SEED_OUT)
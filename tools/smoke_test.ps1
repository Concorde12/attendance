# ULK Attendance System - end-to-end smoke test against the PHP built-in server
$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8090'
$tmp  = 'C:\Users\user\AppData\Local\Temp\opencode'

function Login([string]$email, [string]$pw, [string]$jarFile) {
    curl.exe -s -c $jarFile "$base/index.php" -o "$tmp\page.html" 2>$null
    $html = Get-Content "$tmp\page.html" -Raw
    $tok = [regex]::Match($html, 'name="csrf_token" value="([^"]+)"').Groups[1].Value
    curl.exe -s -b $jarFile -c $jarFile -d "csrf_token=$tok&email=$email&password=$pw" "$base/index.php" 2>$null | Out-Null
}

$out = @()

# --- 1. Admin login + reports page ---
$aj = "$tmp\jar_admin.txt"
Remove-Item $aj -ErrorAction SilentlyContinue
Login 'admin@ulk.ac.rw' 'Admin@123' $aj
$reports = curl.exe -s -b $aj "$base/admin_reports.php"
$out += "ADMIN_CS101_IN_REPORTS=$($reports -match 'CS101')"

# --- 2. Admin API: per-student report ---
$report = curl.exe -s -b $aj "$base/api/reports.php?action=students&course_code=CS101"
$hasEligible = $report -match 'eligible'
$hasRate     = $report -match '"rate":'
$out += "ADMIN_REPORT_JSON_OK=$($hasEligible -and $hasRate)"

# --- 3. Lecturer login + marking page (generates CSRF in session) ---
$lj = "$env:TEMP\jar_lect.txt"
$tmpJarL = "$env:TEMP\jar_lecturer.txt"
Remove-Item $tmpJarL -ErrorAction SilentlyContinue
Login 'a.uwase@ulk.ac.rw' 'Lect@123' $tmpJarL
$lec = curl.exe -s -b $tmpJarL "$base/lecturer_attendance.php?session_id=7"
$lecTok = [regex]::Match($lec, 'name="csrf-token" content="([^"]+)"').Groups[1].Value
$out += "LECTURER_TOKEN_GEN=$($lecTok.Length -gt 0)"

# --- 4. Roster GET ---
$roster = curl.exe -s -b $tmpJarL "$base/api/attendance.php?action=roster&session_id=7"
$rosterCount = ([regex]::Matches($roster, '"student_reg_no"')).Count
$out += "ROSTER_CS101_STUDENTS=$rosterCount"

# --- 5. Save marks (JSON POST + CSRF header) ---
$body = '{"action":"save","session_id":7,"records":[{"student_reg_no":"2024-CS-001","status":"Present"},{"student_reg_no":"2024-CS-002","status":"Late"},{"student_reg_no":"2024-CS-003","status":"Absent"}]}'
$save = curl.exe -s -b $tmpJarL -H "Content-Type: application/json" -H "X-CSRF-Token: $lecTok" -d $body "$base/api/attendance.php"
$out += "SAVE_RESP = $save"

# --- 6. Re-fetch roster to verify persistence ---
$re = curl.exe -s -b $tmpJarL "$base/api/attendance.php?action=roster&session_id=7"
$out += "PERSISTED_LATE = $($re -match '\"Late\"')"
$out += "PERSISTED_ABSENT = $($re -match '\"Absent\"')"

# --- 7. Create session (lecturer) ---
$cb = '{"action":"create","course_code":"CS101","session_date":"2026-08-10","start_time":"09:00:00","end_time":"11:00:00"}'
$sn = curl.exe -s -b $tmpJarL -H "Content-Type: application/json" -H "X-CSRF-Token: $lecTok" -d $cb "$base/api/sessions.php"
$out += "CREATE_SESSION = $sn"

# --- 8. Role guard: student must NOT reach the lecturer API ---
$sj = "$env:TEMP/jar_student.txt"
Remove-Item $sj -ErrorAction SilentlyContinue
Login 's.habimana@ulk.ac.rw' 'Stud@123' $sj
$guard = curl.exe -s -b $sj "$base/api/attendance.php?action=roster&session_id=7"
$guardStr = if ($null -eq $guard) { '' } else { $guard }
$out += "STUDENT_BLOCKED_FROM_LECTURER_API = $(-not $guardStr.StartsWith('{'))"

# --- 9. At-risk student eligibility alert visible ---
$stu = curl.exe -s -b $sj "$base/student_profile.php"
$out += "STUDENT_SEES_RISK_ALERT = $($stu -match 'below the 85%')"

$out | ForEach-Object { Write-Output $_ }
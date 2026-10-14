<?php
require_once __DIR__ . '/includes/auth.php';
start_session();

$result = null;   // [type, message]
$selected = (int)($_POST['lecture'] ?? 0);

$open = db()->query('SELECT l.LectureID, l.CourseID, c.CourseName, l.LectureDate, l.StartTime
    FROM lectures l JOIN courses c ON c.CourseID = l.CourseID
    WHERE l.IsOpen = 1 ORDER BY l.LectureDate DESC, l.StartTime DESC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid = strtoupper(trim($_POST['student_id'] ?? ''));
    $lecture = null;
    foreach ($open as $l) if ((int)$l['LectureID'] === $selected) $lecture = $l;

    if (!csrf_check()) {
        $result = ['err', 'Invalid request. Please reload the page and try again.'];
    } elseif (!$lecture) {
        $result = ['err', 'Please select a lecture that is currently open for attendance.'];
    } elseif (!preg_match('/^[A-Z0-9]{4,20}$/', $sid)) {
        $result = ['err', 'Enter a valid student ID (letters and digits only).'];
    } else {
        $st = db()->prepare('SELECT StudentID, StudentName, Programme, Level FROM students WHERE StudentID = ?');
        $st->execute([$sid]);
        $student = $st->fetch();
        if (!$student) {
            $result = ['err', 'Student ID not found. Please check your ID or contact your lecturer.'];
        } else {
            try {
                db()->prepare("INSERT INTO attendance (StudentID, LectureID, AttendanceDate, AttendanceTime, Status)
                    VALUES (?, ?, CURDATE(), CURTIME(), 'Present')")->execute([$student['StudentID'], $lecture['LectureID']]);
                $result = ['ok', 'Attendance recorded for ' . $student['StudentName'] . ' (' . $student['StudentID'] . ') – '
                    . $lecture['CourseID'] . ' at ' . date('H:i')];
            } catch (PDOException $ex) {
                $result = $ex->getCode() === '23000'
                    ? ['warn', $student['StudentName'] . ', your attendance for this lecture has already been recorded.']
                    : ['err', 'Could not record attendance. Please try again.'];
            }
        }
    }
    if ($result[0] === 'ok') $_POST['student_id'] = '';
} elseif (count($open) === 1) {
    $selected = (int)$open[0]['LectureID'];
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mark Attendance – KNUST IDL</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="center"><form class="card" method="post" autocomplete="off">
<h1>KNUST IDL Attendance</h1><p class="muted">Enter your student ID to mark attendance</p>
<?php if ($result): ?><div class="alert <?= $result[0] ?>"><?= e($result[1]) ?></div><?php endif; ?>
<?php if (!$open): ?>
<div class="alert warn">No lecture is open for attendance right now. Please wait for your lecturer to open one.</div>
<?php else: ?>
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<label>Lecture<select name="lecture" required>
<?php foreach ($open as $l): ?>
<option value="<?= (int)$l['LectureID'] ?>" <?= $selected === (int)$l['LectureID'] ? 'selected' : '' ?>><?= e($l['CourseID'] . ' – ' . $l['CourseName'] . ' (' . $l['LectureDate'] . ' ' . substr($l['StartTime'], 0, 5) . ')') ?></option>
<?php endforeach; ?>
</select></label>
<label>Student ID<input name="student_id" value="<?= e($_POST['student_id'] ?? '') ?>" required autofocus maxlength="20"></label>
<button type="submit">Mark me present</button>
<?php endif; ?>
</form></body></html>

<?php
require_once __DIR__ . '/../includes/layout.php';
require_login();

$f = [
    'q'       => trim($_GET['q'] ?? ''),
    'course'  => trim($_GET['course'] ?? ''),
    'lecture' => (int)($_GET['lecture'] ?? 0),
    'from'    => trim($_GET['from'] ?? ''),
    'to'      => trim($_GET['to'] ?? ''),
    'view'    => ($_GET['view'] ?? '') === 'absent' ? 'absent' : 'present',
];
$valid_date = fn($d) => $d === '' || (($x = DateTime::createFromFormat('Y-m-d', $d)) && $x->format('Y-m-d') === $d);
$errors = [];
if (!$valid_date($f['from']) || !$valid_date($f['to'])) { $errors[] = 'Enter valid dates.'; $f['from'] = $f['to'] = ''; }
if ($f['view'] === 'absent' && !$f['lecture']) $errors[] = 'Choose a lecture to see who was absent.';

$courses  = db()->query('SELECT CourseID, CourseName FROM courses ORDER BY CourseID')->fetchAll();
$lectures = db()->query('SELECT l.LectureID, l.CourseID, l.LectureDate, l.StartTime FROM lectures l ORDER BY l.LectureDate DESC, l.StartTime DESC')->fetchAll();

$headers = []; $rows = [];
if (!$errors) {
    if ($f['view'] === 'absent') {
        $headers = ['Student ID', 'Name', 'Programme', 'Level'];
        $st = db()->prepare('SELECT s.StudentID, s.StudentName, s.Programme, s.Level FROM students s
            WHERE NOT EXISTS (SELECT 1 FROM attendance a WHERE a.StudentID = s.StudentID AND a.LectureID = ?)
            AND (s.StudentID LIKE ? OR s.StudentName LIKE ?) ORDER BY s.StudentName');
        $st->execute([$f['lecture'], '%' . $f['q'] . '%', '%' . $f['q'] . '%']);
        $rows = $st->fetchAll(PDO::FETCH_NUM);
    } else {
        $headers = ['Student ID', 'Name', 'Course', 'Lecture date', 'Date recorded', 'Time', 'Status'];
        $where = ['(s.StudentID LIKE ? OR s.StudentName LIKE ?)']; $p = ['%' . $f['q'] . '%', '%' . $f['q'] . '%'];
        if ($f['course'] !== '') { $where[] = 'l.CourseID = ?'; $p[] = $f['course']; }
        if ($f['lecture'])       { $where[] = 'l.LectureID = ?'; $p[] = $f['lecture']; }
        if ($f['from'] !== '')   { $where[] = 'a.AttendanceDate >= ?'; $p[] = $f['from']; }
        if ($f['to'] !== '')     { $where[] = 'a.AttendanceDate <= ?'; $p[] = $f['to']; }
        $st = db()->prepare('SELECT s.StudentID, s.StudentName, l.CourseID, l.LectureDate, a.AttendanceDate, a.AttendanceTime, a.Status
            FROM attendance a JOIN students s ON s.StudentID = a.StudentID JOIN lectures l ON l.LectureID = a.LectureID
            WHERE ' . implode(' AND ', $where) . ' ORDER BY a.AttendanceDate DESC, a.AttendanceTime DESC');
        $st->execute($p);
        $rows = $st->fetchAll(PDO::FETCH_NUM);
    }
}

if (isset($_GET['export']) && !$errors) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_' . $f['view'] . '_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // BOM so Excel reads UTF-8
    $safe = fn($v) => preg_match('/^[=+\-@\t\r]/', (string)$v) ? "'" . $v : $v;   // block spreadsheet formula injection
    fputcsv($out, $headers);
    foreach ($rows as $r) fputcsv($out, array_map($safe, $r));
    exit;
}

$qs = http_build_query(array_filter($f, fn($v) => $v !== '' && $v !== 0));
page_header('Reports'); ?>
<h1>Attendance records</h1>
<?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>

<form method="get" class="panel noprint">
<div class="row">
<label>Show<select name="view">
<option value="present" <?= $f['view'] === 'present' ? 'selected' : '' ?>>Attendance records</option>
<option value="absent" <?= $f['view'] === 'absent' ? 'selected' : '' ?>>Absentees (needs a lecture)</option>
</select></label>
<label>Student (ID or name)<input name="q" value="<?= e($f['q']) ?>"></label>
<label>Course<select name="course"><option value="">All</option>
<?php foreach ($courses as $c): ?><option value="<?= e($c['CourseID']) ?>" <?= $f['course'] === $c['CourseID'] ? 'selected' : '' ?>><?= e($c['CourseID']) ?></option><?php endforeach; ?>
</select></label>
<label>Lecture<select name="lecture"><option value="0">All</option>
<?php foreach ($lectures as $l): ?><option value="<?= (int)$l['LectureID'] ?>" <?= $f['lecture'] === (int)$l['LectureID'] ? 'selected' : '' ?>><?= e($l['CourseID'] . ' · ' . $l['LectureDate'] . ' ' . substr($l['StartTime'], 0, 5)) ?></option><?php endforeach; ?>
</select></label>
<label>From<input type="date" name="from" value="<?= e($f['from']) ?>"></label>
<label>To<input type="date" name="to" value="<?= e($f['to']) ?>"></label>
</div>
<button class="inline">Apply filters</button>
<a class="btn-link" href="reports.php">Reset</a>
<a class="btn-link" href="?<?= e($qs . ($qs ? '&' : '') . 'export=csv') ?>">Export CSV</a>
<a class="btn-link" href="#" onclick="window.print();return false">Print</a>
</form>

<p class="muted"><?= count($rows) ?> <?= $f['view'] === 'absent' ? 'absent student(s)' : 'record(s)' ?></p>
<table>
<thead><tr><?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?><tr><?php foreach ($r as $c): ?><td><?= e((string)$c) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="<?= max(1, count($headers)) ?>" class="muted">Nothing to show.</td></tr><?php endif; ?>
</tbody></table>
<?php page_footer();

<?php
require_once __DIR__ . '/../includes/layout.php';
require_login();

$errors = [];
$form = ['CourseID' => '', 'LectureDate' => date('Y-m-d'), 'StartTime' => '08:00'];
$courses = db()->query('SELECT CourseID, CourseName FROM courses ORDER BY CourseID')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('Invalid request.', 'err'); header('Location: lectures.php'); exit; }
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        db()->prepare('UPDATE lectures SET IsOpen = 1 - IsOpen WHERE LectureID = ?')->execute([(int)($_POST['LectureID'] ?? 0)]);
        flash('Lecture status updated.'); header('Location: lectures.php'); exit;
    }
    if ($action === 'delete') {
        db()->prepare('DELETE FROM lectures WHERE LectureID = ?')->execute([(int)($_POST['LectureID'] ?? 0)]);
        flash('Lecture deleted.'); header('Location: lectures.php'); exit;
    }
    $in = [
        'CourseID'    => trim($_POST['CourseID'] ?? ''),
        'LectureDate' => trim($_POST['LectureDate'] ?? ''),
        'StartTime'   => trim($_POST['StartTime'] ?? ''),
    ];
    $d = DateTime::createFromFormat('Y-m-d', $in['LectureDate']);
    if (!$d || $d->format('Y-m-d') !== $in['LectureDate']) $errors[] = 'Enter a valid date.';
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $in['StartTime'])) $errors[] = 'Enter a valid start time.';
    $chk = db()->prepare('SELECT 1 FROM courses WHERE CourseID = ?');
    $chk->execute([$in['CourseID']]);
    if (!$chk->fetchColumn()) $errors[] = 'Select a valid course.';
    if (!$errors) {
        db()->prepare('INSERT INTO lectures (CourseID, LectureDate, StartTime) VALUES (?,?,?)')
            ->execute([$in['CourseID'], $in['LectureDate'], $in['StartTime'] . ':00']);
        flash('Lecture session created.'); header('Location: lectures.php'); exit;
    }
    $form = $in;
}

$filter = trim($_GET['course'] ?? '');
$sql = 'SELECT l.LectureID, l.CourseID, c.CourseName, l.LectureDate, l.StartTime, l.IsOpen,
        (SELECT COUNT(*) FROM attendance a WHERE a.LectureID = l.LectureID) AS Present
        FROM lectures l JOIN courses c ON c.CourseID = l.CourseID';
$params = [];
if ($filter !== '') { $sql .= ' WHERE l.CourseID = ?'; $params[] = $filter; }
$sql .= ' ORDER BY l.LectureDate DESC, l.StartTime DESC';
$st = db()->prepare($sql);
$st->execute($params);
$lectures = $st->fetchAll();
$flash = flash();

page_header('Lectures'); ?>
<h1>Lecture sessions</h1>
<?php if ($flash): ?><div class="alert <?= $flash[1] ?>"><?= e($flash[0]) ?></div><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>

<?php if (!$courses): ?>
<div class="alert err">Add a course first before creating lecture sessions.</div>
<?php else: ?>
<form method="post" class="panel">
<h2>Create lecture session</h2>
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="add">
<div class="row">
<label>Course<select name="CourseID" required>
<?php foreach ($courses as $c): ?>
<option value="<?= e($c['CourseID']) ?>" <?= $form['CourseID'] === $c['CourseID'] ? 'selected' : '' ?>><?= e($c['CourseID'] . ' – ' . $c['CourseName']) ?></option>
<?php endforeach; ?>
</select></label>
<label>Date<input type="date" name="LectureDate" value="<?= e($form['LectureDate']) ?>" required></label>
<label>Start time<input type="time" name="StartTime" value="<?= e($form['StartTime']) ?>" required></label>
</div>
<button type="submit" class="inline">Create session</button>
</form>
<?php endif; ?>

<form method="get" class="search">
<select name="course"><option value="">All courses</option>
<?php foreach ($courses as $c): ?>
<option value="<?= e($c['CourseID']) ?>" <?= $filter === $c['CourseID'] ? 'selected' : '' ?>><?= e($c['CourseID']) ?></option>
<?php endforeach; ?>
</select><button class="inline">Filter</button></form>

<table>
<thead><tr><th>Course</th><th>Date</th><th>Start</th><th>Status</th><th>Attendance</th><th></th></tr></thead>
<tbody>
<?php foreach ($lectures as $l): ?>
<tr><td><?= e($l['CourseID'] . ' – ' . $l['CourseName']) ?></td><td><?= e($l['LectureDate']) ?></td>
<td><?= e(substr($l['StartTime'], 0, 5)) ?></td>
<td><span class="badge <?= $l['IsOpen'] ? 'open' : '' ?>"><?= $l['IsOpen'] ? 'Open' : 'Closed' ?></span></td>
<td><?= (int)$l['Present'] ?></td>
<td class="actions">
<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle">
<input type="hidden" name="LectureID" value="<?= (int)$l['LectureID'] ?>"><button class="link"><?= $l['IsOpen'] ? 'Close' : 'Open' ?></button></form>
<form method="post" onsubmit="return confirm('Delete this lecture and its attendance records?')">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete">
<input type="hidden" name="LectureID" value="<?= (int)$l['LectureID'] ?>"><button class="link danger">Delete</button></form></td></tr>
<?php endforeach; ?>
<?php if (!$lectures): ?><tr><td colspan="6" class="muted">No lecture sessions yet.</td></tr><?php endif; ?>
</tbody></table>
<?php page_footer();

<?php
require_once __DIR__ . '/../includes/layout.php';
require_login();

$errors = [];
$edit = null;
$form = ['CourseID' => '', 'CourseName' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('Invalid request.', 'err'); header('Location: courses.php'); exit; }
    $action = $_POST['action'] ?? '';
    $in = ['CourseID' => strtoupper(trim($_POST['CourseID'] ?? '')), 'CourseName' => trim($_POST['CourseName'] ?? '')];

    if ($action === 'delete') {
        db()->prepare('DELETE FROM courses WHERE CourseID = ?')->execute([$in['CourseID']]);
        flash('Course deleted.'); header('Location: courses.php'); exit;
    }
    $isNew = $action === 'add';
    if ($isNew && !preg_match('/^[A-Z0-9]{3,20}$/', $in['CourseID'])) $errors[] = 'Course ID must be 3–20 letters/digits.';
    if ($in['CourseName'] === '' || mb_strlen($in['CourseName']) > 150) $errors[] = 'Course name is required (max 150 characters).';
    if (!$errors) {
        try {
            if ($isNew) {
                db()->prepare('INSERT INTO courses (CourseID, CourseName) VALUES (?,?)')->execute([$in['CourseID'], $in['CourseName']]);
                flash('Course added.');
            } else {
                db()->prepare('UPDATE courses SET CourseName=? WHERE CourseID=?')->execute([$in['CourseName'], $in['CourseID']]);
                flash('Course updated.');
            }
            header('Location: courses.php'); exit;
        } catch (PDOException $ex) {
            $errors[] = $ex->getCode() === '23000' ? 'That Course ID already exists.' : 'Database error.';
        }
    }
    $form = $in;
    if (!$isNew) $edit = $in;
} elseif (isset($_GET['edit'])) {
    $st = db()->prepare('SELECT * FROM courses WHERE CourseID = ?');
    $st->execute([$_GET['edit']]);
    if ($edit = $st->fetch()) $form = $edit;
}

$courses = db()->query('SELECT c.CourseID, c.CourseName, COUNT(l.LectureID) AS Lectures
    FROM courses c LEFT JOIN lectures l ON l.CourseID = c.CourseID GROUP BY c.CourseID, c.CourseName ORDER BY c.CourseID')->fetchAll();
$flash = flash();

page_header('Courses'); ?>
<h1>Courses</h1>
<?php if ($flash): ?><div class="alert <?= $flash[1] ?>"><?= e($flash[0]) ?></div><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" class="panel">
<h2><?= $edit ? 'Edit course' : 'Add course' ?></h2>
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>">
<div class="row">
<label>Course ID<input name="CourseID" value="<?= e($form['CourseID']) ?>" <?= $edit ? 'readonly' : 'required' ?> maxlength="20" placeholder="DIT301"></label>
<label>Course name<input name="CourseName" value="<?= e($form['CourseName']) ?>" required maxlength="150"></label>
</div>
<button type="submit" class="inline"><?= $edit ? 'Save changes' : 'Add course' ?></button>
<?php if ($edit): ?><a class="btn-link" href="courses.php">Cancel</a><?php endif; ?>
</form>

<table>
<thead><tr><th>Course ID</th><th>Name</th><th>Lectures</th><th></th></tr></thead>
<tbody>
<?php foreach ($courses as $c): ?>
<tr><td><?= e($c['CourseID']) ?></td><td><?= e($c['CourseName']) ?></td><td><?= (int)$c['Lectures'] ?></td>
<td class="actions"><a href="?edit=<?= urlencode($c['CourseID']) ?>">Edit</a>
<form method="post" onsubmit="return confirm('Delete this course, its lectures and their attendance records?')">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete">
<input type="hidden" name="CourseID" value="<?= e($c['CourseID']) ?>"><button class="link danger">Delete</button></form></td></tr>
<?php endforeach; ?>
<?php if (!$courses): ?><tr><td colspan="4" class="muted">No courses yet.</td></tr><?php endif; ?>
</tbody></table>
<?php page_footer();

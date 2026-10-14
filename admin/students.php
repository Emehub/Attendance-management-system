<?php
require_once __DIR__ . '/../includes/layout.php';
require_login();

$errors = [];
$edit = null;   // row being edited
$form = ['StudentID' => '', 'StudentName' => '', 'Programme' => '', 'Level' => ''];

function validate_student(array $in, bool $isNew): array {
    $errs = [];
    if ($isNew && !preg_match('/^[A-Za-z0-9]{4,20}$/', $in['StudentID'])) $errs[] = 'Student ID must be 4–20 letters/digits.';
    if ($in['StudentName'] === '' || mb_strlen($in['StudentName']) > 100) $errs[] = 'Name is required (max 100 characters).';
    if ($in['Programme'] === '' || mb_strlen($in['Programme']) > 100) $errs[] = 'Programme is required (max 100 characters).';
    if (!preg_match('/^\d{3}$/', $in['Level'])) $errs[] = 'Level must be 3 digits, e.g. 100.';
    return $errs;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('Invalid request.', 'err'); header('Location: students.php'); exit; }
    $action = $_POST['action'] ?? '';
    $in = [];
    foreach (['StudentID', 'StudentName', 'Programme', 'Level'] as $k) $in[$k] = trim($_POST[$k] ?? '');

    if ($action === 'delete') {
        db()->prepare('DELETE FROM students WHERE StudentID = ?')->execute([$in['StudentID']]);
        flash('Student deleted.'); header('Location: students.php'); exit;
    }
    $isNew = $action === 'add';
    $errors = validate_student($in, $isNew);
    if (!$errors) {
        try {
            if ($isNew) {
                db()->prepare('INSERT INTO students (StudentID, StudentName, Programme, Level) VALUES (?,?,?,?)')
                    ->execute([$in['StudentID'], $in['StudentName'], $in['Programme'], $in['Level']]);
                flash('Student added.');
            } else {
                db()->prepare('UPDATE students SET StudentName=?, Programme=?, Level=? WHERE StudentID=?')
                    ->execute([$in['StudentName'], $in['Programme'], $in['Level'], $in['StudentID']]);
                flash('Student updated.');
            }
            header('Location: students.php'); exit;
        } catch (PDOException $ex) {
            $errors[] = $ex->getCode() === '23000' ? 'That Student ID already exists.' : 'Database error.';
        }
    }
    $form = $in;
    if (!$isNew) $edit = $in;
} elseif (isset($_GET['edit'])) {
    $st = db()->prepare('SELECT * FROM students WHERE StudentID = ?');
    $st->execute([$_GET['edit']]);
    if ($edit = $st->fetch()) $form = $edit;
}

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';
$st = db()->prepare('SELECT * FROM students WHERE StudentID LIKE ? OR StudentName LIKE ? OR Programme LIKE ? ORDER BY StudentName');
$st->execute([$like, $like, $like]);
$students = $st->fetchAll();
$flash = flash();

page_header('Students'); ?>
<h1>Students</h1>
<?php if ($flash): ?><div class="alert <?= $flash[1] ?>"><?= e($flash[0]) ?></div><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" class="panel">
<h2><?= $edit ? 'Edit student' : 'Add student' ?></h2>
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>">
<div class="row">
<label>Student ID<input name="StudentID" value="<?= e($form['StudentID']) ?>" <?= $edit ? 'readonly' : 'required' ?> maxlength="20"></label>
<label>Full name<input name="StudentName" value="<?= e($form['StudentName']) ?>" required maxlength="100"></label>
<label>Programme<input name="Programme" value="<?= e($form['Programme']) ?>" required maxlength="100"></label>
<label>Level<input name="Level" value="<?= e($form['Level']) ?>" required maxlength="3" pattern="\d{3}" placeholder="100"></label>
</div>
<button type="submit" class="inline"><?= $edit ? 'Save changes' : 'Add student' ?></button>
<?php if ($edit): ?><a class="btn-link" href="students.php">Cancel</a><?php endif; ?>
</form>

<form method="get" class="search"><input name="q" value="<?= e($q) ?>" placeholder="Search by ID, name or programme"><button class="inline">Search</button></form>

<table>
<thead><tr><th>Student ID</th><th>Name</th><th>Programme</th><th>Level</th><th></th></tr></thead>
<tbody>
<?php foreach ($students as $s): ?>
<tr><td><?= e($s['StudentID']) ?></td><td><?= e($s['StudentName']) ?></td><td><?= e($s['Programme']) ?></td><td><?= e($s['Level']) ?></td>
<td class="actions"><a href="?edit=<?= urlencode($s['StudentID']) ?>">Edit</a>
<form method="post" onsubmit="return confirm('Delete this student and their attendance records?')">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete">
<input type="hidden" name="StudentID" value="<?= e($s['StudentID']) ?>"><button class="link danger">Delete</button></form></td></tr>
<?php endforeach; ?>
<?php if (!$students): ?><tr><td colspan="5" class="muted">No students found.</td></tr><?php endif; ?>
</tbody></table>
<?php page_footer();

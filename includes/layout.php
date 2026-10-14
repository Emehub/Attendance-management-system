<?php
require_once __DIR__ . '/auth.php';

function page_header(string $title): void { ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> – IDL Attendance</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<header class="bar"><strong>KNUST IDL Attendance</strong>
<nav><a href="dashboard.php">Dashboard</a><a href="students.php">Students</a><a href="courses.php">Courses</a><a href="lectures.php">Lectures</a><a href="reports.php">Reports</a></nav>
<span><?= e($_SESSION['full_name'] ?? '') ?> · <a href="password.php">Password</a> · <a href="logout.php">Log out</a></span></header>
<main>
<?php }

function page_footer(): void { echo "</main></body></html>"; }

function flash(?string $msg = null, string $type = 'ok'): ?array {
    start_session();
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

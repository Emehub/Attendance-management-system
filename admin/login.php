<?php
require_once __DIR__ . '/../includes/auth.php';
start_session();
if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $stmt = db()->prepare('SELECT UserID, FullName, PasswordHash FROM users WHERE Username = ?');
        $stmt->execute([trim($_POST['username'] ?? '')]);
        $u = $stmt->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['PasswordHash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $u['UserID'];
            $_SESSION['full_name'] = $u['FullName'];
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login – IDL Attendance</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="center"><form class="card" method="post" autocomplete="off">
<h1>KNUST IDL Attendance</h1><p class="muted">Staff login</p>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<label>Username<input name="username" required autofocus></label>
<label>Password<input type="password" name="password" required></label>
<button type="submit">Log in</button></form></body></html>

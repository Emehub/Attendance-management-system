<?php
require_once __DIR__ . '/../includes/layout.php';
require_login();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $current = $_POST['current'] ?? '';
        $new     = $_POST['new'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        $st = db()->prepare('SELECT PasswordHash FROM users WHERE UserID = ?');
        $st->execute([$_SESSION['user_id']]);
        $hash = $st->fetchColumn();

        if (!$hash || !password_verify($current, $hash)) $errors[] = 'Current password is incorrect.';
        if (strlen($new) < 10) $errors[] = 'New password must be at least 10 characters.';
        if ($new !== $confirm) $errors[] = 'New password and confirmation do not match.';
        if ($new !== '' && $new === $current) $errors[] = 'New password must be different from the current one.';

        if (!$errors) {
            db()->prepare('UPDATE users SET PasswordHash = ? WHERE UserID = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            session_regenerate_id(true);
            flash('Password changed.');
            header('Location: password.php'); exit;
        }
    }
}
$flash = flash();

page_header('Change password'); ?>
<h1>Change password</h1>
<?php if ($flash): ?><div class="alert <?= $flash[1] ?>"><?= e($flash[0]) ?></div><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" class="panel" style="max-width:420px" autocomplete="off">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<label>Current password<input type="password" name="current" required></label>
<label>New password (min. 10 characters)<input type="password" name="new" required minlength="10"></label>
<label>Confirm new password<input type="password" name="confirm" required minlength="10"></label>
<button type="submit" class="inline">Change password</button>
</form>
<?php page_footer();

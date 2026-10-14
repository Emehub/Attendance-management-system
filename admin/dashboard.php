<?php
require_once __DIR__ . '/../includes/layout.php';
require_login();
$counts = [];
foreach (['students', 'courses', 'lectures', 'attendance'] as $t) {
    $counts[$t] = db()->query("SELECT COUNT(*) FROM $t")->fetchColumn();
}
page_header('Dashboard'); ?>
<h1>Dashboard</h1>
<div class="grid">
<?php foreach ($counts as $name => $n): ?>
<div class="stat"><div class="n"><?= (int)$n ?></div><div class="muted"><?= e(ucfirst($name)) ?></div></div>
<?php endforeach; ?>
</div>
<?php page_footer();

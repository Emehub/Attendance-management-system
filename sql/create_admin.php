<?php
// CLI only: php sql/create_admin.php <username> <password> "<Full Name>"
if (PHP_SAPI !== 'cli') exit('CLI only');
require __DIR__ . '/../config/db.php';
[$_, $user, $pass, $name] = $argv + [null, 'admin', 'Admin@123', 'Administrator'];
db()->prepare('INSERT INTO users (Username, PasswordHash, FullName) VALUES (?,?,?)
  ON DUPLICATE KEY UPDATE PasswordHash = VALUES(PasswordHash), FullName = VALUES(FullName)')
    ->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $name]);
echo "Admin '$user' ready.\n";

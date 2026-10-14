<?php
require __DIR__ . '/config/db.php';
try {
    $n = db()->query('SELECT COUNT(*) FROM students')->fetchColumn();
    echo "Environment OK. Database connected; students in DB: $n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Database connection failed: ' . htmlspecialchars($e->getMessage());
}

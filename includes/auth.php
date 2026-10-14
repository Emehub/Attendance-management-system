<?php
require_once __DIR__ . '/../config/db.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
        session_start();
    }
}

function require_login(): void {
    start_session();
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_check(): bool {
    start_session();
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

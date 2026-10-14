<?php
// Copy this file to db.php and fill in your own credentials. db.php is not committed.
// Database connection (XAMPP MariaDB on port 3307)
date_default_timezone_set('Africa/Accra');   // GMT; XAMPP's PHP defaults to Europe/Berlin
const DB_HOST = '127.0.0.1';
const DB_PORT = 3307;
const DB_NAME = 'attendance_db';
const DB_USER = 'attendance_user';
const DB_PASS = 'CHANGE_ME';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

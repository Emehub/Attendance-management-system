# Builds a hosting-ready copy of the project in ..\hosting-package and a zip next to it.
# Usage (from the project folder):  powershell -File tools\build_hosting_package.ps1
$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$out  = Join-Path $root 'hosting-package'
$zip  = Join-Path $root 'attendance-hosting.zip'
$php  = 'C:\xampp\php\php.exe'

if (Test-Path $out) { Remove-Item $out -Recurse -Force }
if (Test-Path $zip) { Remove-Item $zip -Force }
New-Item -ItemType Directory $out | Out-Null

# 1. Application files (no index.php test page, docs, proposal or local tooling)
foreach ($item in 'attendance.php', 'admin', 'includes', 'assets', 'config') {
    Copy-Item (Join-Path $root $item) (Join-Path $out $item) -Recurse
}

# 2. Production config with placeholders and PHP errors hidden
@'
<?php
// Fill in the values from your hosting control panel (MySQL Databases section).
ini_set('display_errors', '0');              // never show PHP errors to visitors
date_default_timezone_set('Africa/Accra');

const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_NAME = 'CHANGE_ME';
const DB_USER = 'CHANGE_ME';
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
'@ | Set-Content (Join-Path $out 'config\db.php') -Encoding UTF8

# Root .htaccess: no directory listings, hide dotfiles
@'
Options -Indexes
<FilesMatch "^\.(?!well-known)">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</FilesMatch>
DirectoryIndex attendance.php
'@ | Set-Content (Join-Path $out '.htaccess') -Encoding UTF8

# 3. Database setup: schema.sql minus the lines shared hosts do not allow
$skip = '^\s*(CREATE DATABASE|USE |CREATE USER|GRANT |FLUSH |-- Application DB user)'
(Get-Content (Join-Path $root 'sql\schema.sql') | Where-Object { $_ -notmatch $skip }) |
    Set-Content (Join-Path $out '1_database_setup.sql') -Encoding UTF8

# 4. Admin account with a random one-time password (change it after first login)
$chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'
$pass  = -join (1..14 | ForEach-Object { $chars[(Get-Random -Maximum $chars.Length)] })
$hash  = & $php -r "echo password_hash('$pass', PASSWORD_DEFAULT);"
"INSERT INTO users (Username, PasswordHash, FullName) VALUES ('admin', '$hash', 'Administrator');" |
    Set-Content (Join-Path $out '2_create_admin.sql') -Encoding UTF8

# 5. Short guide
@"
HOSTING GUIDE
=============
1. In your host's control panel create a MySQL database and a database user, and give the user all privileges on it.
2. Open phpMyAdmin, select that database, and import 1_database_setup.sql, then 2_create_admin.sql (Import tab or SQL tab).
3. Edit config/db.php: set DB_NAME, DB_USER and DB_PASS (and DB_HOST if the host says it is not 'localhost').
4. Upload these folders and files to public_html (or a subfolder): attendance.php, admin/, includes/, assets/, config/, .htaccess.
   Do NOT upload the two .sql files or this guide.
5. Visit:  https://YOUR-SITE/attendance.php        (students)
           https://YOUR-SITE/admin/login.php       (staff)
6. Log in as admin with the one-time password given to you, then use the Password link to change it immediately.
7. Confirm https://YOUR-SITE/config/db.php shows "403 Forbidden" (the config must not be readable from the web).
8. Use sample data only. Add reviewers' test students under Students; delete them afterwards.
"@ | Set-Content (Join-Path $out 'HOSTING_GUIDE.txt') -Encoding UTF8

Compress-Archive -Path (Join-Path $out '*') -DestinationPath $zip -Force
Write-Host "Package: $zip"
Write-Host "One-time admin password (username: admin): $pass"

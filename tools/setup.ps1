# One-command local setup for XAMPP on Windows.
#   1. creates the database and tables (sample data included)
#   2. creates config/db.php with a random database password
#   3. creates the admin account with a random one-time password
# Safe to re-run: it never overwrites an existing config/db.php or admin account.
#
# Usage (PowerShell, from the project folder):
#   powershell -ExecutionPolicy Bypass -File tools\setup.ps1
#   powershell -ExecutionPolicy Bypass -File tools\setup.ps1 -Port 3306      # if MySQL runs on the default port
param(
    [int]$Port = 3307,
    [string]$XamppPath = 'C:\xampp',
    [string]$RootPassword = '',
    [string]$DbName = 'attendance_db',
    [string]$DbUser = 'attendance_user'
)
$ErrorActionPreference = 'Stop'
$root  = Split-Path $PSScriptRoot -Parent
$mysql = Join-Path $XamppPath 'mysql\bin\mysql.exe'
$php   = Join-Path $XamppPath 'php\php.exe'
foreach ($f in $mysql, $php) { if (-not (Test-Path $f)) { throw "Not found: $f. Install XAMPP or pass -XamppPath." } }

$rootArgs = @('-h', '127.0.0.1', '-P', $Port, '-u', 'root')
if ($RootPassword) { $rootArgs += "-p$RootPassword" }

& $mysql @rootArgs -e 'SELECT 1' *> $null
if ($LASTEXITCODE -ne 0) {
    throw "Cannot connect to MySQL/MariaDB on port $Port as root. Start MySQL in the XAMPP Control Panel, check the port (-Port), or pass -RootPassword."
}

function New-Secret([int]$n) {
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'
    -join (1..$n | ForEach-Object { $chars[(Get-Random -Maximum $chars.Length)] })
}

# 1. Database and tables
Write-Host 'Creating database and tables...'
$schema = (Get-Content (Join-Path $root 'sql\schema.sql') -Raw).Replace('attendance_db', $DbName).Replace('attendance_user', $DbUser)
$schema | & $mysql @rootArgs
if ($LASTEXITCODE -ne 0) { throw 'Importing sql\schema.sql failed.' }

# 2. config/db.php
$cfg = Join-Path $root 'config\db.php'
if (Test-Path $cfg) {
    Write-Host 'config\db.php already exists - leaving it unchanged.'
} else {
    $dbPass = New-Secret 20
    & $mysql @rootArgs -e "ALTER USER '$DbUser'@'localhost' IDENTIFIED BY '$dbPass'; FLUSH PRIVILEGES;"
    if ($LASTEXITCODE -ne 0) { throw 'Could not set the database user password.' }
    $c = Get-Content (Join-Path $root 'config\db.example.php') -Raw
    $c = $c.Replace("DB_PORT = 3307", "DB_PORT = $Port").Replace("'attendance_db'", "'$DbName'").Replace("'attendance_user'", "'$DbUser'").Replace("'CHANGE_ME'", "'$dbPass'")
    Set-Content $cfg $c -Encoding UTF8
    Write-Host 'Created config\db.php.'
}

# 3. Admin account (only if no admin exists yet)
$count = & $mysql @rootArgs -N -e "SELECT COUNT(*) FROM $DbName.users"
if ([int]$count -eq 0) {
    $adminPass = New-Secret 14
    & $php (Join-Path $root 'sql\create_admin.php') admin $adminPass 'Administrator' | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'Creating the admin account failed.' }
    Write-Host ''
    Write-Host "Admin login  ->  username: admin   password: $adminPass" -ForegroundColor Green
    Write-Host 'Change this password after logging in (Password link in the header).'
} else {
    Write-Host 'An admin account already exists - not creating another.'
}

$folder = Split-Path $root -Leaf
Write-Host ''
Write-Host 'Setup complete. Make sure Apache and MySQL are running, then open:'
Write-Host "  Staff:    http://localhost/$folder/admin/login.php"
Write-Host "  Students: http://localhost/$folder/attendance.php"
Write-Host '(This assumes the project folder is inside C:\xampp\htdocs.)'

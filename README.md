# Student Attendance Management System

Web-based attendance system for KNUST Institute of Distance Learning (IDL), built with PHP, MySQL/MariaDB and XAMPP. Students mark attendance with their student ID; staff manage students, courses and lecture sessions, and view or export attendance records.

## Requirements

- Windows with [XAMPP](https://www.apachefriends.org/) (PHP 8.2+, Apache, MariaDB). The PHP extensions `pdo_mysql` and `mysqli` must be enabled (they are by default).

## Setup

### 1. Database port

XAMPP's MariaDB defaults to port 3306. This project uses **3307** so it can run next to another MySQL server. Edit `C:\xampp\mysql\bin\my.ini` and set `port=3307` in both the `[client]` and `[mysqld]` sections. If you have no other MySQL server, you can keep 3306 and change `DB_PORT` in `config/db.php` instead.

If you use phpMyAdmin on a non-default port, add this to `C:\xampp\phpMyAdmin\config.inc.php`:

```php
$cfg['Servers'][$i]['port'] = '3307';
```

### 2. Start the servers

Start **Apache** and **MySQL** from the XAMPP Control Panel.

### 3. Create the database config and the database

Copy `config/db.example.php` to `config/db.php` (it is not committed to git) and set `DB_PASS` to match the database user's password. If you change the password in `sql/schema.sql`, change it here too.

From the project folder:

```
C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -P 3307 -u root < sql\schema.sql
```

This creates `attendance_db`, its tables, a few sample students and courses, and a limited database user `attendance_user`. **Change that user's password** (in MySQL and in `config/db.php`) before using the system for anything real.

### 4. Make the site reachable

Either copy the project folder into `C:\xampp\htdocs\student-attendance`, or create a junction so the files stay where they are (run in PowerShell):

```
New-Item -ItemType Junction -Path C:\xampp\htdocs\student-attendance -Target "D:\Projects\Student management system"
```

### 5. Create the admin account

```
C:\xampp\php\php.exe sql\create_admin.php admin "Admin@123" "Administrator"
```

Running it again with the same username resets that account's password. **Change the default password** (`Admin@123`) before demoing or deploying.

### 6. Check it works

Open <http://localhost/student-attendance/>. It should say "Environment OK" and show the number of students.

## Using the system

| Who | Page | URL |
|---|---|---|
| Staff | Login | `/student-attendance/admin/login.php` |
| Students | Mark attendance | `/student-attendance/attendance.php` |

Typical flow:

1. Staff log in and add students (**Students**) and courses (**Courses**).
2. Staff create a lecture session (**Lectures**) and click **Open** on it.
3. Students go to the attendance page, pick the open lecture, enter their student ID and are marked Present. Unknown IDs are rejected and a second attempt for the same lecture is blocked.
4. Staff click **Close** when the lecture ends.
5. Staff use **Reports** to filter attendance by student, course, lecture or date, see who was absent from a lecture, export to CSV, or print.

## Project structure

```
attendance.php          Student-facing attendance page
index.php               Environment / database connection check
admin/                  Staff pages (login, dashboard, students, courses, lectures, reports)
includes/               Shared code (auth, CSRF, layout)
config/db.php           Database connection and timezone settings
assets/css/style.css    Styles
sql/schema.sql          Database schema and sample data
sql/create_admin.php    Command-line tool to create or reset an admin account
```

## Security notes

- Passwords are hashed with `password_hash`; all database queries use prepared statements; forms carry CSRF tokens; admin pages require a login session.
- Only sample data should be used during development. Real student data needs institutional approval.
- Not yet implemented: rate limiting on the attendance page, an in-app password change page, and HTTPS. Add these before any real deployment.

## Troubleshooting

- **MySQL will not start in XAMPP:** another MySQL server is probably using port 3306. Use port 3307 as described above.
- **Times are off by hours:** the timezone is set in `config/db.php` (`Africa/Accra`).
- **"Database connection failed" on the index page:** check that MySQL is running on the port in `config/db.php` and that `sql/schema.sql` was imported.

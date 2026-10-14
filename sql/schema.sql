-- Student Attendance Management System (KNUST IDL)
CREATE DATABASE IF NOT EXISTS attendance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE attendance_db;

CREATE TABLE IF NOT EXISTS users (
  UserID INT AUTO_INCREMENT PRIMARY KEY,
  Username VARCHAR(50) NOT NULL UNIQUE,
  PasswordHash VARCHAR(255) NOT NULL,
  FullName VARCHAR(100) NOT NULL,
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
  StudentID VARCHAR(20) PRIMARY KEY,
  StudentName VARCHAR(100) NOT NULL,
  Programme VARCHAR(100) NOT NULL,
  Level VARCHAR(10) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
  CourseID VARCHAR(20) PRIMARY KEY,
  CourseName VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lectures (
  LectureID INT AUTO_INCREMENT PRIMARY KEY,
  CourseID VARCHAR(20) NOT NULL,
  LectureDate DATE NOT NULL,
  StartTime TIME NOT NULL,
  IsOpen TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (CourseID) REFERENCES courses(CourseID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
  AttendanceID INT AUTO_INCREMENT PRIMARY KEY,
  StudentID VARCHAR(20) NOT NULL,
  LectureID INT NOT NULL,
  AttendanceDate DATE NOT NULL,
  AttendanceTime TIME NOT NULL,
  Status ENUM('Present','Absent') NOT NULL DEFAULT 'Present',
  UNIQUE KEY uq_student_lecture (StudentID, LectureID),
  FOREIGN KEY (StudentID) REFERENCES students(StudentID) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (LectureID) REFERENCES lectures(LectureID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample data only (no real student records)
INSERT IGNORE INTO students VALUES
 ('6400001','Ama Mensah','Diploma in Information Technology','300'),
 ('6400002','Kofi Boateng','Diploma in Information Technology','300'),
 ('6400003','Akosua Owusu','Diploma in Information Technology','200');
INSERT IGNORE INTO courses VALUES
 ('DIT301','Web Development'),('DIT302','Database Systems');

-- Application DB user (password set in config/db.php)
CREATE USER IF NOT EXISTS 'attendance_user'@'localhost' IDENTIFIED BY 'Attend@2026!';
GRANT SELECT, INSERT, UPDATE, DELETE ON attendance_db.* TO 'attendance_user'@'localhost';
FLUSH PRIVILEGES;

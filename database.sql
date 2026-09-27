-- =========================================================
-- QLHT - He thong Quan ly Hoc tap Ca nhan
-- Sinh vien: Le Tuan Kiet - SE223216 - Nganh An ninh mang AI
-- =========================================================

CREATE DATABASE IF NOT EXISTS qlht CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qlht;

-- ---------------------------------------------------------
-- Sinh vien (tai khoan dang nhap - he thong ca nhan)
-- ---------------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    major VARCHAR(150) NOT NULL,
    email VARCHAR(100),
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    streak_days INT DEFAULT 0,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- Mon hoc
-- ---------------------------------------------------------
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(200) NOT NULL,
    credits INT DEFAULT 3,
    semester VARCHAR(20) NOT NULL,
    status ENUM('not_started','ongoing','completed') DEFAULT 'not_started',
    is_online TINYINT(1) DEFAULT 0,
    online_platform VARCHAR(100) DEFAULT NULL,      -- Microsoft Teams, Zoom, Google Meet...
    online_link VARCHAR(255) DEFAULT NULL,
    lecturer VARCHAR(150) DEFAULT NULL,
    color VARCHAR(20) DEFAULT '#8bc53f',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- Cac dau diem thanh phan cua tung mon (Quiz, PE, FE, Assignment...)
-- ---------------------------------------------------------
CREATE TABLE grade_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    weight_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    score DECIMAL(5,2) DEFAULT NULL,
    max_score DECIMAL(5,2) DEFAULT 10,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- Lich hoc - slot 1 den slot 10, keo tha giua cac o (thu x slot)
-- ---------------------------------------------------------
CREATE TABLE schedule_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    day_of_week TINYINT NOT NULL,          -- 2=Thu2 ... 7=Thu7, 8=CN
    slot_number TINYINT NOT NULL,          -- 1 - 10
    room VARCHAR(50) DEFAULT NULL,
    week_type ENUM('all','odd','even') DEFAULT 'all',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_slot (student_id, day_of_week, slot_number)
);

-- ---------------------------------------------------------
-- Diem danh
-- ---------------------------------------------------------
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    session_date DATE NOT NULL,
    slot_number TINYINT NOT NULL,
    status ENUM('present','absent','late','excused') DEFAULT 'present',
    note VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- Don gui tren FAP (don xin nghi, don phuc khao, don bao luu...)
-- ---------------------------------------------------------
CREATE TABLE fap_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    form_type VARCHAR(150) NOT NULL,
    subject_id INT DEFAULT NULL,
    submit_date DATE NOT NULL,
    status ENUM('pending','processing','approved','rejected') DEFAULT 'pending',
    note TEXT,
    file_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
);

-- =========================================================
-- Du lieu mau
-- =========================================================
-- Mat khau mac dinh: 123456  (doi tren trang Profile)
INSERT INTO students (student_code, full_name, major, email, password_hash, streak_days, points)
VALUES ('SE223216', N'Le Tuan Kiet', N'An ninh mang AI', 'kietltse223216@fpt.edu.vn',
        '$2b$12$xNGJn8FgWiUirR6NqliXN.3a/fRxr7ayKekQigjBvwP5Fhx9pGhRO', 18, 34);
-- (hash tren tuong ung voi "123456")

INSERT INTO subjects (student_id, subject_code, subject_name, credits, semester, status, is_online, online_platform, online_link, lecturer, color) VALUES
(1, 'SEC301', N'Bao mat mang', 3, 'Fall2026', 'ongoing', 0, NULL, NULL, N'Nguyen Van A', '#8bc53f'),
(1, 'AIL303', N'Hoc may ung dung', 3, 'Fall2026', 'ongoing', 1, 'Microsoft Teams', 'https://teams.microsoft.com/l/meetup-join/example', N'Tran Thi B', '#4ea1ff'),
(1, 'PRJ301', N'Lap trinh Java', 3, 'Fall2026', 'ongoing', 0, NULL, NULL, N'Le Van C', '#f2a93b'),
(1, 'ETH302', N'Hacking dao duc', 3, 'Fall2026', 'ongoing', 1, 'Zoom', 'https://zoom.us/j/example', N'Pham Thi D', '#e2445c'),
(1, 'MLN111', N'Triet hoc Mac-Lenin', 2, 'Fall2026', 'not_started', 0, NULL, NULL, N'Hoang Van E', '#9b6bd4');

INSERT INTO grade_components (subject_id, component_name, weight_percent, score, max_score) VALUES
(1, 'Quiz', 10, 8.5, 10), (1, 'Assignment', 20, 9.0, 10), (1, 'Practical Exam', 30, NULL, 10), (1, 'Final Exam', 40, NULL, 10),
(2, 'Lab', 20, 7.5, 10), (2, 'Project', 30, 8.0, 10), (2, 'Final Exam', 50, NULL, 10);

INSERT INTO schedule_slots (student_id, subject_id, day_of_week, slot_number, room, week_type) VALUES
(1, 1, 2, 1, N'Phong 301', 'all'),
(1, 2, 2, 3, N'Phong Lab 2', 'all'),
(1, 3, 3, 2, N'Phong 205', 'all'),
(1, 4, 4, 4, N'Online', 'all'),
(1, 1, 5, 1, N'Phong 301', 'all');

INSERT INTO attendance (subject_id, session_date, slot_number, status) VALUES
(1, '2026-09-15', 1, 'present'),
(1, '2026-09-17', 1, 'present'),
(2, '2026-09-16', 3, 'late'),
(3, '2026-09-16', 2, 'absent');

INSERT INTO fap_submissions (student_id, form_type, subject_id, submit_date, status, note) VALUES
(1, N'Don xin nghi hoc', 3, '2026-09-10', 'approved', N'Nghi vi ly do suc khoe'),
(1, N'Don phuc khao diem', 1, '2026-09-05', 'pending', N'Phuc khao diem Assignment');

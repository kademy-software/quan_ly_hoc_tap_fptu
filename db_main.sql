-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 11, 2026 at 05:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `qlht`
--

-- --------------------------------------------------------

--
-- Table structure for table `anm_certs`
--

CREATE TABLE `anm_certs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `cert_name` varchar(200) NOT NULL,
  `target_date` date DEFAULT NULL,
  `exam_fee` varchar(50) DEFAULT NULL,
  `prerequisites` text DEFAULT NULL,
  `status` enum('planning','studying','certified') DEFAULT 'planning',
  `badge_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `anm_labs`
--

CREATE TABLE `anm_labs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `platform` varchar(100) NOT NULL,
  `target_ip` varchar(50) NOT NULL,
  `ports` varchar(255) DEFAULT NULL,
  `active_until` datetime DEFAULT NULL,
  `flags_collected` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `anm_labs`
--

INSERT INTO `anm_labs` (`id`, `student_id`, `platform`, `target_ip`, `ports`, `active_until`, `flags_collected`, `created_at`) VALUES
(3, 1, 'TryHackMe', '10.48.141.208', '', '2026-09-25 18:00:00', 'THM{WGET_WEBSERVER}', '2026-09-25 12:23:17');

-- --------------------------------------------------------

--
-- Table structure for table `anm_payloads`
--

CREATE TABLE `anm_payloads` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `os_target` enum('Linux','Windows','Cross-platform') DEFAULT 'Cross-platform',
  `tool_name` varchar(100) DEFAULT NULL,
  `command_syntax` text NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `anm_payloads`
--

INSERT INTO `anm_payloads` (`id`, `student_id`, `os_target`, `tool_name`, `command_syntax`, `description`) VALUES
(1, 1, 'Linux', 'Bash', 'ls', 'Hiển thị tất cả thư mục'),
(2, 1, 'Cross-platform', 'Github bash', 'git add .\r\ngit commit -m \"Them cac file moi va cap nhat code\"\r\ngit push origin main', 'Push code lên Github'),
(3, 1, 'Linux', 'Bash', '-lh', 'Xem quyền của file / thư mục'),
(4, 1, 'Linux', 'Bash', 'nano filename', 'Tạo file '),
(5, 1, 'Linux', 'Bash', 'Ctrl X', 'Thoát khỏi trình tạo file Nano'),
(6, 1, 'Linux', 'Bash', 'wget <URL>', 'Tải về file từ Internet'),
(7, 1, 'Linux', 'Bash', 'scp SOURCE DESTINATION', 'Chuyển file giữa hai máy: Máy A                         Máy B ┌──────────┐                 ┌──────────┐ │ important│ ──── SSH ─────> │ ubuntu   │ │   .txt   │    SCP          │          │ └──────────┘                 └──────────┘'),
(8, 1, 'Windows', 'Password', 'PJA_XYEA5R', 'Mật khẩu máy'),
(9, 1, 'Linux', 'Bash', 'ps', 'Hiển thị các tiến trình đang chạy');

-- --------------------------------------------------------

--
-- Table structure for table `anm_writeups`
--

CREATE TABLE `anm_writeups` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `category` enum('Recon','Web Exploitation','Privilege Escalation','Forensics','Reverse Engineering','Other') DEFAULT 'Other',
  `title` varchar(255) NOT NULL,
  `content_markdown` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `total_questions` int(11) DEFAULT 0,
  `passing_score` decimal(5,2) DEFAULT 70.00,
  `time_limit_minutes` int(11) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_answers`
--

CREATE TABLE `assessment_answers` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` varchar(500) NOT NULL,
  `answer_order` int(11) NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_attempts`
--

CREATE TABLE `assessment_attempts` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submitted_at` timestamp NULL DEFAULT NULL,
  `total_correct` int(11) DEFAULT 0,
  `total_score` decimal(5,2) DEFAULT 0.00,
  `status` enum('in_progress','submitted','reviewed') DEFAULT 'in_progress'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_questions`
--

CREATE TABLE `assessment_questions` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `question_order` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('single','multiple') DEFAULT 'single'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_responses`
--

CREATE TABLE `assessment_responses` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_answer_id` int(11) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `slot_number` tinyint(4) NOT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `subject_id`, `session_date`, `slot_number`, `status`, `note`) VALUES
(5, 15, '2026-09-10', 1, 'present', ''),
(6, 6, '2026-09-25', 10, 'present', '');

-- --------------------------------------------------------

--
-- Table structure for table `deadlines`
--

CREATE TABLE `deadlines` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fap_sessions`
--

CREATE TABLE `fap_sessions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `subject_code` varchar(20) NOT NULL,
  `room` varchar(50) NOT NULL DEFAULT '',
  `session_date` date NOT NULL,
  `slot_number` tinyint(4) NOT NULL,
  `start_time` varchar(5) DEFAULT NULL,
  `end_time` varchar(5) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fap_sessions`
--

INSERT INTO `fap_sessions` (`id`, `student_id`, `subject_id`, `subject_code`, `room`, `session_date`, `slot_number`, `start_time`, `end_time`, `status`) VALUES
(1, 1, NULL, 'PEN501', '415', '2026-11-02', 1, '07:00', '08:30', ''),
(2, 1, NULL, 'PEN501', '415', '2026-11-03', 1, '07:00', '08:30', ''),
(3, 1, NULL, 'PEN501', '415', '2026-11-04', 1, '07:00', '08:30', ''),
(4, 1, NULL, 'PEN501', '415', '2026-11-05', 1, '07:00', '08:30', ''),
(5, 1, NULL, 'PEN501', '415', '2026-11-06', 1, '07:00', '08:30', ''),
(6, 1, NULL, 'PEN501', '415', '2026-11-02', 2, '08:45', '10:15', ''),
(7, 1, NULL, 'PEN501', '415', '2026-11-03', 2, '08:45', '10:15', ''),
(8, 1, NULL, 'PEN501', '415', '2026-11-04', 2, '08:45', '10:15', ''),
(9, 1, NULL, 'PEN501', '415', '2026-11-05', 2, '08:45', '10:15', ''),
(10, 1, NULL, 'PEN501', '415', '2026-11-06', 2, '08:45', '10:15', ''),
(11, 1, 18, 'VOV114', 'Sân trường 1', '2026-11-04', 5, '14:15', '15:45', ''),
(12, 1, 18, 'VOV114', 'Sân trường 1', '2026-11-07', 5, '14:15', '15:45', ''),
(13, 1, 18, 'VOV114', 'Sân trường 1', '2026-11-04', 6, '16:00', '17:30', ''),
(14, 1, 18, 'VOV114', 'Sân trường 1', '2026-11-07', 6, '16:00', '17:30', '');

-- --------------------------------------------------------

--
-- Table structure for table `fap_submissions`
--

CREATE TABLE `fap_submissions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `form_type` varchar(150) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `submit_date` date NOT NULL,
  `status` enum('pending','processing','approved','rejected') DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fap_submissions`
--

INSERT INTO `fap_submissions` (`id`, `student_id`, `form_type`, `subject_id`, `submit_date`, `status`, `note`, `file_path`, `created_at`) VALUES
(3, 1, 'Đơn khác', NULL, '2026-09-23', 'approved', 'Sau khi xem danh mục chuyển đổi FUNIX và FPT và theo khung chương trình An ninh mạng và An toàn số. Xem xin phép chuyển đổi môn Giới thiệu về Khoa học Máy Tính. Mã số sinh viên Funix: FX13257 Minh chứng chứng nhận môn: https://drive.google.com/file/d/1iHa2pDaIYFk5-iRa8zGyTlbZtKCLe8QF/view?usp=drivesdk Minh chứng bảng điểm: https://drive.google.com/file/d/151NhtvZ5wQy8GxmSJa-tc4GrDi54TSbl/view?usp=drivesdk * Tình trạng: ĐẠT * Tổng điểm: 7,8/10 * Assignment: Asm1 9,70; Asm2 9,60; Asm3 9,10 * Progress Test: Pgt1 8,00; Pgt2 7,00; Pgt3 6,50 * Quiz: Quiz1–Quiz14 10/10 * Exam: 4,00', NULL, '2026-09-23 03:29:06');

-- --------------------------------------------------------

--
-- Table structure for table `fap_sync`
--

CREATE TABLE `fap_sync` (
  `student_id` int(11) NOT NULL,
  `token` char(48) NOT NULL,
  `last_sync_at` datetime DEFAULT NULL,
  `last_count` int(11) NOT NULL DEFAULT 0,
  `last_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fap_sync`
--

INSERT INTO `fap_sync` (`student_id`, `token`, `last_sync_at`, `last_count`, `last_message`) VALUES
(1, '7096292a6252b86dc28e7eaa80aa1ee01dd90c9bbd2762d9', '2026-10-10 16:47:06', 14, 'Thêm 14 buổi, cập nhật 0 buổi');

-- --------------------------------------------------------

--
-- Table structure for table `grade_components`
--

CREATE TABLE `grade_components` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `component_name` varchar(100) NOT NULL,
  `weight_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT 10.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grade_components`
--

INSERT INTO `grade_components` (`id`, `subject_id`, `component_name`, `weight_percent`, `score`, `max_score`) VALUES
(8, 22, 'MIEN_MON', 100.00, 7.80, 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `student_id`, `title`, `description`, `created_at`) VALUES
(1, 1, 'Test1', 'test2', '2026-09-27 06:43:43'),
(2, 1, 'Ôn tập Giáo dục Quốc phòng và An ninh', 'HP1_OTP101', '2026-09-27 06:45:07');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `opt_a` text NOT NULL,
  `opt_b` text NOT NULL,
  `opt_c` text NOT NULL,
  `opt_d` text NOT NULL,
  `correct_opt` char(1) NOT NULL COMMENT 'A, B, C, hoac D',
  `explanation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question_text`, `opt_a`, `opt_b`, `opt_c`, `opt_d`, `correct_opt`, `explanation`) VALUES
(2, 2, 'Đối tượng nghiên cứu của môn Giáo dục quốc phòng và an ninh theo Thông tư số 05/2020/TT-BGDĐT ngày 18/3/2020 của Bộ Giáo dục và Đào tạo?', 'Kỹ thuật chiến đấu bộ binh và chiến thuật', 'Chủ nghĩa yêu nước', 'Phòng tránh địch tiến công hỏa lực bằng vũ khí công nghệ cao', 'Quan điểm logic', 'A', NULL),
(3, 2, 'Một trong những nội dung nghiên cứu về đường lối quốc phòng và an ninh của Đảng Cộng sản Việt Nam?', 'Quan điểm logic', 'Xây dựng nền quốc phòng toàn dân, an ninh nhân dân bảo vệ Tổ quốc Việt Nam xã hội chủ nghĩa', 'Chủ nghĩa yêu nước', 'Phòng tránh địch tiến công hỏa lực bằng vũ khí công nghệ cao', 'B', NULL),
(4, 2, 'Một trong những nội dung nghiên cứu về đường lối quốc phòng và an ninh của Đảng Cộng sản Việt Nam?', 'Quan điểm logic', 'Xây dựng lực lượng vũ trang nhân dân', 'Quân đội nhân dân', 'Những kiến thức cơ bản về đội ngũ trong đơn vị', 'B', NULL),
(5, 2, 'Một trong những nội dung nghiên cứu về đường lối quốc phòng và an ninh của Đảng Cộng sản Việt Nam?', 'Chủ nghĩa yêu nước', 'Kết hợp phát triển kinh tế, xã hội với tăng cường quốc phòng, an ninh và đối ngoại', 'Quân đội nhân dân', 'Từng người trong chiến đấu phòng ngự', 'B', NULL),
(6, 2, 'Một trong những nội dung nghiên cứu về công tác quốc phòng và an ninh?', 'Phòng, chống vi phạm pháp luật về bảo vệ môi trường', 'Phòng, chống vi phạm pháp luật', 'Xây dựng lực lượng Vũ trang Nhân dân Việt Nam', 'Lịch sử truyền thống các quân binh chủng', 'A', NULL),
(7, 2, 'Một trong những nội dung nghiên cứu về công tác quốc phòng và an ninh?', 'An ninh phi truyền thống và các mối đe dọa an ninh phi truyền thống ở Việt Nam', 'Từng người trong chiến đấu', 'Xây dựng lực lượng dân quân tự vệ', 'Từng người trong chiến đấu tiến công', 'A', NULL),
(8, 2, 'Một trong những nội dung nghiên cứu về Quân sự chung?', 'Chế độ sinh hoạt, học tập, công tác trong ngày, trong tuần', 'Xây dựng phong trào toàn dân', 'Xây dựng và bảo vệ chủ quyền biển, đảo', 'Chủ nghĩa Mác - Lênin', 'A', NULL),
(9, 2, 'Một trong những nội dung nghiên cứu về Quân sự chung?', 'Những vấn đề cơ bản về lịch sử', 'Chủ nghĩa Mác - Lênin', 'Hiểu biết chung về các quân, binh chủng trong quân đội', 'Xây dựng và bảo vệ chủ quyền biển, đảo', 'C', NULL),
(10, 2, 'Cơ sở phương pháp luận nghiên cứu môn học Giáo dục quốc phòng và an ninh?', 'Quan điểm hệ thống', 'Chủ nghĩa Mác - Lênin', 'Quan điểm lịch sử', 'Quan điểm khoa học', 'B', NULL),
(11, 2, 'Cơ sở phương pháp luận nghiên cứu môn học Giáo dục quốc phòng và an ninh?', 'Quan điểm khoa học', 'Chủ nghĩa Mác - Lênin', 'Quan điểm lịch sử, logic', 'Quan điểm lịch sử', 'B', NULL),
(12, 2, 'Cơ sở phương pháp luận nghiên cứu môn học Giáo dục quốc phòng và an ninh?', 'Chủ nghĩa Mác - Lênin', 'Quan điểm thực tiễn', 'Quan điểm khoa học', 'Quan điểm lịch sử', 'A', NULL),
(13, 2, 'Chương trình môn Giáo dục quốc phòng và an ninh gồm có mấy học phần?', '3', '4', '5', '6', 'B', NULL),
(14, 2, 'Quan điểm của chủ nghĩa Mác - Lênin về chiến tranh là gì?', 'Chiến tranh là hiện tượng chính trị xã hội có tính lịch sử', 'Chiến tranh là hiện tượng chính trị xã hội', 'Chiến tranh là hiện tượng chính trị', 'Chiến tranh có tính lịch sử', 'A', NULL),
(15, 2, 'Chủ tịch Hồ Chí Minh xác định tính chất xã hội của chiến tranh xâm lược là gì?', 'Chính nghĩa', 'Phi nghĩa', 'Trung lập', 'Đối kháng', 'B', NULL),
(16, 2, 'Chủ tịch Hồ Chí Minh xác định tính chất xã hội của chiến tranh chống xâm lược là gì?', 'Chính nghĩa', 'Phi nghĩa', 'Trung lập', 'Đối kháng', 'A', NULL),
(17, 2, 'Chủ tịch Hồ Chí Minh ra lời kêu gọi toàn quốc kháng chiến vào ngày, tháng, năm nào?', '19/12/1945', '19/12/1946', '19/12/1947', '19/12/1948', 'B', NULL),
(18, 2, 'Quan điểm của chủ nghĩa Mác - Lênin về sức mạnh chiến đấu của quân đội phụ thuộc vào yếu tố nào?', 'Yếu tố chính trị - tinh thần và kỷ luật', 'Số lượng vũ khí', 'Yếu tố quân số', 'Yếu tố cơ cấu biên chế', 'A', NULL),
(19, 2, 'Một trong những nguyên tắc xây dựng quân đội kiểu mới của V.I.Lênin?', 'Đoàn kết với quân đội nước ngoài', 'Đoàn kết thống nhất quân đội với nhân dân', 'Đoàn kết với nhân dân', 'Thống nhất với nhân dân', 'B', NULL),
(20, 2, 'Đội Việt Nam tuyên truyền giải phóng quân được thành lập khi nào?', '22-12-1942', '22-12-1943', '22-12-1944', '22-12-1945', 'C', NULL),
(21, 2, 'Quân đội nhân dân Việt Nam mang bản chất của ai?', 'Giai cấp công nhân', 'Giai cấp nông dân', 'Tầng lớp trí thức', 'Đội ngũ trí thức', 'A', NULL),
(22, 2, 'Các chức năng của Quân đội nhân dân Việt Nam?', 'Chiến đấu, sẵn sàng chiến đấu', 'Chiến đấu, lao động sản xuất', 'Chiến đấu và tham gia giữ gìn hòa bình trong khu vực', 'Chiến đấu, công tác, lao động sản xuất', 'D', NULL),
(23, 2, 'Quân đội nhân dân Việt Nam có mấy chức năng?', '2', '3', '4', '5', 'B', NULL),
(24, 2, 'Quan điểm của chủ nghĩa Mác - Lênin về bảo vệ Tổ quốc xã hội chủ nghĩa?', 'Là một yếu tố chủ quan', 'Chiến đấu, sẵn sàng chiến đấu và làm nhiệm vụ quốc tế', 'Là một tất yếu khách quan', 'Là một tất yếu lịch sử', 'C', NULL),
(25, 2, 'Theo quan điểm của Lênin, ai lãnh đạo mọi mặt sự nghiệp bảo vệ Tổ quốc xã hội chủ nghĩa?', 'Đảng Cộng sản', 'Đảng tư sản', 'Giai cấp công nhân', 'Giai cấp nông dân', 'A', NULL),
(26, 2, 'Một trong những nội dung tư tưởng Hồ Chí Minh về mục tiêu bảo vệ Tổ quốc?', 'Độc lập dân tộc, tiến bộ xã hội', 'Độc lập dân tộc và chủ nghĩa xã hội', 'Độc lập dân tộc và chủ nghĩa cộng sản', 'Độc lập dân tộc, dân chủ và chủ nghĩa xã hội', 'B', NULL),
(27, 2, 'Mục đích của nền quốc phòng toàn dân là gì?', 'Để giữ vững hoà bình, ổn định của đất nước, bảo vệ vững chắc Tổ quốc Việt Nam XHCN', 'Để giữ vững hoà bình, ổn định của đất nước Việt Nam', 'Bảo vệ chủ quyền, thống nhất và toàn vẹn lãnh thổ của đất nước.', 'Bảo vệ vững chắc độc lập, chủ quyền và toàn vẹn lãnh thổ quốc gia dân tộc.', 'D', NULL),
(28, 2, 'Tính chất của nền quốc phòng toàn dân là gì?', 'Vì dân, do dân, của dân', 'Toàn dân, toàn diện, độc lập tự chủ, tự lực tự cường', 'Của nhân dân và các lực lượng vũ trang', 'Của toàn dân', 'B', NULL),
(29, 2, 'Nền quốc phòng toàn dân, an ninh nhân dân là nền quốc phòng, an ninh của ai, do ai tiến hành?', 'là nền quốc phòng, an ninh của đất nước, do mọi người tiến hành', 'là nền quốc phòng, an ninh của dân, do lực lượng vũ trang tiến hành', 'là nền quốc phòng, an ninh của lực lượng vũ trang, do nhân dân tiến hành', 'là nền quốc phòng, an ninh của dân, do toàn thể nhân dân tiến hành', 'D', NULL),
(30, 2, 'Một trong những nội dung xây dựng tiềm lực quốc phòng toàn dân, an ninh nhân dân là gì?', 'Xây dựng tiềm lực chính trị, tinh thần', 'Xây dựng tiềm lực văn hóa - xã hội', 'Xây dựng tiềm lực công nghiệp quốc phòng', 'Xây dựng tiềm lực ngoại giao', 'A', NULL),
(31, 2, 'Một trong những nội dung xây dựng tiềm lực quốc phòng toàn dân, an ninh nhân dân là gì?', 'Xây dựng tiềm lực khoa học, công nghệ', 'Xây dựng tiềm lực ngoại giao, đối ngoại', 'Xây dựng tiềm lực văn hóa, tinh thần', 'Xây dựng tiềm lực công nghiệp hóa, hiện đại hóa', 'A', NULL),
(32, 2, 'Một trong những nội dung xây dựng tiềm lực quốc phòng toàn dân, an ninh nhân dân?', 'Xây dựng tiềm lực quân sự, an ninh', 'Xây dựng tiềm lực công nghiệp', 'Xây dựng tiềm lực ngoại giao', 'Xây dựng tiềm lực văn hóa - xã hội', 'A', NULL),
(33, 2, 'Một trong những đặc trưng nền quốc phòng toàn dân và an ninh nhân dân là gì?', 'Xây dựng tình yêu quê hương đất nước', 'Bồi dưỡng cho cán bộ, công chức và người đứng đầu cơ quan', 'Chăm lo xây dựng khối đại đoàn kết toàn dân', 'Nền quốc phòng toàn dân, an ninh nhân dân được xây dựng toàn diện và từng bước hiện đại', 'D', NULL),
(34, 2, 'Tiềm lực chính trị, tinh thần có vị trí như thế nào?', 'Là một trong những yếu tố cơ bản tạo nên sức mạnh', 'Là yếu tố quyết định hướng đi của các tiềm lực khác', 'Là yếu tố cơ bản tạo nên sức mạnh quốc phòng, an ninh', 'Là nhân tố bảo đảm cho sức mạnh quốc phòng', 'C', NULL),
(35, 2, 'Nội dung xây dựng thế trận quốc phòng toàn dân, an ninh nhân dân là gì?', 'Xây dựng thế trận', 'Bố trí thế trận làng xã', 'Xây dựng thế trận và bố trí thế trận', 'Xây dựng thế trận lòng dân', 'C', NULL),
(36, 2, 'Quốc hội đã thông qua Luật Giáo dục quốc phòng - an ninh khi nào?', '19/6/2013', '19/6/2014', '19/5/2013', '19/5/2014', 'A', NULL),
(37, 2, 'Bố cục Luật Giáo dục quốc phòng và an ninh?', '8 chương 46 điều', '9 chương 47 điều', '8 chương 47 điều', '10 chương 47 điều', 'C', NULL),
(38, 2, 'Chiến tranh nhân dân bảo vệ Tổ quốc là gì?', 'Cuộc chiến tranh toàn dân, toàn diện, lấy lực lượng vũ trang nhân dân làm nòng cốt', 'Cuộc chiến tranh do nhân dân tiến hành', 'Cuộc chiến tranh chính nghĩa chống lại cuộc chiến tranh phi nghĩa', 'Cuộc chiến tranh chống kẻ thù xâm lược', 'A', NULL),
(39, 2, 'Một trong những tính chất của chiến tranh nhân dân Việt Nam bảo vệ Tổ quốc là gì?', 'Là cuộc chiến tranh do nhân dân tiến hành', 'Là cuộc chiến tranh chính nghĩa, tự vệ cách mạng', 'Là cuộc chiến tranh phi nghĩa', 'Là cuộc chiến tranh tự phát, tự vệ cách mạng', 'B', NULL),
(40, 2, 'Một trong những tính chất của chiến tranh nhân dân Việt Nam bảo vệ Tổ quốc?', 'Là cuộc chiến tranh công nghệ cao', 'Là cuộc chiến tranh mang tính hiện đại', 'Là cuộc chiến tranh do nhân dân tiến hành', 'Cuộc chiến tranh vì nhân loại', 'B', NULL),
(41, 2, 'Mặt trận nào có ý nghĩa quyết định nhất trong chiến tranh?', 'Mặt trận kinh tế', 'Mặt trận ngoại giao', 'Mặt trận chính trị', 'Mặt trận quân sự', 'D', NULL),
(42, 2, 'Chiến tranh nhân dân nhằm chống lại hành động xâm lược, phá hoại trên mặt trận nào?', 'Kinh tế', 'Chính trị', 'Văn hóa', 'Mọi mặt', 'D', NULL),
(43, 2, 'Thực hiện toàn dân đánh giặc phải lấy lực lượng nào làm nòng cốt?', 'lực lượng quân đội', 'lực lượng vũ trang ba thứ quân', 'lực lượng công an', 'lực lượng dân quân tự vệ', 'B', NULL),
(44, 2, 'Mặt trận đấu tranh nào dưới đây là hình thức cơ bản, giữ vai trò quyết định trực tiếp tiêu hao, tiêu diệt lực lượng quân sự của địch?', 'Mặt trận quân sự', 'Mặt trận ngoại giao', 'Mặt trận kinh tế', 'Mặt trận tư tưởng, văn hóa', 'A', NULL),
(45, 2, 'Theo quan điểm của Đảng ta, yếu tố nào sau đây quyết định thắng lợi trên chiến trường?', 'Con người và vũ khí trang bị', 'Vũ khí trang bị hiện đại, trình độ huấn luyện và thể lực', 'Vũ khí hiện đại, trình độ ứng dụng khoa học và nghệ thuật quân sự', 'Trình độ tổ chức chỉ huy của cán bộ các cấp', 'A', NULL),
(46, 2, 'Thế trận chiến tranh nhân dân là gì?', 'Sự tổ chức, bố trí lực lượng đánh giặc', 'Sự tổ chức, bố trí lực lượng để tiến hành chiến tranh và hoạt động tác chiến', 'Sự tổ chức, bố trí lực lượng chiến đấu trên chiến trường khắp cả nước', 'Sự tổ chức, bố trí lực lượng phòng thủ đất nước', 'B', NULL),
(47, 2, 'Một trong những nội dung chủ yếu của chiến tranh nhân dân bảo vệ Tổ quốc?', 'Vũ khí hiện đại, trình độ ứng dụng khoa học và nghệ thuật quân sự', 'Chống chiến tranh xâm lược với chống khủng bố', 'Phối hợp chặt chẽ chống quân địch tiến công từ bên ngoài vào và bạo loạn lật đổ từ bên trong', 'Chống bạo loạn với trấn áp bọn phản động trong nước cấu kết với nước ngoài', 'C', NULL),
(48, 2, 'Một trong những nội dung chủ yếu của chiến tranh nhân dân bảo vệ Tổ quốc?', 'Phản đối tất cả các cuộc chiến tranh', 'Tổ chức thế trận chiến tranh nhân dân', 'Cuộc chiến tranh vì chủ nghĩa xã hội', 'Sự tổ chức, bố trí lực lượng đánh giặc', 'B', NULL),
(49, 2, 'Lực lượng vũ trang nhân dân gồm các lực lượng nào?', 'Quân đội nhân dân, Công an nhân dân, Dân quân tự vệ', 'Quân đội nhân dân, Công an nhân dân, Cảnh sát biển', 'Quân đội nhân dân, Bộ đội Biên phòng, Cảnh sát biển', 'Tất cả đều đúng', 'A', NULL),
(50, 2, 'Cảnh sát biển Việt Nam trực thuộc lực lượng nào?', 'Quân đội Nhân dân Việt Nam.', 'Công an Nhân dân Việt Nam.', 'Dân quân tự vệ.', 'An ninh Nhân dân.', 'A', NULL),
(51, 2, 'Ngày truyền thống của lực lượng Dân quân tự vệ là ngày nào?', 'Ngày 28 tháng 3.', 'Ngày 22 tháng 12.', 'Ngày 27 tháng 7.', 'Ngày 27 tháng 3.', 'A', NULL),
(52, 2, 'Hãy chọn quan điểm đúng?', 'Đảng cộng sản Việt Nam độc tôn duy nhất lãnh đạo lực lượng vũ trang nhân dân.', 'Nhà nước Việt Nam độc tôn duy nhất lãnh đạo lực lượng vũ trang nhân dân.', 'Chính phủ Việt Nam độc tôn duy nhất lãnh đạo lực lượng vũ trang nhân dân.', 'Nhân dân Việt Nam độc tôn duy nhất lãnh đạo lực lượng vũ trang nhân dân.', 'A', NULL),
(53, 2, 'Một trong những quan điểm, nguyên tắc cơ bản xây dựng lực lượng vũ trang nhân dân trong thời kỳ mới là gì?', 'Bảo đảm lực lượng vũ trang Nhân dân luôn trong tư thế sẵn sàng chiến đấu và chiến đấu thắng lợi.', 'Bảo đảm lực lượng vũ trang Nhân dân luôn huấn luyện giỏi.', 'Bảo đảm lực lượng vũ trang Nhân dân có sức cơ động cao.', 'Bảo đảm lực lượng vũ trang Nhân dân có sức cơ động cao, sức khỏe tốt.', 'A', NULL),
(54, 2, 'Lực lượng dự bị động viên gồm những thành phần nào?', 'Quân nhân dự bị và phương tiện kỹ thuật dự bị', 'Quân nhân chuyên nghiệp và phương tiện kỹ thuật dự bị', 'Sĩ quan dự bị và phương tiện kỹ thuật dự bị', 'Quân nhân dự bị và phương tiện chiến đấu', 'A', NULL),
(55, 2, '“Vì nước quên thân, vì dân phục vụ” là lý tưởng của lực lượng nào?', 'Quân đội Nhân dân Việt Nam.', 'Công an Nhân dân Việt Nam.', 'Dân quân tự vệ.', 'Dự bị động viên.', 'B', NULL),
(56, 2, 'Một trong những quan điểm, nguyên tắc cơ bản xây dựng lực lượng vũ trang nhân dân trong thời kỳ mới là gì?', 'Liên minh, liên kết để xây dựng lực lượng vũ trang nhân dân.', 'Tự lực, tự cường xây dựng lực lượng vũ trang nhân dân.', 'Tự cấp, tự túc xây dựng lực lượng vũ trang nhân dân.', 'Liên minh để xây dựng lực lượng vũ trang nhân dân.', 'B', NULL),
(57, 2, 'Một trong những biện pháp chủ yếu xây dựng lực lượng vũ trang Nhân dân trong giai đoạn mới là gì?', 'Nhanh chóng giải quyết yêu cầu về vũ khí, trang bị kỹ thuật của lực lượng vũ trang Nhân dân.', 'Từng bước giải quyết yêu cầu về vũ khí, trang bị kỹ thuật của lực lượng vũ trang Nhân dân.', 'Ngay lập tức giải quyết yêu cầu về vũ khí, trang bị kỹ thuật của lực lượng vũ trang Nhân dân.', 'Nhanh chóng giải quyết yêu cầu về trang bị kỹ thuật của lực lượng vũ trang Nhân dân.', 'B', NULL),
(58, 2, 'Có bao nhiêu biện pháp chủ yếu xây dựng lực lượng vũ trang Nhân dân trong giai đoạn mới?', '4', '5', '6', '7', 'B', NULL),
(59, 2, 'Lực lượng vũ trang Nhân dân Việt Nam tuyệt đối trung thành với ai?', 'Tổ quốc, công nhân, nông dân', 'Tổ quốc, Nhân dân', 'Tổ quốc, Nhân dân, Đảng, Nhà nước', 'Tất cả đều sai', 'C', NULL),
(60, 2, 'Quân đội Nhân dân Việt Nam gồm các lực lượng nào?', 'Chiến đấu, xây dựng', 'Chiến đấu, hậu cần', 'Thường trực, Dự bị động viên', 'Sẵn sàng chiến đấu, chiến đấu', 'C', NULL),
(61, 2, 'Ngày truyền thống của Quân đội Nhân dân Việt Nam là ngày nào?', 'Ngày 22 tháng 11.', 'Ngày 02 tháng 12.', 'Ngày 22 tháng 12.', 'Ngày 12 tháng 12.', 'C', NULL),
(62, 2, 'Ngày truyền thống của Công an Nhân dân Việt Nam là ngày nào?', 'Ngày 18 tháng 9.', 'Ngày 18 tháng 8.', 'Ngày 19 tháng 8.', 'Ngày 17 tháng 8.', 'C', NULL),
(63, 2, 'Lực lượng nào được mệnh danh là “Lực lượng vô địch” trong lực lượng vũ trang Nhân dân Việt Nam?', 'Quân đội Nhân dân Việt Nam.', 'Công an Nhân dân Việt Nam.', 'Dân quân tự vệ.', 'Hải quân Nhân dân Việt Nam.', 'C', NULL),
(64, 2, 'Lực lượng nào được mệnh danh là “Bức tường sắt của Tổ quốc”?', 'Công an Nhân dân Việt Nam.', 'Quân đội Nhân dân Việt Nam.', 'Dân quân tự vệ.', 'Hải quân Nhân dân Việt Nam.', 'C', NULL),
(65, 2, 'Nguyên tắc Đảng Cộng sản Việt Nam lãnh đạo lực lượng vũ trang Nhân dân là gì?', '“Trực tiếp, tuyệt đối, về mọi mặt”.', '“Tuyệt đối, trực tiếp về một số mặt”.', '“Tuyệt đối, trực tiếp về mọi mặt”.', '“Tuyệt đối, về mọi mặt”.', 'C', NULL),
(66, 2, 'Một trong những phương hướng xây dựng lực lượng vũ trang Nhân dân trong giai đoạn mới là gì?', 'Dân quân tự vệ được xây dựng rộng rãi.', 'Dân quân tự vệ được xây dựng rộng lớn.', 'Dân quân tự vệ xây dựng rộng khắp.', 'Dân quân tự vệ được xây dựng nhiều nơi.', 'C', NULL),
(67, 2, 'Một trong những biện pháp chủ yếu xây dựng lực lượng vũ trang Nhân dân trong giai đoạn mới là gì?', 'Xây dựng đội ngũ cán bộ lực lượng vũ trang có phẩm chất đạo đức tốt.', 'Xây dựng đội ngũ cán bộ lực lượng vũ trang giỏi về công nghệ thông tin.', 'Xây dựng đội ngũ cán bộ lực lượng vũ trang có phẩm chất năng lực tốt.', 'Xây dựng đội ngũ cán bộ lực lượng vũ trang có tác phong nhanh nhẹn.', 'C', NULL),
(68, 2, 'Chủ thể kết hợp phát triển kinh tế, xã hội với tăng cường, củng cố quốc phòng, an ninh ở nước ta?', 'Nhà nước, nhân dân và quân đội', 'Nhân dân và Quân đội', 'Nhà nước và Nhân dân dưới sự lãnh đạo của Đảng', 'Quân đội và công an', 'C', NULL),
(69, 2, 'Phương thức kết hợp phát triển kinh tế, xã hội với tăng cường, củng cố quốc phòng, an ninh ở nước ta?', 'Sự quản lý điều hành của Nhà nước', 'Một thể thống nhất của từng địa phương trên phạm vi cả nước', 'Sự gắn kết chặt chẽ, tích cực, chủ động tự giác giữa hoạt động kinh tế', 'Là sự gắn kết chặt chẽ, tích cực, chủ động tự giác', 'C', NULL),
(70, 2, 'Một trong những nội dung kinh tế quyết định đến quốc phòng, an ninh nội dung nào?', 'Nguồn gốc ra đời, sức mạnh của quốc phòng, an ninh', 'Quốc phòng, an ninh vững mạnh sẽ tạo môi trường hoà bình', 'Hoạt động quốc phòng, an ninh tiêu tốn đáng kể một phần nguồn nhân lực', 'Bảo đảm ổn định kinh tế vĩ mô, phấn đấu tăng trưởng kinh tế nhanh bền vững', 'A', NULL),
(71, 2, 'Một trong những nội dung kết hợp phát triển kinh tế, xã hội với tăng cường củng cố quốc phòng, an ninh và đối ngoại ở nước ta hiện nay?', 'Kết hợp trong nông, lâm, ngư nghiệp, các vùng kinh tế trọng điểm', 'Kết hợp trong giao thông vận tải, khoa học - công nghệ, giáo dục', 'Kết hợp trong thực hiện nhiệm vụ chiến lược bảo vệ Tổ quốc', 'Kết hợp mọi nguồn lực, mọi lực lượng của cả Trung ương và địa phương', 'C', NULL),
(72, 2, 'Một trong những nội dung kết hợp phát triển kinh tế, xã hội với tăng cường củng cố quốc phòng, an ninh và đối ngoại ở nước ta hiện nay?', 'Kết hợp trong vùng núi biên giới', 'Kết hợp trong hoạt động đối ngoại', 'Kết hợp trong công nghiệp', 'Kết hợp mọi nguồn lực, mọi lực lượng', 'D', NULL),
(73, 2, 'Một trong những nội dung kết hợp phát triển kinh tế, xã hội với tăng cường, củng cố quốc phòng, an ninh và đối ngoại ở nước ta hiện nay trong bưu chính viễn thông thể hiện như thế nào?', 'Phải kết hợp chặt chẽ giữa ngành bưu điện với ngành quân đội, công an, dân quân', 'Thực hiện chuyển giao công nghệ hai chiều, từ công nghiệp quốc phòng vào công nghiệp', 'Xây dựng kế hoạch động viên thông tin liên lạc cho thời chiến', 'Phát triển hệ thống thông tin đồng bộ, hiện đại', 'A', NULL),
(74, 2, 'Một trong những nội dung kết hợp phát triển kinh tế - xã hội với tăng cường, củng cố quốc phòng, an ninh trong lĩnh vực y tế thể hiện như thế nào?', 'Xây dựng kế hoạch động viên y tế dân sự cho quân sự khi có chiến tranh xảy ra', 'Phát triển nông, lâm, ngư nghiệp phải gắn với việc giải quyết tốt các vấn đề xã hội như xoá đói', 'Phải phối hợp chặt chẽ và toàn diện hoạt động giữa các ngành khoa học và công nghệ then chốt', 'Xây dựng kế hoạch động viên thông tin liên lạc cho thời chiến', 'A', NULL),
(75, 2, 'Yếu tố nào tác động đến việc hình thành nghệ thuật quân sự Việt Nam?', 'Kinh tế.', 'Chính trị, văn hoá - xã hội.', 'Địa lý.', 'Cả ba đáp án.', 'D', NULL),
(76, 2, 'Nghệ thuật quân sự Việt Nam được hình thành và phát triển trong quá trình nào dưới đây?', 'Dựng và giữ nước của dân tộc.', 'Kinh nghiệm chiến đấu của các nước.', 'Trong quá trình lãnh đạo của Đảng Cộng sản Việt Nam.', 'Nghệ thuật đánh giặc của Việt Nam và thế giới.', 'A', NULL),
(77, 2, 'Đâu là đặc trưng cơ bản của nghệ thuật quân sự Việt Nam?', 'Nghệ thuật vừa kháng chiến vừa kiến quốc, xây dựng đất nước.', 'Nghệ thuật chiến tranh toàn dân, toàn diện, trường kỳ.', 'Nghệ thuật chiến tranh nhân dân, toàn dân đánh giặc.', 'Nghệ thuật chiến tranh nhân dân, chiến tranh du kích.', 'C', NULL),
(78, 2, 'Cuộc chiến tranh giữ nước đầu tiên của dân tộc ta?', 'Cuộc kháng chiến chống quân Nam Hán.', 'Cuộc kháng chiến chống quân Nguyên Mông.', 'Cuộc kháng chiến chống quân Triệu Đà.', 'Cuộc kháng chiến chống quân Tần.', 'D', NULL),
(79, 2, 'Cuộc khởi nghĩa của Hai Bà Trưng diễn ra vào mùa xuân năm nào?', '38', '39', '40', '41', 'C', NULL),
(80, 2, 'Mặt trận nào có ý nghĩa quyết định nhất trong chiến tranh?', 'Mặt trận kinh tế.', 'Mặt trận ngoại giao.', 'Mặt trận chính trị.', 'Mặt trận quân sự.', 'D', NULL),
(81, 2, '“Mưu” trong nghệ thuật quân sự của ông cha ta là gì?', '“Mưu” là để lừa địch.', '“Mưu” là để điều địch theo ý định của ta.', '“Mưu” là làm cho địch hoang mang, rối loạn phương án tác chiến.', '“Mưu” là mưu phạt công tâm – đánh vào lòng người.', 'C', NULL),
(82, 2, '“Kế” trong nghệ thuật đánh giặc của ông cha ta là gì?', '“Kế” là để lừa địch đánh vào nơi ta đã chuẩn bị trước.', '“Kế” là để điều binh, khiển tướng.', '“Kế” là nghi binh lừa địch.', '“Kế” là để điều địch theo ý định của ta.', 'D', NULL),
(83, 2, 'Nghệ thuật lấy nhỏ đánh lớn, lấy ít địch nhiều, lấy yếu chống mạnh là gì?', 'Lấy “thế” thắng “lực”.', 'Mạnh được, yếu thua.', 'Mai phục, vây thành diệt viện.', 'Thần tốc, táo bạo, bất ngờ.', 'A', NULL),
(84, 2, 'Ba mũi giáp công trong nghệ thuật quân sự Việt Nam từ khi có Đảng lãnh đạo là gì?', 'Quân sự, chính trị, ngoại giao.', 'Quân sự, chính trị, kinh tế.', 'Quân sự, chính trị, binh vận.', 'Quân sự, chính trị, dân vận.', 'C', NULL),
(85, 2, 'Phương châm tác chiến trong Chiến dịch Điện Biên Phủ được Đảng ủy mặt trận Điện Biên Phủ quyết định là gì?', 'Đánh nhanh, thắng nhanh.', 'Đánh chắc, tiến chắc.', 'Đánh vây lấn.', 'Đánh đồng loạt.', 'B', NULL),
(86, 2, 'Chiến dịch Việt Bắc Thu Đông năm 1947 là loại hình chiến dịch gì?', 'Chiến dịch tiến công', 'Chiến dịch phản công', 'Chiến dịch phòng ngự', 'Chiến dịch tiến công tổng hợp', 'B', NULL),
(87, 2, 'Nghệ thuật quân sự Việt Nam từ khi có Đảng lãnh đạo gồm ba bộ phận cơ bản nào hợp thành?', 'Chiến lược quân sự, nghệ thuật vận động hàng binh và chiến thuật.', 'Chiến lược quân sự, kỹ thuật chiến đấu và chiến thuật.', 'Chiến lược quân sự, nghệ thuật chiến dịch và chiến thuật.', 'Chiến lược quân sự, nghệ thuật chiến dịch và chiến tranh toàn diện.', 'C', NULL),
(88, 2, 'Trong kháng chiến chống Pháp, chống Mỹ, Quân đội nhân dân Việt Nam đã tổ chức và thực hành mấy loại hình chiến dịch?', '3', '4', '5', '6', 'C', NULL),
(89, 2, 'Một trong những bài học kinh nghiệm về nghệ thuật quân sự vào sự nghiệp bảo vệ tổ quốc trong thời kỳ mới là gì?', 'Quán triệt tư tưởng tích cực tiến công.', 'Quán triệt tư tưởng tiến công không ngừng.', 'Quán triệt tư tưởng đột phá, đột phá liên tục.', 'Quán triệt tư tưởng bám thắt lưng địch mà đánh.', 'A', NULL),
(90, 2, 'Một trong những bài học kinh nghiệm về nghệ thuật quân sự vào sự nghiệp bảo vệ tổ quốc trong thời kỳ mới là gì?', 'Đánh tiêu hao rộng rãi bằng đánh nhỏ, đánh vừa', 'Nghệ thuật quân sự toàn dân đánh giặc', 'Tận dụng địa hình, tận dụng được yếu tố bí mật, bất ngờ', 'Tạo thế, tạo lực để đánh địch khi thời cơ có lợi nhất', 'B', NULL),
(91, 2, 'Chiến dịch Điện Biên Phủ diễn ra năm nào?', '1951', '1952', '1953', '1954', 'D', NULL),
(92, 2, 'Chiến dịch Hồ Chí Minh diễn ra năm nào?', '1975', '1972', '1973', '1974', 'A', NULL),
(93, 2, 'Chiến dịch Điện Biên Phủ là loại hình chiến dịch gì?', 'Chiến dịch tiến công', 'Chiến dịch phản công', 'Chiến dịch phòng ngự', 'Chiến dịch tiến công tổng hợp', 'A', NULL),
(94, 2, 'Chiến dịch Hồ Chí Minh là loại hình chiến dịch gì?', 'Chiến dịch tiến công', 'Chiến dịch phản công', 'Chiến dịch phòng ngự', 'Chiến dịch tiến công tổng hợp', 'A', NULL),
(95, 2, 'Một trong những cơ sở hình thành nghệ thuật quân sự Việt Nam là gì?', 'Đảng ta chỉ đạo tiến hành chiến tranh nhân dân', 'Chủ nghĩa Mác - Lênin về chiến tranh, quân đội và bảo vệ Tổ quốc', 'Chủ nghĩa Mác - Lênin về kinh tế', 'Cuộc chiến tranh chống quân xâm lược nước ta là chiến tranh cách mạng, chính nghĩa và tự vệ', 'B', NULL),
(96, 2, 'Một trong những cơ sở hình thành nghệ thuật quân sự Việt Nam là gì?', 'Tư tưởng quân sự Hồ Chí Minh', 'Truyền thống đánh giặc', 'Đảng Cộng sản Việt Nam', 'Cuộc khởi nghĩa của Lam Sơn', 'A', NULL),
(97, 2, 'Một trong những nội dung nghệ thuật đánh giặc của ông cha ta là gì?', 'Xuất phát từ lòng yêu nước, từ tính chất tự vệ chính nghĩa của cuộc kháng chiến vệ quốc', 'Nghệ thuật chiến tranh nhân dân, thực hiện toàn dân đánh giặc', 'Nghệ thuật nhân dân đánh giặc bảo vệ đất nước', 'Nghệ thuật lấy nhỏ đánh lớn, lấy ít địch nhiều, lợi dụng sơ hở của địch', 'B', NULL),
(98, 2, 'Một trong những nội dung nghệ thuật đánh giặc của ông cha ta là gì?', 'Nghệ thuật lấy nhỏ đánh lớn, lấy ít địch nhiều, lấy yếu chống mạnh', 'Nghệ thuật chiến tranh toàn diện, thực hiện toàn dân đánh giặc', 'Nghệ thuật chiến tranh toàn dân, nhân dân đánh giặc', 'Nghệ thuật lấy ít địch nhiều, lấy nhỏ đánh lớn, lấy xa đánh gần', 'A', NULL),
(99, 2, 'Một trong những nội dung nghệ thuật đánh giặc của ông cha ta là gì?', 'Nghệ thuật kết hợp đấu tranh giữa các mặt trận quân sự, chính trị, ngoại giao và binh vận', 'Thực hiện toàn dân đánh giặc, tự vệ chính nghĩa của cuộc kháng chiến vệ quốc', 'Nghệ thuật tận dụng yếu tố bí mật, bất ngờ, tiết kiệm, địa hình, địa vật', 'Nghệ thuật lấy nhỏ đánh lớn, lấy ít địch nhiều, lợi dụng sơ hở của địch', 'A', NULL),
(100, 2, 'Bờ biển Việt Nam dài bao nhiêu km?', '3.260 km', '3.620 km', '2.36 km', '2.630 km', 'A', NULL),
(101, 2, 'Lãnh hải có chiều rộng không vượt quá bao nhiêu hải lý tính từ đường cơ sở?', '12', '13', '14', '15', 'A', NULL),
(102, 2, 'Đâu là quần đảo của Việt Nam?', 'Hoàng Sa', 'Khánh Hòa', 'Đảo Lý Sơn', 'An Bang', 'A', NULL),
(103, 2, 'Đâu là quần đảo của Việt Nam?', 'Trường Sa', 'An Thới', 'Sơn Ca', 'An Bang', 'A', NULL),
(104, 2, 'Việt Nam có biên giới quốc gia trên đất liền dài bao nhiêu km?', '4.550 km', '4.450 km', '5.450 km', '6.550 km', 'A', NULL),
(105, 2, 'Bờ biển nước ta nằm ở phía nào của Biển Đông?', 'Phía Tây của Biển Đông', 'Phía Đông của Biển Đông', 'Phía Nam của Biển Đông', 'Phía Bắc của Biển Đông', 'A', NULL),
(106, 2, 'Lãnh hải là gì?', 'Vùng biển có chiều rộng 24 hải lý tính từ đường cơ sở thẳng ra phía biển', 'Vùng biển có chiều rộng 12 hải lý tính từ đường cơ sở thẳng ra phía biển', 'Vùng biển có chiều rộng 12 hải lý tính từ đường cơ sở thông thường ra phía biển', 'Vùng biển có chiều rộng 24 hải lý tính từ đường cơ sở thông thường ra phía biển', 'B', NULL),
(107, 2, 'Hiện nay, Việt Nam đang thực hiện chủ quyền và quản lý bao nhiêu đảo tại quần đảo Trường Sa?', '20', '21', '22', '23', 'B', NULL),
(108, 2, 'Biển Đông rộng khoảng bao nhiêu km2?', '3.448.000', '6.448.000', '4.448.000', '5.448.000', 'A', NULL),
(109, 2, 'Biên giới Việt Nam – Trung Quốc dài bao nhiêu km?', '1.350 km', '1.550 km', '1.450 km', '1.650 km', 'A', NULL),
(110, 2, 'Hiệp ước hòa bình, hữu nghị và hợp tác giữa Việt Nam và Campuchia được ký khi nào?', '18/02/1979', '18/02/1980', '18/02/1981', '18/02/1982', 'A', NULL),
(111, 2, 'Một trong những nội dung bảo vệ chủ quyền biển đảo Việt Nam?', 'Biên giới trên biển của một quốc gia được xác định là ranh giới phía ngoài của lãnh thổ quốc gia', 'Bảo vệ toàn vẹn lãnh thổ của Tổ quốc, sự nghiệp công nghiệp hóa, hiện đại hóa đất nước', 'Biên giới quốc gia của nước Cộng hòa xã hội chủ nghĩa Việt Nam là đường và mặt phẳng thẳng đứng', 'Bảo vệ an ninh, trật tự an toàn xã hội và văn hoá trên biển, đảo và vùng ven biển', 'D', NULL),
(112, 2, 'Việt Nam quản lý quần đảo Trường Sa gồm mấy đảo nổi và đảo chìm?', '10 đảo nổi và 12 đảo chìm', '8 đảo nổi và 12 đảo chìm', '9 đảo nổi và 12 đảo chìm', '10 đảo nổi và 11 đảo chìm', 'C', NULL),
(113, 2, 'Biên giới Việt Nam – Lào dài bao nhiêu km?', '2.067 km', '2.706 km', '2. 607 km', '2.076 km', 'A', NULL),
(114, 2, 'Biên giới Việt Nam – Campuchia dài bao nhiêu km?', '1.137 km', '1.373 km', '1.173 km', '1.731 km', 'A', NULL),
(115, 2, 'Điểm A11 nằm trên đường cơ sở thẳng thuộc hòn đảo nào của Việt Nam?', 'Đảo Cồn Cỏ', 'Hòn Đôi', 'Đảo Lý Sơn', 'Hòn Ông Căn', 'A', NULL),
(116, 2, 'Điểm A1 nằm trên đường cơ sở thẳng thuộc hòn đảo nào của Việt Nam?', 'Hòn Đá Lẻ', 'Hòn Nhạn', 'Hòn Hải', 'Hòn Tài Lớn', 'B', NULL),
(117, 2, 'Lực lượng chuyên trách bảo vệ biên giới quốc gia là?', 'Bộ đội Biên phòng', 'Dân quân tự vệ', 'Bộ đội chủ lực', 'Bộ đội địa phương', 'A', NULL),
(118, 2, 'Theo Luật Dân quân tự vệ năm 2019, một trong những thành phần nào là của lực lượng dân quân tự vệ?', 'Dân quân tự vệ cơ động', 'Dân quân tự vệ biên giới', 'Dân quân tự vệ thôn', 'Dân quân tự vệ hậu cần', 'A', NULL),
(119, 2, 'Ngày truyền thống của Dân quân tự vệ', '28 tháng 3', '29 tháng 3', '28 tháng 4', '29 tháng 4', 'A', NULL),
(120, 2, 'Trong thời bình, thời gian huấn luyện quân sự, giáo dục chính trị, pháp luật hằng năm cho Dân quân thường trực được quy định mấy ngày?', '60', '61', '62', '63', 'A', NULL),
(121, 2, 'Một trong những chức vụ chỉ huy của Ban chỉ huy quân sự cấp xã?', 'Chính trị viên', 'Chủ tịch Ủy ban nhân dân cấp xã', 'Chủ tịch Hội đồng nhân dân cấp xã', 'Bí thư cấp ủy cấp huyện', 'A', NULL),
(122, 2, 'Một trong những chức vụ chỉ huy của Ban chỉ huy quân sự cấp xã?', 'Chính trị viên phó', 'Chủ tịch Hội Phụ nữ cấp xã', 'Thôn đội trưởng kiêm chỉ huy đơn vị dân quân tại chỗ', 'Bí thư cấp ủy cấp xã', 'A', NULL),
(123, 2, 'Lực lượng dự bị động viên gồm những thành phần nào?', 'Quân nhân dự bị và phương tiện kỹ thuật dự bị', 'Quân nhân chuyên nghiệp và phương tiện kỹ thuật dự bị', 'Sĩ quan dự bị và phương tiện kỹ thuật dự bị', 'Quân nhân dự bị và phương tiện chiến đấu', 'A', NULL),
(124, 2, 'Một trong những nguyên tắc xây dựng, huy động lực lượng dự bị động viên?', 'Vững mạnh về chính trị, tư tưởng, tổ chức, có trình độ chiến đấu cao và được quản lý chặt chẽ', 'Đảm bảo số lượng ít, chất lượng cao, có trọng tâm, trọng điểm.', 'Đảm bảo số lượng nhiều, chất lượng thấp, chiến đấu tốt.', 'Đơn vị dự bị động viên phải duy trì đủ quân số quân nhân dự bị, phương tiện kỹ thuật dự bị', 'A', NULL),
(125, 2, 'Một trong những nguyên tắc xây dựng, huy động lực lượng dự bị động viên?', 'Huy động sức mạnh tổng hợp của toàn dân', 'Phát huy sức mạnh tổng hợp trên địa bàn', 'Huy động sức mạnh, vật chất tổng hợp toàn dân tộc', 'Phát huy sức mạnh tổng hợp của cả xã hội', 'A', NULL),
(126, 2, 'Theo Luật Dân quân tự vệ năm 2019, lực lượng dân quân tự vệ gồm có mấy thành phần?', '4', '5', '6', '7', 'B', NULL),
(127, 2, 'Theo Luật Dân quân tự vệ năm 2019, một trong những thành phần nào là của lực lượng dân quân tự vệ?', 'Dân quân tự vệ làng', 'Dân quân tự vệ tại chỗ', 'Dân quân tự vệ kỹ thuật', 'Dân quân tự vệ hậu cần', 'B', NULL),
(128, 2, 'Ban chỉ huy quân sự cấp xã gồm có mấy người?', '3', '4', '5', '6', 'B', NULL),
(129, 2, 'Nguyên tắc tổ chức hoạt động của dân quân tự vệ ?', 'Xây dựng dân quân tự vệ vững mạnh, rộng khắp, mọi nơi', 'Xây dựng dân quân tự vệ vững mạnh, rộng khắp', 'Xây dựng dân quân tự vệ mạnh mẽ, đầy đủ, rộng khắp', 'Xây dựng dân quân tự vệ vững mạnh, chiến đấu tốt', 'B', NULL),
(130, 2, 'Có mấy nguyên tắc xây dựng, huy động lực lượng dự bị động viên?', '5', '6', '7', '8', 'B', NULL),
(131, 2, 'Có mấy nguyên tắc động viên quốc phòng?', '4', '5', '6', '7', 'B', NULL),
(132, 2, 'Lực lượng Dân quân tự vệ có bao nhiêu nhiệm vụ?', '5', '6', '7', '8', 'C', NULL),
(133, 2, 'Lực lượng dự bị động viên được xây dựng để bổ sung cho lực lượng nào?', 'Lực lượng An ninh nhân dân', 'Lực lượng Dân quân tự vệ', 'Lực lượng thường trực của Quân đội nhân dân', 'Lực lượng Công an nhân dân', 'C', NULL),
(134, 2, 'Theo Luật Dân quân tự vệ năm 2019, một trong những thành phần nào là của lực lượng dân quân tự vệ?', 'Dân quân tự vệ kỹ thuật', 'Dân quân tự vệ bán thường trực', 'Dân quân tự vệ thôn', 'Dân quân thường trực', 'D', NULL),
(135, 2, 'Quân nhân dự bị bao gồm những thành phần nào?', 'Sĩ quan dự bị, quân nhân chuyên nghiệp và binh sĩ dự bị', 'Sĩ quan dự bị, quân nhân chuyên nghiệp và hạ sĩ quan dự bị', 'Quân nhân chuyên nghiệp dự bị và binh sĩ dự bị', 'Sĩ quan dự bị, quân nhân chuyên nghiệp dự bị, hạ sĩ quan dự bị và binh sĩ dự bị', 'D', NULL),
(136, 2, 'Một trong những mục đích của phong trào toàn dân bảo vệ an ninh Tổ quốc là gì?', 'Đấu tranh chống tội phạm, ngăn ngừa, đấu tranh đẩy lùi các tệ nạn xã hội', 'Xây dựng điển hình và nhân điển hình tiên tiến ở cơ sở', 'Có khả năng tiến hành các biện pháp nghiệp vụ', 'Góp phần xây dựng và bảo vệ Tổ quốc Việt Nam xã hội chủ nghĩa', 'A', NULL),
(137, 2, 'Một trong các phương pháp xây dựng phong trào toàn dân bảo vệ an ninh Tổ quốc là gì?', 'Vận động nhân dân tích cực tham gia phòng chống tội phạm', 'Xây dựng các tổ chức quần chúng nòng cốt làm nhiệm vụ bảo vệ an ninh Tổ quốc', 'Nắm tình hình và xây dựng kế hoạch phát động phong trào toàn dân bảo vệ an ninh Tổ quốc', 'Xây dựng phong trào toàn dân bảo vệ an ninh Tổ quốc gắn liền với các cuộc vận động', 'C', NULL),
(138, 2, 'Ở các cơ sở xã, phường trong toàn quốc thường có mấy loại hình tổ chức quần chúng làm công tác an ninh trật tự?', '3', '4', '5', '6', 'A', NULL),
(139, 2, 'Có mấy phương pháp xây dựng phong trào toàn dân bảo vệ an ninh Tổ quốc?', '4', '5', '6', '7', 'C', NULL),
(140, 2, 'Tổ chức nào sau đây được chính quyền thành lập', 'Tổ an ninh thôn, bản, làng', 'Đội bảo vệ', 'Đội bảo vệ nhà máy, xí nghiệp.', 'Tổ bảo vệ an ninh, trật tự', 'D', NULL),
(141, 2, 'Phong trào toàn dân bảo vệ an ninh Tổ quốc có mấy đặc điểm?', '1', '2', '3', '4', 'C', NULL),
(142, 2, 'Có bao nhiêu tính chất trong bảo vệ an ninh quốc gia, bảo đảm trật tự, an toàn xã hội?', '4', '5', '6', '7', 'B', NULL),
(143, 2, 'Một trong những tính chất trong bảo vệ an ninh quốc gia, bảo đảm trật tự, an toàn xã hội là gì?', 'Tính gay go, quyết liệt, phức tạp, lâu dài', 'Tính nguy hiểm, mất mát, quyết liệt, chủ động', 'Tính nguy hiểm, mất mát, quyết liệt, phức tạp', 'Tính nguy hiểm, quyết liệt, lâu dài, chủ động', 'A', NULL),
(144, 2, 'Một trong những tính chất trong bảo vệ an ninh quốc gia, bảo đảm trật tự, an toàn xã hội là gì?', 'Tính Nhân dân', 'Tính quốc tế', 'Tính chính trị', 'Tính xã hội', 'A', NULL),
(145, 2, 'Một trong những tính chất trong bảo vệ an ninh quốc gia, bảo đảm trật tự, an toàn xã hội là gì?', 'Tính giai cấp', 'Tính chính trị trực tiếp', 'Tính răn đe', 'Tính pháp luật', 'B', NULL),
(146, 2, 'Một trong những nội dung bảo vệ an ninh quốc gia là gì?', 'Bảo vệ an ninh mạng', 'Bảo vệ an ninh chính trị', 'Bảo vệ an ninh tiền tệ', 'Bảo vệ an ninh biên giới', 'B', NULL),
(147, 2, 'Một trong những nội dung bảo vệ an ninh quốc gia là gì?', 'Bảo vệ an ninh lương thực', 'Bảo vệ an ninh truyền thống', 'Bảo vệ an ninh kinh tế', 'Bảo vệ an ninh khoa học', 'C', NULL),
(148, 2, 'Một trong những nội dung bảo vệ an ninh quốc gia là gì?', 'Bảo vệ an ninh tư tưởng - văn hóa', 'Bảo vệ an ninh năng lượng', 'Bảo vệ an ninh tài chính tiền tệ', 'Bảo vệ an ninh biển đảo', 'A', NULL),
(149, 2, 'Một trong những nội dung bảo vệ an ninh quốc gia là gì?', 'Bảo vệ an ninh thông tin, truyền thông', 'Bảo vệ an ninh trên lĩnh vực quốc phòng, an ninh đối ngoại', 'Bảo vệ an ninh tài chính, kinh tế, thương mại', 'Bảo vệ an ninh biên giới quốc gia', 'B', NULL),
(150, 2, 'Một trong những nội dung bảo vệ an ninh quốc gia là gì?', 'Bảo vệ an ninh bí mật công nghệ', 'Bảo vệ an ninh trên biển, đảo', 'Bảo vệ an ninh bí mật Đảng', 'Bảo vệ an ninh lãnh thổ', 'D', NULL),
(151, 2, 'Một trong những nội dung bảo đảm trật tự, an toàn xã hội là gì?', 'Phòng, chống tội phạm xâm phạm công trình quốc gia', 'Chống tội phạm ma túy', 'Phòng, chống tệ nạn xã hội', 'Phòng, chống tội phạm xâm phạm bí mật quốc gia', 'C', NULL),
(152, 2, 'Một trong những nội dung bảo đảm trật tự, an toàn xã hội là gì?', 'Phòng ngừa tai nạn', 'Phòng, chống tội phạm cờ bạc', 'Chống tội phạm xâm phạm công trình', 'Chống tội phạm xâm phạm an ninh quốc gia', 'A', NULL),
(153, 2, 'Nội dung bảo vệ an ninh kinh tế?', 'Bảo vệ bí mật nhà nước, lợi ích quốc gia dân tộc', 'Bảo vệ đội ngũ cán bộ, trí thức văn nghệ sỹ', 'Bảo vệ phát triển nền kinh tế, các hoạt động thương mại.', 'Đấu tranh làm thất bại âm mưu, hoạt động xâm hại an ninh kinh tế', 'D', NULL),
(154, 2, 'Nội dung bảo vệ an ninh tư tưởng - văn hóa?', 'Bảo vệ bản sắc văn hóa, dân tộc của Đảng', 'Bảo vệ lợi ích văn hóa, của Nhà nước', 'Bảo vệ sự nghiệp văn hóa của quốc gia dân tộc', 'Bảo vệ chủ nghĩa Mác-Lênin, tư tưởng Hồ Chí Minh, tăng cường nghiên cứu, phát triển lý luận để phù hợp với tình hình mới', 'D', NULL),
(155, 2, 'Một trong những quan điểm chỉ đạo trong bảo vệ an ninh quốc gia, bảo đảm trật tự, an toàn xã hội?', 'Quán triệt đường lối độc lập, tự chủ đồng thời chủ động, tích cực hội nhập quốc tế', 'Quán triệt, kiên định mục tiêu độc lập dân tộc, tự chủ gắn liền với chủ nghĩa xã hội', 'Chú trọng cả hai nhiệm vụ “xây” và “chống”, trong đó lấy xây dựng là chính', 'Phát huy sức mạnh tổng hợp của cả hệ thống chính trị, của khối đại đoàn kết toàn dân tộc', 'A', NULL),
(156, 2, 'Chủ thể bảo vệ an ninh quốc gia, bảo đảm trật tự, an toàn xã hội?', 'Đảng Cộng sản Việt Nam', 'Quân đội nhân dân Việt Nam', 'Bảo vệ an ninh Việt Nam', 'Công an nhân dân Việt Nam', 'A', NULL),
(157, 2, 'Một trong những giải pháp bảo vệ an ninh quốc gia, bảo đảm trật tự, an toàn xã hội là gì?', 'Kết hợp chặt chẽ giữa chủ động phòng ngừa với chủ động tiến công', 'Tuân thủ Hiến pháp, pháp luật, bảo đảm lợi ích của Nhà nước', 'Xây dựng Mặt trận Tổ quốc Việt Nam và các tổ chức thành viên vững mạnh', 'Tăng cường công tác bảo vệ an ninh tư tưởng, văn hóa, an ninh thông tin, truyền thông', 'D', NULL),
(158, 2, 'Chủ thể bảo vệ an ninh quốc gia, bảo đảm trật tự, an toàn xã hội?', 'Đảng Cộng sản Việt Nam', 'Quân đội nhân dân Việt Nam', 'Bảo vệ an ninh Việt Nam', 'Công an nhân dân Việt Nam', 'A', NULL),
(159, 2, 'Một trong những giải pháp bảo vệ an ninh quốc gia, bảo đảm trật tự, an toàn xã hội là gì?', 'Kết hợp chặt chẽ giữa chủ động phòng ngừa với chủ động tiến công', 'Tuân thủ Hiến pháp, pháp luật, bảo đảm lợi ích của Nhà nước', 'Xây dựng Mặt trận Tổ quốc Việt Nam và các tổ chức thành viên vững mạnh', 'Tăng cường công tác bảo vệ an ninh tư tưởng, văn hóa, an ninh thông tin, truyền thông', 'D', NULL),
(160, 2, 'Một trong những giải pháp bảo vệ an ninh quốc gia, bảo đảm trật tự, an toàn xã hội là gì?', 'Kiên định mục tiêu độc lập dân tộc gắn liền với chủ nghĩa xã hội', 'Kiên định mục tiêu, nguyên tắc chiến lược, mềm dẻo, linh hoạt về sách lược', 'Tổ chức thực hiện có hiệu quả công tác đảm bảo an sinh xã hội', 'Kết hợp chặt chẽ giữa chủ động phòng ngừa với chủ động tiến công', 'C', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `schedule_slots`
--

CREATE TABLE `schedule_slots` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `slot_number` tinyint(4) NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `week_type` enum('all','odd','even') DEFAULT 'all'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedule_slots`
--

INSERT INTO `schedule_slots` (`id`, `student_id`, `subject_id`, `day_of_week`, `slot_number`, `room`, `week_type`) VALUES
(6, 1, 6, 2, 10, NULL, 'all'),
(7, 1, 6, 3, 10, NULL, 'all'),
(8, 1, 6, 4, 10, NULL, 'all'),
(9, 1, 6, 5, 10, NULL, 'all'),
(10, 1, 6, 6, 10, NULL, 'all'),
(11, 1, 6, 7, 10, NULL, 'all');

-- --------------------------------------------------------

--
-- Table structure for table `self_study_modules`
--

CREATE TABLE `self_study_modules` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `module_link` varchar(255) DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `target_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `self_study_modules`
--

INSERT INTO `self_study_modules` (`id`, `student_id`, `module_name`, `platform`, `module_link`, `status`, `target_date`, `created_at`) VALUES
(5, 1, 'Linux Fundamentals Part 2', 'TryHackMe', 'https://tryhackme.com/paths', 'completed', '2026-09-23', '2026-09-23 04:50:39'),
(6, 1, 'Linux Fundamentals Part 3', 'TryHackMe', 'https://tryhackme.com/paths', 'in_progress', '2026-09-23', '2026-09-23 04:50:39'),
(7, 1, 'Windows Fundamentals 1', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-23', '2026-09-23 04:50:39'),
(8, 1, 'Windows Fundamentals 2', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-24', '2026-09-23 04:50:39'),
(9, 1, 'Windows Fundamentals 3', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-24', '2026-09-23 04:50:39'),
(10, 1, 'Active Directory Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-24', '2026-09-23 04:50:39'),
(11, 1, 'Windows Command Line', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-25', '2026-09-23 04:50:39'),
(12, 1, 'Windows PowerShell', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-25', '2026-09-23 04:50:39'),
(13, 1, 'Linux Shells', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-25', '2026-09-23 04:50:39'),
(14, 1, 'Networking Concepts', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-26', '2026-09-23 04:50:39'),
(15, 1, 'Networking Essentials', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-26', '2026-09-23 04:50:39'),
(16, 1, 'Networking Core Protocols', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-26', '2026-09-23 04:50:39'),
(17, 1, 'Networking Secure Protocols', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-27', '2026-09-23 04:50:39'),
(18, 1, 'Wireshark: The Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-27', '2026-09-23 04:50:39'),
(19, 1, 'Tcpdump: The Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-27', '2026-09-23 04:50:39'),
(20, 1, 'Nmap: The Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-28', '2026-09-23 04:50:39'),
(21, 1, 'Cryptography Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-28', '2026-09-23 04:50:39'),
(22, 1, 'Public Key Cryptography Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-28', '2026-09-23 04:50:39'),
(23, 1, 'Hashing Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-29', '2026-09-23 04:50:39'),
(24, 1, 'John the Ripper: The Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-29', '2026-09-23 04:50:39'),
(25, 1, 'Moniker Link (CVE-2024-21413)', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-29', '2026-09-23 04:50:39'),
(26, 1, 'Metasploit: Introduction', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-30', '2026-09-23 04:50:39'),
(27, 1, 'Metasploit: Exploitation', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-30', '2026-09-23 04:50:39'),
(28, 1, 'Metasploit: Meterpreter', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-09-30', '2026-09-23 04:50:39'),
(29, 1, 'Blue', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-01', '2026-09-23 04:50:39'),
(30, 1, 'Web Application Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-01', '2026-09-23 04:50:39'),
(31, 1, 'JavaScript Essentials', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-01', '2026-09-23 04:50:39'),
(32, 1, 'SQL Fundamentals', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-02', '2026-09-23 04:50:39'),
(33, 1, 'Burp Suite: The Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-02', '2026-09-23 04:50:39'),
(34, 1, 'Hydra', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-02', '2026-09-23 04:50:39'),
(35, 1, 'Gobuster: The Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-03', '2026-09-23 04:50:39'),
(36, 1, 'Shells Overview', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-03', '2026-09-23 04:50:39'),
(37, 1, 'SQLMap: The Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-03', '2026-09-23 04:50:39'),
(38, 1, 'Defensive Security Intro', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-04', '2026-09-23 04:50:39'),
(39, 1, 'SOC Fundamentals', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-04', '2026-09-23 04:50:39'),
(40, 1, 'Digital Forensics Fundamentals', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-04', '2026-09-23 04:50:39'),
(41, 1, 'Incident Response Fundamentals', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-05', '2026-09-23 04:50:39'),
(42, 1, 'Logs Fundamentals', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-05', '2026-09-23 04:50:39'),
(43, 1, 'Introduction to SIEM', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-05', '2026-09-23 04:50:39'),
(44, 1, 'Firewall Fundamentals', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-06', '2026-09-23 04:50:39'),
(45, 1, 'IDS Fundamentals', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-06', '2026-09-23 04:50:39'),
(46, 1, 'Vulnerability Scanner Overview', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-06', '2026-09-23 04:50:39'),
(47, 1, 'CyberChef: The Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-07', '2026-09-23 04:50:39'),
(48, 1, 'CAPA: The Basics', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-07', '2026-09-23 04:50:39'),
(49, 1, 'REMnux: Getting Started', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-07', '2026-09-23 04:50:39'),
(50, 1, 'FlareVM: Arsenal of Tools', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-08', '2026-09-23 04:50:39'),
(51, 1, 'Security Principles', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-08', '2026-09-23 04:50:39'),
(52, 1, 'Careers in Cyber', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-08', '2026-09-23 04:50:39'),
(53, 1, 'Training Impact on Teams', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-09', '2026-09-23 04:50:39'),
(54, 1, 'OWASP Top 10 2025: IAAA Failures', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-09', '2026-09-23 04:50:39'),
(55, 1, 'OWASP Top 10 2025: Application Design Flaws', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-09', '2026-09-23 04:50:39'),
(56, 1, 'OWASP Top 10 2025: Insecure Data Handling', 'TryHackMe', 'https://tryhackme.com/paths', 'pending', '2026-10-10', '2026-09-23 04:50:39');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_code` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `major` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `avatar` varchar(255) DEFAULT NULL,
  `streak_days` int(11) DEFAULT 0,
  `points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_code`, `full_name`, `major`, `email`, `password_hash`, `role`, `avatar`, `streak_days`, `points`, `created_at`) VALUES
(1, 'SE223216', 'KietLTSE223216', 'PC_BCS_CD_22', 'kietltse223216@fpt.edu.vn', '$2b$12$xNGJn8FgWiUirR6NqliXN.3a/fRxr7ayKekQigjBvwP5Fhx9pGhRO', 'admin', NULL, 18, 34, '2026-09-23 02:42:53');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(200) NOT NULL,
  `credits` int(11) DEFAULT 3,
  `semester` varchar(20) NOT NULL,
  `status` enum('not_started','ongoing','completed','exempted') DEFAULT 'not_started',
  `is_online` tinyint(1) DEFAULT 0,
  `online_platform` varchar(100) DEFAULT NULL,
  `online_link` varchar(255) DEFAULT NULL,
  `lecturer` varchar(150) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#8bc53f',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `student_id`, `subject_code`, `subject_name`, `credits`, `semester`, `status`, `is_online`, `online_platform`, `online_link`, `lecturer`, `color`, `created_at`) VALUES
(6, 1, 'THM_CSEC101', 'Cyber Security 101', 0, 'Fall2026', 'ongoing', 1, 'https://tryhackme.com', 'https://tryhackme.com/dashboard', 'THM', '#c63f3f', '2026-09-23 03:20:27'),
(9, 1, 'IELTS', 'Tiếng Anh IELTS ', 0, 'Fall2026', 'ongoing', 1, 'Zoom', '', '', '#8bc53f', '2026-09-23 03:50:28'),
(10, 1, 'ENT001', 'Top Notch Fundamental', 3, 'Term -5', 'exempted', 0, NULL, NULL, NULL, '#9b6bd4', '2026-09-23 04:37:26'),
(11, 1, 'ENT104', 'English 2 (Top Notch 1)', 3, 'Term -4', 'exempted', 0, NULL, NULL, NULL, '#9b6bd4', '2026-09-23 04:37:26'),
(12, 1, 'ENT203', 'English 3 (Top Notch 2)', 3, 'Term -3', 'exempted', 0, NULL, NULL, NULL, '#9b6bd4', '2026-09-23 04:37:26'),
(13, 1, 'ENT303', 'English 4 (Top Notch 3)', 3, 'Term -2', 'exempted', 0, NULL, NULL, NULL, '#9b6bd4', '2026-09-23 04:37:26'),
(14, 1, 'ENT403', 'English 5 (Summit 1)', 3, 'Term -1', 'ongoing', 0, '', '', '', '#8bc53f', '2026-09-23 04:37:26'),
(15, 1, 'OTP101', 'Orientation and General Training Program', 3, 'Term 0', 'completed', 0, NULL, NULL, NULL, '#4ea1ff', '2026-09-23 04:37:26'),
(16, 1, 'TMI101', 'Traditional musical instrument', 3, 'Term 0', 'ongoing', 0, '', '', '', '#f2a93b', '2026-09-23 04:37:26'),
(17, 1, 'ENT503', 'English 6 (Summit 2)', 3, 'Term 0', 'not_started', 0, '', '', '', '#8bc53f', '2026-09-23 04:37:26'),
(18, 1, 'VOV114', 'Vovinam 1', 3, 'Term 0', 'ongoing', 0, '', '', '', '#e2445c', '2026-09-23 04:37:26'),
(19, 1, 'VOV124', 'Vovinam 2', 3, 'Term 0', 'not_started', 0, '', '', '', '#e2445c', '2026-09-23 04:37:26'),
(20, 1, 'VOV134', 'Vovinam 3', 3, 'Term 0', 'not_started', 0, '', '', '', '#e2445c', '2026-09-23 04:37:26'),
(21, 1, 'CEA103', 'Computer Organization and Architecture', 3, 'Term 1', 'exempted', 0, '', '', '', '#4ea1ff', '2026-09-23 04:37:26'),
(22, 1, 'ICS101', 'Introduction to Computer Science', 3, 'Term 1', 'exempted', 0, '', '', '', '#8bc53f', '2026-09-23 04:37:26'),
(23, 1, 'PFP191', 'Programming fundamental with Python', 3, 'Term 1', 'not_started', 0, '', '', '', '#f2a93b', '2026-09-23 04:37:26'),
(24, 1, 'CAL111', 'Calculus I', 3, 'Term 1', 'not_started', 0, '', '', '', '#9b6bd4', '2026-09-23 04:37:26'),
(25, 1, 'MAD102', 'Discrete mathematics', 3, 'Term 1', 'not_started', 0, '', '', '', '#e2445c', '2026-09-23 04:37:26'),
(26, 1, 'MLT201', 'Matrices and Linear Transformations', 3, 'Term 2', 'not_started', 0, NULL, NULL, NULL, '#4ea1ff', '2026-09-23 04:37:26'),
(27, 1, 'OSG203', 'Operating System', 3, 'Term 2', 'not_started', 0, NULL, NULL, NULL, '#8bc53f', '2026-09-23 04:37:26'),
(28, 1, 'NWC204', 'Computer Networking', 3, 'Term 2', 'not_started', 0, NULL, NULL, NULL, '#f2a93b', '2026-09-23 04:37:26'),
(29, 1, 'APO202', 'Advanced Python with OOP', 3, 'Term 2', 'not_started', 0, NULL, NULL, NULL, '#9b6bd4', '2026-09-23 04:37:26'),
(30, 1, 'DBI202', 'Database Systems', 3, 'Term 2', 'not_started', 0, NULL, NULL, NULL, '#e2445c', '2026-09-23 04:37:26'),
(31, 1, 'JPD113', 'Elementary Japanese 1- A1.1', 3, 'Term 3', 'not_started', 0, NULL, NULL, NULL, '#8bc53f', '2026-09-23 04:37:26'),
(32, 1, 'CSD204', 'Data Structures and Algorithm with Python', 3, 'Term 3', 'not_started', 0, NULL, NULL, NULL, '#4ea1ff', '2026-09-23 04:37:26'),
(33, 1, 'PRS201', 'Policy and Risk Management in Information Systems', 3, 'Term 3', 'not_started', 0, NULL, NULL, NULL, '#f2a93b', '2026-09-23 04:37:26'),
(34, 1, 'SPF291', 'Systems Programming', 3, 'Term 3', 'not_started', 0, NULL, NULL, NULL, '#9b6bd4', '2026-09-23 04:37:26'),
(35, 1, 'IPY111', 'Introduction to Probability', 3, 'Term 3', 'not_started', 0, NULL, NULL, NULL, '#e2445c', '2026-09-23 04:37:26'),
(36, 1, 'PWD301', 'Python Web Development', 3, 'Term 4', 'not_started', 0, NULL, NULL, NULL, '#4ea1ff', '2026-09-23 04:37:26'),
(37, 1, 'AIS201', 'Artificial Intelligence for Cybersecurity', 3, 'Term 4', 'not_started', 0, NULL, NULL, NULL, '#8bc53f', '2026-09-23 04:37:26'),
(38, 1, 'CAC301', 'Cryptography and Applied Cryptography', 3, 'Term 4', 'not_started', 0, NULL, NULL, NULL, '#f2a93b', '2026-09-23 04:37:26'),
(39, 1, 'IAM303', 'Malware Analysis and Reverse Engineering', 3, 'Term 4', 'not_started', 0, NULL, NULL, NULL, '#9b6bd4', '2026-09-23 04:37:26'),
(40, 1, 'JPD123', 'Elementary Japanese 1-A1.2', 3, 'Term 4', 'not_started', 0, NULL, NULL, NULL, '#e2445c', '2026-09-23 04:37:26'),
(41, 1, 'BCS_CD_COM*1', 'BCS_CD_COM*1', 3, 'Term 5', 'not_started', 0, NULL, NULL, NULL, '#4ea1ff', '2026-09-23 04:37:26'),
(42, 1, 'CSO301', 'Cybersecurity Operations', 3, 'Term 5', 'not_started', 0, NULL, NULL, NULL, '#8bc53f', '2026-09-23 04:37:26'),
(43, 1, 'FRS302', 'Network Forensics', 3, 'Term 5', 'not_started', 0, NULL, NULL, NULL, '#f2a93b', '2026-09-23 04:37:26'),
(44, 1, 'MAW301', 'Modern Web Application Security', 3, 'Term 5', 'not_started', 0, NULL, NULL, NULL, '#9b6bd4', '2026-09-23 04:37:26'),
(45, 1, 'ITE304', 'Law and Ethics in Digital Technology', 3, 'Term 5', 'not_started', 0, NULL, NULL, NULL, '#e2445c', '2026-09-23 04:37:26'),
(46, 1, 'LCT301c', 'Leadership and Critical Thinking', 3, 'Term 6', 'not_started', 0, NULL, NULL, NULL, '#4ea1ff', '2026-09-23 04:37:26'),
(47, 1, 'OJT202', 'On the job training', 3, 'Term 6', 'not_started', 0, NULL, NULL, NULL, '#8bc53f', '2026-09-23 04:37:26'),
(48, 1, 'SPM401', 'Security Project Management', 3, 'Term 7', 'not_started', 0, NULL, NULL, NULL, '#f2a93b', '2026-09-23 04:37:26'),
(49, 1, 'BCS_CD_COM*2', 'BCS_CD_COM*2', 3, 'Term 7', 'not_started', 0, NULL, NULL, NULL, '#9b6bd4', '2026-09-23 04:37:26'),
(50, 1, 'CLC301c', 'Cloud Computing', 3, 'Term 7', 'not_started', 0, NULL, NULL, NULL, '#e2445c', '2026-09-23 04:37:26'),
(51, 1, 'RMC301', 'Research methods in Communication', 3, 'Term 7', 'not_started', 0, NULL, NULL, NULL, '#4ea1ff', '2026-09-23 04:37:26'),
(52, 1, 'HOD402', 'Ethical Hacking and Offensive Security', 3, 'Term 7', 'not_started', 0, NULL, NULL, NULL, '#8bc53f', '2026-09-23 04:37:26'),
(53, 1, 'EXE101', 'Experiential Entrepreneurship 1', 3, 'Term 7', 'not_started', 0, NULL, NULL, NULL, '#f2a93b', '2026-09-23 04:37:26'),
(54, 1, 'EXE201', 'Experiential Entrepreneurship 2', 3, 'Term 8', 'not_started', 0, NULL, NULL, NULL, '#9b6bd4', '2026-09-23 04:37:26'),
(55, 1, 'BCS_CD_COM*3', 'BCS_CD_COM*3', 3, 'Term 8', 'not_started', 0, NULL, NULL, NULL, '#e2445c', '2026-09-23 04:37:26'),
(56, 1, 'BCS_CD_COM*4', 'BCS_CD_COM*4', 3, 'Term 8', 'not_started', 0, NULL, NULL, NULL, '#4ea1ff', '2026-09-23 04:37:26'),
(57, 1, 'DAA401', 'Design and Secure AI System', 3, 'Term 8', 'not_started', 0, NULL, NULL, NULL, '#8bc53f', '2026-09-23 04:37:26'),
(58, 1, 'MLN111', 'Philosophy of Marxism – Leninism', 3, 'Term 8', 'not_started', 0, NULL, NULL, NULL, '#f2a93b', '2026-09-23 04:37:26'),
(59, 1, 'MLN122', 'Political economics of Marxism – Leninism', 3, 'Term 8', 'not_started', 0, NULL, NULL, NULL, '#9b6bd4', '2026-09-23 04:37:26'),
(60, 1, 'HCM202', 'Ho Chi Minh Ideology', 3, 'Term 9', 'not_started', 0, NULL, NULL, NULL, '#e2445c', '2026-09-23 04:37:26'),
(61, 1, 'MLN131', 'Scientific socialism', 3, 'Term 9', 'not_started', 0, NULL, NULL, NULL, '#4ea1ff', '2026-09-23 04:37:26'),
(62, 1, 'VNR202', 'History of Vietnam Communist Party', 3, 'Term 9', 'not_started', 0, NULL, NULL, NULL, '#8bc53f', '2026-09-23 04:37:26'),
(63, 1, 'CD_GRA_ELE', 'Graduation Elective for Cybersecurity and Digital Safety', 3, 'Term 9', 'not_started', 0, NULL, NULL, NULL, '#f2a93b', '2026-09-23 04:37:26');

-- --------------------------------------------------------

--
-- Table structure for table `writeups`
--

CREATE TABLE `writeups` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `study_date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `font_family` varchar(60) NOT NULL DEFAULT 'Be Vietnam Pro',
  `font_size` tinyint(3) UNSIGNED NOT NULL DEFAULT 16,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `writeup_links`
--

CREATE TABLE `writeup_links` (
  `id` int(11) NOT NULL,
  `writeup_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anm_certs`
--
ALTER TABLE `anm_certs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `anm_labs`
--
ALTER TABLE `anm_labs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `anm_payloads`
--
ALTER TABLE `anm_payloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `anm_writeups`
--
ALTER TABLE `anm_writeups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `assessment_answers`
--
ALTER TABLE `assessment_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`);

--
-- Indexes for table `assessment_responses`
--
ALTER TABLE `assessment_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `selected_answer_id` (`selected_answer_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `deadlines`
--
ALTER TABLE `deadlines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `fap_sessions`
--
ALTER TABLE `fap_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_session` (`student_id`,`session_date`,`slot_number`),
  ADD KEY `idx_date` (`student_id`,`session_date`);

--
-- Indexes for table `fap_submissions`
--
ALTER TABLE `fap_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `fap_sync`
--
ALTER TABLE `fap_sync`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `uq_fap_token` (`token`);

--
-- Indexes for table `grade_components`
--
ALTER TABLE `grade_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `schedule_slots`
--
ALTER TABLE `schedule_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_slot` (`student_id`,`day_of_week`,`slot_number`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `self_study_modules`
--
ALTER TABLE `self_study_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_code` (`student_code`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `writeups`
--
ALTER TABLE `writeups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`,`study_date`);

--
-- Indexes for table `writeup_links`
--
ALTER TABLE `writeup_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `writeup_id` (`writeup_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anm_certs`
--
ALTER TABLE `anm_certs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `anm_labs`
--
ALTER TABLE `anm_labs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `anm_payloads`
--
ALTER TABLE `anm_payloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `anm_writeups`
--
ALTER TABLE `anm_writeups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assessment_answers`
--
ALTER TABLE `assessment_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_responses`
--
ALTER TABLE `assessment_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `deadlines`
--
ALTER TABLE `deadlines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fap_sessions`
--
ALTER TABLE `fap_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `fap_submissions`
--
ALTER TABLE `fap_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `grade_components`
--
ALTER TABLE `grade_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `schedule_slots`
--
ALTER TABLE `schedule_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `self_study_modules`
--
ALTER TABLE `self_study_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `writeups`
--
ALTER TABLE `writeups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `writeup_links`
--
ALTER TABLE `writeup_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `anm_certs`
--
ALTER TABLE `anm_certs`
  ADD CONSTRAINT `anm_certs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `anm_labs`
--
ALTER TABLE `anm_labs`
  ADD CONSTRAINT `anm_labs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `anm_payloads`
--
ALTER TABLE `anm_payloads`
  ADD CONSTRAINT `anm_payloads_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `anm_writeups`
--
ALTER TABLE `anm_writeups`
  ADD CONSTRAINT `anm_writeups_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessment_answers`
--
ALTER TABLE `assessment_answers`
  ADD CONSTRAINT `assessment_answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  ADD CONSTRAINT `assessment_attempts_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_attempts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD CONSTRAINT `assessment_questions_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_responses`
--
ALTER TABLE `assessment_responses`
  ADD CONSTRAINT `assessment_responses_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `assessment_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_responses_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_responses_ibfk_3` FOREIGN KEY (`selected_answer_id`) REFERENCES `assessment_answers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deadlines`
--
ALTER TABLE `deadlines`
  ADD CONSTRAINT `deadlines_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deadlines_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fap_submissions`
--
ALTER TABLE `fap_submissions`
  ADD CONSTRAINT `fap_submissions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fap_submissions_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `grade_components`
--
ALTER TABLE `grade_components`
  ADD CONSTRAINT `grade_components_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule_slots`
--
ALTER TABLE `schedule_slots`
  ADD CONSTRAINT `schedule_slots_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_slots_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `self_study_modules`
--
ALTER TABLE `self_study_modules`
  ADD CONSTRAINT `self_study_modules_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `writeups`
--
ALTER TABLE `writeups`
  ADD CONSTRAINT `writeups_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `writeup_links`
--
ALTER TABLE `writeup_links`
  ADD CONSTRAINT `writeup_links_ibfk_1` FOREIGN KEY (`writeup_id`) REFERENCES `writeups` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

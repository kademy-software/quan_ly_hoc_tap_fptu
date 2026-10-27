-- Kho video: bảng lưu trạng thái "đã xem" (video lấy trực tiếp từ thư mục videos/)
CREATE TABLE IF NOT EXISTS `video_watched` (
  `student_id` int(11) NOT NULL,
  `category` varchar(200) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `watched_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`student_id`,`category`,`filename`),
  CONSTRAINT `video_watched_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

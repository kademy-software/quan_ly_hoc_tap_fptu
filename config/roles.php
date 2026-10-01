<?php
/**
 * Phân quyền: 2 vai trò
 *   - admin   : quản lý người dùng, xem/sửa mọi bài trắc nghiệm
 *   - student : chỉ xem/sửa bài trắc nghiệm do chính mình tạo
 *
 * Dùng: require_once __DIR__ . '/config/roles.php';  (file này tự nạp auth.php)
 * Vai trò được đọc từ CSDL mỗi request nên đổi quyền có hiệu lực ngay.
 */
require_once __DIR__ . '/auth.php';

function currentRole(): string {
    static $role = null;
    if ($role !== null) return $role;
    $sid = $_SESSION['student_id'] ?? null;
    if (!$sid) return $role = 'guest';
    $stmt = getDB()->prepare('SELECT role FROM students WHERE id = ?');
    $stmt->execute([$sid]);
    $r = $stmt->fetchColumn();
    return $role = ($r === 'admin') ? 'admin' : 'student';
}

function isAdmin(): bool {
    return currentRole() === 'admin';
}

/** Chặn trang chỉ dành cho admin */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        exit('403 - Bạn không có quyền truy cập trang này.');
    }
}

/** Admin hoặc chủ sở hữu bài mới được xem/sửa bài trắc nghiệm */
function canEditQuiz(array $quiz): bool {
    if (isAdmin()) return true;
    return isset($_SESSION['student_id']) && (int)$quiz['student_id'] === (int)$_SESSION['student_id'];
}
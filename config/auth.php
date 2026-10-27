<?php
// Ngày bắt đầu học kỳ (YYYY-MM-DD) - dùng cho lịch tuần ở index.php
if (!defined('QLHT_SEMESTER_START')) {
    define('QLHT_SEMESTER_START', '2026-09-07'); // đổi đúng ngày bắt đầu kỳ của bạn
}
require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['student_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function currentStudent(): ?array {
    if (!isLoggedIn()) return null;
    static $cache = null;
    if ($cache === null) {
        $stmt = getDB()->prepare('SELECT * FROM students WHERE id = ?');
        $stmt->execute([$_SESSION['student_id']]);
        $cache = $stmt->fetch() ?: null;
    }
    return $cache;
}

function attemptLogin(string $studentCode, string $password): bool {
    $stmt = getDB()->prepare('SELECT * FROM students WHERE student_code = ?');
    $stmt->execute([$studentCode]);
    $student = $stmt->fetch();
    if ($student && password_verify($password, $student['password_hash'])) {
        $_SESSION['student_id'] = $student['id'];
        return true;
    }
    return false;
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
}

/** Tra JSON va thoat - dung cho cac endpoint trong /api */
function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireLoginApi(): int {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Chua dang nhap'], 401);
    }
    return (int)$_SESSION['student_id'];
}

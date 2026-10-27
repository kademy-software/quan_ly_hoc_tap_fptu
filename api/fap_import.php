<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/fap_sync.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-QLHT-Token');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$db = getDB();
fapEnsureTable($db);
$action = $_GET['action'] ?? 'import';

// ---- Trạng thái (trình duyệt đã đăng nhập gọi để biết Automa xong chưa) ----
if ($action === 'status') {
    if (empty($_SESSION['student_id'])) out(['ok' => false, 'error' => 'Chưa đăng nhập'], 401);
    $row = fapGetRow($db, (int)$_SESSION['student_id']);
    out(['ok' => true, 'last_sync_at' => $row['last_sync_at'], 'last_count' => (int)$row['last_count'], 'message' => $row['last_message']]);
}

// ---- Nhận dữ liệu từ Automa ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['ok' => false, 'error' => 'Method not allowed'], 405);

$token = $_SERVER['HTTP_X_QLHT_TOKEN'] ?? '';
$sid = false;
if ($token !== '') {                                   // cách cũ: webhook + token
    $st = $db->prepare('SELECT student_id FROM fap_sync WHERE token = ?');
    $st->execute([$token]);
    $sid = $st->fetchColumn();
} elseif (!empty($_SESSION['student_id'])) {           // cách mới: trang QLHT đang đăng nhập tự gửi
    $sid = (int)$_SESSION['student_id'];
}
if (!$sid) out(['ok' => false, 'error' => 'Chưa đăng nhập hoặc token không hợp lệ'], 401);
$sid = (int)$sid;

$body = json_decode(file_get_contents('php://input'), true);
$sessions = is_array($body['sessions'] ?? null) ? $body['sessions'] : null;
if ($sessions === null) out(['ok' => false, 'error' => 'Thiếu dữ liệu sessions'], 400);

try {
    $res = fapSaveSessions($db, $sid, $sessions);
    out(array_merge(['ok' => true], $res));
} catch (Throwable $e) {
    out(['ok' => false, 'error' => 'Lỗi lưu dữ liệu: ' . $e->getMessage()], 500);
}
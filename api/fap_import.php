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

// Map mã môn -> id môn (nếu có trong bảng subjects, không bắt buộc)
$subj = [];
$q = $db->prepare('SELECT id, subject_code FROM subjects WHERE student_id = ?');
$q->execute([$sid]);
foreach ($q->fetchAll() as $r) $subj[strtoupper(trim($r['subject_code']))] = (int)$r['id'];

$up = $db->prepare('INSERT INTO fap_sessions
    (student_id, subject_id, subject_code, room, session_date, slot_number, start_time, end_time, status)
    VALUES (?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE subject_id = VALUES(subject_id), subject_code = VALUES(subject_code),
        room = VALUES(room), start_time = VALUES(start_time), end_time = VALUES(end_time), status = VALUES(status)');

$time = fn($v) => preg_match('/^\d{1,2}:\d{2}$/', (string)$v) ? (string)$v : null;
$added = 0; $updated = 0;

$db->beginTransaction();
try {
    foreach ($sessions as $s) {
        $date = (string)($s['date'] ?? '');
        $slot = (int)($s['slot'] ?? 0);
        $code = strtoupper(trim((string)($s['code'] ?? '')));
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) continue;
        if ($slot < 1 || $slot > 12 || $code === '' || strlen($code) > 20) continue;
        if ($date < QLHT_SEMESTER_START) continue;   // chỉ nhận lịch từ ngày bắt đầu học kỳ
        $room   = mb_substr(trim((string)($s['room'] ?? '')), 0, 50);
        $status = in_array($s['status'] ?? '', ['attended', 'absent'], true) ? $s['status'] : '';

        $up->execute([$sid, $subj[$code] ?? null, $code, $room, $date, $slot, $time($s['start'] ?? ''), $time($s['end'] ?? ''), $status]);
        $n = $up->rowCount();           // 1 = thêm mới, 2 = cập nhật, 0 = không đổi
        if ($n === 1) $added++; elseif ($n === 2) $updated++;
    }
    $msg = "Thêm $added buổi, cập nhật $updated buổi";
    $db->prepare('UPDATE fap_sync SET last_sync_at = NOW(), last_count = ?, last_message = ? WHERE student_id = ?')
       ->execute([$added + $updated, $msg, $sid]);
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    out(['ok' => false, 'error' => 'Lỗi lưu dữ liệu'], 500);
}

out(['ok' => true, 'added' => $added, 'updated' => $updated]);
<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$db  = getDB();
$sid = (int)$_SESSION['student_id'];
$in  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? '';

function out(array $d, int $code = 200): void {
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['ok' => false, 'error' => 'Chỉ chấp nhận POST'], 405);

if ($action === 'save') {
    $id    = (int)($in['id'] ?? 0);
    $title = trim($in['title'] ?? '');
    $date  = trim($in['event_date'] ?? '');
    $start = trim($in['start_time'] ?? '');
    $end   = trim($in['end_time'] ?? '');
    $loc   = trim($in['location'] ?? '');
    $note  = trim($in['note'] ?? '');
    $color = trim($in['color'] ?? '#8bc53f');

    if ($title === '' || mb_strlen($title) > 200)            out(['ok' => false, 'error' => 'Tiêu đề không hợp lệ (1-200 ký tự)']);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))          out(['ok' => false, 'error' => 'Ngày không hợp lệ']);
    if ($start !== '' && !preg_match('/^\d{2}:\d{2}$/', $start)) out(['ok' => false, 'error' => 'Giờ bắt đầu không hợp lệ']);
    if ($end   !== '' && !preg_match('/^\d{2}:\d{2}$/', $end))   out(['ok' => false, 'error' => 'Giờ kết thúc không hợp lệ']);
    if ($start !== '' && $end !== '' && $end < $start)        out(['ok' => false, 'error' => 'Giờ kết thúc phải sau giờ bắt đầu']);
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color))           $color = '#8bc53f';

    $start = $start ?: null;
    $end   = $end ?: null;
    $loc   = mb_substr($loc, 0, 150) ?: null;
    $note  = $note ?: null;

    if ($id > 0) {
        $st = $db->prepare('UPDATE personal_events SET title=?, event_date=?, start_time=?, end_time=?, location=?, note=?, color=? WHERE id=? AND student_id=?');
        $st->execute([$title, $date, $start, $end, $loc, $note, $color, $id, $sid]);
        out(['ok' => true, 'message' => 'Đã cập nhật lịch cá nhân']);
    }
    $st = $db->prepare('INSERT INTO personal_events (student_id, title, event_date, start_time, end_time, location, note, color) VALUES (?,?,?,?,?,?,?,?)');
    $st->execute([$sid, $title, $date, $start, $end, $loc, $note, $color]);
    out(['ok' => true, 'message' => 'Đã thêm lịch cá nhân', 'id' => (int)$db->lastInsertId()]);
}

if ($action === 'delete') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) out(['ok' => false, 'error' => 'Thiếu id']);
    $st = $db->prepare('DELETE FROM personal_events WHERE id=? AND student_id=?');
    $st->execute([$id, $sid]);
    out(['ok' => true, 'message' => 'Đã xóa']);
}

out(['ok' => false, 'error' => 'Action không hợp lệ'], 400);
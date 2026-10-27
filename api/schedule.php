<?php
/**
 * API lịch học.
 *  GET  ?week=YYYY-MM-DD  -> dữ liệu 1 tuần (tiết cố định + buổi FAP + lịch cá nhân), ngày bất kỳ trong tuần đều được
 *  GET  (không tham số)   -> danh sách tiết cố định (giữ tương thích bản cũ)
 *  POST   {subject_id, day_of_week, slot_number, room?, week_type?, start_date?, end_date?}
 *  PUT    {schedule_id, ...các trường cần đổi}
 *  DELETE {schedule_id}
 * Quy ước day_of_week: 2 = Thứ 2 ... 7 = Thứ 7, 8 = Chủ nhật.
 * week_type: all = mọi tuần | odd / even = tuần ISO lẻ / chẵn.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/fap_sync.php';
$sid = requireLoginApi();
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

/** '' / null => null, đúng Y-m-d => chính nó, sai => false */
function parseDate($s) {
    if ($s === null || $s === '') return null;
    $d = DateTime::createFromFormat('!Y-m-d', (string)$s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : false;
}

/** Gộp dữ liệu gửi lên với bản ghi cũ (nếu có) và kiểm tra hợp lệ. Trả về [data, lỗi]. */
function normalizeSlot(array $in, array $base = []): array {
    $get = fn($k, $def = null) => array_key_exists($k, $in) ? $in[$k] : ($base[$k] ?? $def);
    $d = [
        'subject_id'  => (int)$get('subject_id', 0),
        'day_of_week' => (int)$get('day_of_week', 0),
        'slot_number' => (int)$get('slot_number', 0),
        'room'        => trim((string)$get('room', '')),
        'week_type'   => (string)$get('week_type', 'all'),
        'start_date'  => parseDate($get('start_date')),
        'end_date'    => parseDate($get('end_date')),
    ];
    if ($d['subject_id'] < 1) return [null, 'Chưa chọn môn học'];
    if ($d['day_of_week'] < 2 || $d['day_of_week'] > 8) return [null, 'Thứ không hợp lệ (2–8, 8 = Chủ nhật)'];
    if ($d['slot_number'] < 1 || $d['slot_number'] > 12) return [null, 'Slot phải từ 1 đến 12'];
    if (!in_array($d['week_type'], ['all', 'odd', 'even'], true)) return [null, 'Kiểu tuần không hợp lệ'];
    if ($d['start_date'] === false || $d['end_date'] === false) return [null, 'Ngày không hợp lệ'];
    if ($d['start_date'] && $d['end_date'] && $d['end_date'] < $d['start_date']) return [null, 'Ngày kết thúc phải sau ngày bắt đầu'];
    if (mb_strlen($d['room']) > 50) return [null, 'Phòng tối đa 50 ký tự'];
    $d['room'] = $d['room'] === '' ? null : $d['room'];
    return [$d, null];
}

/** Xóa các tiết cũ trùng ô (cùng thứ + slot + trùng kiểu tuần + trùng khoảng ngày) => hành vi "ghi đè". */
function clearConflicts(PDO $db, int $sid, array $d, int $exceptId = 0): int {
    $st = $db->prepare('SELECT id, week_type, start_date, end_date FROM schedule_slots WHERE student_id=? AND day_of_week=? AND slot_number=? AND id<>?');
    $st->execute([$sid, $d['day_of_week'], $d['slot_number'], $exceptId]);
    $del = $db->prepare('DELETE FROM schedule_slots WHERE id=? AND student_id=?');
    $n = 0;
    foreach ($st->fetchAll() as $o) {
        $weekClash = $o['week_type'] === 'all' || $d['week_type'] === 'all' || $o['week_type'] === $d['week_type'];
        $rangeClash = ($o['start_date'] === null || $d['end_date'] === null || $o['start_date'] <= $d['end_date'])
                   && ($o['end_date'] === null || $d['start_date'] === null || $d['start_date'] <= $o['end_date']);
        if ($weekClash && $rangeClash) { $del->execute([$o['id'], $sid]); $n++; }
    }
    return $n;
}

function ownsSubject(PDO $db, int $sid, int $subjectId): bool {
    $c = $db->prepare('SELECT id FROM subjects WHERE id=? AND student_id=?');
    $c->execute([$subjectId, $sid]);
    return (bool)$c->fetch();
}

/* ───────────── GET ───────────── */
if ($method === 'GET' && isset($_GET['week'])) {
    $anchor = parseDate($_GET['week']) ?: date('Y-m-d');
    $data = getWeekScheduleData($db, $sid, $anchor);
    jsonResponse([
        'week_start' => $data['from'],
        'week_end'   => $data['to'],
        'week_no'    => $data['week_no'],
        'items'      => $data['items']
    ]);
}

if ($method === 'GET') {   // tương thích bản cũ
    $stmt = $db->prepare('SELECT ss.*, s.subject_code, s.subject_name, s.color FROM schedule_slots ss JOIN subjects s ON s.id = ss.subject_id WHERE ss.student_id = ?');
    $stmt->execute([$sid]);
    jsonResponse($stmt->fetchAll());
}

/* ───────────── POST: thêm tiết cố định ───────────── */
if ($method === 'POST') {
    [$d, $err] = normalizeSlot($input);
    if ($err) jsonResponse(['error' => $err], 422);
    if (!ownsSubject($db, $sid, $d['subject_id'])) jsonResponse(['error' => 'Không có quyền với môn học này'], 403);

    $replaced = clearConflicts($db, $sid, $d);
    $db->prepare('INSERT INTO schedule_slots (student_id, subject_id, day_of_week, slot_number, room, week_type, start_date, end_date) VALUES (?,?,?,?,?,?,?,?)')
       ->execute([$sid, $d['subject_id'], $d['day_of_week'], $d['slot_number'], $d['room'], $d['week_type'], $d['start_date'], $d['end_date']]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId(), 'replaced' => $replaced]);
}

/* ───────────── PUT: sửa / di chuyển ───────────── */
if ($method === 'PUT') {
    $id = (int)($input['schedule_id'] ?? 0);
    $st = $db->prepare('SELECT * FROM schedule_slots WHERE id=? AND student_id=?');
    $st->execute([$id, $sid]);
    $existing = $st->fetch();
    if (!$existing) jsonResponse(['error' => 'Không tìm thấy'], 404);

    [$d, $err] = normalizeSlot($input, $existing);
    if ($err) jsonResponse(['error' => $err], 422);
    if (!ownsSubject($db, $sid, $d['subject_id'])) jsonResponse(['error' => 'Không có quyền với môn học này'], 403);

    $replaced = clearConflicts($db, $sid, $d, $id);
    $db->prepare('UPDATE schedule_slots SET subject_id=?, day_of_week=?, slot_number=?, room=?, week_type=?, start_date=?, end_date=? WHERE id=? AND student_id=?')
       ->execute([$d['subject_id'], $d['day_of_week'], $d['slot_number'], $d['room'], $d['week_type'], $d['start_date'], $d['end_date'], $id, $sid]);
    jsonResponse(['success' => true, 'replaced' => $replaced]);
}

/* ───────────── DELETE ───────────── */
if ($method === 'DELETE') {
    $id = (int)($input['schedule_id'] ?? $_GET['schedule_id'] ?? 0);
    $db->prepare('DELETE FROM schedule_slots WHERE id=? AND student_id=?')->execute([$id, $sid]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
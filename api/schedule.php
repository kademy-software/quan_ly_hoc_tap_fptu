<?php
require_once __DIR__ . '/../config/auth.php';
$sid = requireLoginApi();
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT ss.*, s.subject_code, s.subject_name, s.color FROM schedule_slots ss JOIN subjects s ON s.id = ss.subject_id WHERE ss.student_id = ?');
    $stmt->execute([$sid]);
    jsonResponse($stmt->fetchAll());
}

// Dat mon hoc moi vao 1 o slot (keo tu subject bank)
if ($method === 'POST') {
    $subjectId = (int)$input['subject_id'];
    $day = (int)$input['day_of_week'];
    $slot = (int)$input['slot_number'];

    $check = $db->prepare('SELECT id FROM subjects WHERE id=? AND student_id=?');
    $check->execute([$subjectId, $sid]);
    if (!$check->fetch()) jsonResponse(['error' => 'Không có quyền với môn học này'], 403);

    // Xoa o cu neu da co mon khac chiem cho (ghi de)
    $del = $db->prepare('DELETE FROM schedule_slots WHERE student_id=? AND day_of_week=? AND slot_number=?');
    $del->execute([$sid, $day, $slot]);

    $stmt = $db->prepare('INSERT INTO schedule_slots (student_id, subject_id, day_of_week, slot_number, room) VALUES (?,?,?,?,?)');
    $stmt->execute([$sid, $subjectId, $day, $slot, $input['room'] ?? null]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

// Di chuyen 1 o da xep sang vi tri khac, hoac cap nhat phong
if ($method === 'PUT') {
    $scheduleId = (int)$input['schedule_id'];
    $check = $db->prepare('SELECT * FROM schedule_slots WHERE id=? AND student_id=?');
    $check->execute([$scheduleId, $sid]);
    $existing = $check->fetch();
    if (!$existing) jsonResponse(['error' => 'Không tìm thấy'], 404);

    $day = isset($input['day_of_week']) ? (int)$input['day_of_week'] : $existing['day_of_week'];
    $slot = isset($input['slot_number']) ? (int)$input['slot_number'] : $existing['slot_number'];
    $room = array_key_exists('room', $input) ? $input['room'] : $existing['room'];

    // Neu vi tri dich da bi chiem boi 1 o khac, xoa o do truoc (ghi de)
    $del = $db->prepare('DELETE FROM schedule_slots WHERE student_id=? AND day_of_week=? AND slot_number=? AND id != ?');
    $del->execute([$sid, $day, $slot, $scheduleId]);

    $stmt = $db->prepare('UPDATE schedule_slots SET day_of_week=?, slot_number=?, room=? WHERE id=? AND student_id=?');
    $stmt->execute([$day, $slot, $room, $scheduleId, $sid]);
    jsonResponse(['success' => true]);
}

if ($method === 'DELETE') {
    $scheduleId = (int)($input['schedule_id'] ?? $_GET['schedule_id'] ?? 0);
    $stmt = $db->prepare('DELETE FROM schedule_slots WHERE id=? AND student_id=?');
    $stmt->execute([$scheduleId, $sid]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);

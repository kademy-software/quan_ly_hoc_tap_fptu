<?php
require_once __DIR__ . '/../config/auth.php';
$sid = requireLoginApi();
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT a.* FROM attendance a JOIN subjects s ON s.id=a.subject_id WHERE s.student_id=? ORDER BY a.session_date DESC');
    $stmt->execute([$sid]);
    jsonResponse($stmt->fetchAll());
}

if ($method === 'POST') {
    $subjectId = (int)$input['subject_id'];
    $check = $db->prepare('SELECT id FROM subjects WHERE id=? AND student_id=?');
    $check->execute([$subjectId, $sid]);
    if (!$check->fetch()) jsonResponse(['error' => 'Không có quyền'], 403);

    $stmt = $db->prepare('INSERT INTO attendance (subject_id, session_date, slot_number, status, note) VALUES (?,?,?,?,?)');
    $stmt->execute([$subjectId, $input['session_date'], (int)$input['slot_number'], $input['status'] ?? 'present', $input['note'] ?? null]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    $stmt = $db->prepare('DELETE a FROM attendance a JOIN subjects s ON s.id=a.subject_id WHERE a.id=? AND s.student_id=?');
    $stmt->execute([$id, $sid]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);

<?php
require_once __DIR__ . '/../config/auth.php';
$sid = requireLoginApi();
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

function ownsSubject(PDO $db, int $subjectId, int $sid): bool {
    $stmt = $db->prepare('SELECT id FROM subjects WHERE id=? AND student_id=?');
    $stmt->execute([$subjectId, $sid]);
    return (bool)$stmt->fetch();
}

if ($method === 'POST') {
    $subjectId = (int)$input['subject_id'];
    if (!ownsSubject($db, $subjectId, $sid)) jsonResponse(['error' => 'Không có quyền'], 403);
    $stmt = $db->prepare('INSERT INTO grade_components (subject_id, component_name, weight_percent, max_score) VALUES (?,?,?,?)');
    $stmt->execute([$subjectId, $input['component_name'], $input['weight_percent'], $input['max_score'] ?? 10]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

if ($method === 'PUT') {
    // Cap nhat diem cho 1 dau diem
    $compId = (int)$input['id'];
    $check = $db->prepare('SELECT gc.id FROM grade_components gc JOIN subjects s ON s.id=gc.subject_id WHERE gc.id=? AND s.student_id=?');
    $check->execute([$compId, $sid]);
    if (!$check->fetch()) jsonResponse(['error' => 'Không có quyền'], 403);
    $stmt = $db->prepare('UPDATE grade_components SET score=? WHERE id=?');
    $stmt->execute([$input['score'] === '' ? null : $input['score'], $compId]);
    jsonResponse(['success' => true]);
}

if ($method === 'DELETE') {
    $compId = (int)($input['id'] ?? $_GET['id'] ?? 0);
    $stmt = $db->prepare('DELETE gc FROM grade_components gc JOIN subjects s ON s.id=gc.subject_id WHERE gc.id=? AND s.student_id=?');
    $stmt->execute([$compId, $sid]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);

<?php
require_once __DIR__ . '/../config/auth.php';
$sid = requireLoginApi();
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT * FROM subjects WHERE student_id = ? ORDER BY semester DESC, subject_name');
    $stmt->execute([$sid]);
    jsonResponse($stmt->fetchAll());
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($method === 'POST') {
    $stmt = $db->prepare('INSERT INTO subjects (student_id, subject_code, subject_name, credits, semester, status, is_online, online_platform, online_link, lecturer, color)
                           VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $sid,
        $input['subject_code'],
        $input['subject_name'],
        (int)($input['credits'] ?? 3),
        $input['semester'],
        $input['status'] ?? 'not_started',
        !empty($input['is_online']) ? 1 : 0,
        $input['online_platform'] ?? null,
        $input['online_link'] ?? null,
        $input['lecturer'] ?? null,
        $input['color'] ?? '#8bc53f',
    ]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

if ($method === 'PUT') {
    $id = (int)$input['id'];
    $stmt = $db->prepare('UPDATE subjects SET subject_code=?, subject_name=?, credits=?, semester=?, status=?, is_online=?, online_platform=?, online_link=?, lecturer=?, color=?
                           WHERE id=? AND student_id=?');
    $stmt->execute([
        $input['subject_code'],
        $input['subject_name'],
        (int)($input['credits'] ?? 3),
        $input['semester'],
        $input['status'] ?? 'not_started',
        !empty($input['is_online']) ? 1 : 0,
        $input['online_platform'] ?? null,
        $input['online_link'] ?? null,
        $input['lecturer'] ?? null,
        $input['color'] ?? '#8bc53f',
        $id, $sid,
    ]);
    jsonResponse(['success' => true]);
}

if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    $stmt = $db->prepare('DELETE FROM subjects WHERE id=? AND student_id=?');
    $stmt->execute([$id, $sid]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);

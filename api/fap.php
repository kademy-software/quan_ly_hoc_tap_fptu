<?php
require_once __DIR__ . '/../config/auth.php';
$sid = requireLoginApi();
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT * FROM fap_submissions WHERE student_id = ? ORDER BY submit_date DESC');
    $stmt->execute([$sid]);
    jsonResponse($stmt->fetchAll());
}

if ($method === 'POST') {
    $stmt = $db->prepare('INSERT INTO fap_submissions (student_id, form_type, subject_id, submit_date, status, note) VALUES (?,?,?,?,?,?)');
    $stmt->execute([
        $sid,
        $input['form_type'],
        $input['subject_id'] ?: null,
        $input['submit_date'],
        'pending',
        $input['note'] ?? null,
    ]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

if ($method === 'PUT') {
    $id = (int)$input['id'];
    $stmt = $db->prepare('UPDATE fap_submissions SET status=? WHERE id=? AND student_id=?');
    $stmt->execute([$input['status'], $id, $sid]);
    jsonResponse(['success' => true]);
}

if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    $stmt = $db->prepare('DELETE FROM fap_submissions WHERE id=? AND student_id=?');
    $stmt->execute([$id, $sid]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);

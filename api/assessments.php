<?php
require_once __DIR__ . '/../config/auth.php';
$sid = requireLoginApi();
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($method === 'POST' && isset($input['action']) && $input['action'] === 'create') {
    // Tao bai thi moi
    $stmt = $db->prepare('INSERT INTO assessments (student_id, subject_id, title, description, passing_score, time_limit_minutes) VALUES (?,?,?,?,?,?)');
    $stmt->execute([
        $sid,
        $input['subject_id'] ?: null,
        $input['title'],
        $input['description'] ?? null,
        $input['passing_score'] ?? 70,
        $input['time_limit_minutes'] ?: null,
    ]);
    $assessmentId = $db->lastInsertId();
    
    // Neu co questions trong import JSON
    if (!empty($input['questions'])) {
        $qOrder = 1;
        foreach ($input['questions'] as $q) {
            $qStmt = $db->prepare('INSERT INTO assessment_questions (assessment_id, question_order, question_text) VALUES (?,?,?)');
            $qStmt->execute([$assessmentId, $qOrder, $q['text']]);
            $qId = $db->lastInsertId();
            
            $aOrder = 1;
            foreach ($q['answers'] as $a) {
                $aStmt = $db->prepare('INSERT INTO assessment_answers (question_id, answer_order, answer_text, is_correct) VALUES (?,?,?,?)');
                $aStmt->execute([$qId, $aOrder, $a['text'], $a['correct'] ? 1 : 0]);
                $aOrder++;
            }
            $qOrder++;
        }
        $db->prepare('UPDATE assessments SET total_questions=? WHERE id=?')->execute([count($input['questions']), $assessmentId]);
    }
    
    jsonResponse(['success' => true, 'id' => $assessmentId]);
}

if ($method === 'POST' && isset($input['action']) && $input['action'] === 'import_json') {
    // Import bai thi tu JSON
    $jsonStr = $input['json'] ?? '';
    try {
        $data = json_decode($jsonStr, true);
        if (!$data || !isset($data['title']) || !isset($data['questions'])) {
            jsonResponse(['error' => 'Format JSON khong dung'], 400);
        }
        
        $stmt = $db->prepare('INSERT INTO assessments (student_id, subject_id, title, description, total_questions, passing_score) VALUES (?,?,?,?,?,?)');
        $stmt->execute([
            $sid,
            $data['subject_id'] ?? null,
            $data['title'],
            $data['description'] ?? null,
            count($data['questions']),
            $data['passing_score'] ?? 70,
        ]);
        $assessmentId = $db->lastInsertId();
        
        $qOrder = 1;
        foreach ($data['questions'] as $q) {
            $qStmt = $db->prepare('INSERT INTO assessment_questions (assessment_id, question_order, question_text) VALUES (?,?,?)');
            $qStmt->execute([$assessmentId, $qOrder, $q['text']]);
            $qId = $db->lastInsertId();
            
            $aOrder = 1;
            foreach ($q['answers'] as $a) {
                $aStmt = $db->prepare('INSERT INTO assessment_answers (question_id, answer_order, answer_text, is_correct) VALUES (?,?,?,?)');
                $aStmt->execute([$qId, $aOrder, $a['text'], $a['correct'] ? 1 : 0]);
                $aOrder++;
            }
            $qOrder++;
        }
        
        jsonResponse(['success' => true, 'id' => $assessmentId]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Loi import JSON: ' . $e->getMessage()], 400);
    }
}

if ($method === 'POST' && isset($input['action']) && $input['action'] === 'save_response') {
    // Luu cau tra loi
    $attemptId = (int)$input['attempt_id'];
    $questionId = (int)$input['question_id'];
    $answerId = $input['answer_id'] ?: null;
    
    // Kiem tra attempt co thuoc sinh vien
    $check = $db->prepare('SELECT a.id FROM assessment_attempts a WHERE a.id=? AND a.student_id=?');
    $check->execute([$attemptId, $sid]);
    if (!$check->fetch()) jsonResponse(['error' => 'Khong co quyen'], 403);
    
    // Xoa cau tra loi cu neu co
    $db->prepare('DELETE FROM assessment_responses WHERE attempt_id=? AND question_id=?')->execute([$attemptId, $questionId]);
    
    // Kiem tra xem dap an dung khong
    $isCorrect = 0;
    if ($answerId) {
        $checkAns = $db->prepare('SELECT is_correct FROM assessment_answers WHERE id=?');
        $checkAns->execute([$answerId]);
        $ans = $checkAns->fetch();
        $isCorrect = $ans['is_correct'] ? 1 : 0;
    }
    
    // Luu cau tra loi moi
    $stmt = $db->prepare('INSERT INTO assessment_responses (attempt_id, question_id, selected_answer_id, is_correct) VALUES (?,?,?,?)');
    $stmt->execute([$attemptId, $questionId, $answerId, $isCorrect]);
    
    jsonResponse(['success' => true, 'is_correct' => $isCorrect]);
}

if ($method === 'POST' && isset($input['action']) && $input['action'] === 'submit') {
    // Nop bai thi
    $attemptId = (int)$input['attempt_id'];
    
    // Kiem tra attempt
    $check = $db->prepare('SELECT * FROM assessment_attempts WHERE id=? AND student_id=?');
    $check->execute([$attemptId, $sid]);
    $attempt = $check->fetch();
    if (!$attempt) jsonResponse(['error' => 'Khong co quyen'], 403);
    
    // Tinh diem
    $stmt = $db->prepare('SELECT COUNT(*) c FROM assessment_responses WHERE attempt_id=? AND is_correct=1');
    $stmt->execute([$attemptId]);
    $correctCount = $stmt->fetch()['c'];
    $totalQuestions = $db->prepare('SELECT COUNT(DISTINCT question_id) c FROM assessment_responses WHERE attempt_id=?');
    $totalQuestions->execute([$attemptId]);
    $totalQuestions = max(1, $totalQuestions->fetch()['c']);
    $score = ($correctCount / $totalQuestions) * 100;
    
    // Cap nhat attempt
    $stmt = $db->prepare('UPDATE assessment_attempts SET submitted_at=NOW(), total_correct=?, total_score=?, status="submitted" WHERE id=?');
    $stmt->execute([$correctCount, $score, $attemptId]);
    
    jsonResponse(['success' => true, 'score' => $score, 'correct' => $correctCount, 'total' => $totalQuestions]);
}

if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    $check = $db->prepare('SELECT id FROM assessments WHERE id=? AND student_id=?');
    $check->execute([$id, $sid]);
    if (!$check->fetch()) jsonResponse(['error' => 'Khong co quyen'], 403);
    
    $stmt = $db->prepare('DELETE FROM assessments WHERE id=? AND student_id=?');
    $stmt->execute([$id, $sid]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);

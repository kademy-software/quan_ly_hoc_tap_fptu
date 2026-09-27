<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];
$assessmentId = (int)($_GET['id'] ?? 0);

// Lay thong tin bai thi
$stmt = $db->prepare('SELECT * FROM assessments WHERE id=? AND is_published=1');
$stmt->execute([$assessmentId]);
$assessment = $stmt->fetch();
if (!$assessment) {
    header('Location: assessments.php');
    exit;
}

// Tao attempt moi
$stmt = $db->prepare('INSERT INTO assessment_attempts (assessment_id, student_id) VALUES (?,?)');
$stmt->execute([$assessmentId, $sid]);
$attemptId = $db->lastInsertId();

// Lay cac cau hoi va dap an
$stmt = $db->prepare('SELECT q.*, COUNT(DISTINCT a.id) as answer_count FROM assessment_questions q LEFT JOIN assessment_answers a ON a.question_id=q.id WHERE q.assessment_id=? GROUP BY q.id ORDER BY q.question_order');
$stmt->execute([$assessmentId]);
$questions = $stmt->fetchAll();

$pageTitle = $assessment['title'];
$activePage = 'assessments';
$extraScripts = ['assets/js/take_assessment.js'];
include __DIR__ . '/includes/header.php';
?>

<div class="hero-banner" style="margin-bottom:24px;">
    <div style="display:flex; align-items:center; justify-content:space-between;">
        <div>
            <h1 style="margin:0 0 8px;"><?= htmlspecialchars($assessment['title']) ?></h1>
            <p style="margin:0; color:var(--text-dim);"><?= htmlspecialchars($assessment['description'] ?? '') ?></p>
        </div>
        <div style="text-align:right;">
            <div style="font-size:24px; font-weight:700;">
                <span id="progress">0</span> / <?= (int)$assessment['total_questions'] ?>
            </div>
            <div style="color:var(--text-dim); font-size:13px;">Câu hỏi</div>
        </div>
    </div>
    <div class="progress-track" style="margin-top:12px;"><div class="progress-fill" id="progressBar" style="width:0%"></div></div>
</div>

<form id="assessmentForm">
    <input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
    
    <div class="card" id="questionsContainer">
        <?php foreach ($questions as $q): 
            $stmt = $db->prepare('SELECT * FROM assessment_answers WHERE question_id=? ORDER BY answer_order');
            $stmt->execute([$q['id']]);
            $answers = $stmt->fetchAll();
        ?>
        <div class="question-block" data-question-id="<?= $q['id'] ?>" data-question-order="<?= $q['question_order'] ?>">
            <div class="progress-row" style="margin-bottom:16px;">
                <span><strong>Câu <?= (int)$q['question_order'] ?></strong></span>
                <span class="badge badge-blue" id="status-<?= $q['id'] ?>">Chưa trả lời</span>
            </div>
            <h3 style="margin-top:0; margin-bottom:14px;"><?= htmlspecialchars($q['question_text']) ?></h3>
            
            <div style="display:flex; flex-direction:column; gap:10px;">
            <?php foreach ($answers as $ans): ?>
                <label style="display:flex; align-items:center; gap:12px; padding:10px; border:1px solid var(--border); border-radius:8px; cursor:pointer; transition:all .2s;">
                    <input type="radio" name="answer_<?= $q['id'] ?>" value="<?= $ans['id'] ?>" onchange="updateAnswer(<?= $q['id'] ?>, this.value, <?= $assessmentId ?>, <?= $attemptId ?>)" style="width:auto; accent-color:var(--accent);">
                    <span><?= htmlspecialchars($ans['answer_text']) ?></span>
                </label>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card" style="margin-top:24px; text-align:center;">
        <button type="button" class="btn btn-accent" onclick="submitAssessment(<?= $attemptId ?>, <?= $assessmentId ?>)" style="width:100%; padding:12px; font-size:16px;">Nộp bài thi</button>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>

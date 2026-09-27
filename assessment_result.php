<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];
$attemptId = (int)($_GET['attempt_id'] ?? 0);

// Lay thong tin attempt
$stmt = $db->prepare('SELECT aa.*, a.title, a.total_questions, a.passing_score FROM assessment_attempts aa JOIN assessments a ON a.id=aa.assessment_id WHERE aa.id=? AND aa.student_id=?');
$stmt->execute([$attemptId, $sid]);
$attempt = $stmt->fetch();
if (!$attempt) {
    header('Location: assessments.php');
    exit;
}

// Lay cac cau tra loi
$stmt = $db->prepare('
    SELECT ar.*, q.question_text, qa.answer_text as selected_text, correct_a.answer_text as correct_text
    FROM assessment_responses ar
    JOIN assessment_questions q ON q.id=ar.question_id
    LEFT JOIN assessment_answers qa ON qa.id=ar.selected_answer_id
    LEFT JOIN assessment_answers correct_a ON correct_a.question_id=q.id AND correct_a.is_correct=1
    WHERE ar.attempt_id=?
    ORDER BY q.question_order
');
$stmt->execute([$attemptId]);
$responses = $stmt->fetchAll();

$correctCount = 0; $wrongCount = 0;
$wrongAnswers = [];
foreach ($responses as $r) {
    if ($r['is_correct']) $correctCount++;
    else {
        $wrongCount++;
        $wrongAnswers[] = $r;
    }
}
$accuracy = $correctCount / max(1, $attempt['total_questions']) * 100;
$passed = $accuracy >= $attempt['passing_score'];

$pageTitle = 'Kết quả bài thi';
$activePage = 'assessments';
include __DIR__ . '/includes/header.php';
?>

<div class="hero-banner">
    <h1 style="margin:0 0 16px;"><?= htmlspecialchars($attempt['title']) ?></h1>
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-top:16px;">
        <div style="background:rgba(164,208,55,.1); padding:14px; border-radius:8px; border-left:4px solid var(--accent);">
            <div style="color:var(--text-dim); font-size:12px; font-weight:700; text-transform:uppercase;">Điểm số</div>
            <div style="font-size:24px; font-weight:700; color:var(--accent);"><?= number_format($accuracy, 1) ?>%</div>
        </div>
        <div style="background:rgba(78,161,255,.1); padding:14px; border-radius:8px; border-left:4px solid var(--blue);">
            <div style="color:var(--text-dim); font-size:12px; font-weight:700; text-transform:uppercase;">Câu đúng</div>
            <div style="font-size:24px; font-weight:700; color:var(--blue);"><?= $correctCount ?> / <?= $attempt['total_questions'] ?></div>
        </div>
        <div style="background:rgba(226,68,92,.1); padding:14px; border-radius:8px; border-left:4px solid var(--red);">
            <div style="color:var(--text-dim); font-size:12px; font-weight:700; text-transform:uppercase;">Kết quả</div>
            <div style="font-size:18px; font-weight:700; color:<?= $passed ? 'var(--accent)' : 'var(--red)' ?>;"><?= $passed ? '✓ Đạt' : '✗ Không đạt' ?></div>
        </div>
    </div>
</div>

<?php if ($wrongAnswers): ?>
<div class="card" style="margin-top:24px;">
    <h2>Câu hỏi cần ôn tập (<?= count($wrongAnswers) ?> câu)</h2>
    <p style="color:var(--text-dim); font-size:13px;">Dưới đây là các câu bạn trả lời sai. Hãy xem lại đáp án đúng để cải thiện kỹ năng.</p>
    
    <?php foreach ($wrongAnswers as $i => $wa): ?>
    <div style="margin-top:20px; padding-top:20px; border-top:1px solid var(--border);">
        <div class="progress-row"><span><strong>Câu <?= htmlspecialchars($wa['question_order'] ?? ($i+1)) ?></strong></span><span class="badge badge-red">Sai</span></div>
        <h3 style="margin:10px 0;"><?= htmlspecialchars($wa['question_text']) ?></h3>
        
        <div style="background:rgba(226,68,92,.1); padding:12px; border-radius:8px; margin:10px 0; border-left:4px solid var(--red);">
            <div style="color:var(--text-dim); font-size:12px; font-weight:700;">Câu trả lời của bạn:</div>
            <div style="color:var(--red); font-weight:600;"><?= htmlspecialchars($wa['selected_text'] ?? 'Không chọn') ?></div>
        </div>
        
        <div style="background:rgba(164,208,55,.1); padding:12px; border-radius:8px; border-left:4px solid var(--accent);">
            <div style="color:var(--text-dim); font-size:12px; font-weight:700;">Đáp án đúng:</div>
            <div style="color:var(--accent); font-weight:600;"><?= htmlspecialchars($wa['correct_text'] ?? '—') ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card" style="margin-top:24px; background:rgba(164,208,55,.08); border:1px solid var(--accent); text-align:center; padding:30px;">
    <div style="font-size:48px; margin-bottom:10px;">🎉</div>
    <h2>Hoàn hảo!</h2>
    <p>Bạn đã trả lời đúng tất cả các câu hỏi. Chúc mừng bạn!</p>
</div>
<?php endif; ?>

<div class="card" style="margin-top:24px; text-align:center;">
    <a href="assessments.php" class="btn btn-outline">← Quay lại danh sách bài thi</a>
    <a href="take_assessment.php?id=<?= $attempt['assessment_id'] ?>" class="btn btn-accent">Làm lại bài thi</a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

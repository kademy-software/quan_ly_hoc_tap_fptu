<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid =$_SESSION['student_id'];

// Lấy quiz_id từ URL
$quiz_id =$_GET['id'] ?? null;

// ==============================================================================
// 1. NẾU KHÔNG CÓ ID -> HIỂN THỊ DANH SÁCH BÀI THI
// ==============================================================================
if (!$quiz_id) {
    // Lấy tất cả bài thi của sinh viên hiện tại
    $stmt =$db->prepare("SELECT * FROM quizzes WHERE student_id = ? ORDER BY created_at DESC");
    $stmt->execute([$sid]);
    $quizzes =$stmt->fetchAll(PDO::FETCH_ASSOC);

    $pageTitle = 'Danh sách bài trắc nghiệm';
    $activePage = 'quiz';
    include __DIR__ . '/includes/header.php'; //[cite: 4]
    ?>
    
    <div class="card-header" style="margin-bottom: 20px;">
        <h2>Chọn bài trắc nghiệm để bắt đầu</h2>
        <a href="quiz_create.php" class="btn btn-outline btn-sm">+ Tạo bài mới</a> <!--[cite: 2] -->
    </div>

    <div class="grid grid-3"> <!--[cite: 2] -->
        <?php foreach ($quizzes as$q): ?>
            <?php
            // Đếm số lượng câu hỏi trong bài
            $count_stmt =$db->prepare("SELECT COUNT(*) as c FROM quiz_questions WHERE quiz_id = ?");
            $count_stmt->execute([$q['id']]);
            $q_count =$count_stmt->fetch()['c'];
            ?>
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;"> <!--[cite: 2] -->
                <div>
                    <div class="badge badge-blue" style="margin-bottom: 12px;"> <!--[cite: 2] -->
                        <?= htmlspecialchars($q['description'] ?: 'Assessment') ?>
                    </div>
                    <h3 style="margin-top: 0; margin-bottom: 8px; color: var(--accent);"><?= htmlspecialchars($q['title']) ?></h3> <!--[cite: 2] -->
                    
                    <p style="color: var(--text-dim); font-size: 13px; margin: 4px 0;"> <!--[cite: 2] -->
                        <strong>Số câu hỏi:</strong> <?= $q_count ?> câu
                    </p>
                    <p style="color: var(--text-dim); font-size: 13px; margin: 4px 0;"> <!--[cite: 2] -->
                        <strong>Ngày tạo:</strong> <?= date('d/m/Y', strtotime($q['created_at'])) ?>
                    </p>
                </div>
                <div style="margin-top: 20px;">
                    <a href="quiz_take.php?id=<?= $q['id'] ?>" class="btn btn-accent" style="display: block; text-align: center; font-weight: bold;"> <!--[cite: 2] -->
                        Bắt đầu làm bài ❯
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($quizzes)): ?>
            <div class="empty-state" style="grid-column: 1 / -1;"> <!--[cite: 2] -->
                Chưa có bài trắc nghiệm nào. Hãy tạo bài thi mới để luyện tập nhé!
            </div>
        <?php endif; ?>
    </div>

    <?php
    include __DIR__ . '/includes/footer.php'; //[cite: 3]
    exit; // Dừng tại đây, không load phần làm bài bên dưới
}

// ==============================================================================
// 2. NẾU CÓ ID -> HIỂN THỊ GIAO DIỆN LÀM BÀI
// ==============================================================================
$quiz_stmt =$db->prepare("SELECT * FROM quizzes WHERE id = ?");
$quiz_stmt->execute([$quiz_id]);
$quiz =$quiz_stmt->fetch(PDO::FETCH_ASSOC);

// Kiểm tra xem bài thi có tồn tại không
if (!$quiz) {$pageTitle = 'Không tìm thấy bài thi';
    $activePage = 'quiz';
    include __DIR__ . '/includes/header.php';  //[cite: 4]
    echo "<div class='card' style='text-align: center; padding: 50px 20px; margin-top: 20px;'>
            <h2 style='color: var(--red);'>Không tìm thấy bài thi!</h2> <!-- -->
            <p style='color: var(--text-dim); margin-bottom: 20px;'>Bài trắc nghiệm này không tồn tại hoặc đã bị xóa.</p> <!--[cite: 2] -->
            <a href='quiz_take.php' class='btn btn-outline'>Về danh sách bài thi</a> <!--[cite: 2] -->
          </div>";
    include __DIR__ . '/includes/footer.php'; //[cite: 3]
    exit;
}

// Lấy danh sách câu hỏi
$q_stmt =$db->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ?");
$q_stmt->execute([$quiz_id]);
$questions =$q_stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Làm bài: ' . $quiz['title'];$activePage = 'quiz';
include __DIR__ . '/includes/header.php'; //[cite: 4]
?>

<!-- ================= GIAO DIỆN LÀM BÀI (HTML & CSS & JS) ================= -->
<style>
/* CSS Tùy chỉnh bám sát giao diện trong hình và style.css */
.qz-wrap { max-width: 900px; margin: 0 auto; padding-top: 10px; }
.qz-header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;}
.qz-title { color: var(--accent); font-size: 24px; margin: 0; font-weight: 500; } /*[cite: 2] */
.qz-subtitle { color: var(--text); font-size: 14px; margin-top: 5px; opacity: 0.9; } /*[cite: 2] */
.qz-tools { display: flex; gap: 15px; color: var(--text-dim); font-size: 13px; } /*[cite: 2] */
.qz-tools span, .qz-tools a { cursor: pointer; display: flex; align-items: center; gap: 5px; color: inherit; text-decoration: none;}

.qz-progress-container { margin-bottom: 25px; }
.qz-progress-bar { height: 4px; background: var(--bg-panel-2); border-radius: 2px; position: relative; } /*[cite: 2] */
.qz-progress-fill { height: 100%; background: var(--accent); border-radius: 2px; transition: width 0.3s; width: 0%; } /*[cite: 2] */
.qz-progress-text { text-align: right; font-size: 12px; color: var(--text-dim); margin-top: 8px; } /*[cite: 2] */

.qz-badge { background: rgba(78,161,255,.15); color: var(--blue); padding: 5px 12px; border-radius: 20px; font-size: 12px; display: inline-block; margin-bottom: 15px; font-weight: bold; } /*[cite: 2] */

.qz-question-box { background: var(--bg-panel-2); padding: 20px; border-radius: 8px; font-size: 16px; line-height: 1.5; margin-bottom: 20px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.1); } /*[cite: 2] */

.qz-option { background: var(--bg-panel); border: 1px solid var(--border); padding: 16px 20px; border-radius: 8px; margin-bottom: 12px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: space-between; } /*[cite: 2] */
.qz-option:hover:not(.disabled) { border-color: var(--text-dim); background: var(--bg-panel-2); } /*[cite: 2] */
.qz-option.disabled { cursor: not-allowed; opacity: 0.8; }

/* Trạng thái đúng/sai */
.qz-option.correct { border-color: var(--accent); color: var(--accent); background: rgba(164,208,55,.05); } /*[cite: 2] */
.qz-option.wrong { border-color: var(--red); color: var(--red); background: rgba(226,68,92,.05); } /*[cite: 2] */

.qz-icon-check { display: none; }
.qz-option.correct .qz-icon-check { display: block; font-weight: bold; }
.qz-option.wrong .qz-icon-check { display: block; content: '✕'; font-weight: bold; }

.qz-explanation { background: var(--bg-panel-2); border: 1px solid var(--border); padding: 16px; border-radius: 8px; margin-top: 20px; font-size: 14px; display: none; } /*[cite: 2] */
.qz-btn-next { background: var(--accent); color: #000; font-weight: bold; padding: 10px 24px; border-radius: 6px; border: none; cursor: pointer; float: right; margin-top: 20px; display: none; transition: background 0.2s; } /*[cite: 2] */
.qz-btn-next:hover { background: var(--accent-dark); } /*[cite: 2] */

.qz-results { display: none; text-align: center; padding: 40px 20px; background: var(--bg-panel-2); border-radius: var(--radius); border: 1px solid var(--border); } /*[cite: 2] */
</style>

<div class="qz-wrap" id="quizApp">
    <div class="qz-header-top">
        <div>
            <h1 class="qz-title"><?= htmlspecialchars($quiz['title']) ?></h1>
            <div class="qz-subtitle">Practice assessment</div>
        </div>
        <div class="qz-tools">
            <a href="quiz_take.php">☰ Danh sách bài</a>
            <span onclick="location.reload()">↺ Restart</span>
        </div>
    </div>

    <div class="qz-progress-container" id="progressContainer">
        <div class="qz-progress-bar"><div class="qz-progress-fill" id="progressFill"></div></div>
        <div class="qz-progress-text" id="progressText">1/1</div>
    </div>

    <div id="quizContent">
        <div class="qz-badge"><?= htmlspecialchars($quiz['description'] ?: 'Assessment') ?></div>
        <div class="qz-question-box" id="questionText">Loading question...</div>
        
        <div id="optionsContainer">
            <!-- Render options here via JS -->
        </div>

        <div class="qz-explanation" id="explanationBox"></div>
        <button class="qz-btn-next" id="btnNext" onclick="nextQuestion()">Next Question ❯</button>
        <div style="clear: both;"></div>
    </div>

    <div class="qz-results" id="resultsScreen">
        <h2 style="color: var(--accent); margin-bottom: 10px;">Phân tích kết quả</h2> <!--[cite: 2] -->
        <h1 style="font-size: 48px; margin: 10px 0;" id="scorePercent">0%</h1>
        <p style="color: var(--text-dim); margin-bottom: 20px;" id="scoreDetail"></p> <!--[cite: 2] -->
        <a href="quiz_take.php?id=<?= $quiz_id ?>" class="btn btn-accent">Làm lại bài thi</a> <!--[cite: 2] -->
        <a href="quiz_take.php" class="btn btn-outline">Về danh sách</a> <!--[cite: 2] -->
    </div>
</div>

<script>
// Dữ liệu câu hỏi từ PHP truyền xuống
const allQuestions = <?= json_encode($questions) ?>;

let questionQueue = [...allQuestions];
let totalUniqueQuestions = allQuestions.length;
let currentQ = null;
let answeredCorrectlyOnFirstTry = 0;
let currentIndexInQueue = 0;

const DOM = {
    qText: document.getElementById('questionText'),
    options: document.getElementById('optionsContainer'),
    expl: document.getElementById('explanationBox'),
    nextBtn: document.getElementById('btnNext'),
    progFill: document.getElementById('progressFill'),
    progText: document.getElementById('progressText'),
    content: document.getElementById('quizContent'),
    results: document.getElementById('resultsScreen'),
    progressContainer: document.getElementById('progressContainer')
};

function renderQuestion() {
    if (currentIndexInQueue >= questionQueue.length) {
        showResults();
        return;
    }

    currentQ = questionQueue[currentIndexInQueue];
    
    // UI Reset
    DOM.expl.style.display = 'none';
    DOM.nextBtn.style.display = 'none';
    DOM.options.innerHTML = '';
    
    // Update Progress
    let progress = ((currentIndexInQueue) / questionQueue.length) * 100;
    DOM.progFill.style.width = progress + '%';
    DOM.progText.innerText = `${currentIndexInQueue + 1} / ${questionQueue.length}`;

    // Update Text
    DOM.qText.innerText = currentQ.question_text;

    // Render Options
    const opts = ['A', 'B', 'C', 'D'];
    opts.forEach(opt => {
        let text = currentQ['opt_' + opt.toLowerCase()];
        if(text && text.trim() !== '') {
            let div = document.createElement('div');
            div.className = 'qz-option';
            div.innerHTML = `<span>${text}</span> <span class="qz-icon-check"></span>`;
            div.onclick = () => selectOption(opt, div);
            DOM.options.appendChild(div);
        }
    });
}

function selectOption(selectedOpt, optElement) {
    if (optElement.classList.contains('disabled')) return;
    
    let optionsList = DOM.options.children;
    for (let el of optionsList) {
        el.classList.add('disabled');
        el.onclick = null;
    }

    let isCorrect = (selectedOpt === currentQ.correct_opt);

    const opts = ['A', 'B', 'C', 'D'];
    let correctIndex = -1;
    
    // Tìm index của đáp án đúng hiện đang hiển thị trên giao diện
    let visibleIndex = 0;
    opts.forEach(opt => {
        if (currentQ['opt_' + opt.toLowerCase()] && currentQ['opt_' + opt.toLowerCase()].trim() !== '') {
            if (opt === currentQ.correct_opt) correctIndex = visibleIndex;
            visibleIndex++;
        }
    });

    let correctEl = correctIndex >= 0 ? DOM.options.children[correctIndex] : null;

    if (isCorrect) {
        optElement.classList.add('correct');
        optElement.querySelector('.qz-icon-check').innerText = '✔';
        if (!currentQ.hasFailed) answeredCorrectlyOnFirstTry++;
    } else {
        optElement.classList.add('wrong');
        optElement.querySelector('.qz-icon-check').innerText = '✕';
        if (correctEl) {
            correctEl.classList.add('correct');
            correctEl.querySelector('.qz-icon-check').innerText = '✔';
        }
        
        currentQ.hasFailed = true;
        questionQueue.push(currentQ); 
    }

    if (currentQ.explanation && currentQ.explanation.trim() !== '') {
        DOM.expl.innerText = currentQ.explanation;
        DOM.expl.style.display = 'block';
    }

    DOM.nextBtn.style.display = 'block';
}

function nextQuestion() {
    currentIndexInQueue++;
    renderQuestion();
}

function showResults() {
    DOM.content.style.display = 'none';
    DOM.progressContainer.style.display = 'none';
    DOM.results.style.display = 'block';

    let accuracy = totalUniqueQuestions > 0 ? Math.round((answeredCorrectlyOnFirstTry / totalUniqueQuestions) * 100) : 0;
    document.getElementById('scorePercent').innerText = accuracy + '%';
    document.getElementById('scoreDetail').innerText = `Bạn đã trả lời đúng ${answeredCorrectlyOnFirstTry}/${totalUniqueQuestions} câu hỏi ở lần thử đầu tiên.\nTổng số lượt làm bài (bao gồm làm lại câu sai) là ${questionQueue.length} lượt.`;
}

if(allQuestions.length > 0) {
    renderQuestion();
} else {
    DOM.qText.innerText = "Chưa có câu hỏi nào trong bài thi này.";
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?> <!--[cite: 3] -->
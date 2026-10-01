<?php
require_once __DIR__ . '/config/roles.php';
requireLogin();
$db = getDB();
$sid = (int)$_SESSION['student_id'];

// ==============================================================================
// 0. XỬ LÝ CẬP NHẬT NỘI DUNG CÂU HỎI (AJAX POST -> JSON)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_question') {
    header('Content-Type: application/json; charset=utf-8');

    $qid      = (int)($_POST['id'] ?? 0);
    $question = trim($_POST['question_text'] ?? '');
    $opts = [
        'opt_a' => trim($_POST['opt_a'] ?? ''),
        'opt_b' => trim($_POST['opt_b'] ?? ''),
        'opt_c' => trim($_POST['opt_c'] ?? ''),
        'opt_d' => trim($_POST['opt_d'] ?? ''),
    ];
    $correct     = strtoupper(trim($_POST['correct_opt'] ?? ''));
    $explanation = trim($_POST['explanation'] ?? '');

    // Chỉ admin hoặc chủ bài mới được sửa câu hỏi
    $own = $db->prepare("SELECT z.* FROM quiz_questions qq JOIN quizzes z ON z.id = qq.quiz_id WHERE qq.id = ?");
    $own->execute([$qid]);
    $ownQuiz = $own->fetch(PDO::FETCH_ASSOC);
    if (!$ownQuiz || !canEditQuiz($ownQuiz)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Bạn không có quyền sửa câu hỏi này.']);
        exit;
    }

    $error = null;
    if ($qid <= 0)                                  $error = 'Câu hỏi không hợp lệ.';
    elseif ($question === '')                       $error = 'Nội dung câu hỏi không được để trống.';
    elseif ($opts['opt_a'] === '' || $opts['opt_b'] === '') $error = 'Cần ít nhất 2 đáp án (A và B).';
    elseif (!in_array($correct, ['A', 'B', 'C', 'D'], true)) $error = 'Hãy chọn đáp án đúng.';
    elseif ($opts['opt_' . strtolower($correct)] === '')    $error = 'Đáp án đúng không được để trống.';

    if ($error) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => $error]);
        exit;
    }

    try {
        $upd = $db->prepare(
            "UPDATE quiz_questions
                SET question_text = ?, opt_a = ?, opt_b = ?, opt_c = ?, opt_d = ?,
                    correct_opt = ?, explanation = ?
              WHERE id = ?"
        );
        $upd->execute([
            $question, $opts['opt_a'], $opts['opt_b'], $opts['opt_c'], $opts['opt_d'],
            $correct, $explanation, $qid
        ]);
        echo json_encode([
            'ok' => true,
            'question' => array_merge(
                ['id' => $qid, 'question_text' => $question],
                $opts,
                ['correct_opt' => $correct, 'explanation' => $explanation]
            )
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Không lưu được, vui lòng thử lại.']);
    }
    exit;
}

// Lấy quiz_id từ URL
$quiz_id =$_GET['id'] ?? null;

// ==============================================================================
// 1. NẾU KHÔNG CÓ ID -> HIỂN THỊ DANH SÁCH BÀI THI
// ==============================================================================
if (!$quiz_id) {
    // Lấy tất cả bài thi của sinh viên hiện tại
    if (isAdmin()) {
        $stmt = $db->prepare("SELECT * FROM quizzes ORDER BY created_at DESC");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT * FROM quizzes WHERE student_id = ? ORDER BY created_at DESC");
        $stmt->execute([$sid]);
    }
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
                    <?php if (canEditQuiz($q)): ?>
                    <a href="quiz_take.php?id=<?= $q['id'] ?>&manage=1" class="btn btn-outline" style="display: block; text-align: center; margin-top: 8px;">
                        ✎ Sửa câu hỏi
                    </a>
                    <?php endif; ?>
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
if (!$quiz || !canEditQuiz($quiz)) {$pageTitle = 'Không tìm thấy bài thi';
    $activePage = 'quiz';
    include __DIR__ . '/includes/header.php';  //[cite: 4]
    echo "<div class='card' style='text-align: center; padding: 50px 20px; margin-top: 20px;'>
            <h2 style='color: var(--red);'>Không tìm thấy bài thi!</h2> <!-- -->
            <p style='color: var(--text-dim); margin-bottom: 20px;'>Bài trắc nghiệm này không tồn tại, đã bị xóa hoặc bạn không có quyền truy cập.</p> <!--[cite: 2] -->
            <a href='quiz_take.php' class='btn btn-outline'>Về danh sách bài thi</a> <!--[cite: 2] -->
          </div>";
    include __DIR__ . '/includes/footer.php'; //[cite: 3]
    exit;
}

// ==============================================================================
// 2b. MÀN HÌNH SỬA CÂU HỎI CỦA MỘT BÀI (quiz_take.php?id=X&manage=1)
// ==============================================================================
if (isset($_GET['manage'])) {
    $m_stmt = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id");
    $m_stmt->execute([$quiz_id]);
    $manage_questions = $m_stmt->fetchAll(PDO::FETCH_ASSOC);

    $pageTitle = 'Sửa câu hỏi: ' . $quiz['title'];
    $activePage = 'quiz';
    include __DIR__ . '/includes/header.php';
    ?>

    <div class="card-header" style="margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0 0 4px;">Sửa câu hỏi</h2>
            <span style="color: var(--text-dim); font-size: 13px;"><?= htmlspecialchars($quiz['title']) ?> · <b id="manageCount"><?= count($manage_questions) ?></b> câu</span>
        </div>
        <div class="toolbar">
            <a href="quiz_take.php" class="btn btn-outline btn-sm">☰ Danh sách bài</a>
            <a href="quiz_take.php?id=<?= (int)$quiz_id ?>" class="btn btn-accent btn-sm">Làm bài ❯</a>
        </div>
    </div>

    <div class="flash success" id="manageFlash" style="display: none;"></div>

    <div class="card">
        <?php if (empty($manage_questions)): ?>
            <div class="empty-state">Bài này chưa có câu hỏi nào.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 48px;">#</th>
                    <th>Câu hỏi</th>
                    <th style="width: 32%;">Đáp án đúng</th>
                    <th style="width: 90px;"></th>
                </tr>
            </thead>
            <tbody id="manageBody"></tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/includes/question_editor.php'; ?>

    <script>
    const manageQs = <?= json_encode($manage_questions) ?>;
    const manageBody = document.getElementById('manageBody');
    let flashTimer = null;

    function clip(text, n) {
        text = (text || '').replace(/\s+/g, ' ').trim();
        return text.length > n ? text.slice(0, n) + '…' : text;
    }

    function cell(text, style) {
        const td = document.createElement('td');
        td.textContent = text;
        if (style) td.style.cssText = style;
        return td;
    }

    function renderManage() {
        if (!manageBody) return;
        manageBody.innerHTML = '';
        manageQs.forEach((q, i) => {
            const tr = document.createElement('tr');
            const correctText = q['opt_' + (q.correct_opt || '').toLowerCase()] || '';

            tr.appendChild(cell(i + 1, 'color: var(--text-dim);'));
            tr.appendChild(cell(clip(q.question_text, 140)));

            const tdOk = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = 'badge badge-green';
            badge.textContent = q.correct_opt;
            tdOk.appendChild(badge);
            tdOk.appendChild(document.createTextNode(' ' + clip(correctText, 50)));
            tr.appendChild(tdOk);

            const tdAct = document.createElement('td');
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline btn-sm';
            btn.textContent = '✎ Sửa';
            btn.onclick = () => editQuestion(i);
            tdAct.appendChild(btn);
            tr.appendChild(tdAct);

            manageBody.appendChild(tr);
        });
    }

    function editQuestion(i) {
        QEditor.open(manageQs[i], 'câu ' + (i + 1), function (u) {
            const q = manageQs[i];
            q.question_text = u.question_text;
            q.opt_a = u.opt_a; q.opt_b = u.opt_b; q.opt_c = u.opt_c; q.opt_d = u.opt_d;
            q.correct_opt = u.correct_opt;
            q.explanation = u.explanation;
            renderManage();

            const f = document.getElementById('manageFlash');
            f.textContent = 'Đã lưu câu ' + (i + 1) + '.';
            f.style.display = 'block';
            clearTimeout(flashTimer);
            flashTimer = setTimeout(() => { f.style.display = 'none'; }, 2500);
        });
    }

    renderManage();
    </script>

    <?php
    include __DIR__ . '/includes/footer.php';
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
.qz-wrap { max-width: 1120px; margin: 0 auto; padding-top: 10px; }
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
.qz-option.hidden { display: none; }
.qz-option.wrong { animation: qzShake 0.35s; }
@keyframes qzShake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-6px)} 75%{transform:translateX(6px)} }

/* Trạng thái đúng/sai */
.qz-option.correct { border-color: var(--accent); color: var(--accent); background: rgba(164,208,55,.05); } /*[cite: 2] */
.qz-option.wrong { border-color: var(--red); color: var(--red); background: rgba(226,68,92,.05); } /*[cite: 2] */

.qz-icon-check { display: none; }
.qz-option.correct .qz-icon-check { display: block; font-weight: bold; }
.qz-option.wrong .qz-icon-check { display: block; content: '✕'; font-weight: bold; }

.qz-explanation { background: var(--bg-panel-2); border: 1px solid var(--border); padding: 16px; border-radius: 8px; margin-top: 20px; font-size: 14px; display: none; } /*[cite: 2] */
.qz-btn-next { background: var(--accent); color: #000; font-weight: bold; padding: 10px 24px; border-radius: 6px; border: none; cursor: pointer; float: right; margin-top: 20px; display: none; transition: background 0.2s; } /*[cite: 2] */
.qz-btn-next:hover { background: var(--accent-dark); } /*[cite: 2] */

.qz-tree { position: fixed; left: 16px; bottom: 16px; width: 130px; z-index: 50; pointer-events: none; text-align: center; }
.qz-tree svg { width: 100%; height: auto; display: block; overflow: visible; }
.qz-tree-plant { transform-origin: 60px 150px; transition: transform 0.8s cubic-bezier(.34,1.56,.64,1); }
.qz-tree-fruit { opacity: 0; transition: opacity 0.6s; }
.qz-tree-label { font-size: 12px; color: var(--text-dim); margin-top: 4px; }
.qz-tree.pop .qz-tree-plant { animation: qzTreePop 0.6s; }
@keyframes qzTreePop { 0%,100%{filter:none} 50%{filter:brightness(1.4)} }
@media (max-width: 700px) { .qz-tree { width: 80px; left: 8px; bottom: 8px; } }
.qz-results { display: none; text-align: center; padding: 40px 20px; background: var(--bg-panel-2); border-radius: var(--radius); border: 1px solid var(--border); } /*[cite: 2] */

/* Số phím tắt trên đáp án */
.qz-opt-label { display: flex; align-items: center; gap: 12px; }
.qz-key { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 6px; background: var(--bg-panel-2); border: 1px solid var(--border); color: var(--text-dim); font-size: 12px; font-weight: bold; flex-shrink: 0; }
.qz-option.hidden { display: none; }
.qz-hint { font-size: 12px; color: var(--text-dim); margin-top: 14px; }

/* Bố cục + bảng chọn câu hỏi bên phải */
.qz-layout { display: grid; grid-template-columns: 1fr 200px; gap: 24px; align-items: start; }
.qz-nav { position: sticky; top: 16px; background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; padding: 14px; }
.qz-nav-title { font-size: 13px; font-weight: bold; color: var(--text); margin-bottom: 10px; }
.qz-nav-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
.qz-nav-item { aspect-ratio: 1; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-panel-2); color: var(--text); cursor: pointer; font-size: 12px; padding: 0; transition: all 0.15s; }
.qz-nav-item:hover { border-color: var(--text-dim); }
.qz-nav-item.ok { background: rgba(164,208,55,.25); border-color: var(--accent); }
.qz-nav-item.retry { background: rgba(245,166,35,.25); border-color: #f5a623; }
.qz-nav-item.current { outline: 2px solid var(--accent); outline-offset: 1px; font-weight: bold; }
.qz-nav-legend { margin-top: 12px; font-size: 11px; color: var(--text-dim); line-height: 1.8; }
.qz-dot { display: inline-block; width: 9px; height: 9px; border-radius: 2px; margin-right: 6px; border: 1px solid var(--border); }
@media (max-width: 800px) { .qz-layout { grid-template-columns: 1fr; } .qz-nav { position: static; } }

/* Màn hình thiết lập + đồng hồ */
.qz-setup { max-width: 520px; margin: 30px auto; background: var(--bg-panel); border: 1px solid var(--border); border-radius: 10px; padding: 28px; }
.qz-setup h2 { margin: 0 0 6px; color: var(--accent); font-weight: 500; }
.qz-setup-sub { color: var(--text-dim); font-size: 13px; margin-bottom: 20px; }
.qz-field { margin-bottom: 16px; }
.qz-field label { display: block; font-size: 13px; margin-bottom: 6px; color: var(--text); }
.qz-field input[type=number] { width: 100%; box-sizing: border-box; padding: 10px 12px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-panel-2); color: var(--text); font-size: 15px; }
.qz-field input[type=number]:focus { outline: none; border-color: var(--accent); }
.qz-field small { display: block; margin-top: 4px; color: var(--text-dim); font-size: 12px; }
.qz-check { display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; }
.qz-presets { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
.qz-presets button { padding: 4px 10px; border-radius: 14px; border: 1px solid var(--border); background: var(--bg-panel-2); color: var(--text-dim); font-size: 12px; cursor: pointer; }
.qz-presets button:hover { border-color: var(--accent); color: var(--accent); }
.qz-timer { display: none; align-items: center; gap: 6px; font-variant-numeric: tabular-nums; color: var(--accent); font-weight: bold; font-size: 15px; }
.qz-timer.danger { color: var(--red); animation: qzBlink 1s infinite; }
@keyframes qzBlink { 50% { opacity: 0.45; } }

/* Hàng badge + nút sửa câu hỏi */
.qz-badge-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 15px; }
.qz-badge-row .qz-badge { margin-bottom: 0; }
</style>

<div class="qz-wrap" id="quizApp">
    <div class="qz-header-top">
        <div>
            <h1 class="qz-title"><?= htmlspecialchars($quiz['title']) ?></h1>
            <div class="qz-subtitle">Practice assessment</div>
        </div>
        <div class="qz-tools">
            <div class="qz-timer" id="timerBox">⏱ <span id="timerText">00:00</span></div>
            <a href="quiz_take.php">☰ Danh sách bài</a>
            <span onclick="location.reload()">↺ Restart</span>
        </div>
    </div>

    <!-- Màn hình thiết lập: chọn số câu + thời gian -->
    <div class="qz-setup" id="setupScreen" style="display:none;">
        <h2>Thiết lập bài làm</h2>
        <div class="qz-setup-sub">Bài này có <b id="poolCount">0</b> câu hỏi. Chọn số câu ngẫu nhiên và thời gian làm bài.</div>

        <div class="qz-field">
            <label for="setupCount">Số câu hỏi</label>
            <input type="number" id="setupCount" min="1" step="1">
            <div class="qz-presets" id="countPresets"></div>
        </div>

        <div class="qz-field">
            <label for="setupMinutes">Thời gian (phút)</label>
            <input type="number" id="setupMinutes" min="0" step="1" placeholder="Để trống = không giới hạn">
            <div class="qz-presets" id="minutePresets"></div>
            <small>Hết giờ hệ thống sẽ tự nộp bài và hiện kết quả.</small>
        </div>

        <div class="qz-field">
            <label class="qz-check"><input type="checkbox" id="setupShuffle" checked> Chọn ngẫu nhiên và xáo trộn thứ tự câu hỏi</label>
        </div>

        <button type="button" class="btn btn-accent" id="btnStart" style="width:100%; font-weight:bold; padding:12px;">Bắt đầu làm bài ❯</button>
    </div>

    <div class="qz-layout" style="display:none;" id="quizLayout">
        <div class="qz-main">
            <div class="qz-progress-container" id="progressContainer">
                <div class="qz-progress-bar"><div class="qz-progress-fill" id="progressFill"></div></div>
                <div class="qz-progress-text" id="progressText">1/1</div>
            </div>

            <div id="quizContent">
                <div class="qz-badge-row">
                    <div class="qz-badge"><?= htmlspecialchars($quiz['description'] ?: 'Assessment') ?></div>
                    <button type="button" class="btn btn-outline btn-sm" id="btnEditQ">✎ Sửa câu hỏi</button>
                </div>
                <div class="qz-question-box" id="questionText">Loading question...</div>

                <div id="optionsContainer"></div>

                <div class="qz-explanation" id="explanationBox"></div>
                <button class="qz-btn-next" id="btnNext" onclick="nextQuestion()">Next Question ❯ <small>(Enter)</small></button>
                <div style="clear: both;"></div>
                <div class="qz-hint">Phím tắt: <b>1 2 3 4</b> chọn đáp án · <b>Enter</b> sang câu tiếp theo</div>
            </div>

            <div class="qz-results" id="resultsScreen">
                <h2 style="color: var(--accent); margin-bottom: 10px;" id="resultTitle">Phân tích kết quả</h2>
                <h1 style="font-size: 48px; margin: 10px 0;" id="scorePercent">0%</h1>
                <p style="color: var(--text-dim); margin-bottom: 20px; white-space: pre-line;" id="scoreDetail"></p>
                <a href="quiz_take.php?id=<?= $quiz_id ?>" class="btn btn-accent">Làm lại bài thi</a>
                <a href="quiz_take.php" class="btn btn-outline">Về danh sách</a>
            </div>
        </div>

        <aside class="qz-nav" id="quizNav">
            <div class="qz-nav-title">Danh sách câu hỏi</div>
            <div class="qz-nav-grid" id="navGrid"></div>
            <div class="qz-nav-legend">
                <div><span class="qz-dot" style="background: rgba(164,208,55,.4)"></span>Đúng ngay lần đầu</div>
                <div><span class="qz-dot" style="background: rgba(245,166,35,.4)"></span>Đúng sau khi sai</div>
                <div><span class="qz-dot"></span>Chưa làm</div>
            </div>
        </aside>
    </div>
</div>

<div class="qz-tree" id="quizTree" style="display:none;">
    <svg viewBox="0 0 120 160" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="60" cy="152" rx="50" ry="7" fill="#5a3d24" opacity="0.6"/>
        <g class="qz-tree-plant" id="treePlant" style="transform: scale(0.15);">
            <rect x="54" y="80" width="12" height="70" rx="3" fill="#8b5a2b"/>
            <circle cx="60" cy="70" r="30" fill="#3f9142"/>
            <circle cx="38" cy="86" r="22" fill="#4caf50"/>
            <circle cx="82" cy="86" r="22" fill="#4caf50"/>
            <circle cx="60" cy="48" r="22" fill="#66bb6a"/>
            <g class="qz-tree-fruit" id="treeFruit">
                <circle cx="48" cy="62" r="4" fill="#e2445c"/>
                <circle cx="74" cy="76" r="4" fill="#e2445c"/>
                <circle cx="60" cy="42" r="4" fill="#e2445c"/>
                <circle cx="36" cy="88" r="4" fill="#e2445c"/>
                <circle cx="86" cy="92" r="4" fill="#e2445c"/>
            </g>
        </g>
    </svg>
    <div class="qz-tree-label" id="treeLabel">🌱 0/0</div>
</div>

<?php include __DIR__ . '/includes/question_editor.php'; ?>

<canvas id="fireworksCanvas" style="position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:9999;"></canvas>

<script>
// Dữ liệu câu hỏi từ PHP truyền xuống
const questionPool = <?= json_encode($questions) ?>;
questionPool.forEach(q => { q.done = false; q.firstTry = false; q.wrongOpts = []; });

let allQuestions = [];          // bộ câu hỏi đang làm (sau khi chọn ngẫu nhiên)
let totalUniqueQuestions = 0;
let quizStarted = false;
let timeUp = false;
let startTime = 0;
let currentQ = null;
let currentIndex = 0;
let totalWrongClicks = 0;
const OPT_LETTERS = ['A', 'B', 'C', 'D'];

const DOM = {
    qText: document.getElementById('questionText'),
    options: document.getElementById('optionsContainer'),
    expl: document.getElementById('explanationBox'),
    nextBtn: document.getElementById('btnNext'),
    progFill: document.getElementById('progressFill'),
    progText: document.getElementById('progressText'),
    content: document.getElementById('quizContent'),
    results: document.getElementById('resultsScreen'),
    progressContainer: document.getElementById('progressContainer'),
    navGrid: document.getElementById('navGrid')
};

const countCorrect = () => allQuestions.filter(q => q.firstTry).length;
const countDone = () => allQuestions.filter(q => q.done).length;

// ================= BẢNG CHỌN CÂU HỎI =================
function renderNav() {
    DOM.navGrid.innerHTML = '';
    allQuestions.forEach((q, i) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'qz-nav-item';
        if (q.done) b.classList.add(q.firstTry ? 'ok' : 'retry');
        if (i === currentIndex) b.classList.add('current');
        b.textContent = i + 1;
        b.title = 'Câu ' + (i + 1);
        b.onclick = () => { b.blur(); goTo(i); };
        DOM.navGrid.appendChild(b);
    });
}

function goTo(i) {
    if (timeUp) return;
    currentIndex = i;
    DOM.content.style.display = 'block';
    DOM.progressContainer.style.display = 'block';
    DOM.results.style.display = 'none';
    renderQuestion();
}

// ================= HIỂN THỊ CÂU HỎI =================
function renderQuestion() {
    currentQ = allQuestions[currentIndex];

    DOM.expl.style.display = 'none';
    DOM.nextBtn.style.display = 'none';
    DOM.options.innerHTML = '';

    DOM.progFill.style.width = (totalUniqueQuestions ? countDone() / totalUniqueQuestions * 100 : 0) + '%';
    DOM.progText.innerText = `Câu ${currentIndex + 1} / ${totalUniqueQuestions}`;
    DOM.qText.innerText = currentQ.question_text;

    OPT_LETTERS.forEach((opt, idx) => {
        const text = currentQ['opt_' + opt.toLowerCase()];
        if (text && text.trim() !== '') {
            const div = document.createElement('div');
            div.className = 'qz-option';
            div.dataset.opt = opt;

            const label = document.createElement('span');
            label.className = 'qz-opt-label';
            const key = document.createElement('span');
            key.className = 'qz-key';
            key.textContent = idx + 1;
            const txt = document.createElement('span');
            txt.textContent = text;
            label.appendChild(key);
            label.appendChild(txt);

            const icon = document.createElement('span');
            icon.className = 'qz-icon-check';

            div.appendChild(label);
            div.appendChild(icon);
            div.onclick = () => selectOption(opt, div);

            // Khôi phục trạng thái nếu quay lại câu đã làm
            if (currentQ.wrongOpts.includes(opt)) div.classList.add('wrong', 'hidden');
            DOM.options.appendChild(div);
        }
    });

    if (currentQ.done) showSolved();
    renderNav();
}

function showSolved() {
    for (const el of DOM.options.children) {
        el.classList.add('disabled');
        el.onclick = null;
        if (el.dataset.opt === currentQ.correct_opt) {
            el.classList.add('correct');
            el.querySelector('.qz-icon-check').innerText = '✔';
        }
    }
    if (currentQ.explanation && currentQ.explanation.trim() !== '') {
        DOM.expl.innerText = currentQ.explanation;
        DOM.expl.style.display = 'block';
    }
    DOM.nextBtn.style.display = 'block';
}

function selectOption(selectedOpt, optElement) {
    if (currentQ.done) return;
    if (optElement.classList.contains('disabled') || optElement.classList.contains('hidden')) return;

    if (selectedOpt === currentQ.correct_opt) {
        // ĐÚNG -> khóa, tô xanh, pháo hoa, cây lớn thêm
        currentQ.done = true;
        currentQ.firstTry = currentQ.wrongOpts.length === 0;
        showSolved();
        launchFireworks();
        updateTree(true);
        DOM.progFill.style.width = (countDone() / totalUniqueQuestions * 100) + '%';
        renderNav();
    } else {
        // SAI -> ẩn phương án vừa chọn, chọn lại đến khi đúng
        currentQ.wrongOpts.push(selectedOpt);
        totalWrongClicks++;
        optElement.classList.add('wrong');
        optElement.querySelector('.qz-icon-check').innerText = '✕';
        optElement.onclick = null;
        setTimeout(() => optElement.classList.add('hidden'), 400);
    }
}

// Sang câu chưa làm kế tiếp; nếu làm hết rồi thì hiện kết quả
function nextQuestion() {
    const n = allQuestions.length;
    for (let k = 1; k <= n; k++) {
        const j = (currentIndex + k) % n;
        if (!allQuestions[j].done) { goTo(j); return; }
    }
    showResults();
}

function showResults() {
    stopTimer();
    DOM.content.style.display = 'none';
    DOM.progressContainer.style.display = 'none';
    DOM.results.style.display = 'block';

    const ok = countCorrect();
    const total = totalUniqueQuestions;
    const accuracy = total > 0 ? Math.round(ok / total * 100) : 0;
    document.getElementById('scorePercent').innerText = accuracy + '%';

    let detail = `Bạn đã trả lời đúng ${ok}/${total} câu hỏi ở lần thử đầu tiên.\nSố lần chọn sai: ${totalWrongClicks}.\nThời gian làm bài: ${fmtTime(Date.now() - startTime)}.`;
    if (timeUp) {
        document.getElementById('resultTitle').innerText = '⏰ Hết giờ!';
        detail = `Bạn hoàn thành ${countDone()}/${total} câu, đúng ngay lần đầu ${ok} câu.\nCòn ${total - countDone()} câu chưa làm.\nSố lần chọn sai: ${totalWrongClicks}.`;
    } else {
        launchFireworks();
    }
    document.getElementById('scoreDetail').innerText = detail;
}

// ================= ĐỒNG HỒ ĐẾM NGƯỢC =================
const TIMER = { box: document.getElementById('timerBox'), text: document.getElementById('timerText') };
let timerId = null;
let endTime = 0;

function fmtTime(ms) {
    const sec = Math.max(0, Math.round(ms / 1000));
    return String(Math.floor(sec / 60)).padStart(2, '0') + ':' + String(sec % 60).padStart(2, '0');
}

function startTimer(ms) {
    endTime = Date.now() + ms;
    TIMER.box.style.display = 'flex';
    tickTimer();
    timerId = setInterval(tickTimer, 250);
}

function tickTimer() {
    const left = endTime - Date.now();
    TIMER.text.textContent = fmtTime(Math.ceil(left / 1000) * 1000);
    TIMER.box.classList.toggle('danger', left <= 60000);
    if (left <= 0) {
        timeUp = true;
        showResults();
    }
}

function stopTimer() {
    if (timerId) { clearInterval(timerId); timerId = null; }
}

// ================= SỬA NỘI DUNG CÂU HỎI (dùng QEditor dùng chung) =================
document.getElementById('btnEditQ').onclick = function () {
    if (!currentQ || timeUp) return;
    this.blur();
    QEditor.open(currentQ, 'câu ' + (currentIndex + 1), function (u) {
        // currentQ cũng chính là phần tử của questionPool nên cập nhật tại chỗ
        const correctChanged = u.correct_opt !== currentQ.correct_opt;
        currentQ.question_text = u.question_text;
        currentQ.opt_a = u.opt_a; currentQ.opt_b = u.opt_b;
        currentQ.opt_c = u.opt_c; currentQ.opt_d = u.opt_d;
        currentQ.correct_opt = u.correct_opt;
        currentQ.explanation = u.explanation;

        // Đổi đáp án đúng -> kết quả đã làm trước đó không còn đúng nghĩa, cho làm lại câu này
        if (correctChanged) {
            currentQ.done = false;
            currentQ.firstTry = false;
            currentQ.wrongOpts = [];
        }
        renderQuestion();
        updateTree(false);
    });
};

// ================= PHÍM TẮT =================
document.addEventListener('keydown', e => {
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    if (QEditor.isOpen()) return; // đang sửa câu hỏi -> không bắt phím tắt làm bài
    const tag = e.target.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
    if (!quizStarted || !allQuestions.length || DOM.results.style.display === 'block') return;

    if (e.key >= '1' && e.key <= '4') {
        const letter = OPT_LETTERS[parseInt(e.key, 10) - 1];
        const el = DOM.options.querySelector(`[data-opt="${letter}"]`);
        if (el && !el.classList.contains('hidden') && !el.classList.contains('disabled')) {
            e.preventDefault();
            selectOption(letter, el);
        }
    } else if (e.key === 'Enter' && DOM.nextBtn.style.display === 'block') {
        e.preventDefault();
        nextQuestion();
    }
});

// ================= TRỒNG CÂY =================
const TREE = {
    box: document.getElementById('quizTree'),
    plant: document.getElementById('treePlant'),
    fruit: document.getElementById('treeFruit'),
    label: document.getElementById('treeLabel')
};

function updateTree(animate) {
    const ok = countCorrect();
    const ratio = totalUniqueQuestions > 0 ? ok / totalUniqueQuestions : 0;
    TREE.plant.style.transform = `scale(${0.15 + ratio * 0.85})`;
    TREE.fruit.style.opacity = ratio >= 1 ? 1 : 0;

    let icon = '🌱';
    if (ratio >= 1) icon = '🍎';
    else if (ratio >= 0.5) icon = '🌳';
    else if (ratio > 0) icon = '🌿';
    TREE.label.innerText = `${icon} ${ok}/${totalUniqueQuestions}`;

    if (animate) {
        TREE.box.classList.remove('pop');
        void TREE.box.offsetWidth;
        TREE.box.classList.add('pop');
    }
}

// ================= PHÁO HOA =================
const fwCanvas = document.getElementById('fireworksCanvas');
const fwCtx = fwCanvas.getContext('2d');
let fwParticles = [];
let fwRunning = false;

function fwResize() {
    fwCanvas.width = window.innerWidth;
    fwCanvas.height = window.innerHeight;
}
fwResize();
window.addEventListener('resize', fwResize);

function fwBurst(x, y) {
    const hue = Math.random() * 360;
    for (let i = 0; i < 60; i++) {
        const angle = Math.random() * Math.PI * 2;
        const speed = Math.random() * 5 + 1.5;
        fwParticles.push({
            x, y,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed,
            life: 1,
            decay: Math.random() * 0.015 + 0.012,
            color: `hsl(${hue + Math.random() * 40}, 100%, 60%)`
        });
    }
}

function fwLoop() {
    fwCtx.clearRect(0, 0, fwCanvas.width, fwCanvas.height);
    fwParticles.forEach(p => {
        p.x += p.vx;
        p.y += p.vy;
        p.vy += 0.06;          // trọng lực
        p.vx *= 0.99;
        p.life -= p.decay;
        fwCtx.globalAlpha = Math.max(p.life, 0);
        fwCtx.fillStyle = p.color;
        fwCtx.beginPath();
        fwCtx.arc(p.x, p.y, 2.5, 0, Math.PI * 2);
        fwCtx.fill();
    });
    fwParticles = fwParticles.filter(p => p.life > 0);
    fwCtx.globalAlpha = 1;

    if (fwParticles.length > 0) {
        requestAnimationFrame(fwLoop);
    } else {
        fwRunning = false;
        fwCtx.clearRect(0, 0, fwCanvas.width, fwCanvas.height);
    }
}

function launchFireworks() {
    const w = fwCanvas.width, h = fwCanvas.height;
    for (let i = 0; i < 4; i++) {
        setTimeout(() => {
            fwBurst(w * (0.2 + Math.random() * 0.6), h * (0.15 + Math.random() * 0.4));
            if (!fwRunning) { fwRunning = true; fwLoop(); }
        }, i * 250);
    }
}

// ================= THIẾT LẬP BÀI LÀM (CHỌN NGẪU NHIÊN xx CÂU TRONG yy PHÚT) =================
const SETUP = {
    box: document.getElementById('setupScreen'),
    count: document.getElementById('setupCount'),
    minutes: document.getElementById('setupMinutes'),
    shuffle: document.getElementById('setupShuffle'),
    layout: document.getElementById('quizLayout')
};

function shuffleArray(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

function addPresets(containerId, values, input, suffix) {
    const wrap = document.getElementById(containerId);
    values.forEach(v => {
        const b = document.createElement('button');
        b.type = 'button';
        b.textContent = v.label;
        b.onclick = () => { input.value = v.value; };
        wrap.appendChild(b);
    });
}

function startQuiz() {
    const total = questionPool.length;
    let n = parseInt(SETUP.count.value, 10);
    if (isNaN(n) || n < 1) n = total;
    n = Math.min(n, total);

    let mins = parseFloat(SETUP.minutes.value);
    if (isNaN(mins) || mins < 0) mins = 0;
    mins = Math.min(mins, 600);

    const picked = questionPool.slice();
    if (SETUP.shuffle.checked) shuffleArray(picked);
    allQuestions = picked.slice(0, n);
    totalUniqueQuestions = allQuestions.length;

    allQuestions.forEach(q => { q.done = false; q.firstTry = false; q.wrongOpts = []; });
    currentIndex = 0;
    totalWrongClicks = 0;
    timeUp = false;
    quizStarted = true;
    startTime = Date.now();

    SETUP.box.style.display = 'none';
    SETUP.layout.style.display = '';
    TREE.box.style.display = '';

    if (mins > 0) startTimer(mins * 60000);
    updateTree(false);
    renderQuestion();
}

if (questionPool.length > 0) {
    document.getElementById('poolCount').innerText = questionPool.length;
    SETUP.count.max = questionPool.length;
    SETUP.count.value = questionPool.length;

    const t = questionPool.length;
    const countOpts = [5, 10, 20, 30].filter(v => v < t).map(v => ({ label: v + ' câu', value: v }));
    countOpts.push({ label: 'Tất cả (' + t + ')', value: t });
    addPresets('countPresets', countOpts, SETUP.count);
    addPresets('minutePresets', [
        { label: '5 phút', value: 5 }, { label: '10 phút', value: 10 },
        { label: '15 phút', value: 15 }, { label: '30 phút', value: 30 },
        { label: 'Không giới hạn', value: '' }
    ], SETUP.minutes);

    document.getElementById('btnStart').onclick = startQuiz;
    [SETUP.count, SETUP.minutes].forEach(el => el.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); startQuiz(); }
    }));
    SETUP.box.style.display = 'block';
    SETUP.count.focus();
} else {
    // Bài chưa có câu hỏi nào
    SETUP.layout.style.display = '';
    DOM.qText.innerText = "Chưa có câu hỏi nào trong bài thi này.";
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();$sid = $_SESSION['student_id'];$message = '';

// Xử lý Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title =$_POST['title'] ?? 'Bài trắc nghiệm mới';
    $desc =$_POST['description'] ?? '';

    // Tạo Quiz mới
    $stmt =$db->prepare("INSERT INTO quizzes (student_id, title, description) VALUES (?, ?, ?)");
    $stmt->execute([$sid, $title,$desc]);
    $quiz_id =$db->lastInsertId();

    if ($_POST['import_type'] === 'manual') {
        // Nhập thủ công
        $questions =$_POST['questions'] ?? [];
        $q_stmt =$db->prepare("INSERT INTO quiz_questions (quiz_id, question_text, opt_a, opt_b, opt_c, opt_d, correct_opt, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($questions as$q) {
            if (!empty($q['question_text'])) {$q_stmt->execute([$quiz_id,$q['question_text'], $q['opt_a'],$q['opt_b'], $q['opt_c'],$q['opt_d'], $q['correct_opt'],$q['explanation']]);
            }
        }
        $message = "Đã tạo bài trắc nghiệm thủ công thành công!";
    } elseif ($_POST['import_type'] === 'sql') {
        // Nhập bằng SQL thô
        $sql_content =$_POST['sql_content'];
        // Replace {quiz_id} placeholder in user's SQL with actual ID
        $sql_content = str_replace('{quiz_id}', $quiz_id,$sql_content);
        try {
            $db->exec($sql_content);$message = "Đã import câu hỏi từ SQL thành công!";
        } catch (PDOException $e) {$message = "Lỗi Import SQL: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Tạo bài trắc nghiệm';
$activePage = 'quiz';
include __DIR__ . '/includes/header.php'; //[cite: 4]
?>

<div class="card">
    <div class="card-header">
        <h2>Tạo bài trắc nghiệm mới</h2>
    </div>
    
    <?php if($message): ?>
        <div class="flash success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-row">
            <label>Tiêu đề bài viết</label>
            <input type="text" name="title" required placeholder="VD: Threat Modelling Assessment">
        </div>
        <div class="form-row">
            <label>Mô tả / Thẻ (Tags)</label>
            <input type="text" name="description" placeholder="VD: Component Identification, Security">
        </div>

        <div class="form-row">
            <label>Phương thức nhập câu hỏi</label>
            <select name="import_type" id="import_type" onchange="toggleImport()">
                <option value="manual">Nhập thủ công</option>
                <option value="sql">Import bằng lệnh SQL</option>
            </select>
        </div>

        <!-- Vùng nhập thủ công -->
        <div id="manual_section">
            <div id="questions_container">
                <div class="card" style="background: var(--bg-panel-2); margin-bottom: 15px;">
                    <h4>Câu hỏi 1</h4>
                    <textarea name="questions[0][question_text]" placeholder="Nội dung câu hỏi..." required></textarea>
                    <div class="form-grid" style="margin-top:10px;">
                        <input type="text" name="questions[0][opt_a]" placeholder="Đáp án A" required>
                        <input type="text" name="questions[0][opt_b]" placeholder="Đáp án B" required>
                        <input type="text" name="questions[0][opt_c]" placeholder="Đáp án C" required>
                        <input type="text" name="questions[0][opt_d]" placeholder="Đáp án D" required>
                    </div>
                    <div class="form-row" style="margin-top:10px;">
                        <label>Đáp án đúng</label>
                        <select name="questions[0][correct_opt]">
                            <option value="A">A</option><option value="B">B</option>
                            <option value="C">C</option><option value="D">D</option>
                        </select>
                    </div>
                    <textarea name="questions[0][explanation]" placeholder="Giải thích đáp án..."></textarea>
                </div>
            </div>
            <button type="button" class="btn btn-outline" onclick="addQuestion()">+ Thêm câu hỏi</button>
        </div>

        <!-- Vùng nhập SQL -->
        <div id="sql_section" style="display:none;">
            <p style="color:var(--text-dim); font-size:13px;">Dán các câu lệnh INSERT INTO `quiz_questions`. Dùng từ khóa <code>{quiz_id}</code> ở cột quiz_id để hệ thống tự map ID bài thi.</p>
            <textarea name="sql_content" rows="10" placeholder="INSERT INTO `quiz_questions` (`quiz_id`, `question_text`, `opt_a`, `opt_b`, `opt_c`, `opt_d`, `correct_opt`, `explanation`) VALUES ({quiz_id}, 'Câu hỏi?', 'A', 'B', 'C', 'D', 'A', 'Giải thích');"></textarea>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-accent">Tạo bài / Import</button>
        </div>
    </form>
</div>

<script>
let qIndex = 1;
function toggleImport() {
    let type = document.getElementById('import_type').value;
    document.getElementById('manual_section').style.display = (type === 'manual') ? 'block' : 'none';
    document.getElementById('sql_section').style.display = (type === 'sql') ? 'block' : 'none';
}
function addQuestion() {
    let html = `
        <div class="card" style="background: var(--bg-panel-2); margin-bottom: 15px;">
            <h4>Câu hỏi ${qIndex + 1}</h4>
            <textarea name="questions[${qIndex}][question_text]" placeholder="Nội dung câu hỏi..." required></textarea>
            <div class="form-grid" style="margin-top:10px;">
                <input type="text" name="questions[${qIndex}][opt_a]" placeholder="Đáp án A" required>
                <input type="text" name="questions[${qIndex}][opt_b]" placeholder="Đáp án B" required>
                <input type="text" name="questions[${qIndex}][opt_c]" placeholder="Đáp án C" required>
                <input type="text" name="questions[${qIndex}][opt_d]" placeholder="Đáp án D" required>
            </div>
            <div class="form-row" style="margin-top:10px;">
                <label>Đáp án đúng</label>
                <select name="questions[${qIndex}][correct_opt]">
                    <option value="A">A</option><option value="B">B</option>
                    <option value="C">C</option><option value="D">D</option>
                </select>
            </div>
            <textarea name="questions[${qIndex}][explanation]" placeholder="Giải thích đáp án..."></textarea>
        </div>`;
    document.getElementById('questions_container').insertAdjacentHTML('beforeend', html);
    qIndex++;
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?> <!--[cite: 3] -->
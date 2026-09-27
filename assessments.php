<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];

// Lay danh sach bai thi cua sinh vien (nguoi tao)
$stmt = $db->prepare('SELECT a.*, s.subject_code FROM assessments a LEFT JOIN subjects s ON s.id=a.subject_id WHERE a.student_id=? ORDER BY a.created_at DESC');
$stmt->execute([$sid]);
$createdAssessments = $stmt->fetchAll();

// Lay danh sach bai thi da lam
$stmt = $db->prepare('SELECT a.*, aa.id as attempt_id, aa.total_score, aa.submitted_at FROM assessments a JOIN assessment_attempts aa ON aa.assessment_id=a.id WHERE aa.student_id=? AND a.is_published=1 ORDER BY aa.submitted_at DESC');
$stmt->execute([$sid]);
$completedAssessments = $stmt->fetchAll();

$pageTitle = 'Bài thi trắc nghiệm';
$activePage = 'assessments';
$extraScripts = ['assets/js/assessments.js'];
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0;">Bài thi trắc nghiệm</h1>
    <button class="btn btn-accent" onclick="openCreateModal()">+ Tạo bài thi mới</button>
</div>

<h2 style="margin-top:24px;">Bài thi đã tạo</h2>
<div class="card">
    <table>
        <thead><tr><th>Tên bài thi</th><th>Môn học</th><th>Số câu</th><th>Điểm đạt</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($createdAssessments as $a): ?>
            <tr data-id="<?= $a['id'] ?>">
                <td><?= htmlspecialchars($a['title']) ?></td>
                <td><?= htmlspecialchars($a['subject_code'] ?? '—') ?></td>
                <td><?= (int)$a['total_questions'] ?></td>
                <td><?= number_format((float)$a['passing_score'], 0) ?>%</td>
                <td><span class="badge <?= $a['is_published'] ? 'badge-green' : 'badge-gray' ?>"><?= $a['is_published'] ? 'Đã xuất bản' : 'Nháp' ?></span></td>
                <td>
                    <a href="edit_assessment.php?id=<?= $a['id'] ?>" class="icon-btn" title="Chỉnh sửa">✏️</a>
                    <button class="icon-btn" title="Xoá" onclick="deleteAssessment(<?= $a['id'] ?>)">🗑️</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$createdAssessments): ?><div class="empty-state">Bạn chưa tạo bài thi nào.</div><?php endif; ?>
</div>

<h2 style="margin-top:24px;">Bài thi có sẵn</h2>
<div class="card">
    <table>
        <thead><tr><th>Tên bài thi</th><th>Môn học</th><th>Số câu</th><th>Điểm đạt</th><th>Lần làm gần nhất</th><th>Điểm</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($completedAssessments as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['title']) ?></td>
                <td><?= htmlspecialchars($a['subject_code'] ?? '—') ?></td>
                <td><?= (int)$a['total_questions'] ?></td>
                <td><?= number_format((float)$a['passing_score'], 0) ?>%</td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($a['submitted_at']))) ?></td>
                <td><strong><?= number_format((float)$a['total_score'], 2) ?></strong></td>
                <td>
                    <a href="take_assessment.php?id=<?= $a['id'] ?>" class="btn btn-accent btn-sm">Làm lại</a>
                    <a href="assessment_result.php?attempt_id=<?= $a['attempt_id'] ?>" class="btn btn-outline btn-sm">Xem kết quả</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$completedAssessments): ?><div class="empty-state">Bạn chưa làm bài thi nào.</div><?php endif; ?>
</div>

<!-- Modal tao bai thi moi -->
<div class="modal-backdrop" id="createModal">
    <div class="modal">
        <div class="card-header"><h2>Tạo bài thi mới</h2><span class="modal-close" onclick="document.getElementById('createModal').classList.remove('open')">&times;</span></div>
        <div style="margin-bottom:14px;">
            <button class="btn btn-accent" style="width:100%; margin-bottom:8px;" onclick="showTab('manual')">📝 Nhập tay</button>
            <button class="btn btn-outline" style="width:100%;" onclick="showTab('import')">📤 Import JSON</button>
        </div>
        
        <!-- Tab nhap tay -->
        <form id="manualForm" style="display:none;">
            <div class="form-row"><label>Tên bài thi</label><input name="title" required></div>
            <div class="form-row"><label>Mô tả (không bắt buộc)</label><textarea name="description" rows="2"></textarea></div>
            <div class="form-row"><label>Môn học</label>
                <select name="subject_id">
                    <option value="">-- Không áp dụng --</option>
                    <?php
                    $subj = $db->prepare('SELECT id, subject_code, subject_name FROM subjects WHERE student_id=?');
                    $subj->execute([$sid]);
                    foreach ($subj->fetchAll() as $s):
                    ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_code']) ?> - <?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-grid">
                <div class="form-row"><label>Điểm đạt (%)</label><input type="number" name="passing_score" value="70" min="0" max="100"></div>
                <div class="form-row"><label>Giới hạn thời gian (phút)</label><input type="number" name="time_limit_minutes" placeholder="Không áp dụng"></div>
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%">Tạo bài thi rỗng</button>
        </form>

        <!-- Tab import JSON -->
        <form id="importForm" style="display:none;">
            <div class="form-row"><label>Dán JSON hoặc chọn file</label><textarea id="jsonInput" rows="6" placeholder='{"title":"...","questions":[...]}'></textarea></div>
            <button type="submit" class="btn btn-accent" style="width:100%">Import bài thi</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];

$stmt = $db->prepare('SELECT * FROM subjects WHERE student_id = ? ORDER BY semester DESC, subject_name');
$stmt->execute([$sid]);
$subjects = $stmt->fetchAll();

$pageTitle = 'Môn học';
$activePage = 'subjects';
$extraScripts = ['assets/js/subjects.js'];
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0;">Quản lý môn học</h1>
    <button class="btn btn-accent" onclick="openSubjectModal()">+ Thêm môn học</button>
</div>

<div class="card" style="margin-top:16px;">
    <table>
        <thead>
            <tr><th>Mã môn</th><th>Tên môn</th><th>Tín chỉ</th><th>Học kỳ</th><th>Giảng viên</th><th>Hình thức</th><th>Trạng thái</th><th></th></tr>
        </thead>
        <tbody id="subjectsTableBody">
        <?php foreach ($subjects as $s): ?>
            <tr data-id="<?= $s['id'] ?>">
                <td><span class="dot" style="background:<?= htmlspecialchars($s['color']) ?>"></span> <?= htmlspecialchars($s['subject_code']) ?></td>
                <td><?= htmlspecialchars($s['subject_name']) ?></td>
                <td><?= (int)$s['credits'] ?></td>
                <td><?= htmlspecialchars($s['semester']) ?></td>
                <td><?= htmlspecialchars($s['lecturer'] ?: '—') ?></td>
                <td><?= $s['is_online'] ? '<span class="badge badge-blue">Online</span>' : '<span class="badge badge-gray">Trực tiếp</span>' ?></td>
                <td>
                    <?php 
                    $map = [
                        'ongoing' => ['Đang học','badge-blue'], 
                        'completed' => ['Passed','badge-green'], 
                        'not_started' => ['Chưa bắt đầu','badge-gray'],
                        'exempted' => ['Miễn môn','badge-purple']
                    ];
                    $status_key = $s['status'] ?? 'not_started';
                    [$label, $cls] = $map[$status_key] ?? ['Không xác định', 'badge-gray']; 
                    ?>
                    <span class="badge <?= $cls ?>"><?= $label ?></span>
                </td>
                <td>
                    <button class="icon-btn" title="Sửa" onclick='editSubject(<?= json_encode($s, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                    <button class="icon-btn" title="Xoá" onclick="deleteSubject(<?= $s['id'] ?>)">🗑️</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$subjects): ?><div class="empty-state">Chưa có môn học nào. Hãy thêm môn học đầu tiên của bạn.</div><?php endif; ?>
</div>

<!-- Modal them/sua mon hoc -->
<div class="modal-backdrop" id="subjectModal">
    <div class="modal">
        <div class="card-header">
            <h2 id="subjectModalTitle">Thêm môn học</h2>
            <span class="modal-close" onclick="closeSubjectModal()">&times;</span>
        </div>
        <form id="subjectForm">
            <input type="hidden" name="id" id="f_id">
            <div class="form-grid">
                <div class="form-row"><label>Mã môn</label><input name="subject_code" id="f_code" required></div>
                <div class="form-row"><label>Số tín chỉ</label><input type="number" name="credits" id="f_credits" value="3" required></div>
            </div>
            <div class="form-row"><label>Tên môn học</label><input name="subject_name" id="f_name" required></div>
            <div class="form-grid">
                <div class="form-row"><label>Học kỳ</label><input name="semester" id="f_semester" placeholder="Fall2026" required></div>
                <div class="form-row"><label>Giảng viên</label><input name="lecturer" id="f_lecturer"></div>
            </div>
            <div class="form-row"><label>Trạng thái</label>
                <select name="status" id="f_status">
                    <option value="not_started">Chưa bắt đầu</option>
                    <option value="ongoing">Đang học</option>
                    <option value="completed">Passed</option>
                    <option value="exempted">Miễn môn</option>
                </select>
            </div>
            <div class="form-row">
                <label><input type="checkbox" name="is_online" id="f_online" style="width:auto; display:inline-block; margin-right:6px;">Môn học Online</label>
            </div>
            <div class="form-grid" id="onlineFields" style="display:none;">
                <div class="form-row"><label>Nền tảng</label><input name="online_platform" id="f_platform" placeholder="Microsoft Teams / Zoom"></div>
                <div class="form-row"><label>Link lớp học</label><input name="online_link" id="f_link" placeholder="https://..."></div>
            </div>
            <div class="form-row"><label>Màu hiển thị</label><input type="color" name="color" id="f_color" value="#8bc53f" style="height:38px; padding:4px;"></div>
            <button type="submit" class="btn btn-accent" style="width:100%">Lưu môn học</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
```<FollowUp></FollowUp>
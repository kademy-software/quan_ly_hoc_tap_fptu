<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];

$subjStmt = $db->prepare('SELECT id, subject_code, subject_name FROM subjects WHERE student_id = ? ORDER BY subject_name');
$subjStmt->execute([$sid]);
$subjects = $subjStmt->fetchAll();

$stmt = $db->prepare('SELECT f.*, s.subject_code FROM fap_submissions f LEFT JOIN subjects s ON s.id = f.subject_id WHERE f.student_id = ? ORDER BY f.submit_date DESC');
$stmt->execute([$sid]);
$submissions = $stmt->fetchAll();

$statusMeta = [
    'pending'    => ['Chờ xử lý', 'badge-orange'],
    'processing' => ['Đang xử lý', 'badge-blue'],
    'approved'   => ['Đã duyệt', 'badge-green'],
    'rejected'   => ['Từ chối', 'badge-red'],
];

$formTypes = ['Đơn xin nghỉ học', 'Đơn phúc khảo điểm', 'Đơn bảo lưu kết quả', 'Đơn xin học lại', 'Đơn xin rút môn', 'Đơn khác'];

$pageTitle = 'Đơn FAP';
$activePage = 'fap';
$extraScripts = ['assets/js/fap.js'];
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0;">Đơn gửi trên FAP</h1>
    <button class="btn btn-accent" onclick="openFapModal()">+ Tạo đơn mới</button>
</div>
<p style="color:var(--text-dim); margin-top:-8px;">Theo dõi trạng thái các đơn từ đã gửi trên hệ thống FAP (fap.fpt.edu.vn) của bạn.</p>

<div class="card" style="margin-top:16px;">
    <table>
        <thead><tr><th>Loại đơn</th><th>Môn liên quan</th><th>Ngày gửi</th><th>Trạng thái</th><th>Ghi chú</th><th></th></tr></thead>
        <tbody id="fapTableBody">
        <?php foreach ($submissions as $f): [$label, $cls] = $statusMeta[$f['status']]; ?>
            <tr data-id="<?= $f['id'] ?>">
                <td><?= htmlspecialchars($f['form_type']) ?></td>
                <td><?= htmlspecialchars($f['subject_code'] ?? '—') ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($f['submit_date']))) ?></td>
                <td>
                    <select onchange="updateFapStatus(<?= $f['id'] ?>, this.value)" style="width:auto; padding:4px 8px; font-size:12px;">
                        <?php foreach ($statusMeta as $val => [$lbl, $c]): ?>
                            <option value="<?= $val ?>" <?= $f['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><?= htmlspecialchars($f['note'] ?: '—') ?></td>
                <td><button class="icon-btn" onclick="deleteFap(<?= $f['id'] ?>)">🗑️</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$submissions): ?><div class="empty-state">Bạn chưa gửi đơn nào.</div><?php endif; ?>
</div>

<div class="modal-backdrop" id="fapModal">
    <div class="modal">
        <div class="card-header"><h2>Tạo đơn FAP</h2><span class="modal-close" onclick="document.getElementById('fapModal').classList.remove('open')">&times;</span></div>
        <form id="fapForm">
            <div class="form-row"><label>Loại đơn</label>
                <select name="form_type" required>
                    <?php foreach ($formTypes as $t): ?><option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-row"><label>Môn học liên quan (nếu có)</label>
                <select name="subject_id">
                    <option value="">-- Không áp dụng --</option>
                    <?php foreach ($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_code']) ?> - <?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-row"><label>Ngày gửi</label><input type="date" name="submit_date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="form-row"><label>Ghi chú</label><textarea name="note" rows="3" placeholder="Lý do / nội dung đơn..."></textarea></div>
            <button type="submit" class="btn btn-accent" style="width:100%">Lưu đơn</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

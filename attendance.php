<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];

$subjStmt = $db->prepare('SELECT id, subject_code, subject_name, color FROM subjects WHERE student_id = ? ORDER BY subject_name');
$subjStmt->execute([$sid]);
$subjects = $subjStmt->fetchAll();

$recStmt = $db->prepare('SELECT a.*, s.subject_code, s.subject_name, s.color FROM attendance a JOIN subjects s ON s.id = a.subject_id WHERE s.student_id = ? ORDER BY a.session_date DESC, a.slot_number');
$recStmt->execute([$sid]);
$records = $recStmt->fetchAll();

$statusMeta = [
    'present' => ['Có mặt', 'badge-green'],
    'late'    => ['Đi trễ', 'badge-orange'],
    'absent'  => ['Vắng', 'badge-red'],
    'excused' => ['Vắng có phép', 'badge-gray'],
];

$pageTitle = 'Điểm danh';
$activePage = 'attendance';
$extraScripts = ['assets/js/attendance.js'];
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0;">Quản lý điểm danh</h1>
    <button class="btn btn-accent" onclick="openAttendanceModal()">+ Ghi nhận điểm danh</button>
</div>

<div class="grid grid-3" style="margin:16px 0;">
<?php foreach ($subjects as $s):
    $subTotal = array_filter($records, fn($r) => $r['subject_code'] === $s['subject_code']);
    $total = count($subTotal);
    $present = count(array_filter($subTotal, fn($r) => $r['status'] === 'present'));
    $rate = $total ? round($present / $total * 100) : 0;
?>
    <div class="card">
        <div class="progress-row"><span><span class="dot" style="background:<?= htmlspecialchars($s['color']) ?>"></span> <?= htmlspecialchars($s['subject_code']) ?></span><span class="num"><?= $rate ?>%</span></div>
        <div class="progress-track"><div class="progress-fill" style="width:<?= $rate ?>%"></div></div>
    </div>
<?php endforeach; ?>
</div>

<div class="card">
    <table>
        <thead><tr><th>Ngày</th><th>Slot</th><th>Môn học</th><th>Trạng thái</th><th>Ghi chú</th><th></th></tr></thead>
        <tbody id="attendanceTableBody">
        <?php foreach ($records as $r): [$label, $cls] = $statusMeta[$r['status']]; ?>
            <tr data-id="<?= $r['id'] ?>">
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['session_date']))) ?></td>
                <td>Slot <?= (int)$r['slot_number'] ?></td>
                <td><span class="dot" style="background:<?= htmlspecialchars($r['color']) ?>"></span> <?= htmlspecialchars($r['subject_name']) ?></td>
                <td><span class="badge <?= $cls ?>"><?= $label ?></span></td>
                <td><?= htmlspecialchars($r['note'] ?: '—') ?></td>
                <td><button class="icon-btn" onclick="deleteAttendance(<?= $r['id'] ?>)">🗑️</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$records): ?><div class="empty-state">Chưa có bản ghi điểm danh nào.</div><?php endif; ?>
</div>

<div class="modal-backdrop" id="attendanceModal">
    <div class="modal">
        <div class="card-header"><h2>Ghi nhận điểm danh</h2><span class="modal-close" onclick="document.getElementById('attendanceModal').classList.remove('open')">&times;</span></div>
        <form id="attendanceForm">
            <div class="form-row"><label>Môn học</label>
                <select name="subject_id" required>
                    <?php foreach ($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_code']) ?> - <?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-grid">
                <div class="form-row"><label>Ngày</label><input type="date" name="session_date" value="<?= date('Y-m-d') ?>" required></div>
                <div class="form-row"><label>Slot</label>
                    <select name="slot_number">
                        <?php for ($i = 1; $i <= 10; $i++): ?><option value="<?= $i ?>">Slot <?= $i ?></option><?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="form-row"><label>Trạng thái</label>
                <select name="status">
                    <option value="present">Có mặt</option>
                    <option value="late">Đi trễ</option>
                    <option value="absent">Vắng</option>
                    <option value="excused">Vắng có phép</option>
                </select>
            </div>
            <div class="form-row"><label>Ghi chú</label><input name="note" placeholder="Không bắt buộc"></div>
            <button type="submit" class="btn btn-accent" style="width:100%">Lưu</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

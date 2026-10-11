<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/fap_sync.php';
requireLogin();
$db  = getDB();
$sid = (int)$_SESSION['student_id'];

$fap     = fapGetRow($db, $sid);
$syncing = isset($_GET['fap_sync']);
$importing = isset($_GET['fap_import']);
$showAll = isset($_GET['all']);
$apiBase = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$sql = 'SELECT fs.*, s.subject_name, s.color
        FROM fap_sessions fs
        LEFT JOIN subjects s ON s.id = fs.subject_id
        WHERE fs.student_id = ?' . ($showAll ? '' : ' AND fs.session_date >= CURDATE()') . '
        ORDER BY fs.session_date, fs.slot_number';
$stmt = $db->prepare($sql);
$stmt->execute([$sid]);
$sessions = $stmt->fetchAll();

$dayNames = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'CN'];
$statusLabel = ['attended' => '✔ Đã học', 'absent' => '✖ Vắng', '' => '—'];
$today = date('Y-m-d');

$pageTitle  = 'Lịch học';
$activePage = 'schedule';
include __DIR__ . '/includes/header.php';
?>

<h1>Lịch học</h1>
<p style="color:var(--text-dim); margin-top:-8px;">Lịch được lấy từ FAP theo từng buổi cụ thể. Bấm nút bên dưới để cập nhật.</p>

<div class="card" style="margin-bottom:16px;">
    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <button type="button" class="btn" id="fapSyncBtn" <?= ($syncing || $importing) ? 'disabled' : '' ?>>Cập nhật dữ liệu từ Fap</button>
        <span id="fapSyncStatus" style="color:var(--text-dim);">
            <?php if ($importing): ?>Đang lưu lịch học từ FAP…
            <?php elseif ($syncing): ?>Đang chờ Automa lấy dữ liệu từ FAP…
            <?php elseif ($fap['last_sync_at']): ?>Cập nhật lần cuối: <?= htmlspecialchars($fap['last_sync_at']) ?> — <?= htmlspecialchars($fap['last_message'] ?? '') ?>
            <?php else: ?>Chưa cập nhật lần nào.<?php endif; ?>
        </span>
    </div>
    <details style="margin-top:10px;">
        <summary style="cursor:pointer; color:var(--text-dim);">Thông tin kết nối Automa (dán vào Data của workflow)</summary>
        <pre style="white-space:pre-wrap; user-select:all;">{"qlht_base": "<?= htmlspecialchars($apiBase) ?>", "token": "<?= htmlspecialchars($fap['token']) ?>"}</pre>
    </details>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
        <h3 style="margin:0;"><?= $showAll ? 'Toàn bộ lịch học' : 'Lịch học sắp tới' ?> (<?= count($sessions) ?> buổi)</h3>
        <a href="schedule.php<?= $showAll ? '' : '?all=1' ?>"><?= $showAll ? 'Chỉ xem sắp tới' : 'Xem cả lịch cũ' ?></a>
    </div>

    <?php if (!$sessions): ?>
        <div class="empty-state" style="margin-top:12px;">Chưa có lịch học. Bấm “Cập nhật dữ liệu từ Fap” để lấy lịch.</div>
    <?php else: ?>
    <div style="overflow-x:auto; margin-top:12px;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="text-align:left; color:var(--text-dim);">
                    <th style="padding:8px;">Ngày</th>
                    <th style="padding:8px;">Slot</th>
                    <th style="padding:8px;">Giờ</th>
                    <th style="padding:8px;">Môn học</th>
                    <th style="padding:8px;">Phòng</th>
                    <th style="padding:8px;">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sessions as $r):
                $ts = strtotime($r['session_date']);
                $isToday = $r['session_date'] === $today; ?>
                <tr style="border-top:1px solid rgba(255,255,255,.08); <?= $isToday ? 'background:rgba(34,197,94,.12);' : '' ?>">
                    <td style="padding:8px; white-space:nowrap;">
                        <strong><?= $dayNames[(int)date('N', $ts)] ?></strong>, <?= date('d/m/Y', $ts) ?>
                        <?= $isToday ? '<small>(hôm nay)</small>' : '' ?>
                    </td>
                    <td style="padding:8px;"><?= (int)$r['slot_number'] ?></td>
                    <td style="padding:8px; white-space:nowrap;"><?= $r['start_time'] ? htmlspecialchars($r['start_time'] . ' - ' . $r['end_time']) : '—' ?></td>
                    <td style="padding:8px;">
                        <span class="subject-chip" style="display:inline-block; background:<?= htmlspecialchars($r['color'] ?: '#334155') ?>;">
                            <?= htmlspecialchars($r['subject_code']) ?>
                        </span>
                        <?= htmlspecialchars($r['subject_name'] ?? '') ?>
                    </td>
                    <td style="padding:8px;"><?= htmlspecialchars($r['room'] ?: '—') ?></td>
                    <td style="padding:8px;"><?= $statusLabel[$r['status']] ?? '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var btn = document.getElementById('fapSyncBtn');
    var statusEl = document.getElementById('fapSyncStatus');
    var lastSync = <?= json_encode($fap['last_sync_at']) ?>;

    <?php if ($importing): ?>
    (function () {
        var m = location.hash.match(/^#d=(.*)$/), sessions = null;
        try { sessions = JSON.parse(decodeURIComponent(m[1])); } catch (e) {}
        if (!Array.isArray(sessions)) { statusEl.textContent = 'Không nhận được dữ liệu từ Automa.'; btn.disabled = false; return; }
        fetch('api/fap_import.php', {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({sessions: sessions})
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d.ok) { location.replace('schedule.php'); }
            else { statusEl.textContent = 'Lỗi: ' + (d.error || 'không lưu được'); btn.disabled = false; }
        }).catch(function (e) { statusEl.textContent = 'Lỗi gửi dữ liệu: ' + e; btn.disabled = false; });
    })();
    <?php endif; ?>

    btn.addEventListener('click', function () {
        // Automa có trigger "Visit web" theo URL này -> tự chạy workflow FAP_LH
        location.href = 'schedule.php?fap_sync=1';
    });

    <?php if ($syncing): ?>
    var tries = 0;
    var timer = setInterval(function () {
        tries++;
        fetch('api/fap_import.php?action=status', {credentials: 'same-origin'})
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.ok && d.last_sync_at && d.last_sync_at !== lastSync) {
                    clearInterval(timer);
                    location.href = 'schedule.php';
                }
            }).catch(function () {});
        if (tries > 60) { // ~2 phút (có thể phải bấm "Tuần sau" nhiều lần)
            clearInterval(timer);
            statusEl.textContent = 'Chưa nhận được dữ liệu. Kiểm tra Automa đã bật, đã đăng nhập FAP và các tuần tới có lịch chưa.';
            btn.disabled = false;
        }
    }, 2000);
    <?php endif; ?>
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
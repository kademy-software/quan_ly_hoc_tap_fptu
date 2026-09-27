<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid =$_SESSION['student_id'];

// Xử lý Form Thêm / Cập nhật Flag / Xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt =$db->prepare('INSERT INTO anm_labs (student_id, platform, target_ip, ports, active_until) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$sid, $_POST['platform'],$_POST['target_ip'], $_POST['ports'],$_POST['active_until']]);
    } elseif ($_POST['action'] === 'update_flag') {
        $stmt =$db->prepare('UPDATE anm_labs SET flags_collected = ? WHERE id = ? AND student_id = ?');
        $stmt->execute([$_POST['flags'], $_POST['id'],$sid]);
    } elseif ($_POST['action'] === 'delete') {
        $stmt =$db->prepare('DELETE FROM anm_labs WHERE id = ? AND student_id = ?');
        $stmt->execute([$_POST['id'],$sid]);
    }
    header('Location: labs.php');
    exit;
}

$stmt =$db->prepare('SELECT * FROM anm_labs WHERE student_id = ? ORDER BY active_until DESC');
$stmt->execute([$sid]);
$labs =$stmt->fetchAll();

$pageTitle = 'Lab Environment';$activePage = 'labs';
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0; color: var(--red);">Quản lý Lab & Máy chủ</h1>
    <button class="btn btn-accent" onclick="document.getElementById('labModal').classList.add('open')">+ Add Target</button>
</div>
<p style="color:var(--text-dim); margin-top:-8px;">Theo dõi Target IP, đếm ngược thời gian máy ảo và lưu trữ Flag CTF.</p>

<div class="grid grid-3" style="margin-top:20px;">
    <?php foreach ($labs as$lab): 
        $isActive = strtotime($lab['active_until']) > time();
    ?>
    <div class="card" style="border-top: 4px solid <?= $isActive ? 'var(--red)' : 'var(--text-dim)' ?>;">
        <div class="card-header" style="margin-bottom: 8px;">
            <strong style="font-size: 16px;"><?= htmlspecialchars($lab['platform']) ?></strong>
            <span class="badge <?= $isActive ? 'badge-red' : 'badge-gray' ?>" 
                  data-time="<?= htmlspecialchars($lab['active_until']) ?>" 
                  class="countdown-timer">
                <?= $isActive ? 'Đang tính...' : 'Expired' ?>
            </span>
        </div>
        
        <!-- Khối hiển thị IP và Nút Copy -->
        <div style="background: var(--bg); border: 1px solid var(--border); border-radius: 6px; padding: 10px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
            <code style="font-size: 16px; color: var(--accent); font-weight: bold;" id="ip-<?= $lab['id'] ?>">
                <?= htmlspecialchars($lab['target_ip']) ?>
            </code>
            <button class="btn btn-outline btn-sm" onclick="copyIP('ip-<?= $lab['id'] ?>')">Copy IP</button>
        </div>

        <div style="font-size: 13px; color: var(--text-dim); margin-bottom: 12px;">
            <strong>Ports:</strong> <?= htmlspecialchars($lab['ports'] ?: 'Chưa rà quét') ?>
        </div>

        <!-- Form lưu Flag -->
        <form method="post" style="margin-bottom: 12px;">
            <input type="hidden" name="action" value="update_flag">
            <input type="hidden" name="id" value="<?= $lab['id'] ?>">
            <textarea name="flags" rows="2" placeholder="CTF{...}" style="font-family: monospace; font-size: 12px; margin-bottom: 6px;"><?= htmlspecialchars($lab['flags_collected'] ?? '') ?></textarea>
            <button type="submit" class="btn btn-outline btn-sm" style="width: 100%;">Lưu Flag</button>
        </form>

        <form method="post" onsubmit="return confirm('Xóa Target này?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $lab['id'] ?>">
            <button type="submit" class="icon-btn" style="width: 100%; text-align: center;">🗑️ Hủy máy (Terminate)</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<?php if (!$labs): ?><div class="empty-state card">Chưa có Target IP nào đang hoạt động.</div><?php endif; ?>

<!-- Modal Thêm Target -->
<div class="modal-backdrop" id="labModal">
    <div class="modal">
        <div class="card-header">
            <h2>Add Target Machine</h2>
            <span class="modal-close" onclick="document.getElementById('labModal').classList.remove('open')">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <label>Nền tảng (Platform)</label>
                <input type="text" name="platform" required placeholder="TryHackMe, HackTheBox, VulnHub...">
            </div>
            <div class="form-row">
                <label>Target IP</label>
                <input type="text" name="target_ip" required placeholder="10.10.x.x">
            </div>
            <div class="form-grid">
                <div class="form-row">
                    <label>Open Ports (Tùy chọn)</label>
                    <input type="text" name="ports" placeholder="22, 80, 443...">
                </div>
                <div class="form-row">
                    <label>Thời gian hết hạn (Active Until)</label>
                    <input type="datetime-local" name="active_until" required>
                </div>
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%; background: var(--red); color: #fff;">Khởi tạo Target</button>
        </form>
    </div>
</div>

<script>
// Chức năng Copy IP
function copyIP(elementId) {
    var ipText = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(ipText).then(function() {
        alert("Đã copy: " + ipText);
    });
}

// Chức năng đếm ngược thời gian
function updateTimers() {
    const timers = document.querySelectorAll('.countdown-timer');
    const now = new Date().getTime();

    timers.forEach(timer => {
        const targetDate = new Date(timer.getAttribute('data-time')).getTime();
        const distance = targetDate - now;

        if (distance > 0) {
            const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((distance % (1000 * 60)) / 1000);
            timer.innerText = `${h}h ${m}m ${s}s`;
        } else {
            timer.innerText = "Expired";
            timer.classList.remove('badge-red');
            timer.classList.add('badge-gray');
        }
    });
}
setInterval(updateTimers, 1000);
updateTimers();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
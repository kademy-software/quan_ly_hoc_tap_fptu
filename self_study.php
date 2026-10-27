<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];

// Xử lý các thao tác Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $db->prepare('INSERT INTO self_study_modules (student_id, module_name, platform, module_link, target_date) VALUES (?, ?, ?, ?, ?)');
        $target = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
        $stmt->execute([$sid, $_POST['module_name'], $_POST['platform'], $_POST['module_link'], $target]);
    } elseif ($_POST['action'] === 'update_status') {
        $stmt = $db->prepare('UPDATE self_study_modules SET status = ? WHERE id = ? AND student_id = ?');
        $stmt->execute([$_POST['status'], $_POST['id'], $sid]);
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $db->prepare('DELETE FROM self_study_modules WHERE id = ? AND student_id = ?');
        $stmt->execute([$_POST['id'], $sid]);
    }
    header('Location: self_study.php');
    exit;
}

// Lấy danh sách module
$stmt = $db->prepare('SELECT * FROM self_study_modules WHERE student_id = ? ORDER BY status = "completed", target_date ASC');
$stmt->execute([$sid]);
$modules = $stmt->fetchAll();

$statusMeta = [
    'pending'     => ['Chưa bắt đầu', 'badge-gray'],
    'in_progress' => ['Đang học', 'badge-blue'],
    'completed'   => ['Hoàn thành', 'badge-green'],
];

$pageTitle = 'Tự học';
$activePage = 'self_study';
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0;">Các Module Tự học</h1>
    <button class="btn btn-accent" onclick="document.getElementById('moduleModal').classList.add('open')">+ Thêm Module</button>
</div>
<p style="color:var(--text-dim); margin-top:-8px;">Theo dõi tiến độ tự học các kỹ năng ngoài chương trình học chính thức (VD: TryHackMe, Coursera...).</p>

<div class="card" style="margin-top:16px;">
    <table>
        <thead>
            <tr>
                <th>Module / Khóa học</th>
                <th>Nền tảng</th>
                <th>Mục tiêu hoàn thành</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($modules as $m):
            [$label, $cls] = $statusMeta[$m['status']];
        ?>
            <tr style="<?= $m['status'] === 'completed' ? 'opacity: 0.6;' : '' ?>">
                <td>
                    <strong style="<?= $m['status'] === 'completed' ? 'text-decoration: line-through;' : '' ?>">
                        <?= htmlspecialchars($m['module_name']) ?>
                    </strong>
                    <?php if ($m['module_link']): ?>
                        <br><a href="<?= htmlspecialchars($m['module_link']) ?>" target="_blank" rel="noopener" style="font-size:12px; color:var(--blue);"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Đi tới khóa học</a>
                    <?php endif; ?>
                </td>
                <td>
                    <?= htmlspecialchars($m['platform'] ?: '—') ?>
                </td>
                <td>
                    <?= $m['target_date'] ? date('d/m/Y', strtotime($m['target_date'])) : '—' ?>
                </td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                        <select name="status" onchange="this.form.submit()" style="width:auto; padding:4px 8px; font-size:12px;">
                            <?php foreach ($statusMeta as $val => [$lbl, $c]): ?>
                                <option value="<?= $val ?>" <?= $m['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Xóa module này?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                        <button type="submit" class="icon-btn" title="Xóa" aria-label="Xóa module"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$modules): ?><div class="empty-state">Bạn chưa thêm module tự học nào.</div><?php endif; ?>
</div>

<!-- Modal Thêm Module -->
<div class="modal-backdrop" id="moduleModal">
    <div class="modal">
        <div class="card-header">
            <h2>Thêm Module Tự học</h2>
            <span class="modal-close" onclick="document.getElementById('moduleModal').classList.remove('open')">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <label>Tên Module / Khóa học</label>
                <input type="text" name="module_name" required placeholder="VD: Linux Fundamentals Part 1">
            </div>
            <div class="form-grid">
                <div class="form-row">
                    <label>Nền tảng (Platform)</label>
                    <input type="text" name="platform" placeholder="VD: TryHackMe, Coursera...">
                </div>
                <div class="form-row">
                    <label>Ngày mục tiêu hoàn thành</label>
                    <input type="date" name="target_date">
                </div>
            </div>
            <div class="form-row">
                <label>Đường dẫn (Link)</label>
                <input type="url" name="module_link" placeholder="https://...">
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%">Lưu Module</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
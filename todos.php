<<<<<<< HEAD
<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];

// Xử lý các thao tác Thêm / Đổi trạng thái / Xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $db->prepare('INSERT INTO deadlines (student_id, subject_id, title, description, due_date) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$sid, $_POST['subject_id'] ?: null, $_POST['title'], $_POST['description'], $_POST['due_date']]);
    } elseif ($_POST['action'] === 'toggle') {
        $stmt = $db->prepare('UPDATE deadlines SET status = IF(status="pending", "completed", "pending") WHERE id = ? AND student_id = ?');
        $stmt->execute([$_POST['id'], $sid]);
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $db->prepare('DELETE FROM deadlines WHERE id = ? AND student_id = ?');
        $stmt->execute([$_POST['id'], $sid]);
    }
    header('Location: todos.php');
    exit;
}

// Lấy danh sách môn học cho Form
$subjStmt = $db->prepare('SELECT id, subject_code, subject_name FROM subjects WHERE student_id = ? ORDER BY subject_name');
$subjStmt->execute([$sid]);
$subjects = $subjStmt->fetchAll();

// Lấy danh sách việc cần làm
$stmt = $db->prepare('
    SELECT d.*, s.subject_code, s.color 
    FROM deadlines d 
    LEFT JOIN subjects s ON d.subject_id = s.id 
    WHERE d.student_id = ? 
    ORDER BY d.status = "completed", d.due_date ASC
');
$stmt->execute([$sid]);
$todos = $stmt->fetchAll();

$pageTitle = 'Việc cần làm';
$activePage = 'todos';
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0;">Việc cần làm (Deadlines)</h1>
    <button class="btn btn-accent" onclick="document.getElementById('todoModal').classList.add('open')">+ Thêm công việc</button>
</div>
<p style="color:var(--text-dim); margin-top:-8px;">Quản lý bài tập, deadline dự án và các công việc cá nhân.</p>

<div class="card" style="margin-top:16px;">
    <table>
        <thead><tr><th>Trạng thái</th><th>Tiêu đề</th><th>Môn học</th><th>Hạn chót</th><th>Ghi chú</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($todos as $t): 
            $isOverdue = (strtotime($t['due_date']) < time() && $t['status'] === 'pending');
        ?>
            <tr style="<?= $t['status'] === 'completed' ? 'opacity: 0.6;' : '' ?>">
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <input type="checkbox" onchange="this.form.submit()" <?= $t['status'] === 'completed' ? 'checked' : '' ?> style="width:18px; height:18px; cursor:pointer;">
                    </form>
                </td>
                <td>
                    <strong style="<?= $t['status'] === 'completed' ? 'text-decoration: line-through;' : '' ?>">
                        <?= htmlspecialchars($t['title']) ?>
                    </strong>
                </td>
                <td>
                    <?php if ($t['subject_code']): ?>
                        <span class="badge badge-gray"><span class="dot" style="background:<?= htmlspecialchars($t['color']) ?>; margin-right:4px;"></span><?= htmlspecialchars($t['subject_code']) ?></span>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td style="<?= $isOverdue ? 'color: var(--red); font-weight: bold;' : '' ?>">
                    <?= date('H:i d/m/Y', strtotime($t['due_date'])) ?>
                    <?php if ($isOverdue) echo ' <span style="font-size:11px;">(Trễ)</span>'; ?>
                </td>
                <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($t['description'] ?: '—') ?>
                </td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Xóa công việc này?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <button type="submit" class="icon-btn">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$todos): ?><div class="empty-state">Bạn không có việc cần làm nào. 🎉</div><?php endif; ?>
</div>

<!-- Modal Thêm Công Việc -->
<div class="modal-backdrop" id="todoModal">
    <div class="modal">
        <div class="card-header">
            <h2>Thêm việc cần làm</h2>
            <span class="modal-close" onclick="document.getElementById('todoModal').classList.remove('open')">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <label>Tiêu đề</label>
                <input type="text" name="title" required placeholder="Ví dụ: Làm quiz 1, Nộp assignment...">
            </div>
            <div class="form-row">
                <label>Môn học (Không bắt buộc)</label>
                <select name="subject_id">
                    <option value="">-- Không áp dụng --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_code']) ?> - <?= htmlspecialchars($s['subject_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label>Hạn chót (Thời gian)</label>
                <input type="datetime-local" name="due_date" required>
            </div>
            <div class="form-row">
                <label>Ghi chú thêm</label>
                <textarea name="description" rows="3" placeholder="Chi tiết yêu cầu..."></textarea>
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%">Lưu công việc</button>
        </form>
    </div>
</div>

=======
<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];

// Xử lý các thao tác Thêm / Đổi trạng thái / Xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $db->prepare('INSERT INTO deadlines (student_id, subject_id, title, description, due_date) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$sid, $_POST['subject_id'] ?: null, $_POST['title'], $_POST['description'], $_POST['due_date']]);
    } elseif ($_POST['action'] === 'toggle') {
        $stmt = $db->prepare('UPDATE deadlines SET status = IF(status="pending", "completed", "pending") WHERE id = ? AND student_id = ?');
        $stmt->execute([$_POST['id'], $sid]);
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $db->prepare('DELETE FROM deadlines WHERE id = ? AND student_id = ?');
        $stmt->execute([$_POST['id'], $sid]);
    }
    header('Location: todos.php');
    exit;
}

// Lấy danh sách môn học cho Form
$subjStmt = $db->prepare('SELECT id, subject_code, subject_name FROM subjects WHERE student_id = ? ORDER BY subject_name');
$subjStmt->execute([$sid]);
$subjects = $subjStmt->fetchAll();

// Lấy danh sách việc cần làm
$stmt = $db->prepare('
    SELECT d.*, s.subject_code, s.color 
    FROM deadlines d 
    LEFT JOIN subjects s ON d.subject_id = s.id 
    WHERE d.student_id = ? 
    ORDER BY d.status = "completed", d.due_date ASC
');
$stmt->execute([$sid]);
$todos = $stmt->fetchAll();

$pageTitle = 'Việc cần làm';
$activePage = 'todos';
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0;">Việc cần làm (Deadlines)</h1>
    <button class="btn btn-accent" onclick="document.getElementById('todoModal').classList.add('open')">+ Thêm công việc</button>
</div>
<p style="color:var(--text-dim); margin-top:-8px;">Quản lý bài tập, deadline dự án và các công việc cá nhân.</p>

<div class="card" style="margin-top:16px;">
    <table>
        <thead><tr><th>Trạng thái</th><th>Tiêu đề</th><th>Môn học</th><th>Hạn chót</th><th>Ghi chú</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($todos as $t): 
            $isOverdue = (strtotime($t['due_date']) < time() && $t['status'] === 'pending');
        ?>
            <tr style="<?= $t['status'] === 'completed' ? 'opacity: 0.6;' : '' ?>">
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <input type="checkbox" onchange="this.form.submit()" <?= $t['status'] === 'completed' ? 'checked' : '' ?> style="width:18px; height:18px; cursor:pointer;">
                    </form>
                </td>
                <td>
                    <strong style="<?= $t['status'] === 'completed' ? 'text-decoration: line-through;' : '' ?>">
                        <?= htmlspecialchars($t['title']) ?>
                    </strong>
                </td>
                <td>
                    <?php if ($t['subject_code']): ?>
                        <span class="badge badge-gray"><span class="dot" style="background:<?= htmlspecialchars($t['color']) ?>; margin-right:4px;"></span><?= htmlspecialchars($t['subject_code']) ?></span>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td style="<?= $isOverdue ? 'color: var(--red); font-weight: bold;' : '' ?>">
                    <?= date('H:i d/m/Y', strtotime($t['due_date'])) ?>
                    <?php if ($isOverdue) echo ' <span style="font-size:11px;">(Trễ)</span>'; ?>
                </td>
                <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($t['description'] ?: '—') ?>
                </td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Xóa công việc này?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <button type="submit" class="icon-btn">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$todos): ?><div class="empty-state">Bạn không có việc cần làm nào. 🎉</div><?php endif; ?>
</div>

<!-- Modal Thêm Công Việc -->
<div class="modal-backdrop" id="todoModal">
    <div class="modal">
        <div class="card-header">
            <h2>Thêm việc cần làm</h2>
            <span class="modal-close" onclick="document.getElementById('todoModal').classList.remove('open')">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <label>Tiêu đề</label>
                <input type="text" name="title" required placeholder="Ví dụ: Làm quiz 1, Nộp assignment...">
            </div>
            <div class="form-row">
                <label>Môn học (Không bắt buộc)</label>
                <select name="subject_id">
                    <option value="">-- Không áp dụng --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_code']) ?> - <?= htmlspecialchars($s['subject_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label>Hạn chót (Thời gian)</label>
                <input type="datetime-local" name="due_date" required>
            </div>
            <div class="form-row">
                <label>Ghi chú thêm</label>
                <textarea name="description" rows="3" placeholder="Chi tiết yêu cầu..."></textarea>
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%">Lưu công việc</button>
        </form>
    </div>
</div>

>>>>>>> 7f590bde40161d5efb0793dde28fbf210cbfdd8a
<?php include __DIR__ . '/includes/footer.php'; ?>
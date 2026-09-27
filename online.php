<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];

$stmt = $db->prepare('SELECT * FROM subjects WHERE student_id = ? AND is_online = 1 ORDER BY subject_name');
$stmt->execute([$sid]);
$onlineSubjects = $stmt->fetchAll();

$pageTitle = 'Học Online';
$activePage = 'online';
include __DIR__ . '/includes/header.php';
?>

<h1>Môn học Online</h1>
<p style="color:var(--text-dim); margin-top:-8px;">Đánh dấu môn học là "Online" trong trang <a href="subjects.php">Quản lý môn học</a> để môn đó xuất hiện tại đây.</p>

<div class="grid grid-3" style="margin-top:16px;">
<?php foreach ($onlineSubjects as $s): ?>
    <div class="online-card">
        <div class="progress-row" style="margin-bottom:0;">
            <span><span class="dot" style="background:<?= htmlspecialchars($s['color']) ?>"></span> <?= htmlspecialchars($s['subject_code']) ?></span>
            <?php $map = ['ongoing' => ['Đang học','badge-blue'], 'completed' => ['Hoàn thành','badge-green'], 'not_started' => ['Chưa bắt đầu','badge-gray']];
            [$label, $cls] = $map[$s['status']]; ?>
            <span class="badge <?= $cls ?>"><?= $label ?></span>
        </div>
        <strong style="font-size:16px;"><?= htmlspecialchars($s['subject_name']) ?></strong>
        <div class="platform"><?= htmlspecialchars($s['online_platform'] ?: 'Chưa cập nhật nền tảng') ?></div>
        <?php if ($s['online_link']): ?>
            <a href="<?= htmlspecialchars($s['online_link']) ?>" target="_blank" class="btn btn-accent" style="text-align:center;">Vào lớp học →</a>
        <?php else: ?>
            <a href="subjects.php" class="btn btn-outline" style="text-align:center;">+ Thêm link lớp học</a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>
<?php if (!$onlineSubjects): ?><div class="card"><div class="empty-state">Bạn chưa có môn học Online nào.</div></div><?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

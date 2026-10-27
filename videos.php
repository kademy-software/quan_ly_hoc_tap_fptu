<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/video_helpers.php';
requireLogin();
$db = getDB();
$sid = (int)$_SESSION['student_id'];

$watched = vk_watched($db, $sid);
$cats = [];
foreach (vk_categories() as $c) {
    $files = vk_files($c);
    $done = 0;
    foreach ($files as $f) if (isset($watched[$c . '/' . $f])) $done++;
    $cats[] = ['name' => $c, 'total' => count($files), 'done' => $done];
}

$pageTitle = 'Kho video';
$activePage = 'videos';
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0;"><i class="fa-solid fa-video" aria-hidden="true"></i> Kho video</h1>
</div>
<p style="color:var(--text-dim); margin-top:-8px;">Chọn một mục để xem danh sách video.</p>

<?php if (!$cats): ?>
    <div class="card empty-state">Chưa có mục nào. Tạo thư mục con trong <code>videos/</code> rồi bỏ file .mp4 vào.</div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px; margin-top:16px;">
<?php foreach ($cats as $c):
    $pct = $c['total'] ? round($c['done'] / $c['total'] * 100) : 0; ?>
    <a class="card" href="video_category.php?c=<?= urlencode($c['name']) ?>" style="display:block; text-decoration:none; color:inherit;">
        <div style="font-size:24px; margin-bottom:8px; color:var(--accent);"><i class="fa-solid fa-circle-play" aria-hidden="true"></i></div>
        <h3 style="margin:0 0 12px;"><?= htmlspecialchars(vk_label($c['name'])) ?></h3>
        <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--text-dim); margin-bottom:6px;">
            <span><?= $c['total'] ?> video</span><span><?= $pct ?>% đã xem</span>
        </div>
        <div style="height:6px; background:var(--border); border-radius:3px; overflow:hidden;">
            <div style="height:100%; width:<?= $pct ?>%; background:var(--accent);"></div>
        </div>
    </a>
<?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/video_helpers.php';
requireLogin();
$db = getDB();
$sid = (int)$_SESSION['student_id'];

$c = $_GET['c'] ?? '';
$f = basename($_GET['f'] ?? '');
if (!vk_valid_cat($c)) { header('Location: videos.php'); exit; }
$files = vk_files($c);
$idx = array_search($f, $files, true);
if ($idx === false) { header('Location: video_category.php?c=' . urlencode($c)); exit; }

$self = 'video_watch.php?c=' . urlencode($c) . '&f=' . urlencode($f);
$nav  = fn($x) => 'video_watch.php?c=' . urlencode($c) . '&f=' . urlencode($x);

// Đánh dấu đã xem (nút bấm, hoặc tự động khi video chạy hết qua fetch)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    vk_mark($db, $sid, $c, $f, $_POST['mode'] ?? 'toggle');
    if (($_POST['ajax'] ?? '') === '1') { echo 'ok'; exit; }
    header('Location: ' . $self);
    exit;
}

$prev = $files[$idx - 1] ?? null;
$next = $files[$idx + 1] ?? null;
$is = isset(vk_watched($db, $sid, $c)[$c . '/' . $f]);

$pageTitle = vk_label($f);
$activePage = 'videos';
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0;"><?= htmlspecialchars(vk_label($f)) ?></h1>
    <a class="btn btn-outline" href="video_category.php?c=<?= urlencode($c) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?= htmlspecialchars(vk_label($c)) ?></a>
</div>

<div class="card" style="margin-top:16px; padding:12px;">
    <video id="vkVideo" src="<?= htmlspecialchars(vk_url($c, $f)) ?>" controls preload="metadata"
           style="width:100%; max-height:70vh; background:#000; border-radius:8px; display:block;"></video>
</div>

<div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:16px;">
    <?php if ($prev): ?><a class="btn btn-outline" href="<?= htmlspecialchars($nav($prev)) ?>"><i class="fa-solid fa-backward-step" aria-hidden="true"></i> Trước</a><?php endif; ?>
    <form method="post" style="display:inline;">
        <input type="hidden" name="action" value="toggle">
        <button type="submit" class="btn btn-accent"><?= $is ? '<i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Bỏ đánh dấu' : '<i class="fa-solid fa-check" aria-hidden="true"></i> Đã xem xong' ?></button>
    </form>
    <?php if ($next): ?><a class="btn btn-outline" href="<?= htmlspecialchars($nav($next)) ?>">Sau <i class="fa-solid fa-forward-step" aria-hidden="true"></i></a><?php endif; ?>
</div>

<script>
// Nhớ vị trí xem dở; hết video thì tự đánh dấu đã xem và sang video kế tiếp
(function () {
    var v = document.getElementById('vkVideo');
    var key = 'vk_pos:' + <?= json_encode($c . '/' . $f) ?>;
    try { var p = parseFloat(localStorage.getItem(key)); if (p > 5) v.currentTime = p; } catch (e) {}
    var last = 0;
    v.addEventListener('timeupdate', function () {
        if (Date.now() - last > 3000) { last = Date.now(); try { localStorage.setItem(key, v.currentTime); } catch (e) {} }
    });
    v.addEventListener('ended', function () {
        try { localStorage.removeItem(key); } catch (e) {}
        var fd = new FormData();
        fd.append('action', 'toggle'); fd.append('mode', 'set'); fd.append('ajax', '1');
        fetch(<?= json_encode($self) ?>, { method: 'POST', body: fd }).then(function () {
            <?php if ($next): ?>location.href = <?= json_encode($nav($next)) ?>;<?php else: ?>location.reload();<?php endif; ?>
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

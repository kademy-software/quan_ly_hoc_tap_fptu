
<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/video_helpers.php';

requireLogin();

$db = getDB();
$sid = (int) $_SESSION['student_id'];

$c = $_GET['c'] ?? '';

if (!vk_valid_cat($c)) {
    header('Location: videos.php');
    exit;
}

// Đổi trạng thái đã xem
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'toggle'
) {
    $filename = basename($_POST['f'] ?? '');

    if (in_array($filename, vk_files($c), true)) {
        vk_mark($db, $sid, $c, $filename, 'toggle');
    }

    header('Location: video_category.php?c=' . urlencode($c));
    exit;
}

$files = vk_files($c);
$watched = vk_watched($db, $sid, $c);

$done = 0;

foreach ($files as $f) {
    if (isset($watched[$c . '/' . $f])) {
        $done++;
    }
}

$total = count($files);
$pct = $total ? round($done / $total * 100) : 0;

$pageTitle = vk_label($c);
$activePage = 'videos';

include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0;">
        <?= htmlspecialchars(vk_label($c), ENT_QUOTES, 'UTF-8') ?>
    </h1>
    <a class="btn" href="videos.php">← Kho video</a>
</div>

<p style="color:var(--text-dim); margin-top:-8px;">
    <?= $done ?>/<?= $total ?> video đã xem · <?= $pct ?>% hoàn thành
</p>

<div style="
    height:6px;
    background:rgba(255,255,255,.1);
    border-radius:3px;
    overflow:hidden;
    margin:12px 0 20px;
">
    <div style="
        height:100%;
        width:<?= $pct ?>%;
        background:var(--accent, #8bc53f);
    "></div>
</div>

<?php if (!$files): ?>
    <div class="card empty-state">
        Thư mục này chưa có file .mp4.
    </div>
<?php else: ?>

<div style="
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(min(100%,260px),1fr));
    gap:16px;
">

    <?php foreach ($files as $i => $f):
        $is = isset($watched[$c . '/' . $f]);
        $url = 'video_watch.php?c=' . urlencode($c)
             . '&f=' . urlencode($f);

        // Tạo hoặc lấy thumbnail từ video MP4
        $thumbnail = vk_thumbnail($c, $f);
    ?>

    <article class="card" style="
        padding:0;
        overflow:hidden;
        <?= $is ? 'opacity:.75;' : '' ?>
    ">

        <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
           style="
               display:block;
               color:inherit;
               text-decoration:none;
           ">

            <div style="
                position:relative;
                aspect-ratio:16/9;
                overflow:hidden;
                background:#1b1b1b;
            ">

                <?php if ($thumbnail): ?>
                    <img
                        src="<?= htmlspecialchars($thumbnail, ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars(vk_label($f), ENT_QUOTES, 'UTF-8') ?>"
                        loading="lazy"
                        style="
                            width:100%;
                            height:100%;
                            object-fit:cover;
                            display:block;
                        "
                    >
                <?php else: ?>
                    <div style="
                        width:100%;
                        height:100%;
                        display:grid;
                        place-items:center;
                        color:var(--text-dim);
                        font-size:36px;
                    ">▶</div>
                <?php endif; ?>

                <span style="
                    position:absolute;
                    right:8px;
                    bottom:8px;
                    padding:4px 8px;
                    border-radius:5px;
                    background:rgba(0,0,0,.75);
                    color:#fff;
                    font-size:12px;
                ">▶ Xem video</span>

                <?php if ($is): ?>
                    <span style="
                        position:absolute;
                        top:8px;
                        left:8px;
                        padding:4px 8px;
                        border-radius:5px;
                        background:var(--accent, #8bc53f);
                        color:#111;
                        font-size:11px;
                        font-weight:600;
                    ">✓ Đã xem</span>
                <?php endif; ?>

            </div>

            <div style="padding:14px 14px 8px;">
                <h3 style="
                    margin:0;
                    font-size:15px;
                    line-height:1.5;
                    overflow-wrap:anywhere;
                ">
                    <?= ($i + 1) . '. ' .
                        htmlspecialchars(vk_label($f), ENT_QUOTES, 'UTF-8') ?>
                </h3>
            </div>

        </a>

        <div style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:8px;
            padding:8px 14px 14px;
        ">

            <form method="post" style="
                display:flex;
                align-items:center;
                gap:7px;
                margin:0;
            ">
                <input type="hidden" name="action" value="toggle">
                <input
                    type="hidden"
                    name="f"
                    value="<?= htmlspecialchars($f, ENT_QUOTES, 'UTF-8') ?>"
                >
                <input
                    type="checkbox"
                    aria-label="Đánh dấu đã xem"
                    onchange="this.form.submit()"
                    <?= $is ? 'checked' : '' ?>
                    style="
                        width:18px;
                        height:18px;
                        cursor:pointer;
                    "
                >
                <span style="
                    font-size:12px;
                    color:var(--text-dim);
                ">Đã xem</span>
            </form>

            <a class="btn btn-accent"
               href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
                ▶ Xem
            </a>

        </div>

    </article>

    <?php endforeach; ?>

</div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

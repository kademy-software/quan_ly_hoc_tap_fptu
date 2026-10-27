<?php
define('VK_DIR', dirname(__DIR__) . '/videos');   // videos/<Mục>/<file>.mp4

function vk_thumbnail($category, $filename)
{
    $root = dirname(__DIR__);
    $video = $root . '/videos/' . $category . '/' . $filename;

    if (!is_file($video)) {
        return null;
    }

    $cacheDir = $root . '/videos/thumbnails';

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    $name = hash('sha256', $category . '/' . $filename);
    $thumb = $cacheDir . '/' . $name . '.jpg';

    // Chỉ tạo ảnh một lần
    if (!is_file($thumb)) {
        $cmd = 'ffmpeg -hide_banner -loglevel error -y'
            . ' -ss 3 -i ' . escapeshellarg($video)
            . ' -frames:v 1 -vf ' . escapeshellarg('scale=480:-2')
            . ' -q:v 4 ' . escapeshellarg($thumb) . ' 2>&1';

        exec($cmd, $output, $status);

        if ($status !== 0 || !is_file($thumb)) {
            return null;
        }
    }

    return 'videos/thumbnails/' . $name . '.jpg';
}
/** "01_Bai_Gioi_Thieu.mp4" -> "01 Bai Gioi Thieu" */
function vk_label(string $name): string {
    return trim(preg_replace('/[_\s]+/', ' ', pathinfo($name, PATHINFO_FILENAME)));
}

function vk_categories(): array {
    $out = [];
    foreach (glob(VK_DIR . '/*', GLOB_ONLYDIR) ?: [] as $d) $out[] = basename($d);
    natcasesort($out);
    return array_values($out);
}

/** Chống ../ : tên mục phải là thư mục con trực tiếp của videos/ */
function vk_valid_cat(string $c): bool {
    return $c !== '' && $c === basename($c) && is_dir(VK_DIR . '/' . $c);
}

function vk_files(string $c): array {
    $out = [];
    foreach (glob(VK_DIR . '/' . $c . '/*') ?: [] as $f)
        if (is_file($f) && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'mp4') $out[] = basename($f);
    natcasesort($out);
    return array_values($out);
}

function vk_url(string $c, string $f): string {
    return 'videos/' . rawurlencode($c) . '/' . rawurlencode($f);
}

/** ["Mục/file.mp4" => true, ...] */
function vk_watched(PDO $db, int $sid, ?string $cat = null): array {
    $sql = 'SELECT category, filename FROM video_watched WHERE student_id = ?';
    $args = [$sid];
    if ($cat !== null) { $sql .= ' AND category = ?'; $args[] = $cat; }
    $st = $db->prepare($sql);
    $st->execute($args);
    $set = [];
    foreach ($st->fetchAll(PDO::FETCH_NUM) as $r) $set[$r[0] . '/' . $r[1]] = true;
    return $set;
}

/** mode: set | unset | toggle */
function vk_mark(PDO $db, int $sid, string $c, string $f, string $mode = 'toggle'): void {
    if (!vk_valid_cat($c) || !in_array($f, vk_files($c), true)) return;
    $has = isset(vk_watched($db, $sid, $c)[$c . '/' . $f]);
    if ($mode === 'set' || ($mode === 'toggle' && !$has)) {
        $db->prepare('INSERT IGNORE INTO video_watched (student_id, category, filename) VALUES (?, ?, ?)')->execute([$sid, $c, $f]);
    } else {
        $db->prepare('DELETE FROM video_watched WHERE student_id = ? AND category = ? AND filename = ?')->execute([$sid, $c, $f]);
    }
}

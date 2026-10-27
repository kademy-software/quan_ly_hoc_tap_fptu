<?php
/**
 * Script CLI đồng bộ lịch học FAP sử dụng Cookie đã lưu trong CSDL
 * Cách chạy: php scripts/sync_fap.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fap_sync.php';

$db = getDB();
fapEnsureTable($db);

$sid = 1; // Mặc định sinh viên 1
$row = fapGetRow($db, $sid);

if (empty($row['fap_cookie'])) {
    echo "[!] Chưa có Cookie FAP trong cơ sở dữ liệu. Vui lòng cấu hình cookie trước.\n";
    exit(1);
}

echo "[*] Đang gửi yêu cầu tới FAP bằng Cookie đã lưu...\n";
$url = 'https://fap.fpt.edu.vn/WeeklyTimetable';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER => [
        "Cookie: " . $row['fap_cookie'],
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Language: vi,en-US;q=0.9,en;q=0.8"
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 25
]);

$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo "[x] Lỗi kết nối cURL: $curlErr\n";
    exit(1);
}

if ($httpCode === 403 || strpos($res, 'Just a moment...') !== false) {
    echo "[!] Cloudflare Managed Challenge (Turnstile) đang chặn cURL từ Server.\n";
    echo "    -> Hãy sử dụng tính năng '⚡ Đồng bộ 1 chạm (Bookmarklet)' trên giao diện schedule.php để gửi dữ liệu trực tiếp từ trình duyệt!\n";
    exit(1);
}

if (strpos($res, 'login-step-credentials') !== false) {
    echo "[x] Cookie đã hết hạn hoặc không hợp lệ. Vui lòng cập nhật Cookie FAP mới trên giao diện.\n";
    exit(1);
}

echo "[*] Tải trang thành công (HTTP $httpCode). Đang phân tích HTML...\n";
require_once __DIR__ . '/../api/fap_cookie_sync.php';
// Parse và lưu
$sessions = parseFapHtml($res);
echo "[*] Bóc tách được " . count($sessions) . " buổi học.\n";

if (!empty($sessions)) {
    $result = fapSaveSessions($db, $sid, $sessions);
    echo "[✔] Hoàn tất! Đã thêm " . ($result['added'] ?? 0) . " buổi, cập nhật " . ($result['updated'] ?? 0) . " buổi vào CSDL.\n";
}

<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/fap_sync.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-QLHT-Token');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$db = getDB();
fapEnsureTable($db);

$sid = false;
$token = $_SERVER['HTTP_X_QLHT_TOKEN'] ?? '';
if ($token !== '') {
    $st = $db->prepare('SELECT student_id FROM fap_sync WHERE token = ?');
    $st->execute([$token]);
    $sid = $st->fetchColumn();
} elseif (!empty($_SESSION['student_id'])) {
    $sid = (int)$_SESSION['student_id'];
}

if (!$sid) {
    out(['ok' => false, 'error' => 'Chưa đăng nhập hoặc token không hợp lệ'], 401);
}
$sid = (int)$sid;

$action = $_GET['action'] ?? ($_POST['action'] ?? 'status');

// 1. Lấy thông tin cấu hình và trạng thái
if ($action === 'get_cookie' || $action === 'status') {
    $row = fapGetRow($db, $sid);
    out([
        'ok'            => true,
        'has_cookie'    => !empty($row['fap_cookie']),
        'cookie'        => $row['fap_cookie'] ?? '',
        'sync_from_date'=> $row['sync_from_date'] ?? '',
        'sync_to_date'  => $row['sync_to_date'] ?? '',
        'last_sync_at'  => $row['last_sync_at'],
        'last_count'    => (int)$row['last_count'],
        'last_message'  => $row['last_message'],
    ]);
}

// 2. Lưu Cookie
if ($action === 'save_cookie') {
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $cookie = trim($body['cookie'] ?? '');
    if (empty($cookie)) {
        out(['ok' => false, 'error' => 'Vui lòng cung cấp chuỗi cookie'], 400);
    }
    fapSaveCookie($db, $sid, $cookie);
    out(['ok' => true, 'message' => 'Đã lưu Cookie FAP thành công!']);
}

// 2b. Lưu cấu hình (ngày bắt đầu và kết thúc lấy lịch)
if ($action === 'save_config') {
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $fromDate = trim($body['sync_from_date'] ?? '');
    $toDate   = trim($body['sync_to_date'] ?? '');

    if ($fromDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
        out(['ok' => false, 'error' => 'Từ ngày không hợp lệ, phải theo định dạng YYYY-MM-DD'], 400);
    }
    if ($toDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
        out(['ok' => false, 'error' => 'Đến ngày không hợp lệ, phải theo định dạng YYYY-MM-DD'], 400);
    }
    if ($fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
        out(['ok' => false, 'error' => '"Từ ngày" phải nhỏ hơn hoặc bằng "Đến ngày"'], 400);
    }

    fapSaveConfig($db, $sid, $fromDate === '' ? null : $fromDate, $toDate === '' ? null : $toDate);

    if (!empty($body['cookie'])) {
        fapSaveCookie($db, $sid, trim($body['cookie']));
    }

    $row = fapGetRow($db, $sid);
    out([
        'ok'             => true,
        'message'        => 'Đã lưu cấu hình thành công!',
        'sync_from_date' => $row['sync_from_date'] ?? '',
        'sync_to_date'   => $row['sync_to_date'] ?? '',
    ]);
}

// 3. Parse HTML giữ nguyên hàm parseFapHtml từ file gốc...
function parseFapHtml(string $html): array {
    $sessions = [];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();

    $tables = $doc->getElementsByTagName('table');
    if ($tables->length === 0) return $sessions;

    $xpath = new DOMXPath($doc);
    $datesByCol = [];
    $headers = $xpath->query('//table//tr[1]//th | //table//tr[1]//td');
    $currentYear = date('Y');

    $colIdx = 0;
    foreach ($headers as $th) {
        $text = trim($th->textContent);
        if (preg_match('/(\d{1,2})\/(\d{1,2})(?:\/(\d{4}))?/', $text, $m)) {
            $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $y = !empty($m[3]) ? $m[3] : $currentYear;
            $datesByCol[$colIdx] = "$y-$mo-$d";
        }
        $colIdx++;
    }

    $rows = $xpath->query('//table//tr');
    $currentSlot = 0;

    foreach ($rows as $rowIndex => $tr) {
        if ($rowIndex === 0) continue;
        $cells = $tr->getElementsByTagName('td');
        if ($cells->length === 0) continue;

        $firstText = trim($cells->item(0)->textContent);
        if (preg_match('/(?:Slot|Ca)\s*(\d+)/i', $firstText, $sm) || preg_match('/^(\d+)$/', $firstText, $sm)) {
            $currentSlot = (int)$sm[1];
        } else {
            $currentSlot++;
        }

        for ($i = 1; $i < $cells->length; $i++) {
            $td = $cells->item($i);
            $content = trim($td->textContent);
            if (empty($content) || $content === '-' || $content === '—') continue;

            $date = $datesByCol[$i] ?? null;
            if (!$date) continue;

            $code = '';
            $links = $td->getElementsByTagName('a');
            if ($links->length > 0) {
                $code = trim($links->item(0)->textContent);
            }
            if (empty($code) && preg_match('/\b([A-Z]{3,4}\d{3}[a-zA-Z]?)\b/', $content, $cm)) {
                $code = $cm[1];
            }

            if (empty($code)) continue;

            $room = '';
            if (preg_match('/(?:at|phòng|room)\s*([A-Za-z0-9_-]+)/i', $content, $rm)) {
                $room = $rm[1];
            }

            $start = ''; $end = '';
            if (preg_match('/(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})/', $content, $tm)) {
                $start = $tm[1];
                $end = $tm[2];
            }

            $status = '';
            if (stripos($content, 'attended') !== false || stripos($content, 'đã học') !== false) {
                $status = 'attended';
            } elseif (stripos($content, 'absent') !== false || stripos($content, 'vắng') !== false) {
                $status = 'absent';
            }

            $sessions[] = [
                'date' => $date,
                'slot' => $currentSlot,
                'code' => $code,
                'room' => $room,
                'start' => $start,
                'end' => $end,
                'status' => $status
            ];
        }
    }
    return $sessions;
}

// 4. Import HTML
if ($action === 'import_html') {
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $html = $body['html'] ?? '';
    if (empty($html)) out(['ok' => false, 'error' => 'Thiếu dữ liệu HTML'], 400);

    $sessions = parseFapHtml($html);
    if (empty($sessions)) {
        out(['ok' => false, 'error' => 'Không tìm thấy buổi học nào trong HTML được cung cấp'], 400);
    }

    try {
        $res = fapSaveSessions($db, $sid, $sessions);
        out(array_merge(['ok' => true], $res));
    } catch (Throwable $e) {
        out(['ok' => false, 'error' => 'Lỗi lưu: ' . $e->getMessage()], 500);
    }
}

// 5. Cào từ Server
if ($action === 'crawl_server') {
    $row = fapGetRow($db, $sid);
    $cookie = $row['fap_cookie'] ?? '';
    if (empty($cookie)) {
        out(['ok' => false, 'error' => 'Chưa cấu hình Cookie FAP'], 400);
    }

    $url = 'https://fap.fpt.edu.vn/WeeklyTimetable';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            "Cookie: $cookie",
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

    if ($curlErr) out(['ok' => false, 'error' => 'Lỗi cURL: ' . $curlErr], 500);

    if ($httpCode === 403 || strpos($res, 'Just a moment...') !== false) {
        out(['ok' => false, 'cloudflare' => true, 'error' => 'Cloudflare kích hoạt xác minh Turnstile. Vui lòng dùng Bookmarklet hoặc nhập HTML.'], 403);
    }

    if (strpos($res, 'login-step-credentials') !== false) {
        out(['ok' => false, 'error' => 'Cookie đã hết hạn hoặc không hợp lệ. Vui lòng cập nhật Cookie mới.'], 401);
    }

    $sessions = parseFapHtml($res);
    if (empty($sessions)) {
        out(['ok' => false, 'error' => 'Tải trang thành công nhưng không bóc tách được lịch học.'], 200);
    }

    try {
        $saveResult = fapSaveSessions($db, $sid, $sessions);
        out(array_merge(['ok' => true], $saveResult));
    } catch (Throwable $e) {
        out(['ok' => false, 'error' => 'Lỗi lưu: ' . $e->getMessage()], 500);
    }
}

out(['ok' => false, 'error' => 'Hành động không hợp lệ'], 400);
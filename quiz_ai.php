<?php
// =============================================================================
// quiz_ai.php — Tạo quiz từ nội dung bài học bằng Gemini, lưu thẳng vào DB.
// Nhận POST (AJAX): title, description, lesson, count  ->  trả JSON.
// (Mở trực tiếp bằng trình duyệt sẽ báo "Method not allowed" — đó là bình thường.)
// =============================================================================
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/gemini.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

function fail(int $code, string $msg): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'Method not allowed.');
}

$sid    = (int)$_SESSION['student_id'];
$lesson = trim($_POST['lesson'] ?? '');
$count  = max(1, min(30, (int)($_POST['count'] ?? 10)));
$title  = trim($_POST['title'] ?? '') ?: 'Quiz tạo bởi AI';
$desc   = trim($_POST['description'] ?? '');

if (mb_strlen($lesson) < 200) {
    fail(422, 'Nội dung bài học quá ngắn (tối thiểu ~200 ký tự).');
}
if (mb_strlen($lesson) > 30000) {
    fail(422, 'Nội dung quá dài (tối đa 30.000 ký tự). Hãy chia nhỏ bài học.');
}
if (GEMINI_API_KEY === '' || GEMINI_API_KEY === 'DÁN_API_KEY_VÀO_ĐÂY') {
    fail(500, 'Chưa cấu hình GEMINI_API_KEY trong config/gemini.php.');
}

// Giải phóng khóa session để các request khác không bị treo trong lúc chờ AI
session_write_close();
set_time_limit(240);

// ---------------------------------------------------------------------------
// 1. Prompt
// ---------------------------------------------------------------------------
$systemPrompt = <<<PROMPT
Bạn là một chuyên gia giáo dục và biên soạn đề thi chuyên nghiệp. Nhiệm vụ của bạn là đọc kỹ đoạn văn bản bài học được cung cấp và tạo ra một bộ câu hỏi trắc nghiệm (Quiz) để kiểm tra mức độ hiểu bài của người học.

### YÊU CẦU BẮT BUỘC:
1. Số lượng câu hỏi: đúng {$count} câu.
2. Nội dung: Các câu hỏi phải bám sát kiến thức trong văn bản được cung cấp, tránh ra đề dựa trên thông tin ngoài văn bản.
3. Đáp án: Mỗi câu hỏi gồm 4 lựa chọn (A, B, C, D). Chỉ có duy nhất 1 đáp án đúng. Vị trí đáp án đúng phải phân bố ngẫu nhiên giữa A, B, C, D.
4. Giải thích: Đưa ra lời giải thích ngắn gọn, súc tích tại sao đáp án đó đúng dựa trên bài học.
5. Ngôn ngữ: viết cùng ngôn ngữ với văn bản bài học.
6. Định dạng đầu ra: BẮT BUỘC chỉ trả về định dạng JSON thuần (Pure JSON), không chứa bất kỳ câu dẫn, lời mở đầu hay định dạng Markdown nào khác ngoài khối JSON.
PROMPT;

$payload = [
    'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
    'contents' => [[
        'role'  => 'user',
        'parts' => [['text' => "### VĂN BẢN BÀI HỌC:\n" . $lesson]],
    ]],
    'generationConfig' => [
        'temperature'      => 0.4,
        'responseMimeType' => 'application/json',
        // Ép Gemini trả đúng cấu trúc khớp với bảng quiz_questions
        'responseSchema'   => [
            'type'  => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'question_text' => ['type' => 'STRING'],
                    'opt_a'         => ['type' => 'STRING'],
                    'opt_b'         => ['type' => 'STRING'],
                    'opt_c'         => ['type' => 'STRING'],
                    'opt_d'         => ['type' => 'STRING'],
                    'correct_opt'   => ['type' => 'STRING', 'enum' => ['A', 'B', 'C', 'D']],
                    'explanation'   => ['type' => 'STRING'],
                ],
                'required' => ['question_text', 'opt_a', 'opt_b', 'opt_c', 'opt_d', 'correct_opt', 'explanation'],
            ],
        ],
    ],
];

// ---------------------------------------------------------------------------
// 2. Gọi Gemini (retry khi quá tải + tự chuyển model dự phòng)
// ---------------------------------------------------------------------------
function callGemini(string $model, array $payload): array
{
    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . GEMINI_API_KEY,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    $raw  = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);
    return [$raw, $http, $cerr];
}

$res = null;
$lastErr = '';
foreach (GEMINI_MODELS as $model) {
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        [$raw, $http, $cerr] = callGemini($model, $payload);

        if ($raw !== false && $http === 200) {
            $res = json_decode($raw, true);
            break 2;                                   // thành công
        }

        $err = ($raw !== false) ? json_decode($raw, true) : null;
        $lastErr = ($raw === false)
            ? 'Không kết nối được Gemini: ' . $cerr
            : 'Gemini lỗi (' . $http . '): ' . ($err['error']['message'] ?? 'không rõ');

        if ($http === 404) break;                      // model không tồn tại -> sang model kế
        if ($raw !== false && !in_array($http, [429, 500, 503, 504], true)) {
            fail(502, $lastErr);                       // lỗi không thể thử lại (400, 403...)
        }
        if ($attempt < 3) sleep($attempt * 2);         // chờ 2s, 4s rồi thử lại
    }
}
if ($res === null) {
    fail(503, $lastErr . ' — Google đang quá tải, hãy thử lại sau ít phút.');
}

$text = $res['candidates'][0]['content']['parts'][0]['text'] ?? '';
if ($text === '') {
    fail(502, 'Gemini không trả về nội dung (có thể bị chặn bởi bộ lọc an toàn).');
}

// Phòng hờ model vẫn bọc ```json ... ```
$text  = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)));
$items = json_decode($text, true);
if (isset($items['questions']) && is_array($items['questions'])) {
    $items = $items['questions'];
}
if (!is_array($items)) {
    fail(502, 'AI trả về dữ liệu không đúng định dạng JSON. Hãy thử lại.');
}

// ---------------------------------------------------------------------------
// 3. Kiểm tra & chuẩn hóa từng câu
// ---------------------------------------------------------------------------
$valid = [];
foreach ($items as $it) {
    if (!is_array($it)) continue;
    $q = [
        'question_text' => trim((string)($it['question_text'] ?? '')),
        'opt_a'         => trim((string)($it['opt_a'] ?? '')),
        'opt_b'         => trim((string)($it['opt_b'] ?? '')),
        'opt_c'         => trim((string)($it['opt_c'] ?? '')),
        'opt_d'         => trim((string)($it['opt_d'] ?? '')),
        'correct_opt'   => strtoupper(trim((string)($it['correct_opt'] ?? ''))),
        'explanation'   => trim((string)($it['explanation'] ?? '')),
    ];
    if ($q['question_text'] === '' || !in_array($q['correct_opt'], ['A', 'B', 'C', 'D'], true)) continue;
    if ($q['opt_a'] === '' || $q['opt_b'] === '' || $q['opt_c'] === '' || $q['opt_d'] === '') continue;
    $valid[] = $q;
}
if (!$valid) {
    fail(502, 'AI không tạo được câu hỏi hợp lệ. Hãy thử lại hoặc rút gọn bài học.');
}

// ---------------------------------------------------------------------------
// 4. Lưu vào DB (transaction: hoặc có đủ quiz + câu hỏi, hoặc không có gì)
// ---------------------------------------------------------------------------
$db = getDB();
try {
    $db->beginTransaction();

    $db->prepare("INSERT INTO quizzes (student_id, title, description) VALUES (?, ?, ?)")
       ->execute([$sid, $title, $desc !== '' ? $desc : 'AI Generated']);
    $quiz_id = (int)$db->lastInsertId();

    $ins = $db->prepare(
        "INSERT INTO quiz_questions (quiz_id, question_text, opt_a, opt_b, opt_c, opt_d, correct_opt, explanation)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($valid as $q) {
        $ins->execute([
            $quiz_id, $q['question_text'], $q['opt_a'], $q['opt_b'],
            $q['opt_c'], $q['opt_d'], $q['correct_opt'], $q['explanation'],
        ]);
    }
    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fail(500, 'Không lưu được vào cơ sở dữ liệu.');
}

echo json_encode(['ok' => true, 'quiz_id' => $quiz_id, 'count' => count($valid)], JSON_UNESCAPED_UNICODE);
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

const FONTS = ['Be Vietnam Pro', 'Arial', 'Times New Roman', 'Georgia', 'Consolas', 'Courier New', 'Comic Sans MS'];
const TAGS = ['p','br','div','b','strong','i','em','u','s','h1','h2','h3','ul','ol','li','pre','code','blockquote','a','span','hr'];

function out($d, $c = 200) {
    http_response_code($c);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

function safe_style(string $s): string {
    $o = [];
    if (preg_match('/font-size:\s*(\d{1,2})px/i', $s, $m) && $m[1] >= 10 && $m[1] <= 48) {
        $o[] = "font-size:{$m[1]}px";
    }
    if (preg_match('/font-family:\s*([^;]+)/i', $s, $m)) {
        $f = trim(explode(',', str_replace(['"', "'"], '', $m[1]))[0]);
        if (in_array($f, FONTS, true)) {
            $o[] = "font-family:'$f'";
        }
    }
    return implode(';', $o);
}

function walk(DOMNode $n): void {
    foreach (iterator_to_array($n->childNodes) as $c) {
        if ($c->nodeType !== XML_ELEMENT_NODE) continue;
        $t = strtolower($c->nodeName);
        if (!in_array($t, TAGS, true)) {
            if (in_array($t, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                $n->removeChild($c);
                continue;
            }
            walk($c);
            while ($c->firstChild) {
                $n->insertBefore($c->firstChild, $c);
            }
            $n->removeChild($c);
            continue;
        }
        foreach (iterator_to_array($c->attributes) as $a) {
            $name = $a->nodeName;
            $v = $a->nodeValue;
            $c->removeAttribute($name);
            if ($t === 'a' && $name === 'href' && preg_match('~^https?://~i', $v)) {
                $c->setAttribute('href', $v);
                $c->setAttribute('target', '_blank');
                $c->setAttribute('rel', 'noopener noreferrer');
            }
            if ($t === 'span' && $name === 'style' && ($s = safe_style($v))) {
                $c->setAttribute('style', $s);
            }
        }
        walk($c);
    }
}

function clean_html(string $html): string {
    if (trim($html) === '') return '';
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementsByTagName('div')->item(0);
    if (!$root) return '';
    walk($root);
    $out = '';
    foreach ($root->childNodes as $c) {
        $out .= $doc->saveHTML($c);
    }
    return $out;
}

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$act = $_GET['action'] ?? ($in['action'] ?? '');

try {
    $pdo = getDB();
    $studentId = isLoggedIn() ? (int)$_SESSION['student_id'] : null;

    switch ($act) {
        case 'subjects':
            if ($studentId) {
                $st = $pdo->prepare('SELECT id, subject_name AS name, subject_name FROM subjects WHERE student_id = ? ORDER BY subject_name');
                $st->execute([$studentId]);
                out($st->fetchAll(PDO::FETCH_ASSOC));
            } else {
                out($pdo->query('SELECT id, subject_name AS name, subject_name FROM subjects ORDER BY subject_name')->fetchAll(PDO::FETCH_ASSOC));
            }

        case 'list':
            $w = ['1=1'];
            $p = [];
            if (!empty($_GET['subject_id'])) {
                $w[] = 'w.subject_id=?';
                $p[] = (int)$_GET['subject_id'];
            }
            if (!empty($_GET['from'])) {
                $w[] = 'w.study_date>=?';
                $p[] = $_GET['from'];
            }
            if (!empty($_GET['to'])) {
                $w[] = 'w.study_date<=?';
                $p[] = $_GET['to'];
            }
            if (!empty($_GET['q'])) {
                $w[] = '(w.title LIKE ? OR w.content LIKE ?)';
                $qVal = '%' . trim($_GET['q']) . '%';
                $p[] = $qVal;
                $p[] = $qVal;
            }
            if ($studentId) {
                $w[] = 's.student_id=?';
                $p[] = $studentId;
            }
            $st = $pdo->prepare('SELECT w.id, w.title, w.study_date, w.subject_id, s.subject_name
                FROM writeups w JOIN subjects s ON s.id=w.subject_id WHERE ' . implode(' AND ', $w) .
                ' ORDER BY s.subject_name, w.study_date DESC, w.id DESC');
            $st->execute($p);
            out($st->fetchAll(PDO::FETCH_ASSOC));

        case 'get':
            $st = $pdo->prepare('SELECT * FROM writeups WHERE id=?');
            $st->execute([(int)($_GET['id'] ?? 0)]);
            $w = $st->fetch(PDO::FETCH_ASSOC);
            if (!$w) out(['error' => 'Không tìm thấy'], 404);
            $l = $pdo->prepare('SELECT title, url FROM writeup_links WHERE writeup_id=? ORDER BY id');
            $l->execute([$w['id']]);
            $w['links'] = $l->fetchAll(PDO::FETCH_ASSOC);
            out($w);

        case 'save':
            $id = (int)($in['id'] ?? 0);
            $subjectId = (int)($in['subject_id'] ?? 0);
            $title = trim($in['title'] ?? '');
            
            if (!$subjectId) {
                out(['error' => 'Vui lòng chọn môn học'], 422);
            }
            if ($title === '') {
                out(['error' => 'Vui lòng nhập tiêu đề'], 422);
            }

            // Kiểm tra môn học tồn tại
            $chk = $pdo->prepare('SELECT id FROM subjects WHERE id=?');
            $chk->execute([$subjectId]);
            if (!$chk->fetch()) {
                out(['error' => 'Môn học không tồn tại'], 422);
            }

            $studyDateStr = trim($in['study_date'] ?? '');
            $studyDate = date('Y-m-d');
            if ($studyDateStr) {
                $d = DateTime::createFromFormat('Y-m-d', $studyDateStr);
                if ($d && $d->format('Y-m-d') === $studyDateStr) {
                    $studyDate = $studyDateStr;
                } elseif (strtotime($studyDateStr)) {
                    $studyDate = date('Y-m-d', strtotime($studyDateStr));
                }
            }

            $font = in_array($in['font_family'] ?? '', FONTS, true) ? $in['font_family'] : FONTS[0];
            $size = max(10, min(48, (int)($in['font_size'] ?? 16)));
            $content = clean_html((string)($in['content'] ?? ''));

            $pdo->beginTransaction();
            if ($id) {
                $pdo->prepare('UPDATE writeups SET subject_id=?, study_date=?, title=?, content=?, font_family=?, font_size=? WHERE id=?')
                    ->execute([$subjectId, $studyDate, $title, $content, $font, $size, $id]);
                $pdo->prepare('DELETE FROM writeup_links WHERE writeup_id=?')->execute([$id]);
            } else {
                $pdo->prepare('INSERT INTO writeups (subject_id, study_date, title, content, font_family, font_size) VALUES (?,?,?,?,?,?)')
                    ->execute([$subjectId, $studyDate, $title, $content, $font, $size]);
                $id = (int)$pdo->lastInsertId();
            }

            $ins = $pdo->prepare('INSERT INTO writeup_links (writeup_id, title, url) VALUES (?,?,?)');
            foreach (($in['links'] ?? []) as $lk) {
                $u = trim($lk['url'] ?? '');
                if ($u !== '') {
                    if (!preg_match('~^https?://~i', $u)) {
                        $u = 'https://' . $u;
                    }
                    $t = trim($lk['title'] ?? '') ?: $u;
                    $ins->execute([$id, mb_substr($t, 0, 255), mb_substr($u, 0, 1000)]);
                }
            }

            $pdo->commit();
            out(['ok' => true, 'id' => $id]);

        case 'delete':
            $pdo->prepare('DELETE FROM writeups WHERE id=?')->execute([(int)($in['id'] ?? 0)]);
            out(['ok' => true]);

        default:
            out(['error' => 'Action không hợp lệ'], 400);
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out(['error' => 'Lỗi máy chủ: ' . $e->getMessage()], 500);
}
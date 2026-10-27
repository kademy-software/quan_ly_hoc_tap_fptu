<?php
/**
 * progress.php — Cập nhật tiến độ học tập
 * Sử dụng chuẩn kết nối của dự án: auth.php, requireLogin(), getDB().
 * Cần tạo bảng learning_progress và learning_goals trước khi sử dụng.
 */

require_once __DIR__ . '/config/auth.php';
requireLogin();

$db = getDB();
$studentId = (int)($_SESSION['student_id'] ?? 0);
if ($studentId <= 0) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Cập nhật tiến độ học tập';
$activePage = 'progress';

const SIGNER_NAME = 'Lê Tuấn Kiệt';

const PER_PAGE    = 10;



/* ---------- helpers ---------- */

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function validDate(?string $d): bool {

    if (!$d) return false;

    $x = DateTime::createFromFormat('Y-m-d', $d);

    return $x && $x->format('Y-m-d') === $d;

}

function flash(string $type, string $msg): void { $_SESSION['pg_flash'] = [$type, $msg]; }

function back(array $qs = []): void {

    $url = 'progress.php' . ($qs ? '?' . http_build_query($qs) : '');

    header('Location: ' . $url); exit;

}

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$csrf = $_SESSION['csrf'];



/* ---------- filters (GET) ---------- */

$q      = trim($_GET['q'] ?? '');

$from   = validDate($_GET['from'] ?? '') ? $_GET['from'] : '';

$to     = validDate($_GET['to'] ?? '') ? $_GET['to'] : '';

$status = in_array($_GET['status'] ?? '', ['signed', 'unsigned'], true) ? $_GET['status'] : '';

$sort   = ($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

$page   = max(1, (int)($_GET['page'] ?? 1));

$keep   = array_filter(['q' => $q, 'from' => $from, 'to' => $to, 'status' => $status, 'sort' => $sort], fn($v) => $v !== '');



/* ---------- POST actions ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) { flash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.'); back($keep); }

    $act = $_POST['action'] ?? '';

    $id  = (int)($_POST['id'] ?? 0);



    try {

        if ($act === 'save') {

            $date    = $_POST['study_date'] ?? '';

            $learned = trim($_POST['learned_today'] ?? '');

            $result  = trim($_POST['result_note'] ?? '');

            $plan    = trim($_POST['plan_tomorrow'] ?? '');

            if (!validDate($date))                       throw new RuntimeException('Ngày không hợp lệ.');

            if ($learned === '')                         throw new RuntimeException('Vui lòng nhập nội dung học được hôm nay.');

            if (mb_strlen($learned) > 20000 || mb_strlen($result) > 20000 || mb_strlen($plan) > 20000)

                                                         throw new RuntimeException('Nội dung quá dài (tối đa 20.000 ký tự mỗi ô).');

            if ($id > 0) {

                $st = $db->prepare('SELECT is_signed FROM learning_progress WHERE id=? AND student_id=?');

                $st->execute([$id, $studentId]);

                $row = $st->fetch(PDO::FETCH_ASSOC);

                if (!$row)               throw new RuntimeException('Không tìm thấy bản ghi.');

                if ($row['is_signed'])   throw new RuntimeException('Bản ghi đã ký xác nhận. Hãy mở khóa chỉnh sửa trước.');

                $db->prepare('UPDATE learning_progress SET study_date=?, learned_today=?, result_note=?, plan_tomorrow=? WHERE id=? AND student_id=?')

                    ->execute([$date, $learned, $result, $plan, $id, $studentId]);

                flash('success', 'Đã cập nhật bản ghi.');

            } else {

                $db->prepare('INSERT INTO learning_progress (student_id, study_date, learned_today, result_note, plan_tomorrow) VALUES (?,?,?,?,?)')

                    ->execute([$studentId, $date, $learned, $result, $plan]);

                flash('success', 'Đã thêm tiến độ học tập.');

            }

        } elseif ($act === 'sign') {

            // UPDATE có điều kiện is_signed=0 → không thể ký lặp lại

            $st = $db->prepare('UPDATE learning_progress SET is_signed=1, signed_by=?, signed_at=NOW() WHERE id=? AND student_id=? AND is_signed=0');

            $st->execute([SIGNER_NAME, $id, $studentId]);

            $st->rowCount() ? flash('success', 'Đã ký xác nhận hoàn thành.') : flash('error', 'Bản ghi không tồn tại hoặc đã được xác nhận.');

        } elseif ($act === 'unlock') {

            $st = $db->prepare('UPDATE learning_progress SET is_signed=0, signed_by=NULL, signed_at=NULL WHERE id=? AND student_id=? AND is_signed=1');

            $st->execute([$id, $studentId]);

            $st->rowCount() ? flash('success', 'Đã mở khóa. Bản ghi quay về trạng thái chưa xác nhận, bạn có thể chỉnh sửa.') : flash('error', 'Không thể mở khóa bản ghi này.');

        } elseif ($act === 'delete') {

            $st = $db->prepare('DELETE FROM learning_progress WHERE id=? AND student_id=? AND is_signed=0');

            $st->execute([$id, $studentId]);

            $st->rowCount() ? flash('success', 'Đã xóa bản ghi.') : flash('error', 'Không xóa được (bản ghi không tồn tại hoặc đã ký xác nhận — hãy mở khóa trước).');

        } elseif ($act === 'goal_save') {

            $title = trim($_POST['title'] ?? '');

            $desc  = trim($_POST['description'] ?? '');

            $gs    = $_POST['start_date'] ?? '';

            $gt    = $_POST['target_date'] ?? '';

            $stt   = $_POST['gstatus'] ?? 'not_started';

            $pct   = max(0, min(100, (int)($_POST['progress_percent'] ?? 0)));

            if ($title === '' || mb_strlen($title) > 200) throw new RuntimeException('Tên mục tiêu bắt buộc (tối đa 200 ký tự).');

            if (!in_array($stt, ['not_started', 'in_progress', 'completed'], true)) throw new RuntimeException('Trạng thái không hợp lệ.');

            $gs = validDate($gs) ? $gs : null; $gt = validDate($gt) ? $gt : null;

            if ($gs && $gt && $gt < $gs) throw new RuntimeException('Ngày hoàn thành phải sau ngày bắt đầu.');

            if ($stt === 'completed') $pct = 100;

            if ($stt === 'not_started') $pct = 0;

            if ($id > 0) {

                $st = $db->prepare('UPDATE learning_goals SET title=?, description=?, start_date=?, target_date=?, status=?, progress_percent=? WHERE id=? AND student_id=?');

                $st->execute([$title, $desc, $gs, $gt, $stt, $pct, $id, $studentId]);

                flash('success', 'Đã cập nhật mục tiêu.');

            } else {

                $db->prepare('INSERT INTO learning_goals (student_id,title,description,start_date,target_date,status,progress_percent) VALUES (?,?,?,?,?,?,?)')

                    ->execute([$studentId, $title, $desc, $gs, $gt, $stt, $pct]);

                flash('success', 'Đã thêm mục tiêu học tập.');

            }

        } elseif ($act === 'goal_delete') {

            $db->prepare('DELETE FROM learning_goals WHERE id=? AND student_id=?')->execute([$id, $studentId]);

            flash('success', 'Đã xóa mục tiêu.');

        } else {

            throw new RuntimeException('Thao tác không hợp lệ.');

        }

    } catch (RuntimeException $ex) {

        flash('error', $ex->getMessage());

    } catch (Throwable $ex) {

        error_log('[progress.php] ' . $ex->getMessage());

        flash('error', 'Đã xảy ra lỗi hệ thống, vui lòng thử lại.');

    }

    back($keep + ($page > 1 ? ['page' => $page] : []));

}



/* ---------- WHERE builder ---------- */

$where = ['student_id = :sid']; $params = [':sid' => $studentId];

if ($q !== '') {

    $where[] = '(learned_today LIKE :q1 OR result_note LIKE :q2 OR plan_tomorrow LIKE :q3)';

    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';

    $params[':q1'] = $params[':q2'] = $params[':q3'] = $like;

}

if ($from)   { $where[] = 'study_date >= :from'; $params[':from'] = $from; }

if ($to)     { $where[] = 'study_date <= :to';   $params[':to']   = $to; }

if ($status === 'signed')   $where[] = 'is_signed = 1';

if ($status === 'unsigned') $where[] = 'is_signed = 0';

$whereSql = implode(' AND ', $where);

$orderSql = $sort === 'asc' ? 'study_date ASC, id ASC' : 'study_date DESC, id DESC';



/* ---------- CSV export ---------- */

if (($_GET['export'] ?? '') === 'csv') {

    $st = $db->prepare("SELECT study_date, learned_today, result_note, plan_tomorrow, is_signed, signed_by, signed_at FROM learning_progress WHERE $whereSql ORDER BY $orderSql");

    $st->execute($params);

    header('Content-Type: text/csv; charset=utf-8');

    header('Content-Disposition: attachment; filename="tien_do_hoc_tap_' . date('Ymd') . '.csv"');

    $out = fopen('php\://output', 'w');

    fwrite($out, "\xEF\xBB\xBF"); // BOM cho Excel đọc tiếng Việt

    fputcsv($out, ['STT', 'Ngày', 'Nội dung học được', 'Kết quả / ghi chú', 'Kế hoạch ngày mai', 'Trạng thái', 'Người ký', 'Thời gian ký']);

    $i = 0;

    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {

        $cell = fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v; // chống CSV injection

        fputcsv($out, [++$i, $r['study_date'], $cell($r['learned_today']), $cell($r['result_note']), $cell($r['plan_tomorrow']),

            $r['is_signed'] ? 'Đã xác nhận' : 'Chưa xác nhận', $r['signed_by'], $r['signed_at']]);

    }

    fclose($out); exit;

}



/* ---------- Stats ---------- */

$st = $db->prepare('SELECT COUNT(*) total, COALESCE(SUM(is_signed),0) signed, COUNT(DISTINCT study_date) days FROM learning_progress WHERE student_id=?');

$st->execute([$studentId]);

$stats = $st->fetch(PDO::FETCH_ASSOC);

$total = (int)$stats['total']; $signedCnt = (int)$stats['signed']; $unsignedCnt = $total - $signedCnt; $daysCnt = (int)$stats['days'];

$rate = $total ? round($signedCnt * 100 / $total, 1) : 0;



// streak: các ngày liên tiếp kết thúc ở hôm nay (hoặc hôm qua)

$st = $db->prepare('SELECT DISTINCT study_date FROM learning_progress WHERE student_id=? AND study_date <= CURDATE() ORDER BY study_date DESC');

$st->execute([$studentId]);

$dates = $st->fetchAll(PDO::FETCH_COLUMN);

$streak = 0;

if ($dates) {

    $cursor = new DateTime($dates[0]);

    $gap = (new DateTime('today'))->diff($cursor)->days;

    if ($gap <= 1) {

        foreach ($dates as $d) {

            if ($d === $cursor->format('Y-m-d')) { $streak++; $cursor->modify('-1 day'); } else break;

        }

    }

}



// biểu đồ: số bản ghi theo tuần, 8 tuần gần nhất

$st = $db->prepare("SELECT YEARWEEK(study_date,1) yw, MIN(study_date) d0, COUNT(*) c FROM learning_progress

                     WHERE student_id=? AND study_date >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK) AND study_date <= CURDATE()

                     GROUP BY YEARWEEK(study_date,1) ORDER BY yw");

$st->execute([$studentId]);

$weekly = $st->fetchAll(PDO::FETCH_ASSOC);

$maxWeek = max(array_merge([1], array_map(fn($w) => (int)$w['c'], $weekly)));



/* ---------- List + pagination ---------- */

$st = $db->prepare("SELECT COUNT(*) FROM learning_progress WHERE $whereSql");

$st->execute($params);

$filtered = (int)$st->fetchColumn();

$pages = max(1, (int)ceil($filtered / PER_PAGE));

$page  = min($page, $pages);

$offset = ($page - 1) * PER_PAGE;

$st = $db->prepare("SELECT * FROM learning_progress WHERE $whereSql ORDER BY $orderSql LIMIT " . PER_PAGE . " OFFSET $offset");

$st->execute($params);

$rows = $st->fetchAll(PDO::FETCH_ASSOC);



$st = $db->prepare('SELECT * FROM learning_goals WHERE student_id=? ORDER BY FIELD(status,"in_progress","not_started","completed"), target_date IS NULL, target_date');

$st->execute([$studentId]);

$goals = $st->fetchAll(PDO::FETCH_ASSOC);



$flash = $_SESSION['pg_flash'] ?? null; unset($_SESSION['pg_flash']);

$gLabel = ['not_started' => 'Chưa bắt đầu', 'in_progress' => 'Đang thực hiện', 'completed' => 'Hoàn thành'];

include __DIR__ . '/includes/header.php'; // (4) chỉnh theo dự án (header + sidebar + navbar)

?>

<style>

/* Dùng biến CSS của dự án nếu có; fallback giúp hiển thị đúng cả Light/Dark */

.lp{--lp-card:var(--card,var(--bg-card,rgba(127,127,127,.08)));--lp-bd:var(--border,rgba(127,127,127,.25));--lp-mut:var(--muted,var(--text-muted,#8a94a6));--lp-ac:var(--accent,var(--primary,#8bc53f));--lp-ok:#2ea043;--lp-warn:#e0a526;--lp-bad:#e2445c}

.lp *{box-sizing:border-box}

.lp-head{display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center;margin-bottom:18px}

.lp-head h1{margin:0;font-size:1.5rem}

.lp-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:1px solid var(--lp-bd);background:var(--lp-card);color:inherit;cursor:pointer;font:inherit;text-decoration:none;font-size:.9rem}

.lp-btn:hover{border-color:var(--lp-ac)}

.lp-btn.pri{background:var(--lp-ac);color:#0b1200;border-color:var(--lp-ac);font-weight:600}

.lp-btn.danger{color:var(--lp-bad)} .lp-btn.sm{padding:4px 9px;font-size:.8rem}

.lp-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:18px}

.lp-stat{background:var(--lp-card);border:1px solid var(--lp-bd);border-radius:12px;padding:14px}

.lp-stat b{display:block;font-size:1.6rem;line-height:1.2} .lp-stat span{color:var(--lp-mut);font-size:.82rem}

.lp-flash{padding:10px 14px;border-radius:8px;margin-bottom:14px;border:1px solid}

.lp-flash.success{border-color:var(--lp-ok);background:rgba(46,160,67,.12)} .lp-flash.error{border-color:var(--lp-bad);background:rgba(226,68,92,.12)}

.lp-panel{background:var(--lp-card);border:1px solid var(--lp-bd);border-radius:12px;padding:14px;margin-bottom:18px}

.lp-panel h2{margin:0 0 10px;font-size:1.05rem}

.lp-filter{display:flex;flex-wrap:wrap;gap:8px;align-items:end}

.lp input,.lp select,.lp textarea{background:transparent;color:inherit;border:1px solid var(--lp-bd);border-radius:8px;padding:7px 10px;font:inherit;max-width:100%}

.lp textarea{width:100%;min-height:110px;resize:vertical} .lp label{display:block;font-size:.8rem;color:var(--lp-mut);margin-bottom:3px}

.lp-wrap{overflow-x:auto;border:1px solid var(--lp-bd);border-radius:12px}

.lp table{width:100%;border-collapse:collapse;min-width:900px}

.lp th,.lp td{padding:10px 12px;border-bottom:1px solid var(--lp-bd);text-align:left;vertical-align:top;font-size:.9rem}

.lp th{background:var(--lp-card);white-space:nowrap} .lp tr:last-child td{border-bottom:0}

.lp td.txt{max-width:280px;white-space:pre-wrap;word-break:break-word}

.lp-clamp{display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden}

.lp-badge{display:inline-block;padding:2px 9px;border-radius:999px;font-size:.75rem;border:1px solid}

.lp-badge.ok{color:var(--lp-ok);border-color:var(--lp-ok)} .lp-badge.no{color:var(--lp-warn);border-color:var(--lp-warn)}

.lp-sign{font-family:"Brush Script MT","Segoe Script",cursive;font-size:1.25rem;color:var(--lp-ok)}

.lp-acts{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}

.lp-pager{display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-top:14px}

.lp-pager a,.lp-pager span{padding:5px 10px;border:1px solid var(--lp-bd);border-radius:6px;text-decoration:none;color:inherit}

.lp-pager .cur{background:var(--lp-ac);color:#0b1200;border-color:var(--lp-ac)}

.lp-chart{display:flex;gap:8px;align-items:flex-end;height:120px}

.lp-chart div{flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;height:100%;font-size:.7rem;color:var(--lp-mut)}

.lp-chart i{display:block;width:100%;background:var(--lp-ac);border-radius:4px 4px 0 0;min-height:2px}

.lp-goals{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}

.lp-goal{border:1px solid var(--lp-bd);border-radius:10px;padding:12px}

.lp-bar{height:8px;border-radius:6px;background:rgba(127,127,127,.25);overflow:hidden;margin:8px 0 4px}

.lp-bar i{display:block;height:100%;background:var(--lp-ac)}

.lp dialog{border:1px solid var(--lp-bd);border-radius:14px;background:var(--bg,var(--bg-body,Canvas));color:inherit;width:min(640px,94vw);padding:18px}

.lp dialog::backdrop{background:rgba(0,0,0,.55)}

.lp-row{display:grid;grid-template-columns:1fr 1fr;gap:10px} @media(max-width:560px){.lp-row{grid-template-columns:1fr}}

.lp-field{margin-bottom:10px} .lp-field input,.lp-field select{width:100%}

.lp-detail h4{margin:12px 0 3px;color:var(--lp-mut);font-size:.8rem;font-weight:500} .lp-detail p{margin:0;white-space:pre-wrap;word-break:break-word}

</style>



<div class="lp">

  <div class="lp-head">

    <h1>Cập nhật tiến độ học tập</h1>

    <div style="display:flex;gap:8px;flex-wrap:wrap">

      <a class="lp-btn" href="?<?= e(http_build_query($keep + ['export' => 'csv'])) ?>">Xuất CSV</a>

      <button class="lp-btn pri" type="button" onclick="openForm()">+ Thêm tiến độ hôm nay</button>

    </div>

  </div>



  <?php if ($flash): ?><div class="lp-flash <?= e($flash[0]) ?>" role="alert"><?= e($flash[1]) ?></div><?php endif; ?>



  <div class="lp-stats">

    <div class="lp-stat"><b><?= $daysCnt ?></b><span>Ngày đã ghi nhận</span></div>

    <div class="lp-stat"><b><?= $signedCnt ?></b><span>Đã xác nhận</span></div>

    <div class="lp-stat"><b><?= $unsignedCnt ?></b><span>Chưa xác nhận</span></div>

    <div class="lp-stat"><b><?= $rate ?>%</b><span>Tỷ lệ hoàn thành</span></div>

    <div class="lp-stat"><b><?= $streak ?></b><span>Ngày liên tiếp</span></div>

  </div>



  <form class="lp-panel lp-filter" method="get">

    <div><label>Tìm kiếm</label><input type="search" name="q" value="<?= e($q) ?>" placeholder="Nội dung, kết quả, kế hoạch…"></div>

    <div><label>Từ ngày</label><input type="date" name="from" value="<?= e($from) ?>"></div>

    <div><label>Đến ngày</label><input type="date" name="to" value="<?= e($to) ?>"></div>

    <div><label>Trạng thái</label>

      <select name="status"><option value="">Tất cả</option>

        <option value="signed" <?= $status === 'signed' ? 'selected' : '' ?>>Đã xác nhận</option>

        <option value="unsigned" <?= $status === 'unsigned' ? 'selected' : '' ?>>Chưa xác nhận</option></select></div>

    <div><label>Sắp xếp</label>

      <select name="sort"><option value="desc" <?= $sort === 'desc' ? 'selected' : '' ?>>Mới nhất</option>

        <option value="asc" <?= $sort === 'asc' ? 'selected' : '' ?>>Cũ nhất</option></select></div>

    <button class="lp-btn pri" type="submit">Lọc</button>

    <a class="lp-btn" href="progress.php">Xóa lọc</a>

  </form>



  <div class="lp-wrap">

    <table>

      <thead><tr><th>STT</th><th>Ngày</th><th>Nội dung học được hôm nay</th><th>Kết quả / ghi chú</th><th>Kế hoạch học ngày mai</th><th>Ký xác nhận</th></tr></thead>

      <tbody>

      <?php if (!$rows): ?>

        <tr><td colspan="6" style="text-align:center;color:var(--lp-mut);padding:28px">Chưa có bản ghi nào<?= $keep && count($keep) > 1 ? ' khớp bộ lọc' : '' ?>.</td></tr>

      <?php endif; ?>

      <?php foreach ($rows as $i => $r):

          $stt = $sort === 'asc' ? $offset + $i + 1 : $filtered - $offset - $i;

          $payload = e(json_encode(['id' => (int)$r['id'], 'date' => $r['study_date'], 'learned' => $r['learned_today'], 'result' => $r['result_note'],

              'plan' => $r['plan_tomorrow'], 'signed' => (int)$r['is_signed'], 'by' => $r['signed_by'], 'at' => $r['signed_at']], JSON_UNESCAPED_UNICODE)); ?>

        <tr>

          <td><?= $stt ?></td>

          <td style="white-space:nowrap"><?= e(date('d/m/Y', strtotime($r['study_date']))) ?></td>

          <td class="txt"><div class="lp-clamp"><?= e($r['learned_today']) ?></div></td>

          <td class="txt"><div class="lp-clamp"><?= e($r['result_note']) ?></div></td>

          <td class="txt"><div class="lp-clamp"><?= e($r['plan_tomorrow']) ?></div></td>

          <td style="min-width:190px">

            <?php if ($r['is_signed']): ?>

              <span class="lp-badge ok">Đã xác nhận</span>

              <div class="lp-sign"><?= e($r['signed_by']) ?></div>

              <small style="color:var(--lp-mut)"><?= e(date('H:i d/m/Y', strtotime($r['signed_at']))) ?></small>

            <?php else: ?>

              <span class="lp-badge no">Chưa xác nhận</span>

              <form method="post" style="margin-top:6px" onsubmit="return confirm('Ký xác nhận hoàn thành bản ghi này?')">

                <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="sign"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                <button class="lp-btn pri sm" type="submit">Ký xác nhận</button>

              </form>

            <?php endif; ?>

            <div class="lp-acts">

              <button class="lp-btn sm" type="button" data-r="<?= $payload ?>" onclick="openView(this)">Xem</button>

              <?php if ($r['is_signed']): ?>

                <form method="post" onsubmit="return confirm('Mở khóa sẽ hủy chữ ký hiện tại để bạn chỉnh sửa. Tiếp tục?')">

                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="unlock"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                  <button class="lp-btn sm" type="submit">Mở khóa</button></form>

              <?php else: ?>

                <button class="lp-btn sm" type="button" data-r="<?= $payload ?>" onclick="openForm(this)">Sửa</button>

                <form method="post" onsubmit="return confirm('Xóa bản ghi này? Không thể hoàn tác.')">

                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                  <button class="lp-btn sm danger" type="submit">Xóa</button></form>

              <?php endif; ?>

            </div>

          </td>

        </tr>

      <?php endforeach; ?>

      </tbody>

    </table>

  </div>



  <?php if ($pages > 1): ?>

  <div class="lp-pager">

    <?php for ($p = 1; $p <= $pages; $p++): $href = '?' . e(http_build_query($keep + ['page' => $p])); ?>

      <?= $p === $page ? "<span class='cur'>$p</span>" : "<a href='$href'>$p</a>" ?>

    <?php endfor; ?>

  </div>

  <?php endif; ?>



  <div class="lp-panel" style="margin-top:18px">

    <h2>Số bản ghi theo tuần (8 tuần gần nhất)</h2>

    <?php if (!$weekly): ?><p style="color:var(--lp-mut);margin:0">Chưa có dữ liệu.</p><?php else: ?>

    <div class="lp-chart">

      <?php foreach ($weekly as $w): ?>

        <div title="<?= (int)$w['c'] ?> bản ghi"><span><?= (int)$w['c'] ?></span><i style="height:<?= round($w['c'] / $maxWeek * 85) ?>%"></i>

        <span><?= e(date('d/m', strtotime($w['d0']))) ?></span></div>

      <?php endforeach; ?>

    </div><?php endif; ?>

  </div>



  <div class="lp-panel">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">

      <h2 style="margin:0">Mục tiêu học tập</h2>

      <button class="lp-btn sm pri" type="button" onclick="openGoal()">+ Thêm mục tiêu</button>

    </div>

    <?php if (!$goals): ?><p style="color:var(--lp-mut);margin:0">Chưa có mục tiêu nào.</p><?php endif; ?>

    <div class="lp-goals">

    <?php foreach ($goals as $g): $gp = e(json_encode($g, JSON_UNESCAPED_UNICODE)); ?>

      <div class="lp-goal">

        <b><?= e($g['title']) ?></b>

        <div style="color:var(--lp-mut);font-size:.8rem"><?= e($gLabel[$g['status']]) ?>

          <?= $g['start_date'] ? ' · ' . e(date('d/m/Y', strtotime($g['start_date']))) : '' ?><?= $g['target_date'] ? ' → ' . e(date('d/m/Y', strtotime($g['target_date']))) : '' ?></div>

        <?php if ($g['description']): ?><p style="font-size:.85rem;margin:6px 0;white-space:pre-wrap"><?= e($g['description']) ?></p><?php endif; ?>

        <div class="lp-bar"><i style="width:<?= (int)$g['progress_percent'] ?>%"></i></div>

        <small><?= (int)$g['progress_percent'] ?>%</small>

        <div class="lp-acts">

          <button class="lp-btn sm" type="button" data-g="<?= $gp ?>" onclick="openGoal(this)">Sửa</button>

          <form method="post" onsubmit="return confirm('Xóa mục tiêu này?')">

            <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="goal_delete"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">

            <button class="lp-btn sm danger" type="submit">Xóa</button></form>

        </div>

      </div>

    <?php endforeach; ?>

    </div>

  </div>



  <!-- Form thêm/sửa tiến độ -->

  <dialog id="dlgForm"><form method="post" id="frmProgress">

    <h2 id="frmTitle" style="margin-top:0">Thêm tiến độ hôm nay</h2>

    <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="f_id" value="0">

    <div class="lp-field"><label>Ngày</label><input type="date" name="study_date" id="f_date" required></div>

    <div class="lp-field"><label>Nội dung học được hôm nay </label><textarea name="learned_today" id="f_learned" required maxlength*="20000"></textarea></div>

    <div class="lp-field"><label>Kết quả / ghi chú</label><textarea name="result_note" id="f_result" maxlength="20000"></textarea></div>

    <div class="lp-field"><label>Kế hoạch học ngày mai</label><textarea name="plan_tomorrow" id="f_plan" maxlength="20000"></textarea></div>

    <div style="display:flex;gap:8px;justify-content:flex-end">

      <button class="lp-btn" type="button" onclick="dlgForm.close()">Hủy</button><button class="lp-btn pri" type="submit">Lưu</button></div>

  </form></dialog>



  <!-- Xem chi tiết -->

  <dialog id="dlgView"><div class="lp-detail" id="viewBody"></div>

    <div style="text-align:right;margin-top:14px"><button class="lp-btn" type="button" onclick="dlgView.close()">Đóng</button></div></dialog>



  <!-- Form mục tiêu -->

  <dialog id="dlgGoal"><form method="post">

    <h2 id="gTitle" style="margin-top:0">Thêm mục tiêu</h2>

    <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="goal_save"><input type="hidden" name="id" id="g_id" value="0">

    <div class="lp-field"><label>Tên mục tiêu / kỹ năng </label><input name="title" id="g_title" required maxlength*="200"></div>

    <div class="lp-row">

      <div class="lp-field"><label>Ngày bắt đầu</label><input type="date" name="start_date" id="g_start"></div>

      <div class="lp-field"><label>Ngày dự kiến hoàn thành</label><input type="date" name="target_date" id="g_target"></div>

    </div>

    <div class="lp-field"><label>Mô tả</label><textarea name="description" id="g_desc" style="min-height:70px"></textarea></div>

    <div class="lp-row">

      <div class="lp-field"><label>Trạng thái</label><select name="gstatus" id="g_status">

        <?php foreach ($gLabel as $k => $v) echo '<option value="' . $k . '">' . e($v) . '</option>'; ?></select></div>

      <div class="lp-field"><label>Tiến độ (%)</label><input type="number" name="progress_percent" id="g_pct" min="0" max="100" value="0"></div>

    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end">

      <button class="lp-btn" type="button" onclick="dlgGoal.close()">Hủy</button><button class="lp-btn pri" type="submit">Lưu</button></div>

  </form></dialog>

</div>



<script>

(function(){

  const $ = id => document.getElementById(id);

  const today = () => { const d = new Date(); d.setMinutes(d.getMinutes() - d.getTimezoneOffset()); return d.toISOString().slice(0,10); };

  const fmt = s => s ? new Date(s.replace(' ','T')).toLocaleString('vi-VN') : '';

  const esc = s => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };



  window.openForm = function(btn){

    const r = btn ? JSON.parse(btn.dataset.r) : null;

    $('frmTitle').textContent = r ? 'Chỉnh sửa tiến độ' : 'Thêm tiến độ hôm nay';

    $('f_id').value = r ? r.id : 0;

    $('f_date').value = r ? r.date : today();

    $('f_learned').value = r ? r.learned : '';

    $('f_result').value = r ? (r.result || '') : '';

    $('f_plan').value = r ? (r.plan || '') : '';

    $('dlgForm').showModal();

  };

  window.openView = function(btn){

    const r = JSON.parse(btn.dataset.r);

    $('viewBody').innerHTML =

      '<h3 style="margin:0">Ngày ' + esc(new Date(r.date).toLocaleDateString('vi-VN')) + '</h3>' +

      '<h4>Nội dung học được</h4><p>' + esc(r.learned) + '</p>' +

      '<h4>Kết quả / ghi chú</h4><p>' + (esc(r.result) || '—') + '</p>' +

      '<h4>Kế hoạch ngày mai</h4><p>' + (esc(r.plan) || '—') + '</p>' +

      '<h4>Xác nhận</h4><p>' + (r.signed ? '<span class="lp-sign">' + esc(r.by) + '</span> — ' + esc(fmt(r.at)) : 'Chưa xác nhận') + '</p>';

    $('dlgView').showModal();

  };

  window.openGoal = function(btn){

    const g = btn ? JSON.parse(btn.dataset.g) : null;

    $('gTitle').textContent = g ? 'Sửa mục tiêu' : 'Thêm mục tiêu';

    $('g_id').value = g ? g.id : 0;

    $('g_title').value = g ? g.title : '';

    $('g_start').value = g ? (g.start_date || '') : '';

    $('g_target').value = g ? (g.target_date || '') : '';

    $('g_desc').value = g ? (g.description || '') : '';

    $('g_status').value = g ? g.status : 'not_started';

    $('g_pct').value = g ? g.progress_percent : 0;

    $('dlgGoal').showModal();

  };

  window.dlgForm = $('dlgForm'); window.dlgView = $('dlgView'); window.dlgGoal = $('dlgGoal');

})();

</script>

<?php include __DIR__ . '/includes/footer.php'; // (4) chỉnh theo dự án ?>
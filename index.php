<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/fap_sync.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];
$student = currentStudent();

$subjects = $db->prepare('SELECT * FROM subjects WHERE student_id = ? ORDER BY status = "ongoing" DESC, subject_name');
$subjects->execute([$sid]);
$subjects = $subjects->fetchAll();

$totalSubjects = count($subjects);
$completed = count(array_filter($subjects, fn($s) => $s['status'] === 'completed'));
$ongoing = count(array_filter($subjects, fn($s) => $s['status'] === 'ongoing'));
$exempted = count(array_filter($subjects, fn($s) => $s['status'] === 'exempted')); // Thêm dòng này

// Diem danh gan nhat
$att = $db->prepare('SELECT a.*, s.subject_name, s.color FROM attendance a JOIN subjects s ON s.id = a.subject_id WHERE s.student_id = ? ORDER BY a.session_date DESC LIMIT 5');
$att->execute([$sid]);
$attRows = $att->fetchAll();
$presentCount = $db->prepare('SELECT COUNT(*) c FROM attendance a JOIN subjects s ON s.id=a.subject_id WHERE s.student_id=? AND a.status="present"');
$presentCount->execute([$sid]);
$presentCount = $presentCount->fetch()['c'];
$totalAtt = $db->prepare('SELECT COUNT(*) c FROM attendance a JOIN subjects s ON s.id=a.subject_id WHERE s.student_id=?');
$totalAtt->execute([$sid]);
$totalAtt = max(1, $totalAtt->fetch()['c']);

// Don FAP dang cho
$pendingFap = $db->prepare('SELECT COUNT(*) c FROM fap_submissions WHERE student_id=? AND status="pending"');
$pendingFap->execute([$sid]);
$pendingFap = $pendingFap->fetch()['c'];

// Lich hoc theo tuan (du lieu lay tu FAP -> bang fap_sessions)
fapEnsureTable($db);
$baseTs = (isset($_GET['week']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['week']) && ($t = strtotime($_GET['week'])) !== false) ? $t : time();
$weekStartTs = strtotime('monday this week', $baseTs);
$semStartWeekTs = strtotime('monday this week', strtotime(QLHT_SEMESTER_START));
if ($weekStartTs < $semStartWeekTs) $weekStartTs = $semStartWeekTs;          // không xem trước ngày bắt đầu
$hasPrevWeek = $weekStartTs > $semStartWeekTs;
$homeWeekTs  = max(strtotime('monday this week'), $semStartWeekTs);         // tuần mặc định: tuần này, hoặc tuần đầu kỳ nếu chưa tới
$isHomeWeek  = $weekStartTs === $homeWeekTs;
$homeLabel   = strtotime('monday this week') < $semStartWeekTs ? 'Tuần đầu kỳ' : 'Tuần này';
$weekDates = [];
for ($i = 0; $i < 7; $i++) $weekDates[] = date('Y-m-d', strtotime("+$i days", $weekStartTs));
$prevWeek = date('Y-m-d', strtotime('-7 days', $weekStartTs));
$nextWeek = date('Y-m-d', strtotime('+7 days', $weekStartTs));
$todayStr = date('Y-m-d');
$dayShort = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];

$wk = $db->prepare('SELECT fs.*, s.subject_name, s.color FROM fap_sessions fs LEFT JOIN subjects s ON s.id = fs.subject_id WHERE fs.student_id = ? AND fs.session_date BETWEEN ? AND ? ORDER BY fs.slot_number');
$wk->execute([$sid, $weekDates[0], $weekDates[6]]);
$weekGrid = []; $slotTimes = [];
foreach ($wk->fetchAll() as $r) {
    $n = (int)$r['slot_number'];
    $weekGrid[$n][$r['session_date']][] = $r;
    if ($r['start_time'] && empty($slotTimes[$n])) $slotTimes[$n] = $r['start_time'] . '-' . $r['end_time'];
}
ksort($weekGrid);
$weekSessionCount = array_sum(array_map(fn($byDate) => array_sum(array_map('count', $byDate)), $weekGrid));

// Tính % hoàn thành Deadline
$totalDeadlines = $db->prepare('SELECT COUNT(*) c FROM deadlines WHERE student_id = ?');
$totalDeadlines->execute([$sid]);
$totalDeadlinesCount = (int)$totalDeadlines->fetch()['c'];

$completedDeadlines = $db->prepare('SELECT COUNT(*) c FROM deadlines WHERE student_id = ? AND status = "completed"');
$completedDeadlines->execute([$sid]);
$completedDeadlinesCount = (int)$completedDeadlines->fetch()['c'];

$deadlinePercent = $totalDeadlinesCount > 0 ? round(($completedDeadlinesCount / $totalDeadlinesCount) * 100) : 0;

// Tính % hoàn thành Tự học
$totalSelfStudy = $db->prepare('SELECT COUNT(*) c FROM self_study_modules WHERE student_id = ?');
$totalSelfStudy->execute([$sid]);
$totalSelfStudyCount = (int)$totalSelfStudy->fetch()['c'];

$completedSelfStudy = $db->prepare('SELECT COUNT(*) c FROM self_study_modules WHERE student_id = ? AND status = "completed"');
$completedSelfStudy->execute([$sid]);
$completedSelfStudyCount = (int)$completedSelfStudy->fetch()['c'];

$selfStudyPercent = $totalSelfStudyCount > 0 ? round(($completedSelfStudyCount / $totalSelfStudyCount) * 100) : 0;

// Tự học tuần này (Từ Thứ 2 đến Chủ Nhật)
$monday = date('Y-m-d', strtotime('monday this week'));
$sunday = date('Y-m-d', strtotime('sunday this week'));
$selfStudyStmt = $db->prepare('
    SELECT * FROM self_study_modules 
    WHERE student_id = ? AND target_date BETWEEN ? AND ? 
    ORDER BY status = "completed", target_date ASC
');
$selfStudyStmt->execute([$sid, $monday, $sunday]);
$thisWeekModules = $selfStudyStmt->fetchAll();




$pageTitle = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="hero-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 280px;">
        <h1 style="margin: 0 0 8px;">Chào <?= htmlspecialchars($student['full_name']) ?>! 👋</h1>
        <p style="margin: 0; color: var(--text-dim);"><?= htmlspecialchars($student['student_code']) ?> · <?= htmlspecialchars($student['major']) ?> — Đây là tổng quan việc học của bạn hôm nay. Giữ vững chuỗi ngày học và hoàn thành mục tiêu tuần này.</p>
    </div>

    <!-- Khối hiển thị 2 vòng tròn % tiến độ -->
    <div style="display: flex; gap: 24px; align-items: center;">
        <!-- Vòng tròn Tiến độ Deadline -->
        <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
            <div style="position: relative; width: 68px; height: 68px; border-radius: 50%; background: conic-gradient(var(--accent) <?= $deadlinePercent ?>%, var(--bg-panel) 0); display: flex; align-items: center; justify-content: center; box-shadow: 0 0 10px rgba(0,0,0,0.3);">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: var(--bg-panel-2); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; color: var(--text);">
                    <?= $deadlinePercent ?>%
                </div>
            </div>
            <span style="font-size: 12px; font-weight: 700; color: var(--text-dim);">Deadline</span>
        </div>

        <!-- Vòng tròn Tiến độ Tự học -->
        <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
            <div style="position: relative; width: 68px; height: 68px; border-radius: 50%; background: conic-gradient(var(--blue) <?= $selfStudyPercent ?>%, var(--bg-panel) 0); display: flex; align-items: center; justify-content: center; box-shadow: 0 0 10px rgba(0,0,0,0.3);">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: var(--bg-panel-2); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; color: var(--text);">
                    <?= $selfStudyPercent ?>%
                </div>
            </div>
            <span style="font-size: 12px; font-weight: 700; color: var(--text-dim);">Tự học</span>
        </div>
    </div>
</div>
<div class="card" id="week-mini" style="margin-bottom:20px;">
    <div class="card-header" style="flex-wrap:wrap; gap:8px;">
        <h2>Lịch học tuần <?= date('d/m', strtotime($weekDates[0])) ?> - <?= date('d/m/Y', strtotime($weekDates[6])) ?>
            <small style="font-weight:400; color:var(--text-dim);">(<?= (int)$weekSessionCount ?> buổi · học kỳ bắt đầu <?= date('d/m/Y', strtotime(QLHT_SEMESTER_START)) ?>)</small></h2>
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
            <?php if ($hasPrevWeek): ?><a class="btn btn-outline btn-sm" href="index.php?week=<?= $prevWeek ?>#week-mini">◀ Tuần trước</a><?php else: ?><span class="btn btn-outline btn-sm" style="opacity:.4; cursor:not-allowed;">◀ Tuần trước</span><?php endif; ?>
            <?php if (!$isHomeWeek): ?><a class="btn btn-accent btn-sm" href="index.php#week-mini"><?= $homeLabel ?></a><?php endif; ?>
            <a class="btn btn-outline btn-sm" href="index.php?week=<?= $nextWeek ?>#week-mini">Tuần sau ▶</a>
            <a class="btn btn-outline btn-sm" href="schedule.php">Xem danh sách</a>
        </div>
    </div>

    <?php if ($weekGrid): ?>
    <div style="overflow-x:auto;">
        <table style="width:100%; min-width:680px; table-layout:fixed; border-collapse:collapse; font-size:12px;">
            <thead>
                <tr>
                    <th style="width:72px; text-align:left; padding:6px 4px;">Slot</th>
                    <?php foreach ($weekDates as $i => $dt): ?>
                        <th style="text-align:center; padding:6px 4px; <?= $dt === $todayStr ? 'color:var(--accent);' : '' ?>">
                            <?= $dayShort[$i] ?><br><span style="font-weight:400;"><?= date('d/m', strtotime($dt)) ?></span>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($weekGrid as $slotNo => $byDate): ?>
                <tr style="border-top:1px solid rgba(255,255,255,.08);">
                    <td style="padding:6px 4px; vertical-align:top;">
                        <strong><?= (int)$slotNo ?></strong><br>
                        <span style="color:var(--text-dim); font-size:11px;"><?= htmlspecialchars($slotTimes[$slotNo] ?? '') ?></span>
                    </td>
                    <?php foreach ($weekDates as $dt): ?>
                        <td style="padding:4px; vertical-align:top; <?= $dt === $todayStr ? 'background:rgba(34,197,94,.08);' : '' ?>">
                            <?php foreach ($byDate[$dt] ?? [] as $r): ?>
                                <div title="<?= htmlspecialchars($r['subject_name'] ?? $r['subject_code']) ?>"
                                     style="border-left:3px solid <?= htmlspecialchars($r['color'] ?: '#64748b') ?>; background:rgba(255,255,255,.05); padding:4px 6px; border-radius:6px; line-height:1.35; <?= $r['status'] === 'absent' ? 'opacity:.7;' : '' ?>">
                                    <strong><?= htmlspecialchars($r['subject_code']) ?></strong>
                                    <?= $r['status'] === 'attended' ? '<span style="color:var(--accent);">✔</span>' : ($r['status'] === 'absent' ? '<span style="color:var(--red);">✖</span>' : '') ?>
                                    <?php if ($r['room']): ?><br><span style="color:var(--text-dim);"><?= htmlspecialchars($r['room']) ?></span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">Tuần này không có buổi học nào. 🎉 <a href="schedule.php">Cập nhật dữ liệu từ FAP</a></div>
    <?php endif; ?>
</div>

<div class="card" style="margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
        <h3 style="margin:0 0 4px;">Đơn từ FAP</h3>
        <span style="color:var(--text-dim); font-size:14px;">Bạn đang có <strong style="color:var(--orange)"><?= (int)$pendingFap ?></strong> đơn chờ xử lý trên FAP.</span>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="fap.php" class="btn btn-outline btn-sm">Xem tất cả đơn đã gửi</a>
        <a href="fap.php" class="btn btn-accent btn-sm">+ Tạo đơn</a>
    </div>
</div>

<div class="grid grid-3" style="margin-top:20px;">
    <div class="card">
        <h3>Tiến độ môn học</h3>
        <div class="progress-row">
            <span>Hoàn thành / Miễn</span>
            <span class="num"><?= $completed + $exempted ?> / <?= $totalSubjects ?></span>
        </div>
        <div class="progress-track">
            <div class="progress-fill" style="width:<?= $totalSubjects ? round(($completed + $exempted)/$totalSubjects*100) : 0 ?>%"></div>
        </div>
        <p style="color:var(--text-dim); font-size:13px; margin-bottom:0; margin-top:14px; display: flex; justify-content: space-between;">
            <span><?= $ongoing ?> môn đang học</span>
            <?php if ($exempted > 0): ?><span style="color:var(--purple); font-weight:600;"><?= $exempted ?> môn được miễn</span><?php endif; ?>
        </p>
    </div>
    <div class="card">
        <h3>Tỉ lệ chuyên cần</h3>
        <div class="progress-row"><span>Có mặt</span><span class="num"><?= $presentCount ?> / <?= $totalAtt ?></span></div>
        <div class="progress-track"><div class="progress-fill" style="width:<?= round($presentCount/$totalAtt*100) ?>%"></div></div>
        <p style="color:var(--text-dim); font-size:13px; margin-bottom:0; margin-top:14px;"><a href="attendance.php">Xem chi tiết điểm danh →</a></p>
    </div>
    <?php
    // Deadline sắp tới (chưa hoàn thành)
$upcomingDeadlines = $db->prepare('
    SELECT d.*, s.subject_code, s.color 
    FROM deadlines d 
    LEFT JOIN subjects s ON d.subject_id = s.id 
    WHERE d.student_id = ? AND d.status = "pending" 
    ORDER BY d.due_date ASC LIMIT 5
');
$upcomingDeadlines->execute([$sid]);
$upcomingDeadlines = $upcomingDeadlines->fetchAll();
    ?> 
    <div class="card">
    <div class="card-header">
        <h2>Deadline sắp tới</h2>
        <a href="todos.php" class="btn btn-outline btn-sm">Xem tất cả</a>
    </div>
    <?php if ($upcomingDeadlines): ?>
        <table style="font-size: 13px;">
            <tbody>
            <?php foreach ($upcomingDeadlines as $d): ?>
                <tr>
                    <td>
                        <?php if($d['subject_code']): ?>
                            <span class="dot" style="background:<?= htmlspecialchars($d['color']) ?>"></span> 
                            <strong><?= htmlspecialchars($d['subject_code']) ?></strong>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($d['title']) ?></td>
                    <td style="<?= strtotime($d['due_date']) < time() ? 'color:var(--red);' : 'color:var(--text-dim);' ?>">
                        <?= date('H:i d/m', strtotime($d['due_date'])) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color:var(--text-dim); font-size:13px; text-align:center;">Tuyệt vời! Bạn đã hoàn thành mọi việc.</p>
    <?php endif; ?>
</div>
    <div class="card">
        <h3>Môn học Online</h3>
        <?php $onlineCount = count(array_filter($subjects, fn($s) => $s['is_online'])); ?>
        <div class="progress-row"><span>Đang học Online</span><span class="num"><?= $onlineCount ?> môn</span></div>
        <div class="progress-track"><div class="progress-fill" style="width:<?= $totalSubjects ? round($onlineCount/$totalSubjects*100) : 0 ?>%"></div></div>
        <p style="color:var(--text-dim); font-size:13px; margin-bottom:0; margin-top:14px;"><a href="online.php">Vào lớp học Online →</a></p>
    </div>
</div>
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h2>Tự học tuần này (<?= date('d/m', strtotime($monday)) ?> - <?= date('d/m', strtotime($sunday)) ?>)</h2>
        <a href="self_study.php" class="btn btn-outline btn-sm">Quản lý tự học</a>
    </div>
    <?php if ($thisWeekModules): ?>
    <table>
        <thead>
            <tr><th>Ngày</th><th>Module / Bài học</th><th>Nền tảng</th><th>Trạng thái</th></tr>
        </thead>
        <tbody>
        <?php foreach ($thisWeekModules as $m): 
            $statusMap = [
                'pending' => ['Chưa bắt đầu', 'badge-gray'],
                'in_progress' => ['Đang học', 'badge-blue'],
                'completed' => ['Hoàn thành', 'badge-green']
            ];
            [$lbl, $c] = $statusMap[$m['status']] ?? ['Không xác định', 'badge-gray'];
            $isOverdue = ($m['target_date'] < date('Y-m-d') && $m['status'] !== 'completed');
        ?>
            <tr style="<?= $m['status'] === 'completed' ? 'opacity: 0.6;' : '' ?>">
                <td style="<?= $isOverdue ? 'color:var(--red); font-weight:bold;' : '' ?>">
                    <?= date('d/m', strtotime($m['target_date'])) ?>
                    <?php if ($isOverdue) echo ' <span style="font-size:11px;">(Trễ)</span>'; ?>
                </td>
                <td>
                    <strong style="<?= $m['status'] === 'completed' ? 'text-decoration: line-through;' : '' ?>">
                        <?= htmlspecialchars($m['module_name']) ?>
                    </strong>
                </td>
                <td><?= htmlspecialchars($m['platform'] ?: '—') ?></td>
                <td><span class="badge <?= $c ?>"><?= $lbl ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="color:var(--text-dim); text-align:center; padding: 20px 0;">Tuần này bạn không có lịch tự học nào. 🎉</p>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:20px;">
    <div class="card-header"><h2>Danh sách môn học kỳ này</h2><a href="subjects.php" class="btn btn-outline btn-sm">Quản lý môn học</a></div>
    <table>
        <thead><tr><th>Mã môn</th><th>Tên môn</th><th>Số tín chỉ</th><th>Giảng viên</th><th>Trạng thái</th></tr></thead>
        <tbody>
        <?php foreach ($subjects as $s): ?>
            <tr>
                <td><span class="dot" style="background:<?= htmlspecialchars($s['color']) ?>"></span> <?= htmlspecialchars($s['subject_code']) ?></td>
                <td><?= htmlspecialchars($s['subject_name']) ?></td>
                <td><?= (int)$s['credits'] ?></td>
                <td><?= htmlspecialchars($s['lecturer'] ?: '—') ?></td>
               <td>
                    <?php
                    $map = [
                        'ongoing' => ['Đang học','badge-blue'], 
                        'completed' => ['Passed','badge-green'], 
                        'not_started' => ['Chưa bắt đầu','badge-gray'],
                        'exempted' => ['Miễn môn','badge-purple']
                    ];
                    
                    // Lấy giá trị status, nếu trống hoặc mảng không chứa key này thì dùng fallback
                    $status_key = $s['status'] ?? 'not_started';
                    [$label, $cls] = $map[$status_key] ?? ['Không xác định', 'badge-gray'];
                    ?>
                    <span class="badge <?= $cls ?>"><?= $label ?></span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
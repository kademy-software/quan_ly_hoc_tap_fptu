<?php
// Helper cho tính năng "Cập nhật dữ liệu từ FAP" (Cookie / Bookmarklet / Automa -> QLHT)

function fapEnsureTable(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS fap_sync (
        student_id    INT NOT NULL PRIMARY KEY,
        token         CHAR(48) NOT NULL,
        last_sync_at  DATETIME NULL,
        last_count    INT NOT NULL DEFAULT 0,
        last_message  TEXT NULL,
        fap_cookie    TEXT NULL,
        sync_from_date DATE NULL COMMENT 'Ngày bắt đầu lấy lịch',
        sync_to_date   DATE NULL COMMENT 'Ngày kết thúc lấy lịch',
        UNIQUE KEY uq_fap_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migration: thêm các cột mới nếu bảng đã tồn tại từ trước
    foreach ([
        "ALTER TABLE fap_sync ADD COLUMN fap_cookie TEXT NULL",
        "ALTER TABLE fap_sync ADD COLUMN sync_from_date DATE NULL COMMENT 'Ngày bắt đầu lấy lịch'",
        "ALTER TABLE fap_sync ADD COLUMN sync_to_date DATE NULL COMMENT 'Ngày kết thúc lấy lịch'",
    ] as $sql) {
        try { $db->exec($sql); } catch (Throwable $e) {}
    }

    $db->exec("CREATE TABLE IF NOT EXISTS fap_sessions (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        student_id   INT NOT NULL,
        subject_id   INT NULL,
        subject_code VARCHAR(20) NOT NULL,
        room         VARCHAR(50) NOT NULL DEFAULT '',
        session_date DATE NOT NULL,
        slot_number  TINYINT NOT NULL,
        start_time   VARCHAR(5) NULL,
        end_time     VARCHAR(5) NULL,
        status       VARCHAR(20) NOT NULL DEFAULT '',
        UNIQUE KEY uq_session (student_id, session_date, slot_number),
        KEY idx_date (student_id, session_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function fapGetRow(PDO $db, int $sid): array {
    fapEnsureTable($db);
    $st = $db->prepare('SELECT * FROM fap_sync WHERE student_id = ?');
    $st->execute([$sid]);
    $row = $st->fetch();
    if (!$row) {
        $token = bin2hex(random_bytes(24));
        $db->prepare('INSERT INTO fap_sync (student_id, token) VALUES (?, ?)')->execute([$sid, $token]);
        $st->execute([$sid]);
        $row = $st->fetch();
    }
    return $row;
}

function fapSaveCookie(PDO $db, int $sid, string $cookie): void {
    fapEnsureTable($db);
    $db->prepare('UPDATE fap_sync SET fap_cookie = ? WHERE student_id = ?')
       ->execute([trim($cookie), $sid]);
}

/**
 * Lưu cấu hình đồng bộ (khoảng ngày lấy lịch)
 */
function fapSaveConfig(PDO $db, int $sid, ?string $fromDate, ?string $toDate): void {
    fapEnsureTable($db);
    if ($fromDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) $fromDate = null;
    if ($toDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) $toDate = null;

    $db->prepare('UPDATE fap_sync SET sync_from_date = ?, sync_to_date = ? WHERE student_id = ?')
       ->execute([$fromDate, $toDate, $sid]);
}

/**
 * Lưu danh sách sessions vào CSDL cho sinh viên.
 * Chỉ lưu những buổi có session_date trong khoảng [fromDate, toDate] (nếu có thiết lập).
 */
function fapSaveSessions(PDO $db, int $sid, array $sessions, string $fromDate = '', string $toDate = ''): array {
    fapEnsureTable($db);
    $sid = (int)$sid;

    $row = fapGetRow($db, $sid);
    if ($fromDate === '') {
        $fromDate = $row['sync_from_date'] ?? '';
    }
    if ($toDate === '') {
        $toDate = $row['sync_to_date'] ?? '';
    }

    $subj = [];
    $q = $db->prepare('SELECT id, subject_code FROM subjects WHERE student_id = ?');
    $q->execute([$sid]);
    foreach ($q->fetchAll() as $r) {
        $subj[strtoupper(trim($r['subject_code']))] = (int)$r['id'];
    }

    $up = $db->prepare('INSERT INTO fap_sessions
        (student_id, subject_id, subject_code, room, session_date, slot_number, start_time, end_time, status)
        VALUES (?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE subject_id = VALUES(subject_id), subject_code = VALUES(subject_code),
            room = VALUES(room), start_time = VALUES(start_time), end_time = VALUES(end_time), status = VALUES(status)');

    $time = fn($v) => preg_match('/^\d{1,2}:\d{2}$/', (string)$v) ? (string)$v : null;
    $added = 0;
    $updated = 0;
    $skipped = 0;

    $db->beginTransaction();
    try {
        foreach ($sessions as $s) {
            $date = (string)($s['date'] ?? '');
            $slot = (int)($s['slot'] ?? 0);
            $code = strtoupper(trim((string)($s['code'] ?? '')));

            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)
                || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) continue;
            if ($slot < 1 || $slot > 12 || $code === '' || strlen($code) > 20) continue;

            // Lọc theo Từ ngày
            if (!empty($fromDate) && $date < $fromDate) { $skipped++; continue; }
            // Lọc theo Đến ngày
            if (!empty($toDate) && $date > $toDate) { $skipped++; continue; }

            $room   = mb_substr(trim((string)($s['room'] ?? '')), 0, 50);
            $status = in_array($s['status'] ?? '', ['attended', 'absent'], true) ? $s['status'] : '';

            $up->execute([
                $sid, $subj[$code] ?? null, $code, $room, $date, $slot,
                $time($s['start'] ?? ''), $time($s['end'] ?? ''), $status
            ]);
            $n = $up->rowCount();
            if ($n === 1) $added++; elseif ($n === 2) $updated++;
        }
        $rangeInfo = ($fromDate || $toDate) ? "trong khoảng " . ($fromDate ?: 'đầu') . " đến " . ($toDate ?: 'cuối') : "";
        $msg = "Thêm $added buổi, cập nhật $updated buổi (bỏ qua $skipped buổi ngoài khoảng chọn $rangeInfo)";
        
        $db->prepare('UPDATE fap_sync SET last_sync_at = NOW(), last_count = ?, last_message = ? WHERE student_id = ?')
           ->execute([$added + $updated, $msg, $sid]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    return [
        'ok'        => true,
        'added'     => $added,
        'updated'   => $updated,
        'skipped'   => $skipped,
        'total'     => $added + $updated,
        'from_date' => $fromDate,
        'to_date'   => $toDate,
    ];
}

/**
 * Kiểm tra xem tiết cố định có áp dụng vào ngày chỉ định hay không
 */
function slotAppliesOn(array $r, string $date): bool {
    if (!empty($r['start_date']) && $date < $r['start_date']) return false;
    if (!empty($r['end_date']) && $date > $r['end_date']) return false;
    if (!empty($r['week_type']) && $r['week_type'] !== 'all') {
        $odd = ((int)(new DateTime($date))->format('W')) % 2 === 1;
        if (($r['week_type'] === 'odd') !== $odd) return false;
    }
    return true;
}

/**
 * Lấy toàn bộ lịch học của 1 tuần (FAP + Cố định + Sự kiện cá nhân)
 * Dùng chung duy nhất cho cả Dashboard (index.php) và schedule.php
 */
function getWeekScheduleData(PDO $db, int $sid, string $anchorDate = ''): array {
    fapEnsureTable($db);

    if (!$anchorDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $anchorDate)) {
        $anchorDate = date('Y-m-d');
    }

    $ws = new DateTime($anchorDate);
    $ws->modify('-' . ((int)$ws->format('N') - 1) . ' days'); // Về Thứ 2 của tuần
    $we = (clone $ws)->modify('+6 days');                     // Đến Chủ nhật

    $from = $ws->format('Y-m-d');
    $to   = $we->format('Y-m-d');

    $weekDates = [];
    for ($i = 0; $i < 7; $i++) {
        $weekDates[] = (clone $ws)->modify("+$i days")->format('Y-m-d');
    }

    // Bảng giờ chuẩn từng slot (nếu slot nào không có giờ từ FAP)
    $defaultSlotTimes = [
        1 => ['07:00', '08:30'], 2 => ['08:45', '10:15'], 3 => ['10:30', '12:00'], 4 => ['12:30', '14:00'],
        5 => ['14:15', '15:45'], 6 => ['16:00', '17:30'], 7 => ['17:45', '19:15'], 8 => ['19:30', '21:00'],
    ];

    $items = [];
    $fapKeys = [];
    $slotTimes = [];

    // 1. Buổi học từ FAP
    $st = $db->prepare('SELECT fs.session_date, fs.slot_number, fs.start_time, fs.end_time, fs.subject_code, fs.room, fs.status, s.subject_name, s.color
        FROM fap_sessions fs 
        LEFT JOIN subjects s ON s.id = fs.subject_id
        WHERE fs.student_id = ? AND fs.session_date BETWEEN ? AND ? 
        ORDER BY fs.slot_number');
    $st->execute([$sid, $from, $to]);
    foreach ($st->fetchAll() as $r) {
        $slotNo = (int)$r['slot_number'];
        $fapKeys[$r['session_date'] . '#' . $slotNo] = true;

        if ($r['start_time'] && $r['end_time'] && empty($slotTimes[$slotNo])) {
            $slotTimes[$slotNo] = $r['start_time'] . '–' . $r['end_time'];
        }

        $items[] = [
            'kind'         => 'fap',
            'date'         => $r['session_date'],
            'slot'         => $slotNo,
            'start'        => $r['start_time'] ?: ($defaultSlotTimes[$slotNo][0] ?? ''),
            'end'          => $r['end_time'] ?: ($defaultSlotTimes[$slotNo][1] ?? ''),
            'code'         => $r['subject_code'],
            'name'         => $r['subject_name'] ?? $r['subject_code'],
            'room'         => $r['room'],
            'color'        => $r['color'] ?: '#2563EB',
            'status'       => $r['status'],
        ];
    }

    // 2. Tiết cố định hằng tuần (tự xếp từ danh sách môn học)
    $st = $db->prepare('SELECT ss.*, s.subject_code, s.subject_name, s.color
        FROM schedule_slots ss 
        JOIN subjects s ON s.id = ss.subject_id 
        WHERE ss.student_id = ?');
    $st->execute([$sid]);
    $fixed = $st->fetchAll();

    for ($i = 0; $i < 7; $i++) {
        $date = $weekDates[$i];
        foreach ($fixed as $r) {
            $slotNo = (int)$r['slot_number'];
            if ((int)$r['day_of_week'] !== $i + 2 || !slotAppliesOn($r, $date)) continue;
            // Nếu ô này FAP đã có buổi học thì ưu tiên FAP
            if (isset($fapKeys[$date . '#' . $slotNo])) continue;

            if (empty($slotTimes[$slotNo]) && isset($defaultSlotTimes[$slotNo])) {
                $slotTimes[$slotNo] = $defaultSlotTimes[$slotNo][0] . '–' . $defaultSlotTimes[$slotNo][1];
            }

            $items[] = [
                'kind'        => 'fixed',
                'id'          => (int)$r['id'],
                'subject_id'  => (int)$r['subject_id'],
                'date'        => $date,
                'slot'        => $slotNo,
                'day_of_week' => (int)$r['day_of_week'],
                'code'        => $r['subject_code'],
                'name'        => $r['subject_name'],
                'room'        => $r['room'] ?? '',
                'color'       => $r['color'] ?: '#2563EB',
                'week_type'   => $r['week_type'],
                'start_date'  => $r['start_date'],
                'end_date'    => $r['end_date'],
                'status'      => '',
                'start'       => $defaultSlotTimes[$slotNo][0] ?? '',
                'end'         => $defaultSlotTimes[$slotNo][1] ?? '',
            ];
        }
    }

    // 3. Lịch cá nhân (personal_events)
    try {
        $st = $db->prepare('SELECT * FROM personal_events WHERE student_id = ? AND event_date BETWEEN ? AND ?');
        $st->execute([$sid, $from, $to]);
        foreach ($st->fetchAll() as $e) {
            $t = fn($v) => $v ? substr($v, 0, 5) : '';
            $items[] = [
                'kind'  => 'event',
                'date'  => $e['event_date'],
                'slot'  => 0, // Slot 0 là sự kiện đặc biệt
                'name'  => $e['title'],
                'code'  => 'Sự kiện',
                'start' => $t($e['start_time']),
                'end'   => $t($e['end_time']),
                'room'  => $e['location'] ?? '',
                'color' => $e['color'] ?: '#F59E0B',
                'status'=> '',
                'ev'    => [
                    'id'         => (int)$e['id'],
                    'title'      => $e['title'],
                    'event_date' => $e['event_date'],
                    'start_time' => $t($e['start_time']),
                    'end_time'   => $t($e['end_time']),
                    'location'   => $e['location'] ?? '',
                    'note'       => $e['note'] ?? '',
                    'color'      => $e['color']
                ]
            ];
        }
    } catch (Throwable $ex) {}

    // Xây dựng cấu trúc grid theo Slot và Date
    $grid = [];
    foreach ($items as $it) {
        $s = (int)$it['slot'];
        $grid[$s][$it['date']][] = $it;
    }
    ksort($grid);

    // Bổ sung slotTimes cho các slot còn thiếu
    foreach (array_keys($grid) as $s) {
        if ($s > 0 && empty($slotTimes[$s]) && isset($defaultSlotTimes[$s])) {
            $slotTimes[$s] = $defaultSlotTimes[$s][0] . '–' . $defaultSlotTimes[$s][1];
        }
    }

    $totalSessions = array_sum(array_map(fn($byDate) => array_sum(array_map('count', $byDate)), $grid));

    return [
        'from'           => $from,
        'to'             => $to,
        'week_dates'     => $weekDates,
        'week_no'        => (int)$ws->format('W'),
        'items'          => $items,
        'grid'           => $grid,
        'slot_times'     => $slotTimes,
        'total_sessions' => $totalSessions,
        'prev_week'      => date('Y-m-d', strtotime('-7 days', $ws->getTimestamp())),
        'next_week'      => date('Y-m-d', strtotime('+7 days', $ws->getTimestamp())),
    ];
}
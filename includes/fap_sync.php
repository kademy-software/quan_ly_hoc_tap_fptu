<?php
// Ngày bắt đầu học kỳ (Thứ 2 của tuần đầu tiên). Đổi ở đây nếu cần.
const QLHT_SEMESTER_START = '2026-11-02';

// Helper cho tính năng "Cập nhật dữ liệu từ FAP" (Automa -> QLHT)

function fapEnsureTable(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS fap_sync (
        student_id   INT NOT NULL PRIMARY KEY,
        token        CHAR(48) NOT NULL,
        last_sync_at DATETIME NULL,
        last_count   INT NOT NULL DEFAULT 0,
        last_message TEXT NULL,
        UNIQUE KEY uq_fap_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Lịch học theo ngày cụ thể (không cố định theo tuần)
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
<?php
// $activePage phai duoc set truoc khi include file nay: dashboard|subjects|schedule|grades|attendance|online|fap|profile
$student = currentStudent();
$initial = $student ? mb_substr($student['full_name'], 0, 1) : '?';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - QLHT' : 'QLHT' ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="topbar">
    <div class="brand">
        <div class="logo">QL</div>
        QLHT
    </div>
    <nav>
        <a href="index.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="subjects.php" class="<?= $activePage === 'subjects' ? 'active' : '' ?>">Môn học</a>
        <a href="schedule.php" class="<?= $activePage === 'schedule' ? 'active' : '' ?>">Lịch học</a>
        <a href="grades.php" class="<?= $activePage === 'grades' ? 'active' : '' ?>">Điểm số</a>
        <a href="attendance.php" class="<?= $activePage === 'attendance' ? 'active' : '' ?>">Điểm danh</a>
        <a href="online.php" class="<?= $activePage === 'online' ? 'active' : '' ?>">Học Online</a>
        <a href="self_study.php" class="<?= ($activePage === 'self_study') ? 'active' : '' ?>">Tự học</a>
        <a href="fap.php" class="<?= $activePage === 'fap' ? 'active' : '' ?>">Đơn FAP</a>
         <a href="https://fap.fpt.edu.vn/Student.aspx" class="<?= $activePage === 'fap' ? 'active' : '' ?>">FAP</a>
         <div style="width: 1px; background: var(--border); margin: 0 8px;"></div> <!-- Dấu gạch dọc phân cách -->
    <a href="labs.php" class="<?= ($activePage === 'labs') ? 'active' : '' ?>" style="color: var(--red);"> Labs</a>
        <a href="quiz_take.php" class="<?= ($activePage === 'quiz') ? 'active' : '' ?>" style="color: var(--pink);">Luyện tập trắc nghiệm</a>
    <a href="writeups.php" class="<?= ($activePage === 'writeups') ? 'active' : '' ?>" style="color: var(--orange);"> Writeups</a>
    <a href="payloads.php" class="<?= ($activePage === 'payloads') ? 'active' : '' ?>" style="color: var(--blue);"> Mã hóa & Lưu trữ</a>
    <a href="certs.php" class="<?= ($activePage === 'certs') ? 'active' : '' ?>" style="color: var(--accent);"> Chứng chỉ</a>
    </nav>
    <div class="right">
        <?php if ($student): ?>
        <div class="stat-pill" title="Điểm rèn luyện"><span>💎</span> <?= (int)$student['points'] ?></div>
        <div class="stat-pill" title="Chuỗi ngày học liên tiếp"><span>🔥</span> <?= (int)$student['streak_days'] ?></div>
        <a href="profile.php" class="avatar"><?= htmlspecialchars($initial) ?></a>
        <?php endif; ?>
    </div>
</div>
<div class="page-wrap">

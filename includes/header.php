<?php
// $activePage phải được set trước khi include file này:
// dashboard|subjects|schedule|grades|attendance|online|fap|self_study
// labs|quiz|writeups|payloads|todos|videos|progress|assessments|profile
$student = currentStudent();
$initial = $student ? mb_substr($student['full_name'], 0, 1) : '?';

$styleVer = file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : '1';
$themeVer = file_exists(__DIR__ . '/../assets/css/theme.css') ? filemtime(__DIR__ . '/../assets/css/theme.css') : '1';
$themeJsVer = file_exists(__DIR__ . '/../assets/js/theme.js') ? filemtime(__DIR__ . '/../assets/js/theme.js') : '1';

$isSelfStudyActive = in_array($activePage ?? '', ['self_study', 'videos', 'online'], true);
$isLabsActive = in_array($activePage ?? '', ['labs', 'quiz', 'writeups', 'payloads', 'assessments'], true);
$isFapActive = ($activePage ?? '') === 'fap';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - QLHT' : 'QLHT' ?></title>
<?php
$FA = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">';
echo $FA;
?>
<link rel="stylesheet" href="assets/css/style.css?v=<?= $styleVer ?>">
<link rel="stylesheet" href="assets/css/theme.css?v=<?= $themeVer ?>">
<script src="assets/js/theme.js?v=<?= $themeJsVer ?>"></script>
</head>
<body>
<div class="topbar">
    <div class="brand">
        <a href="index.php" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
            <div class="logo">QL</div>
            <span>QLHT</span>
        </a>
    </div>

    <nav id="mainNav">
        <!-- Nhóm học tập chính -->
        <a href="index.php" class="<?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-house" aria-hidden="true"></i> Dashboard
        </a>
        <a href="progress.php" class="<?= ($activePage ?? '') === 'progress' ? 'active' : '' ?>">
            <i class="fa-solid fa-bars-progress" aria-hidden="true"></i> Tiến độ
        </a>
        <a href="subjects.php" class="<?= ($activePage ?? '') === 'subjects' ? 'active' : '' ?>">
            <i class="fa-solid fa-book" aria-hidden="true"></i> Môn học
        </a>
        <a href="schedule.php" class="<?= ($activePage ?? '') === 'schedule' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Lịch học
        </a>
        <a href="grades.php" class="<?= ($activePage ?? '') === 'grades' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line" aria-hidden="true"></i> Điểm số
        </a>
        <a href="attendance.php" class="<?= ($activePage ?? '') === 'attendance' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-check" aria-hidden="true"></i> Điểm danh
        </a>
        <a href="todos.php" class="<?= ($activePage ?? '') === 'todos' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock" aria-hidden="true"></i> Deadlines
        </a>

        <span class="nav-sep"></span>

        <!-- Dropdown: Tự học & Tài nguyên -->
        <div class="nav-dropdown">
            <button type="button" class="nav-drop-btn <?= $isSelfStudyActive ? 'active' : '' ?>">
                <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Tự học <i class="fa-solid fa-chevron-down nav-caret" aria-hidden="true"></i>
            </button>
            <div class="nav-dropdown-menu">
                <a href="self_study.php" class="<?= ($activePage ?? '') === 'self_study' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open" aria-hidden="true"></i> Module tự học
                </a>
                <a href="videos.php" class="<?= ($activePage ?? '') === 'videos' ? 'active' : '' ?>">
                    <i class="fa-solid fa-video" aria-hidden="true"></i> Kho Videos
                </a>
                <a href="online.php" class="<?= ($activePage ?? '') === 'online' ? 'active' : '' ?>">
                    <i class="fa-solid fa-globe" aria-hidden="true"></i> Môn học Online
                </a>
            </div>
        </div>

        <!-- Dropdown: Labs & Trắc nghiệm -->
        <div class="nav-dropdown">
            <button type="button" class="nav-drop-btn <?= $isLabsActive ? 'active' : '' ?>" style="<?= $isLabsActive ? '' : 'color: var(--blue);' ?>">
                <i class="fa-solid fa-flask" aria-hidden="true"></i> Labs & Quiz <i class="fa-solid fa-chevron-down nav-caret" aria-hidden="true"></i>
            </button>
            <div class="nav-dropdown-menu">
                <a href="labs.php" class="<?= ($activePage ?? '') === 'labs' ? 'active' : '' ?>" style="color: var(--red);">
                    <i class="fa-solid fa-server" aria-hidden="true"></i> Lab Environment
                </a>
                <a href="quiz_take.php" class="<?= ($activePage ?? '') === 'quiz' ? 'active' : '' ?>" style="color: var(--pink);">
                    <i class="fa-solid fa-clipboard-question" aria-hidden="true"></i> Luyện tập trắc nghiệm
                </a>
                <a href="quiz_create.php" style="color: var(--pink);">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Tạo bài trắc nghiệm
                </a>
                <a href="assessments.php" class="<?= ($activePage ?? '') === 'assessments' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Bài thi / Đánh giá
                </a>
                <a href="writeups.php" class="<?= ($activePage ?? '') === 'writeups' ? 'active' : '' ?>" style="color: var(--orange);">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i> Ghi chép (Writeups)
                </a>
                <a href="payloads.php" class="<?= ($activePage ?? '') === 'payloads' ? 'active' : '' ?>" style="color: var(--blue);">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Mã hóa & Lưu trữ
                </a>
            </div>
        </div>

        <span class="nav-sep"></span>

        <!-- Dropdown: FAP -->
        <div class="nav-dropdown">
            <button type="button" class="nav-drop-btn <?= $isFapActive ? 'active' : '' ?>">
                <i class="fa-solid fa-file-signature" aria-hidden="true"></i> FAP <i class="fa-solid fa-chevron-down nav-caret" aria-hidden="true"></i>
            </button>
            <div class="nav-dropdown-menu">
                <a href="fap.php" class="<?= ($activePage ?? '') === 'fap' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-circle-check" aria-hidden="true"></i> Đơn dịch vụ FAP
                </a>
                <a href="https://fap.fpt.edu.vn/Student.aspx" target="_blank" rel="noopener">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Cổng thông tin FAP
                </a>
            </div>
        </div>
    </nav>

    <div class="right">
        <button type="button" id="themeToggle" class="icon-btn" title="Chuyển đổi giao diện sáng/tối"></button>
        <?php if ($student): ?>
        <div class="stat-pill" title="Điểm rèn luyện"><i class="fa-solid fa-gem" style="color:var(--blue);" aria-hidden="true"></i> <?= (int)$student['points'] ?></div>
        <div class="stat-pill" title="Chuỗi ngày học liên tiếp"><i class="fa-solid fa-fire" style="color:var(--orange);" aria-hidden="true"></i> <?= (int)$student['streak_days'] ?></div>
        <a href="profile.php" class="avatar" title="Hồ sơ: <?= htmlspecialchars($student['full_name']) ?>"><?= htmlspecialchars($initial) ?></a>
        <?php endif; ?>
    </div>
</div>
<div class="page-wrap">
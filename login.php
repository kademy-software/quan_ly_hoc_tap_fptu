<?php
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['student_code'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (attemptLogin($code, $pass)) {
        header('Location: index.php');
        exit;
    }
    $error = 'Mã số sinh viên hoặc mật khẩu không đúng.';
}

$styleVer = file_exists(__DIR__ . '/assets/css/style.css') ? filemtime(__DIR__ . '/assets/css/style.css') : '1';
$themeVer = file_exists(__DIR__ . '/assets/css/theme.css') ? filemtime(__DIR__ . '/assets/css/theme.css') : '1';
$themeJsVer = file_exists(__DIR__ . '/assets/js/theme.js') ? filemtime(__DIR__ . '/assets/js/theme.js') : '1';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập - QLHT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
<link rel="stylesheet" href="assets/css/style.css?v=<?= $styleVer ?>">
<link rel="stylesheet" href="assets/css/theme.css?v=<?= $themeVer ?>">
<script src="assets/js/theme.js?v=<?= $themeJsVer ?>"></script>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
            <div class="logo" style="width:38px; height:38px; border-radius:8px; background:linear-gradient(135deg, var(--accent), var(--accent-dark)); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:18px;">QL</div>
            <strong style="font-size:20px; color:var(--text);">QLHT</strong>
        </div>
        <h1 style="font-size:22px; margin:0 0 4px;">Đăng nhập</h1>
        <p style="color:var(--text-dim); margin-top:0; margin-bottom:20px; font-size:14px;">Hệ thống quản lý học tập cá nhân</p>
        <?php if ($error): ?><div class="flash error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-row">
                <label>Mã số sinh viên</label>
                <input type="text" name="student_code" placeholder="SE223216" required autofocus>
            </div>
            <div class="form-row">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Đăng nhập</button>
        </form>

        <p style="margin: 20px 0 0; text-align: center; font-size: 14px; color: var(--text-dim);">
            Chưa có tài khoản? <a href="register.php" style="color: var(--accent); font-weight: 700;">Đăng ký</a>
        </p>
    </div>
</div>
</body>
</html>
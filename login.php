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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập - QLHT</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <h1>Đăng nhập QLHT</h1>
        <p>Hệ thống quản lý học tập cá nhân</p>
        <?php if ($error): ?><div class="flash error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-row">
                <label>Mã số sinh viên</label>
                <input type="text" name="student_code" placeholder="SE223216" required>
            </div>
            <div class="form-row">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%">Đăng nhập</button>
        </form>
    </div>
</div>
</body>
</html>

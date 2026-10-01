<?php
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$errors = [];
$old = ['student_code' => '', 'full_name' => '', 'major' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['student_code'] = strtoupper(trim($_POST['student_code'] ?? ''));
    $old['full_name']    = trim($_POST['full_name'] ?? '');
    $old['major']        = trim($_POST['major'] ?? '');
    $old['email']        = trim($_POST['email'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (!preg_match('/^[A-Z0-9]{4,20}$/', $old['student_code'])) {
        $errors[] = 'Mã số sinh viên chỉ gồm chữ và số, dài 4–20 ký tự.';
    }
    if (mb_strlen($old['full_name']) < 2 || mb_strlen($old['full_name']) > 100) {
        $errors[] = 'Họ tên phải từ 2 đến 100 ký tự.';
    }
    if ($old['major'] === '' || mb_strlen($old['major']) > 150) {
        $errors[] = 'Vui lòng nhập ngành/lớp (tối đa 150 ký tự).';
    }
    if ($old['email'] !== '' && (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 100)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if (strlen($pass) < 8) {
        $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự.';
    }
    if ($pass !== $confirm) {
        $errors[] = 'Mật khẩu nhập lại không khớp.';
    }

    if (!$errors) {
        $chk = $db->prepare('SELECT 1 FROM students WHERE student_code = ?');
        $chk->execute([$old['student_code']]);
        if ($chk->fetchColumn()) {
            $errors[] = 'Mã số sinh viên này đã được đăng ký.';
        }
    }

    if (!$errors) {
        // Tài khoản đăng ký luôn là 'student' (cột role mặc định), không nhận role từ form
        $ins = $db->prepare(
            'INSERT INTO students (student_code, full_name, major, email, password_hash)
             VALUES (?, ?, ?, ?, ?)'
        );
        try {
            $ins->execute([
                $old['student_code'],
                $old['full_name'],
                $old['major'],
                $old['email'] !== '' ? $old['email'] : null,
                password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12])
            ]);
            // Đăng nhập luôn sau khi đăng ký
            if (attemptLogin($old['student_code'], $pass)) {
                header('Location: index.php');
                exit;
            }
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Không tạo được tài khoản (mã số sinh viên có thể đã tồn tại). Vui lòng thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng ký - QLHT</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card" style="max-width: 440px;">
        <h1>Đăng ký tài khoản QLHT</h1>
        <p>Tạo tài khoản để quản lý việc học của riêng bạn</p>

        <?php foreach ($errors as $err): ?>
            <div class="flash error"><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <form method="post" autocomplete="off">
            <div class="form-row">
                <label>Mã số sinh viên</label>
                <input type="text" name="student_code" placeholder="SE223216" value="<?= htmlspecialchars($old['student_code']) ?>" maxlength="20" required>
            </div>
            <div class="form-row">
                <label>Họ và tên</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($old['full_name']) ?>" maxlength="100" required>
            </div>
            <div class="form-row">
                <label>Ngành / Lớp</label>
                <input type="text" name="major" placeholder="PC_BCS_CD_22" value="<?= htmlspecialchars($old['major']) ?>" maxlength="150" required>
            </div>
            <div class="form-row">
                <label>Email (tùy chọn)</label>
                <input type="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" maxlength="100">
            </div>
            <div class="form-row">
                <label>Mật khẩu (tối thiểu 8 ký tự)</label>
                <input type="password" name="password" minlength="8" required>
            </div>
            <div class="form-row">
                <label>Nhập lại mật khẩu</label>
                <input type="password" name="password_confirm" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%">Đăng ký</button>
        </form>

        <p style="margin: 16px 0 0; text-align: center;">
            Đã có tài khoản? <a href="login.php" style="color: var(--accent); font-weight: 700;">Đăng nhập</a>
        </p>
    </div>
</div>
</body>
</html>
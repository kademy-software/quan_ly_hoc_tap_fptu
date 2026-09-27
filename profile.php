<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];
$student = currentStudent();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $student['password_hash'])) {
        $message = ['error', 'Mật khẩu hiện tại không đúng.'];
    } elseif (strlen($new) < 6) {
        $message = ['error', 'Mật khẩu mới phải có ít nhất 6 ký tự.'];
    } elseif ($new !== $confirm) {
        $message = ['error', 'Xác nhận mật khẩu không khớp.'];
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE students SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $sid]);
        $message = ['success', 'Đổi mật khẩu thành công.'];
    }
}

$pageTitle = 'Hồ sơ';
$activePage = 'profile';
include __DIR__ . '/includes/header.php';
?>

<h1>Hồ sơ cá nhân</h1>

<div class="grid grid-2">
    <div class="card">
        <h3 style="margin-top:0;">Thông tin sinh viên</h3>
        <div class="form-row"><label>Họ và tên</label><input value="<?= htmlspecialchars($student['full_name']) ?>" disabled></div>
        <div class="form-row"><label>Mã số sinh viên</label><input value="<?= htmlspecialchars($student['student_code']) ?>" disabled></div>
        <div class="form-row"><label>Ngành học</label><input value="<?= htmlspecialchars($student['major']) ?>" disabled></div>
        <div class="form-row"><label>Email</label><input value="<?= htmlspecialchars($student['email']) ?>" disabled></div>
        <a href="logout.php" class="btn btn-danger" style="display:block; text-align:center;">Đăng xuất</a>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Đổi mật khẩu</h3>
        <?php if ($message): ?><div class="flash <?= $message[0] ?>"><?= htmlspecialchars($message[1]) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-row"><label>Mật khẩu hiện tại</label><input type="password" name="current_password" required></div>
            <div class="form-row"><label>Mật khẩu mới</label><input type="password" name="new_password" required></div>
            <div class="form-row"><label>Xác nhận mật khẩu mới</label><input type="password" name="confirm_password" required></div>
            <button type="submit" class="btn btn-accent" style="width:100%">Cập nhật mật khẩu</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

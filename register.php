<?php
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();

$errors = [];
$old = ['fullname' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $old = ['fullname' => $fullname, 'email' => $email];

    if ($fullname === '')
        $errors[] = 'Vui lòng nhập họ tên.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Email không hợp lệ.';
    if (strlen($password) < 6)
        $errors[] = 'Mật khẩu tối thiểu 6 ký tự.';
    if ($password !== $confirm)
        $errors[] = 'Mật khẩu xác nhận không khớp.';

    if (!$errors) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch())
            $errors[] = 'Email này đã được đăng ký.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$fullname, $email, $hash]);   // role mặc định = 'customer'

        $_SESSION['register_success'] = 'Đăng ký thành công! Hãy đăng nhập.';
        header('Location: login.php');
        exit;
    }
}

$pageTitle = 'Đăng ký';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5" style="max-width: 460px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold text-center mb-4">Đăng ký tài khoản</h1>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?>
                        <div><?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="mb-3">
                    <label class="form-label" for="fullname">Họ và tên</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" required
                        value="<?= htmlspecialchars($old['fullname']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required
                        value="<?= htmlspecialchars($old['email']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="confirm_password">Nhập lại mật khẩu</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Đăng ký</button>
            </form>

            <p class="text-center mt-3 mb-0 small">
                Đã có tài khoản? <a href="login.php">Đăng nhập</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
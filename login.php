<?php
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();

// Đã đăng nhập thì về trang chủ
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        // Theo đề của leader (không lưu mật khẩu)
        $_SESSION['user'] = [
            'id' => $user['id'],
            'fullname' => $user['fullname'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        // Các key phẳng mà header.php / auth.php có thể đang dùng
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['fullname'];
        $_SESSION['user_role'] = $user['role'];

        header('Location: index.php');
        exit;
    }
    $error = 'Email hoặc mật khẩu không đúng.';
}

$pageTitle = 'Đăng nhập';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5" style="max-width: 460px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold text-center mb-4">Đăng nhập</h1>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
            </form>

            <p class="text-center mt-3 mb-0 small">
                Chưa có tài khoản? <a href="register.php">Đăng ký</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
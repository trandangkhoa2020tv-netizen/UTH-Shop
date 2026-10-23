<?php
// =============================================
// FILE: admin/login.php
// Trang đăng nhập khu vực Admin
// =============================================
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

// Nếu đã là admin thì chuyển thẳng vào dashboard
if (isAdmin()) {
    header('Location: /Web/admin/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && verifyPassword($password, $user['password'])) {
            loginUser($user);
            flashSuccess('Chào mừng trở lại, ' . $user['full_name'] . '!');
            header('Location: /Web/admin/index.php');
            exit();
        } else {
            $error = 'Email hoặc mật khẩu không đúng, hoặc tài khoản không có quyền admin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin | UTH Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #1a1a2e; font-family: 'Be Vietnam Pro', sans-serif; }
        .login-card { border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
        .login-header { background: linear-gradient(135deg, #0d6efd, #0a58ca); border-radius: 16px 16px 0 0; }
        .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.25); }
        .btn-login { background: linear-gradient(135deg, #0d6efd, #0a58ca); border: none; }
        .btn-login:hover { background: linear-gradient(135deg, #0a58ca, #084298); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card login-card">
                <div class="login-header text-white text-center py-4 px-4">
                    <i class="bi bi-shield-lock fs-1 mb-2 d-block"></i>
                    <h4 class="fw-bold mb-1">Admin Panel</h4>
                    <p class="mb-0 opacity-75 small">UTH Shop Management</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                       placeholder="admin@gmail.com" required autofocus>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold small">Mật khẩu</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="••••••••" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-login btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="/Web/index.php" class="text-muted small">
                            <i class="bi bi-arrow-left me-1"></i>Quay về trang chủ
                        </a>
                    </div>
                </div>
            </div>
            <p class="text-center text-secondary small mt-3">
                &copy; <?= date('Y') ?> UTH Shop – Admin Area
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

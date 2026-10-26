<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (isAdmin()) {
    header('Location: /Web/admin/index.php');
    exit;
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Phiên đăng nhập không hợp lệ. Vui lòng tải lại trang.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $statement = $conn->prepare('SELECT id, full_name, email, password, role FROM users WHERE email = :email AND role = :role LIMIT 1');
        $statement->execute([':email' => $email, ':role' => 'admin']);
        $admin = $statement->fetch(PDO::FETCH_ASSOC);
        if ($admin && password_verify($password, $admin['password'])) {
            loginUser($admin);
            $_SESSION['user'] = [
                'id' => (int)$admin['id'],
                'fullname' => $admin['full_name'],
                'email' => $admin['email'],
                'role' => $admin['role'],
            ];
            header('Location: /Web/admin/index.php');
            exit;
        }
        $error = 'Email hoặc mật khẩu quản trị không chính xác.';
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập quản trị | UTH Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/Web/assets/css/style.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
    <main class="auth-page admin-auth-page">
        <section class="auth-card admin-auth-card">
            <div class="auth-card__heading">
                <span class="auth-card__eyebrow">KHU VỰC QUẢN TRỊ</span>
                <h1 class="h2" id="admin-login-title">Đăng nhập quản trị</h1>
                <p>Đăng nhập để quản lý cửa hàng và đơn hàng.</p>
            </div>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger auth-alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="post" class="auth-form" aria-labelledby="admin-login-title">
                <?= csrfInput() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email quản trị</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <input class="form-control" id="email" name="email" type="email" autocomplete="username" maxlength="100" required autofocus value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                        <button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Hiện mật khẩu">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <button class="btn btn-primary auth-submit" type="submit">Đăng nhập <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></button>
            </form>
            <a class="auth-back mt-3" href="/Web/index.php"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Quay lại cửa hàng</a>
        </section>
    </main>
    <script src="/Web/assets/js/main.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/main.js') ?>"></script>
</body>
</html>
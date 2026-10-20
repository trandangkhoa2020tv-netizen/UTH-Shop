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
</head>
<body class="bg-light">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center">
        <form method="post" class="card shadow-sm p-4 w-100" style="max-width: 420px">
            <h1 class="h4 fw-bold mb-1">Đăng nhập quản trị</h1>
            <p class="text-secondary mb-4">UTH Shop</p>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <?= csrfInput() ?>
            <label class="form-label" for="email">Email quản trị</label>
            <input class="form-control mb-3" id="email" name="email" type="email" autocomplete="username" required>
            <label class="form-label" for="password">Mật khẩu</label>
            <input class="form-control mb-4" id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
            <a class="text-center mt-3" href="/Web/index.php">Quay lại cửa hàng</a>
        </form>
    </main>
</body>
</html>
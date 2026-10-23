<?php
require_once __DIR__ . '/bootstrap.php';
if (!empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin') { header('Location: ' . admin_url('index.php')); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if ($email === '' || $password === '') { $error = 'Vui lòng nhập email và mật khẩu.'; }
    else {
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password']) && $user['role'] === 'admin') {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id']; $_SESSION['user_name'] = $user['full_name']; $_SESSION['user_email'] = $user['email']; $_SESSION['user_role'] = $user['role'];
            unset($_SESSION['admin_csrf']);
            header('Location: ' . admin_url('index.php')); exit;
        }
        $error = 'Email hoặc mật khẩu không đúng, hoặc tài khoản không có quyền Admin.';
    }
}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Đăng nhập Admin | UTH Shop</title><link rel="stylesheet" href="<?= h(admin_url('admin.css')) ?>"></head><body class="login-page"><section class="login-card"><div class="login-brand">UTH Shop <span>ADMIN</span></div><h1>Đăng nhập quản trị</h1><p>Vui lòng đăng nhập bằng tài khoản có quyền Admin.</p><?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><div class="form-group"><label for="email">Email</label><input class="form-control" type="email" id="email" name="email" required autocomplete="username" value="<?= h($_POST['email'] ?? '') ?>"></div><div class="form-group"><label for="password">Mật khẩu</label><input class="form-control" type="password" id="password" name="password" required autocomplete="current-password"></div><button class="btn" type="submit" style="width:100%">Đăng nhập</button></form><p class="text-muted" style="margin-top:18px">Chỉ tài khoản có role = admin mới truy cập được khu vực này.</p><a href="<?= h(site_url('index.php')) ?>">← Quay lại cửa hàng</a></section></body></html>

<?php
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_success']);
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Phiên đăng nhập không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } else {
        $stmt = $conn->prepare(
            'SELECT id, full_name, email, password, role FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'fullname' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];

            $redirect = (string)($_GET['redirect'] ?? '');
            $redirectPath = $redirect;
            if (preg_match('/\A[a-zA-Z0-9_-]+\.php\z/', $redirect)) {
                $redirectPath = '/Web/' . $redirect;
            }
            if ($redirectPath !== '' && strpos($redirectPath, '/Web/') === 0 &&
                strpos($redirectPath, '//') === false && strpos($redirectPath, '..') === false &&
                !in_array(parse_url($redirectPath, PHP_URL_PATH), ['/Web/login.php', '/Web/register.php'], true)) {
                header('Location: ' . $redirectPath);
            } else {
                header('Location: index.php');
            }
            exit;
        }
        $error = 'Email hoặc mật khẩu không đúng.';
    }
}

$pageTitle = 'Đăng nhập';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <section class="auth-layout auth-layout--login" aria-labelledby="login-title">
        <div class="auth-card">
            <div class="auth-card__heading">
                <span class="auth-card__eyebrow">TÀI KHOẢN KHÁCH HÀNG</span>
                <h2 id="login-title">Chào mừng trở lại</h2>
                <p>Đăng nhập để tiếp tục.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success auth-alert" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger auth-alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="login.php<?= isset($_GET['redirect']) ? '?redirect=' . rawurlencode((string)$_GET['redirect']) : '' ?>" class="auth-form">
                <?= csrfInput() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <input type="email" class="form-control" id="email" name="email" required maxlength="100"
                               autocomplete="email" placeholder="ban@example.com"
                               value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" class="form-control" id="password" name="password" required
                               autocomplete="current-password" placeholder="Nhập mật khẩu">
                        <button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Hiện mật khẩu">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary auth-submit">
                    Đăng nhập <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                </button>
            </form>

            <p class="auth-switch">Chưa có tài khoản? <a href="register.php">Tạo tài khoản</a></p>
            <a class="auth-back" href="/Web/index.php"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Quay lại cửa hàng</a>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

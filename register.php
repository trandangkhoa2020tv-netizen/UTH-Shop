<?php
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old = ['fullname' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim((string)($_POST['fullname'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $old = ['fullname' => $fullname, 'email' => $email];

    if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    }
    if ($fullname === '' || strlen($fullname) > 100) {
        $errors[] = 'Họ tên không được để trống và tối đa 100 ký tự.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ (tối đa 100 ký tự).';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Mật khẩu cần có ít nhất 6 ký tự.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    }

    if (!$errors) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $errors[] = 'Email này đã được đăng ký.';
        }
    }

    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$fullname, $email, $hash]);

            $_SESSION['register_success'] = 'Đăng ký thành công! Hãy đăng nhập bằng tài khoản mới.';
            header('Location: login.php');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'Email này đã được đăng ký.';
            } else {
                error_log('Customer registration failed: ' . $exception->getMessage());
                $errors[] = 'Không thể tạo tài khoản lúc này. Vui lòng thử lại sau.';
            }
        }
    }
}

$pageTitle = 'Tạo tài khoản';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <section class="auth-layout" aria-labelledby="register-title">
        <div class="auth-intro">
            <a class="auth-brand" href="/Web/index.php">
                <span class="auth-brand__icon"><i class="bi bi-bag-heart-fill" aria-hidden="true"></i></span>
                <span>UTH <strong>Shop</strong></span>
            </a>
            <span class="auth-kicker">BẮT ĐẦU MUA SẮM</span>
            <h1>Tạo tài khoản<br>của riêng bạn.</h1>
            <p>Đăng ký nhanh chóng để đặt hàng và theo dõi đơn hàng của bạn tại UTH Shop.</p>
            <div class="auth-benefits">
                <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Lưu thông tin giao hàng</span>
                <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Theo dõi lịch sử đơn hàng</span>
                <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Mua sắm thuận tiện hơn</span>
            </div>
        </div>

        <div class="auth-card">
            <div class="auth-card__heading">
                <span class="auth-card__eyebrow">TÀI KHOẢN KHÁCH HÀNG</span>
                <h2 id="register-title">Đăng ký</h2>
                <p>Điền thông tin bên dưới để bắt đầu.</p>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger auth-alert" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php" class="auth-form">
                <?= csrfInput() ?>
                <div class="mb-3">
                    <label class="form-label" for="fullname">Họ và tên</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-person" aria-hidden="true"></i>
                        <input type="text" class="form-control" id="fullname" name="fullname" required maxlength="100"
                               autocomplete="name" placeholder="Nguyễn Văn A"
                               value="<?= htmlspecialchars($old['fullname'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <input type="email" class="form-control" id="email" name="email" required maxlength="100"
                               autocomplete="email" placeholder="ban@example.com"
                               value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6"
                               autocomplete="new-password" placeholder="Ít nhất 6 ký tự">
                        <button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Hiện mật khẩu">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="confirm_password">Nhập lại mật khẩu</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                               autocomplete="new-password" placeholder="Nhập lại mật khẩu">
                        <button class="auth-password-toggle" type="button" data-password-toggle="confirm_password" aria-label="Hiện mật khẩu">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary auth-submit">
                    Tạo tài khoản <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                </button>
            </form>

            <p class="auth-switch">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
            <a class="auth-back" href="/Web/index.php"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Quay lại cửa hàng</a>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

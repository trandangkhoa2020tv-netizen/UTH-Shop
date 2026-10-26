<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/migrations/account_profile_fields.php';

requireLogin('account.php');

$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId < 1) {
    header('Location: login.php?redirect=account.php');
    exit();
}

$stmtUser = $conn->prepare('SELECT full_name, email, password, phone, address, gender, birth_date FROM users WHERE id = ?');
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    exit('Không tìm thấy thông tin tài khoản.');
}

$errors = [];
$passwordErrors = [];
$passwordValues = [
    'current_password' => '',
    'new_password' => '',
    'confirm_password' => '',
];
$formValues = [
    'full_name' => $user['full_name'],
    'phone' => $user['phone'] ?? '',
    'address' => $user['address'] ?? '',
    'gender' => $user['gender'] ?? '',
    'birth_date' => $user['birth_date'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'change_password') {
        $passwordValues = [
            'current_password' => (string)($_POST['current_password'] ?? ''),
            'new_password' => (string)($_POST['new_password'] ?? ''),
            'confirm_password' => (string)($_POST['confirm_password'] ?? ''),
        ];

        if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            $passwordErrors[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
        }
        if ($passwordValues['current_password'] === '') {
            $passwordErrors[] = 'Vui lòng nhập mật khẩu hiện tại.';
        } elseif (!password_verify($passwordValues['current_password'], $user['password'])) {
            $passwordErrors[] = 'Mật khẩu hiện tại không chính xác.';
        }
        if (!meetsPasswordPolicy($passwordValues['new_password'])) {
            $passwordErrors[] = 'Mật khẩu mới phải có ít nhất 8 ký tự, gồm ít nhất 1 chữ hoa và 1 ký tự đặc biệt, đồng thời không quá 72 byte.';
        }
        if ($passwordValues['new_password'] !== $passwordValues['confirm_password']) {
            $passwordErrors[] = 'Mật khẩu xác nhận không khớp.';
        }
        if (
            !$passwordErrors
            && password_verify($passwordValues['new_password'], $user['password'])
        ) {
            $passwordErrors[] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
        }

        if (!$passwordErrors) {
            try {
                $passwordHash = password_hash($passwordValues['new_password'], PASSWORD_DEFAULT);
                $stmtPassword = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmtPassword->execute([$passwordHash, $userId]);
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                flashSuccess('Mật khẩu đã được cập nhật thành công.');
                header('Location: account.php');
                exit();
            } catch (PDOException $exception) {
                error_log('Account password update failed: ' . $exception->getMessage());
                $passwordErrors[] = 'Không thể cập nhật mật khẩu lúc này. Vui lòng thử lại sau.';
            }
        }
    } else {
        $formValues = [
            'full_name' => trim((string)($_POST['full_name'] ?? '')),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'address' => trim((string)($_POST['address'] ?? '')),
            'gender' => trim((string)($_POST['gender'] ?? '')),
            'birth_date' => trim((string)($_POST['birth_date'] ?? '')),
        ];

        if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            $errors[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
        }
        if ($formValues['full_name'] === '' || mb_strlen($formValues['full_name'], 'UTF-8') > 100) {
            $errors[] = 'Họ và tên không được để trống và tối đa 100 ký tự.';
        }
        if (mb_strlen($formValues['phone'], 'UTF-8') > 20) {
            $errors[] = 'Số điện thoại không được vượt quá 20 ký tự.';
        }
        if (!in_array($formValues['gender'], ['', 'Nam', 'Nữ', 'Khác'], true)) {
            $errors[] = 'Vui lòng chọn giới tính hợp lệ.';
        }
        if ($formValues['birth_date'] !== '') {
            $birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $formValues['birth_date']);
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (
                !$birthDate
                || $birthDate->format('Y-m-d') !== $formValues['birth_date']
                || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                || $birthDate > new DateTimeImmutable('today')
            ) {
                $errors[] = 'Vui lòng nhập ngày sinh hợp lệ, không ở trong tương lai.';
            }
        }

        if (!$errors) {
            try {
                $stmtUpdate = $conn->prepare(
                    'UPDATE users SET full_name = ?, phone = ?, address = ?, gender = ?, birth_date = ? WHERE id = ?'
                );
                $stmtUpdate->execute([
                    $formValues['full_name'],
                    $formValues['phone'] !== '' ? $formValues['phone'] : null,
                    $formValues['address'] !== '' ? $formValues['address'] : null,
                    $formValues['gender'] !== '' ? $formValues['gender'] : null,
                    $formValues['birth_date'] !== '' ? $formValues['birth_date'] : null,
                    $userId,
                ]);

                $_SESSION['user_name'] = $formValues['full_name'];
                flashSuccess('Thông tin tài khoản đã được cập nhật.');
                header('Location: account.php');
                exit();
            } catch (PDOException $exception) {
                error_log('Account profile update failed: ' . $exception->getMessage());
                $errors[] = 'Không thể cập nhật thông tin lúc này. Vui lòng thử lại sau.';
            }
        }
    }
}

$pageTitle = 'Thông tin tài khoản';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="mb-4">
                <h1 class="h2 mb-2">Thông tin tài khoản</h1>
                <p class="text-muted mb-0">Cập nhật thông tin liên hệ và giao hàng của bạn.</p>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="post" action="account.php">
                        <?= csrfInput() ?>

                        <div class="mb-3">
                            <label class="form-label" for="full_name">Họ và tên</label>
                            <input class="form-control" type="text" id="full_name" name="full_name"
                                   value="<?= htmlspecialchars($formValues['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="100" autocomplete="name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control" type="email" id="email"
                                   value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>"
                                   autocomplete="email" readonly>
                            <div class="form-text">Email tài khoản không thể thay đổi.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="phone">Số điện thoại</label>
                            <input class="form-control" type="tel" id="phone" name="phone"
                                   value="<?= htmlspecialchars($formValues['phone'], ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="20" autocomplete="tel">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="gender">Giới tính</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Chưa chọn</option>
                                <?php foreach (['Nam', 'Nữ', 'Khác'] as $gender): ?>
                                    <option value="<?= htmlspecialchars($gender, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $formValues['gender'] === $gender ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($gender, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="birth_date">Ngày sinh</label>
                            <input class="form-control" type="date" id="birth_date" name="birth_date"
                                   value="<?= htmlspecialchars($formValues['birth_date'], ENT_QUOTES, 'UTF-8') ?>"
                                   max="<?= date('Y-m-d') ?>" autocomplete="bday">
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="address">Địa chỉ</label>
                            <textarea class="form-control" id="address" name="address" rows="3"
                                      autocomplete="street-address"><?= htmlspecialchars($formValues['address'], ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2 me-1"></i>Lưu thông tin
                            </button>
                            <a href="/Web/index.php" class="btn btn-outline-secondary">Quay lại cửa hàng</a>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h2 class="h4 mb-2">Đổi mật khẩu</h2>
                    <p class="text-muted">Để bảo vệ tài khoản, bạn cần xác nhận đúng mật khẩu hiện tại.</p>
                    <?php if ($passwordErrors): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php foreach ($passwordErrors as $error): ?>
                                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="account.php">
                        <?= csrfInput() ?>
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label class="form-label" for="current_password">Mật khẩu hiện tại</label>
                            <div class="auth-input-wrap">
                                <i class="bi bi-lock" aria-hidden="true"></i>
                                <input class="form-control" type="password" id="current_password" name="current_password"
                                       autocomplete="current-password" required>
                                <button class="auth-password-toggle" type="button" data-password-toggle="current_password" aria-label="Hiện mật khẩu">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="new_password">Mật khẩu mới</label>
                            <div class="auth-input-wrap">
                                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                                <input class="form-control" type="password" id="new_password" name="new_password"
                                       minlength="8" maxlength="72" autocomplete="new-password" required>
                                <button class="auth-password-toggle" type="button" data-password-toggle="new_password" aria-label="Hiện mật khẩu">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="form-text">Ít nhất 8 ký tự, có 1 chữ hoa và 1 ký tự đặc biệt; tối đa 72 byte.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="confirm_password">Nhập lại mật khẩu mới</label>
                            <div class="auth-input-wrap">
                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                <input class="form-control" type="password" id="confirm_password" name="confirm_password"
                                       minlength="8" maxlength="72" autocomplete="new-password" required>
                                <button class="auth-password-toggle" type="button" data-password-toggle="confirm_password" aria-label="Hiện mật khẩu">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-key me-1"></i>Cập nhật mật khẩu
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

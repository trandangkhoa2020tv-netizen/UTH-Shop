<?php
// =============================================
// FILE: includes/auth.php
// Kiểm tra quyền đăng nhập và phân quyền
// =============================================

// Khởi động session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------
// KIỂM TRA ĐĂNG NHẬP KHÁCH HÀNG
// ----------------------------

/**
 * Kiểm tra khách hàng đã đăng nhập chưa
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Bắt buộc đăng nhập khách hàng.
 * Nếu chưa đăng nhập, chuyển hướng về trang login.
 * @param string $redirect URL trang muốn quay lại sau khi đăng nhập
 */
function requireLogin($redirect = '') {
    if (!isLoggedIn()) {
        $url = '/Web/login.php';
        if (!empty($redirect)) {
            $url .= '?redirect=' . urlencode($redirect);
        }
        header('Location: ' . $url);
        exit();
    }
}

/**
 * Lấy thông tin user đang đăng nhập từ session
 * @return array|null  Mảng ['id', 'name', 'email', 'role'] hoặc null
 */
function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name']  ?? 'Khách',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role']  ?? 'customer',
    ];
}

// ----------------------------
// KIỂM TRA QUYỀN ADMIN
// ----------------------------

/**
 * Kiểm tra người dùng hiện tại có phải admin không
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Bắt buộc quyền admin.
 * Nếu không phải admin, chuyển hướng về trang đăng nhập admin.
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /Web/admin/login.php');
        exit();
    }
}

// ----------------------------
// ĐĂNG NHẬP / ĐĂNG XUẤT
// ----------------------------

/**
 * Đăng nhập người dùng: ghi thông tin vào session
 * @param array $user  Mảng dữ liệu user từ database (phải có: id, full_name, email, role)
 */
function loginUser($user) {
    // Tái sinh session ID để chống Session Fixation
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'] ?? 'customer';
}

/**
 * Đăng xuất người dùng: xóa toàn bộ session
 */
function logoutUser() {
    $_SESSION = [];

    // Xóa cookie session nếu có
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
}

// ----------------------------
// BẢO MẬT MẬT KHẨU
// ----------------------------

/**
 * Kiểm tra mật khẩu có tối thiểu 8 ký tự, chữ hoa, ký tự đặc biệt và không quá 72 byte.
 * @param string $password Mật khẩu dạng plaintext
 * @return bool
 */
function meetsPasswordPolicy($password) {
    return strlen($password) <= 72
        && preg_match('/\A(?=.{8,}\z)(?=.*\p{Lu})(?=.*[^\p{L}\p{N}]).+\z/us', $password) === 1;
}

/**
 * Hash mật khẩu trước khi lưu vào database
 * @param string $password Mật khẩu dạng plaintext
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Kiểm tra mật khẩu người dùng nhập có khớp với hash không
 * @param string $password  Mật khẩu dạng plaintext
 * @param string $hash      Hash lưu trong database
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

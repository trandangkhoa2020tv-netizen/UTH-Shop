<?php
// =============================================
// FILE: includes/flash.php
// Hiển thị thông báo nhanh (flash messages)
// Sử dụng session để truyền thông báo qua redirect
// =============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------
// ĐẶT THÔNG BÁO (SET)
// ----------------------------

/**
 * Đặt thông báo flash vào session
 * @param string $message Nội dung thông báo
 * @param string $type    Loại: 'success' | 'error' | 'warning' | 'info'
 */
function setFlash($message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type'    => $type,
    ];
}

/**
 * Kiểm tra có thông báo flash chờ hiển thị không
 * @return bool
 */
function hasFlash() {
    return isset($_SESSION['flash']) && !empty($_SESSION['flash']['message']);
}

/**
 * Lấy thông báo flash (và xóa khỏi session)
 * @return array|null  Mảng ['message', 'type'] hoặc null
 */
function getFlash() {
    if (!hasFlash()) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']); // Chỉ hiển thị 1 lần
    return $flash;
}

// ----------------------------
// HIỂN THỊ THÔNG BÁO (RENDER)
// ----------------------------

/**
 * Render thông báo flash ra HTML (nếu có)
 * Tự động xóa thông báo sau khi hiển thị.
 * Gọi hàm này trong header.php hoặc đầu mỗi trang.
 */
function showFlash() {
    $flash = getFlash();
    if (!$flash) return;

    $type    = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');

    // Map type -> icon
    $icons = [
        'success' => '✅',
        'error'   => '❌',
        'warning' => '⚠️',
        'info'    => 'ℹ️',
    ];
    $icon = $icons[$type] ?? 'ℹ️';

    // Map type -> CSS class (dùng Bootstrap 5 alert)
    $cssMap = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
    ];
    $css = $cssMap[$type] ?? 'alert-secondary';

    echo <<<HTML
    <div class="alert {$css} alert-dismissible fade show flash-message" role="alert" id="flash-alert">
        <span class="flash-icon">{$icon}</span>
        <span class="flash-text">{$message}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
    <script>
        // Tự động ẩn thông báo sau 4 giây
        setTimeout(function() {
            var el = document.getElementById('flash-alert');
            if (el) {
                el.classList.remove('show');
                setTimeout(function() { el.remove(); }, 300);
            }
        }, 4000);
    </script>
    HTML;
}

// ----------------------------
// SHORTCUT HELPERS
// ----------------------------

/** Đặt thông báo thành công */
function flashSuccess($msg) { setFlash($msg, 'success'); }

/** Đặt thông báo lỗi */
function flashError($msg) { setFlash($msg, 'error'); }

/** Đặt thông báo cảnh báo */
function flashWarning($msg) { setFlash($msg, 'warning'); }

/** Đặt thông báo thông tin */
function flashInfo($msg) { setFlash($msg, 'info'); }

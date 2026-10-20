<?php
// =============================================
// FILE: includes/functions.php
// Các hàm hỗ trợ chung dùng cho toàn bộ website
// =============================================

// ----------------------------
// 1. LÀM SẠCH DỮ LIỆU ĐẦU VÀO
// ----------------------------

/**
 * Làm sạch chuỗi, chống XSS
 * @param string $data
 * @return string
 */
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Làm sạch số nguyên
 * @param mixed $value
 * @return int
 */
function cleanInt($value) {
    return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Kiểm tra và làm sạch email
 * @param string $email
 * @return string|false
 */
function cleanEmail($email) {
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

// ----------------------------
// 2. XỬ LÝ CHUỖI
// ----------------------------

/**
 * Cắt ngắn chuỗi theo số ký tự, thêm "..." nếu bị cắt
 * @param string $str
 * @param int    $length Số ký tự tối đa
 * @return string
 */
function truncate($str, $length = 100) {
    if (mb_strlen($str, 'UTF-8') <= $length) {
        return $str;
    }
    return mb_substr($str, 0, $length, 'UTF-8') . '...';
}

/**
 * Tạo slug URL từ chuỗi tiếng Việt
 * @param string $str
 * @return string
 */
function createSlug($str) {
    $unicode = [
        'a' => ['á','à','ả','ã','ạ','ă','ắ','ặ','ằ','ẳ','ẵ','â','ấ','ầ','ẩ','ẫ','ậ'],
        'd' => ['đ'],
        'e' => ['é','è','ẻ','ẽ','ẹ','ê','ế','ề','ể','ễ','ệ'],
        'i' => ['í','ì','ỉ','ĩ','ị'],
        'o' => ['ó','ò','ỏ','õ','ọ','ô','ố','ồ','ổ','ỗ','ộ','ơ','ớ','ờ','ở','ỡ','ợ'],
        'u' => ['ú','ù','ủ','ũ','ụ','ư','ứ','ừ','ử','ữ','ự'],
        'y' => ['ý','ỳ','ỷ','ỹ','ỵ'],
    ];
    foreach ($unicode as $latin => $viet) {
        $str = str_replace($viet, $latin, $str);
        $str = str_replace(array_map('strtoupper', $viet), strtoupper($latin), $str);
    }
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-');
}

// ----------------------------
// 3. ĐỊNH DẠNG HIỂN THỊ
// ----------------------------

/**
 * Định dạng tiền tệ VNĐ
 * @param float $price
 * @return string  Ví dụ: 1.500.000 ₫
 */
function formatPrice($price) {
    return number_format((float)$price, 0, ',', '.') . ' ₫';
}

/**
 * Định dạng ngày giờ theo kiểu Việt Nam
 * @param string $datetime  Chuỗi datetime từ MySQL
 * @param bool   $showTime  Có hiển thị giờ phút không
 * @return string
 */
function formatDate($datetime, $showTime = false) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') return '—';
    $format = $showTime ? 'd/m/Y H:i' : 'd/m/Y';
    return date($format, strtotime($datetime));
}

/**
 * Trả về nhãn trạng thái đơn hàng (tiếng Việt)
 * @param string $status
 * @return string
 */
function orderStatusLabel($status) {
    $labels = [
        'pending'    => 'Chờ xác nhận',
        'processing' => 'Đang xử lý',
        'shipping'   => 'Đang giao hàng',
        'delivered'  => 'Đã giao hàng',
        'cancelled'  => 'Đã hủy',
    ];
    return $labels[$status] ?? ucfirst($status);
}

/**
 * Trả về class CSS badge cho từng trạng thái đơn hàng
 * @param string $status
 * @return string
 */
function orderStatusClass($status) {
    $classes = [
        'pending'    => 'badge-warning',
        'processing' => 'badge-info',
        'shipping'   => 'badge-primary',
        'delivered'  => 'badge-success',
        'cancelled'  => 'badge-danger',
    ];
    return $classes[$status] ?? 'badge-secondary';
}

// ----------------------------
// 4. ĐƯỜNG DẪN & HÌNH ẢNH
// ----------------------------

/**
 * Trả về đường dẫn ảnh sản phẩm, dùng ảnh mặc định nếu không tồn tại
 * @param string $filename  Tên file ảnh lưu trong DB
 * @param string $basePath  Thư mục chứa ảnh (relative từ root)
 * @return string
 */
function getStoredImageUrl($filename, $allowedDirectories = ['image/', 'uploads/products/'], $fallback = '/Web/image/no-image.svg') {
    $filename = str_replace('\\', '/', trim((string)$filename));
    $filename = ltrim($filename, '/');

    if ($filename !== '' && strpos($filename, '..') === false) {
        if (strpos($filename, '/') === false) {
            $filename = 'image/' . $filename;
        }

        foreach ($allowedDirectories as $directory) {
            if (strpos($filename, $directory) === 0) {
                $fullPath = dirname(__DIR__) . '/' . $filename;
                if (is_file($fullPath)) {
                    return '/Web/' . str_replace('%2F', '/', rawurlencode($filename));
                }
                if ($directory === 'image/') {
                    $requestedName = basename($filename);
                    $requestedExtension = strtolower(pathinfo($requestedName, PATHINFO_EXTENSION));
                    $requestedSlug = createSlug(str_replace('_', ' ', pathinfo($requestedName, PATHINFO_FILENAME)));
                    $imageDirectory = dirname(__DIR__) . '/image';
                    foreach (glob($imageDirectory . '/*') ?: [] as $candidate) {
                        if (!is_file($candidate) || strtolower(pathinfo($candidate, PATHINFO_EXTENSION)) !== $requestedExtension) {
                            continue;
                        }
                        $candidateSlug = createSlug(str_replace('_', ' ', pathinfo($candidate, PATHINFO_FILENAME)));
                        if ($candidateSlug === $requestedSlug) {
                            $relativePath = 'image/' . basename($candidate);
                            return '/Web/' . str_replace('%2F', '/', rawurlencode($relativePath));
                        }
                    }
                }
                break;
            }
        }
    }

    return $fallback;
}

/**
 * Trả về URL ảnh sản phẩm; hỗ trợ tên file cũ trong image/ và ảnh tải lên.
 */
function getProductImage($filename, $basePath = null) {
    return getStoredImageUrl($filename, ['image/', 'uploads/products/']);
}

/**
 * Trả về URL ảnh banner; chỉ chấp nhận ảnh mặc định hoặc ảnh tải lên dành cho banner.
 */
function getBannerImage($filename) {
    return getStoredImageUrl($filename, ['image/', 'uploads/banners/'], '');
}

/**
 * Redirect đến URL khác
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Lấy URL gốc của dự án (base URL)
 * @return string
 */
function baseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // Lấy đường dẫn thư mục gốc (điều chỉnh nếu đặt project ở subfolder)
    $base = '/Web/';
    return $protocol . '://' . $host . $base;
}

// ----------------------------
// 5. XÁC THỰC DỮ LIỆU FORM
// ----------------------------

/**
 * Kiểm tra chuỗi có rỗng không
 * @param string $str
 * @return bool
 */
function isEmpty($str) {
    return trim($str) === '';
}

/**
 * Kiểm tra độ dài chuỗi
 * @param string $str
 * @param int    $min
 * @param int    $max
 * @return bool
 */
function checkLength($str, $min = 1, $max = 255) {
    $len = mb_strlen(trim($str), 'UTF-8');
    return $len >= $min && $len <= $max;
}

/**
 * Kiểm tra giá trị số dương
 * @param mixed $value
 * @return bool
 */
function isPositiveNumber($value) {
    return is_numeric($value) && (float)$value > 0;
}

// ----------------------------
// 6. SESSION & CSRF
// ----------------------------

/**
 * Tạo CSRF token và lưu vào session
 * @return string
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Kiểm tra CSRF token có hợp lệ không
 * @param string $token Token từ form POST
 * @return bool
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Tạo input hidden chứa CSRF token cho form
 * @return string HTML input
 */
function csrfInput() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// ----------------------------
// 7. GIỎ HÀNG (SESSION)
// ----------------------------

/**
 * Lấy số lượng sản phẩm trong giỏ hàng từ session
 * @return int
 */
function getCartCount() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    return array_sum(array_column($_SESSION['cart'], 'quantity'));
}

/**
 * Tính tổng giá trị giỏ hàng từ session
 * @return float
 */
function getCartTotal() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

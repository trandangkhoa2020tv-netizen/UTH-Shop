<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Yêu cầu đăng xuất không hợp lệ.');
}
if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Phiên đăng xuất không hợp lệ. Vui lòng tải lại trang và thử lại.');
}

require_once __DIR__ . '/includes/auth.php';
logoutUser();
header('Location: index.php');
exit;
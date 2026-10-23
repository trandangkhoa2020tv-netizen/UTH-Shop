<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Yêu cầu đăng xuất không hợp lệ.');
}
if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Phiên đăng xuất không hợp lệ. Vui lòng tải lại trang và thử lại.');
}

logoutUser();
header('Location: /Web/admin/login.php');
exit;
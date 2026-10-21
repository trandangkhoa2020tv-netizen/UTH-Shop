<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Yêu cầu không hợp lệ. Tải lại trang và thử lại.');
}
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Mã danh mục không hợp lệ.');
}
$delete = $conn->prepare('DELETE FROM categories WHERE id = :id');
$delete->execute([':id' => $id]);
if ($delete->rowCount() === 0) {
    header('Location: /Web/admin/categories/index.php?delete_error=1');
    exit;
}
header('Location: /Web/admin/categories/index.php?deleted=1');
exit;
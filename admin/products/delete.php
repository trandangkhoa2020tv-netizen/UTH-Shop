<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/media.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Yêu cầu không hợp lệ. Tải lại trang và thử lại.');
}
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Mã sản phẩm không hợp lệ.');
}
$statement = $conn->prepare('SELECT image FROM products WHERE id = :id');
$statement->execute([':id' => $id]);
$product = $statement->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    http_response_code(404);
    exit('Không tìm thấy sản phẩm.');
}
$orderCheck = $conn->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = :id');
$orderCheck->execute([':id' => $id]);
if ((int)$orderCheck->fetchColumn() > 0) {
    header('Location: /Web/admin/products/index.php?delete_error=ordered');
    exit;
}
$delete = $conn->prepare('DELETE FROM products WHERE id = :id');
$delete->execute([':id' => $id]);
if (!empty($product['image'])) {
    deleteUploadedImage($product['image']);
}
header('Location: /Web/admin/products/index.php?deleted=1');
exit;
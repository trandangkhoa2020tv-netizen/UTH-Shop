<?php
require_once dirname(__DIR__) . '/../config/database.php';
require_once dirname(__DIR__) . '/../includes/functions.php';
require_once dirname(__DIR__) . '/../includes/auth.php';
require_once dirname(__DIR__) . '/../includes/flash.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT name, image FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {
    $conn->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    // Xóa file ảnh nếu có (chỉ xóa ảnh do admin upload, không xóa ảnh seed)
    if (!empty($product['image']) && strpos($product['image'], 'product_') === 0) {
        $imgPath = dirname(__DIR__, 2) . '/image/' . $product['image'];
        if (file_exists($imgPath)) unlink($imgPath);
    }
    flashSuccess("Đã xóa sản phẩm \"{$product['name']}\".");
} else {
    flashError('Không tìm thấy sản phẩm.');
}
header('Location: /Web/admin/products/index.php');
exit();

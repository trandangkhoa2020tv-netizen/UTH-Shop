<?php
require_once dirname(__DIR__) . '/../config/database.php';
require_once dirname(__DIR__) . '/../includes/functions.php';
require_once dirname(__DIR__) . '/../includes/auth.php';
require_once dirname(__DIR__) . '/../includes/flash.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
$stmt->execute([$id]);
$cat = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cat) {
    $conn->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    flashSuccess("Đã xóa danh mục \"{$cat['name']}\".");
} else {
    flashError('Không tìm thấy danh mục.');
}
header('Location: /Web/admin/categories/index.php');
exit();

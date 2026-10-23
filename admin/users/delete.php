<?php
require_once dirname(__DIR__) . '/../config/database.php';
require_once dirname(__DIR__) . '/../includes/functions.php';
require_once dirname(__DIR__) . '/../includes/auth.php';
require_once dirname(__DIR__) . '/../includes/flash.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireAdmin();

$id = (int)($_GET['id'] ?? 0);

// Không cho xóa admin
$stmt = $conn->prepare("SELECT full_name, role FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    flashError('Không tìm thấy người dùng.');
} elseif ($user['role'] === 'admin') {
    flashError('Không thể xóa tài khoản Admin.');
} else {
    $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    flashSuccess("Đã xóa tài khoản \"{$user['full_name']}\".");
}
header('Location: /Web/admin/users/index.php');
exit();

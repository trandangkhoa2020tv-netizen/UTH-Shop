<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Yêu cầu không hợp lệ. Tải lại trang và thử lại.');
}
$id = filter_var($_POST['order_id'] ?? '', FILTER_VALIDATE_INT);
$status = (string)($_POST['status'] ?? '');
$allowedStatuses = ['pending', 'processing', 'completed', 'cancelled'];
if (!$id || $id < 1 || !in_array($status, $allowedStatuses, true)) {
    header('Location: /Web/admin/orders/index.php?update_error=1');
    exit;
}
$update = $conn->prepare('UPDATE orders SET status = :status WHERE id = :id');
$update->execute([':status' => $status, ':id' => $id]);
if ($update->rowCount() === 0) {
    $check = $conn->prepare('SELECT id FROM orders WHERE id = :id');
    $check->execute([':id' => $id]);
    if (!$check->fetchColumn()) {
        header('Location: /Web/admin/orders/index.php?update_error=1');
        exit;
    }
}
header('Location: /Web/admin/orders/detail.php?id=' . $id . '&updated=1');
exit;
<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Yêu cầu không hợp lệ. Tải lại trang và thử lại.');
}
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Mã người dùng không hợp lệ.');
}
if ((int)($_SESSION['user_id'] ?? 0) === $id) {
    header('Location: /Web/admin/users/index.php?delete_error=self');
    exit;
}

try {
    $conn->beginTransaction();
    $userQuery = $conn->prepare('SELECT id, role FROM users WHERE id = :id FOR UPDATE');
    $userQuery->execute([':id' => $id]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $conn->rollBack();
        header('Location: /Web/admin/users/index.php?delete_error=failed');
        exit;
    }
    if ($user['role'] !== 'user') {
        $conn->rollBack();
        header('Location: /Web/admin/users/index.php?delete_error=admin');
        exit;
    }
    $ordersQuery = $conn->prepare('SELECT COUNT(*) FROM orders WHERE user_id = :id');
    $ordersQuery->execute([':id' => $id]);
    if ((int)$ordersQuery->fetchColumn() > 0) {
        $conn->rollBack();
        header('Location: /Web/admin/users/index.php?delete_error=orders');
        exit;
    }
    $delete = $conn->prepare('DELETE FROM users WHERE id = :id AND role = :role');
    $delete->execute([':id' => $id, ':role' => 'user']);
    $conn->commit();
} catch (PDOException $exception) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Admin user deletion failed: ' . $exception->getMessage());
    header('Location: /Web/admin/users/index.php?delete_error=failed');
    exit;
}

header('Location: /Web/admin/users/index.php?deleted=1');
exit;
<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Yêu cầu không hợp lệ. Tải lại trang và thử lại.');
}

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
$role = is_scalar($_POST['role'] ?? null) ? (string)$_POST['role'] : '';
if (!$id || $id < 1 || !in_array($role, ['user', 'admin'], true)) {
    header('Location: ' . adminUserListUrl(array_merge($_GET, ['role_error' => 'invalid'])));
    exit;
}
if ((int)($_SESSION['user_id'] ?? 0) === $id) {
    header('Location: ' . adminUserListUrl(array_merge($_GET, ['role_error' => 'self'])));
    exit;
}

try {
    $conn->beginTransaction();
    $userQuery = $conn->prepare('SELECT id, role FROM users WHERE id = :id FOR UPDATE');
    $userQuery->execute([':id' => $id]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $conn->rollBack();
        header('Location: ' . adminUserListUrl(array_merge($_GET, ['role_error' => 'invalid'])));
        exit;
    }

    if ($user['role'] === 'admin' && $role === 'user') {
        $adminQuery = $conn->query("SELECT id FROM users WHERE role = 'admin' FOR UPDATE");
        if (count($adminQuery->fetchAll(PDO::FETCH_COLUMN)) <= 1) {
            $conn->rollBack();
            header('Location: ' . adminUserListUrl(array_merge($_GET, ['role_error' => 'last_admin'])));
            exit;
        }
    }

    $update = $conn->prepare('UPDATE users SET role = :role WHERE id = :id');
    $update->execute([':role' => $role, ':id' => $id]);
    $conn->commit();
} catch (PDOException $exception) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Admin user role update failed: ' . $exception->getMessage());
    header('Location: ' . adminUserListUrl(array_merge($_GET, ['role_error' => 'failed'])));
    exit;
}

header('Location: ' . adminUserListUrl(array_merge($_GET, ['role_updated' => '1'])));
exit;

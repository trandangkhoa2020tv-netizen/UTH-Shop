<?php
$pageTitle = 'Quản lý Người dùng';
require_once dirname(__DIR__) . '/_layout.php';

$users = $conn->query("
    SELECT u.*, COUNT(o.id) AS order_count
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    GROUP BY u.id ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-people me-2 text-primary"></i>Người dùng</h5>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th class="px-4" style="width:60px">ID</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th class="text-center">Vai trò</th>
                    <th class="text-center">Đơn hàng</th>
                    <th>Ngày đăng ký</th>
                    <th class="text-center" style="width:80px">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Chưa có người dùng</td></tr>
            <?php else: foreach ($users as $u): ?>
                <tr>
                    <td class="px-4 text-muted small"><?= $u['id'] ?></td>
                    <td class="fw-semibold small"><?= htmlspecialchars($u['full_name']) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="small"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td class="text-center">
                        <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-secondary' ?>">
                            <?= $u['role'] === 'admin' ? 'Admin' : 'Khách hàng' ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($u['order_count'] > 0): ?>
                        <a href="/Web/admin/orders/index.php" class="badge bg-primary text-decoration-none">
                            <?= $u['order_count'] ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted small">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= formatDate($u['created_at']) ?></td>
                    <td class="text-center">
                        <?php if ($u['role'] !== 'admin'): ?>
                        <a href="/Web/admin/users/delete.php?id=<?= $u['id'] ?>"
                           class="btn btn-sm btn-outline-danger py-0"
                           onclick="return confirm('Xóa tài khoản này?\nTất cả đơn hàng của họ cũng sẽ bị xóa.')">
                            <i class="bi bi-trash"></i>
                        </a>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small py-2 px-4">
        Tổng: <strong><?= count($users) ?></strong> tài khoản
    </div>
</div>
<?php require_once dirname(__DIR__) . '/_layout_end.php'; ?>

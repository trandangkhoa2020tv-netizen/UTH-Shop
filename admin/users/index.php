<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';

$search = is_scalar($_GET['q'] ?? null) ? trim((string)$_GET['q']) : '';
$role = is_scalar($_GET['role'] ?? null) ? (string)$_GET['role'] : 'all';
if (!in_array($role, ['all', 'user', 'admin'], true)) {
    $role = 'all';
}
$dateFromInput = is_scalar($_GET['date_from'] ?? null) ? (string)$_GET['date_from'] : '';
$dateToInput = is_scalar($_GET['date_to'] ?? null) ? (string)$_GET['date_to'] : '';
$isValidDate = static function (string $date): bool {
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $matches) !== 1) {
        return false;
    }
    return checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1]);
};
$dateFrom = $isValidDate($dateFromInput) ? $dateFromInput : '';
$dateTo = $isValidDate($dateToInput) ? $dateToInput : '';
$filterError = $dateFromInput !== '' && $dateFrom === ''
    ? 'Ngày bắt đầu không hợp lệ.'
    : ($dateToInput !== '' && $dateTo === '' ? 'Ngày kết thúc không hợp lệ.' : '');
if ($filterError === '' && $dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $filterError = 'Ngày bắt đầu không thể sau ngày kết thúc.';
}
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$where = 'WHERE 1 = 1';
$params = [];
if ($search !== '') {
    $where .= ' AND (u.full_name LIKE :name_search OR u.email LIKE :email_search OR u.phone LIKE :phone_search)';
    $params = [
        ':name_search' => '%' . $search . '%',
        ':email_search' => '%' . $search . '%',
        ':phone_search' => '%' . $search . '%',
    ];
}
if ($role !== 'all') {
    $where .= ' AND u.role = :role';
    $params[':role'] = $role;
}
if ($filterError === '' && $dateFrom !== '') {
    $where .= ' AND u.created_at >= :date_from';
    $params[':date_from'] = $dateFrom . ' 00:00:00';
}
if ($filterError === '' && $dateTo !== '') {
    $where .= ' AND u.created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)';
    $params[':date_to'] = $dateTo;
}
$count = $conn->prepare("SELECT COUNT(*) FROM users u $where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$query = $conn->prepare(
    "SELECT u.id, u.full_name, u.email, u.phone, u.address, u.role, u.created_at,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count
     FROM users u $where
     ORDER BY u.created_at DESC, u.id DESC LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $query->bindValue($key, $value, PDO::PARAM_STR);
}
$query->bindValue(':limit', $perPage, PDO::PARAM_INT);
$query->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$query->execute();
$users = $query->fetchAll(PDO::FETCH_ASSOC);

$messages = [
    'self' => 'Bạn không thể xóa tài khoản đang đăng nhập.',
    'orders' => 'Không thể xóa tài khoản có lịch sử đơn hàng để bảo toàn dữ liệu mua hàng.',
    'admin' => 'Không thể xóa tài khoản quản trị.',
    'failed' => 'Không thể xóa tài khoản. Vui lòng thử lại.',
];
$deleteError = $messages[is_scalar($_GET['delete_error'] ?? null) ? (string)$_GET['delete_error'] : ''] ?? '';
$roleMessages = [
    'self' => 'Bạn không thể tự thay đổi quyền tài khoản đang đăng nhập.',
    'last_admin' => 'Không thể hạ quyền admin cuối cùng.',
    'invalid' => 'Vai trò được chọn không hợp lệ.',
    'failed' => 'Không thể cập nhật vai trò. Vui lòng thử lại.',
];
$roleError = $roleMessages[is_scalar($_GET['role_error'] ?? null) ? (string)$_GET['role_error'] : ''] ?? '';
$deleted = isset($_GET['deleted']);
$roleUpdated = isset($_GET['role_updated']);
$listQuery = [
    'q' => $search,
    'role' => $role,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'page' => $page,
];

adminHeader('Quản lý người dùng');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <p class="admin-page-intro mb-0">Tìm kiếm, lọc, kiểm soát tài khoản và phân quyền người dùng trong hệ thống.</p>
    <span class="badge rounded-pill text-bg-primary-subtle text-primary px-3 py-2"><i class="bi bi-people me-1"></i><?= $total ?> kết quả</span>
</div>
<?php if ($deleted): ?><div class="alert alert-success">Đã xóa tài khoản khách hàng.</div><?php endif; ?>
<?php if ($roleUpdated): ?><div class="alert alert-success">Cập nhật vai trò người dùng thành công.</div><?php endif; ?>
<?php if ($deleteError): ?><div class="alert alert-warning"><?= adminEscape($deleteError) ?></div><?php endif; ?>
<?php if ($roleError): ?><div class="alert alert-warning"><?= adminEscape($roleError) ?></div><?php endif; ?>
<?php if ($filterError !== ''): ?><div class="alert alert-warning"><?= adminEscape($filterError) ?></div><?php endif; ?>
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-4"><div class="admin-summary-card bg-white p-3 h-100"><div class="admin-summary-label"><i class="bi bi-people me-1"></i>Tài khoản hiển thị</div><div class="admin-summary-value"><?= $total ?></div></div></div>
    <div class="col-sm-6 col-xl-4"><div class="admin-summary-card bg-white p-3 h-100"><div class="admin-summary-label"><i class="bi bi-shield-check me-1"></i>Dữ liệu đơn hàng</div><div class="small text-secondary mt-2">Tài khoản có đơn được giữ lại để bảo toàn lịch sử mua hàng.</div></div></div>
</div>
<form class="card card-body admin-table-card mb-3" method="get">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-5">
            <label class="form-label" for="user-search">Tìm kiếm</label>
            <input class="form-control" id="user-search" type="search" name="q" value="<?= adminEscape($search) ?>" placeholder="Nhập họ tên, email hoặc số điện thoại">
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label" for="user-role">Vai trò</label>
            <select class="form-select" id="user-role" name="role">
                <option value="all" <?= $role === 'all' ? 'selected' : '' ?>>Tất cả vai trò</option>
                <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="date-from">Từ ngày</label>
            <input class="form-control" id="date-from" type="date" name="date_from" value="<?= adminEscape($dateFromInput) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="date-to">Đến ngày</label>
            <input class="form-control" id="date-to" type="date" name="date_to" value="<?= adminEscape($dateToInput) ?>">
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Lọc người dùng</button>
            <a class="btn btn-outline-secondary" href="/Web/admin/users/index.php">Xóa lọc</a>
        </div>
    </div>
</form>
<div class="card admin-table-card">
    <div class="table-responsive admin-table-wrap">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th class="ps-3">Người dùng</th><th>Số điện thoại</th><th>Địa chỉ</th><th>Đơn hàng</th><th>Vai trò</th><th>Ngày tham gia</th><th>Phân quyền</th><th class="text-end pe-3">Thao tác</th></tr></thead>
            <tbody>
            <?php if (!$users): ?>
                <tr><td colspan="8" class="text-center text-secondary py-5"><i class="bi bi-person-exclamation fs-3 d-block mb-2"></i>Chưa có người dùng phù hợp.</td></tr>
            <?php else: foreach ($users as $user): ?>
                <tr>
                    <td class="ps-3"><span class="d-block fw-semibold"><?= adminEscape($user['full_name']) ?></span><small class="text-secondary"><?= adminEscape($user['email']) ?></small></td>
                    <td><?= adminEscape($user['phone'] ?: '—') ?></td>
                    <td class="admin-cell-wrap text-secondary"><?= adminEscape($user['address'] ?: 'Chưa cập nhật') ?></td>
                    <td>
                        <?php if ($user['role'] === 'user'): ?>
                            <a class="badge rounded-pill text-bg-light text-decoration-none" href="/Web/admin/users/detail.php?id=<?= (int)$user['id'] ?>#orders"><?= (int)$user['order_count'] ?> đơn</a>
                        <?php else: ?>
                            <span class="badge rounded-pill text-bg-light"><?= (int)$user['order_count'] ?> đơn</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge rounded-pill <?= $user['role'] === 'admin' ? 'text-bg-primary' : 'text-bg-secondary' ?>"><?= $user['role'] === 'admin' ? 'Admin' : 'User' ?></span></td>
                    <td class="text-secondary"><?= adminEscape(date('d/m/Y', strtotime($user['created_at']))) ?></td>
                    <td>
                        <?php if ((int)$user['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                            <form method="post" action="/Web/admin/users/update_role.php?<?= adminEscape(http_build_query($listQuery)) ?>">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                <select class="form-select form-select-sm mb-1" name="role" aria-label="Vai trò của <?= adminEscape($user['full_name']) ?>">
                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Lưu</button>
                            </form>
                        <?php else: ?>
                            <span class="text-secondary small">Tài khoản hiện tại</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3 admin-row-actions">
                        <?php if ($user['role'] === 'user'): ?>
                            <a class="btn btn-sm btn-outline-primary" href="/Web/admin/users/detail.php?id=<?= (int)$user['id'] ?>"><i class="bi bi-person-lines-fill me-1"></i>Hồ sơ</a>
                        <?php endif; ?>
                        <?php if ($user['role'] === 'user' && (int)$user['order_count'] === 0): ?>
                            <form class="d-inline" method="post" action="/Web/admin/users/delete.php?<?= adminEscape(http_build_query($listQuery)) ?>" onsubmit="return confirm(<?= adminEscape(json_encode('Xóa tài khoản ' . $user['full_name'] . '? Thao tác này không thể hoàn tác.', JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>);">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Xóa người dùng"><i class="bi bi-person-x"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if ($totalPages > 1): ?>
<nav class="mt-3" aria-label="Phân trang người dùng">
    <ul class="pagination justify-content-end">
        <?php for ($number = 1; $number <= $totalPages; $number++): ?>
            <?php $pageQuery = $listQuery; $pageQuery['page'] = $number; ?>
            <li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="<?= adminEscape(adminUserListUrl($pageQuery)) ?>"><?= $number ?></a></li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php adminFooter(); ?>
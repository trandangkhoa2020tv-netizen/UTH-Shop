<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';

$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$where = "WHERE u.role = 'user'";
$params = [];
if ($search !== '') {
    $where .= ' AND (u.full_name LIKE :name_search OR u.email LIKE :email_search OR u.phone LIKE :phone_search)';
    $params = [
        ':name_search' => '%' . $search . '%',
        ':email_search' => '%' . $search . '%',
        ':phone_search' => '%' . $search . '%',
    ];
}
$count = $conn->prepare("SELECT COUNT(*) FROM users u $where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$query = $conn->prepare(
    "SELECT u.id, u.full_name, u.email, u.phone, u.address, u.created_at,
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
    'admin' => 'Không thể xóa tài khoản quản trị tại trang này.',
    'failed' => 'Không thể xóa tài khoản. Vui lòng thử lại.',
];
$deleteError = $messages[(string)($_GET['delete_error'] ?? '')] ?? '';
$deleted = isset($_GET['deleted']);

adminHeader('Quản lý người dùng');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <p class="admin-page-intro mb-0">Quản lý hồ sơ khách hàng, thông tin liên hệ và lịch sử mua hàng.</p>
    <span class="badge rounded-pill text-bg-primary-subtle text-primary px-3 py-2"><i class="bi bi-people me-1"></i><?= $total ?> khách hàng</span>
</div>
<?php if ($deleted): ?><div class="alert alert-success">Đã xóa tài khoản khách hàng.</div><?php endif; ?>
<?php if ($deleteError): ?><div class="alert alert-warning"><?= adminEscape($deleteError) ?></div><?php endif; ?>
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-4"><div class="admin-summary-card bg-white p-3 h-100"><div class="admin-summary-label"><i class="bi bi-person-check me-1"></i>Khách hàng hiển thị</div><div class="admin-summary-value"><?= $total ?></div></div></div>
    <div class="col-sm-6 col-xl-4"><div class="admin-summary-card bg-white p-3 h-100"><div class="admin-summary-label"><i class="bi bi-shield-check me-1"></i>Dữ liệu đơn hàng</div><div class="small text-secondary mt-2">Tài khoản có đơn được giữ lại để bảo toàn lịch sử mua hàng.</div></div></div>
</div>
<form class="card card-body admin-table-card mb-3" method="get">
    <div class="row g-2">
        <div class="col-md">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input class="form-control" type="search" name="q" value="<?= adminEscape($search) ?>" placeholder="Tên, email hoặc số điện thoại">
            </div>
        </div>
        <div class="col-auto"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Tìm khách hàng</button></div>
        <?php if ($search !== ''): ?><div class="col-auto"><a class="btn btn-outline-secondary" href="/Web/admin/users/index.php">Xóa lọc</a></div><?php endif; ?>
    </div>
</form>
<div class="card admin-table-card">
    <div class="table-responsive admin-table-wrap">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th class="ps-3">Khách hàng</th><th>Số điện thoại</th><th>Địa chỉ</th><th>Đơn hàng</th><th>Ngày tham gia</th><th class="text-end pe-3">Thao tác</th></tr></thead>
            <tbody>
            <?php if (!$users): ?>
                <tr><td colspan="6" class="text-center text-secondary py-5"><i class="bi bi-person-exclamation fs-3 d-block mb-2"></i>Chưa có khách hàng phù hợp.</td></tr>
            <?php else: foreach ($users as $user): ?>
                <tr>
                    <td class="ps-3"><span class="d-block fw-semibold"><?= adminEscape($user['full_name']) ?></span><small class="text-secondary"><?= adminEscape($user['email']) ?></small></td>
                    <td><?= adminEscape($user['phone'] ?: '—') ?></td>
                    <td class="admin-cell-wrap text-secondary"><?= adminEscape($user['address'] ?: 'Chưa cập nhật') ?></td>
                    <td><a class="badge rounded-pill text-bg-light text-decoration-none" href="/Web/admin/users/detail.php?id=<?= (int)$user['id'] ?>#orders"><?= (int)$user['order_count'] ?> đơn</a></td>
                    <td class="text-secondary"><?= adminEscape(date('d/m/Y', strtotime($user['created_at']))) ?></td>
                    <td class="text-end pe-3 admin-row-actions">
                        <a class="btn btn-sm btn-outline-primary" href="/Web/admin/users/detail.php?id=<?= (int)$user['id'] ?>"><i class="bi bi-person-lines-fill me-1"></i>Hồ sơ</a>
                        <?php if ((int)$user['order_count'] === 0): ?>
                            <form class="d-inline" method="post" action="/Web/admin/users/delete.php" onsubmit="return confirm('Xóa tài khoản <?= adminEscape($user['full_name']) ?>? Thao tác này không thể hoàn tác.');">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Xóa khách hàng"><i class="bi bi-person-x"></i></button>
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
            <li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?q=<?= urlencode($search) ?>&page=<?= $number ?>"><?= $number ?></a></li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php adminFooter(); ?>
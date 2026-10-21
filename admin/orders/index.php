<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';

$statuses = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
];
$status = (string)($_GET['status'] ?? '');
if (!array_key_exists($status, $statuses)) {
    $status = '';
}
$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$conditions = [];
$params = [];
if ($status !== '') {
    $conditions[] = 'o.status = :status';
    $params[':status'] = $status;
}
if ($search !== '') {
    $conditions[] = '(u.full_name LIKE :name_search OR u.email LIKE :email_search OR CAST(o.id AS CHAR) = :order_id)';
    $params[':name_search'] = '%' . $search . '%';
    $params[':email_search'] = '%' . $search . '%';
    $params[':order_id'] = $search;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$count = $conn->prepare(
    "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON u.id = o.user_id $where"
);
$count->execute($params);
$total = (int)$count->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$query = $conn->prepare(
    "SELECT o.id, o.total_amount, o.status, o.created_at, o.phone, o.payment_method,
            o.shipping_address, u.id AS customer_id, u.full_name, u.email,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o LEFT JOIN users u ON u.id = o.user_id
     $where
     ORDER BY o.created_at DESC, o.id DESC LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $query->bindValue($key, $value, PDO::PARAM_STR);
}
$query->bindValue(':limit', $perPage, PDO::PARAM_INT);
$query->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$query->execute();
$orders = $query->fetchAll(PDO::FETCH_ASSOC);

$notice = '';
if (isset($_GET['updated'])) {
    $notice = 'Đã cập nhật trạng thái đơn hàng.';
} elseif (isset($_GET['update_error'])) {
    $notice = 'Không thể cập nhật đơn hàng. Vui lòng kiểm tra lại trạng thái.';
}

adminHeader('Quản lý đơn hàng');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <p class="admin-page-intro mb-0">Theo dõi khách đặt, thông tin giao hàng, thanh toán và tiến độ xử lý.</p>
    <span class="badge rounded-pill text-bg-primary-subtle text-primary px-3 py-2"><i class="bi bi-receipt me-1"></i><?= $total ?> đơn hàng</span>
</div>
<?php if ($notice): ?><div class="alert <?= isset($_GET['updated']) ? 'alert-success' : 'alert-warning' ?>"><?= adminEscape($notice) ?></div><?php endif; ?>
<?php
$statusCounts = $conn->query('SELECT status, COUNT(*) AS total FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<div class="row g-2 mb-3">
    <?php foreach ($statuses as $value => $label):
        $summaryColors = ['pending' => 'warning', 'processing' => 'primary', 'completed' => 'success', 'cancelled' => 'secondary'];
    ?>
        <div class="col-6 col-xl-3">
            <a class="admin-summary-card bg-white d-flex align-items-center justify-content-between p-3 h-100 text-decoration-none" href="/Web/admin/orders/index.php?status=<?= adminEscape($value) ?>">
                <span><span class="admin-summary-label d-block"><?= adminEscape($label) ?></span><span class="admin-summary-value"><?= (int)($statusCounts[$value] ?? 0) ?></span></span>
                <span class="badge rounded-pill text-bg-<?= adminEscape($summaryColors[$value]) ?>"><i class="bi bi-circle-fill small"></i></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>
<form class="card card-body admin-table-card mb-3" method="get">
    <div class="row g-2">
        <div class="col-md">
            <input class="form-control" type="search" name="q" value="<?= adminEscape($search) ?>" placeholder="Tìm mã đơn, tên hoặc email khách hàng">
        </div>
        <div class="col-md-4">
            <select class="form-select" name="status" aria-label="Lọc trạng thái">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($statuses as $value => $label): ?>
                    <option value="<?= adminEscape($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= adminEscape($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Lọc</button></div>
        <?php if ($search !== '' || $status !== ''): ?><div class="col-auto"><a class="btn btn-outline-secondary" href="/Web/admin/orders/index.php">Xóa lọc</a></div><?php endif; ?>
    </div>
</form>
<div class="card admin-table-card">
    <div class="table-responsive admin-table-wrap">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th class="ps-3">Mã đơn</th><th>Khách hàng & liên hệ</th><th>Địa chỉ giao hàng</th><th>Sản phẩm</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày đặt</th><th class="text-end pe-3">Chi tiết</th></tr></thead>
            <tbody>
            <?php if (!$orders): ?>
                <tr><td colspan="9" class="text-center text-secondary py-5"><i class="bi bi-receipt fs-3 d-block mb-2"></i>Không tìm thấy đơn hàng phù hợp.</td></tr>
            <?php else: foreach ($orders as $order):
                $badge = ['pending' => 'warning', 'processing' => 'primary', 'completed' => 'success', 'cancelled' => 'secondary'][$order['status']] ?? 'secondary';
                $paymentLabels = ['cod' => 'Khi nhận hàng', 'bank_transfer' => 'Chuyển khoản'];
            ?>
                <tr>
                    <td class="ps-3 fw-semibold">#<?= (int)$order['id'] ?></td>
                    <td class="admin-contact">
                        <?php if ($order['customer_id']): ?><a class="d-block fw-semibold text-decoration-none" href="/Web/admin/users/detail.php?id=<?= (int)$order['customer_id'] ?>"><?= adminEscape($order['full_name']) ?></a><?php else: ?><span class="d-block fw-semibold">Tài khoản đã xóa</span><?php endif; ?>
                        <small class="d-block text-secondary"><?= adminEscape($order['email'] ?? '') ?></small>
                        <small class="d-block text-secondary"><i class="bi bi-telephone me-1"></i><?= adminEscape($order['phone'] ?: 'Chưa có số điện thoại') ?></small>
                    </td>
                    <td class="admin-cell-wrap text-secondary"><?= adminEscape($order['shipping_address'] ?: 'Chưa có địa chỉ') ?></td>
                    <td><?= (int)$order['item_count'] ?> mặt hàng</td>
                    <td class="fw-semibold"><?= number_format((float)$order['total_amount'], 0, ',', '.') ?> ₫</td>
                    <td><?= adminEscape($paymentLabels[$order['payment_method']] ?? $order['payment_method']) ?></td>
                    <td><span class="badge text-bg-<?= adminEscape($badge) ?>"><?= adminEscape($statuses[$order['status']] ?? $order['status']) ?></span></td>
                    <td class="text-secondary"><?= adminEscape(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                    <td class="text-end pe-3 admin-row-actions"><a class="btn btn-sm btn-outline-primary" href="/Web/admin/orders/detail.php?id=<?= (int)$order['id'] ?>"><i class="bi bi-eye me-1"></i>Xem đơn</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if ($totalPages > 1): ?>
<nav class="mt-3" aria-label="Phân trang đơn hàng">
    <ul class="pagination justify-content-end">
        <?php for ($number = 1; $number <= $totalPages; $number++): ?>
            <li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&page=<?= $number ?>"><?= $number ?></a></li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php adminFooter(); ?>
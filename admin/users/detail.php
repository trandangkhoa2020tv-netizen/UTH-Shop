<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Mã người dùng không hợp lệ.');
}

$userQuery = $conn->prepare(
    "SELECT id, full_name, email, phone, address, role, created_at
     FROM users WHERE id = :id AND role = 'user'"
);
$userQuery->execute([':id' => $id]);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    exit('Không tìm thấy khách hàng.');
}

$orderSummary = $conn->prepare(
    "SELECT COUNT(*) AS order_count,
            COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total_amount ELSE 0 END), 0) AS total_spent
     FROM orders WHERE user_id = :id"
);
$orderSummary->execute([':id' => $id]);
$summary = $orderSummary->fetch(PDO::FETCH_ASSOC);

$ordersQuery = $conn->prepare(
    'SELECT o.id, o.total_amount, o.status, o.created_at, o.phone, o.payment_method,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o WHERE o.user_id = :id
     ORDER BY o.created_at DESC, o.id DESC'
);
$ordersQuery->execute([':id' => $id]);
$orders = $ordersQuery->fetchAll(PDO::FETCH_ASSOC);
$statuses = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
];
$statusBadges = ['pending' => 'warning', 'processing' => 'primary', 'completed' => 'success', 'cancelled' => 'secondary'];
$paymentLabels = ['cod' => 'Thanh toán khi nhận hàng', 'bank_transfer' => 'Chuyển khoản ngân hàng'];

adminHeader('Hồ sơ khách hàng');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <p class="admin-page-intro mb-0">Thông tin tài khoản và toàn bộ đơn hàng liên quan đến khách hàng.</p>
    <a class="btn btn-outline-secondary" href="/Web/admin/users/index.php"><i class="bi bi-arrow-left me-1"></i>Danh sách khách hàng</a>
</div>
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-4">
        <section class="admin-summary-card bg-white p-3 h-100">
            <div class="admin-summary-label"><i class="bi bi-receipt me-1"></i>Tổng đơn hàng</div>
            <div class="admin-summary-value"><?= (int)$summary['order_count'] ?></div>
        </section>
    </div>
    <div class="col-sm-6 col-xl-4">
        <section class="admin-summary-card bg-white p-3 h-100">
            <div class="admin-summary-label"><i class="bi bi-currency-exchange me-1"></i>Giá trị đơn không bị hủy</div>
            <div class="admin-summary-value"><?= number_format((float)$summary['total_spent'], 0, ',', '.') ?> ₫</div>
        </section>
    </div>
</div>
<section class="card admin-table-card mb-3">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-4">
            <span class="admin-stat-icon"><i class="bi bi-person"></i></span>
            <div>
                <h2 class="h5 fw-bold mb-1"><?= adminEscape($user['full_name']) ?></h2>
                <span class="small text-secondary">Khách hàng từ <?= adminEscape(date('d/m/Y', strtotime($user['created_at']))) ?></span>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="admin-summary-label mb-1"><i class="bi bi-envelope me-1"></i>Email</div>
                <div class="fw-semibold text-break"><?= adminEscape($user['email']) ?></div>
            </div>
            <div class="col-md-6">
                <div class="admin-summary-label mb-1"><i class="bi bi-telephone me-1"></i>Số điện thoại</div>
                <div class="fw-semibold"><?= adminEscape($user['phone'] ?: 'Chưa cập nhật') ?></div>
            </div>
            <div class="col-12">
                <div class="admin-summary-label mb-1"><i class="bi bi-geo-alt me-1"></i>Địa chỉ tài khoản</div>
                <div class="fw-semibold"><?= nl2br(adminEscape($user['address'] ?: 'Chưa cập nhật')) ?></div>
            </div>
        </div>
    </div>
</section>
<section class="card admin-table-card" id="orders">
    <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
        <div>
            <h2 class="h5 fw-bold mb-1">Lịch sử đơn hàng</h2>
            <span class="small text-secondary">Thông tin giao nhận được lấy theo từng đơn.</span>
        </div>
        <span class="badge rounded-pill text-bg-light"><?= count($orders) ?> đơn</span>
    </div>
    <div class="table-responsive admin-table-wrap">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th class="ps-3">Mã đơn / Ngày đặt</th><th>Liên hệ giao hàng</th><th>Mặt hàng</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th class="text-end pe-3">Chi tiết</th></tr></thead>
            <tbody>
            <?php if (!$orders): ?>
                <tr><td colspan="7" class="text-center text-secondary py-5"><i class="bi bi-bag-x fs-3 d-block mb-2"></i>Khách hàng chưa có đơn hàng.</td></tr>
            <?php else: foreach ($orders as $order):
                $badge = $statusBadges[$order['status']] ?? 'secondary';
            ?>
                <tr>
                    <td class="ps-3"><span class="d-block fw-semibold">#<?= (int)$order['id'] ?></span><small class="text-secondary"><?= adminEscape(date('d/m/Y H:i', strtotime($order['created_at']))) ?></small></td>
                    <td><?= adminEscape($order['phone'] ?: 'Chưa có số điện thoại') ?></td>
                    <td><?= (int)$order['item_count'] ?> mặt hàng</td>
                    <td class="fw-semibold"><?= number_format((float)$order['total_amount'], 0, ',', '.') ?> ₫</td>
                    <td><?= adminEscape($paymentLabels[$order['payment_method']] ?? $order['payment_method']) ?></td>
                    <td><span class="badge text-bg-<?= adminEscape($badge) ?>"><?= adminEscape($statuses[$order['status']] ?? $order['status']) ?></span></td>
                    <td class="text-end pe-3"><a class="btn btn-sm btn-outline-primary" href="/Web/admin/orders/detail.php?id=<?= (int)$order['id'] ?>"><i class="bi bi-eye me-1"></i>Xem đơn</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php adminFooter(); ?>

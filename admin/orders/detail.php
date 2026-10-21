<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Mã đơn hàng không hợp lệ.');
}
$statement = $conn->prepare(
    'SELECT o.*, u.id AS customer_id, u.full_name, u.email
     FROM orders o LEFT JOIN users u ON u.id = o.user_id
     WHERE o.id = :id'
);
$statement->execute([':id' => $id]);
$order = $statement->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}
$itemsQuery = $conn->prepare(
    'SELECT oi.product_id, oi.quantity, oi.price, p.name AS product_name
     FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = :order_id ORDER BY oi.id'
);
$itemsQuery->execute([':order_id' => $id]);
$items = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);
$statuses = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
];
$paymentMethods = ['cod' => 'Thanh toán khi nhận hàng', 'bank_transfer' => 'Chuyển khoản ngân hàng'];
$badge = ['pending' => 'warning', 'processing' => 'primary', 'completed' => 'success', 'cancelled' => 'secondary'][$order['status']] ?? 'secondary';

adminHeader('Chi tiết đơn hàng #' . $id);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <p class="admin-page-intro mb-0">Kiểm tra thông tin giao hàng và cập nhật tiến trình đơn hàng.</p>
    <a class="btn btn-outline-secondary" href="/Web/admin/orders/index.php"><i class="bi bi-arrow-left me-1"></i>Danh sách đơn hàng</a>
</div>
<?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Đã cập nhật trạng thái đơn hàng.</div><?php endif; ?>
<div class="row g-3">
    <div class="col-lg-8">
        <section class="card admin-table-card mb-3">
            <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                <h2 class="h5 fw-bold mb-0">Sản phẩm trong đơn</h2>
                <span class="badge text-bg-<?= adminEscape($badge) ?>"><?= adminEscape($statuses[$order['status']] ?? $order['status']) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light"><tr><th class="ps-3">Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th class="text-end pe-3">Thành tiền</th></tr></thead>
                    <tbody>
                    <?php if (!$items): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-4">Đơn hàng chưa có chi tiết sản phẩm.</td></tr>
                    <?php else: foreach ($items as $item): $lineTotal = (float)$item['price'] * (int)$item['quantity']; ?>
                        <tr>
                            <td class="ps-3 fw-semibold"><?= adminEscape($item['product_name'] ?: 'Sản phẩm đã bị xóa') ?><small class="d-block text-secondary">Mã sản phẩm: <?= (int)$item['product_id'] ?></small></td>
                            <td><?= number_format((float)$item['price'], 0, ',', '.') ?> ₫</td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td class="text-end pe-3 fw-semibold"><?= number_format($lineTotal, 0, ',', '.') ?> ₫</td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot><tr><th colspan="3" class="text-end">Tổng đơn hàng</th><th class="text-end pe-3 text-primary"><?= number_format((float)$order['total_amount'], 0, ',', '.') ?> ₫</th></tr></tfoot>
                </table>
            </div>
        </section>
        <section class="card admin-table-card">
            <div class="card-body">
                <h2 class="h5 fw-bold mb-3">Thông tin giao hàng</h2>
                <div class="row g-3">
                    <div class="col-md-6"><small class="text-secondary d-block">Người nhận</small><span class="fw-semibold"><?= adminEscape($order['full_name'] ?: 'Tài khoản đã xóa') ?></span></div>
                    <div class="col-md-6"><small class="text-secondary d-block">Số điện thoại</small><span><?= adminEscape($order['phone'] ?: '—') ?></span></div>
                    <div class="col-12"><small class="text-secondary d-block">Địa chỉ</small><span><?= nl2br(adminEscape($order['shipping_address'] ?: '—')) ?></span></div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Email tài khoản</small>
                        <?php if ($order['customer_id']): ?>
                            <a href="/Web/admin/users/detail.php?id=<?= (int)$order['customer_id'] ?>"><?= adminEscape($order['email'] ?: 'Xem hồ sơ khách hàng') ?></a>
                        <?php else: ?>
                            <span><?= adminEscape($order['email'] ?: '—') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6"><small class="text-secondary d-block">Ghi chú</small><span><?= nl2br(adminEscape($order['note'] ?: 'Không có')) ?></span></div>
                </div>
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="card admin-table-card mb-3">
            <div class="card-body">
                <h2 class="h5 fw-bold">Thông tin đơn</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-secondary">Mã đơn</dt><dd class="col-7 fw-semibold">#<?= (int)$order['id'] ?></dd>
                    <dt class="col-5 text-secondary">Ngày đặt</dt><dd class="col-7"><?= adminEscape(date('d/m/Y H:i', strtotime($order['created_at']))) ?></dd>
                    <dt class="col-5 text-secondary">Thanh toán</dt><dd class="col-7"><?= adminEscape($paymentMethods[$order['payment_method']] ?? $order['payment_method']) ?></dd>
                </dl>
            </div>
        </section>
        <section class="card admin-table-card">
            <div class="card-body">
                <h2 class="h5 fw-bold mb-3">Cập nhật trạng thái</h2>
                <form method="post" action="/Web/admin/orders/update_status.php">
                    <?= csrfInput() ?>
                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                    <label class="form-label" for="status">Trạng thái đơn hàng</label>
                    <select class="form-select mb-3" id="status" name="status" required>
                        <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?= adminEscape($value) ?>" <?= $order['status'] === $value ? 'selected' : '' ?>><?= adminEscape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Lưu trạng thái</button>
                </form>
            </div>
        </section>
    </div>
</div>
<?php adminFooter(); ?>
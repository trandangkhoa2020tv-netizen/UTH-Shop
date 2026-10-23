<?php
$pageTitle = 'Chi tiết Đơn hàng';
require_once dirname(__DIR__) . '/_layout.php';

$id = (int)($_GET['id'] ?? 0);

// Lấy thông tin đơn hàng + khách hàng
$stmt = $conn->prepare("
    SELECT o.*, u.full_name, u.email, u.phone AS user_phone
    FROM orders o JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flashError('Không tìm thấy đơn hàng.');
    header('Location: /Web/admin/orders/index.php');
    exit();
}

// Lấy các sản phẩm trong đơn
$items = $conn->prepare("
    SELECT oi.*, p.name AS product_name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$items->execute([$id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);

$badgeColor = ['pending'=>'warning','processing'=>'info','shipping'=>'primary','delivered'=>'success','cancelled'=>'danger'];
$badge = $badgeColor[$order['status']] ?? 'secondary';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Đơn hàng #<?= $id ?></h5>
    <div class="d-flex gap-2">
        <a href="/Web/admin/orders/update_status.php?id=<?= $id ?>" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-arrow-repeat me-1"></i>Cập nhật trạng thái
        </a>
        <a href="/Web/admin/orders/index.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Quay lại
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Thông tin đơn hàng -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header py-2 px-4"><i class="bi bi-info-circle me-2"></i>Thông tin đơn hàng</div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted small" style="width:130px">Mã đơn hàng</td>
                        <td class="fw-bold">#<?= $order['id'] ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Trạng thái</td>
                        <td><span class="badge bg-<?= $badge ?>"><?= orderStatusLabel($order['status']) ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Tổng tiền</td>
                        <td class="fw-bold text-danger"><?= formatPrice($order['total_amount']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Thanh toán</td>
                        <td><?= $order['payment_method'] === 'cod' ? '<i class="bi bi-cash me-1"></i>Tiền mặt (COD)' : '<i class="bi bi-bank me-1"></i>Chuyển khoản' ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Ngày đặt</td>
                        <td><?= formatDate($order['created_at'], true) ?></td>
                    </tr>
                    <?php if ($order['note']): ?>
                    <tr>
                        <td class="text-muted small">Ghi chú</td>
                        <td><?= htmlspecialchars($order['note']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Thông tin khách hàng -->
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header py-2 px-4"><i class="bi bi-person me-2"></i>Thông tin khách hàng</div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted small" style="width:130px">Họ tên</td>
                        <td class="fw-semibold"><?= htmlspecialchars($order['full_name']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Email</td>
                        <td><?= htmlspecialchars($order['email']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Điện thoại</td>
                        <td><?= htmlspecialchars($order['phone'] ?? $order['user_phone'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Địa chỉ giao</td>
                        <td><?= htmlspecialchars($order['shipping_address']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="col-12">
        <div class="card">
            <div class="card-header py-2 px-4"><i class="bi bi-cart me-2"></i>Sản phẩm trong đơn</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="px-4" style="width:60px">Ảnh</th>
                            <th>Sản phẩm</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-end pe-4">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="px-4">
                                <img src="/Web/<?= htmlspecialchars(getProductImage($item['image'])) ?>"
                                     alt="" width="45" height="45" style="object-fit:cover;border-radius:8px">
                            </td>
                            <td class="small fw-semibold"><?= htmlspecialchars($item['product_name']) ?></td>
                            <td class="text-center"><?= $item['quantity'] ?></td>
                            <td class="text-end small"><?= formatPrice($item['price']) ?></td>
                            <td class="text-end pe-4 fw-bold text-danger">
                                <?= formatPrice($item['price'] * $item['quantity']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold pe-0">Tổng cộng:</td>
                            <td class="text-end pe-4 fw-bold text-danger fs-6">
                                <?= formatPrice($order['total_amount']) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/_layout_end.php'; ?>

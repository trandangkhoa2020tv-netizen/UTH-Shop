<?php
$pageTitle = 'Quản lý Đơn hàng';
require_once dirname(__DIR__) . '/_layout.php';

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT o.*, u.full_name, u.email FROM orders o
        JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];
if ($statusFilter) {
    $sql .= " AND o.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusList = ['pending','processing','shipping','delivered','cancelled'];
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-bag-check me-2 text-primary"></i>Đơn hàng</h5>
</div>

<!-- Filter tabs -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex gap-2 flex-wrap">
            <a href="/Web/admin/orders/index.php"
               class="btn btn-sm <?= $statusFilter === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Tất cả
            </a>
            <?php foreach ($statusList as $s): ?>
            <a href="/Web/admin/orders/index.php?status=<?= $s ?>"
               class="btn btn-sm <?= $statusFilter === $s ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= orderStatusLabel($s) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th class="px-4" style="width:70px">Mã ĐH</th>
                    <th>Khách hàng</th>
                    <th>Địa chỉ giao</th>
                    <th>Tổng tiền</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái</th>
                    <th>Ngày đặt</th>
                    <th class="text-center" style="width:90px">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Không có đơn hàng nào</td></tr>
            <?php else: foreach ($orders as $o):
                $badgeColor = ['pending'=>'warning','processing'=>'info','shipping'=>'primary','delivered'=>'success','cancelled'=>'danger'];
                $badge = $badgeColor[$o['status']] ?? 'secondary';
            ?>
                <tr>
                    <td class="px-4 fw-bold">#<?= $o['id'] ?></td>
                    <td>
                        <div class="small fw-semibold"><?= htmlspecialchars($o['full_name']) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($o['email']) ?></div>
                    </td>
                    <td class="small text-muted"><?= htmlspecialchars(truncate($o['shipping_address'], 40)) ?></td>
                    <td class="text-danger fw-semibold small"><?= formatPrice($o['total_amount']) ?></td>
                    <td class="small"><?= $o['payment_method'] === 'cod' ? 'Tiền mặt' : 'Chuyển khoản' ?></td>
                    <td>
                        <span class="badge bg-<?= $badge ?> badge-status">
                            <?= orderStatusLabel($o['status']) ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= formatDate($o['created_at']) ?></td>
                    <td class="text-center">
                        <a href="/Web/admin/orders/detail.php?id=<?= $o['id'] ?>"
                           class="btn btn-sm btn-outline-primary py-0">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small py-2 px-4">
        Tổng: <strong><?= count($orders) ?></strong> đơn hàng
    </div>
</div>
<?php require_once dirname(__DIR__) . '/_layout_end.php'; ?>

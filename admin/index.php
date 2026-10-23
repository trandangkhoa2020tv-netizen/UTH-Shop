<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/_layout.php';

// Thống kê tổng quan
$totalProducts  = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders    = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers     = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalRevenue   = $conn->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$pendingOrders  = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Đơn hàng mới nhất
$latestOrders = $conn->query("
    SELECT o.*, u.full_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 7
")->fetchAll(PDO::FETCH_ASSOC);

// Sản phẩm tồn kho thấp
$lowStock = $conn->query("SELECT * FROM products WHERE stock <= 3 ORDER BY stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-primary p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Tổng sản phẩm</div>
                    <div class="fs-3 fw-bold text-primary"><?= $totalProducts ?></div>
                </div>
                <i class="bi bi-box-seam fs-2 text-primary opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-success p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Tổng đơn hàng</div>
                    <div class="fs-3 fw-bold text-success"><?= $totalOrders ?></div>
                </div>
                <i class="bi bi-bag-check fs-2 text-success opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-warning p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Khách hàng</div>
                    <div class="fs-3 fw-bold text-warning"><?= $totalUsers ?></div>
                </div>
                <i class="bi bi-people fs-2 text-warning opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-danger p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Doanh thu</div>
                    <div class="fs-5 fw-bold text-danger"><?= formatPrice($totalRevenue) ?></div>
                </div>
                <i class="bi bi-currency-dollar fs-2 text-danger opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Đơn hàng mới nhất -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between py-3 px-4">
                <span><i class="bi bi-clock-history me-2 text-primary"></i>Đơn hàng mới nhất</span>
                <?php if ($pendingOrders > 0): ?>
                <span class="badge bg-danger"><?= $pendingOrders ?> chờ xử lý</span>
                <?php endif; ?>
                <a href="/Web/admin/orders/index.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="px-4">Mã ĐH</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($latestOrders)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Chưa có đơn hàng nào</td></tr>
                        <?php else: foreach ($latestOrders as $o): ?>
                            <tr>
                                <td class="px-4 fw-semibold">#<?= $o['id'] ?></td>
                                <td><?= htmlspecialchars($o['full_name']) ?></td>
                                <td><?= formatPrice($o['total_amount']) ?></td>
                                <td>
                                    <?php
                                    $badges = [
                                        'pending'    => 'warning',
                                        'processing' => 'info',
                                        'completed'  => 'success',
                                        'cancelled'  => 'danger',
                                    ];
                                    $badge = $badges[$o['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badge ?> badge-status">
                                        <?= orderStatusLabel($o['status']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= formatDate($o['created_at']) ?></td>
                                <td>
                                    <a href="/Web/admin/orders/detail.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-secondary py-0">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tồn kho thấp -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header py-3 px-4">
                <i class="bi bi-exclamation-triangle me-2 text-warning"></i>Tồn kho thấp (&le; 3)
            </div>
            <div class="card-body p-0">
                <?php if (empty($lowStock)): ?>
                    <p class="text-center text-muted py-4 small">Không có sản phẩm nào</p>
                <?php else: foreach ($lowStock as $p): ?>
                <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom">
                    <div>
                        <div class="small fw-semibold"><?= htmlspecialchars(truncate($p['name'], 30)) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><?= formatPrice($p['price']) ?></div>
                    </div>
                    <span class="badge bg-<?= $p['stock'] == 0 ? 'danger' : 'warning' ?> badge-status">
                        <?= $p['stock'] ?> còn lại
                    </span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>

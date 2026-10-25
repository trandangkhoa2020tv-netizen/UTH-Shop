<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/view.php';

$productCount = (int)$conn->query('SELECT COUNT(*) FROM products')->fetchColumn();
$categoryCount = (int)$conn->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$orderCount = (int)$conn->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$userCount = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$pendingOrderCount = (int)$conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
adminHeader('Quản trị cửa hàng');
?>
<p class="admin-page-intro">Tổng quan nhanh và lối tắt đến các khu vực quản lý cửa hàng.</p>
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Sản phẩm', $productCount, 'bi-box-seam', '/Web/admin/products/index.php', 'Đang kinh doanh'],
        ['Danh mục', $categoryCount, 'bi-grid', '/Web/admin/categories/index.php', 'Phân loại sản phẩm'],
        ['Đơn hàng', $orderCount, 'bi-receipt', '/Web/admin/orders/index.php', $pendingOrderCount . ' đơn đang chờ xử lý'],
        ['Người dùng', $userCount, 'bi-people', '/Web/admin/users/index.php', 'Tài khoản khách hàng'],
    ] as [$label, $count, $icon, $href, $description]): ?>
        <div class="col-6 col-xl-3">
            <a class="card card-body admin-stat-card h-100 text-decoration-none text-dark" href="<?= adminEscape($href) ?>">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="small text-secondary"><?= adminEscape($label) ?></span>
                    <span class="admin-stat-icon"><i class="bi <?= adminEscape($icon) ?>"></i></span>
                </div>
                <strong class="display-6 fw-bold mt-2"><?= (int)$count ?></strong>
                <span class="small text-secondary"><?= adminEscape($description) ?></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <section class="card admin-table-card h-100">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="h5 fw-bold mb-1">Công việc quản trị</h2>
                    <p class="small text-secondary mb-0">Quản lý nội dung và hoạt động bán hàng tại một nơi.</p>
                </div>
                <a class="btn btn-primary" href="/Web/admin/orders/index.php?status=pending">
                    <i class="bi bi-arrow-right-circle me-1"></i>Kiểm tra đơn chờ
                </a>
            </div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" href="/Web/admin/products/index.php">
                    <i class="bi bi-box-seam text-primary fs-5"></i><span><strong class="d-block">Sản phẩm</strong><small class="text-secondary">Cập nhật hàng hóa, giá và ảnh sản phẩm.</small></span>
                </a>
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" href="/Web/admin/categories/index.php">
                    <i class="bi bi-grid text-primary fs-5"></i><span><strong class="d-block">Danh mục</strong><small class="text-secondary">Sắp xếp sản phẩm theo nhóm phù hợp.</small></span>
                </a>
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" href="/Web/admin/banners/index.php">
                    <i class="bi bi-images text-primary fs-5"></i><span><strong class="d-block">Banner quảng cáo</strong><small class="text-secondary">Cập nhật hình ảnh nổi bật trên trang chủ.</small></span>
                </a>
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="card admin-table-card h-100">
            <div class="card-body">
                <span class="admin-stat-icon mb-3"><i class="bi bi-lightning-charge"></i></span>
                <h2 class="h5 fw-bold">Truy cập nhanh</h2>
                <p class="small text-secondary">Mở cửa hàng để kiểm tra nội dung khách hàng đang thấy.</p>
                <a class="btn btn-outline-primary w-100" href="/Web/index.php">Xem cửa hàng</a>
                <a class="btn btn-outline-secondary w-100 mt-2" href="/Web/admin/users/index.php">Quản lý người dùng</a>
            </div>
        </section>
    </div>
</div>
<?php adminFooter(); ?>
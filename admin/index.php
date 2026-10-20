<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/view.php';

$productCount = (int)$conn->query('SELECT COUNT(*) FROM products')->fetchColumn();
adminHeader('Quản trị cửa hàng');
?>
<div class="row g-3">
    <div class="col-md-6">
        <a class="card card-body h-100 text-decoration-none shadow-sm" href="/Web/admin/products/index.php">
            <span class="text-secondary">Danh mục quản lý</span>
            <strong class="h4 text-dark mt-2">Sản phẩm</strong>
            <span class="text-secondary"><?= $productCount ?> sản phẩm · Thêm, sửa, xóa và tải ảnh</span>
        </a>
    </div>
    <div class="col-md-6">
        <a class="card card-body h-100 text-decoration-none shadow-sm" href="/Web/admin/banners/index.php">
            <span class="text-secondary">Nội dung cửa hàng</span>
            <strong class="h4 text-dark mt-2">Banner quảng cáo</strong>
            <span class="text-secondary">Tải ảnh banner lên, thay ảnh hoặc gỡ banner</span>
        </a>
    </div>
</div>
<?php adminFooter(); ?>
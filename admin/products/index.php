<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';

$products = $conn->query(
    'SELECT p.id, p.name, p.price, p.stock, p.image, c.name AS category_name
     FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

adminHeader('Quản lý sản phẩm');
?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Đã lưu sản phẩm.</div><?php endif; ?>
<?php if (isset($_GET['image_cleanup_error'])): ?><div class="alert alert-warning">Đã cập nhật ảnh sản phẩm, nhưng một số tệp ảnh cũ chưa được dọn khỏi máy chủ.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Đã xóa sản phẩm.</div><?php endif; ?>
<?php if (isset($_GET['delete_error'])): ?><div class="alert alert-warning">Không thể xóa sản phẩm đã có trong đơn hàng để giữ nguyên lịch sử giao dịch.</div><?php endif; ?>
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-primary" href="/Web/admin/products/create.php"><i class="bi bi-plus-lg me-1"></i>Thêm sản phẩm</a>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Ảnh</th><th>Sản phẩm</th><th>Danh mục</th><th>Giá</th><th>Tồn kho</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><img class="admin-image-preview" src="<?= adminEscape(getProductImage($product['image'])) ?>" alt="" onerror="this.onerror=null;this.src='/Web/image/no-image.svg'"></td>
                    <td class="fw-semibold"><?= adminEscape($product['name']) ?></td>
                    <td><?= adminEscape($product['category_name'] ?? '—') ?></td>
                    <td><?= number_format((float)$product['price'], 0, ',', '.') ?>đ</td>
                    <td><?= (int)$product['stock'] ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="/Web/admin/products/edit.php?id=<?= (int)$product['id'] ?>">Sửa</a>
                        <form class="d-inline" method="post" action="/Web/admin/products/delete.php" onsubmit="return confirm('Xóa sản phẩm này?')">
                            <?= csrfInput() ?>
                            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?><tr><td colspan="6" class="text-center text-secondary py-4">Chưa có sản phẩm.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php adminFooter(); ?>
<?php
$pageTitle = 'Quản lý Danh mục';
require_once dirname(__DIR__) . '/_layout.php';

$categories = $conn->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id ORDER BY c.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-tags me-2 text-primary"></i>Danh mục sản phẩm</h5>
    <a href="/Web/admin/categories/create.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Thêm danh mục
    </a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="px-4" style="width:60px">ID</th>
                    <th>Tên danh mục</th>
                    <th>Mô tả</th>
                    <th class="text-center">Số SP</th>
                    <th>Ngày tạo</th>
                    <th class="text-center" style="width:120px">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($categories)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có danh mục nào</td></tr>
            <?php else: foreach ($categories as $cat): ?>
                <tr>
                    <td class="px-4 text-muted"><?= $cat['id'] ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($cat['name']) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars(truncate($cat['description'] ?? '', 60)) ?></td>
                    <td class="text-center"><span class="badge bg-primary"><?= $cat['product_count'] ?></span></td>
                    <td class="small text-muted"><?= formatDate($cat['created_at']) ?></td>
                    <td class="text-center">
                        <a href="/Web/admin/categories/edit.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-warning py-0 me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="/Web/admin/categories/delete.php?id=<?= $cat['id'] ?>"
                           class="btn btn-sm btn-outline-danger py-0"
                           onclick="return confirm('Xóa danh mục này?\nSản phẩm liên quan sẽ không còn danh mục.')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/_layout_end.php'; ?>

<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';

$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE c.name LIKE :name_search OR c.description LIKE :description_search';
    $params[':name_search'] = '%' . $search . '%';
    $params[':description_search'] = '%' . $search . '%';
}

$countQuery = $conn->prepare("SELECT COUNT(*) FROM categories c $where");
$countQuery->execute($params);
$total = (int)$countQuery->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$query = $conn->prepare(
    "SELECT c.id, c.name, c.description, c.created_at, COUNT(p.id) AS product_count
     FROM categories c LEFT JOIN products p ON p.category_id = c.id
     $where
     GROUP BY c.id, c.name, c.description, c.created_at
     ORDER BY c.name ASC LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $query->bindValue($key, $value, PDO::PARAM_STR);
}
$query->bindValue(':limit', $perPage, PDO::PARAM_INT);
$query->bindValue(':offset', $offset, PDO::PARAM_INT);
$query->execute();
$categories = $query->fetchAll(PDO::FETCH_ASSOC);

$notice = isset($_GET['saved']) ? 'Đã lưu danh mục.' : '';
$error = '';
if (isset($_GET['deleted'])) {
    $notice = 'Đã xóa danh mục. Sản phẩm thuộc danh mục này vẫn được giữ lại.';
} elseif (isset($_GET['delete_error'])) {
    $error = 'Không thể xóa danh mục. Vui lòng thử lại.';
}

adminHeader('Quản lý danh mục');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <p class="admin-page-intro mb-0">Tạo và sắp xếp các nhóm sản phẩm hiển thị trong cửa hàng.</p>
    <a class="btn btn-primary" href="/Web/admin/categories/create.php"><i class="bi bi-plus-lg me-1"></i>Thêm danh mục</a>
</div>
<?php if ($notice): ?><div class="alert alert-success"><?= adminEscape($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= adminEscape($error) ?></div><?php endif; ?>
<form class="card card-body admin-table-card mb-3" method="get">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input class="form-control" type="search" name="q" value="<?= adminEscape($search) ?>" placeholder="Tìm theo tên hoặc mô tả">
        <button class="btn btn-outline-primary" type="submit">Tìm kiếm</button>
        <?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="/Web/admin/categories/index.php">Xóa lọc</a><?php endif; ?>
    </div>
</form>
<div class="card admin-table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th class="ps-3">Danh mục</th><th>Mô tả</th><th>Sản phẩm</th><th>Ngày tạo</th><th class="text-end pe-3">Thao tác</th></tr></thead>
            <tbody>
            <?php if (!$categories): ?>
                <tr><td colspan="5" class="text-center text-secondary py-5">Chưa có danh mục phù hợp.</td></tr>
            <?php else: foreach ($categories as $category): ?>
                <tr>
                    <td class="ps-3 fw-semibold"><?= adminEscape($category['name']) ?></td>
                    <td class="text-secondary"><?= adminEscape($category['description'] ?: '—') ?></td>
                    <td><span class="badge text-bg-light"><?= (int)$category['product_count'] ?></span></td>
                    <td class="text-secondary"><?= adminEscape(date('d/m/Y', strtotime($category['created_at']))) ?></td>
                    <td class="text-end pe-3">
                        <div class="d-inline-flex gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="/Web/admin/categories/edit.php?id=<?= (int)$category['id'] ?>" aria-label="Sửa danh mục"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="/Web/admin/categories/delete.php" onsubmit="return confirm('Xóa danh mục này? Sản phẩm sẽ vẫn được giữ lại nhưng không còn thuộc danh mục.');">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Xóa danh mục"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if ($totalPages > 1): ?>
<nav class="mt-3" aria-label="Phân trang danh mục">
    <ul class="pagination justify-content-end">
        <?php for ($number = 1; $number <= $totalPages; $number++): ?>
            <li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?q=<?= urlencode($search) ?>&page=<?= $number ?>"><?= $number ?></a></li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php adminFooter(); ?>
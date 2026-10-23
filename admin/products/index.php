<?php
$pageTitle = 'Quản lý Sản phẩm';
require_once dirname(__DIR__) . '/_layout.php';

// Tìm kiếm & lọc
$search   = trim($_GET['q'] ?? '');
$catFilter = (int)($_GET['category'] ?? 0);

$sql = "SELECT p.*, c.name AS category_name FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 1=1";
$params = [];
if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catFilter) {
    $sql .= " AND p.category_id = ?";
    $params[] = $catFilter;
}
$sql .= " ORDER BY p.id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-primary"></i>Sản phẩm</h5>
    <a href="/Web/admin/products/create.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Thêm sản phẩm
    </a>
</div>

<!-- Filter bar -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Tìm tên, thương hiệu..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select form-select-sm">
                    <option value="">-- Tất cả danh mục --</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Lọc</button>
                <a href="/Web/admin/products/index.php" class="btn btn-sm btn-outline-secondary ms-1">Xóa lọc</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th class="px-3" style="width:50px">ID</th>
                    <th style="width:60px">Ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th class="text-center">Tồn kho</th>
                    <th class="text-center" style="width:120px">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Không tìm thấy sản phẩm nào</td></tr>
            <?php else: foreach ($products as $p): ?>
                <tr>
                    <td class="px-3 text-muted small"><?= $p['id'] ?></td>
                    <td>
                        <img src="/Web/<?= htmlspecialchars(getProductImage($p['image'])) ?>"
                             alt="" width="45" height="45" style="object-fit:cover;border-radius:8px">
                    </td>
                    <td>
                        <div class="fw-semibold small"><?= htmlspecialchars(truncate($p['name'], 45)) ?></div>
                        <?php if ($p['brand']): ?>
                        <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($p['brand']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                    <td class="small text-danger fw-semibold"><?= formatPrice($p['price']) ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $p['stock'] == 0 ? 'danger' : ($p['stock'] <= 3 ? 'warning' : 'success') ?>">
                            <?= $p['stock'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="/Web/admin/products/edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning py-0 me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="/Web/admin/products/delete.php?id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-outline-danger py-0"
                           onclick="return confirm('Xóa sản phẩm này?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small py-2 px-4">
        Tổng: <strong><?= count($products) ?></strong> sản phẩm
    </div>
</div>
<?php require_once dirname(__DIR__) . '/_layout_end.php'; ?>

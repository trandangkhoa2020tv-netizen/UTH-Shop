<?php
$pageTitle = 'Sản phẩm | UTH Shop';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$q = trim((string)($_GET['q'] ?? ''));
$categoryId = filter_var($_GET['category'] ?? '', FILTER_VALIDATE_INT);
if ($categoryId === false || $categoryId < 1) {
    $categoryId = null;
}

$categories = [];
try {
    $categoryStmt = $conn->query('SELECT id, name FROM categories ORDER BY name');
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = 'SELECT p.id, p.name, p.price, p.description, p.image, p.category_id, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE 1 = 1';
    $params = [];

    if ($q !== '') {
        $sql .= ' AND (p.name LIKE :q OR p.description LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }
    if ($categoryId !== null) {
        $sql .= ' AND p.category_id = :category_id';
        $params[':category_id'] = $categoryId;
    }

    $sql .= ' ORDER BY p.id DESC';
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    $products = [];
    $loadError = 'Không thể tải danh sách sản phẩm. Vui lòng kiểm tra kết nối cơ sở dữ liệu.';
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="container py-4 py-md-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold mb-1">UTH SHOP</p>
            <h1 class="section-title mb-0">Tất cả sản phẩm</h1>
            <p class="text-secondary mb-0 mt-2">Tìm sản phẩm phù hợp với nhu cầu của bạn.</p>
        </div>
        <span class="text-secondary small"><?php echo count($products); ?> sản phẩm</span>
    </div>

    <form action="/Web/products.php" method="get" class="row g-2 align-items-end mb-4 p-3 bg-white rounded-lg shadow-sm">
        <div class="col-12 col-md-6">
            <label for="q" class="form-label">Tìm kiếm sản phẩm</label>
            <input type="search" class="form-control" id="q" name="q"
                   placeholder="Nhập tên hoặc mô tả sản phẩm..." value="<?php echo e($q); ?>">
        </div>
        <div class="col-12 col-md-4">
            <label for="category" class="form-label">Danh mục</label>
            <select class="form-select" id="category" name="category">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int)$category['id']; ?>"
                        <?php echo ($categoryId === (int)$category['id']) ? 'selected' : ''; ?>>
                        <?php echo e($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-1 d-grid">
            <button type="submit" class="btn btn-primary" aria-label="Tìm kiếm"><i class="bi bi-search"></i></button>
        </div>
        <div class="col-6 col-md-1 d-grid">
            <a href="/Web/products.php" class="btn btn-outline-secondary">Xóa lọc</a>
        </div>
    </form>

    <?php if (!empty($loadError)): ?>
        <div class="alert alert-danger"><?php echo e($loadError); ?></div>
    <?php elseif (!$products): ?>
        <div class="text-center bg-white rounded-lg p-5 shadow-sm">
            <i class="bi bi-box-seam display-4 text-secondary"></i>
            <h2 class="h5 mt-3">Không tìm thấy sản phẩm</h2>
            <p class="text-secondary mb-0">Bạn hãy thử từ khóa khác hoặc chọn danh mục khác.</p>
        </div>
    <?php else: ?>
        <div class="row g-3 g-md-4">
            <?php foreach ($products as $product): ?>
                <?php
                $imagePath = getProductImage($product['image'] ?? '');
                ?>

                <div class="col-6 col-md-4 col-lg-3">
                    <article class="product-card">
                        <a class="product-card__img-wrap d-block" href="/Web/product_detail.php?id=<?php echo (int)$product['id']; ?>">
                            <img src="<?php echo e($imagePath); ?>"
                                 alt="<?php echo e($product['name']); ?>" loading="lazy"
                                 onerror="this.onerror=null;this.src='/Web/image/no-image.svg'">
                        </a>
                        <div class="product-card__body">
                            <?php if (!empty($product['category_name'])): ?>
                                <span class="small text-secondary"><?php echo e($product['category_name']); ?></span>
                            <?php endif; ?>
                            <a class="product-card__name" href="/Web/product_detail.php?id=<?php echo (int)$product['id']; ?>">
                                <?php echo e($product['name']); ?>
                            </a>
                            <div class="product-card__price"><?php echo e(formatPrice($product['price'])); ?></div>
                        </div>
                        <div class="product-card__footer">
                            <a class="btn-add-cart text-decoration-none" href="/Web/product_detail.php?id=<?php echo (int)$product['id']; ?>">
                                <i class="bi bi-eye"></i> Xem chi tiết
                            </a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
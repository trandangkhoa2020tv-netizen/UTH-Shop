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

$brandOptions = [
    'apple' => 'Apple',
    'samsung' => 'Samsung',
    'dell' => 'Dell',
    'asus' => 'Asus',
    'sony' => 'Sony',
];
$brand = is_scalar($_GET['brand'] ?? null) ? strtolower(trim((string)$_GET['brand'])) : '';
if (!isset($brandOptions[$brand])) {
    $brand = '';
}

$rawMinPrice = $_GET['min_price'] ?? '';
$rawMaxPrice = $_GET['max_price'] ?? '';
$minPriceInput = is_scalar($rawMinPrice) ? trim((string)$rawMinPrice) : '';
$maxPriceInput = is_scalar($rawMaxPrice) ? trim((string)$rawMaxPrice) : '';
$parsePrice = static function (string $value): ?string {
    if ($value === '') {
        return null;
    }
    if (preg_match('/^\d+$/D', $value) === 1) {
        return $value;
    }
    if (preg_match('/^\d{1,3}(?:\.\d{3})+$/D', $value) === 1) {
        return str_replace('.', '', $value);
    }
    return null;
};
$minPrice = $parsePrice($minPriceInput);
$maxPrice = $parsePrice($maxPriceInput);
$minPriceDisplay = $minPrice !== null ? number_format((float)$minPrice, 0, ',', '.') : $minPriceInput;
$maxPriceDisplay = $maxPrice !== null ? number_format((float)$maxPrice, 0, ',', '.') : $maxPriceInput;
$filterError = null;

if ($minPriceInput !== '' && $minPrice === null) {
    $filterError = 'Giá tối thiểu không hợp lệ. Hãy nhập số tiền, có thể dùng dấu chấm phân tách hàng nghìn.';
} elseif ($maxPriceInput !== '' && $maxPrice === null) {
    $filterError = 'Giá tối đa không hợp lệ. Hãy nhập số tiền, có thể dùng dấu chấm phân tách hàng nghìn.';
} elseif ($minPrice !== null && $maxPrice !== null && (float)$minPrice > (float)$maxPrice) {
    $filterError = 'Giá tối thiểu không thể lớn hơn giá tối đa.';
    $minPrice = null;
    $maxPrice = null;
}

$sortOptions = [
    'newest' => 'p.id DESC',
    'price_asc' => 'p.price ASC, p.id DESC',
    'price_desc' => 'p.price DESC, p.id DESC',
    'name_asc' => 'p.name ASC, p.id DESC',
];
$sort = is_scalar($_GET['sort'] ?? null) ? (string)$_GET['sort'] : 'newest';
if (!isset($sortOptions[$sort])) {
    $sort = 'newest';
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
        $sql .= ' AND (p.name LIKE :name_q OR p.description LIKE :description_q)';
        $params[':name_q'] = '%' . $q . '%';
        $params[':description_q'] = '%' . $q . '%';
    }
    if ($categoryId !== null) {
        $sql .= ' AND p.category_id = :category_id';
        $params[':category_id'] = $categoryId;
    }
    if ($brand !== '') {
        $sql .= ' AND LOWER(TRIM(p.brand)) = :brand';
        $params[':brand'] = $brand;
    }
    if ($filterError === null && $minPrice !== null) {
        $sql .= ' AND p.price >= :min_price';
        $params[':min_price'] = $minPrice;
    }
    if ($filterError === null && $maxPrice !== null) {
        $sql .= ' AND p.price <= :max_price';
        $params[':max_price'] = $maxPrice;
    }

    $sql .= ' ORDER BY ' . $sortOptions[$sort];
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

    <form action="/Web/products.php" method="get" class="row g-3 align-items-end mb-4 p-3 bg-white rounded-lg shadow-sm">
        <div class="col-12 col-md-4">
            <label for="q" class="form-label">Tìm kiếm sản phẩm</label>
            <input type="search" class="form-control" id="q" name="q"
                   placeholder="Nhập tên hoặc mô tả sản phẩm..." value="<?php echo e($q); ?>">
        </div>
        <div class="col-12 col-md-2">
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
        <div class="col-12 col-md-2">
            <label for="brand" class="form-label">Thương hiệu</label>
            <select class="form-select" id="brand" name="brand">
                <option value="">Tất cả thương hiệu</option>
                <?php foreach ($brandOptions as $brandValue => $brandLabel): ?>
                    <option value="<?php echo e($brandValue); ?>"
                        <?php echo $brand === $brandValue ? 'selected' : ''; ?>>
                        <?php echo e($brandLabel); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="min_price" class="form-label">Giá từ (₫)</label>
            <div class="price-filter-stepper">
                <button type="button" class="price-filter-stepper__button" data-price-step="-1"
                        aria-label="Giảm giá tối thiểu 500.000 đồng"><i class="bi bi-dash"></i></button>
                <input type="text" class="form-control price-filter-stepper__input" id="min_price" name="min_price"
                       inputmode="numeric" autocomplete="off" data-price-format
                       value="<?php echo e($minPriceDisplay); ?>" placeholder="Thấp nhất">
                <button type="button" class="price-filter-stepper__button" data-price-step="1"
                        aria-label="Tăng giá tối thiểu 500.000 đồng"><i class="bi bi-plus"></i></button>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <label for="max_price" class="form-label">Giá đến (₫)</label>
            <div class="price-filter-stepper">
                <button type="button" class="price-filter-stepper__button" data-price-step="-1"
                        aria-label="Giảm giá tối đa 500.000 đồng"><i class="bi bi-dash"></i></button>
                <input type="text" class="form-control price-filter-stepper__input" id="max_price" name="max_price"
                       inputmode="numeric" autocomplete="off" data-price-format
                       value="<?php echo e($maxPriceDisplay); ?>" placeholder="Cao nhất">
                <button type="button" class="price-filter-stepper__button" data-price-step="1"
                        aria-label="Tăng giá tối đa 500.000 đồng"><i class="bi bi-plus"></i></button>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <label for="sort" class="form-label">Sắp xếp</label>
            <select class="form-select" id="sort" name="sort">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Tên A–Z</option>
            </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Lọc</button>
            <a href="/Web/products.php" class="btn btn-outline-secondary">Xóa lọc</a>
        </div>
    </form>

    <?php if ($filterError !== null): ?>
        <div class="alert alert-warning"><?php echo e($filterError); ?></div>
    <?php endif; ?>

    <?php if (!empty($loadError)): ?>
        <div class="alert alert-danger"><?php echo e($loadError); ?></div>
    <?php elseif (!$products): ?>
        <div class="text-center bg-white rounded-lg p-5 shadow-sm">
            <i class="bi bi-box-seam display-4 text-secondary"></i>
            <h2 class="h5 mt-3">Không tìm thấy sản phẩm</h2>
            <p class="text-secondary mb-0">Bạn hãy thử thay đổi từ khóa, danh mục hoặc khoảng giá.</p>
        </div>
    <?php else: ?>
        <div class="row g-3 g-md-4">
            <?php foreach ($products as $product): ?>
                <?php
                $imagePath = getProductImage($product['image'] ?? '');
                ?>

                <div class="col-6 col-md-4 col-xl-3">
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
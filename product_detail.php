<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$productId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if ($productId === false || $productId < 1) {
    http_response_code(400);
    $productId = 0;
}

$stmt = $conn->prepare(
    'SELECT p.id, p.name, p.price, p.description, p.image, p.category_id, c.name AS category_name
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.id = :id LIMIT 1'
);
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = $product ? $product['name'] . ' | UTH Shop' : 'Không tìm thấy sản phẩm | UTH Shop';
$notice = '';
$noticeType = 'danger';

if ($product && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);

    if (!verifyCsrfToken($token)) {
        $notice = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } elseif ($quantity === false || $quantity < 1 || $quantity > 99) {
        $notice = 'Số lượng phải từ 1 đến 99.';
    } else {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $key = (string)$product['id'];
        $currentQuantity = (int)($_SESSION['cart'][$key]['quantity'] ?? 0);
        $newQuantity = $currentQuantity + $quantity;

        if ($newQuantity > 99) {
            $notice = 'Mỗi sản phẩm chỉ có thể đặt tối đa 99 sản phẩm.';
        } else {
            $_SESSION['cart'][$key] = [
                'product_id' => (int)$product['id'],
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'image' => $product['image'] ?? '',
                'quantity' => $newQuantity
            ];
            $notice = 'Đã thêm sản phẩm vào giỏ hàng.';
            $noticeType = 'success';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="container py-4 py-md-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/Web/index.php">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="/Web/products.php">Sản phẩm</a></li>
            <?php if ($product): ?><li class="breadcrumb-item active" aria-current="page"><?php echo e($product['name']); ?></li><?php endif; ?>
        </ol>
    </nav>

    <?php if (!$product): ?>
        <div class="text-center bg-white rounded-lg shadow-sm p-5">
            <i class="bi bi-exclamation-circle display-4 text-secondary"></i>
            <h1 class="h4 mt-3">Không tìm thấy sản phẩm</h1>
            <p class="text-secondary">Sản phẩm có thể đã bị xóa hoặc đường dẫn không hợp lệ.</p>
            <a href="/Web/products.php" class="btn btn-primary">Quay lại danh sách sản phẩm</a>
        </div>
    <?php else: ?>
        <?php $imagePath = getProductImage($product['image'] ?? ''); ?>
        <?php if ($notice !== ''): ?>
            <div class="alert alert-<?php echo e($noticeType); ?> alert-dismissible fade show" role="alert">
                <?php echo e($notice); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 g-lg-5 align-items-start">
            <div class="col-12 col-md-6">
                <div class="bg-white rounded-lg shadow-sm p-3 p-md-4">
                    <img src="<?php echo e($imagePath); ?>" alt="<?php echo e($product['name']); ?>"
                         onerror="this.onerror=null;this.src='/Web/image/no-image.svg'"
                         class="w-100 rounded" style="max-height: 520px; object-fit: contain;">
                </div>
            </div>
            <div class="col-12 col-md-6">
                <?php if (!empty($product['category_name'])): ?>
                    <span class="badge text-bg-light border mb-2"><?php echo e($product['category_name']); ?></span>
                <?php endif; ?>
                <h1 class="h2 fw-bold mb-3"><?php echo e($product['name']); ?></h1>
                <div class="product-card__price fs-3 mb-3"><?php echo e(formatPrice($product['price'])); ?></div>
                <p class="text-secondary">Sản phẩm chính hãng được cung cấp bởi UTH Shop.</p>
                <hr>
                <h2 class="h5 fw-bold">Mô tả sản phẩm</h2>
                <p class="text-secondary" style="white-space: pre-line;"><?php echo e($product['description'] ?: 'Thông tin mô tả đang được cập nhật.'); ?></p>

                <form method="post" action="/Web/product_detail.php?id=<?php echo (int)$product['id']; ?>" class="mt-4">
                    <?php echo csrfInput(); ?>
                    <div class="mb-3" style="max-width: 150px;">
                        <label for="quantity" class="form-label">Số lượng</label>
                        <input class="form-control" type="number" id="quantity" name="quantity"
                               value="1" min="1" max="99" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-cart-plus me-2"></i>Thêm vào giỏ hàng
                    </button>
                    <a href="/Web/cart.php" class="btn btn-outline-primary w-100 mt-2">
                        <i class="bi bi-bag-check me-2"></i>Xem giỏ hàng
                    </a>
                </form>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/product_specifications.php';
require_once __DIR__ . '/includes/migrations/product_specifications.php';
require_once __DIR__ . '/includes/migrations/product_reviews.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$productId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if ($productId === false || $productId < 1) {
    http_response_code(400);
    $productId = 0;
}

$stmt = $conn->prepare(
    'SELECT p.id, p.name, p.price, p.description, p.image, p.category_id, p.brand,
            p.cpu, p.ram, p.specifications, c.name AS category_name
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.id = :id LIMIT 1'
);
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

$gallery = [];
$purchaseCount = 0;
$reviewSummary = ['review_count' => 0, 'average_rating' => 0];
$reviews = [];
$eligibleOrders = [];
if ($product) {
    $galleryStmt = $conn->prepare(
        'SELECT image, is_primary, sort_order FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, sort_order ASC, id ASC'
    );
    $galleryStmt->execute([':product_id' => $product['id']]);
    $productImages = $galleryStmt->fetchAll(PDO::FETCH_ASSOC);
    $primaryImage = $product['image'] ?: ($productImages[0]['image'] ?? '');
    $gallery[] = ['image' => $primaryImage];
    foreach ($productImages as $productImage) {
        if ($productImage['image'] !== $primaryImage) {
            $gallery[] = $productImage;
        }
    }
    $product['image'] = $primaryImage;

    $purchaseQuery = $conn->prepare(
        "SELECT COALESCE(SUM(oi.quantity), 0)
         FROM order_items oi
         INNER JOIN orders o ON o.id = oi.order_id
         WHERE oi.product_id = ? AND o.status = 'completed'"
    );
    $purchaseQuery->execute([$product['id']]);
    $purchaseCount = (int)$purchaseQuery->fetchColumn();

    $reviewSummaryQuery = $conn->prepare(
        'SELECT COUNT(*) AS review_count, COALESCE(AVG(rating), 0) AS average_rating
         FROM product_reviews WHERE product_id = ?'
    );
    $reviewSummaryQuery->execute([$product['id']]);
    $reviewSummary = $reviewSummaryQuery->fetch(PDO::FETCH_ASSOC);

    $reviewsQuery = $conn->prepare(
        'SELECT r.id, r.rating, r.comment, r.created_at, u.full_name
         FROM product_reviews r
         INNER JOIN users u ON u.id = r.user_id
         WHERE r.product_id = ?
         ORDER BY r.created_at DESC, r.id DESC'
    );
    $reviewsQuery->execute([$product['id']]);
    $reviews = $reviewsQuery->fetchAll(PDO::FETCH_ASSOC);
    if ($reviews) {
        $reviewIds = array_column($reviews, 'id');
        $reviewImageQuery = $conn->prepare(
            'SELECT review_id, image FROM product_review_images
             WHERE review_id IN (' . implode(',', array_fill(0, count($reviewIds), '?')) . ')
             ORDER BY id'
        );
        $reviewImageQuery->execute($reviewIds);
        $reviewImages = $reviewImageQuery->fetchAll(PDO::FETCH_ASSOC);
        $imagesByReview = [];
        foreach ($reviewImages as $reviewImage) {
            $imagesByReview[$reviewImage['review_id']][] = $reviewImage['image'];
        }
        foreach ($reviews as &$review) {
            $review['images'] = $imagesByReview[$review['id']] ?? [];
        }
        unset($review);
    }

    if (isLoggedIn() && !isAdmin()) {
        $eligibleOrdersQuery = $conn->prepare(
            "SELECT o.id, o.created_at
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.id
             LEFT JOIN product_reviews r
               ON r.order_id = o.id AND r.product_id = oi.product_id
             WHERE o.user_id = ? AND o.status = 'completed'
               AND oi.product_id = ? AND r.id IS NULL
             ORDER BY o.created_at DESC"
        );
        $eligibleOrdersQuery->execute([$_SESSION['user_id'], $product['id']]);
        $eligibleOrders = $eligibleOrdersQuery->fetchAll(PDO::FETCH_ASSOC);
    }
}

$pageTitle = $product ? $product['name'] . ' | UTH Shop' : 'Không tìm thấy sản phẩm | UTH Shop';
$notice = '';
$noticeType = 'danger';
$showCartForm = false;

if ($product && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);

    if (isAdmin()) {
        $notice = 'Tài khoản quản trị chỉ dùng để quản lý cửa hàng, không thể thêm sản phẩm vào giỏ hàng.';
    } elseif (!verifyCsrfToken($token)) {
        $notice = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } elseif ($quantity === false || $quantity < 1 || $quantity > 99) {
        $notice = 'Số lượng phải từ 1 đến 99.';
        $showCartForm = true;
    } else {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $key = (string)$product['id'];
        $currentQuantity = (int)($_SESSION['cart'][$key]['quantity'] ?? 0);
        $newQuantity = $currentQuantity + $quantity;

        if ($newQuantity > 99) {
            $notice = 'Mỗi sản phẩm chỉ có thể đặt tối đa 99 sản phẩm.';
            $showCartForm = true;
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
            <div class="alert alert-<?php echo e($noticeType); ?> alert-dismissible fade show border-2 shadow-sm fs-5" role="alert" aria-live="assertive">
                <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i><?php echo e($notice); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 g-lg-5 align-items-start">
            <div class="col-12 col-md-6">
                <?php if (!empty($gallery)): ?>
                    <div class="product-gallery bg-white rounded-lg shadow-sm p-3 p-md-4" data-product-gallery>
                        <div class="product-gallery__viewport">
                            <div class="product-gallery__track">
                                <?php foreach ($gallery as $index => $item): ?>
                                    <div class="product-gallery__slide" aria-label="Ảnh sản phẩm <?= (int)($index + 1) ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>">
                                        <img src="<?= e(getProductImage($item['image'] ?? '')) ?>" alt="<?= e($product['name']) ?>"
                                             loading="eager" onerror="this.onerror=null;this.src='/Web/image/no-image.svg'">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php if (count($gallery) > 1): ?>
                            <div class="product-gallery__controls">
                                <button type="button" class="product-gallery__nav product-gallery__nav--prev" aria-label="Ảnh trước">‹</button>
                                <button type="button" class="product-gallery__nav product-gallery__nav--next" aria-label="Ảnh sau">›</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-sm p-3 p-md-4">
                        <img src="<?php echo e($imagePath); ?>" alt="<?php echo e($product['name']); ?>"
                             onerror="this.onerror=null;this.src='/Web/image/no-image.svg'"
                             class="w-100 rounded" style="max-height: 520px; object-fit: contain;">
                    </div>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-shield-lock me-1"></i>Tài khoản quản trị không thể mua hàng.
                    </div>
                    <a href="/Web/admin/products/edit.php?id=<?= (int)$product['id'] ?>" class="btn btn-primary btn-lg w-100 mt-2">
                        <i class="bi bi-pencil-square me-2"></i>Chỉnh sửa sản phẩm này
                    </a>
                <?php else: ?>
                    <button class="btn btn-primary btn-lg w-100 mt-3"
                            type="button"
                            data-show-cart-form
                            aria-controls="add-to-cart-form"
                            aria-expanded="<?= $showCartForm ? 'true' : 'false' ?>"
                            <?= $showCartForm ? 'hidden' : '' ?>>
                        <i class="bi bi-cart-plus me-2" aria-hidden="true"></i>Thêm vào giỏ hàng
                    </button>
                    <form method="post"
                          action="/Web/product_detail.php?id=<?php echo (int)$product['id']; ?>"
                          class="mt-3 <?= $showCartForm ? '' : 'd-none' ?>"
                          id="add-to-cart-form">
                        <?php echo csrfInput(); ?>
                        <div class="mb-3" style="max-width: 150px;">
                            <label for="quantity" class="form-label">Số lượng</label>
                            <input class="form-control" type="number" id="quantity" name="quantity"
                                   value="<?= $showCartForm && isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1 ?>"
                                   min="1" max="99" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-cart-plus me-2"></i>Thêm vào giỏ hàng
                        </button>
                    </form>
                <?php endif; ?>
                <section class="mt-4" aria-labelledby="product-description-title">
                    <h2 class="h5 fw-bold" id="product-description-title">Mô tả sản phẩm</h2>
                    <p class="text-secondary mb-0" style="white-space: pre-line;"><?php echo e($product['description'] ?: 'Thông tin mô tả đang được cập nhật.'); ?></p>
                </section>
                <section class="mt-4" id="product-reviews" aria-labelledby="product-reviews-title">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h2 class="h5 fw-bold mb-0" id="product-reviews-title">Đánh giá sản phẩm</h2>
                        <span class="small text-secondary">
                            <?= number_format($purchaseCount) ?> lượt mua
                            <span class="mx-1">·</span>
                            <?= number_format((float)$reviewSummary['average_rating'], 1) ?> ★
                            (<?= (int)$reviewSummary['review_count'] ?> đánh giá)
                        </span>
                    </div>

                    <?php if ($eligibleOrders): ?>
                        <form class="card card-body bg-light mb-3" method="post" action="/Web/reviews.php" enctype="multipart/form-data">
                            <?= csrfInput() ?>
                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label" for="review-order">Đơn hàng đã nhận</label>
                                <select class="form-select" id="review-order" name="order_id" required>
                                    <?php foreach ($eligibleOrders as $eligibleOrder): ?>
                                        <option value="<?= (int)$eligibleOrder['id'] ?>">
                                            Đơn #<?= (int)$eligibleOrder['id'] ?> — <?= e(date('d/m/Y', strtotime($eligibleOrder['created_at']))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <fieldset class="mb-3">
                                <legend class="form-label fs-6">Chọn số sao</legend>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="rating"
                                                   id="review-rating-<?= $star ?>" value="<?= $star ?>"
                                                   <?= $star === 5 ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="review-rating-<?= $star ?>">
                                                <?= $star ?> ★
                                            </label>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </fieldset>
                            <div class="mb-3">
                                <label class="form-label" for="review-comment">Bình luận</label>
                                <textarea class="form-control" id="review-comment" name="comment" rows="3"
                                          maxlength="2000" required placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="review-images">Ảnh sản phẩm (không bắt buộc)</label>
                                <input class="form-control" type="file" id="review-images" name="images[]"
                                       accept="image/jpeg,image/png,image/webp" multiple>
                                <div class="form-text">Tối đa 5 ảnh JPG, PNG hoặc WEBP; mỗi ảnh không quá 5 MB.</div>
                            </div>
                            <button class="btn btn-primary align-self-start" type="submit">
                                <i class="bi bi-send me-1" aria-hidden="true"></i>Gửi đánh giá
                            </button>
                        </form>
                    <?php elseif (!isLoggedIn()): ?>
                        <p class="small text-secondary">Đăng nhập và hoàn tất đơn hàng để đánh giá sản phẩm đã mua.</p>
                    <?php elseif (!isAdmin()): ?>
                        <p class="small text-secondary">Bạn có thể đánh giá sau khi đơn hàng chứa sản phẩm này được giao.</p>
                    <?php endif; ?>

                    <?php if ($reviews): ?>
                        <div class="d-grid gap-3">
                            <?php foreach ($reviews as $review): ?>
                                <article class="border rounded p-3 bg-white">
                                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-1">
                                        <strong><?= e($review['full_name']) ?></strong>
                                        <time class="small text-secondary" datetime="<?= e($review['created_at']) ?>">
                                            <?= e(date('d/m/Y', strtotime($review['created_at']))) ?>
                                        </time>
                                    </div>
                                    <div class="text-warning mb-2" aria-label="<?= (int)$review['rating'] ?> trên 5 sao">
                                        <?= str_repeat('★', (int)$review['rating']) ?><?= str_repeat('☆', 5 - (int)$review['rating']) ?>
                                    </div>
                                    <p class="mb-0 text-secondary" style="white-space: pre-line;"><?= e($review['comment']) ?></p>
                                    <?php if ($review['images']): ?>
                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            <?php foreach ($review['images'] as $reviewImage): ?>
                                                <a href="<?= e(getStoredImageUrl($reviewImage, ['uploads/reviews/'], '/Web/image/no-image.svg')) ?>"
                                                   target="_blank" rel="noopener noreferrer">
                                                    <img class="product-review__image"
                                                         src="<?= e(getStoredImageUrl($reviewImage, ['uploads/reviews/'], '/Web/image/no-image.svg')) ?>"
                                                         alt="Ảnh đính kèm trong đánh giá"
                                                         loading="lazy">
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="small text-secondary mb-0">Sản phẩm chưa có đánh giá.</p>
                    <?php endif; ?>
                </section>
            </div>
            <div class="col-12 col-md-6">
                <?php if (!empty($product['category_name'])): ?>
                    <span class="badge text-bg-light border mb-2"><?php echo e($product['category_name']); ?></span>
                <?php endif; ?>
                <h1 class="h2 fw-bold mb-3"><?php echo e($product['name']); ?></h1>
                <div class="product-card__price fs-3 mb-3"><?php echo e(formatPrice($product['price'])); ?></div>
                <p class="text-secondary">Sản phẩm chính hãng được cung cấp bởi UTH Shop.</p>
                <?php
                $specificationRows = decodeProductSpecifications(
                    $product['specifications'],
                    $product['cpu'],
                    $product['ram']
                );
                if (trim((string)$product['brand']) !== '') {
                    array_unshift($specificationRows, ['name' => 'Thương hiệu', 'value' => $product['brand']]);
                }
                ?>
                <?php if ($specificationRows): ?>
                    <div class="product-specs mb-3">
                        <h2 class="h6 fw-bold mb-2">Thông số kỹ thuật</h2>
                        <dl class="product-specs__list mb-0">
                            <?php foreach ($specificationRows as $specification): ?>
                                <div class="product-specs__row">
                                    <dt><?= e($specification['name']) ?></dt>
                                    <dd><?= nl2br(e($specification['value'])) ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>
</section>

<script>
    const showCartFormButton = document.querySelector('[data-show-cart-form]');
    const addToCartForm = document.getElementById('add-to-cart-form');

    if (showCartFormButton && addToCartForm) {
        showCartFormButton.addEventListener('click', () => {
            showCartFormButton.hidden = true;
            showCartFormButton.setAttribute('aria-expanded', 'true');
            addToCartForm.classList.remove('d-none');
            document.getElementById('quantity').focus();
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
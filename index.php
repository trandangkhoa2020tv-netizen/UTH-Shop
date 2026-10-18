<?php
require_once 'config/database.php';

// SQL truy vấn sản phẩm nổi bật
$sqlProducts = "SELECT p.*, c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.id DESC
                LIMIT 8";
$sqlCategories = "SELECT * FROM categories ORDER BY id ASC";

$products   = $conn->query($sqlProducts)->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query($sqlCategories)->fetchAll(PDO::FETCH_ASSOC);

// 15990000 -> 15.990.000đ
function format_price($price) {
    return number_format((float)$price, 0, ',', '.') . 'đ';
}

// Trả về đường dẫn ảnh, thiếu file thì dùng ảnh mặc định
function product_image($file) {
    $path = 'image/' . $file;
    if (!empty($file) && file_exists(__DIR__ . '/' . $path)) {
        return $path;
    }
    return 'image/no-image.png';
}

// Cắt mô tả ngắn gọn
function short_text($text, $max = 70) {
    $text = trim(strip_tags((string)$text));
    if (mb_strlen($text) <= $max) return $text;
    return mb_substr($text, 0, $max) . '...';
}

$pageTitle = 'Trang chủ - UTH Shop';
require_once 'includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/home.css">

<div class="uth-home">

    <!-- Tiêu đề + slogan -->
    <section class="uth-hero uth-container">
        <h1>Welcome to <span>UTH Shop</span></h1>
        <p>Điện thoại, laptop và phụ kiện công nghệ chính hãng, giá tốt mỗi ngày.</p>
    </section>

        <!-- Dải banner -->
    <section class="uth-container">
        <a href="products.php?search=samsung" class="uth-strip">
            <img src="image/banner_samsung.png" alt="Khuyến mãi Samsung">
        </a>
    </section>

    <div class="uth-container" style="padding-bottom: 64px;">

        <!-- Danh mục -->
        <?php if (!empty($categories)): ?>
        <nav class="uth-cats">
            <a href="products.php" class="active">Tất cả</a>
            <?php foreach ($categories as $cat): ?>
                <a href="products.php?category_id=<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <!-- Danh sách sản phẩm -->
        <?php if (empty($products)): ?>
            <p class="uth-empty">Chưa có sản phẩm nào. Hãy import database.sql hoặc thêm sản phẩm trong trang quản trị.</p>
        <?php else: ?>
            <div class="uth-grid">
                <?php foreach ($products as $i => $p): ?>
                    <article class="uth-card">
                        <!-- Ảnh + huy hiệu -->
                        <a href="product_detail.php?id=<?= (int)$p['id'] ?>" class="uth-card-img">
                            <?php if ($i < 4): ?><span class="uth-badge">Mới</span><?php endif; ?>
                            <img src="<?= htmlspecialchars(product_image($p['image'] ?? '')) ?>"
                                 alt="<?= htmlspecialchars($p['name']) ?>">
                        </a>

                        <!-- Nội dung -->
                        <div class="uth-card-body">
                            <h3>
                                <a href="product_detail.php?id=<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a>
                            </h3>

                            <?php if (!empty($p['description'])): ?>
                                <p class="uth-desc"><?= htmlspecialchars(short_text($p['description'])) ?></p>
                            <?php endif; ?>

                            <div class="uth-row">
                                <span class="uth-price"><?= format_price($p['price']) ?></span>
                                <?php if (!empty($p['category_name'])): ?>
                                    <span class="uth-tag"><?= htmlspecialchars($p['category_name']) ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Nút thêm giỏ hàng: sửa action cho khớp cart.php của bạn phụ trách giỏ hàng -->
                            <form action="cart.php" method="POST">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="uth-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                    </svg>
                                    Thêm vào giỏ
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="uth-more"><a href="products.php">Xem tất cả sản phẩm</a></div>
        <?php endif; ?>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
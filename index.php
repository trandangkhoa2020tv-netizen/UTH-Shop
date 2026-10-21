<?php
require_once 'config/database.php';
require_once __DIR__ . '/includes/functions.php';

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

// Cắt mô tả ngắn gọn
function short_text($text, $max = 70) {
    $text = trim(strip_tags((string)$text));
    if (mb_strlen($text) <= $max) return $text;
    return mb_substr($text, 0, $max) . '...';
}

function homeCategoryIcon($name) {
    $name = mb_strtolower((string)$name, 'UTF-8');
    if (mb_strpos($name, 'điện thoại') !== false) return 'bi-phone';
    if (mb_strpos($name, 'laptop') !== false) return 'bi-laptop';
    if (mb_strpos($name, 'máy tính bảng') !== false) return 'bi-tablet';
    if (mb_strpos($name, 'phụ kiện') !== false) return 'bi-headphones';
    if (mb_strpos($name, 'âm thanh') !== false) return 'bi-speaker';
    if (mb_strpos($name, 'đồng hồ') !== false) return 'bi-smartwatch';
    if (mb_strpos($name, 'pc') !== false || mb_strpos($name, 'màn hình') !== false) return 'bi-pc-display';
    if (mb_strpos($name, 'tivi') !== false || mb_strpos($name, 'tv') !== false) return 'bi-tv';
    return 'bi-grid';
}

$pageTitle = 'Trang chủ - UTH Shop';
require_once 'includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/Web/assets/css/home.css?v=<?= filemtime(__DIR__ . '/assets/css/home.css') ?>">

<div class="uth-home">

    <!-- Tiêu đề + slogan -->
    <section class="uth-hero uth-container">
        <h1>Welcome to <span>UTH Shop</span></h1>
        <p>Điện thoại, laptop và phụ kiện công nghệ chính hãng, giá tốt mỗi ngày.</p>
    </section>

        <!-- Banner tự chuyển -->
    <?php
        $bannerSlides = [];
        try {
            $bannerSlides = $conn->query(
                'SELECT title, image, link_url FROM site_banner_slides
                 WHERE is_active = 1 AND image IS NOT NULL AND image <> ""
                 ORDER BY sort_order ASC, id DESC'
            )->fetchAll(PDO::FETCH_ASSOC);
            if (!$bannerSlides && (int)$conn->query('SELECT COUNT(*) FROM site_banner_slides')->fetchColumn() === 0) {
                $bannerSlides[] = [
                    'title' => 'Khuyến mãi Samsung',
                    'image' => 'image/banner_samsung.png',
                    'link_url' => '/Web/products.php?q=samsung',
                ];
            }
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '42S02') {
                throw $exception;
            }
            try {
                $legacyBanner = $conn->query(
                    'SELECT title, image, link_url FROM site_banners WHERE id = 1 AND is_active = 1'
                )->fetch(PDO::FETCH_ASSOC);
                if ($legacyBanner) {
                    $bannerSlides[] = $legacyBanner;
                }
            } catch (PDOException $legacyException) {
                if ($legacyException->getCode() !== '42S02') {
                    throw $legacyException;
                }
            }
            if (!$bannerSlides) {
                $bannerSlides[] = [
                    'title' => 'Khuyến mãi Samsung',
                    'image' => 'image/banner_samsung.png',
                    'link_url' => '/Web/products.php?q=samsung',
                ];
            }
        }

        $uniqueBannerSlides = [];
        $seenBannerImages = [];
        foreach ($bannerSlides as $slide) {
            $imageUrl = getBannerImage($slide['image'] ?? '');
            if ($imageUrl === '') {
                continue;
            }
            $imagePath = __DIR__ . '/' . ltrim((string)$slide['image'], '/');
            $imageHash = is_file($imagePath) ? hash_file('sha256', $imagePath) : false;
            $imageKey = $imageHash !== false ? $imageHash : $imageUrl;
            if (isset($seenBannerImages[$imageKey])) {
                continue;
            }
            $seenBannerImages[$imageKey] = true;
            $slide['image_url'] = $imageUrl;
            $uniqueBannerSlides[] = $slide;
        }
        $bannerSlides = $uniqueBannerSlides;
        ?>
        <?php if ($bannerSlides): ?>
        <section class="uth-container">
            <div class="uth-carousel banner-container" data-banner-carousel role="region" aria-label="Banner quảng cáo" aria-roledescription="carousel">
                <div class="uth-carousel__viewport">
                    <div class="uth-carousel__track">
                        <?php foreach ($bannerSlides as $index => $slide): ?>
                            <div class="uth-carousel__slide" role="group" aria-roledescription="slide"
                                 aria-label="<?= ($index + 1) ?> / <?= count($bannerSlides) ?>" <?= $index > 0 ? 'aria-hidden="true" inert' : '' ?>>
                                <a href="<?= htmlspecialchars($slide['link_url'] ?: '/Web/products.php', ENT_QUOTES, 'UTF-8') ?>" class="uth-strip">
                                    <img src="<?= htmlspecialchars($slide['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                                         alt="<?= htmlspecialchars($slide['title'], ENT_QUOTES, 'UTF-8') ?>"
                                         <?= $index > 0 ? 'loading="lazy"' : 'fetchpriority="high"' ?>
                                         onerror="this.onerror=null;this.src='/Web/image/no-image.svg'">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if (count($bannerSlides) > 1): ?>
                    <button class="uth-carousel__arrow uth-carousel__arrow--previous" type="button" data-banner-previous aria-label="Banner trước">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button class="uth-carousel__arrow uth-carousel__arrow--next" type="button" data-banner-next aria-label="Banner tiếp theo">
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                    <div class="uth-carousel__indicators" aria-label="Chọn banner">
                        <?php foreach ($bannerSlides as $index => $slide): ?>
                            <button type="button" data-banner-indicator="<?= $index ?>"
                                    aria-label="Hiển thị banner <?= $index + 1 ?>"
                                    aria-current="<?= $index === 0 ? 'true' : 'false' ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

    <div class="uth-container" style="padding-bottom: 64px;">

        <section class="uth-catalog-panel" aria-label="Danh mục và sản phẩm">
        <!-- Danh mục -->
        <?php if (!empty($categories)): ?>
        <nav class="uth-cats" aria-label="Danh mục sản phẩm">
            <a href="products.php" class="uth-cat-link active">
                <span class="uth-cat-icon"><i class="bi bi-grid" aria-hidden="true"></i></span>
                <span>Tất cả</span>
            </a>
            <?php foreach ($categories as $cat): ?>
                <a class="uth-cat-link" href="products.php?category=<?= (int)$cat['id'] ?>">
                    <span class="uth-cat-icon"><i class="bi <?= htmlspecialchars(homeCategoryIcon($cat['name']), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
                    <span><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
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
                            <img src="<?= htmlspecialchars(getProductImage($p['image'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>"
                                 onerror="this.onerror=null;this.src='/Web/image/no-image.svg'">
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

                            <?php if (isAdmin()): ?>
                                <a class="uth-btn text-decoration-none" href="/Web/admin/products/edit.php?id=<?= (int)$p['id'] ?>">
                                    <i class="bi bi-pencil-square me-1"></i>Chỉnh sửa sản phẩm
                                </a>
                            <?php else: ?>
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
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="uth-more"><a href="products.php">Xem tất cả sản phẩm</a></div>
        <?php endif; ?>

        </section>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
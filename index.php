<?php
require_once 'config/database.php';

$products   = [];
$categories = [];

$sqlProducts = "SELECT p.*, c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.id DESC
                LIMIT 8";
$sqlCategories = "SELECT * FROM categories ORDER BY id ASC";

if (isset($pdo)) {
    // --- PDO ---
    $products   = $pdo->query($sqlProducts)->fetchAll(PDO::FETCH_ASSOC);
    $categories = $pdo->query($sqlCategories)->fetchAll(PDO::FETCH_ASSOC);
} elseif (isset($conn)) {
    // --- MySQLi ---
    $rs = $conn->query($sqlProducts);
    while ($row = $rs->fetch_assoc()) { $products[] = $row; }
    $rs = $conn->query($sqlCategories);
    while ($row = $rs->fetch_assoc()) { $categories[] = $row; }
}

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

<!-- Nếu header.php chưa nhúng Tailwind thì bỏ comment dòng dưới -->
<!-- <script src="https://cdn.tailwindcss.com"></script> -->

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

<main class="home-wrap max-w-7xl mx-auto px-4 pt-12 pb-16">

    <!-- Tiêu Đề -->
    <section class="text-center mb-10">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
            Chào mừng đến với <span class="home-title">UTH Shop</span>
        </h1>
        <p class="text-slate-400 text-base md:text-lg">
            Điện thoại, laptop và phụ kiện công nghệ chính hãng, giá tốt mỗi ngày.
        </p>
    </section>

    <!-- Banner -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
        <a href="products.php?search=iphone" class="block overflow-hidden rounded-2xl border border-slate-800">
            <img src="image/banner_apple.png" alt="Khuyến mãi Apple"
                 class="w-full h-44 md:h-60 object-cover hover:scale-105 transition-transform duration-300">
        </a>
        <a href="products.php?search=samsung" class="block overflow-hidden rounded-2xl border border-slate-800">
            <img src="image/banner_samsung.png" alt="Khuyến mãi Samsung"
                 class="w-full h-44 md:h-60 object-cover hover:scale-105 transition-transform duration-300">
        </a>
    </section>

    <!-- Danh Mục -->
    <?php if (!empty($categories)): ?>
    <section class="flex flex-wrap items-center justify-center gap-2 mb-8">
        <a href="products.php" class="px-4 py-2 rounded-full text-sm bg-amber-600 text-white">Tất cả</a>
        <?php foreach ($categories as $cat): ?>
            <a href="products.php?category_id=<?= (int)$cat['id'] ?>"
               class="px-4 py-2 rounded-full text-sm border border-slate-700 text-slate-300 hover:border-amber-500 hover:text-amber-400 transition-colors">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <!-- Danh sách sản phẩm -->
    <section>
        <?php if (empty($products)): ?>
            <p class="text-slate-500 py-16 text-center">
                Chưa có sản phẩm nào. Hãy import database.sql hoặc thêm sản phẩm trong trang quản trị.
            </p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <?php foreach ($products as $i => $p): ?>
                    <article class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden flex flex-col hover:border-slate-600 transition-colors">

                        <!-- Ảnh + huy hiệu -->
                        <a href="product_detail.php?id=<?= (int)$p['id'] ?>" class="relative block bg-slate-100">
                            <?php if ($i < 4): ?>
                                <span class="absolute top-3 left-3 z-10 bg-teal-500 text-white text-xs font-medium px-2.5 py-1 rounded-md">Mới</span>
                            <?php endif; ?>
                            <img src="<?= htmlspecialchars(product_image($p['image'] ?? '')) ?>"
                                 alt="<?= htmlspecialchars($p['name']) ?>"
                                 class="w-full aspect-square object-contain p-6">
                        </a>

                        <!-- Nội dung -->
                        <div class="p-4 flex flex-col flex-1">
                            <h3 class="font-semibold text-white mb-1">
                                <a href="product_detail.php?id=<?= (int)$p['id'] ?>" class="hover:text-amber-400">
                                    <?= htmlspecialchars($p['name']) ?>
                                </a>
                            </h3>

                            <?php if (!empty($p['description'])): ?>
                                <p class="text-sm text-slate-400 mb-3">
                                    <?= htmlspecialchars(short_text($p['description'])) ?>
                                </p>
                            <?php endif; ?>

                            <div class="flex items-center justify-between mt-auto mb-4">
                                <span class="text-xl font-bold text-white"><?= format_price($p['price']) ?></span>
                                <?php if (!empty($p['category_name'])): ?>
                                    <span class="text-xs px-2.5 py-1 rounded-md bg-amber-500/15 text-amber-400">
                                        <?= htmlspecialchars($p['category_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Nút thêm giỏ hàng: sửa action cho khớp cart.php của bạn phụ trách giỏ hàng -->
                            <form action="cart.php" method="POST">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                <button type="submit"
                                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-medium transition-colors">
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

            <div class="text-center mt-10">
                <a href="products.php"
                   class="inline-block px-6 py-2.5 rounded-lg border border-slate-700 text-slate-300 hover:border-amber-500 hover:text-amber-400 transition-colors">
                    Xem tất cả sản phẩm
                </a>
            </div>
        <?php endif; ?>
    </section>

</main>

<?php require_once 'includes/footer.php'; ?>
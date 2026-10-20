<?php
function adminEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function adminHeader($title) {
    ?>
    <!doctype html>
    <html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= adminEscape($title) ?> | UTH Shop Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="/Web/assets/css/style.css" rel="stylesheet">
        <style>
            body.admin-body { background: #f1f5f9; min-height: 100vh; display: block; }
            .admin-topbar { background: #111827; }
            .admin-main { max-width: 1200px; }
            .admin-image-preview { width: 112px; height: 90px; object-fit: contain; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .5rem; }
            .admin-banner-preview { width: 100%; aspect-ratio: 23 / 10; overflow: hidden; border-radius: .5rem; background: #f8fafc; }
            .admin-banner-preview img { display: block; width: 100%; height: 100%; object-fit: cover; }
        </style>
    </head>
    <body class="admin-body">
        <nav class="navbar navbar-expand-lg navbar-dark admin-topbar">
            <div class="container admin-main">
                <a class="navbar-brand fw-bold" href="/Web/admin/index.php">UTH Shop <span class="text-info">Admin</span></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-label="Mở menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="adminNav">
                    <div class="navbar-nav me-auto">
                        <a class="nav-link" href="/Web/admin/products/index.php">Sản phẩm</a>
                        <a class="nav-link" href="/Web/admin/banners/index.php">Banner</a>
                        <a class="nav-link" href="/Web/index.php" target="_blank" rel="noopener">Xem cửa hàng</a>
                    </div>
                    <a class="btn btn-sm btn-outline-light" href="/Web/admin/logout.php">Đăng xuất</a>
                </div>
            </div>
        </nav>
        <main class="container admin-main py-4">
            <h1 class="h3 fw-bold mb-4"><?= adminEscape($title) ?></h1>
    <?php
}

function adminFooter() {
    ?>
        </main>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="/Web/assets/js/main.js?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/js/main.js') ?>"></script>
    </body>
    </html>
    <?php
}

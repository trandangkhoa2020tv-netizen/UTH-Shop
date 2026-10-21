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
            .admin-topbar { border-bottom: 1px solid var(--gray-200); padding: .8rem 0; }
            .admin-main { max-width: 1240px; }
            .admin-topbar .navbar-brand { margin-right: 1.25rem; }
            .admin-brand-logo { width: 120px; height: 50px; object-fit: cover; object-position: center; }
            .admin-topbar .navbar-nav { gap: .15rem; }
            .admin-topbar .nav-link { display: inline-flex; align-items: center; gap: .35rem; white-space: nowrap; }
            .admin-topbar .nav-link > i { font-size: .95rem; }
            .admin-logout { white-space: nowrap; }
            .admin-page-intro { color: #64748b; margin-top: -.9rem; margin-bottom: 1.25rem; }
            .admin-body main > h1 { padding-bottom: .85rem; border-bottom: 1px solid #e2e8f0; letter-spacing: -.025em; }
            .admin-stat-card { border: 0; border-radius: 1rem; box-shadow: 0 4px 16px rgba(15,23,42,.06); }
            .admin-stat-icon { display: grid; width: 2.75rem; height: 2.75rem; place-items: center; border-radius: .8rem; background: #eff6ff; color: #2563eb; font-size: 1.2rem; }
            .admin-table-card { overflow: hidden; border: 1px solid #e2e8f0; border-radius: .9rem; box-shadow: 0 3px 12px rgba(15,23,42,.04); }
            .admin-table-card .table { margin-bottom: 0; }
            .admin-table-card th { white-space: nowrap; }
            .admin-table-card .table > :not(caption) > * > * { padding-top: .85rem; padding-bottom: .85rem; }
            .admin-summary-card { border: 1px solid #e2e8f0; border-radius: .85rem; box-shadow: 0 3px 12px rgba(15,23,42,.035); }
            .admin-summary-label { color: #64748b; font-size: .84rem; }
            .admin-summary-value { color: #0f172a; font-size: 1.45rem; font-weight: 700; }
            .admin-table-wrap { overflow-x: auto; }
            .admin-table-wrap > .table { min-width: 780px; }
            .admin-contact { min-width: 185px; }
            .admin-row-actions { white-space: nowrap; }
            .admin-cell-wrap { max-width: 260px; overflow-wrap: anywhere; white-space: normal; }
            .admin-image-preview { width: 112px; height: 90px; object-fit: contain; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .5rem; }
            .admin-banner-preview { width: 100%; aspect-ratio: 23 / 10; overflow: hidden; border-radius: .5rem; background: #f8fafc; }
            .admin-banner-preview img { display: block; width: 100%; height: 100%; object-fit: cover; }
            @media (max-width: 991.98px) {
                .admin-topbar .navbar-collapse { padding: .75rem 0 .5rem; }
                .admin-topbar .navbar-nav { gap: .2rem; padding-bottom: .5rem; }
                .admin-topbar .nav-link { width: 100%; margin: 0; }
                .admin-topbar .admin-logout { margin-top: .35rem; }
            }
            @media (max-width: 575.98px) {
                .admin-main { padding-left: 1rem; padding-right: 1rem; }
                .admin-topbar .navbar-brand { margin-right: 0; }
                .admin-brand-logo { width: 105px; height: 44px; }
                .admin-body main { padding-top: 1.25rem !important; }
                .admin-body main > h1 { font-size: 1.4rem; margin-bottom: 1.25rem !important; }
                .admin-table-card .card-body { padding: 1rem; }
            }
        </style>
    </head>
    <body class="admin-body">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top admin-topbar" id="main-navbar">
            <div class="container admin-main">
                <a class="navbar-brand d-flex align-items-center" href="/Web/admin/index.php" aria-label="UTH Shop - Quản trị">
                    <img class="admin-brand-logo" src="/Web/image/logouthh.jpg" alt="UTH Shop">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-label="Mở menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="adminNav">
                    <div class="navbar-nav me-auto">
                        <?php
                        $adminPath = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
                        $adminLinks = [
                            ['/Web/admin/index.php', 'Tổng quan', '/admin/index.php', 'bi-speedometer2'],
                            ['/Web/admin/products/index.php', 'Sản phẩm', '/admin/products/', 'bi-box-seam'],
                            ['/Web/admin/categories/index.php', 'Danh mục', '/admin/categories/', 'bi-grid'],
                            ['/Web/admin/orders/index.php', 'Đơn hàng', '/admin/orders/', 'bi-receipt'],
                            ['/Web/admin/users/index.php', 'Người dùng', '/admin/users/', 'bi-people'],
                            ['/Web/admin/banners/index.php', 'Banner', '/admin/banners/', 'bi-images'],
                        ];
                        foreach ($adminLinks as [$href, $label, $activePath, $icon]):
                            $isActive = $activePath === '/admin/index.php'
                                ? $adminPath === '/Web/admin/index.php'
                                : strpos($adminPath, $activePath) === 0;
                        ?>
                            <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="<?= adminEscape($href) ?>"><i class="bi <?= adminEscape($icon) ?>"></i><?= adminEscape($label) ?></a>
                        <?php endforeach; ?>
                        <a class="nav-link admin-store-link" href="/Web/index.php" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i>Xem cửa hàng</a>
                    </div>
                    <a class="btn btn-primary btn-sm px-3 admin-logout" href="/Web/admin/logout.php"><i class="bi bi-box-arrow-right me-1"></i>Đăng xuất</a>
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

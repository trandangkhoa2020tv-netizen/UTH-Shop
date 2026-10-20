<?php
// =============================================
// FILE: includes/header.php
// Phần đầu website: <head>, navbar, thông báo flash
// Include ở đầu mỗi trang khách hàng
// =============================================

// Khởi động session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require các file cần thiết (dùng đường dẫn tuyệt đối từ thư mục gốc project)
$root = dirname(__DIR__); // Thư mục gốc: /Web/
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/flash.php';

// Giá trị mặc định cho <title> (trang gọi có thể override $pageTitle trước khi include)
if (!isset($pageTitle)) {
    $pageTitle = 'UTH Shop';
}

// Số lượng trong giỏ hàng
$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UTH Shop – Website mua sắm trực tuyến uy tín, giá tốt.">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | UTH Shop</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS chính -->
    <link href="/Web/assets/css/style.css?v=<?= filemtime($root . '/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<!-- ===================== NAVBAR ===================== -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top" id="main-navbar">
    <div class="container">

        <!-- LOGO -->
        <a class="navbar-brand d-flex align-items-center" href="/Web/index.php" aria-label="UTH - Trang chủ">
            <img class="navbar-brand__logo" src="/Web/image/logouthh.jpg" alt="UTH">
        </a>

        <!-- TOGGLE (mobile) -->
        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Mở menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- MENU -->
        <div class="collapse navbar-collapse" id="navbarMain">

            <!-- Navigation links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/Web/index.php">
                        <i class="bi bi-house me-1"></i>Trang chủ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/Web/products.php">
                        <i class="bi bi-grid me-1"></i>Sản phẩm
                    </a>
                </li>
            </ul>

            <!-- Search form -->
            <form class="d-flex me-3" action="/Web/products.php" method="GET" role="search" id="search-form">
                <div class="input-group">
                    <input class="form-control form-control-sm border-end-0"
                           type="search" name="q"
                           placeholder="Tìm sản phẩm..."
                           value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           aria-label="Tìm kiếm sản phẩm" id="search-input">
                    <button class="btn btn-outline-primary btn-sm" type="submit" id="btn-search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- Right: Cart + User -->
            <ul class="navbar-nav align-items-lg-center gap-1">

                <!-- Giỏ hàng -->
                <li class="nav-item">
                    <a class="nav-link position-relative" href="/Web/cart.php" id="cart-link" title="Giỏ hàng">
                        <i class="bi bi-cart3 fs-5"></i>
                        <?php if ($cartCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-badge">
                            <?= $cartCount ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>

                <?php if (isLoggedIn()): ?>
                <!-- Đã đăng nhập -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#"
                       id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span class="d-none d-lg-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Tài khoản', ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                        <?php if (isAdmin()): ?>
                        <li>
                            <a class="dropdown-item" href="/Web/admin/index.php">
                                <i class="bi bi-speedometer2 me-2"></i>Quản trị cửa hàng
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item" href="/Web/orders.php" id="link-orders">
                                <i class="bi bi-bag-check me-2"></i>Đơn hàng của tôi
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="/Web/logout.php" id="link-logout">
                                <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                            </a>
                        </li>
                    </ul>
                </li>
                <?php else: ?>
                <!-- Chưa đăng nhập -->
                <li class="nav-item">
                    <a class="nav-link" href="/Web/login.php" id="link-login">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-primary btn-sm px-3" href="/Web/register.php" id="link-register">
                        Đăng ký
                    </a>
                </li>
                <?php endif; ?>

            </ul>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
</nav>
<!-- =================== END NAVBAR =================== -->

<!-- =================== FLASH MESSAGE =================== -->
<div class="container mt-3" id="flash-container">
    <?php showFlash(); ?>
</div>
<!-- ================ END FLASH MESSAGE ================ -->

<!-- =================== MAIN CONTENT WRAP =================== -->
<main class="main-content">

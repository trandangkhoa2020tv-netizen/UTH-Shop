<?php
// =============================================
// FILE: admin/_layout.php
// Layout dùng chung cho tất cả trang admin
// Gọi: include __DIR__ . '/_layout.php'; với $pageTitle đã set trước
// =============================================

// Đảm bảo auth
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/flash.php';

requireAdmin();

if (!isset($pageTitle)) $pageTitle = 'Admin';
$currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | UTH Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; background: #f4f6f9; }
        #sidebar { width: 250px; min-height: 100vh; background: #1a1a2e; transition: all .3s; }
        #sidebar .sidebar-brand { background: #16213e; padding: 1.2rem 1rem; }
        #sidebar .nav-link { color: rgba(255,255,255,.7); border-radius: 8px; margin: 2px 8px; padding: .6rem 1rem; }
        #sidebar .nav-link:hover, #sidebar .nav-link.active { background: #0d6efd; color: #fff; }
        #sidebar .nav-section { color: rgba(255,255,255,.35); font-size: .7rem; text-transform: uppercase;
            letter-spacing: .1em; padding: 1rem 1.2rem .3rem; }
        .main-wrapper { flex: 1; overflow-x: hidden; }
        .topbar { background: #fff; border-bottom: 1px solid #e9ecef; padding: .75rem 1.5rem; }
        .content-area { padding: 1.5rem; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
        .card-header { background: #fff; border-bottom: 1px solid #f0f0f0; font-weight: 600; border-radius: 12px 12px 0 0 !important; }
        .stat-card { border-left: 4px solid; }
        .table th { background: #f8f9fa; font-size: .85rem; font-weight: 600; }
        .badge-status { font-size: .75rem; padding: .35em .7em; }
    </style>
</head>
<body>
<div class="d-flex">

<!-- ===== SIDEBAR ===== -->
<nav id="sidebar" class="d-flex flex-column">
    <div class="sidebar-brand text-center">
        <a href="/Web/admin/index.php" class="text-decoration-none text-white d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-shop fs-4 text-primary"></i>
            <span class="fw-bold fs-5">UTH <span class="text-primary">Admin</span></span>
        </a>
    </div>

    <nav class="flex-column mt-2 pb-3">
        <span class="nav-section">Tổng quan</span>
        <a href="/Web/admin/index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' && dirname($_SERVER['PHP_SELF']) !== '/' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>

        <span class="nav-section">Quản lý</span>
        <a href="/Web/admin/categories/index.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], 'categories') ? 'active' : '' ?>">
            <i class="bi bi-tags me-2"></i>Danh mục
        </a>
        <a href="/Web/admin/products/index.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], 'products') ? 'active' : '' ?>">
            <i class="bi bi-box-seam me-2"></i>Sản phẩm
        </a>
        <a href="/Web/admin/orders/index.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], 'orders') ? 'active' : '' ?>">
            <i class="bi bi-bag-check me-2"></i>Đơn hàng
        </a>
        <a href="/Web/admin/users/index.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], 'users') ? 'active' : '' ?>">
            <i class="bi bi-people me-2"></i>Người dùng
        </a>

        <span class="nav-section">Hệ thống</span>
        <a href="/Web/index.php" class="nav-link" target="_blank">
            <i class="bi bi-globe me-2"></i>Xem website
        </a>
        <a href="/Web/admin/logout.php" class="nav-link text-danger">
            <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
        </a>
    </nav>
</nav>

<!-- ===== MAIN ===== -->
<div class="main-wrapper d-flex flex-column">
    <!-- Topbar -->
    <div class="topbar d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold text-secondary"><?= htmlspecialchars($pageTitle) ?></h6>
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-circle text-primary fs-5"></i>
            <span class="small fw-semibold"><?= htmlspecialchars($currentUser['name']) ?></span>
        </div>
    </div>

    <!-- Flash -->
    <div class="px-4 pt-3">
        <?php showFlash(); ?>
    </div>

    <!-- Content -->
    <div class="content-area flex-grow-1">


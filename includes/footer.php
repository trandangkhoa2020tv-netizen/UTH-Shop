<?php
// =============================================
// FILE: includes/footer.php
// Phần chân website: footer HTML + scripts
// Include ở cuối mỗi trang khách hàng
// =============================================
?>

</main><!-- /.main-content -->

<!-- ===================== FOOTER ===================== -->
<footer class="site-footer bg-dark text-white mt-5 pt-5 pb-3">
    <div class="container">
        <div class="row g-4">

            <!-- Cột 1: Giới thiệu -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-shop fs-3 text-primary"></i>
                    <span class="fs-4 fw-bold">UTH <span class="text-primary">Shop</span></span>
                </div>
                <p class="footer-description small lh-lg">
                    Website mua sắm trực tuyến được xây dựng bởi sinh viên Trường Đại học Giao thông Vận tải TP.HCM (UTH).
                    Cung cấp các sản phẩm chất lượng với giá cả hợp lý.
                </p>
                <!-- Mạng xã hội -->
                <div class="social-links d-flex gap-3 mt-3">
                    <a href="#" class="fs-5 social-icon" title="Facebook" id="footer-facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" class="fs-5 social-icon" title="Instagram" id="footer-instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="#" class="fs-5 social-icon" title="YouTube" id="footer-youtube">
                        <i class="bi bi-youtube"></i>
                    </a>
                    <a href="#" class="fs-5 social-icon" title="GitHub" id="footer-github">
                        <i class="bi bi-github"></i>
                    </a>
                </div>
            </div>

            <!-- Cột 2: Liên kết nhanh -->
            <div class="col-lg-2 col-md-6">
                <h6 class="footer-heading text-uppercase fw-semibold mb-3">Liên kết</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="/Web/index.php" id="footer-link-home">Trang chủ</a></li>
                    <li><a href="/Web/products.php" id="footer-link-products">Sản phẩm</a></li>
                    <li><a href="/Web/cart.php" id="footer-link-cart">Giỏ hàng</a></li>
                    <li><a href="/Web/orders.php" id="footer-link-orders">Đơn hàng</a></li>
                    <li><a href="/Web/login.php" id="footer-link-login">Đăng nhập</a></li>
                    <li><a href="/Web/register.php" id="footer-link-register">Đăng ký</a></li>
                </ul>
            </div>

            <!-- Cột 3: Danh mục (placeholder — sẽ lấy động từ DB) -->
            <div class="col-lg-2 col-md-6">
                <h6 class="footer-heading text-uppercase fw-semibold mb-3">Danh mục</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="/Web/products.php?category=1" id="footer-cat-1">Điện thoại</a></li>
                    <li><a href="/Web/products.php?category=2" id="footer-cat-2">Máy tính bảng</a></li>
                    <li><a href="/Web/products.php?category=3" id="footer-cat-3">Laptop</a></li>
                    <li><a href="/Web/products.php?category=4" id="footer-cat-4">Phụ kiện</a></li>
                </ul>
            </div>

            <!-- Cột 4: Liên hệ -->
            <div class="col-lg-4 col-md-6">
                <h6 class="footer-heading text-uppercase fw-semibold mb-3">Liên hệ</h6>
                <ul class="list-unstyled footer-contact">
                    <li class="d-flex align-items-start gap-2 mb-3">
                        <span class="footer-contact__icon"><i class="bi bi-geo-alt-fill"></i></span>
                        <span class="footer-contact__content">
                            <span class="footer-contact__label">Địa chỉ</span>
                            <span class="footer-contact__value">Số 2 Võ Oanh P. Thạnh Mỹ Tây TP.Hồ Chí Minh </span>
                        </span>
                    </li>
                    <li class="d-flex align-items-start gap-2 mb-3">
                        <span class="footer-contact__icon"><i class="bi bi-telephone-fill"></i></span>
                        <span class="footer-contact__content">
                            <span class="footer-contact__label">Điện thoại</span>
                            <a href="tel:+84900000000" class="footer-contact__value" id="footer-phone">(+84) 900 000 000</a>
                        </span>
                    </li>
                    <li class="d-flex align-items-start gap-2 mb-3">
                        <span class="footer-contact__icon"><i class="bi bi-envelope-fill"></i></span>
                        <span class="footer-contact__content">
                            <span class="footer-contact__label">Email</span>
                            <a href="mailto:contact@uthshop.vn" class="footer-contact__value" id="footer-email">contact@uthshop.vn</a>
                        </span>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <span class="footer-contact__icon"><i class="bi bi-clock-fill"></i></span>
                        <span class="footer-contact__content">
                            <span class="footer-contact__label">Giờ làm việc</span>
                            <span class="footer-contact__value">Thứ 2 – Thứ 7: 8:00 – 21:00</span>
                        </span>
                    </li>
                </ul>
            </div>

        </div><!-- /.row -->

        <!-- Divider -->
        <hr class="mt-4 mb-3 border-secondary">

        <!-- Bottom bar -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="footer-meta small mb-0">
                    &copy; <?= date('Y') ?> <strong>UTH Shop</strong>. Dự án học tập – Môn Lập trình Web.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="footer-meta small mb-0">
                    Trường ĐH Giao thông Vận tải TP.HCM &nbsp;|&nbsp;
                    Được xây dựng bằng PHP &amp; MySQL
                </p>
            </div>
        </div>

    </div><!-- /.container -->
</footer>
<!-- =================== END FOOTER =================== -->

<!-- Back to Top button -->
<button class="btn btn-primary btn-back-to-top" id="btn-back-to-top" title="Lên đầu trang">
    <i class="bi bi-chevron-up"></i>
</button>

<!-- Bootstrap 5 JS Bundle (bao gồm Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- JavaScript chính -->
<script src="/Web/assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>

</body>
</html>

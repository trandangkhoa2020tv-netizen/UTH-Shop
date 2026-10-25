<?php
// =============================================
// FILE: includes/footer.php
// Phần chân website: footer HTML + scripts
// Include ở cuối mỗi trang khách hàng
// =============================================
?>

</main><!-- /.main-content -->

<!-- ===================== FOOTER ===================== -->
<footer class="site-footer mt-4">
    <div class="container">
        <div class="footer-main">

            <section class="footer-column footer-about" aria-label="Giới thiệu UTH Shop">
                <div class="footer-brand">
                    <img class="footer-brand__logo" src="/Web/image/logouthh.jpg" alt="UTH">
                </div>
                <p class="footer-description">
                    Website mua sắm trực tuyến được xây dựng bởi sinh viên Trường Đại học Giao thông Vận tải TP.HCM (UTH).
                    Cung cấp các sản phẩm chất lượng với giá cả hợp lý.
                </p>
            </section>

            <section class="footer-column">
                <h6 class="footer-heading">Địa chỉ &amp; điện thoại</h6>
                <ul class="list-unstyled footer-contact">
                    <li class="footer-contact__item">
                        <span class="footer-contact__icon"><i class="bi bi-geo-alt-fill"></i></span>
                        <span class="footer-contact__content">
                            <span class="footer-contact__label">Địa chỉ</span>
                            <span class="footer-contact__value">Số 2 Võ Oanh P. Thạnh Mỹ Tây TP.Hồ Chí Minh </span>
                        </span>
                    </li>
                    <li class="footer-contact__item">
                        <span class="footer-contact__icon"><i class="bi bi-telephone-fill"></i></span>
                        <span class="footer-contact__content">
                            <span class="footer-contact__label">Điện thoại</span>
                            <a href="tel:+84900000000" class="footer-contact__value" id="footer-phone">0352415850</a>
                        </span>
                    </li>
                </ul>
            </section>

            <section class="footer-column">
                <h6 class="footer-heading">Liên hệ</h6>
                <ul class="list-unstyled footer-contact">
                    <li class="footer-contact__item">
                        <span class="footer-contact__icon"><i class="bi bi-envelope-fill"></i></span>
                        <span class="footer-contact__content">
                            <span class="footer-contact__label">Email</span>
                            <a href="mailto:contact@uthshop.vn" class="footer-contact__value" id="footer-email">contact@uthshop.vn</a>
                        </span>
                    </li>
                    <li class="footer-contact__item">
                        <span class="footer-contact__icon"><i class="bi bi-clock-fill"></i></span>
                        <span class="footer-contact__content">
                            <span class="footer-contact__label">Giờ làm việc</span>
                            <span class="footer-contact__value">Thứ 2 – Thứ 7: 8:00 – 21:00</span>
                        </span>
                    </li>
                </ul>
            </section>

        </div>

        <!-- Divider -->
        <hr class="footer-divider">

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

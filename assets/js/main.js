// =============================================
// FILE: assets/js/main.js
// JavaScript chính cho toàn bộ website UTH Shop
// =============================================

'use strict';

document.addEventListener('DOMContentLoaded', function () {

    // ----------------------------------------
    // 1. BACK TO TOP BUTTON
    // ----------------------------------------
    const btnTop = document.getElementById('btn-back-to-top');

    if (btnTop) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 300) {
                btnTop.classList.add('visible');
            } else {
                btnTop.classList.remove('visible');
            }
        });

        btnTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ----------------------------------------
    // 2. ACTIVE NAV LINK (highlight menu hiện tại)
    // ----------------------------------------
    const currentPath = window.location.pathname;
    document.querySelectorAll('#main-navbar .nav-link').forEach(function (link) {
        const href = link.getAttribute('href') || '';
        if (href && currentPath.endsWith(href.split('/Web/')[1] || '')) {
            link.classList.add('active');
        }
    });

    // ----------------------------------------
    // 3. QUANTITY CONTROL (giỏ hàng)
    // ----------------------------------------
    document.querySelectorAll('.quantity-control').forEach(function (ctrl) {
        const input = ctrl.querySelector('input[type="number"]');
        const btnMinus = ctrl.querySelector('[data-action="minus"]');
        const btnPlus  = ctrl.querySelector('[data-action="plus"]');

        if (!input) return;

        const min = parseInt(input.min) || 1;
        const max = parseInt(input.max) || 999;

        if (btnMinus) {
            btnMinus.addEventListener('click', function () {
                let val = parseInt(input.value) || 1;
                if (val > min) {
                    input.value = val - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }

        if (btnPlus) {
            btnPlus.addEventListener('click', function () {
                let val = parseInt(input.value) || 1;
                if (val < max) {
                    input.value = val + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }

        // Clamp khi nhập tay
        input.addEventListener('change', function () {
            let val = parseInt(this.value) || min;
            if (val < min) val = min;
            if (val > max) val = max;
            this.value = val;
        });
    });

    // ----------------------------------------
    // 4. CONFIRM DELETE (xác nhận trước khi xóa)
    // ----------------------------------------
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = this.getAttribute('data-confirm') || 'Bạn có chắc muốn xóa không?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // ----------------------------------------
    // 5. FLASH MESSAGE AUTO-HIDE
    // (Backup JS phòng khi PHP không render script)
    // ----------------------------------------
    setTimeout(function () {
        document.querySelectorAll('.flash-message').forEach(function (el) {
            el.classList.remove('show');
            setTimeout(function () {
                if (el.parentNode) el.parentNode.removeChild(el);
            }, 350);
        });
    }, 4000);

    // ----------------------------------------
    // 6. PREVIEW ẢNH KHI UPLOAD (form sản phẩm)
    // ----------------------------------------
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
    fileInputs.forEach(function (input) {
        const previewId = input.getAttribute('data-preview');
        const preview   = document.getElementById(previewId);
        if (!preview) return;

        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                alert('Vui lòng chọn file ảnh!');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    });

    // ----------------------------------------
    // 7. NAVBAR STICKY SHADOW
    // ----------------------------------------
    const navbar = document.getElementById('main-navbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 10) {
                navbar.classList.add('shadow');
            } else {
                navbar.classList.remove('shadow');
            }
        });
    }

    // ----------------------------------------
    // 8. TOOLTIP (Bootstrap 5)
    // ----------------------------------------
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // ----------------------------------------
    // 9. SEARCH INPUT — Focus animation
    // ----------------------------------------
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('focus', function () {
            this.parentElement.classList.add('shadow-sm');
        });
        searchInput.addEventListener('blur', function () {
            this.parentElement.classList.remove('shadow-sm');
        });
    }

    // ----------------------------------------
    // 10. FORMAT GIÁ INPUT (admin — tự thêm dấu chấm)
    // ----------------------------------------
    document.querySelectorAll('input[data-format="price"]').forEach(function (input) {
        input.addEventListener('input', function () {
            let raw = this.value.replace(/\D/g, '');
            this.value = raw ? parseInt(raw, 10).toLocaleString('vi-VN') : '';
        });
    });

}); // END DOMContentLoaded

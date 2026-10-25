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
        if (href && href !== '#' && href.charAt(0) !== '#' &&
            new URL(href, window.location.href).pathname === currentPath) {
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
    document.querySelectorAll('[data-search-input]').forEach(function (searchInput) {
        searchInput.addEventListener('focus', function () {
            this.parentElement.classList.add('shadow-sm');
        });
        searchInput.addEventListener('blur', function () {
            this.parentElement.classList.remove('shadow-sm');
        });
    });

    // ----------------------------------------
    // 11. HOMEPAGE BANNER CAROUSEL
    // ----------------------------------------
    document.querySelectorAll('[data-banner-carousel]').forEach(function (carousel) {
        const track = carousel.querySelector('.uth-carousel__track');
        const slides = Array.from(carousel.querySelectorAll('.uth-carousel__slide'));
        const indicators = Array.from(carousel.querySelectorAll('[data-banner-indicator]'));
        const previousButton = carousel.querySelector('[data-banner-previous]');
        const nextButton = carousel.querySelector('[data-banner-next]');

        if (!track || slides.length < 2) return;

        let currentIndex = 0;
        let timer;

        function showSlide(index) {
            currentIndex = (index + slides.length) % slides.length;
            track.style.transform = 'translateX(' + (currentIndex * 100) + '%)';
            slides.forEach(function (slide, slideIndex) {
                const isActive = slideIndex === currentIndex;
                slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                slide.inert = !isActive;
            });
            indicators.forEach(function (indicator, indicatorIndex) {
                indicator.setAttribute('aria-current', indicatorIndex === currentIndex ? 'true' : 'false');
            });
        }

        function stopAutoplay() {
            window.clearInterval(timer);
        }

        function startAutoplay() {
            stopAutoplay();
            if (!document.hidden && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                timer = window.setInterval(function () {
                    showSlide(currentIndex + 1);
                }, 5000);
            }
        }

        if (previousButton) {
            previousButton.addEventListener('click', function () {
                showSlide(currentIndex - 1);
                startAutoplay();
            });
        }
        if (nextButton) {
            nextButton.addEventListener('click', function () {
                showSlide(currentIndex + 1);
                startAutoplay();
            });
        }
        indicators.forEach(function (indicator) {
            indicator.addEventListener('click', function () {
                showSlide(Number(indicator.getAttribute('data-banner-indicator')));
                startAutoplay();
            });
        });

        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);
        carousel.addEventListener('focusin', stopAutoplay);
        carousel.addEventListener('focusout', function (event) {
            if (!carousel.contains(event.relatedTarget)) startAutoplay();
        });
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) stopAutoplay();
            else startAutoplay();
        });
        showSlide(0);
        startAutoplay();
    });

    document.querySelectorAll('[data-product-gallery]').forEach(function (gallery) {
        const track = gallery.querySelector('.product-gallery__track');
        const slides = Array.from(gallery.querySelectorAll('.product-gallery__slide'));
        const previousButton = gallery.querySelector('.product-gallery__nav--prev');
        const nextButton = gallery.querySelector('.product-gallery__nav--next');

        if (!track || slides.length < 2) return;

        let currentIndex = 0;
        let touchStartX = null;

        function render() {
            track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';
            slides.forEach(function (slide, index) {
                slide.setAttribute('aria-hidden', index === currentIndex ? 'false' : 'true');
            });
        }

        previousButton && previousButton.addEventListener('click', function () {
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            render();
        });
        nextButton && nextButton.addEventListener('click', function () {
            currentIndex = (currentIndex + 1) % slides.length;
            render();
        });
        const viewport = gallery.querySelector('.product-gallery__viewport');
        if (viewport) {
            viewport.addEventListener('touchstart', function (event) {
                touchStartX = event.changedTouches[0].clientX;
            }, { passive: true });
            viewport.addEventListener('touchend', function (event) {
                if (touchStartX === null) return;
                const distance = event.changedTouches[0].clientX - touchStartX;
                touchStartX = null;
                if (Math.abs(distance) < 40) return;
                currentIndex = (currentIndex + (distance < 0 ? 1 : -1) + slides.length) % slides.length;
                render();
            }, { passive: true });
        }

        render();
    });

    const productImageInputs = Array.from(document.querySelectorAll('[data-image-preview-input]'));
    productImageInputs.forEach(function (input) {
        const preview = input.parentElement.querySelector('[data-image-preview]');
        if (!preview) return;

        input.addEventListener('change', function () {
            preview.replaceChildren();
            Array.from(input.files || []).forEach(function (file) {
                const card = document.createElement('div');
                card.className = 'product-upload-preview__item';
                const image = document.createElement('img');
                image.alt = '';
                image.src = URL.createObjectURL(file);
                image.addEventListener('load', function () {
                    URL.revokeObjectURL(image.src);
                }, { once: true });
                const name = document.createElement('span');
                name.className = 'product-upload-preview__name';
                name.textContent = file.name;
                card.append(image, name);
                preview.append(card);
            });
            validateImageSelection(input.closest('form'));
        });
    });

    function validateImageSelection(form) {
        if (!form) return true;
        const inputs = Array.from(form.querySelectorAll('[data-image-preview-input]'));
        const errors = [];
        let totalBytes = 0;

        inputs.forEach(function (input) {
            const files = Array.from(input.files || []);
            if (input.multiple && files.length > 10) {
                errors.push('Chỉ chọn tối đa 10 ảnh phụ.');
            }
            files.forEach(function (file) {
                totalBytes += file.size;
                if (file.size > 5 * 1024 * 1024) {
                    errors.push('Mỗi ảnh không được vượt quá 5 MB.');
                }
            });
        });
        if (totalBytes > 30 * 1024 * 1024) {
            errors.push('Tổng dung lượng ảnh không được vượt quá 30 MB.');
        }

        inputs.forEach(function (input) {
            const status = input.parentElement.querySelector('[data-image-preview-status]');
            if (!status) return;
            const files = Array.from(input.files || []);
            status.textContent = errors.length
                ? errors.join(' ')
                : (files.length ? files.length + ' ảnh đã chọn · ' + (files.reduce(function (sum, file) {
                    return sum + file.size;
                }, 0) / (1024 * 1024)).toFixed(1) + ' MB' : '');
            status.classList.toggle('text-danger', errors.length > 0);
            status.classList.toggle('text-secondary', errors.length === 0);
        });
        return errors.length === 0;
    }

    new Set(productImageInputs.map(function (input) {
        return input.closest('form');
    }).filter(Boolean)).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!validateImageSelection(form)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            const input = document.getElementById(toggle.getAttribute('data-password-toggle'));
            if (!input) return;
            const showingPassword = input.type === 'password';
            input.type = showingPassword ? 'text' : 'password';
            toggle.setAttribute('aria-label', showingPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
            const icon = toggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', !showingPassword);
                icon.classList.toggle('bi-eye-slash', showingPassword);
            }
        });
    });

    const bannerFileInput = document.getElementById('images');
    const bannerUploadSummary = document.getElementById('banner-upload-summary');
    const bannerUploadButton = document.querySelector('[data-banner-upload-submit]');
    if (bannerFileInput && bannerUploadSummary && bannerUploadButton) {
        bannerFileInput.addEventListener('change', function () {
            const files = Array.from(bannerFileInput.files || []);
            const totalBytes = files.reduce(function (total, file) {
                return total + file.size;
            }, 0);
            const totalMegabytes = (totalBytes / (1024 * 1024)).toFixed(1);
            const isWithinLimits = files.length > 0 && files.length <= 10 && totalBytes <= 30 * 1024 * 1024;

            bannerUploadSummary.textContent = files.length
                ? files.length + ' ảnh đã chọn · ' + totalMegabytes + ' MB tổng'
                : 'Chưa chọn ảnh.';
            bannerUploadSummary.classList.toggle('text-danger', files.length > 0 && !isWithinLimits);
            bannerUploadSummary.classList.toggle('text-primary', files.length === 0 || isWithinLimits);
            bannerUploadButton.disabled = files.length > 0 && !isWithinLimits;
        });
    }

    // ----------------------------------------
    // 10. FORMAT GIÁ INPUT (tự thêm dấu chấm theo định dạng Việt Nam)
    // ----------------------------------------
    document.querySelectorAll('input[data-format="price"], input[data-price-format]').forEach(function (input) {
        input.addEventListener('input', function () {
            let raw = this.value.replace(/\D/g, '');
            this.value = raw ? parseInt(raw, 10).toLocaleString('vi-VN') : '';
        });

        if (input.hasAttribute('data-price-format')) {
            const buttonGroup = input.closest('.price-filter-stepper');
            if (!buttonGroup) return;

            buttonGroup.querySelectorAll('[data-price-step]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const currentPrice = Number(input.value.replace(/\D/g, '')) || 0;
                    const step = Number(button.getAttribute('data-price-step')) * 500000;
                    input.value = Math.max(0, currentPrice + step).toLocaleString('vi-VN');
                });
            });
        }
    });

}); // END DOMContentLoaded

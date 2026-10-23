<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';
require_once dirname(__DIR__) . '/includes/media.php';

define('ADMIN_BANNERS_PAGE', true);
require_once dirname(__DIR__, 2) . '/includes/migrations/side_banners.php';
require_once dirname(__DIR__) . '/includes/migrations/banner_slides.php';
require_once dirname(__DIR__, 2) . '/includes/migrations/promo_marquee.php';

$error = '';
$promoMessage = '';
$promoActive = false;
$promoBannerQuery = $conn->query('SELECT message, is_active FROM site_promo_banner WHERE id = 1');
$promoBanner = $promoBannerQuery->fetch(PDO::FETCH_ASSOC);
if ($promoBanner) {
    $promoMessage = $promoBanner['message'];
    $promoActive = (int)$promoBanner['is_active'] === 1;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > postMaxSizeInBytes()) {
        $error = 'Tổng dung lượng ảnh vượt quá giới hạn máy chủ. Hãy giảm số ảnh hoặc tải ảnh nhẹ hơn.';
    } elseif (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

        if ($action === 'promo_save') {
            $promoMessage = trim((string)($_POST['promo_message'] ?? ''));
            $promoActive = isset($_POST['promo_active']);
            if ($promoMessage === '' || mb_strlen($promoMessage, 'UTF-8') > 500) {
                $error = 'Nội dung thông báo không được để trống và tối đa 500 ký tự.';
            } else {
                try {
                    $savePromoBanner = $conn->prepare(
                        'UPDATE site_promo_banner SET message = ?, is_active = ? WHERE id = 1'
                    );
                    $savePromoBanner->execute([$promoMessage, $promoActive ? 1 : 0]);
                    header('Location: /Web/admin/banners/index.php?promo_saved=1');
                    exit;
                } catch (PDOException $exception) {
                    $error = 'Không thể lưu thanh thông báo. Vui lòng kiểm tra cấu hình máy chủ.';
                }
            }
        } elseif ($action === 'vertical_save') {
            $position = (string)($_POST['position'] ?? '');
            $sideBannerQuery = $conn->prepare('SELECT * FROM site_side_banners WHERE placement = ?');
            $sideBannerQuery->execute([$position]);
            $currentBanner = $sideBannerQuery->fetch(PDO::FETCH_ASSOC);
            $title = trim((string)($_POST['title'] ?? ''));

            if (!$currentBanner) {
                $error = 'Vị trí banner dọc không hợp lệ.';
            } elseif ($title === '' || mb_strlen($title, 'UTF-8') > 150) {
                $error = 'Tiêu đề không được để trống và tối đa 150 ký tự.';
            } else {
                $uploadedImage = null;
                try {
                    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $uploadedImage = uploadAdminImage($_FILES['image'], 'banners', 'portrait');
                    }
                    $removeImage = isset($_POST['remove_image']);
                    $image = $uploadedImage ?: ($removeImage ? null : $currentBanner['image']);
                    $isActive = isset($_POST['is_active']) && $image !== null ? 1 : 0;
                    $updateSideBanner = $conn->prepare(
                        'UPDATE site_side_banners
                         SET title = ?, image = ?, link_url = ?, is_active = ?
                         WHERE placement = ?'
                    );
                    $updateSideBanner->execute([
                        $title,
                        $image,
                        $currentBanner['link_url'] ?: '/Web/products.php',
                        $isActive,
                        $position,
                    ]);
                    if (($uploadedImage || $removeImage) && $currentBanner['image']) {
                        try {
                            deleteUploadedImage($currentBanner['image']);
                        } catch (RuntimeException $exception) {
                            error_log('Side banner image cleanup failed: ' . $exception->getMessage());
                        }
                    }
                    header('Location: /Web/admin/banners/index.php?saved=1');
                    exit;
                } catch (RuntimeException $exception) {
                    if ($uploadedImage) {
                        deleteUploadedImage($uploadedImage);
                    }
                    $error = $exception->getMessage();
                } catch (PDOException $exception) {
                    if ($uploadedImage) {
                        deleteUploadedImage($uploadedImage);
                    }
                    $error = 'Không thể lưu banner dọc. Vui lòng kiểm tra cấu hình máy chủ.';
                }
            }
        } elseif ($action === 'add') {
            $files = $_FILES['images'] ?? null;
            $uploadedFiles = [];
            if (!$files || !isset($files['name']) || !is_array($files['name'])) {
                $error = 'Chọn ít nhất một ảnh banner để tải lên.';
            } else {
                $selectedCount = count(array_filter($files['error'], static function ($code) {
                    return $code !== UPLOAD_ERR_NO_FILE;
                }));
                if ($selectedCount < 1 || $selectedCount > 10) {
                    $error = 'Chọn từ 1 đến 10 ảnh banner mỗi lần tải lên.';
                } elseif (array_sum($files['size']) > 30 * 1024 * 1024) {
                    $error = 'Tổng dung lượng ảnh mỗi lần tải lên không được vượt quá 30 MB.';
                } else {
                    $conn->beginTransaction();
                    try {
                        $nextOrder = (int)$conn->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM site_banner_slides')->fetchColumn();
                        $insert = $conn->prepare(
                            'INSERT INTO site_banner_slides (title, image, link_url, is_active, sort_order)
                             VALUES (:title, :image, :link_url, 1, :sort_order)'
                        );
                        foreach ($files['name'] as $index => $originalName) {
                            if ($files['error'][$index] === UPLOAD_ERR_NO_FILE) {
                                continue;
                            }
                            $file = [
                                'name' => $originalName,
                                'type' => $files['type'][$index],
                                'tmp_name' => $files['tmp_name'][$index],
                                'error' => $files['error'][$index],
                                'size' => $files['size'][$index],
                            ];
                            $uploadedImage = uploadAdminImage($file, 'banners', 'landscape');
                            $uploadedFiles[] = $uploadedImage;
                            $title = pathinfo((string)$originalName, PATHINFO_FILENAME);
                            $title = trim(preg_replace('/\s+/', ' ', $title));
                            if ($title === '') {
                                $title = 'Banner ' . ($nextOrder + 1);
                            }
                            $insert->execute([
                                ':title' => mb_substr($title, 0, 150, 'UTF-8'),
                                ':image' => $uploadedImage,
                                ':link_url' => '/Web/products.php',
                                ':sort_order' => $nextOrder++,
                            ]);
                        }
                        header('Location: /Web/admin/banners/index.php?saved=1');
                        exit;
                    } catch (RuntimeException $exception) {
                        if ($conn->inTransaction()) {
                            $conn->rollBack();
                        }
                        foreach ($uploadedFiles as $uploadedFile) {
                            deleteUploadedImage($uploadedFile);
                        }
                        $error = $exception->getMessage();
                    } catch (PDOException $exception) {
                        if ($conn->inTransaction()) {
                            $conn->rollBack();
                        }
                        foreach ($uploadedFiles as $uploadedFile) {
                            deleteUploadedImage($uploadedFile);
                        }
                        $error = 'Không thể lưu banner vào cơ sở dữ liệu. Vui lòng kiểm tra cấu hình máy chủ.';
                    }
                }
            }
        } elseif ($action === 'save' && $id && $id > 0) {
            $title = trim((string)($_POST['title'] ?? ''));
            $slideQuery = $conn->prepare('SELECT image, link_url FROM site_banner_slides WHERE id = :id');
            $slideQuery->execute([':id' => $id]);
            $currentSlide = $slideQuery->fetch(PDO::FETCH_ASSOC);

            if (!$currentSlide) {
                $error = 'Không tìm thấy banner cần cập nhật.';
            } elseif ($title === '' || mb_strlen($title, 'UTF-8') > 150) {
                $error = 'Tiêu đề không được để trống và tối đa 150 ký tự.';
            } else {
                $uploadedImage = null;
                try {
                    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $uploadedImage = uploadAdminImage($_FILES['image'], 'banners', 'landscape');
                    }
                    $image = $uploadedImage ?: $currentSlide['image'];
                    $update = $conn->prepare(
                        'UPDATE site_banner_slides
                         SET title = :title, image = :image, is_active = :is_active
                         WHERE id = :id'
                    );
                    $update->execute([
                        ':title' => $title,
                        ':image' => $image,
                        ':is_active' => isset($_POST['is_active']) ? 1 : 0,
                        ':id' => $id,
                    ]);
                    if ($uploadedImage && $currentSlide['image'] !== $uploadedImage) {
                        deleteUploadedImage($currentSlide['image']);
                    }
                    header('Location: /Web/admin/banners/index.php?saved=1');
                    exit;
                } catch (RuntimeException $exception) {
                    $error = $exception->getMessage();
                } catch (PDOException $exception) {
                    if ($uploadedImage) {
                        deleteUploadedImage($uploadedImage);
                    }
                    $error = 'Không thể cập nhật banner. Vui lòng kiểm tra cấu hình máy chủ.';
                }
            }
        } elseif ($action === 'delete' && $id && $id > 0) {
            $slideQuery = $conn->prepare('SELECT image FROM site_banner_slides WHERE id = :id');
            $slideQuery->execute([':id' => $id]);
            $slide = $slideQuery->fetch(PDO::FETCH_ASSOC);
            if (!$slide) {
                $error = 'Không tìm thấy banner cần xóa.';
            } else {
                $delete = $conn->prepare('DELETE FROM site_banner_slides WHERE id = :id');
                $delete->execute([':id' => $id]);
                deleteUploadedImage($slide['image']);
                header('Location: /Web/admin/banners/index.php?deleted=1');
                exit;
            }
        } else {
            $error = 'Yêu cầu không hợp lệ.';
        }
    }
}

$slides = $conn->query('SELECT * FROM site_banner_slides ORDER BY sort_order ASC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
$sideBanners = $conn->query('SELECT * FROM site_side_banners ORDER BY placement')->fetchAll(PDO::FETCH_ASSOC);
$sideBannerImages = array_fill_keys(array_filter(array_column($sideBanners, 'image')), true);
$slides = array_values(array_filter($slides, static function ($slide) use ($sideBannerImages) {
    if (isset($sideBannerImages[$slide['image']])) {
        return false;
    }
    $imageUrl = getBannerImage($slide['image']);
    if ($imageUrl === '') {
        return false;
    }
    $imagePath = dirname(__DIR__, 2) . '/' . rawurldecode(substr($imageUrl, strlen('/Web/')));
    $dimensions = @getimagesize($imagePath);
    return $dimensions !== false && $dimensions[0] > $dimensions[1];
}));
adminHeader('Quản lý banner quảng cáo');
?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= adminEscape($error) ?></div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Đã lưu banner.</div><?php endif; ?>
<?php if (isset($_GET['promo_saved'])): ?><div class="alert alert-success">Đã lưu thanh thông báo.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Đã xóa banner.</div><?php endif; ?>

<section class="card card-body shadow-sm mb-4">
    <h2 class="h5 fw-bold">Thanh Banner Khuyến Mãi / Thông Báo Chạy Chữ</h2>
    <p class="text-secondary">Chỉnh nội dung thông báo chạy trên trang chủ. Hỗ trợ tối đa 500 ký tự.</p>
    <form method="post">
        <?= csrfInput() ?>
        <input type="hidden" name="action" value="promo_save">
        <div class="mb-3">
            <label class="form-label" for="promo-message">Nội dung đang chạy</label>
            <textarea class="form-control" id="promo-message" name="promo_message" rows="3" maxlength="500" required><?= adminEscape($promoMessage) ?></textarea>
            <div class="form-text">Ví dụ: 🔥 Giảm giá đến 30% Laptop Gaming | 🚚 Miễn phí vận chuyển đơn từ 500k</div>
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="promo-active" name="promo_active" <?= $promoActive ? 'checked' : '' ?>>
            <label class="form-check-label" for="promo-active">Hiển thị trên trang chủ</label>
        </div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Lưu thông báo</button>
    </form>
</section>

<h2 class="h5 fw-bold mb-3">Banner quảng cáo dọc 2 bên</h2>
<p class="text-secondary">Tải ảnh riêng cho từng vị trí. Banner dọc hiển thị hai bên banner ngang trên trang chủ khi xem bằng màn hình lớn.</p>
<div class="row g-3 mb-4">
    <?php foreach ($sideBanners as $sideBanner): ?>
        <?php
        $positionLabel = $sideBanner['placement'] === 'left' ? 'Bên trái' : 'Bên phải';
        $previewUrl = $sideBanner['image'] ? getBannerImage($sideBanner['image']) : '/Web/image/no-image.svg';
        ?>
        <div class="col-lg-6">
            <article class="card shadow-sm h-100">
                <div class="row g-0 h-100">
                    <div class="col-4 p-3">
                        <div class="admin-side-banner-preview">
                            <img src="<?= adminEscape($previewUrl) ?>" alt="<?= adminEscape($sideBanner['title']) ?>">
                        </div>
                    </div>
                    <div class="col-8">
                        <form method="post" enctype="multipart/form-data" class="card-body">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="vertical_save">
                            <input type="hidden" name="position" value="<?= adminEscape($sideBanner['placement']) ?>">
                            <h3 class="h6 fw-bold">Banner dọc <?= adminEscape($positionLabel) ?></h3>
                            <div class="mb-2">
                                <label class="form-label" for="side-title-<?= adminEscape($sideBanner['placement']) ?>">Tiêu đề ảnh</label>
                                <input class="form-control" id="side-title-<?= adminEscape($sideBanner['placement']) ?>" name="title" maxlength="150" required value="<?= adminEscape($sideBanner['title']) ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="side-image-<?= adminEscape($sideBanner['placement']) ?>">
                                    <?= $sideBanner['image'] ? 'Thay ảnh' : 'Tải ảnh lên' ?> (JPG, PNG, WEBP hoặc GIF; tối đa 5 MB)
                                </label>
                                <input class="form-control" id="side-image-<?= adminEscape($sideBanner['placement']) ?>" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                                <div class="form-text">Chỉ nhận ảnh dọc (cao hơn rộng); tỷ lệ khoảng 2:5 phù hợp nhất.</div>
                            </div>
                            <?php if ($sideBanner['image']): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="side-remove-<?= adminEscape($sideBanner['placement']) ?>" name="remove_image">
                                    <label class="form-check-label" for="side-remove-<?= adminEscape($sideBanner['placement']) ?>">Gỡ ảnh hiện tại</label>
                                </div>
                            <?php endif; ?>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="side-active-<?= adminEscape($sideBanner['placement']) ?>" name="is_active" <?= $sideBanner['is_active'] ? 'checked' : '' ?> <?= !$sideBanner['image'] ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="side-active-<?= adminEscape($sideBanner['placement']) ?>">Hiển thị trên trang chủ</label>
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-cloud-arrow-up me-1"></i>Lưu banner dọc</button>
                        </form>
                    </div>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>

<section class="card card-body shadow-sm mb-4">
    <h2 class="h5 fw-bold">Tải nhiều banner lên</h2>
    <p class="text-secondary">Chọn tối đa 10 ảnh cùng lúc (tổng dung lượng tối đa 30 MB). Các ảnh đang bật sẽ tự chạy lần lượt trên trang chủ.</p>
    <form method="post" enctype="multipart/form-data">
        <?= csrfInput() ?>
        <input type="hidden" name="action" value="add">
        <div class="row g-3 align-items-end">
            <div class="col-12">
                <label class="form-label" for="images">Chọn ảnh (JPG, PNG, WEBP, GIF; tối đa 5 MB mỗi ảnh)</label>
                <input class="form-control" id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
                <div class="form-text">Chỉ nhận ảnh ngang (rộng hơn cao). Giữ Ctrl (Windows) hoặc Command (Mac) để chọn nhiều ảnh; tỷ lệ khoảng 2.3:1 hiển thị đẹp nhất.</div>
                <output id="banner-upload-summary" class="d-block small text-primary mt-2" aria-live="polite">Chưa chọn ảnh.</output>
            </div>
            <div class="col-12"><button class="btn btn-primary" type="submit" data-banner-upload-submit><i class="bi bi-cloud-arrow-up me-1"></i>Tải banner lên</button></div>
        </div>
    </form>
</section>

<h2 class="h5 fw-bold mb-3">Danh sách banner (<?= count($slides) ?>)</h2>
<?php if (!$slides): ?>
    <div class="alert alert-info">Chưa có banner tải lên. Banner mặc định vẫn được hiển thị cho đến khi bạn thêm banner đầu tiên.</div>
<?php endif; ?>
<div class="row g-3">
    <?php foreach ($slides as $slide): ?>
        <div class="col-12">
            <article class="card shadow-sm">
                <div class="row g-0">
                    <div class="col-md-4 p-3">
                        <div class="admin-banner-preview">
                            <img class="w-100 h-100 rounded border" style="object-fit: cover; object-position: center; background: #f8fafc"
                                 src="<?= adminEscape(getBannerImage($slide['image'])) ?>" alt="<?= adminEscape($slide['title']) ?>"
                                 onerror="this.onerror=null;this.src='/Web/image/no-image.svg'">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <form method="post" enctype="multipart/form-data" class="card-body">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="<?= (int)$slide['id'] ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="title-<?= (int)$slide['id'] ?>">Tiêu đề</label>
                                    <input class="form-control" id="title-<?= (int)$slide['id'] ?>" name="title" maxlength="150" required value="<?= adminEscape($slide['title']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="image-<?= (int)$slide['id'] ?>">Thay ảnh ngang (rộng hơn cao; không chọn nếu giữ ảnh hiện tại)</label>
                                    <input class="form-control" id="image-<?= (int)$slide['id'] ?>" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="active-<?= (int)$slide['id'] ?>" name="is_active" <?= $slide['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="active-<?= (int)$slide['id'] ?>">Hiển thị trên trang chủ</label>
                                    </div>
                                </div>
                                <div class="col-12 d-flex flex-wrap gap-2">
                                    <button class="btn btn-sm btn-primary" type="submit">Lưu thay đổi</button>
                                    <button class="btn btn-sm btn-outline-danger" type="submit" form="delete-banner-<?= (int)$slide['id'] ?>">Xóa banner</button>
                                </div>
                            </div>
                        </form>
                        <form id="delete-banner-<?= (int)$slide['id'] ?>" method="post" onsubmit="return confirm('Xóa banner này?')">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$slide['id'] ?>">
                        </form>
                    </div>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>
<?php adminFooter(); ?>

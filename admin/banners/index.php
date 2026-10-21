<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';
require_once dirname(__DIR__) . '/includes/media.php';

define('ADMIN_BANNERS_PAGE', true);
require_once dirname(__DIR__) . '/includes/migrations/banner_slides.php';

$error = '';
$linkUrl = trim((string)($_POST['link_url'] ?? '/Web/products.php'));
$validLocal = strpos($linkUrl, '/Web/') === 0 && strpos($linkUrl, '//') === false;
$scheme = strtolower((string)parse_url($linkUrl, PHP_URL_SCHEME));
$validExternal = filter_var($linkUrl, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > postMaxSizeInBytes()) {
        $error = 'Tổng dung lượng ảnh vượt quá giới hạn máy chủ. Hãy giảm số ảnh hoặc tải ảnh nhẹ hơn.';
    } elseif (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

        if ($action === 'add') {
            $files = $_FILES['images'] ?? null;
            $uploadedFiles = [];
            if ($linkUrl !== '' && !$validLocal && !$validExternal) {
                $error = 'Liên kết phải bắt đầu bằng /Web/ hoặc là địa chỉ HTTP/HTTPS hợp lệ.';
            } elseif (!$files || !isset($files['name']) || !is_array($files['name'])) {
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
                            $uploadedImage = uploadAdminImage($file, 'banners');
                            $uploadedFiles[] = $uploadedImage;
                            $title = pathinfo((string)$originalName, PATHINFO_FILENAME);
                            $title = trim(preg_replace('/\s+/', ' ', $title));
                            if ($title === '') {
                                $title = 'Banner ' . ($nextOrder + 1);
                            }
                            $insert->execute([
                                ':title' => mb_substr($title, 0, 150, 'UTF-8'),
                                ':image' => $uploadedImage,
                                ':link_url' => $linkUrl !== '' ? $linkUrl : '/Web/products.php',
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
            $linkUrl = trim((string)($_POST['link_url'] ?? ''));
            $validLocal = strpos($linkUrl, '/Web/') === 0 && strpos($linkUrl, '//') === false;
            $scheme = strtolower((string)parse_url($linkUrl, PHP_URL_SCHEME));
            $validExternal = filter_var($linkUrl, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true);
            $slideQuery = $conn->prepare('SELECT image FROM site_banner_slides WHERE id = :id');
            $slideQuery->execute([':id' => $id]);
            $currentSlide = $slideQuery->fetch(PDO::FETCH_ASSOC);

            if (!$currentSlide) {
                $error = 'Không tìm thấy banner cần cập nhật.';
            } elseif ($title === '' || mb_strlen($title, 'UTF-8') > 150) {
                $error = 'Tiêu đề không được để trống và tối đa 150 ký tự.';
            } elseif ($linkUrl !== '' && !$validLocal && !$validExternal) {
                $error = 'Liên kết phải bắt đầu bằng /Web/ hoặc là địa chỉ HTTP/HTTPS hợp lệ.';
            } else {
                $uploadedImage = null;
                try {
                    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $uploadedImage = uploadAdminImage($_FILES['image'], 'banners');
                    }
                    $image = $uploadedImage ?: $currentSlide['image'];
                    $update = $conn->prepare(
                        'UPDATE site_banner_slides
                         SET title = :title, image = :image, link_url = :link_url, is_active = :is_active
                         WHERE id = :id'
                    );
                    $update->execute([
                        ':title' => $title,
                        ':image' => $image,
                        ':link_url' => $linkUrl !== '' ? $linkUrl : '/Web/products.php',
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
adminHeader('Quản lý banner quảng cáo');
?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= adminEscape($error) ?></div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Đã lưu banner.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Đã xóa banner.</div><?php endif; ?>

<section class="card card-body shadow-sm mb-4">
    <h2 class="h5 fw-bold">Tải nhiều banner lên</h2>
    <p class="text-secondary">Chọn tối đa 10 ảnh cùng lúc (tổng dung lượng tối đa 30 MB). Các ảnh đang bật sẽ tự chạy lần lượt trên trang chủ.</p>
    <form method="post" enctype="multipart/form-data">
        <?= csrfInput() ?>
        <input type="hidden" name="action" value="add">
        <div class="row g-3 align-items-end">
            <div class="col-md-7">
                <label class="form-label" for="images">Chọn ảnh (JPG, PNG, WEBP, GIF; tối đa 5 MB mỗi ảnh)</label>
                <input class="form-control" id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
                <div class="form-text">Giữ Ctrl (Windows) hoặc Command (Mac) để chọn nhiều ảnh. Ảnh sẽ tự động lấp đầy khung; ảnh ngang tỷ lệ 2.3:1 hiển thị đẹp nhất.</div>
                <output id="banner-upload-summary" class="d-block small text-primary mt-2" aria-live="polite">Chưa chọn ảnh.</output>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="new-link-url">Liên kết của các banner</label>
                <input class="form-control" id="new-link-url" name="link_url" maxlength="500" value="<?= adminEscape($linkUrl) ?>" placeholder="/Web/products.php">
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
                                <div class="col-md-6">
                                    <label class="form-label" for="link-<?= (int)$slide['id'] ?>">Liên kết</label>
                                    <input class="form-control" id="link-<?= (int)$slide['id'] ?>" name="link_url" maxlength="500" value="<?= adminEscape($slide['link_url']) ?>">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label" for="image-<?= (int)$slide['id'] ?>">Thay ảnh (không chọn nếu giữ ảnh hiện tại)</label>
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

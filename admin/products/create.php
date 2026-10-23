<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';
require_once dirname(__DIR__) . '/includes/media.php';
require_once dirname(__DIR__, 2) . '/includes/product_specifications.php';
require_once dirname(__DIR__, 2) . '/includes/migrations/product_specifications.php';

$categories = $conn->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$error = '';
$values = ['name' => '', 'category_id' => '', 'brand' => '', 'description' => '', 'price' => '', 'stock' => '0'];
$specifications = [['name' => '', 'value' => '']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }
    $specificationError = '';
    $specifications = normalizeProductSpecifications(
        $_POST['specification_names'] ?? [],
        $_POST['specification_values'] ?? [],
        $specificationError
    );
    $price = adminNormalizePrice($values['price']);
    if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
    } elseif ($specificationError !== '') {
        $error = $specificationError;
    } elseif ($values['name'] === '' || $price === null || (float)$price < 0 ||
        filter_var($values['stock'], FILTER_VALIDATE_INT) === false || (int)$values['stock'] < 0) {
        $error = 'Nhập tên, giá hợp lệ và số lượng tồn kho không âm.';
    } else {
        $uploadedImage = null;
        $uploadedImages = [];
        try {
            validateAdminImageBatch($_FILES['images'] ?? []);
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadedImage = uploadAdminImage($_FILES['image'], 'products');
                $uploadedImages[] = $uploadedImage;
            }
            $categoryId = $values['category_id'] !== '' ? (int)$values['category_id'] : null;
            $statement = $conn->prepare(
                'INSERT INTO products (category_id, name, brand, specifications, description, price, stock, image)
                 VALUES (:category_id, :name, :brand, :specifications, :description, :price, :stock, :image)'
            );
            $statement->execute([
                ':category_id' => $categoryId,
                ':name' => $values['name'],
                ':brand' => $values['brand'] ?: null,
                ':specifications' => $specifications
                    ? json_encode($specifications, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null,
                ':description' => $values['description'] ?: null,
                ':price' => (float)$price,
                ':stock' => (int)$values['stock'],
                ':image' => $uploadedImage,
            ]);
            $productId = (int)$conn->lastInsertId();

            if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
                $files = $_FILES['images'];
                foreach ($files['name'] as $index => $name) {
                    if ($files['error'][$index] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $extraFile = [
                        'name' => $files['name'][$index],
                        'type' => $files['type'][$index],
                        'tmp_name' => $files['tmp_name'][$index],
                        'error' => $files['error'][$index],
                        'size' => $files['size'][$index],
                    ];
                    $uploadedExtra = uploadAdminImage($extraFile, 'products');
                    $uploadedImages[] = $uploadedExtra;
                    $insertImage = $conn->prepare(
                        'INSERT INTO product_images (product_id, image, sort_order, is_primary) VALUES (:product_id, :image, :sort_order, :is_primary)'
                    );
                    $insertImage->execute([
                        ':product_id' => $productId,
                        ':image' => $uploadedExtra,
                        ':sort_order' => $index,
                        ':is_primary' => 0,
                    ]);
                }
            }

            if ($uploadedImage) {
                $primary = $conn->prepare(
                    'INSERT INTO product_images (product_id, image, sort_order, is_primary) VALUES (:product_id, :image, :sort_order, :is_primary)'
                );
                $primary->execute([
                    ':product_id' => $productId,
                    ':image' => $uploadedImage,
                    ':sort_order' => 0,
                    ':is_primary' => 1,
                ]);
            }

            header('Location: /Web/admin/products/index.php?saved=1');
            exit;
        } catch (RuntimeException $exception) {
            foreach ($uploadedImages as $path) {
                deleteUploadedImage($path);
            }
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            foreach ($uploadedImages as $path) {
                deleteUploadedImage($path);
            }
            $error = 'Không thể lưu sản phẩm. Vui lòng kiểm tra dữ liệu và kết nối cơ sở dữ liệu.';
        }
    }
}

adminHeader('Thêm sản phẩm');
?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= adminEscape($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card card-body shadow-sm">
    <?= csrfInput() ?>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label" for="name">Tên sản phẩm *</label><input class="form-control" id="name" name="name" required maxlength="150" value="<?= adminEscape($values['name']) ?>"></div>
        <div class="col-md-4"><label class="form-label" for="category_id">Danh mục</label><select class="form-select" id="category_id" name="category_id"><option value="">Chưa phân loại</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= $values['category_id'] == $category['id'] ? 'selected' : '' ?>><?= adminEscape($category['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label" for="price">Giá (₫) *</label><input class="form-control" id="price" name="price" type="text" inputmode="numeric" data-format="price" autocomplete="off" required value="<?= adminEscape(adminFormatPriceInput($values['price'])) ?>"></div>
        <div class="col-md-4"><label class="form-label" for="stock">Số lượng tồn kho (cái)</label><input class="form-control" id="stock" name="stock" type="number" min="0" step="1" value="<?= adminEscape($values['stock']) ?>"></div>
        <div class="col-md-4">
            <label class="form-label" for="brand">Thương hiệu</label>
            <input class="form-control" id="brand" name="brand" maxlength="50" placeholder="Ví dụ: Apple, Samsung" value="<?= adminEscape($values['brand']) ?>">
            <div class="form-text">Tên hãng sản xuất.</div>
        </div>
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                <div>
                    <label class="form-label mb-0">Thông số kỹ thuật</label>
                    <div class="form-text">Thêm các cặp thông tin phù hợp với sản phẩm.</div>
                </div>
                <button class="btn btn-sm btn-outline-primary" type="button" data-add-specification><i class="bi bi-plus-lg me-1"></i>Thêm dòng</button>
            </div>
            <div class="row g-2" data-specifications>
                <?php foreach ($specifications as $specification): ?>
                    <div class="col-12" data-specification-row>
                        <div class="row g-2 align-items-start">
                            <div class="col-md-5"><input class="form-control" name="specification_names[]" maxlength="100" placeholder="Tên thông số, ví dụ: Kích cỡ màn hình" value="<?= adminEscape($specification['name']) ?>" aria-label="Tên thông số"></div>
                            <div class="col-md-6"><input class="form-control" name="specification_values[]" maxlength="2000" placeholder="Giá trị, ví dụ: 55 inch" value="<?= adminEscape($specification['value']) ?>" aria-label="Giá trị thông số"></div>
                            <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-specification aria-label="Xóa dòng"><i class="bi bi-trash"></i></button></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-12"><label class="form-label" for="description">Mô tả</label><textarea class="form-control" id="description" name="description" rows="4"><?= adminEscape($values['description']) ?></textarea></div>
        <div class="col-12">
            <label class="form-label" for="product-main-image">Ảnh chính sản phẩm</label>
            <input class="form-control" id="product-main-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-image-preview-input>
            <div class="form-text">Ảnh này sẽ luôn hiển thị đầu tiên và làm ảnh đại diện trong danh sách sản phẩm.</div>
            <div class="product-upload-preview" data-image-preview></div>
            <output class="d-block small mt-2" data-image-preview-status aria-live="polite"></output>
        </div>
        <div class="col-12">
            <label class="form-label" for="product-extra-images">Ảnh phụ sản phẩm</label>
            <input class="form-control" id="product-extra-images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-image-preview-input>
            <div class="form-text">Chọn tối đa 10 ảnh phụ (5 MB mỗi ảnh). Ảnh phụ sẽ xuất hiện sau ảnh chính ở trang chi tiết.</div>
            <div class="product-upload-preview" data-image-preview></div>
            <output class="d-block small mt-2" data-image-preview-status aria-live="polite"></output>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Lưu sản phẩm</button><a class="btn btn-outline-secondary" href="/Web/admin/products/index.php">Hủy</a></div>
</form>
<template id="specification-row-template">
    <div class="col-12" data-specification-row>
        <div class="row g-2 align-items-start">
            <div class="col-md-5"><input class="form-control" name="specification_names[]" maxlength="100" placeholder="Tên thông số, ví dụ: Kích cỡ màn hình" aria-label="Tên thông số"></div>
            <div class="col-md-6"><input class="form-control" name="specification_values[]" maxlength="2000" placeholder="Giá trị, ví dụ: 55 inch" aria-label="Giá trị thông số"></div>
            <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-specification aria-label="Xóa dòng"><i class="bi bi-trash"></i></button></div>
        </div>
    </div>
</template>
<script>
    const specificationRows = document.querySelector('[data-specifications]');
    const specificationTemplate = document.getElementById('specification-row-template');
    document.querySelector('[data-add-specification]').addEventListener('click', () => {
        specificationRows.append(specificationTemplate.content.cloneNode(true));
    });
    specificationRows.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-specification]');
        if (removeButton) {
            removeButton.closest('[data-specification-row]').remove();
        }
    });
</script>
<?php adminFooter(); ?>
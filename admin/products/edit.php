<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';
require_once dirname(__DIR__) . '/includes/media.php';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Mã sản phẩm không hợp lệ.');
}
$statement = $conn->prepare('SELECT * FROM products WHERE id = :id');
$statement->execute([':id' => $id]);
$product = $statement->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    http_response_code(404);
    exit('Không tìm thấy sản phẩm.');
}
$productImageQuery = $conn->prepare(
    'SELECT id, image FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, sort_order ASC, id ASC'
);
$productImageQuery->execute([':product_id' => $id]);
$productImages = $productImageQuery->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$error = '';
$values = $product;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['name', 'category_id', 'brand', 'cpu', 'ram', 'description', 'price', 'stock'] as $key) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }
    if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
    } elseif ($values['name'] === '' || !is_numeric($values['price']) || (float)$values['price'] < 0 ||
        filter_var($values['stock'], FILTER_VALIDATE_INT) === false || (int)$values['stock'] < 0) {
        $error = 'Nhập tên, giá hợp lệ và số lượng tồn kho không âm.';
    } else {
        $uploadedImage = null;
        $uploadedImages = [];
        $removedImages = [];
        try {
            validateAdminImageBatch($_FILES['images'] ?? []);
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadedImage = uploadAdminImage($_FILES['image'], 'products');
                $uploadedImages[] = $uploadedImage;
            }
            $removeImage = isset($_POST['remove_image']);
            $image = $uploadedImage ?: ($removeImage ? null : $product['image']);
            $categoryId = $values['category_id'] !== '' ? (int)$values['category_id'] : null;
            $conn->beginTransaction();
            $update = $conn->prepare(
                'UPDATE products SET category_id = :category_id, name = :name, brand = :brand, cpu = :cpu,
                 ram = :ram, description = :description, price = :price, stock = :stock, image = :image WHERE id = :id'
            );
            $update->execute([
                ':category_id' => $categoryId,
                ':name' => $values['name'],
                ':brand' => $values['brand'] ?: null,
                ':cpu' => $values['cpu'] ?: null,
                ':ram' => $values['ram'] ?: null,
                ':description' => $values['description'] ?: null,
                ':price' => (float)$values['price'],
                ':stock' => (int)$values['stock'],
                ':image' => $image,
                ':id' => $id,
            ]);

            $removeIds = array_unique(array_filter(
                array_map('intval', (array)($_POST['remove_image_ids'] ?? [])),
                static function ($imageId) {
                    return $imageId > 0;
                }
            ));
            $findImage = $conn->prepare(
                'SELECT image FROM product_images WHERE id = :id AND product_id = :product_id'
            );
            $deleteImage = $conn->prepare(
                'DELETE FROM product_images WHERE id = :id AND product_id = :product_id'
            );
            foreach ($removeIds as $imageId) {
                $findImage->execute([':id' => $imageId, ':product_id' => $id]);
                $storedImage = $findImage->fetchColumn();
                if ($storedImage !== false) {
                    $deleteImage->execute([':id' => $imageId, ':product_id' => $id]);
                    $removedImages[] = $storedImage;
                }
            }

            if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
                $files = $_FILES['images'];
                $nextSortOrder = (int)$conn->query(
                    'SELECT COALESCE(MAX(sort_order), -1) + 1 FROM product_images WHERE product_id = ' . (int)$id
                )->fetchColumn();
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
                        ':product_id' => $id,
                        ':image' => $uploadedExtra,
                        ':sort_order' => $nextSortOrder++,
                        ':is_primary' => 0,
                    ]);
                }
            }

            if ($image !== $product['image'] && !empty($product['image'])) {
                $oldPrimary = $conn->prepare(
                    'DELETE FROM product_images WHERE product_id = :product_id AND image = :image AND is_primary = 1'
                );
                $oldPrimary->execute([':product_id' => $id, ':image' => $product['image']]);
            }

            if ($uploadedImage) {
                $primary = $conn->prepare(
                    'INSERT INTO product_images (product_id, image, sort_order, is_primary) VALUES (:product_id, :image, -1, 1)'
                );
                $primary->execute([':product_id' => $id, ':image' => $uploadedImage]);
            }

            $conn->commit();

            $cleanupFailed = false;
            $imagesToDelete = $removedImages;
            if ($image !== $product['image'] && !empty($product['image'])) {
                $imagesToDelete[] = $product['image'];
            }
            foreach ($imagesToDelete as $imageToDelete) {
                try {
                    deleteUploadedImage($imageToDelete);
                } catch (RuntimeException $exception) {
                    error_log('Product image cleanup failed: ' . $exception->getMessage());
                    $cleanupFailed = true;
                }
            }
            header('Location: /Web/admin/products/index.php?saved=1' . ($cleanupFailed ? '&image_cleanup_error=1' : ''));
            exit;
        } catch (RuntimeException $exception) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            foreach ($uploadedImages as $path) {
                deleteUploadedImage($path);
            }
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            foreach ($uploadedImages as $path) {
                deleteUploadedImage($path);
            }
            $error = 'Không thể cập nhật sản phẩm. Vui lòng kiểm tra dữ liệu và kết nối cơ sở dữ liệu.';
        }
    }
}

adminHeader('Cập nhật sản phẩm');
?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= adminEscape($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card card-body shadow-sm">
    <?= csrfInput() ?>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label" for="name">Tên sản phẩm *</label><input class="form-control" id="name" name="name" required maxlength="150" value="<?= adminEscape($values['name']) ?>"></div>
        <div class="col-md-4"><label class="form-label" for="category_id">Danh mục</label><select class="form-select" id="category_id" name="category_id"><option value="">Chưa phân loại</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (string)$values['category_id'] === (string)$category['id'] ? 'selected' : '' ?>><?= adminEscape($category['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label" for="price">Giá *</label><input class="form-control" id="price" name="price" type="number" min="0" step="0.01" required value="<?= adminEscape($values['price']) ?>"></div>
        <div class="col-md-4"><label class="form-label" for="stock">Số lượng tồn kho (cái)</label><input class="form-control" id="stock" name="stock" type="number" min="0" step="1" value="<?= adminEscape($values['stock']) ?>"></div>
        <div class="col-md-4">
            <label class="form-label" for="brand">Thương hiệu</label>
            <input class="form-control" id="brand" name="brand" maxlength="50" placeholder="Ví dụ: Apple, Samsung" value="<?= adminEscape($values['brand']) ?>">
            <div class="form-text">Tên hãng sản xuất.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="cpu">Bộ xử lý (CPU)</label>
            <input class="form-control" id="cpu" name="cpu" maxlength="100" placeholder="Ví dụ: Intel Core i5-13400, Apple M3" value="<?= adminEscape($values['cpu']) ?>">
            <div class="form-text">Dành cho máy tính/điện thoại; không áp dụng thì để trống.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="ram">Bộ nhớ RAM</label>
            <input class="form-control" id="ram" name="ram" maxlength="50" placeholder="Ví dụ: 8 GB, 16 GB" value="<?= adminEscape($values['ram']) ?>">
            <div class="form-text">Dung lượng RAM, không phải dung lượng ổ cứng; không áp dụng thì để trống.</div>
        </div>
        <div class="col-12"><label class="form-label" for="description">Mô tả</label><textarea class="form-control" id="description" name="description" rows="4"><?= adminEscape($values['description']) ?></textarea></div>
        <div class="col-12">
            <label class="form-label" for="product-main-image">Ảnh chính sản phẩm</label>
            <input class="form-control" id="product-main-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-image-preview-input>
            <div class="d-flex align-items-center gap-3 mt-2">
                <img class="admin-image-preview" src="<?= adminEscape(getProductImage($product['image'])) ?>" alt="" onerror="this.onerror=null;this.src='/Web/image/no-image.svg'">
                <label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="remove_image"> Xóa ảnh hiện tại</label>
            </div>
            <div class="form-text">Ảnh chính luôn được hiển thị đầu tiên trong trang chi tiết sản phẩm.</div>
            <div class="product-upload-preview" data-image-preview></div>
            <output class="d-block small mt-2" data-image-preview-status aria-live="polite"></output>
        </div>
        <div class="col-12">
            <label class="form-label" for="product-extra-images">Thêm ảnh phụ sản phẩm</label>
            <input class="form-control" id="product-extra-images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-image-preview-input>
            <div class="form-text">Chọn tối đa 10 ảnh phụ (5 MB mỗi ảnh). Ảnh mới sẽ được thêm sau các ảnh hiện có.</div>
            <div class="product-upload-preview" data-image-preview></div>
            <output class="d-block small mt-2" data-image-preview-status aria-live="polite"></output>
        </div>
        <?php if ($productImages): ?>
            <div class="col-12">
                <p class="form-label mb-2">Ảnh phụ đang lưu (chọn ảnh sai để gỡ)</p>
                <div class="product-upload-preview">
                    <?php foreach ($productImages as $productImage): ?>
                        <?php if ($productImage['image'] === $product['image']) continue; ?>
                        <label class="product-upload-preview__item product-upload-preview__existing">
                            <img src="<?= adminEscape(getProductImage($productImage['image'])) ?>" alt="" onerror="this.onerror=null;this.src='/Web/image/no-image.svg'">
                            <span class="product-upload-preview__name">
                                <input class="form-check-input me-1" type="checkbox" name="remove_image_ids[]" value="<?= (int)$productImage['id'] ?>">
                                Gỡ ảnh này
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Lưu thay đổi</button><a class="btn btn-outline-secondary" href="/Web/admin/products/index.php">Hủy</a></div>
</form>
<?php adminFooter(); ?>
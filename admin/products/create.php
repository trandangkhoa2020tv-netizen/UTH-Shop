<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';
require_once dirname(__DIR__) . '/includes/media.php';

$categories = $conn->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$error = '';
$values = ['name' => '', 'category_id' => '', 'brand' => '', 'cpu' => '', 'ram' => '', 'description' => '', 'price' => '', 'stock' => '0'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) {
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
        try {
            validateAdminImageBatch($_FILES['images'] ?? []);
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadedImage = uploadAdminImage($_FILES['image'], 'products');
                $uploadedImages[] = $uploadedImage;
            }
            $categoryId = $values['category_id'] !== '' ? (int)$values['category_id'] : null;
            $statement = $conn->prepare(
                'INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
                 VALUES (:category_id, :name, :brand, :cpu, :ram, :description, :price, :stock, :image)'
            );
            $statement->execute([
                ':category_id' => $categoryId,
                ':name' => $values['name'],
                ':brand' => $values['brand'] ?: null,
                ':cpu' => $values['cpu'] ?: null,
                ':ram' => $values['ram'] ?: null,
                ':description' => $values['description'] ?: null,
                ':price' => (float)$values['price'],
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
<?php adminFooter(); ?>
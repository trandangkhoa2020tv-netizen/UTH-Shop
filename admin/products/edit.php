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
        try {
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadedImage = uploadAdminImage($_FILES['image'], 'products');
            }
            $removeImage = isset($_POST['remove_image']);
            $image = $uploadedImage ?: ($removeImage ? null : $product['image']);
            $categoryId = $values['category_id'] !== '' ? (int)$values['category_id'] : null;
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
            if ($image !== $product['image'] && !empty($product['image'])) {
                deleteUploadedImage($product['image']);
            }
            header('Location: /Web/admin/products/index.php?saved=1');
            exit;
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($uploadedImage) {
                deleteUploadedImage($uploadedImage);
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
        <div class="col-md-4"><label class="form-label" for="stock">Tồn kho</label><input class="form-control" id="stock" name="stock" type="number" min="0" step="1" value="<?= adminEscape($values['stock']) ?>"></div>
        <div class="col-md-4"><label class="form-label" for="brand">Thương hiệu</label><input class="form-control" id="brand" name="brand" maxlength="50" value="<?= adminEscape($values['brand']) ?>"></div>
        <div class="col-md-6"><label class="form-label" for="cpu">CPU</label><input class="form-control" id="cpu" name="cpu" maxlength="100" value="<?= adminEscape($values['cpu']) ?>"></div>
        <div class="col-md-6"><label class="form-label" for="ram">RAM</label><input class="form-control" id="ram" name="ram" maxlength="50" value="<?= adminEscape($values['ram']) ?>"></div>
        <div class="col-12"><label class="form-label" for="description">Mô tả</label><textarea class="form-control" id="description" name="description" rows="4"><?= adminEscape($values['description']) ?></textarea></div>
        <div class="col-12">
            <label class="form-label" for="image">Ảnh sản phẩm (JPG, PNG, WEBP, GIF; tối đa 5 MB)</label>
            <input class="form-control" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="d-flex align-items-center gap-3 mt-2">
                <img class="admin-image-preview" src="<?= adminEscape(getProductImage($product['image'])) ?>" alt="" onerror="this.onerror=null;this.src='/Web/image/no-image.svg'">
                <label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="remove_image"> Xóa ảnh hiện tại</label>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Lưu thay đổi</button><a class="btn btn-outline-secondary" href="/Web/admin/products/index.php">Hủy</a></div>
</form>
<?php adminFooter(); ?>
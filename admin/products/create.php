<?php
$pageTitle = 'Thêm Sản phẩm';
require_once dirname(__DIR__) . '/_layout.php';

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $brand       = trim($_POST['brand'] ?? '');
    $cpu         = trim($_POST['cpu'] ?? '');
    $ram         = trim($_POST['ram'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price'] ?? '';
    $stock       = (int)($_POST['stock'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0) ?: null;

    if (empty($name))  $errors[] = 'Tên sản phẩm không được để trống.';
    if (!is_numeric($price) || $price <= 0) $errors[] = 'Giá phải là số dương.';

    // Upload ảnh
    $imageName = '';
    if (!empty($_FILES['image']['name'])) {
        $allowedTypes = ['image/jpeg','image/png','image/webp','image/gif'];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Chỉ chấp nhận ảnh JPG, PNG, WEBP, GIF.';
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = 'product_' . time() . '_' . rand(100,999) . '.' . $ext;
            $dest = dirname(__DIR__, 2) . '/image/' . $imageName;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $errors[] = 'Upload ảnh thất bại. Kiểm tra quyền thư mục image/.';
                $imageName = '';
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO products
            (category_id, name, brand, cpu, ram, description, price, stock, image)
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$category_id, $name, $brand, $cpu, $ram, $description, $price, $stock, $imageName]);
        flashSuccess("Thêm sản phẩm \"$name\" thành công!");
        header('Location: /Web/admin/products/index.php');
        exit();
    }
}
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Thêm sản phẩm mới</h5>
    <a href="/Web/admin/products/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Quay lại
    </a>
</div>

<div class="card" style="max-width:780px">
    <div class="card-body p-4">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2">
                <?php foreach ($errors as $e): ?>
                    <div><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Danh mục</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($_POST['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Thương hiệu</label>
                    <input type="text" name="brand" class="form-control"
                           value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>" placeholder="Apple, Samsung...">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">CPU</label>
                    <input type="text" name="cpu" class="form-control"
                           value="<?= htmlspecialchars($_POST['cpu'] ?? '') ?>" placeholder="M2, i7, ...">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">RAM</label>
                    <input type="text" name="ram" class="form-control"
                           value="<?= htmlspecialchars($_POST['ram'] ?? '') ?>" placeholder="8GB, 16GB...">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Giá (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control" min="0" step="1000"
                           value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required placeholder="15990000">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Số lượng tồn kho</label>
                    <input type="number" name="stock" class="form-control" min="0"
                           value="<?= (int)($_POST['stock'] ?? 0) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Mô tả sản phẩm</label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Mô tả chi tiết..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Ảnh sản phẩm</label>
                    <input type="file" name="image" class="form-control" accept="image/*"
                           onchange="previewImg(this)">
                    <img id="imgPreview" src="" alt="" class="mt-2 rounded d-none" style="max-height:120px">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Lưu sản phẩm</button>
                <a href="/Web/admin/products/index.php" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

<script>
function previewImg(input) {
    const preview = document.getElementById('imgPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.classList.remove('d-none'); };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<?php require_once dirname(__DIR__) . '/_layout_end.php'; ?>

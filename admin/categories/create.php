<?php
$pageTitle = 'Thêm Danh mục';
require_once dirname(__DIR__) . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Tên danh mục không được để trống.';
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $desc]);
        flashSuccess("Thêm danh mục \"$name\" thành công!");
        header('Location: /Web/admin/categories/index.php');
        exit();
    }
}
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Thêm danh mục mới</h5>
    <a href="/Web/admin/categories/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Quay lại
    </a>
</div>
<div class="card" style="max-width:600px">
    <div class="card-body p-4">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Tên danh mục <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       placeholder="VD: Điện thoại, Laptop..." required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Mô tả</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Mô tả ngắn về danh mục..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Lưu danh mục</button>
                <a href="/Web/admin/categories/index.php" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/_layout_end.php'; ?>

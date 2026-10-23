<?php
$pageTitle = 'Sửa Danh mục';
require_once dirname(__DIR__) . '/_layout.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$cat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cat) {
    flashError('Không tìm thấy danh mục.');
    header('Location: /Web/admin/categories/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Tên danh mục không được để trống.';
    } else {
        $stmt = $conn->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
        $stmt->execute([$name, $desc, $id]);
        flashSuccess("Cập nhật danh mục thành công!");
        header('Location: /Web/admin/categories/index.php');
        exit();
    }
}
$cat['name']        = $_POST['name']        ?? $cat['name'];
$cat['description'] = $_POST['description'] ?? $cat['description'];
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-pencil me-2 text-warning"></i>Sửa danh mục #<?= $id ?></h5>
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
                       value="<?= htmlspecialchars($cat['name']) ?>" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Mô tả</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($cat['description'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning text-white"><i class="bi bi-check-lg me-1"></i>Cập nhật</button>
                <a href="/Web/admin/categories/index.php" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/_layout_end.php'; ?>

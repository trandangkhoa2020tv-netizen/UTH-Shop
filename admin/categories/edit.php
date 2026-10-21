<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';

$id = filter_var($_GET['id'] ?? $_POST['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Mã danh mục không hợp lệ.');
}
$select = $conn->prepare('SELECT id, name, description FROM categories WHERE id = :id');
$select->execute([':id' => $id]);
$category = $select->fetch(PDO::FETCH_ASSOC);
if (!$category) {
    http_response_code(404);
    exit('Không tìm thấy danh mục.');
}

$name = $category['name'];
$description = $category['description'] ?? '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
    } elseif ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
        $error = 'Tên danh mục là bắt buộc và không được vượt quá 100 ký tự.';
    } else {
        $duplicate = $conn->prepare('SELECT id FROM categories WHERE name = :name AND id <> :id LIMIT 1');
        $duplicate->execute([':name' => $name, ':id' => $id]);
        if ($duplicate->fetchColumn()) {
            $error = 'Tên danh mục đã tồn tại.';
        } else {
            $update = $conn->prepare('UPDATE categories SET name = :name, description = :description WHERE id = :id');
            $update->execute([
                ':name' => $name,
                ':description' => $description !== '' ? $description : null,
                ':id' => $id,
            ]);
            header('Location: /Web/admin/categories/index.php?saved=1');
            exit;
        }
    }
}

adminHeader('Sửa danh mục');
?>
<p class="admin-page-intro">Cập nhật tên và mô tả danh mục sản phẩm.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= adminEscape($error) ?></div><?php endif; ?>
<form method="post" class="card card-body admin-table-card">
    <?= csrfInput() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="mb-3">
        <label class="form-label fw-semibold" for="name">Tên danh mục <span class="text-danger">*</span></label>
        <input class="form-control" id="name" name="name" maxlength="100" value="<?= adminEscape($name) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold" for="description">Mô tả</label>
        <textarea class="form-control" id="description" name="description" rows="4"><?= adminEscape($description) ?></textarea>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Lưu thay đổi</button>
        <a class="btn btn-outline-secondary" href="/Web/admin/categories/index.php">Hủy</a>
    </div>
</form>
<?php adminFooter(); ?>
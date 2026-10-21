<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/view.php';

$name = '';
$description = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
    } elseif ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
        $error = 'Tên danh mục là bắt buộc và không được vượt quá 100 ký tự.';
    } else {
        $duplicate = $conn->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
        $duplicate->execute([':name' => $name]);
        if ($duplicate->fetchColumn()) {
            $error = 'Tên danh mục đã tồn tại.';
        } else {
            $insert = $conn->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
            $insert->execute([':name' => $name, ':description' => $description !== '' ? $description : null]);
            header('Location: /Web/admin/categories/index.php?saved=1');
            exit;
        }
    }
}

adminHeader('Thêm danh mục');
?>
<p class="admin-page-intro">Danh mục giúp khách hàng tìm sản phẩm theo nhóm.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= adminEscape($error) ?></div><?php endif; ?>
<form method="post" class="card card-body admin-table-card">
    <?= csrfInput() ?>
    <div class="mb-3">
        <label class="form-label fw-semibold" for="name">Tên danh mục <span class="text-danger">*</span></label>
        <input class="form-control" id="name" name="name" maxlength="100" value="<?= adminEscape($name) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold" for="description">Mô tả</label>
        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Mô tả ngắn về nhóm sản phẩm"><?= adminEscape($description) ?></textarea>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Lưu danh mục</button>
        <a class="btn btn-outline-secondary" href="/Web/admin/categories/index.php">Hủy</a>
    </div>
</form>
<?php adminFooter(); ?>
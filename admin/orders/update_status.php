<?php
$pageTitle = 'Cập nhật Trạng thái Đơn hàng';
require_once dirname(__DIR__) . '/_layout.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flashError('Không tìm thấy đơn hàng.');
    header('Location: /Web/admin/orders/index.php');
    exit();
}

$statusList = [
    'pending'    => 'Chờ xác nhận',
    'processing' => 'Đang xử lý',
    'shipping'   => 'Đang giao hàng',
    'delivered'  => 'Đã giao hàng',
    'cancelled'  => 'Đã hủy',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'] ?? '';
    if (!array_key_exists($newStatus, $statusList)) {
        $error = 'Trạng thái không hợp lệ.';
    } else {
        $conn->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        flashSuccess("Đơn hàng #$id đã được cập nhật sang: {$statusList[$newStatus]}");
        header('Location: /Web/admin/orders/detail.php?id=' . $id);
        exit();
    }
}
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-arrow-repeat me-2 text-warning"></i>Cập nhật trạng thái đơn #<?= $id ?></h5>
    <a href="/Web/admin/orders/detail.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Quay lại đơn hàng
    </a>
</div>

<div class="card" style="max-width:500px">
    <div class="card-body p-4">
        <!-- Thông tin hiện tại -->
        <div class="alert alert-light border mb-4">
            <div class="small text-muted mb-1">Đơn hàng của: <strong><?= htmlspecialchars($order['full_name']) ?></strong></div>
            <div class="small text-muted">Trạng thái hiện tại:
                <span class="fw-bold text-primary"><?= orderStatusLabel($order['status']) ?></span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="form-label fw-semibold">Trạng thái mới</label>
                <?php foreach ($statusList as $val => $label):
                    $badgeColor = ['pending'=>'warning','processing'=>'info','shipping'=>'primary','delivered'=>'success','cancelled'=>'danger'];
                    $bc = $badgeColor[$val] ?? 'secondary';
                ?>
                <div class="form-check border rounded p-3 mb-2 <?= $order['status'] === $val ? 'border-primary bg-primary bg-opacity-10' : '' ?>">
                    <input class="form-check-input" type="radio" name="status"
                           id="status_<?= $val ?>" value="<?= $val ?>"
                           <?= $order['status'] === $val ? 'checked' : '' ?>>
                    <label class="form-check-label d-flex align-items-center gap-2 w-100 cursor-pointer" for="status_<?= $val ?>">
                        <span class="badge bg-<?= $bc ?>"><?= $label ?></span>
                        <?php if ($order['status'] === $val): ?>
                            <span class="text-muted small">(hiện tại)</span>
                        <?php endif; ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning text-white fw-semibold">
                    <i class="bi bi-check-lg me-1"></i>Cập nhật
                </button>
                <a href="/Web/admin/orders/detail.php?id=<?= $id ?>" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/_layout_end.php'; ?>

<?php
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Bắt buộc đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$userId = $_SESSION['user']['id'];

// Trạng thái trong DB -> [nhãn tiếng Việt, màu badge Bootstrap]
function statusInfo($status) {
    return match ($status) {
        'Pending'   => ['Đang xử lý', 'warning text-dark'],
        'Shipping'  => ['Đang giao',  'primary'],
        'Completed' => ['Đã giao',    'success'],
        'Cancelled' => ['Đã hủy',     'danger'],
        default     => [$status,      'secondary'],
    };
}

// Chi tiết 1 đơn (chỉ đơn của chính user này)
$detailOrder = null;
$items = [];
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_GET['id'], $userId]);
    $detailOrder = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($detailOrder) {
        $stmt = $conn->prepare(
            "SELECT od.quantity, od.price, p.name, p.image
             FROM order_details od
             LEFT JOIN products p ON p.id = od.product_id
             WHERE od.order_id = ?"
        );
        $stmt->execute([$detailOrder['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Danh sách đơn của user
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Đơn hàng của tôi';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <h1 class="h3 fw-bold mb-4">Lịch sử đơn hàng</h1>

    <?php if (isset($_GET['id'])): ?>
        <?php if ($detailOrder): [$label, $color] = statusInfo($detailOrder['status']); ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Chi tiết đơn #<?= $detailOrder['id'] ?></h2>
                        <a href="orders.php" class="small">← Quay lại</a>
                    </div>
                    <p class="mb-1">Người nhận: <strong><?= htmlspecialchars($detailOrder['fullname']) ?></strong>
                        - <?= htmlspecialchars($detailOrder['phone']) ?></p>
                    <p class="mb-1">Địa chỉ: <?= htmlspecialchars($detailOrder['address']) ?></p>
                    <p class="mb-3">Trạng thái: <span class="badge bg-<?= $color ?>"><?= $label ?></span></p>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr><th>Sản phẩm</th><th>Đơn giá</th><th>SL</th><th class="text-end">Thành tiền</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($it['image'])): ?>
                                                <img src="<?= htmlspecialchars(getProductImage($it['image']), ENT_QUOTES, 'UTF-8') ?>" width="48" height="48"
                                                     class="rounded object-fit-cover" alt=""
                                                     onerror="this.onerror=null;this.src='/Web/image/no-image.svg'">
                                            <?php else: ?>
                                                <img src="/Web/image/no-image.svg" width="48" height="48" class="rounded object-fit-cover" alt="">
                                            <?php endif; ?>
                                            <?= htmlspecialchars($it['name'] ?? 'Sản phẩm đã bị xóa') ?>
                                        </div>
                                    </td>
                                    <td><?= number_format($it['price'], 0, ',', '.') ?>đ</td>
                                    <td><?= $it['quantity'] ?></td>
                                    <td class="text-end"><?= number_format($it['price'] * $it['quantity'], 0, ',', '.') ?>đ</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-end fs-5 fw-bold mb-0">
                        Tổng: <?= number_format($detailOrder['total_price'], 0, ',', '.') ?>đ
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Không tìm thấy đơn hàng.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!$orders): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                Bạn chưa có đơn hàng nào. <a href="products.php">Mua sắm ngay</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mã đơn</th><th>Người nhận</th><th>Trạng thái</th>
                            <th>Tổng tiền</th><th>Ngày đặt</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o): [$label, $color] = statusInfo($o['status']); ?>
                        <tr>
                            <td class="fw-semibold">#<?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['fullname']) ?></td>
                            <td><span class="badge bg-<?= $color ?>"><?= $label ?></span></td>
                            <td><?= number_format($o['total_price'], 0, ',', '.') ?>đ</td>
                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">Xem</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
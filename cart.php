<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

    if ($product_id > 0) {
        $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id'       => $product['id'],
                    'name'     => $product['name'],
                    'price'    => (float)$product['price'],
                    'image'    => $product['image'],
                    'quantity' => $quantity
                ];
            }
        }
    }
    header("Location: cart.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $id => $qty) {
            $id = (int)$id;
            $qty = (int)$qty;
            if (isset($_SESSION['cart'][$id])) {
                if ($qty <= 0) {
                    unset($_SESSION['cart'][$id]);
                } else {
                    $_SESSION['cart'][$id]['quantity'] = $qty;
                }
            }
        }
    }
    header("Location: cart.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
    header("Location: cart.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    unset($_SESSION['cart']);
    header("Location: cart.php");
    exit();
}

$totalAmount = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

include_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <h2 class="mb-4">Giỏ hàng của bạn</h2>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="alert alert-info">
            Giỏ hàng của bạn đang trống. <a href="products.php" class="alert-link">Tiếp tục mua sắm</a>.
        </div>
    <?php else: ?>
        <form action="cart.php" method="POST">
            <input type="hidden" name="action" value="update">
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th>Ảnh</th>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th style="width: 130px;">Số lượng</th>
                            <th>Thành tiền</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                            <tr>
                                <td style="width: 90px;">
                                    <img src="image/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="img-fluid" style="max-height: 70px; object-fit: contain;">
                                </td>
                                <td class="text-start fw-semibold"><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= number_format($item['price'], 0, ',', '.') ?> đ</td>
                                <td>
                                    <input type="number" name="quantities[<?= $id ?>]" value="<?= $item['quantity'] ?>" min="1" class="form-control text-center">
                                </td>
                                <td class="text-danger fw-bold">
                                    <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> đ
                                </td>
                                <td>
                                    <a href="cart.php?action=delete&id=<?= $id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <a href="cart.php?action=clear" class="btn btn-outline-secondary" onclick="return confirm('Bạn có muốn xóa toàn bộ giỏ hàng?');">Xóa hết giỏ hàng</a>
                    <button type="submit" class="btn btn-primary ms-2">Cập nhật số lượng</button>
                </div>
                <div class="text-end">
                    <h4>Tổng thanh toán: <span class="text-danger fw-bold"><?= number_format($totalAmount, 0, ',', '.') ?> đ</span></h4>
                    <a href="checkout.php" class="btn btn-success btn-lg mt-2">Tiến hành đặt hàng</a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
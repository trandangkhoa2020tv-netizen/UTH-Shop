<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';

if (isAdmin()) {
    flashWarning('Tài khoản quản trị chỉ dùng để quản lý cửa hàng. Hãy đăng nhập bằng tài khoản khách hàng để đặt hàng.');
    header('Location: /Web/index.php');
    exit();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$errors = [];
$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId < 1) {
    header('Location: login.php?redirect=checkout.php');
    exit();
}

$cartItems = [];
$totalAmount = 0;
foreach ($_SESSION['cart'] as $key => $item) {
    if (!is_array($item)) {
        unset($_SESSION['cart'][$key]);
        $errors[] = 'Một sản phẩm trong giỏ hàng không hợp lệ và đã được bỏ qua.';
        continue;
    }

    $productId = filter_var($item['product_id'] ?? $item['id'] ?? $key, FILTER_VALIDATE_INT);
    $quantity = filter_var($item['quantity'] ?? 1, FILTER_VALIDATE_INT);
    if ($productId === false || $productId < 1 || $quantity === false || $quantity < 1) {
        unset($_SESSION['cart'][$key]);
        $errors[] = 'Một sản phẩm trong giỏ hàng không hợp lệ và đã được bỏ qua.';
        continue;
    }
    if ($quantity > 99) {
        $quantity = 99;
    }

    $stmtProduct = $conn->prepare('SELECT id, name, price, image FROM products WHERE id = ?');
    $stmtProduct->execute([$productId]);
    $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        unset($_SESSION['cart'][$key]);
        $errors[] = 'Có sản phẩm không còn tồn tại và đã được bỏ khỏi giỏ hàng.';
        continue;
    }

    $cartItems[$productId] = [
        'product_id' => (int)$product['id'],
        'name' => $product['name'],
        'price' => (float)$product['price'],
        'image' => $product['image'] ?? '',
        'quantity' => $quantity,
    ];
    $_SESSION['cart'][$productId] = $cartItems[$productId];
    if ((string)$key !== (string)$productId) {
        unset($_SESSION['cart'][$key]);
    }
    $totalAmount += $cartItems[$productId]['price'] * $quantity;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    }

    $customer_name  = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');
    $note           = trim($_POST['note'] ?? '');

    if ($customer_name === '') {
        $errors[] = "Vui lòng nhập họ và tên người nhận.";
    }
    if ($customer_phone === '') {
        $errors[] = "Vui lòng nhập số điện thoại nhận hàng.";
    }
    if ($customer_address === '') {
        $errors[] = "Vui lòng nhập địa chỉ giao hàng cụ thể.";
    }

    if (empty($errors) && !empty($cartItems)) {
        try {
            $conn->beginTransaction();
            $orderItems = [];
            $orderTotal = 0;
            $stmtProduct = $conn->prepare('SELECT id, price FROM products WHERE id = ? FOR UPDATE');
            foreach ($cartItems as $item) {
                $stmtProduct->execute([$item['product_id']]);
                $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);
                if (!$product) {
                    throw new RuntimeException('Sản phẩm trong giỏ hàng không còn tồn tại.');
                }
                $price = (float)$product['price'];
                $orderItems[] = [
                    'product_id' => (int)$product['id'],
                    'quantity' => $item['quantity'],
                    'price' => $price,
                ];
                $orderTotal += $price * $item['quantity'];
            }

            $sqlOrder = 'INSERT INTO orders
                (user_id, total_amount, status, shipping_address, payment_method, phone, note)
                VALUES (?, ?, ?, ?, ?, ?, ?)';
            $stmtOrder = $conn->prepare($sqlOrder);
            $stmtOrder->execute([
                $userId,
                $orderTotal,
                'pending',
                "Người nhận: {$customer_name}\n{$customer_address}",
                'cod',
                $customer_phone,
                $note,
            ]);

            $orderId = $conn->lastInsertId();
            $stmtItem = $conn->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)'
            );
            foreach ($orderItems as $item) {
                $stmtItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                ]);
            }
            $conn->commit();

            unset($_SESSION['cart']);
            header('Location: orders.php?id=' . urlencode($orderId));
            exit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Checkout order creation failed: ' . $e->getMessage());
            $errors[] = 'Không thể tạo đơn hàng lúc này. Vui lòng thử lại.';
        }
    }
}

include_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <h2 class="mb-4">Thông tin thanh toán & Đặt hàng</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info">
            Giỏ hàng hiện không có sản phẩm hợp lệ. <a href="cart.php" class="alert-link">Quay lại giỏ hàng</a>.
        </div>
    <?php else: ?>
    <div class="row g-4">
        <!-- Cột form thông tin nhận hàng -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">Người nhận hàng</h5>
                    <form action="checkout.php" method="POST">
                        <?= csrfInput() ?>
                        <div class="mb-3">
                            <label class="form-label">Họ và tên *</label>
                            <input type="text" name="customer_name" class="form-control" required
                                   value="<?= htmlspecialchars($_POST['customer_name'] ?? ($_SESSION['user_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nguyễn Văn A">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số điện thoại *</label>
                            <input type="tel" name="customer_phone" class="form-control" required
                                   value="<?= htmlspecialchars($_POST['customer_phone'] ?? ($_SESSION['user_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="0901234567">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Địa chỉ nhận hàng cụ thể *</label>
                            <textarea name="customer_address" class="form-control" rows="3" required
                                      placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành"><?= htmlspecialchars($_POST['customer_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chú giao hàng (nếu có)</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Giao giờ hành chính, gọi trước khi đến..."><?= htmlspecialchars($_POST['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 mt-3">Xác nhận đặt hàng</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cột tóm tắt giỏ hàng -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">Đơn hàng của bạn (<?= count($cartItems) ?> món)</h5>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($cartItems as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                                <div>
                                    <h6 class="my-0"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h6>
                                    <small class="text-muted">SL: <?= $item['quantity'] ?> × <?= number_format($item['price'], 0, ',', '.') ?> đ</small>
                                </div>
                                <span class="text-muted"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> đ</span>
                            </li>
                        <?php endforeach; ?>
                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0 pt-3 border-top">
                            <strong>Tổng thanh toán</strong>
                            <strong class="text-danger fs-5"><?= number_format($totalAmount, 0, ',', '.') ?> đ</strong>
                        </li>
                    </ul>
                    <a href="cart.php" class="btn btn-outline-secondary btn-sm w-100">Quay lại giỏ hàng</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
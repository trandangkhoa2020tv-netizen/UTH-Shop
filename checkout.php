<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$errors = [];
$totalAmount = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name  = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');
    $note           = trim($_POST['note'] ?? '');
    $user_id        = $_SESSION['user_id'] ?? null;

    if (empty($customer_name)) {
        $errors[] = "Vui lòng nhập họ và tên người nhận.";
    }
    if (empty($customer_phone)) {
        $errors[] = "Vui lòng nhập số điện thoại nhận hàng.";
    }
    if (empty($customer_address)) {
        $errors[] = "Vui lòng nhập địa chỉ giao hàng cụ thể.";
    }

    if (empty($errors)) {
        try {
            
            $pdo->beginTransaction();

          
            $sqlOrder = "INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, note, total_amount, status, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $stmtOrder = $pdo->prepare($sqlOrder);
            $stmtOrder->execute([
                $user_id,
                $customer_name,
                $customer_phone,
                $customer_address,
                $note,
                $totalAmount
            ]);

        
            $order_id = $pdo->lastInsertId();

            $sqlDetail = "INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmtDetail = $pdo->prepare($sqlDetail);

            foreach ($_SESSION['cart'] as $item) {
                $stmtDetail->execute([
                    $order_id,
                    $item['id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }

            $pdo->commit();

            unset($_SESSION['cart']);
    
            header("Location: orders.php?success=1&order_id=" . $order_id);
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Có lỗi xảy ra trong quá trình tạo đơn hàng: " . $e->getMessage();
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
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Cột form thông tin nhận hàng -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">Người nhận hàng</h5>
                    <form action="checkout.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Họ và tên *</label>
                            <input type="text" name="customer_name" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['customer_name'] ?? ($_SESSION['user_name'] ?? '')) ?>" placeholder="Nguyễn Văn A">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số điện thoại *</label>
                            <input type="tel" name="customer_phone" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['customer_phone'] ?? ($_SESSION['user_phone'] ?? '')) ?>" placeholder="0901234567">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Địa chỉ nhận hàng cụ thể *</label>
                            <textarea name="customer_address" class="form-control" rows="3" required 
                                      placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành"><?= htmlspecialchars($_POST['customer_address'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chú giao hàng (nếu có)</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Giao giờ hành chính, gọi trước khi đến..."><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
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
                    <h5 class="card-title mb-3">Đơn hàng của bạn (<?= count($_SESSION['cart']) ?> món)</h5>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                                <div>
                                    <h6 class="my-0"><?= htmlspecialchars($item['name']) ?></h6>
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
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
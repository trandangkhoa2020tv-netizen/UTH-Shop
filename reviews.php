<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/review_media.php';
require_once __DIR__ . '/includes/migrations/product_reviews.php';

$productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
$rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
$comment = trim((string)($_POST['comment'] ?? ''));
$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Phương thức không được hỗ trợ.');
}

if (!isLoggedIn() || isAdmin() || $userId === false || $userId < 1) {
    flashWarning('Vui lòng đăng nhập bằng tài khoản khách hàng để đánh giá sản phẩm.');
    header('Location: /Web/login.php?redirect=' . urlencode('product_detail.php?id=' . max(0, (int)$productId)));
    exit();
}

if (!verifyCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
    flashError('Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.');
    header('Location: /Web/product_detail.php?id=' . max(0, (int)$productId) . '#product-reviews');
    exit();
}

if (
    $productId === false || $productId < 1
    || $orderId === false || $orderId < 1
    || $rating === false || $rating < 1 || $rating > 5
    || $comment === '' || mb_strlen($comment, 'UTF-8') > 2000
) {
    flashError('Vui lòng chọn từ 1 đến 5 sao và nhập bình luận tối đa 2.000 ký tự.');
    header('Location: /Web/product_detail.php?id=' . max(0, (int)$productId) . '#product-reviews');
    exit();
}

$eligibleOrder = $conn->prepare(
    "SELECT oi.product_id
     FROM orders o
     INNER JOIN order_items oi ON oi.order_id = o.id
     WHERE o.id = ? AND o.user_id = ? AND o.status = 'completed' AND oi.product_id = ?
     LIMIT 1"
);
$eligibleOrder->execute([$orderId, $userId, $productId]);
if (!$eligibleOrder->fetchColumn()) {
    flashError('Chỉ có thể đánh giá sản phẩm thuộc đơn hàng đã giao của chính bạn.');
    header('Location: /Web/product_detail.php?id=' . $productId . '#product-reviews');
    exit();
}

$uploadedImages = [];
try {
    $uploadedImages = uploadReviewImages($_FILES['images'] ?? []);
    $conn->beginTransaction();
    $insertReview = $conn->prepare(
        'INSERT INTO product_reviews (product_id, user_id, order_id, rating, comment)
         VALUES (?, ?, ?, ?, ?)'
    );
    $insertReview->execute([$productId, $userId, $orderId, $rating, $comment]);
    $reviewId = (int)$conn->lastInsertId();
    if ($uploadedImages) {
        $insertImage = $conn->prepare('INSERT INTO product_review_images (review_id, image) VALUES (?, ?)');
        foreach ($uploadedImages as $imagePath) {
            $insertImage->execute([$reviewId, $imagePath]);
        }
    }
    $conn->commit();
    flashSuccess('Cảm ơn bạn đã đánh giá sản phẩm.');
} catch (RuntimeException $exception) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    deleteReviewImages($uploadedImages);
    flashError($exception->getMessage());
} catch (PDOException $exception) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    deleteReviewImages($uploadedImages);
    if ($exception->getCode() === '23000') {
        flashWarning('Bạn đã đánh giá sản phẩm này cho đơn hàng đã chọn.');
    } else {
        error_log('Product review creation failed: ' . $exception->getMessage());
        flashError('Không thể gửi đánh giá lúc này. Vui lòng thử lại sau.');
    }
} catch (Throwable $exception) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    deleteReviewImages($uploadedImages);
    error_log('Unexpected product review error: ' . $exception->getMessage());
    flashError('Không thể gửi đánh giá lúc này. Vui lòng thử lại sau.');
}

header('Location: /Web/product_detail.php?id=' . $productId . '#product-reviews');
exit();

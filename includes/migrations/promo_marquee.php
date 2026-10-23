<?php
$conn->exec(
    "CREATE TABLE IF NOT EXISTS site_promo_banner (
        id TINYINT UNSIGNED PRIMARY KEY,
        message VARCHAR(500) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
);

$insertPromoBanner = $conn->prepare(
    "INSERT IGNORE INTO site_promo_banner (id, message, is_active)
     VALUES (1, ?, 1)"
);
$insertPromoBanner->execute([
    '🔥 Giảm giá đến 30% các dòng Laptop Gaming mùa tựu trường  |  🚚 Miễn phí vận chuyển toàn quốc cho đơn hàng từ 500k',
]);

<?php
$conn->exec(
    "CREATE TABLE IF NOT EXISTS site_side_banners (
        placement ENUM('left', 'right') PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        link_url VARCHAR(500) NOT NULL DEFAULT '/Web/products.php',
        is_active TINYINT(1) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
);

$insertSideBanner = $conn->prepare(
    "INSERT IGNORE INTO site_side_banners (placement, title, image, link_url, is_active)
     VALUES (?, ?, NULL, '/Web/products.php', 0)"
);
$insertSideBanner->execute(['left', 'Banner quảng cáo dọc bên trái']);
$insertSideBanner->execute(['right', 'Banner quảng cáo dọc bên phải']);

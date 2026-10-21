<?php
if (!defined('ADMIN_BANNERS_PAGE')) {
    http_response_code(404);
    exit;
}

$conn->exec(
    "CREATE TABLE IF NOT EXISTS site_banner_slides (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL DEFAULT 'Khuyến mãi',
        image VARCHAR(255) NOT NULL,
        link_url VARCHAR(500) NOT NULL DEFAULT '/Web/products.php',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
);

$slideCount = (int)$conn->query('SELECT COUNT(*) FROM site_banner_slides')->fetchColumn();
if ($slideCount === 0) {
    $legacySlides = $conn->query(
        "SELECT title, image, link_url, is_active FROM site_banners
         WHERE image IS NOT NULL AND image <> '' ORDER BY id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
    if ($legacySlides) {
        $insertLegacy = $conn->prepare(
            'INSERT INTO site_banner_slides (title, image, link_url, is_active, sort_order)
             VALUES (:title, :image, :link_url, :is_active, :sort_order)'
        );
        foreach ($legacySlides as $order => $legacySlide) {
            $insertLegacy->execute([
                ':title' => $legacySlide['title'],
                ':image' => $legacySlide['image'],
                ':link_url' => $legacySlide['link_url'],
                ':is_active' => $legacySlide['is_active'],
                ':sort_order' => $order,
            ]);
        }
        $conn->exec("UPDATE site_banners SET image = NULL, is_active = 0 WHERE image IS NOT NULL");
    }
}

$knownImages = $conn->query('SELECT image FROM site_banner_slides')->fetchAll(PDO::FETCH_COLUMN);
$knownImages = array_fill_keys($knownImages, true);
$uploadDirectory = dirname(__DIR__, 3) . '/uploads/banners';
$orphanImages = [];
foreach (glob($uploadDirectory . '/*') ?: [] as $imagePath) {
    if (!is_file($imagePath) || getimagesize($imagePath) === false) {
        continue;
    }
    $relativePath = 'uploads/banners/' . basename($imagePath);
    if (!isset($knownImages[$relativePath])) {
        $orphanImages[] = $relativePath;
    }
}
if ($orphanImages) {
    $nextOrder = (int)$conn->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM site_banner_slides')->fetchColumn();
    $restore = $conn->prepare(
        'INSERT INTO site_banner_slides (title, image, link_url, is_active, sort_order)
         VALUES (:title, :image, :link_url, 1, :sort_order)'
    );
    foreach ($orphanImages as $relativePath) {
        $restore->execute([
            ':title' => 'Banner ' . ($nextOrder + 1),
            ':image' => $relativePath,
            ':link_url' => '/Web/products.php',
            ':sort_order' => $nextOrder++,
        ]);
    }
}

<?php
$productColumns = $conn->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('specifications', $productColumns, true)) {
    $conn->exec('ALTER TABLE products ADD COLUMN specifications MEDIUMTEXT DEFAULT NULL AFTER ram');
}

$legacyProducts = $conn->query(
    "SELECT id, cpu, ram FROM products
     WHERE (specifications IS NULL OR specifications = '')
       AND ((cpu IS NOT NULL AND cpu <> '') OR (ram IS NOT NULL AND ram <> ''))"
)->fetchAll(PDO::FETCH_ASSOC);
if ($legacyProducts) {
    require_once dirname(__DIR__) . '/product_specifications.php';
    $updateSpecifications = $conn->prepare('UPDATE products SET specifications = ? WHERE id = ?');
    foreach ($legacyProducts as $legacyProduct) {
        $specifications = decodeProductSpecifications('', $legacyProduct['cpu'], $legacyProduct['ram']);
        $updateSpecifications->execute([
            json_encode($specifications, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $legacyProduct['id'],
        ]);
    }
}

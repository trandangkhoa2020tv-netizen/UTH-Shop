CREATE DATABASE IF NOT EXISTS uth_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uth_shop;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(50) DEFAULT NULL,
    cpu VARCHAR(100) DEFAULT NULL,
    ram VARCHAR(50) DEFAULT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS site_banners (
    id TINYINT UNSIGNED PRIMARY KEY,
    title VARCHAR(150) NOT NULL DEFAULT 'Khuyến mãi',
    image VARCHAR(255) DEFAULT NULL,
    link_url VARCHAR(500) NOT NULL DEFAULT '/Web/products.php',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS site_banner_slides (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL DEFAULT 'Khuyến mãi',
    image VARCHAR(255) NOT NULL,
    link_url VARCHAR(500) NOT NULL DEFAULT '/Web/products.php',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    payment_method ENUM('cod', 'bank_transfer') DEFAULT 'cod',
    phone VARCHAR(20),
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM products;
DELETE FROM categories;

INSERT INTO categories (id, name, description) VALUES
(1, 'Điện thoại', 'Các loại điện thoại thông minh mới nhất'),
(2, 'Laptop', 'Laptop học tập, văn phòng và gaming'),
(3, 'Phụ kiện', 'Tai nghe, chuột, bàn phím, sạc dự phòng'),
(4, 'Âm thanh', 'Loa, tai nghe, mic thu âm'),
(5, 'Đồng hồ', 'Đồng hồ thông minh, camera giám sát'),
(6, 'PC - Màn hình', 'Máy tính để bàn, màn hình, máy in'),
(7, 'Tivi', 'Tivi thông minh, màn hình lớn')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image) VALUES
(1, 'iPhone 13', 'Apple', NULL, NULL, 'Điện thoại Apple iPhone 13, thiết kế sang trọng, hiệu năng ổn định.', 15990000, 10, 'iphone13.jpg'),
(1, 'Samsung Galaxy S23', 'Samsung', NULL, NULL, 'Điện thoại Samsung cao cấp với màn hình đẹp và camera tốt.', 18990000, 8, 's23.jpg'),
(2, 'MacBook Air M2', 'Apple', 'M2', '8GB', 'Laptop mỏng nhẹ, pin lâu, phù hợp học tập và làm việc.', 28990000, 5, 'macbookairm2.jpg'),
(2, 'Dell XPS 13', 'Dell', 'i7', '16GB', 'Laptop cao cấp màn hình vô cực, mỏng nhẹ nhất thế giới.', 35000000, 3, 'Dell_XPS_13.webp'),
(2, 'HP Spectre x360', 'HP', 'i7', '16GB', 'Laptop xoay gập 360 độ, thiết kế sang trọng đẳng cấp.', 32000000, 4, 'HP_Spectre_x360.webp'),
(2, 'ASUS ROG Zephyrus', 'ASUS', 'i9', '32GB', 'Laptop Gaming đỉnh cao, màn hình 165Hz chuyên game.', 45000000, 2, 'ASUS_ROG_Zephyrus.webp'),
(3, 'Tai nghe Bluetooth', 'Sony', NULL, NULL, 'Tai nghe không dây tiện lợi, âm thanh rõ ràng.', 990000, 20, 'tainghe.jpg'),
(4, 'Loa Bluetooth Marshall', 'Marshall', NULL, NULL, 'Âm thanh cực đỉnh, phong cách retro đẳng cấp.', 5500000, 12, 'Loa_Bluetooth_Marshall.webp'),
(5, 'Apple Watch Series 9', 'Apple', NULL, NULL, 'Theo dõi sức khỏe chuyên sâu, thiết kế thời thượng.', 10500000, 15, 'Apple_Watch_Series_9.webp'),
(6, 'Màn hình Dell UltraSharp', 'Dell', NULL, NULL, 'Độ phân giải 4K, màu sắc chuẩn xác cho đồ họa.', 8200000, 7, 'Man_hinh_Dell_UltraSharp.webp'),
(7, 'Tivi Sony 4K 55 inch', 'Sony', NULL, NULL, 'Hình ảnh sắc nét, âm thanh vòm sống động.', 14500000, 4, 'TV_sony_4k.jpg');

INSERT INTO users (full_name, email, password, phone, address, role)
SELECT 'Administrator', 'admin@gmail.com', '$2y$10$5qHiOvHVQzhbe9vfjGdiduGz6pPTPa8a.a7lwcNgbdazb4zaPQQqq', '0123456789', 'TP.HCM', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@gmail.com');

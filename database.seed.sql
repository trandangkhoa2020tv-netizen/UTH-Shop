USE uth_shop;

INSERT INTO categories (name, description)
SELECT 'Điện thoại', 'Các loại điện thoại thông minh mới nhất'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Điện thoại');
INSERT INTO categories (name, description)
SELECT 'Laptop', 'Laptop học tập, văn phòng và gaming'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Laptop');
INSERT INTO categories (name, description)
SELECT 'Phụ kiện', 'Tai nghe, chuột, bàn phím, sạc dự phòng'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Phụ kiện');
INSERT INTO categories (name, description)
SELECT 'Âm thanh', 'Loa, tai nghe, mic thu âm'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Âm thanh');
INSERT INTO categories (name, description)
SELECT 'Đồng hồ', 'Đồng hồ thông minh, camera giám sát'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Đồng hồ');
INSERT INTO categories (name, description)
SELECT 'PC - Màn hình', 'Máy tính để bàn, màn hình, máy in'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'PC - Màn hình');
INSERT INTO categories (name, description)
SELECT 'Tivi', 'Tivi thông minh, màn hình lớn'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Tivi');

INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
SELECT (SELECT id FROM categories WHERE name = 'Điện thoại' ORDER BY id LIMIT 1), 'iPhone 13', 'Apple', NULL, NULL, 'Điện thoại Apple iPhone 13, thiết kế sang trọng, hiệu năng ổn định.', 15990000, 10, 'iphone13.jpg'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'iPhone 13');
INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
SELECT (SELECT id FROM categories WHERE name = 'Điện thoại' ORDER BY id LIMIT 1), 'Samsung Galaxy S23', 'Samsung', NULL, NULL, 'Điện thoại Samsung cao cấp với màn hình đẹp và camera tốt.', 18990000, 8, 's23.jpg'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Samsung Galaxy S23');
INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
SELECT (SELECT id FROM categories WHERE name = 'Laptop' ORDER BY id LIMIT 1), 'MacBook Air M2', 'Apple', 'M2', '8GB', 'Laptop mỏng nhẹ, pin lâu, phù hợp học tập và làm việc.', 28990000, 5, 'macbookairm2.jpg'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'MacBook Air M2');
INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
SELECT (SELECT id FROM categories WHERE name = 'Laptop' ORDER BY id LIMIT 1), 'Dell XPS 13', 'Dell', 'i7', '16GB', 'Laptop cao cấp màn hình vô cực, mỏng nhẹ nhất thế giới.', 35000000, 3, 'Dell_XPS_13.webp'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Dell XPS 13');
INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
SELECT (SELECT id FROM categories WHERE name = 'Laptop' ORDER BY id LIMIT 1), 'HP Spectre x360', 'HP', 'i7', '16GB', 'Laptop xoay gập 360 độ, thiết kế sang trọng đẳng cấp.', 32000000, 4, 'HP_Spectre_x360.webp'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'HP Spectre x360');
INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
SELECT (SELECT id FROM categories WHERE name = 'Laptop' ORDER BY id LIMIT 1), 'ASUS ROG Zephyrus', 'ASUS', 'i9', '32GB', 'Laptop Gaming đỉnh cao, màn hình 165Hz chuyên game.', 45000000, 2, 'ASUS_ROG_Zephyrus.webp'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'ASUS ROG Zephyrus');
INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
SELECT (SELECT id FROM categories WHERE name = 'Phụ kiện' ORDER BY id LIMIT 1), 'Tai nghe Bluetooth', 'Sony', NULL, NULL, 'Tai nghe không dây tiện lợi, âm thanh rõ ràng.', 990000, 20, 'tainghe.jpg'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Tai nghe Bluetooth');
INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
SELECT (SELECT id FROM categories WHERE name = 'Âm thanh' ORDER BY id LIMIT 1), 'Loa Bluetooth Marshall', 'Marshall', NULL, NULL, 'Âm thanh cực đỉnh, phong cách retro đẳng cấp.', 5500000, 12, 'Loa_Bluetooth_Marshall.webp'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Loa Bluetooth Marshall');
INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
SELECT (SELECT id FROM categories WHERE name = 'Đồng hồ' ORDER BY id LIMIT 1), 'Apple Watch Series 9', 'Apple', NULL, NULL, 'Theo dõi sức khỏe chuyên sâu, thiết kế thời thượng.', 10500000, 15, 'Apple_Watch_Series_9.webp'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Apple Watch Series 9');
INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
SELECT (SELECT id FROM categories WHERE name = 'PC - Màn hình' ORDER BY id LIMIT 1), 'Màn hình Dell UltraSharp', 'Dell', NULL, NULL, 'Độ phân giải 4K, màu sắc chuẩn xác cho đồ họa.', 8200000, 7, 'Man_hinh_Dell_UltraSharp.webp'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Màn hình Dell UltraSharp');
INSERT INTO products (category_id, name, brand, cpu, ram, description, price, stock, image)
SELECT (SELECT id FROM categories WHERE name = 'Tivi' ORDER BY id LIMIT 1), 'Tivi Sony 4K 55 inch', 'Sony', NULL, NULL, 'Hình ảnh sắc nét, âm thanh vòm sống động.', 14500000, 4, 'TV_sony_4k.jpg'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Tivi Sony 4K 55 inch');

INSERT IGNORE INTO site_promo_banner (id, message, is_active) VALUES
(1, 'Giảm giá đến 30% các dòng Laptop Gaming mùa tựu trường | Miễn phí vận chuyển toàn quốc cho đơn hàng từ 500k', 1);

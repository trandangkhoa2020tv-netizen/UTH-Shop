<?php
$host = 'localhost';
$port = '3307';          // Port MySQL của bạn
$dbname = 'uth_shop';    // Tên cơ sở dữ liệu bạn vừa tạo
$username = 'root';      // Tài khoản mặc định của XAMPP
$password = '';          // Mật khẩu mặc định của XAMPP (để trống)
// Tạo kết nối nhớ bật XAMPP  http://localhost/phpmyadmin
try {
    // Khởi tạo kết nối PDO kèm theo Port
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Cất chế độ báo lỗi để dễ dàng bắt lỗi khi code
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // (Tùy chọn) Bật dòng dưới nếu muốn test kết nối nhanh
    // echo "Kết nối cơ sở dữ liệu thành công!";

} catch(PDOException $e) {
    // Nếu kết nối thất bại sẽ dừng chương trình và báo lỗi
    die("Kết nối cơ sở dữ liệu thất bại: " . $e->getMessage());
}
?>
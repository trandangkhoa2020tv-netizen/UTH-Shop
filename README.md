# 🛍️ UTH Shop – Website Bán Hàng

Website bán hàng trực tuyến được xây dựng bằng **PHP thuần, MySQL, HTML, CSS và JavaScript**. Dự án được thực hiện nhằm áp dụng kiến thức lập trình web vào xây dựng một hệ thống thương mại điện tử cơ bản.

## 🚀 Công nghệ sử dụng

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP thuần
- **Database:** MySQL
- **Development Environment:** XAMPP
- **Version Control:** Git & GitHub

## ✨ Tính năng chính

### 🛒 Khách hàng
- Xem trang chủ và danh sách sản phẩm.
- Xem thông tin chi tiết sản phẩm.
- Thêm sản phẩm vào giỏ hàng.
- Đặt hàng trực tuyến.
- Đăng ký, đăng nhập và đăng xuất tài khoản.

### ⚙️ Quản trị viên (Admin)
- Quản lý danh mục sản phẩm (CRUD).
- Quản lý sản phẩm (CRUD).
- Quản lý tài khoản người dùng.
- Quản lý đơn hàng.

## 📂 Cấu trúc dự án

```text
Web/
├── admin/                              # Trang quản trị cửa hàng
│   ├── banners/
│   │   └── index.php                   # Thêm, sửa, sắp xếp, bật/tắt và xóa banner trang chủ
│   ├── categories/
│   │   ├── create.php                  # Tạo danh mục
│   │   ├── delete.php                  # Xóa danh mục
│   │   ├── edit.php                    # Sửa danh mục
│   │   └── index.php                   # Danh sách danh mục
│   ├── includes/
│   │   ├── migrations/
│   │   │   └── banner_slides.php       # Tạo bảng slide banner và chuyển/khôi phục dữ liệu banner cũ
│   │   ├── bootstrap.php               # Khởi tạo session, nạp cấu hình và bắt buộc quyền admin
│   │   ├── media.php                   # Kiểm tra, lưu và xóa ảnh sản phẩm/banner đã tải lên
│   │   └── view.php                    # Hàm dựng khung giao diện và escape dữ liệu trang admin
│   ├── orders/
│   │   ├── detail.php                  # Chi tiết đơn hàng, thông tin khách và mặt hàng
│   │   ├── index.php                   # Danh sách đơn hàng
│   │   └── update_status.php           # Cập nhật trạng thái đơn hàng
│   ├── products/
│   │   ├── create.php                  # Tạo sản phẩm và tải ảnh lên
│   │   ├── delete.php                  # Xóa sản phẩm
│   │   ├── edit.php                    # Sửa thông tin sản phẩm và ảnh
│   │   └── index.php                   # Danh sách sản phẩm
│   ├── users/
│   │   ├── delete.php                  # Xóa khách hàng đủ điều kiện
│   │   ├── detail.php                  # Hồ sơ khách hàng và lịch sử đơn hàng
│   │   └── index.php                   # Tìm kiếm, phân trang và quản lý khách hàng
│   ├── index.php                       # Dashboard thống kê quản trị
│   ├── login.php                       # Đăng nhập quản trị
│   └── logout.php                      # Đăng xuất quản trị
├── assets/
│   ├── css/
│   │   ├── home.css                    # Giao diện riêng trang chủ, danh mục và banner
│   │   └── style.css                   # Kiểu dáng chung của cửa hàng và trang quản trị
│   └── js/
│       └── main.js                     # Tương tác giao diện như giỏ hàng, menu và nút lên đầu trang
├── config/
│   ├── database.php                    # Cấu hình kết nối MySQL dùng bởi ứng dụng
│   └── database_hosting.php            # Cấu hình kết nối MySQL dành cho môi trường hosting
├── image/                              # Ảnh tĩnh: logo, banner và ảnh sản phẩm mẫu
├── includes/
│   ├── auth.php                        # Session, đăng nhập/đăng xuất và phân quyền
│   ├── flash.php                       # Tạo và hiển thị thông báo flash
│   ├── footer.php                      # Footer, liên kết và nạp JavaScript dùng chung
│   ├── functions.php                   # Hàm hỗ trợ, CSRF, xử lý ảnh, định dạng và giỏ hàng
│   └── header.php                      # Khung đầu trang, menu, tìm kiếm và trạng thái tài khoản
├── uploads/
│   ├── banners/                        # Ảnh banner tải lên từ trang quản trị
│   ├── products/                       # Ảnh sản phẩm tải lên từ trang quản trị
│   └── .htaccess                       # Tắt liệt kê thư mục và chặn thực thi tệp script đã tải lên
├── cart.php                            # Xem, cập nhật và xóa sản phẩm trong giỏ hàng
├── checkout.php                        # Nhập thông tin giao hàng và tạo đơn hàng
├── database.sql                        # Tạo cấu trúc cơ sở dữ liệu và dữ liệu mẫu
├── index.php                           # Trang chủ, danh mục, banner và sản phẩm
├── login.php                           # Đăng nhập khách hàng
├── logout.php                          # Đăng xuất tài khoản
├── orders.php                          # Lịch sử đơn hàng của khách hàng
├── product_detail.php                  # Chi tiết sản phẩm và lựa chọn ảnh
├── products.php                        # Danh sách, tìm kiếm và lọc sản phẩm
├── register.php                        # Đăng ký tài khoản khách hàng
└── README.md                           # Giới thiệu, cấu trúc và hướng dẫn cài đặt dự án
```

## 🛠️ Hướng dẫn cài đặt và chạy dự án

### Bước 1: Cài đặt môi trường
- Cài đặt [XAMPP](https://www.apachefriends.org/).
- Khởi động **Apache** và **MySQL** trong XAMPP Control Panel.

### Bước 2: Đưa dự án vào XAMPP
Sao chép thư mục `Web` vào:

```text
D:\Xampp\htdocs\Web\
```

### Bước 3: Tạo cơ sở dữ liệu
1. Truy cập [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Tạo hoặc chọn database theo cấu hình của dự án.
3. Import file `database.sql`.
4. Kiểm tra thông tin kết nối trong thư mục `config/`.

### Bước 4: Truy cập website

Mở trình duyệt và truy cập:

- **Website:** http://localhost/Web/
- **Trang quản trị:** http://localhost/Web/admin/

*Đường dẫn có thể thay đổi tùy theo cấu trúc thư mục và cấu hình thực tế.*

## 🔐 Tài khoản mẫu

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin@gmail.com` | `123456` |

*Đây là tài khoản phục vụ mục đích phát triển và kiểm thử. Không sử dụng mật khẩu mẫu trên môi trường thực tế.*

## 🎯 Mục tiêu dự án

- Vận dụng kiến thức HTML, CSS, JavaScript và PHP.
- Thực hành kết nối và thao tác dữ liệu với MySQL.
- Xây dựng chức năng quản lý sản phẩm và đơn hàng.
- Làm quen với Git và GitHub trong quá trình phát triển phần mềm.

## 👨‍💻 Thực hiện

**Dự án:** UTH Shop – Website Bán Hàng  
**Môn học:** Lập trình Web  
**Trường:** Đại học Giao thông Vận tải TP. Hồ Chí Minh (UTH)

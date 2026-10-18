# SMART F&B POS & AI System

Hệ thống Quản lý Bán hàng (POS) tích hợp AI hỗ trợ phân tích và dự báo nhu cầu bán hàng cho ngành F&B. Dự án được thiết kế theo kiến trúc PHP MVC và mô hình dữ liệu chuẩn 3NF.

---

## Công nghệ sử dụng

- Backend: PHP 8 (Kiến trúc MVC, Front Controller, PDO Prepared Statements)
- Database: MySQL (7 bảng chuẩn 3NF: users, categories, products, orders, order_details, customers, forecast_results)
- Frontend: Bootstrap 5, JavaScript, ApexCharts, FontAwesome 6
- AI / Forecasting Module: Python (Prophet / Machine Learning, RESTful API)

---

## Cấu trúc dự án

POS_AI_PROJECT/
├── ai_module/           # Phân hệ AI & Mô hình dự đoán nhu cầu bán hàng
├── docs/                # Sơ đồ thiết kế UML (Use Case, ERD, Class, Sequence...)
└── web_pos/             # Mã nguồn Hệ thống POS (Web Application)
    ├── app/
    │   ├── Controllers/ # Điều phối logic nghiệp vụ (Product, Category, POS...)
    │   ├── Core/        # Core Framework (App, Controller, Database PDO)
    │   ├── Models/      # Tương tác Cơ sở dữ liệu
    │   └── Views/       # Giao diện Bootstrap 5 (Layouts, Products, Categories...)
    ├── config/          # Cấu hình hệ thống & CSDL
    └── public/          # Assets tĩnh (CSS, JS) & Thư mục Uploads hình ảnh

---

## Các chức năng chính

### Giai đoạn 1: Quản trị Hệ thống & Kho hàng (Tuần 1 - Tuần 4)
- Quản lý Sản phẩm & Kho: CRUD sản phẩm, phân loại danh mục, quản lý giá bán, đơn vị tính và số lượng tồn kho.
- Quản lý Danh mục: Phân loại món ăn, đồ uống, topping.
- Thiết lập Cơ sở dữ liệu: Tối ưu các bảng products, categories, tables, areas chuẩn 3NF.

### Giai đoạn 2: Màn hình POS & Xử lý Đơn hàng (Tuần 5 - Tuần 8)
- Sơ đồ Bàn & Khu vực: Quản lý tầng, trạng thái bàn trống / có khách.
- Giỏ hàng & Thanh toán: Thêm/sửa/xóa món, tùy chỉnh topping (% đường, % đá), tính tổng tiền và in hóa đơn.
- Xử lý Đơn hàng: Lưu lịch sử giao dịch vào CSDL, cập nhật tồn kho thời gian thực.

### Giai đoạn 3: Phân hệ AI Dự báo & Báo cáo (Tuần 9 - Tuần 12)
- Dự báo Doanh thu & Sức mua: Phân tích chuỗi thời gian (Time-series Forecasting).
- Gợi ý Nhập hàng & Xu hướng: Đưa ra khuyến nghị nhập nguyên liệu dựa trên dự báo bán ra.
- Báo cáo Thống kê: Trực quan hóa dữ liệu bán hàng bằng biểu đồ ApexCharts.

---

## Hướng dẫn cài đặt & chạy dự án

### 1. Yêu cầu hệ thống
- PHP >= 8.0
- MySQL >= 5.7 / 8.0
- Web Server: Apache (XAMPP / Laragon)

### 2. Các bước cài đặt
1. Clone repository:
   git clone https://github.com/username/POS_AI_PROJECT.git
   cd POS_AI_PROJECT

2. Cấu hình Cơ sở dữ liệu:
   - Tạo cơ sở dữ liệu mới trong phpMyAdmin (ví dụ: web_pos_db).
   - Import file SQL cấu trúc CSDL trong thư mục docs/database.sql.
   - Cập nhật thông tin kết nối CSDL tại file web_pos/config/database.php:
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'web_pos_db');

3. Khởi chạy ứng dụng:
   - Mở trình duyệt và truy cập đường dẫn: http://localhost/POS_AI_PROJECT/web_pos/public/auth/login

---

## Tiến độ phát triển (Kế hoạch 12 tuần)

- Tuần 1 - 2: Khảo sát yêu cầu, thiết kế Database 3NF & Cấu trúc kiến trúc PHP MVC Core.
- Tuần 3 - 4: Hoàn thiện chức năng CRUD Sản phẩm, Danh mục, Quản lý kho & Viết tài liệu Docs.
- Tuần 5 - 6: Xây dựng Giao diện Màn hình POS (Sơ đồ bàn, Khu vực, Thực đơn món).
- Tuần 7 - 8: Hoàn thiện Logic Giỏ hàng, Modal Topping/Ghi chú, Thanh toán & Lưu Đơn hàng.
- Tuần 9 - 10: Xây dựng Module AI (Python Prophet) dự báo doanh thu & kết nối API.
- Tuần 11 - 12: Tích hợp Biểu đồ Thống kê ApexCharts, Kiểm thử toàn hệ thống & Đóng gói Đồ án.

---
© 2026 Smart F&B POS & AI System. Developed for F&B Management.
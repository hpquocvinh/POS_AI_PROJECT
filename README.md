# SMART F&B POS & AI System

Hệ thống Quản lý Bán hàng (POS) tích hợp AI hỗ trợ phân tích và dự báo nhu cầu bán hàng cho ngành F&B. Dự án được thiết kế theo kiến trúc PHP MVC và mô hình dữ liệu chuẩn 3NF.

---

## Công nghệ sử dụng

- **Backend:** PHP 8 (Kiến trúc MVC, Front Controller, PDO Prepared Statements)
- **Database:** MySQL / MariaDB (Hệ thống 13 bảng chuẩn 3NF: `areas`, `categories`, `customers`, `forecast_results`, `orders`, `order_details`, `products`, `reservations`, `rewards`, `shifts`, `system_settings`, `tables`, `users`)
- **Frontend:** Bootstrap 5, JavaScript (ES6, Fetch API), ApexCharts, FontAwesome 6
- **AI / Forecasting Module:** Python (Prophet / Machine Learning, RESTful API)

---

## Cấu trúc dự án

```text
POS_AI_PROJECT/
├── ai_module/           # Phân hệ AI & Mô hình dự đoán nhu cầu bán hàng (Python)
├── docs/                # Sơ đồ thiết kế UML (ERD 13 bảng, Use Case, Sequence...)
└── web_pos/             # Mã nguồn Hệ thống POS (Web Application)
    ├── app/
    │   ├── Controllers/ # Điều phối logic nghiệp vụ (Order, Product, Shift, Customer...)
    │   ├── Core/        # Core Framework (App, Controller, Database PDO)
    │   ├── Models/      # Tương tác Cơ sở dữ liệu MySQL
    │   └── Views/       # Giao diện POS & Admin (Layouts, POS, Reports...)
    ├── config/          # Cấu hình hệ thống & Kết nối CSDL pos_ai_db
    └── public/          # Assets tĩnh (CSS, JS) & Thư mục Uploads hình ảnh
    
## Các phân hệ & Chức năng chính
# 1. Phân hệ Bán hàng POS đa kênh (Multi-channel POS)

- Tại chỗ (Dine-in): Quản lý sơ đồ bàn/khu vực (Tầng 1, Tầng 2, Sân vườn), hỗ trợ gộp bàn/gộp bill, chuyển bàn linh hoạt.

- Mang về (Takeaway): Quản lý danh sách thẻ mang về (MV-01, MV-02...), xử lý báo xong món & rung thẻ gọi khách.

- Đơn Online App (GrabFood / ShopeeFood): Tiếp nhận đơn hàng ứng dụng, quản lý mã đơn sàn, thông tin tài xế và tự động tính toán chiết khấu hoa hồng sàn (15%, 20%, 25%) để tính doanh thu thực nhận.

# 2. Nghiệp vụ Quản lý & Vận hành POS

- Quản lý Ca & Kết ca (Shift Management): Mở ca, kiểm kê tiền mặt đầu/cuối ca, báo cáo doanh thu theo kênh (Tiền mặt, Chuyển khoản, App Online) và in phiếu kết ca.

- Cấu hình Thuế VAT động: Thay đổi mức % VAT hệ thống trực tiếp trên giao diện POS và áp dụng tức thì vào hóa đơn.

- Khách hàng thân thiết (Loyalty System): Tìm kiếm khách hàng theo SĐT, tích điểm giao dịch, tự động xếp hạng thành viên (Bronze, Silver, Gold, Diamond) và áp dụng voucher/ưu đãi.

- Tra cứu & In lại Hóa đơn (Reprint Bill): Truy vấn danh sách lịch sử đơn hàng trong ca trực tiếp từ MySQL.

# 3. Phân hệ AI Dự báo & Báo cáo Thống kê (Đang phát triển)

- Dự báo Bán hàng: Phân tích chuỗi thời gian (Time-series) dự đoán sức mua món ăn/đồ uống.

- Phân tích Xu hướng: Báo cáo trực quan hóa doanh thu và gợi ý nhập nguyên liệu.

## Hướng dẫn cài đặt & Chạy dự án

# 1. Yêu cầu hệ thống
- PHP >= 8.0

- MySQL / MariaDB >= 10.4

- Web Server: Apache (XAMPP / Laragon)

# 2. Các bước cài đặt
- Clone repository: git clone https://github.com/hpquocvinh/POS_AI_PROJECT.git
- cd POS_AI_PROJECT

## Cấu hình Cơ sở dữ liệu:

- Tạo cơ sở dữ liệu mới trong phpMyAdmin tên là pos_ai_db.

- Import file SQL mới nhất (pos_ai_db.sql) trong thư mục docs/.

# Cập nhật thông tin kết nối CSDL tại file web_pos/config/database.php:
- define('DB_HOST', 'localhost');
- define('DB_USER', 'root');
- define('DB_PASS', '');
- define('DB_NAME', 'pos_ai_db');

## Khởi chạy ứng dụng:

- Trỏ Web Server đến thư mục dự án và truy cập đường dẫn:
http://localhost/POS_AI_PROJECT/web_pos/public/pos

## Nhật ký tiến độ thực tế (Progress Log)
Tuần 1 - 2: Khảo sát yêu cầu, thiết kế Database & Cấu trúc kiến trúc PHP MVC Core.

Tuần 3: Hoàn thiện UI Màn hình POS, sơ đồ bàn/khu vực và logic nghiệp vụ phân hệ Tại chỗ.

Tuần 4 (Hoàn thành):

## Nâng cấp CSDL MySQL pos_ai_db lên 13 bảng.

- Hoàn thiện UI/UX và logic nghiệp vụ phân hệ Mang về (Takeaway) & Đơn Online App (GrabFood/ShopeeFood).

- Tích hợp tính năng Cấu hình VAT động, Báo cáo ca/Kết ca và In lại hóa đơn.

- Xây dựng Backend Controller/Model (PHP MVC) cung cấp API kết nối lưu dữ liệu vĩnh viễn vào MySQL.

# Tuần 5 (Kế hoạch): Sửa dứt điểm logic Đặt bàn & Tích điểm đổi quà, xây dựng Phân hệ Quản lý Khách hàng, Quản lý Đơn hàng & Quản lý Danh mục.

## 2026 Smart F&B POS & AI System. Developed by Ho Phan Quoc Vinh.
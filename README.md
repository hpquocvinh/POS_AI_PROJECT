# ☕ Smart F&B POS & AI System

Hệ thống Quản lý Bán hàng (POS) tích hợp AI hỗ trợ Phân tích & Dự đoán Nhu cầu Bán hàng dành cho ngành F&B. Dự án được thiết kế chuẩn kiến trúc PHP MVC và mô hình dữ liệu 3NF.

---

## 🛠️ Công nghệ sử dụng
- **Backend:** PHP 8 (Architecture: MVC, Front Controller, PDO Prepared Statements)
- **Database:** MySQL (7 bảng chuẩn 3NF: `users`, `categories`, `products`, `orders`, `order_details`, `customers`, `forecast_results`)
- **Frontend:** Bootstrap 5, JavaScript, ApexCharts, Bootstrap Icons
- **AI / Forecasting Module:** Python (Prophet / Machine Learning, API Integration)

---

## 📂 Cấu trúc dự án
```text
POS_AI_PROJECT/
├── ai_module/           # Phân hệ AI & Mô hình dự đoán nhu cầu bán hàng
├── docs/                # Sơ đồ thiết kế UML (Use Case, ERD, Class, Sequence...)
└── web_pos/             # Mã nguồn Hệ thống POS (Web Application)
    ├── app/
    │   ├── Controllers/ # Điều phối logic nghiệp vụ (Product, Category...)
    │   ├── Core/        # Core Framework (App, Controller, Database PDO)
    │   ├── Models/      # Tương tác Cơ sở dữ liệu
    │   └── Views/       # Giao diện Bootstrap 5 (Layouts, Products, Categories...)
    ├── config/          # Cấu hình hệ thống & CSDL
    └── public/          # Assets tĩnh (CSS, JS) & Thư mục Uploads hình ảnh
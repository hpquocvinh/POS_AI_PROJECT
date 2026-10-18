<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMART F&B POS & AI Analytics Platform</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- ApexCharts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary: #4f46e5;
            --primary-soft: #e0e7ff;
            --bg-main: #f8fafc;
            --dark-sidebar: #0f172a;
            --card-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: #334155;
            overflow-x: hidden;
        }
        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--dark-sidebar);
            color: #94a3b8;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
        }
        .sidebar-brand {
            padding: 24px;
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            border-bottom: 1px solid #1e293b;
        }
        .sidebar-menu {
            padding: 16px 12px;
            flex-grow: 1;
            overflow-y: auto;
        }
        .menu-header {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: 700;
            margin: 16px 12px 8px 12px;
        }
        .nav-link-custom {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            color: #94a3b8;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 4px;
            transition: all 0.2s;
        }
        .nav-link-custom:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.05);
        }
        .nav-link-custom.active {
            color: #ffffff;
            background-color: var(--primary);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }
        .nav-link-custom i {
            width: 24px;
            font-size: 16px;
        }
        
        /* Main Layout & Fix Tràn Màn Hình */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        .topbar {
            height: 70px;
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .content-body {
            padding: 24px;
            flex-grow: 1;
        }
        .card-custom {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: var(--card-shadow);
            min-width: 0; /* Fix lỗi vỡ biểu đồ ApexCharts */
        }
        .badge-ai-pill {
            background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%);
            color: white;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        /* TỐI ƯU CẤU TRÚC RIÊNG CHO MÀN HÌNH POS (KHÔNG DÙNG PADDING DÀY TRÁNH ĐẨY CỘT PHẢI) */
        .content-body-pos {
            padding: 16px !important;
            flex-grow: 1;
            width: 100% !important;
            box-sizing: border-box !important;
        }
    </style>
</head>
<body>

<!-- Sidebar Menu -->
<aside class="sidebar">
    <div class="sidebar-brand d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="fa-solid fa-mug-hot text-primary me-2 fs-4"></i>
            <span>SMART F&B POS</span>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <div class="menu-header">TỔNG QUAN</div>
        <a href="<?= BASE_URL ?>/dashboard" class="nav-link-custom">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>

        <div class="menu-header">BÁN HÀNG & QUẢN LÝ</div>
        <a href="<?= BASE_URL ?>/pos" class="nav-link-custom">
            <i class="fa-solid fa-cash-register"></i> Màn Hình POS
        </a>
        <a href="<?= BASE_URL ?>/product" class="nav-link-custom">
            <i class="fa-solid fa-box-archive"></i> Sản Phẩm & Kho
        </a>
        <a href="<?= BASE_URL ?>/category" class="nav-link-custom">
            <i class="fa-solid fa-layer-group"></i> Danh Mục
        </a>
        <a href="<?= BASE_URL ?>/order" class="nav-link-custom">
            <i class="fa-solid fa-receipt"></i> Đơn Hàng
        </a>
        <a href="<?= BASE_URL ?>/customer" class="nav-link-custom">
            <i class="fa-solid fa-users"></i> Khách Hàng
        </a>

        <div class="menu-header">TRÍ TUỆ NHÂN TẠO (AI)</div>
        <a href="<?= BASE_URL ?>/ai/forecast" class="nav-link-custom">
            <i class="fa-solid fa-brain"></i> Dự Báo Bán Hàng <span class="badge-ai-pill ms-auto">AI</span>
        </a>
        <a href="<?= BASE_URL ?>/ai/trends" class="nav-link-custom">
            <i class="fa-solid fa-chart-line"></i> Phân Tích Xu Hướng
        </a>
    </div>

    <div class="p-3 border-top border-secondary border-opacity-25">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:36px; height:36px; font-weight:700;">
                <?= strtoupper(substr($_SESSION['user']['username'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="flex-grow-1 overflow-hidden">
                <h6 class="text-white mb-0 text-truncate font-size-14"><?= $_SESSION['user']['fullname'] ?? 'Quản Trị Viên' ?></h6>
                <small class="text-muted d-block text-capitalize"><?= $_SESSION['user']['role'] ?? 'admin' ?></small>
            </div>
            <a href="<?= BASE_URL ?>/auth/logout" class="text-danger ms-2" title="Đăng xuất">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Main Wrapper -->
<div class="main-wrapper">
    <!-- Topbar -->
    <header class="topbar">
        <div class="d-flex align-items-center">
            <h5 class="fw-bold m-0 text-dark">Hệ Thống Quản Lý Bán Hàng Chuỗi F&B Tích Hợp AI</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <i class="fa-solid fa-circle-dot me-1"></i> Database Online
            </span>
            <div class="vr my-2"></div>
            <a href="<?= BASE_URL ?>/auth/logout" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                <i class="fa-solid fa-power-off me-1"></i> Đăng xuất
            </a>
        </div>
    </header>

    <!-- Content Body -->
    <main class="<?= (strpos($_SERVER['REQUEST_URI'], 'pos') !== false) ? 'content-body-pos' : 'content-body' ?>">
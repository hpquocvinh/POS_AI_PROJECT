<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập | Smart POS & AI Analytics</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .brand-icon {
            width: 56px;
            height: 56px;
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin: 0 auto 16px auto;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .btn-primary-custom {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: #ffffff;
            transition: all 0.2s ease;
        }
        .btn-primary-custom:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }
        .badge-ai {
            background: linear-gradient(90deg, #ec4899, #8b5cf6);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<div class="login-card p-4 p-sm-5">
    <div class="text-center mb-4">
        <div class="brand-icon">
            <i class="fa-solid fa-brain"></i>
        </div>
        <span class="badge-ai mb-2 d-inline-block"><i class="fa-solid fa-sparkles me-1"></i> SMART F&B POS & AI ANALYTICS</span>
        <h4 class="fw-bold text-dark m-0">Hệ Thống Quản Lý Bán Hàng F&B</h4>
        <p class="text-muted small mt-1">Giải Pháp Bán Hàng & Dự Báo Nhu Cầu Nguyên Liệu Tự Động</p> 
    </div>

    <?php if (!empty($data['error'])): ?>
        <div class="alert alert-danger border-0 rounded-3 small py-2 text-center mb-3" style="background: #fef2f2; color: #dc2626;">
            <i class="fa-solid fa-circle-exclamation me-1"></i> <?= $data['error'] ?>
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/auth/login" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Tên đăng nhập</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>
                <input type="text" name="username" class="form-control border-start-0 ps-0 bg-light" placeholder="Nhập username (admin)" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold text-secondary">Mật khẩu</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password" class="form-control border-start-0 ps-0 bg-light" placeholder="Nhập mật khẩu (123456)" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 shadow-sm mb-3">
            Đăng Nhập Hệ Thống <i class="fa-solid fa-arrow-right-to-bracket ms-2"></i>
        </button>
    </form>

    <div class="text-center mt-4 pt-3 border-top">
        <span class="text-muted small">Phiên bản: <code>v2.0 AI Edition</code></span>
    </div>
</div>

</body>
</html>
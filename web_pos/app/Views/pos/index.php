<?php 
$headerPath = __DIR__ . '/../inc/header.php';
if (!file_exists($headerPath)) {
    $headerPath = __DIR__ . '/../layouts/header.php';
}
if (file_exists($headerPath)) {
    require_once $headerPath;
}

$toppingCatId = null;
if (!empty($categories)) {
    foreach ($categories as $cat) {
        $catNameLower = mb_strtolower($cat['category_name'] ?? $cat['name'] ?? '', 'UTF-8');
        if (strpos($catNameLower, 'topping') !== false || strpos($catNameLower, 'thạch') !== false) {
            $toppingCatId = $cat['id'];
            break;
        }
    }
}

$userRole = $_SESSION['user']['role'] ?? 'cashier';
?>

<!-- LINK BOOTSTRAP ICONS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    :root {
        --pos-bg: #f8fafc;
        --pos-card-bg: #ffffff;
        --pos-primary: #4f46e5;
        --pos-dark: #0f172a;
        --pos-border: #e2e8f0;
        --pos-text-main: #1e293b;
        --pos-text-muted: #64748b;
    }

    body { background-color: var(--pos-bg); overflow-x: hidden; }
    .pos-container { height: calc(100vh - 85px); overflow: hidden; }
    .pos-left-scroll { height: 100%; overflow-y: auto; padding-right: 6px; }
    .pos-left-scroll::-webkit-scrollbar, .cart-items-list::-webkit-scrollbar { width: 4px; }
    .pos-left-scroll::-webkit-scrollbar-thumb, .cart-items-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .topbar-segmented-control {
        background-color: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
        display: inline-flex;
        gap: 4px;
        border: 1px solid var(--pos-border);
    }

    .topbar-segmented-btn {
        border: none;
        background: transparent;
        color: var(--pos-text-muted);
        font-weight: 700;
        font-size: 0.82rem;
        padding: 7px 14px;
        border-radius: 9px;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .topbar-segmented-btn:hover {
        background-color: #ffffff;
        color: var(--pos-dark);
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06);
    }

    .topbar-segmented-btn.btn-amber:hover {
        background-color: #fef3c7;
        color: #d97706;
    }

    .topbar-segmented-btn.btn-emerald:hover {
        background-color: #d1fae5;
        color: #059669;
    }

    .order-type-tabs { background: #f1f5f9; padding: 4px; border-radius: 12px; display: flex; gap: 4px; border: 1px solid var(--pos-border); }
    .order-type-btn { flex: 1; border: none; background: transparent; padding: 9px 12px; font-weight: 700; font-size: 0.85rem; color: var(--pos-text-muted); border-radius: 9px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
    .order-type-btn.active { background: var(--pos-dark); color: #ffffff; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.15); }

    .online-subtabs { display: flex; gap: 8px; margin-top: 8px; }
    .subtab-btn { flex: 1; border: 1px solid var(--pos-border); background: #ffffff; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--pos-text-muted); }
    .subtab-btn.active-grab { background: #00b14f; color: #ffffff; border-color: #00b14f; }
    .subtab-btn.active-shopee { background: #ff5722 !important; color: #ffffff !important; border-color: #ff5722 !important; }

    .badge-shopee, .bg-shopee { background-color: #ff5722 !important; color: #ffffff !important; }

    .pos-card { background: var(--pos-card-bg); border: 1px solid var(--pos-border); border-radius: 14px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02); }

    .table-badge { cursor: pointer; transition: all 0.2s ease; border: 1px solid var(--pos-border); border-radius: 10px; padding: 8px 10px; background-color: #ffffff; position: relative; }
    .table-badge:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05); border-color: var(--pos-dark); }
    .table-badge.active { border: 2px solid var(--pos-dark) !important; background-color: #f8fafc !important; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.1) !important; }
    .status-available { border-left: 4px solid #10b981; }
    .status-occupied { border-left: 4px solid #ef4444; background-color: #fef2f2; }
    .status-paid { border-left: 4px solid #10b981; background-color: #ecfdf5; }
    .status-reserved { border-left: 4px solid #f59e0b; background-color: #fffbeb; }

    .cat-btn { border-radius: 20px; padding: 6px 16px; font-weight: 600; font-size: 0.82rem; transition: all 0.2s ease; border: 1px solid var(--pos-border); background: #ffffff; color: var(--pos-text-muted); }
    .cat-btn:hover { background: #f1f5f9; color: var(--pos-text-main); }
    .cat-btn.active-cat { background: var(--pos-dark) !important; color: #ffffff !important; border-color: var(--pos-dark) !important; }

    .product-card { cursor: pointer; transition: all 0.2s ease; border: 1px solid var(--pos-border); border-radius: 12px; overflow: hidden; background: #ffffff; }
    .product-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(15, 23, 42, 0.06); border-color: var(--pos-dark); }
    .product-img-wrapper { overflow: hidden; height: 105px; background-color: #f1f5f9; position: relative; }
    .product-img { height: 100%; width: 100%; object-fit: cover; }
    .online-price-badge { position: absolute; top: 6px; right: 6px; background-color: #00b14f; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 6px; }

    .pos-right-sticky { height: 100%; display: flex; flex-direction: column; background: #ffffff; border-radius: 14px; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03); border: 1px solid var(--pos-border); }
    .cart-items-list { flex: 1; overflow-y: auto; }
    .btn-qty { width: 24px; height: 24px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 12px; }

    .loyalty-card { background: #f8fafc; border: 1px solid var(--pos-border); border-radius: 10px; padding: 8px 10px; }

    .sticker-list-container { max-height: 380px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
    .sticker-mockup { width: 220px; border: 2px dashed #0f172a; padding: 12px; background: #fffdf0; font-family: 'Courier New', Courier, monospace; border-radius: 8px; margin: 0 auto; color: #000; }
    .option-pill-group { display: flex; flex-wrap: wrap; gap: 6px; }
    .option-pill { border: 1px solid var(--pos-border); border-radius: 20px; padding: 5px 12px; font-size: 12px; font-weight: 600; cursor: pointer; background: #f8fafc; color: var(--pos-text-muted); }
    .option-pill.selected { background: var(--pos-dark); color: #ffffff; border-color: var(--pos-dark); }

    .receipt-mockup { width: 280px; margin: 0 auto; padding: 16px; background: #fff; border: 1px solid #cbd5e1; font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

    .shift-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; text-align: center; }
    .shift-card-title { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; margin-bottom: 4px; }
    .shift-card-value { font-size: 15px; font-weight: 800; color: #0f172a; }

    .alert-icon-circle {
        width: 54px;
        height: 54px;
        background-color: #fef3c7;
        color: #d97706;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px auto;
        font-size: 26px;
        box-shadow: 0 4px 14px rgba(217, 119, 6, 0.18);
    }
    .alert-icon-circle.alert-danger { background-color: #fee2e2; color: #dc2626; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.18); }
    .alert-icon-circle.alert-success { background-color: #d1fae5; color: #059669; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.18); }
    
    .bg-amber-subtle { background-color: #fef3c7; }
    .text-amber { color: #d97706; }

    #customAlertModal { z-index: 10650 !important; }
</style>

<div class="container-fluid pos-container py-3">
    <div class="row h-100 g-3">
        
        <div class="col-lg-8 col-md-7 pos-left-scroll">
            
            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge <?= !empty($activeShift) ? 'bg-success' : 'bg-danger' ?> rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1" id="shift-status-badge">
                        <i class="bi bi-clock-history"></i> <span id="current-shift-name-label"><?= !empty($activeShift) ? htmlspecialchars($activeShift['shift_name']) : 'Chưa Mở Ca' ?></span>
                    </span>
                    <small class="text-muted fw-semibold" id="cashier-name-label">
                        <i class="bi bi-person-badge"></i> Thu ngân: <strong class="text-dark" id="cashier-id-display"><?= !empty($activeShift) ? htmlspecialchars($activeShift['cashier_id']) : '--' ?></strong>
                    </small>
                </div>
                
                <div class="topbar-segmented-control shadow-sm">
                    <button type="button" class="topbar-segmented-btn btn-amber" onclick="openReservationManagerModal()">
                        <i class="bi bi-calendar-event"></i> QUẢN LÝ ĐẶT BÀN
                    </button>
                    <button type="button" class="topbar-segmented-btn" onclick="openOrderHistoryModal()">
                        <i class="bi bi-journal-check"></i> LỊCH SỬ ĐƠN
                    </button>
                    <button type="button" class="topbar-segmented-btn btn-emerald" onclick="openShiftModal()">
                        <i class="bi bi-door-closed"></i> QUẢN LÝ CA & KẾT CA
                    </button>
                </div>
            </div>

            <div class="pos-card p-2 mb-3">
                <div class="order-type-tabs">
                    <button class="order-type-btn active" id="tab-dine-in" onclick="switchOrderType('dine_in')">
                        <i class="bi bi-cup-hot"></i> Tại Chỗ
                    </button>
                    <button class="order-type-btn" id="tab-takeaway" onclick="switchOrderType('takeaway')">
                        <i class="bi bi-bag-check"></i> Mang Về
                    </button>
                    <button class="order-type-btn" id="tab-online" onclick="switchOrderType('online')">
                        <i class="bi bi-phone"></i> Đơn Online App
                    </button>
                </div>

                <div class="online-subtabs d-none" id="online-subtabs-container">
                    <button class="subtab-btn active-grab" id="subtab-grab" onclick="switchOnlinePlatform('grab')">
                        GrabFood
                    </button>
                    <button class="subtab-btn" id="subtab-shopee" onclick="switchOnlinePlatform('shopee')">
                        ShopeeFood
                    </button>
                </div>
            </div>

            <div class="pos-card p-3 mb-3" id="table-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;">SƠ ĐỒ BÀN & KHU VỰC</h6>
                        <button class="btn btn-sm btn-outline-dark px-3 py-1 fw-semibold rounded-pill" style="font-size: 11px;" onclick="openMergeTableModal()">
                            <i class="bi bi-arrow-merge"></i> Gộp Bàn / Hóa Đơn
                        </button>
                        <button class="btn btn-sm btn-outline-dark px-3 py-1 fw-semibold rounded-pill" style="font-size: 11px;" onclick="openSplitTableModal()">
                            <i class="bi bi-arrow-right-left"></i> Chuyển Bàn / Hóa Đơn
                        </button>
                    </div>
                    <div class="d-flex gap-2" style="font-size: 11px;">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-2 py-1">Trống</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill px-2 py-1">Có khách</span>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success rounded-pill px-2 py-1">Đã thanh toán</span>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle rounded-pill px-2 py-1">Đã đặt</span>
                    </div>
                </div>
                
                <?php if (!empty($areas)): ?>
                    <?php foreach ($areas as $area): ?>
                        <?php if (!empty($area['tables'])): ?>
                            <div class="mb-2">
                                <div class="fw-bold text-uppercase text-secondary mb-1" style="font-size: 11px;">
                                    KHU VỰC: <?= htmlspecialchars($area['area_name'] ?? $area['name'] ?? '') ?>
                                </div>
                                <div class="row g-2">
                                    <?php foreach ($area['tables'] as $table): ?>
                                        <?php 
                                            $statusClass = 'status-available';
                                            $statusText  = 'Trống';
                                            if (($table['status'] ?? 0) == 1) {
                                                $statusClass = 'status-occupied';
                                                $statusText  = 'Có khách';
                                            } else if (($table['status'] ?? 0) == 2 || ($table['status'] ?? '') === 'reserved') {
                                                $statusClass = 'status-reserved';
                                                $statusText  = 'Đã đặt';
                                            }
                                            $tName = $table['table_name'] ?? $table['name'] ?? ('Bàn ' . $table['id']);
                                        ?>
                                        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                                            <div class="table-badge <?= $statusClass ?>" 
                                                 id="table-btn-<?= $table['id'] ?>"
                                                 data-area-id="<?= $area['id'] ?>"
                                                 onclick="selectTable(<?= $table['id'] ?>, '<?= htmlspecialchars($tName) ?>')">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="fw-bold text-dark text-truncate" style="font-size: 13px;"><?= htmlspecialchars($tName) ?></div>
                                                </div>
                                                <small class="text-muted d-block status-label" style="font-size: 10px;"><?= $statusText ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-2">Chưa có danh sách Khu vực và Bàn.</div>
                <?php endif; ?>
            </div>

            <div class="pos-card p-3 mb-3 d-none" id="takeaway-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;">DANH SÁCH THẺ MANG VỀ ĐANG PHỤC VỤ</h6>
                    <button class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 py-1 shadow-sm" style="font-size: 11px;" onclick="addNewTakeawayCard()">
                        <i class="bi bi-plus-lg"></i> Tạo Thẻ Mang Về Mới
                    </button>
                </div>
                <div class="row g-2" id="takeaway-cards-grid"></div>
            </div>

            <div class="pos-card p-3 mb-3 d-none" id="online-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;" id="online-section-title">DANH SÁCH ĐƠN APP ONLINE</h6>
                    <button class="btn btn-sm btn-success fw-bold rounded-pill px-3 py-1 shadow-sm" style="font-size: 11px;" onclick="addNewOnlineCard()">
                        <i class="bi bi-plus-lg"></i> Tiếp Nhận Đơn Mới
                    </button>
                </div>
                <div class="row g-2" id="online-cards-grid"></div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                <div class="d-flex gap-2 overflow-auto py-1">
                    <button class="btn cat-btn active-cat" onclick="filterCategory('all', this)">Tất cả</button>
                    <button class="btn cat-btn text-nowrap" onclick="filterCategory('bestseller', this)">Best Seller</button>
                    <button class="btn cat-btn text-nowrap" onclick="filterCategory('combo', this)">Combo Ưu Đãi</button>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <button class="btn cat-btn text-nowrap" onclick="filterCategory(<?= $cat['id'] ?>, this)">
                                <?= htmlspecialchars($cat['category_name'] ?? $cat['name'] ?? '') ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <input type="text" id="search-product" class="form-control rounded-pill px-3 shadow-sm border-0 w-25" placeholder="Tìm sản phẩm..." onkeyup="searchProduct()">
            </div>

            <div class="row g-3" id="product-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <?php 
                            $pName = $product['product_name'] ?? $product['name'] ?? '';
                            $pPrice = $product['price'] ?? 0;
                            $pImg = $product['image'] ?? $product['image_url'] ?? '';
                        ?>
                        <div class="col-6 col-sm-4 col-md-3 product-item" 
                             data-category="<?= $product['category_id'] ?>" 
                             data-name="<?= htmlspecialchars($pName) ?>"
                             data-price="<?= $pPrice ?>">
                            <div class="card h-100 product-card shadow-sm" onclick="handleProductClick(<?= htmlspecialchars(json_encode([
                                'id' => $product['id'],
                                'category_id' => $product['category_id'],
                                'name' => $pName,
                                'price' => $pPrice
                            ])) ?>)">
                                <?php 
                                    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                                    $imgSrc = !empty($pImg) ? $baseUrl . '/uploads/' . $pImg : 'https://placehold.co/150x110?text=No+Image';
                                ?>
                                <div class="product-img-wrapper">
                                    <img src="<?= $imgSrc ?>" class="product-img" alt="<?= htmlspecialchars($pName) ?>" onerror="this.src='https://placehold.co/150x110?text=No+Image'">
                                    <span class="online-price-badge d-none" id="online-badge-<?= $product['id'] ?>">Giá App</span>
                                </div>
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <h6 class="card-title fs-7 fw-bold text-dark text-truncate mb-1" style="font-size: 13px;"><?= htmlspecialchars($pName) ?></h6>
                                    <div class="fw-bold fs-6" style="color: var(--pos-primary);" id="product-price-<?= $product['id'] ?>" data-base-price="<?= $pPrice ?>">
                                        <?= number_format($pPrice) ?> đ
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center text-muted py-5">Chưa có sản phẩm nào trong hệ thống thực đơn.</div>
                <?php endif; ?>
            </div>

        </div>

        <div class="col-lg-4 col-md-5 h-100">
            <div class="pos-right-sticky p-3">
                
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <div>
                        <span class="badge bg-dark text-white mb-1 fw-bold px-2 py-1" id="order-type-badge">TẠI CHỖ</span>
                        <h5 class="fw-bold m-0 text-dark" id="selected-table-name" style="font-size: 16px;">Chưa Chọn Bàn</h5>
                        <small class="text-muted" style="font-size: 11px;">Thu ngân: <span id="right-panel-cashier"><?= !empty($activeShift) ? htmlspecialchars($activeShift['cashier_id']) : '--' ?></span></small>
                        <div id="table-reservation-note" class="text-warning fw-semibold mt-1 d-none" style="font-size: 11px;"></div>
                    </div>
                    <div class="d-flex gap-1 align-items-center">
                        <button class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 py-1 shadow-sm d-none" id="btn-release-table" onclick="releaseCurrentTable()">Trả bàn</button>
                        <button class="btn btn-sm btn-outline-danger border-0 rounded-pill px-2" onclick="clearCart()">Xóa tất cả</button>
                    </div>
                </div>

                <div class="loyalty-card mb-2 border rounded p-2 bg-light" id="loyalty-section">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="fw-bold text-secondary" style="font-size: 11px;"><i class="bi bi-person-heart text-danger"></i> KHÁCH HÀNG THÂN THIẾT</small>
                        <button class="btn btn-link btn-sm p-0 text-decoration-none fw-bold" style="font-size: 11px;" onclick="openRegisterCustomerModal()">+ Thêm Khách</button>
                    </div>
                    <div class="input-group input-group-sm mb-1">
                        <input type="text" id="cust-phone-input" class="form-control" placeholder="Nhập SĐT tìm khách..." onkeypress="if(event.key==='Enter') searchCustomerByPhone()">
                        <button class="btn btn-dark" type="button" onclick="searchCustomerByPhone()"><i class="bi bi-search"></i></button>
                    </div>
                    
                    <div id="cust-info-display" class="d-none flex-column bg-white p-2 rounded border gap-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-dark d-block" style="font-size: 12px;" id="cust-name-val">--</span>
                                <small class="text-muted" style="font-size: 10px;">Điểm khả dụng: <strong class="text-primary" id="cust-points-val">0</strong> pt</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-secondary" id="cust-rank-badge">Bronze</span>
                                <button class="btn btn-xs btn-outline-danger d-block ms-auto mt-1 p-0 px-1" style="font-size: 9px;" onclick="detachCustomer()">Bỏ chọn</button>
                            </div>
                        </div>

                        <!-- Đổi Quà / Trừ Điểm Khách Hàng -->
                        <div class="border-top pt-1 mt-1">
                            <input type="hidden" id="redeem-points-input" value="0">
                            <div class="input-group input-group-sm my-1">
                                <span class="input-group-text fw-bold" style="font-size:10px;">Đổi điểm</span>
                                <input type="number" id="manual-redeem-pts" class="form-control" placeholder="Số pt..." min="0" style="font-size:11px;">
                                <button class="btn btn-warning text-dark fw-bold" type="button" style="font-size:10px;" onclick="applyManualPointsRedeem()">ĐỔI QUÀ</button>
                            </div>
                            <small class="text-muted d-block" style="font-size: 9px;">* Tỷ lệ 1 pt = 100đ (10 pt = 1.000đ). Điểm dùng để đổi voucher/quà tặng tại quầy.</small>
                            <small class="text-success fw-bold d-block mt-1 d-none" style="font-size: 10px;" id="loyalty-discount-msg">Đã giảm: -0đ</small>
                        </div>
                    </div>
                </div>

                <div class="cart-items-list mb-2" id="cart-items">
                    <div class="text-center text-muted my-5 py-4">
                        <span class="d-block text-secondary fw-semibold" style="font-size: 13px;">Vui lòng chọn bàn/thẻ vị trí và chọn món ăn</span>
                    </div>
                </div>

                <div class="border-top pt-2">
                    <div class="d-flex justify-content-between text-secondary mb-1" style="font-size: 12px;">
                        <span>Tạm tính:</span>
                        <span id="sub-total" class="fw-semibold">0 đ</span>
                    </div>
                    <!-- THUẾ VAT ĐỘNG -->
                    <div class="d-flex justify-content-between text-secondary mb-1" style="font-size: 12px;">
                        <span>
                            Thuế VAT (<span id="vat-label-display">8</span>%):
                            <?php if ($userRole === 'admin'): ?>
                                <button type="button" class="btn btn-link btn-xs p-0 text-primary text-decoration-none fw-bold ms-1" onclick="adminUpdateVatSetting()" style="font-size: 10px;" title="Chỉnh sửa % VAT hệ thống (Admin)">
                                    <i class="bi bi-pencil-square"></i> Đổi
                                </button>
                            <?php endif; ?>
                        </span>
                        <span id="vat-total" class="fw-semibold">0 đ</span>
                    </div>
                    <div class="d-flex justify-content-between text-success mb-2 d-none" id="loyalty-discount-row" style="font-size: 12px;">
                        <span>Chiết khấu & Ưu đãi:</span>
                        <span id="loyalty-discount-total" class="fw-bold">-0 đ</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-5 text-dark border-top pt-2 mb-3">
                        <span>TỔNG CỘNG:</span>
                        <span class="text-danger fw-extrabold" id="grand-total">0 đ</span>
                    </div>

                    <div class="mb-2" id="box-btn-ready">
                        <button class="btn btn-sm btn-outline-success fw-bold w-100 rounded-3 d-inline-flex align-items-center justify-content-center gap-1" id="btn-ready-notify" onclick="notifyKitchenReady()" disabled>
                            <i class="bi bi-bell"></i> THÔNG BÁO HOÀN TẤT & RUNG THẺ
                        </button>
                    </div>
                    
                    <div class="d-flex gap-2 mb-2">
                        <button class="btn btn-outline-dark fw-bold py-2 px-2 rounded-3 w-100 d-inline-flex align-items-center justify-content-center gap-1" id="btn-kitchen" onclick="sendToKitchen()" disabled style="font-size: 12px;">
                            <i class="bi bi-printer"></i> BÁO BẾP / IN TEM
                        </button>
                        <button class="btn btn-success fw-bold py-2 px-2 rounded-3 w-50 shadow d-inline-flex align-items-center justify-content-center gap-1" id="btn-pay-direct" onclick="openCheckoutModal(true)" disabled style="font-size: 12px;">
                            <i class="bi bi-lightning-charge-fill"></i> THANH TOÁN NGAY
                        </button>
                    </div>

                    <div>
                        <button class="btn btn-dark fw-bold py-2 px-3 rounded-3 w-100 shadow d-inline-flex align-items-center justify-content-center gap-1" id="btn-checkout" onclick="openCheckoutModal(false)" disabled>
                            <i class="bi bi-credit-card"></i> THANH TOÁN / CHỐT ĐƠN
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- MODAL 1: OPTION MÓN -->
<div class="modal fade" id="productOptionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h6 class="modal-header-title fw-bold text-dark m-0" id="optionModalTitle">Tùy Chọn Món</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="drink-options-wrapper">
            <div class="mb-3" id="size-options-container">
                <label class="fw-bold text-secondary d-block mb-1" style="font-size: 12px;">CHỌN SIZE SẢN PHẨM</label>
                <div class="option-pill-group" id="size-options">
                    <span class="option-pill selected" data-extra="0" onclick="selectPill(this, 'size')">Size M (+0đ)</span>
                    <span class="option-pill" data-extra="6000" onclick="selectPill(this, 'size')">Size L (+6,000đ)</span>
                </div>
            </div>
            <div class="mb-3" id="sugar-options-container">
                <label class="fw-bold text-secondary d-block mb-1" style="font-size: 12px;">MỨC ĐƯỜNG</label>
                <div class="option-pill-group" id="sugar-options">
                    <span class="option-pill selected" onclick="selectPill(this, 'sugar')">100% Đường</span>
                    <span class="option-pill" onclick="selectPill(this, 'sugar')">70% Đường</span>
                    <span class="option-pill" onclick="selectPill(this, 'sugar')">50% Đường</span>
                    <span class="option-pill" onclick="selectPill(this, 'sugar')">Không Đường</span>
                </div>
            </div>
            <div class="mb-3" id="ice-options-container">
                <label class="fw-bold text-secondary d-block mb-1" style="font-size: 12px;">MỨC ĐÁ</label>
                <div class="option-pill-group" id="ice-options">
                    <span class="option-pill selected" onclick="selectPill(this, 'ice')">100% Đá</span>
                    <span class="option-pill" onclick="selectPill(this, 'ice')">70% Đá</span>
                    <span class="option-pill" onclick="selectPill(this, 'ice')">50% Đá</span>
                    <span class="option-pill" onclick="selectPill(this, 'ice')">Không Đá</span>
                </div>
            </div>

            <div class="mb-3" id="topping-dynamic-container">
                <label class="fw-bold text-secondary d-block mb-1" style="font-size: 12px;">CHỌN TOPPING ĐI KÈM</label>
                <div class="d-flex flex-wrap gap-2" id="dynamic-topping-list"></div>
            </div>
        </div>

        <div class="mb-2">
            <label class="fw-bold text-secondary d-block mb-1" style="font-size: 12px;">GHI CHÚ ĐẶC BIỆT</label>
            <input type="text" id="item-note" class="form-control rounded-3" placeholder="Nhập ghi chú chế biến...">
        </div>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-dark w-100 fw-bold py-2 rounded-3" onclick="confirmAddToCart()">XÁC NHẬN THÊM MÓN</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL ĐĂNG KÝ KHÁCH HÀNG THÀNH VIÊN MỚI -->
<div class="modal fade" id="registerCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h6 class="fw-bold text-dark m-0"><i class="bi bi-person-plus-fill text-primary"></i> ĐĂNG KÝ THÀNH VIÊN</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">SỐ ĐIỆN THOẠI (*)</label>
            <input type="text" id="reg-cust-phone" class="form-control rounded-3" placeholder="Nhập số điện thoại..." maxlength="10">
        </div>
        <div class="mb-2">
            <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">HỌ VÀ TÊN (*)</label>
            <input type="text" id="reg-cust-name" class="form-control rounded-3" placeholder="Nhập họ và tên...">
        </div>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-dark w-100 fw-bold py-2 rounded-3" onclick="confirmRegisterCustomer()">LƯU & CHỌN KHÁCH HÀNG</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CẤU HÌNH THUẾ VAT ĐỘNG (DÀNH CHO ADMIN) -->
<div class="modal fade" id="vatSettingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h6 class="fw-bold text-dark m-0 d-inline-flex align-items-center gap-2">
            <i class="bi bi-gear-fill text-primary"></i> CẤU HÌNH THUẾ VAT
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">MỨC THUẾ VAT HỆ THỐNG (%) (*)</label>
            <div class="input-group">
                <input type="number" id="vat-setting-input" class="form-control rounded-3 fw-bold text-primary" placeholder="8" min="0" max="100" step="0.5">
                <span class="input-group-text fw-bold">%</span>
            </div>
            <small class="text-muted d-block mt-1" style="font-size: 10px;">
                Mức thuế này sẽ áp dụng trực tiếp cho tất cả các đơn hàng mới.
            </small>
        </div>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-dark w-100 fw-bold py-2 rounded-3" onclick="saveVatSettingFromModal()">
            LƯU CẤU HÌNH VAT
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL 2: IN CHUỖI TEM DÁN LY -->
<div class="modal fade" id="stickerPrintModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg rounded-4 p-3 text-center">
        <h6 class="fw-bold mb-3 d-inline-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-printer-fill text-dark"></i> TEM CHẾ BIẾN DÁN LY
        </h6>
        <div class="sticker-list-container mb-3" id="sticker-list-content"></div>
        <button class="btn btn-success fw-bold w-100 rounded-3 mb-2 d-inline-flex align-items-center justify-content-center gap-1" onclick="executeStickerPrint()">
            <i class="bi bi-check2-circle"></i> IN TẤT CẢ TEM MỚI
        </button>
        <button class="btn btn-outline-secondary w-100 fw-bold rounded-3" data-bs-dismiss="modal">ĐÓNG</button>
    </div>
  </div>
</div>

<!-- MODAL 3: GỘP BÀN & BILL -->
<div class="modal fade" id="mergeTableModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-merge"></i> GỘP BÀN / CHUYỂN HÓA ĐƠN BÀN HIỆN TẠI
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHỌN BÀN NGUỒN (CHUYỂN HÓA ĐƠN ĐI)</label>
            <select id="merge-source-table" class="form-select rounded-3" onchange="checkMergeSameArea()"></select>
        </div>
        <div class="mb-3">
            <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHỌN BÀN ĐÍCH (NHẬN TẤT CẢ HÓA ĐƠN)</label>
            <select id="merge-target-table" class="form-select rounded-3" onchange="checkMergeSameArea()"></select>
        </div>
        <div class="form-check" id="same-area-keep-source-box">
            <input class="form-check-input" type="checkbox" id="chk-keep-source-state">
            <label class="form-check-label fw-semibold text-dark" style="font-size: 12px;" for="chk-keep-source-state">
                Giữ trạng thái có khách cho bàn nguồn
            </label>
        </div>
      </div>
      <div class="modal-footer border-top-0">
        <button class="btn btn-dark w-100 fw-bold py-2 rounded-3" onclick="confirmMergeTable()">XÁC NHẬN GỘP BÀN & HÓA ĐƠN</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL 4: CHUYỂN BÀN & CHUYỂN BILL -->
<div class="modal fade" id="splitTableModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-right-left"></i> CHUYỂN BÀN / CHUYỂN MÓN SANG HÓA ĐƠN MỚI
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHỌN BÀN CẦN CHUYỂN MÓN</label>
            <select id="split-source-table" class="form-select rounded-3" onchange="loadSplitItems()"></select>
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="fw-bold text-secondary m-0" style="font-size: 12px;">DANH SÁCH MÓN CHUYỂN SANG BÀN MỚI</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="chk-select-all-split" onchange="toggleSelectAllSplitItems(this)">
                    <label class="form-check-label fw-semibold text-dark" style="font-size: 11px;" for="chk-select-all-split">Chọn Tất Cả</label>
                </div>
            </div>
            <div id="split-items-list" class="border rounded p-2" style="max-height: 180px; overflow-y: auto;">
                <small class="text-muted d-block text-center py-3">Chọn bàn để xem danh sách món ăn</small>
            </div>
        </div>
        <div class="mb-2">
            <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">BÀN ĐÍCH NHẬN MÓN CHUYỂN (BÀN TRỐNG HỢP LỆ)</label>
            <select id="split-target-table" class="form-select rounded-3"></select>
        </div>
      </div>
      <div class="modal-footer border-top-0">
        <button class="btn btn-dark w-100 fw-bold py-2 rounded-3" onclick="confirmSplitTable()">XÁC NHẬN CHUYỂN MÓN</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL QUẢN LÝ ĐẶT BÀN -->
<div class="modal fade" id="reservationManagerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2">
            <i class="bi bi-calendar-check text-warning fs-5"></i> QUẢN LÝ ĐẶT BÀN TRƯỚC HẸN KHÁCH
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <ul class="nav nav-tabs nav-fill mb-3">
          <li class="nav-item">
            <button class="nav-link active fw-bold" id="res-tab-list-btn" data-bs-toggle="tab" data-bs-target="#tab-res-list">
                <i class="bi bi-list-stars me-1"></i> DANH SÁCH BÀN ĐANG ĐẶT
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold" id="res-tab-form-btn" data-bs-toggle="tab" data-bs-target="#tab-res-form">
                <i class="bi bi-plus-circle me-1"></i> ĐẶT BÀN MỚI
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade show active" id="tab-res-list">
            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                <span class="fw-bold text-secondary" style="font-size: 12px;">CÁC LỊCH ĐẶT BÀN HIỆN CÓ</span>
                <span class="badge bg-amber-subtle text-amber fw-bold" id="resCountBadgeDisplay">0 Lượt đặt</span>
            </div>
            <div class="table-responsive border rounded-3" style="max-height: 320px;">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-3 py-2">Bàn</th>
                            <th class="py-2">Tên Khách</th>
                            <th class="py-2">Số Điện Thoại</th>
                            <th class="py-2">Thời Gian Hẹn</th>
                            <th class="py-2">Ghi Chú</th>
                            <th class="text-center pe-3 py-2">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="reservation-manager-tbody">
                        <tr><td colspan="6" class="text-center text-muted py-4">Chưa có thông tin đặt bàn trước.</td></tr>
                    </tbody>
                </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-res-form">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHỌN BÀN ĐỂ ĐẶT (HIỂN THỊ TẤT CẢ)</label>
                    <select id="reserve-target-table" class="form-select rounded-3" onchange="checkReservationTableWarning(this)"></select>
                    <div id="reservation-table-alert-box" class="text-danger fw-bold mt-1 d-none" style="font-size: 11px;"></div>
                </div>
                <div class="col-md-6">
                    <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">TÊN KHÁCH HÀNG</label>
                    <input type="text" id="reserve-customer-name" class="form-control rounded-3" placeholder="Nhập tên khách hàng...">
                </div>
                <div class="col-md-6">
                    <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">SỐ ĐIỆN THOẠI KHÁCH</label>
                    <input type="text" id="reserve-customer-phone" class="form-control rounded-3" placeholder="Nhập số điện thoại..." maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                <div class="col-md-3">
                    <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">NGÀY HẸN ĐẾN</label>
                    <input type="date" id="reserve-date" class="form-control rounded-3">
                </div>
                <div class="col-md-3">
                    <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">GIỜ HẸN ĐẾN</label>
                    <input type="time" id="reserve-time" class="form-control rounded-3">
                </div>
                <div class="col-12">
                    <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">GHI CHÚ ĐẶT BÀN (TRANG TRÍ, SINH NHẬT...)</label>
                    <input type="text" id="reserve-note" class="form-control rounded-3" placeholder="Nhập ghi chú chi tiết nếu có...">
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-warning w-100 fw-bold py-2 rounded-3 text-dark" onclick="confirmReserveTable()">XÁC NHẬN ĐẶT BÀN</button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- MODAL HỦY ĐẶT BÀN -->
<div class="modal fade" id="cancelReserveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2">
            <i class="bi bi-x-circle text-danger"></i> LÝ DO HỦY ĐẶT BÀN <span id="cancel-table-title-id" class="text-primary"></span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="cancel-target-table-id">
        <div class="mb-3">
            <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHỌN LÝ DO HỦY</label>
            <select id="cancel-reserve-reason-select" class="form-select rounded-3" onchange="toggleCancelOtherReason(this)">
                <option value="Khách báo hủy lịch">Khách báo hủy lịch</option>
                <option value="Trùng lịch / Hết bàn trống">Trùng lịch / Hết bàn trống</option>
                <option value="Khách không đến đúng giờ (Quá 30p)">Khách không đến đúng giờ (Quá 30 phút)</option>
                <option value="other">Lý do khác...</option>
            </select>
        </div>
        <div class="mb-2 d-none" id="cancel-other-reason-box">
            <input type="text" id="cancel-reserve-other-reason" class="form-control rounded-3" placeholder="Nhập lý do hủy chi tiết...">
        </div>
      </div>
      <div class="modal-footer border-top-0">
        <button class="btn btn-danger w-100 fw-bold py-2 rounded-3" onclick="confirmCancelReserve()">XÁC NHẬN HỦY ĐẶT BÀN</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL LỊCH SỬ ĐƠN HÀNG -->
<div class="modal fade" id="orderHistoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-receipt-cutoff fs-5 text-primary"></i>
            <h6 class="fw-bold m-0 text-dark">LỊCH SỬ ĐƠN HÀNG TRONG CA LÀM VIỆC</h6>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3 py-3">Mã Đơn / Bàn</th>
                        <th class="py-3">Hình Thức Bán</th>
                        <th class="py-3">Thời Gian</th>
                        <th class="py-3">Thành Tiền</th>
                        <th class="text-center pe-3 py-3">Thao Tác</th>
                    </tr>
                </thead>
                <tbody id="order-history-list">
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 text-secondary d-block mb-2"></i>
                            Chưa có đơn hàng nào được hoàn tất trong ca làm việc này.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer border-top-0 bg-light rounded-bottom-4 py-2">
        <button type="button" class="btn btn-outline-secondary btn-sm fw-bold px-4 rounded-3" data-bs-dismiss="modal">ĐÓNG</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL XEM TRƯỚC VÀ IN LẠI BILL LỊCH SỬ -->
<div class="modal fade" id="reprintBillModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 p-3 text-center">
        <h6 class="fw-bold mb-3 d-inline-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-printer-fill text-dark"></i> XEM TRƯỚC HÓA ĐƠN IN LẠI
        </h6>
        <div class="receipt-mockup mb-3" id="reprint-receipt-content"></div>
        <button class="btn btn-dark fw-bold w-100 rounded-3 mb-2 d-inline-flex align-items-center justify-content-center gap-1" onclick="confirmReprintBill()">
            <i class="bi bi-printer"></i> ĐỒNG Ý IN LẠI HÓA ĐƠN
        </button>
        <button class="btn btn-outline-secondary w-100 fw-bold rounded-3" data-bs-dismiss="modal">ĐÓNG</button>
    </div>
  </div>
</div>

<!-- MODAL THANH TOÁN & CHỐT ĐƠN -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2" id="checkoutModalTitle">
            <i class="bi bi-credit-card-2-front text-success"></i> THANH TOÁN & IN HÓA ĐƠN
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6 border-end pr-3">
                <div class="text-center mb-3">
                    <small class="text-muted d-block">TỔNG TIỀN ĐƠN HÀNG</small>
                    <h3 class="fw-extrabold text-danger m-0" id="modal-pay-amount">0 đ</h3>
                </div>

                <div id="checkout-standard-payment-box">
                    <ul class="nav nav-pills nav-fill mb-3">
                      <li class="nav-item">
                        <button class="nav-link active fw-bold d-inline-flex align-items-center justify-content-center gap-1" id="pills-cash-tab" data-bs-toggle="pill" data-bs-target="#pills-cash">
                            <i class="bi bi-cash-stack"></i> Tiền Mặt
                        </button>
                      </li>
                      <li class="nav-item">
                        <button class="nav-link fw-bold d-inline-flex align-items-center justify-content-center gap-1" id="pills-qr-tab" data-bs-toggle="pill" data-bs-target="#pills-qr">
                            <i class="bi bi-qr-code-scan"></i> VietQR
                        </button>
                      </li>
                    </ul>

                    <div class="tab-content">
                      <div class="tab-pane fade show active" id="pills-cash">
                        <div class="mb-3">
                            <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">TIỀN KHÁCH ĐƯA</label>
                            <input type="number" id="cash-given" class="form-control form-control-lg rounded-3 fw-bold" placeholder="0" oninput="calculateChange()">
                            <div id="inline-payment-error" class="text-danger fw-bold mt-2 d-none" style="font-size: 12px;">
                                <i class="bi bi-exclamation-triangle-fill"></i> Số tiền khách đưa chưa đủ để hoàn tất thanh toán!
                            </div>
                        </div>
                        <div class="d-flex justify-content-between fw-bold fs-6 text-dark border-top pt-2">
                            <span>TIỀN THỪA KHÁCH:</span>
                            <span class="text-success" id="cash-change">0 đ</span>
                        </div>
                      </div>
                      <div class="tab-pane fade text-center" id="pills-qr">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=QUAN_CA_PHE_BAT_ON_VIETQR" class="img-fluid rounded-3 mb-2 border p-2">
                        <small class="d-block text-muted">Vui lòng quét mã QR để chuyển khoản thanh toán</small>
                      </div>
                    </div>
                </div>

                <div id="checkout-online-app-box" class="d-none">
                    <div class="alert alert-success border-success bg-success bg-opacity-10 py-2 mb-3" style="font-size: 11px;">
                        <i class="bi bi-check-circle-fill text-success me-1"></i> Đơn hàng đã được thanh toán trực tiếp trên ứng dụng. Thu ngân không thu tiền mặt từ Shipper.
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">MÃ ĐƠN TÊN SÀN (GRAB/SHOPEE) (*)</label>
                        <input type="text" id="app-order-code-input" class="form-control rounded-3" placeholder="Nhập mã đơn trên ứng dụng (VD: MEX-882910)...">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">TÊN TÀI XẾ (SHIPPER)</label>
                            <input type="text" id="app-driver-name-input" class="form-control rounded-3" placeholder="Nhập tên tài xế...">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">SĐT TÀI XẾ</label>
                            <input type="text" id="app-driver-phone-input" class="form-control rounded-3" placeholder="SĐT tài xế..." maxlength="10">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">TỶ LỆ CHIẾT KHẤU SÀN (%)</label>
                        <select id="app-commission-rate-select" class="form-select rounded-3" onchange="calculateAppNetPrice()">
                            <option value="20">20% (Chiết khấu tiêu chuẩn)</option>
                            <option value="25">25% (Chiết khấu cao điểm)</option>
                            <option value="15">15% (Khuyến mãi đối tác)</option>
                            <option value="0">0% (Đơn giao hàng trực tiếp)</option>
                        </select>
                    </div>
                    <div class="p-2 bg-light rounded border">
                        <div class="d-flex justify-content-between text-muted" style="font-size: 11px;">
                            <span>Phí hoa hồng sàn trừ:</span>
                            <span class="fw-bold text-danger" id="app-commission-fee-val">0 đ</span>
                        </div>
                        <div class="d-flex justify-content-between text-dark fw-bold border-top pt-1 mt-1" style="font-size: 13px;">
                            <span>DOANH THU THỰC NHẬN:</span>
                            <span class="text-success" id="app-net-revenue-val">0 đ</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-md-6">
                <div class="receipt-mockup" id="receipt-preview-content"></div>
            </div>
        </div>
      </div>
      <div class="modal-footer border-top-0">
        <button class="btn btn-dark w-100 fw-bold py-2 rounded-3 d-inline-flex align-items-center justify-content-center gap-2" id="btn-confirm-checkout-action" onclick="processCheckout()">
            <i class="bi bi-printer"></i> HOÀN TẤT THANH TOÁN & IN HÓA ĐƠN
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL QUẢN LÝ CA LÀM VIỆC & MỞ/KẾT CA -->
<div class="modal fade" id="shiftModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2" id="shiftModalTitle">
            <i class="bi bi-clock-history text-primary"></i> QUẢN LÝ CA LÀM VIỆC
        </h6>
        <button type="button" class="btn-close" id="shift-modal-close-btn" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <div id="shift-open-form">
            <div class="alert alert-light border text-muted py-2 mb-3" style="font-size: 12px;">
                <i class="bi bi-info-circle text-primary me-1"></i> Hệ thống vận hành liên tục. Vui lòng chọn Ca làm việc phù hợp, nhập số tiền thối đầu ca và mật khẩu xác thực thu ngân.
            </div>
            <div class="mb-3">
                <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHỌN CA LÀM VIỆC KHỚP GIỜ THỰC TẾ (*)</label>
                <select id="shift-type-select" class="form-select rounded-3 fw-bold">
                    <option value="morning">Ca Sáng (06:00 - 14:00)</option>
                    <option value="afternoon">Ca Chiều (14:00 - 22:00)</option>
                    <option value="night">Ca Đêm (22:00 - 06:00)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">TIỀN THỐI ĐẦU CA (INITIAL CASH)</label>
                <input type="number" id="shift-initial-cash" class="form-control rounded-3 fw-bold text-success" placeholder="Nhập số tiền mặt đầu ca..." value="0">
            </div>
            <div class="mb-3">
                <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">MÃ NHÂN VIÊN THU NGÂN</label>
                <input type="text" id="shift-cashier-id" class="form-control rounded-3 fw-semibold" placeholder="Nhập mã nhân viên..." value="NV01">
            </div>
            <div class="mb-3">
                <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">MẬT KHẨU XÁC THỰC CA</label>
                <input type="password" id="shift-cashier-pass" class="form-control rounded-3" placeholder="Nhập mật khẩu xác thực ca...">
            </div>
            <button class="btn btn-dark w-100 fw-bold py-2 rounded-3 d-inline-flex align-items-center justify-content-center gap-2" onclick="confirmOpenShift()">
                <i class="bi bi-box-arrow-in-right"></i> MỞ CA & BẮT ĐẦU BÁN HÀNG
            </button>
        </div>

        <div id="shift-close-summary-tabs" class="d-none">
            <ul class="nav nav-tabs nav-fill mb-3">
              <li class="nav-item">
                <button class="nav-link active fw-bold" id="shift-tab-stats-btn" data-bs-toggle="tab" data-bs-target="#tab-shift-stats">
                    <i class="bi bi-graph-up me-1"></i> BÁO CÁO DOANH THU CA
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link fw-bold" id="shift-tab-print-btn" data-bs-toggle="tab" data-bs-target="#tab-shift-print">
                    <i class="bi bi-printer me-1"></i> KIỂM KÊ KÉT & KẾT CA
                </button>
              </li>
            </ul>

            <div class="tab-content">
              <div class="tab-pane fade show active" id="tab-shift-stats">
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <div class="shift-card">
                            <div class="shift-card-title">TỔNG DOANH THU CA</div>
                            <div class="shift-card-value text-danger" id="stat-total-revenue">0 đ</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="shift-card">
                            <div class="shift-card-title">DOANH THU TIỀN MẶT</div>
                            <div class="shift-card-value text-primary" id="stat-cash-sales">0 đ</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="shift-card">
                            <div class="shift-card-title">DOANH THU CHUYỂN KHOẢN</div>
                            <div class="shift-card-value text-success" id="stat-bank-sales">0 đ</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="shift-card">
                            <div class="shift-card-title">DOANH THU APP ONLINE</div>
                            <div class="shift-card-value text-warning" id="stat-app-sales">0 đ</div>
                        </div>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <div class="shift-card">
                            <div class="shift-card-title">TỔNG SỐ ĐƠN HOÀN THÀNH</div>
                            <div class="shift-card-value text-dark" id="stat-total-orders">0 Đơn</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="shift-card">
                            <div class="shift-card-title">SỐ LƯỢNG ĐỒ UỐNG BÁN RA</div>
                            <div class="shift-card-value text-dark" id="stat-total-cups">0 Ly</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="shift-card">
                            <div class="shift-card-title">SỐ LƯỢNG BÁNH BÁN RA</div>
                            <div class="shift-card-value text-dark" id="stat-total-cakes">0 Bánh</div>
                        </div>
                    </div>
                </div>
              </div>

              <div class="tab-pane fade" id="tab-shift-print">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">TIỀN MẶT THỰC TẾ TRONG KÉT (*)</label>
                        <input type="number" id="shift-ending-cash-actual" class="form-control form-control-lg rounded-3 fw-bold text-primary" placeholder="0" oninput="calculateShiftDifference()">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHÊNH LỆCH KÉT (LỆCH TIỀN MẶT)</label>
                        <div class="form-control form-control-lg rounded-3 fw-bold bg-light" id="shift-cash-diff-display">0 đ</div>
                    </div>
                </div>
                <div class="receipt-mockup text-start mb-3" id="shift-report-thermal-preview"></div>
                <button class="btn btn-danger w-100 fw-bold py-2 rounded-3 d-inline-flex align-items-center justify-content-center gap-2" onclick="confirmCloseShift()">
                    <i class="bi bi-lock-fill"></i> XÁC NHẬN KẾT CA & BÀN GIAO
                </button>
              </div>
            </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- MODAL ALERT ALERT -->
<div class="modal fade" id="customAlertModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-3">
      <div class="modal-body p-0">
        <div class="alert-icon-circle" id="alertIconCircle">
            <i class="bi bi-clock-history" id="alertIcon"></i>
        </div>
        <h5 class="fw-bold text-dark mb-2" id="alertTitle">Thông Báo Hệ Thống</h5>
        <div class="small text-secondary mb-4" id="alertMessage">Nội dung thông báo...</div>
        <div id="alert-action-buttons">
            <button type="button" class="btn btn-dark w-100 fw-bold rounded-3 py-2" data-bs-dismiss="modal">ĐỒNG Ý</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let currentOrderType = 'dine_in';
let onlinePlatform = 'grab';
let currentTableId = null;

let takeawayCounter = 1;
let onlineGrabCounter = 101;
let onlineShopeeCounter = 101;

let activeTakeawayCards = ['MV-01'];
let activeGrabCards = ['GRAB-101'];
let activeShopeeCards = ['SHOPEE-101'];

let activeOrders = {}; 
let tableReservationData = {}; 
let tableMergeSourcesMap = {}; 
let tableMergedTargetMap = {}; 
let completedOrdersHistory = [];
let cart = [];
let selectedProductForModal = null;
let isShiftOpened = false;
let isPayDirectMode = false;

let selectedCustomer = null;
let registerCustomerModal = null;
let appliedPointsDiscount = 0;
let pointsToRedeem = 0;
let allTablesList = [];

// THUẾ VAT ĐỘNG
let currentVatPercent = 8;
let vatSettingModal = null;
const currentUserRole = <?= json_encode($userRole) ?>;

// BÀN XOAY TUA
let turnaroundTablesMap = {};

let currentShiftInfo = { shift_id: null, cashierId: '', shiftType: '', shiftName: '', initialCash: 0 };
let selectedOrderForReprint = null;

const DB_TOPPING_CAT_ID = <?= json_encode($toppingCatId) ?>;

let shiftStats = { 
    totalRevenue: 0, 
    offlineRevenue: 0,
    onlineRevenue: 0,
    totalCashSales: 0,
    totalBankSales: 0,
    totalAppSales: 0,
    totalOrders: 0,
    totalCups: 0,
    totalCakes: 0
};

let optionModal, stickerPrintModal, checkoutModal, shiftModal, mergeTableModal, splitTableModal, reservationManagerModal, cancelReserveModal, orderHistoryModal, reprintBillModal, customAlertModal;

document.addEventListener('DOMContentLoaded', function() {
    optionModal = new bootstrap.Modal(document.getElementById('productOptionModal'));
    stickerPrintModal = new bootstrap.Modal(document.getElementById('stickerPrintModal'));
    checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
    shiftModal = new bootstrap.Modal(document.getElementById('shiftModal'));
    mergeTableModal = new bootstrap.Modal(document.getElementById('mergeTableModal'));
    splitTableModal = new bootstrap.Modal(document.getElementById('splitTableModal'));
    reservationManagerModal = new bootstrap.Modal(document.getElementById('reservationManagerModal'));
    cancelReserveModal = new bootstrap.Modal(document.getElementById('cancelReserveModal'));
    orderHistoryModal = new bootstrap.Modal(document.getElementById('orderHistoryModal'));
    reprintBillModal = new bootstrap.Modal(document.getElementById('reprintBillModal'));
    customAlertModal = new bootstrap.Modal(document.getElementById('customAlertModal'));

    let regElem = document.getElementById('registerCustomerModal');
    if (regElem) registerCustomerModal = new bootstrap.Modal(regElem);

    let vatElem = document.getElementById('vatSettingModal');
    if (vatElem) vatSettingModal = new bootstrap.Modal(vatElem);

    restoreShiftFromSession();
    checkOpenShiftFromDB();
    fetchReservationsFromDB();
    
    setInterval(fetchReservationsFromDB, 30000);
    setInterval(checkShiftTimeoutStatus, 60000);

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            restoreShiftFromSession();
            checkOpenShiftFromDB();
        }
    });
});

function restoreShiftFromSession() {
    try {
        let savedShift = sessionStorage.getItem('pos_active_shift');
        if (savedShift) {
            let shiftData = JSON.parse(savedShift);
            isShiftOpened = true;
            currentShiftInfo = shiftData;

            document.getElementById('shift-status-badge').className = "badge bg-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
            document.getElementById('current-shift-name-label').innerText = currentShiftInfo.shiftName;
            document.getElementById('cashier-id-display').innerText = currentShiftInfo.cashierId;
            document.getElementById('right-panel-cashier').innerText = currentShiftInfo.cashierId;
        }
    } catch (e) {}
}

// ĐỒNG BỘ DOANH THU CA VÀ % VAT TỪ CSDL KHI F5 HOẶC LOAD TRANG
async function checkOpenShiftFromDB() {
    let savedShift = sessionStorage.getItem('pos_active_shift');
    if (savedShift) {
        try {
            currentShiftInfo = JSON.parse(savedShift);
            isShiftOpened = true;

            document.getElementById('shift-status-badge').className = "badge bg-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
            document.getElementById('current-shift-name-label').innerText = currentShiftInfo.shiftName;
            document.getElementById('cashier-id-display').innerText = currentShiftInfo.cashierId;
            document.getElementById('right-panel-cashier').innerText = currentShiftInfo.cashierId;
        } catch (e) {}
    }

    try {
        let res = await fetch('<?= BASE_URL ?>/order/getShiftStatus').then(r => r.json());
        
        // Load VAT động
        if (res.vat_percent !== undefined) {
            currentVatPercent = parseFloat(res.vat_percent);
            let vatLbl = document.getElementById('vat-label-display');
            if (vatLbl) vatLbl.innerText = currentVatPercent;
        }

        if (res.success && res.has_open_shift && res.shift) {
            isShiftOpened = true;
            currentShiftInfo = {
                shift_id: res.shift.id,
                cashierId: res.shift.cashier_id || 'NV01',
                shiftType: res.shift.shift_type || 'morning',
                shiftName: res.shift.shift_name || 'Ca Sáng',
                initialCash: parseFloat(res.shift.initial_cash || 0)
            };

            sessionStorage.setItem('pos_active_shift', JSON.stringify(currentShiftInfo));

            // ĐỒNG BỘ BÁO CÁO DOANH THU CA KHI F5
            if (res.stats) {
                shiftStats.totalRevenue   = parseFloat(res.stats.total_revenue || 0);
                shiftStats.offlineRevenue = parseFloat(res.stats.offline_revenue || 0);
                shiftStats.onlineRevenue  = parseFloat(res.stats.online_revenue || 0);
                shiftStats.totalCashSales = parseFloat(res.stats.total_cash_sales || 0);
                shiftStats.totalBankSales = parseFloat(res.stats.total_bank_sales || 0);
                shiftStats.totalAppSales  = parseFloat(res.stats.total_app_sales || 0);
                shiftStats.totalOrders    = parseInt(res.stats.total_orders || 0);
                shiftStats.totalCups      = parseInt(res.stats.total_cups || 0);
                shiftStats.totalCakes     = parseInt(res.stats.total_cakes || 0);
            }

            document.getElementById('shift-status-badge').className = "badge bg-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
            document.getElementById('current-shift-name-label').innerText = currentShiftInfo.shiftName;
            document.getElementById('cashier-id-display').innerText = currentShiftInfo.cashierId;
            document.getElementById('right-panel-cashier').innerText = currentShiftInfo.cashierId;

            checkShiftTimeoutStatus();
        } else {
            if (!sessionStorage.getItem('pos_active_shift')) {
                isShiftOpened = false;
                openShiftModal();
            }
        }
    } catch (err) {
        console.error("Lỗi đồng bộ ca:", err);
    }
}

function checkShiftTimeoutStatus() {
    if (!isShiftOpened || !currentShiftInfo.shiftType) return;

    fetch('<?= BASE_URL ?>/order/checkShiftTimeout?shift_type=' + currentShiftInfo.shiftType)
        .then(res => res.json())
        .then(res => {
            if (res.timeout) {
                showCustomAlert(res.message, "Hết Giờ Ca Làm Việc", "danger");
                document.getElementById('btn-checkout').disabled = true;
                document.getElementById('btn-pay-direct').disabled = true;
            }
        });
}

function fetchReservationsFromDB() {
    fetch('<?= BASE_URL ?>/order/getReservations')
        .then(res => res.json())
        .then(res => {
            if (res.success && res.tables) {
                allTablesList = res.tables;
                let now = new Date();

                document.querySelectorAll('#table-section .table-badge').forEach(badge => {
                    let tid = badge.id.replace('table-btn-', '');
                    let foundInDb = res.tables.some(t => String(t.table_id) === String(tid) && t.reservation_id);
                    
                    let dbTable = res.tables.find(t => String(t.table_id) === String(tid));
                    if (dbTable && parseInt(dbTable.table_status) === 0 && !activeOrders[tid] && !foundInDb) {
                        badge.className = "table-badge status-available";
                        badge.querySelector('.status-label').innerText = "Trống";
                        delete tableReservationData[tid];
                    }

                    if (badge.classList.contains('status-reserved') && !foundInDb) {
                        badge.className = "table-badge status-available";
                        badge.querySelector('.status-label').innerText = "Trống";
                        delete tableReservationData[tid];
                    }
                });

                res.tables.forEach(resItem => {
                    let tid = resItem.table_id;
                    if (resItem.reservation_id && resItem.reservation_time) {
                        let isoStr = resItem.reservation_time.replace(' ', 'T');
                        let resDateTime = new Date(isoStr);

                        let resDate = resItem.reservation_time.split(' ')[0];
                        let resTime = resItem.reservation_time.split(' ')[1].substring(0, 5);

                        tableReservationData[tid] = {
                            name: resItem.customer_name || 'Khách hàng',
                            phone: resItem.customer_phone || '',
                            date: resDate,
                            time: resTime,
                            note: resItem.note || '',
                            isTurnaround: turnaroundTablesMap[tid] ? true : false
                        };

                        let tableBadge = document.getElementById('table-btn-' + tid);
                        if (tableBadge && !tableBadge.classList.contains('status-occupied') && !tableBadge.classList.contains('status-paid')) {
                            tableBadge.className = "table-badge status-reserved";
                            let label = tableBadge.querySelector('.status-label');
                            
                            if (now >= resDateTime) {
                                if (label) label.innerText = `Đã tới giờ hẹn / Quá giờ (${resTime})`;
                            } else {
                                if (label) label.innerText = `Đã đặt (${resTime})`;
                            }
                        }
                    }
                });
            }
        })
        .catch(err => console.error("Lỗi đồng bộ lịch đặt bàn:", err));
}

function showCustomAlert(msg, title = "Thông Báo Hệ Thống", type = "danger") {
    let circle = document.getElementById('alertIconCircle');
    let icon = document.getElementById('alertIcon');
    
    circle.className = "alert-icon-circle";
    
    if (type === "success") {
        circle.classList.add("alert-success");
        icon.className = "bi bi-check-lg";
    } else if (type === "info") {
        icon.className = "bi bi-info-lg";
    } else {
        circle.classList.add("alert-danger");
        icon.className = "bi bi-exclamation-lg";
    }

    document.getElementById('alertTitle').innerText = title;
    document.getElementById('alertMessage').innerText = msg;

    document.getElementById('alert-action-buttons').innerHTML = `
        <button type="button" class="btn btn-dark w-100 fw-bold rounded-3 py-2" data-bs-dismiss="modal">ĐỒNG Ý</button>
    `;

    customAlertModal.show();
}

function removeVietnameseTones(str) {
    if (!str) return '';
    str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
    str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
    str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
    str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
    str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
    str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
    str = str.replace(/đ/g, "d");
    return str.toLowerCase().trim();
}

function switchOrderType(type) {
    if (!isShiftOpened) {
        showCustomAlert("Vui lòng mở ca làm việc trước khi thực hiện bán hàng!", "Yêu Cầu Mở Ca", "danger");
        openShiftModal();
        return;
    }

    currentOrderType = type;
    document.querySelectorAll('.order-type-btn').forEach(btn => btn.classList.remove('active'));
    
    const tableSection = document.getElementById('table-section');
    const takeawaySection = document.getElementById('takeaway-section');
    const onlineSection = document.getElementById('online-section');
    const onlineSubtabs = document.getElementById('online-subtabs-container');
    const badge = document.getElementById('order-type-badge');
    const tableNameLabel = document.getElementById('selected-table-name');
    const noteLabel = document.getElementById('table-reservation-note');
    const loyaltySection = document.getElementById('loyalty-section');

    const btnPayDirect = document.getElementById('btn-pay-direct');
    const btnCheckout = document.getElementById('btn-checkout');
    const boxBtnReady = document.getElementById('box-btn-ready');
    const btnKitchen = document.getElementById('btn-kitchen');

    if (noteLabel) noteLabel.classList.add('d-none');

    if (type === 'dine_in') {
        document.getElementById('tab-dine-in').classList.add('active');
        if (tableSection) tableSection.classList.remove('d-none');
        if (takeawaySection) takeawaySection.classList.add('d-none');
        if (onlineSection) onlineSection.classList.add('d-none');
        if (onlineSubtabs) onlineSubtabs.classList.add('d-none');
        if (loyaltySection) loyaltySection.classList.remove('d-none');
        
        badge.innerText = "TẠI CHỖ";
        badge.className = "badge bg-dark text-white mb-1 fw-bold px-2 py-1";

        if (btnPayDirect) btnPayDirect.classList.remove('d-none');
        if (boxBtnReady) boxBtnReady.classList.remove('d-none');
        if (btnKitchen) btnKitchen.className = "btn btn-outline-dark fw-bold py-2 px-2 rounded-3 w-50 d-inline-flex align-items-center justify-content-center gap-1";
        if (btnCheckout) btnCheckout.innerHTML = `<i class="bi bi-credit-card"></i> THANH TOÁN (TRẢ BÀN)`;
        
        if (currentTableId && typeof currentTableId === 'number') {
            let tableBtn = document.getElementById('table-btn-' + currentTableId);
            tableNameLabel.innerText = tableBtn ? tableBtn.querySelector('.fw-bold').innerText : "Chưa Chọn Bàn";
        } else {
            currentTableId = null;
            tableNameLabel.innerText = "Chưa Chọn Bàn";
        }

    } else if (type === 'takeaway') {
        document.getElementById('tab-takeaway').classList.add('active');
        if (tableSection) tableSection.classList.add('d-none');
        if (takeawaySection) takeawaySection.classList.remove('d-none');
        if (onlineSection) onlineSection.classList.add('d-none');
        if (onlineSubtabs) onlineSubtabs.classList.add('d-none');
        if (loyaltySection) loyaltySection.classList.remove('d-none');
        
        badge.innerText = "MANG VỀ";
        badge.className = "badge bg-warning text-dark mb-1 fw-bold px-2 py-1";

        if (btnPayDirect) btnPayDirect.classList.add('d-none');
        if (boxBtnReady) boxBtnReady.classList.add('d-none');
        if (btnKitchen) btnKitchen.className = "btn btn-outline-dark fw-bold py-2 px-2 rounded-3 w-100 d-inline-flex align-items-center justify-content-center gap-1";
        if (btnCheckout) btnCheckout.innerHTML = `<i class="bi bi-cash-stack"></i> THANH TOÁN & CHỐT ĐƠN`;
        
        renderTakeawayCards();
        if (activeTakeawayCards.length > 0) {
            selectTakeawayCard(activeTakeawayCards[0]);
        }

    } else if (type === 'online') {
        document.getElementById('tab-online').classList.add('active');
        if (tableSection) tableSection.classList.add('d-none');
        if (takeawaySection) takeawaySection.classList.add('d-none');
        if (onlineSection) onlineSection.classList.remove('d-none');
        if (onlineSubtabs) onlineSubtabs.classList.remove('d-none');
        if (loyaltySection) loyaltySection.classList.add('d-none');
        detachCustomer();

        if (btnPayDirect) btnPayDirect.classList.add('d-none');
        if (boxBtnReady) boxBtnReady.classList.add('d-none');
        if (btnKitchen) btnKitchen.className = "btn btn-outline-dark fw-bold py-2 px-2 rounded-3 w-100 d-inline-flex align-items-center justify-content-center gap-1";
        if (btnCheckout) btnCheckout.innerHTML = `<i class="bi bi-box-seam"></i> CHỐT ĐƠN & IN BILL SHIPPER`;
        
        switchOnlinePlatform(onlinePlatform);
    }

    loadCartForCurrentTable();
    updateProductPricesDisplay();
    updateCheckoutButton();
}

function switchOnlinePlatform(platform) {
    onlinePlatform = platform;
    const badge = document.getElementById('order-type-badge');
    const btnGrab = document.getElementById('subtab-grab');
    const btnShopee = document.getElementById('subtab-shopee');
    const titleLabel = document.getElementById('online-section-title');

    if (platform === 'grab') {
        btnGrab.className = "subtab-btn active-grab";
        btnShopee.className = "subtab-btn";
        badge.innerText = "GRABFOOD";
        badge.className = "badge bg-success text-white mb-1 fw-bold px-2 py-1";
        if (titleLabel) titleLabel.innerText = "DANH SÁCH ĐƠN GRABFOOD TẠO MỚI";
        
        renderOnlineCards();
        if (activeGrabCards.length > 0) selectOnlineCard(activeGrabCards[0]);

    } else {
        btnGrab.className = "subtab-btn";
        btnShopee.className = "subtab-btn active-shopee";
        badge.innerText = "SHOPEEFOOD";
        badge.className = "badge badge-shopee mb-1 fw-bold px-2 py-1";
        if (titleLabel) titleLabel.innerText = "DANH SÁCH ĐƠN SHOPEEFOOD TẠO MỚI";
        
        renderOnlineCards();
        if (activeShopeeCards.length > 0) selectOnlineCard(activeShopeeCards[0]);
    }

    loadCartForCurrentTable();
    updateProductPricesDisplay();
    updateCheckoutButton();
}

function renderTakeawayCards() {
    const grid = document.getElementById('takeaway-cards-grid');
    if (!grid) return;
    grid.innerHTML = '';

    activeTakeawayCards.forEach(cardCode => {
        let itemCount = activeOrders[cardCode] ? activeOrders[cardCode].reduce((sum, i) => sum + i.qty, 0) : 0;
        let isActive = (currentTableId === cardCode) ? 'active' : '';
        let statusClass = itemCount > 0 ? 'status-occupied' : 'status-available';
        let statusText = itemCount > 0 ? `${itemCount} sản phẩm` : 'Chờ phục vụ';

        grid.innerHTML += `
            <div class="col-6 col-sm-4 col-md-3">
                <div class="table-badge ${statusClass} ${isActive}" id="card-btn-${cardCode}" onclick="selectTakeawayCard('${cardCode}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 13px;">Thẻ ${cardCode}</div>
                    </div>
                    <small class="text-muted d-block status-label" style="font-size: 10px;">${statusText}</small>
                </div>
            </div>`;
    });
}

function selectTakeawayCard(cardCode) {
    currentTableId = cardCode;
    document.getElementById('selected-table-name').innerText = "Thẻ Mang Về: " + cardCode;
    
    document.querySelectorAll('#takeaway-cards-grid .table-badge').forEach(b => b.classList.remove('active'));
    let activeBtn = document.getElementById('card-btn-' + cardCode);
    if (activeBtn) activeBtn.classList.add('active');

    loadCartForCurrentTable();
    updateCheckoutButton();
}

function addNewTakeawayCard() {
    takeawayCounter++;
    let newCode = "MV-" + String(takeawayCounter).padStart(2, '0');
    activeTakeawayCards.push(newCode);
    renderTakeawayCards();
    selectTakeawayCard(newCode);
}

function renderOnlineCards() {
    const grid = document.getElementById('online-cards-grid');
    if (!grid) return;
    grid.innerHTML = '';

    let cardsList = (onlinePlatform === 'grab') ? activeGrabCards : activeShopeeCards;

    cardsList.forEach(cardCode => {
        let itemCount = activeOrders[cardCode] ? activeOrders[cardCode].reduce((sum, i) => sum + i.qty, 0) : 0;
        let isActive = (currentTableId === cardCode) ? 'active' : '';
        let statusClass = itemCount > 0 ? 'status-occupied' : 'status-available';
        let statusText = itemCount > 0 ? `${itemCount} sản phẩm` : 'Đơn mới';

        grid.innerHTML += `
            <div class="col-6 col-sm-4 col-md-3">
                <div class="table-badge ${statusClass} ${isActive}" id="card-btn-${cardCode}" onclick="selectOnlineCard('${cardCode}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 13px;">${cardCode}</div>
                    </div>
                    <small class="text-muted d-block status-label" style="font-size: 10px;">${statusText}</small>
                </div>
            </div>`;
    });
}

function selectOnlineCard(cardCode) {
    currentTableId = cardCode;
    document.getElementById('selected-table-name').innerText = "Mã Đơn: " + cardCode;
    
    document.querySelectorAll('#online-cards-grid .table-badge').forEach(b => b.classList.remove('active'));
    let activeBtn = document.getElementById('card-btn-' + cardCode);
    if (activeBtn) activeBtn.classList.add('active');

    loadCartForCurrentTable();
    updateCheckoutButton();
}

function addNewOnlineCard() {
    let newCode = '';
    if (onlinePlatform === 'grab') {
        onlineGrabCounter++;
        newCode = "GRAB-" + onlineGrabCounter;
        activeGrabCards.push(newCode);
    } else {
        onlineShopeeCounter++;
        newCode = "SHOPEE-" + onlineShopeeCounter;
        activeShopeeCards.push(newCode);
    }
    renderOnlineCards();
    selectOnlineCard(newCode);
}

function selectTable(tableId, tableName) {
    if (!isShiftOpened) {
        showCustomAlert("Vui lòng mở ca làm việc trước khi thực hiện chọn bàn!", "Yêu Cầu Mở Ca", "danger");
        openShiftModal();
        return;
    }

    if (tableMergedTargetMap[tableId]) {
        let targetId = tableMergedTargetMap[tableId];
        let targetBtn = document.getElementById('table-btn-' + targetId);
        let targetName = targetBtn ? targetBtn.querySelector('.fw-bold').innerText : ('Bàn ' + targetId);

        document.getElementById('alertTitle').innerText = "Hóa Đơn Đã Được Gộp";
        document.getElementById('alertMessage').innerHTML = `Bàn <strong class="text-dark">${tableName}</strong> đã được chuyển toàn bộ hóa đơn sang <strong class="text-primary">${targetName}</strong>.<br><span class="text-muted d-block mt-1">Bạn muốn thực hiện <strong>TRẢ BÀN</strong> hay sang <strong>${targetName.toUpperCase()}</strong> bổ sung món?</span>`;
        
        let circle = document.getElementById('alertIconCircle');
        circle.className = "alert-icon-circle";
        document.getElementById('alertIcon').className = "bi bi-arrow-merge";

        document.getElementById('alert-action-buttons').innerHTML = `
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-danger w-50 fw-bold rounded-3 py-2" onclick="customAlertModal.hide(); releaseMergedSourceTable(${tableId});">TRẢ BÀN</button>
                <button type="button" class="btn btn-warning w-50 fw-bold rounded-3 py-2 text-dark shadow-sm" onclick="customAlertModal.hide(); proceedSelectPaidTable(${targetId}, '${targetName}');">SANG ${targetName.toUpperCase()}</button>
            </div>
        `;

        customAlertModal.show();
        return;
    }

    let selectedBtn = document.getElementById('table-btn-' + tableId);
    let noteLabel = document.getElementById('table-reservation-note');
    if (noteLabel) noteLabel.classList.add('d-none');

    if (selectedBtn && selectedBtn.classList.contains('status-reserved')) {
        let resData = tableReservationData[tableId] || {};
        let rTime = resData.time || 'giờ hẹn';
        let cName = resData.name || 'Khách hàng';
        let cPhone = resData.phone || 'Chưa có SĐT';
        let cNote = resData.note || 'Không có';

        let now = new Date();
        let isPastTime = false;
        let isWithin1Hour = false;

        if (resData.date && resData.time) {
            let resDateTime = new Date(`${resData.date}T${resData.time}:00`);
            let diffMins = (resDateTime - now) / (1000 * 60);

            if (diffMins < 0) {
                isPastTime = true;
            } else if (diffMins <= 60) {
                isWithin1Hour = true;
            }
        }

        if (isPastTime) {
            document.getElementById('alertTitle').innerText = "Thông Tin Khách Đặt Bàn (Đã Quá Giờ)";
            document.getElementById('alertMessage').innerHTML = `
                <div class="text-start bg-light p-2 rounded border mb-2" style="font-size:12px;">
                    <p class="mb-1"><strong>Khách hàng:</strong> ${cName}</p>
                    <p class="mb-1"><strong>Số điện thoại:</strong> ${cPhone}</p>
                    <p class="mb-1"><strong>Giờ hẹn:</strong> ${rTime} (${resData.date || ''})</p>
                    <p class="mb-0"><strong>Ghi chú:</strong> ${cNote}</p>
                </div>
                <div class="alert alert-danger p-2 mb-0 fw-bold" style="font-size:11px;">⚠️ ĐÃ QUÁ GIỜ HẸN! Khách có thể đã trễ hẹn hoặc đã tới quán.</div>
            `;
            
            let circle = document.getElementById('alertIconCircle');
            circle.className = "alert-icon-circle alert-danger";
            document.getElementById('alertIcon').className = "bi bi-exclamation-triangle-fill";

            document.getElementById('alert-action-buttons').innerHTML = `
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger w-50 fw-bold rounded-3 py-2" onclick="customAlertModal.hide(); openCancelModalForTable(${tableId}, '${tableName}');">HỦY LỊCH ĐẶT</button>
                    <button type="button" class="btn btn-warning w-50 fw-bold rounded-3 py-2 text-dark shadow-sm" onclick="customAlertModal.hide(); proceedSelectPaidTable(${tableId}, '${tableName}');">MỞ ORDER CHO KHÁCH HẸN</button>
                </div>
            `;
        } else if (isWithin1Hour) {
            document.getElementById('alertTitle').innerText = "Thông Tin Khách Đặt Bàn (Sát Giờ Hẹn)";
            document.getElementById('alertMessage').innerHTML = `
                <div class="text-start bg-light p-2 rounded border mb-2" style="font-size:12px;">
                    <p class="mb-1"><strong>Khách hàng:</strong> ${cName}</p>
                    <p class="mb-1"><strong>Số điện thoại:</strong> ${cPhone}</p>
                    <p class="mb-1"><strong>Giờ hẹn:</strong> ${rTime} (${resData.date || ''})</p>
                    <p class="mb-0"><strong>Ghi chú:</strong> ${cNote}</p>
                </div>
                <div class="alert alert-warning p-2 mb-0 fw-bold text-dark" style="font-size:11px;">⚠️ Còn chưa tới 1 tiếng nữa tới giờ hẹn! Ưu tiên chuẩn bị bàn cho khách hẹn trước.</div>
            `;
            
            let circle = document.getElementById('alertIconCircle');
            circle.className = "alert-icon-circle";
            document.getElementById('alertIcon').className = "bi bi-bookmark-star-fill";

            document.getElementById('alert-action-buttons').innerHTML = `
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary w-50 fw-bold rounded-3 py-2" data-bs-dismiss="modal">ĐÓNG</button>
                    <button type="button" class="btn btn-warning w-50 fw-bold rounded-3 py-2 text-dark shadow-sm" onclick="customAlertModal.hide(); proceedSelectPaidTable(${tableId}, '${tableName}');">MỞ ORDER CHO KHÁCH HẸN</button>
                </div>
            `;
        } else {
            document.getElementById('alertTitle').innerText = "Thông Tin Khách Đặt Bàn";
            document.getElementById('alertMessage').innerHTML = `
                <div class="text-start bg-light p-2 rounded border mb-2" style="font-size:12px;">
                    <p class="mb-1"><strong>Khách hàng:</strong> ${cName}</p>
                    <p class="mb-1"><strong>Số điện thoại:</strong> ${cPhone}</p>
                    <p class="mb-1"><strong>Giờ hẹn:</strong> ${rTime} (${resData.date || ''})</p>
                    <p class="mb-0"><strong>Ghi chú:</strong> ${cNote}</p>
                </div>
                <div class="alert alert-info p-2 mb-0" style="font-size:11px;">ℹ️ Còn hơn 1 tiếng nữa mới tới giờ hẹn. Có thể cho khách ngắn hạn ngồi xoay tua.</div>
            `;
            
            let circle = document.getElementById('alertIconCircle');
            circle.className = "alert-icon-circle";
            document.getElementById('alertIcon').className = "bi bi-clock-history";

            document.getElementById('alert-action-buttons').innerHTML = `
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary w-50 fw-bold rounded-3 py-2" onclick="customAlertModal.hide(); confirmTurnaroundTable(${tableId}, '${tableName}', '${rTime}');">MỞ BÀN XOAY TUA</button>
                    <button type="button" class="btn btn-warning w-50 fw-bold rounded-3 py-2 text-dark shadow-sm" onclick="customAlertModal.hide(); proceedSelectPaidTable(${tableId}, '${tableName}');">MỞ ORDER CHO KHÁCH HẸN</button>
                </div>
            `;
        }

        customAlertModal.show();
        return;
    }

    if (selectedBtn && selectedBtn.classList.contains('status-paid')) {
        document.getElementById('alertTitle').innerText = "Trạng Thái Thanh Toán";
        document.getElementById('alertMessage').innerHTML = `Bàn <strong class="text-dark">${tableName}</strong> đã hoàn tất thanh toán.<br><span class="text-muted d-block mt-1">Bạn muốn thực hiện <strong>TRẢ BÀN TRỐNG</strong> hay <strong>GỌI THÊM MÓN MỚI</strong>?</span>`;
        
        let circle = document.getElementById('alertIconCircle');
        circle.className = "alert-icon-circle";
        document.getElementById('alertIcon').className = "bi bi-info-circle";

        document.getElementById('alert-action-buttons').innerHTML = `
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-danger w-50 fw-bold rounded-3 py-2" onclick="customAlertModal.hide(); releasePaidTable(${tableId});">TRẢ BÀN</button>
                <button type="button" class="btn btn-success w-50 fw-bold rounded-3 py-2" onclick="customAlertModal.hide(); proceedSelectPaidTable(${tableId}, '${tableName}');">GỌI THÊM MÓN</button>
            </div>
        `;

        customAlertModal.show();
        return;
    }

    proceedSelectPaidTable(tableId, tableName);
}

function releaseMergedSourceTable(sourceId) {
    let targetId = tableMergedTargetMap[sourceId];
    delete tableMergedTargetMap[sourceId];
    if (tableMergeSourcesMap[targetId]) {
        tableMergeSourcesMap[targetId] = tableMergeSourcesMap[targetId].filter(id => id != sourceId);
    }
    let srcBadge = document.getElementById('table-btn-' + sourceId);
    if (srcBadge) {
        srcBadge.className = "table-badge status-available";
        srcBadge.querySelector('.status-label').innerText = "Trống";
    }

    syncTableStatusToDB(sourceId, 'available');
    showCustomAlert(`Bàn ${sourceId} đã được đưa về trạng thái trống thành công!`, "Thành Công", "success");
}

// PHÂN BIỆT BÀN XOAY TUA VS BÀN KHÁCH HẸN CHÍNH KHI TRẢ BÀN
function releasePaidTable(tableId) {
    let tableBadge = document.getElementById('table-btn-' + tableId);
    let newStatus = 'available';

    // Nếu là bàn xoay tua -> Trả về bàn Đã Đặt (status = 2 / reserved)
    if (turnaroundTablesMap[tableId]) {
        delete turnaroundTablesMap[tableId];
        if (tableReservationData[tableId]) tableReservationData[tableId].isTurnaround = false;

        if (tableBadge) {
            tableBadge.className = "table-badge status-reserved";
            let rTime = tableReservationData[tableId] ? tableReservationData[tableId].time : '';
            tableBadge.querySelector('.status-label').innerText = rTime ? `Đã đặt (${rTime})` : "Đã đặt";
        }
        newStatus = 'reserved';
    } else {
        // Nếu là khách hẹn chính hoặc bàn thường -> Trả về bàn Trống
        if (tableBadge) {
            tableBadge.className = "table-badge status-available";
            tableBadge.querySelector('.status-label').innerText = "Trống";
        }
        delete tableReservationData[tableId];
    }

    syncTableStatusToDB(tableId, newStatus);

    document.getElementById('btn-release-table').classList.add('d-none');
    delete activeOrders[tableId];
    cart = [];
    renderCart();
    showCustomAlert(`Đã giải phóng bàn thành công!`, "Thành Công", "success");
}

function proceedSelectPaidTable(tableId, tableName) {
    let selectedBtn = document.getElementById('table-btn-' + tableId);
    let noteLabel = document.getElementById('table-reservation-note');

    currentTableId = tableId;
    document.getElementById('selected-table-name').innerText = tableName;
    
    document.querySelectorAll('.table-badge').forEach(badge => badge.classList.remove('active'));
    if (selectedBtn) {
        selectedBtn.classList.add('active');
        if (turnaroundTablesMap[tableId] || (tableReservationData[tableId] && tableReservationData[tableId].isTurnaround)) {
            if (noteLabel) {
                let rTime = tableReservationData[tableId] ? tableReservationData[tableId].time : '';
                noteLabel.innerText = `[Bàn Xoay Tua - Giờ Hẹn: ${rTime}]`;
                noteLabel.classList.remove('d-none');
            }
        } else if (tableReservationData[tableId] && tableReservationData[tableId].note) {
            if (noteLabel) {
                noteLabel.innerText = `[Ghi chú: ${tableReservationData[tableId].note}]`;
                noteLabel.classList.remove('d-none');
            }
        }
    }

    let releaseBtn = document.getElementById('btn-release-table');
    let isPaidStatus = selectedBtn && selectedBtn.classList.contains('status-paid');
    let isSourceEmptyBill = selectedBtn && selectedBtn.classList.contains('status-occupied') && (!activeOrders[tableId] || activeOrders[tableId].length === 0);

    if (releaseBtn) {
        if (isPaidStatus || isSourceEmptyBill) {
            releaseBtn.classList.remove('d-none');
        } else {
            releaseBtn.classList.add('d-none');
        }
    }

    loadCartForCurrentTable();
    updateCheckoutButton();
}

// BÀN XOAY TUA BẬT CỜ
function confirmTurnaroundTable(tableId, tableName, rTime) {
    turnaroundTablesMap[tableId] = true; // Bật cờ xoay tua
    if (!tableReservationData[tableId]) tableReservationData[tableId] = {};
    tableReservationData[tableId].isTurnaround = true;

    let selectedBtn = document.getElementById('table-btn-' + tableId);
    if (selectedBtn) {
        selectedBtn.className = "table-badge status-occupied";
        selectedBtn.querySelector('.status-label').innerText = `[Xoay Tua - Hẹn ${rTime}]`;
    }

    syncTableStatusToDB(tableId, 'occupied', true);

    currentTableId = tableId;
    document.getElementById('selected-table-name').innerText = tableName;
    document.querySelectorAll('.table-badge').forEach(badge => badge.classList.remove('active'));
    if (selectedBtn) selectedBtn.classList.add('active');

    loadCartForCurrentTable();
    updateCheckoutButton();
}

function releaseCurrentTable() {
    if (!currentTableId) return;
    releasePaidTable(currentTableId);
}

function loadCartForCurrentTable() {
    if (currentTableId && activeOrders[currentTableId]) {
        cart = activeOrders[currentTableId];
    } else {
        cart = [];
    }
    renderCart();
}

function handleProductClick(product) {
    if (!isShiftOpened) {
        showCustomAlert("Vui lòng mở ca làm việc trước khi thực hiện gọi món!", "Yêu Cầu Mở Ca", "danger");
        openShiftModal();
        return;
    }

    if (currentOrderType === 'dine_in' && !currentTableId) {
        showCustomAlert("Vui lòng lựa chọn bàn phục vụ trước khi chọn món ăn!", "Chưa Chọn Bàn", "danger");
        return;
    }

    let pCat = String(product.category_id || '');
    let isToppingByCat = (DB_TOPPING_CAT_ID && pCat === String(DB_TOPPING_CAT_ID)) || (pCat === '4');
    let isCakeByCat = (pCat === '5');

    if (isToppingByCat || isCakeByCat) {
        let basePrice = parseFloat(product.price);
        if (currentOrderType === 'online') basePrice = Math.round(basePrice * 1.1);

        cart.push({
            id: product.id,
            category_id: product.category_id,
            name: product.name,
            price: basePrice,
            size: '',
            sugar: '',
            ice: '',
            toppings: [],
            note: '',
            isCake: isCakeByCat,
            qty: 1,
            printedQty: 0
        });

        if (currentTableId) activeOrders[currentTableId] = cart;
        renderCart();
        return;
    }

    openProductOptionModal(product);
}

function openProductOptionModal(product) {
    selectedProductForModal = product;
    document.getElementById('optionModalTitle').innerText = product.name;
    document.getElementById('item-note').value = '';

    const drinkWrapper = document.getElementById('drink-options-wrapper');
    const dynamicToppingList = document.getElementById('dynamic-topping-list');

    drinkWrapper.classList.remove('d-none');
    dynamicToppingList.innerHTML = '';
    
    document.querySelectorAll('.product-item').forEach(item => {
        let itemCat = String(item.getAttribute('data-category'));
        let itemName = item.getAttribute('data-name');
        let itemPrice = parseFloat(item.getAttribute('data-price'));

        if ((DB_TOPPING_CAT_ID && itemCat === String(DB_TOPPING_CAT_ID)) || (itemCat === '4')) {
            dynamicToppingList.innerHTML += `
                <div class="form-check form-check-inline m-0">
                    <input class="btn-check dynamic-topping-chk" type="checkbox" value="${itemPrice}" data-name="${itemName}" id="top-chk-${itemPrice}-${removeVietnameseTones(itemName)}">
                    <label class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1" for="top-chk-${itemPrice}-${removeVietnameseTones(itemName)}" style="font-size:11px;">
                        + ${itemName} (+${itemPrice.toLocaleString()}đ)
                    </label>
                </div>`;
        }
    });

    optionModal.show();
}

function selectPill(element, group) {
    let parent = element.parentElement;
    parent.querySelectorAll('.option-pill').forEach(p => p.classList.remove('selected'));
    element.classList.add('selected');
}

function confirmAddToCart() {
    if (!selectedProductForModal) return;

    let sizePill = document.querySelector('#size-options .selected');
    let sizeName = sizePill ? sizePill.innerText.split(' ')[1] : 'M';
    let sizeExtra = sizePill ? parseFloat(sizePill.getAttribute('data-extra')) : 0;
    let sugar = document.querySelector('#sugar-options .selected')?.innerText || '100% Đường';
    let ice = document.querySelector('#ice-options .selected')?.innerText || '100% Đá';

    let selectedToppings = [];
    let toppingsExtra = 0;

    document.querySelectorAll('.dynamic-topping-chk:checked').forEach(chk => {
        let tPrice = parseFloat(chk.value);
        let tName = chk.getAttribute('data-name');
        selectedToppings.push(tName);
        toppingsExtra += tPrice;
    });

    let note = document.getElementById('item-note').value.trim();
    let basePrice = parseFloat(selectedProductForModal.price);
    if (currentOrderType === 'online') {
        basePrice = Math.round(basePrice * 1.1);
    }
    let finalPrice = basePrice + sizeExtra + toppingsExtra;

    cart.push({
        id: selectedProductForModal.id,
        category_id: selectedProductForModal.category_id,
        name: selectedProductForModal.name,
        price: finalPrice,
        size: sizeName,
        sugar: sugar,
        ice: ice,
        toppings: selectedToppings,
        note: note,
        isCake: false,
        qty: 1,
        printedQty: 0
    });

    if (currentTableId) {
        activeOrders[currentTableId] = cart;
    }

    optionModal.hide();
    renderCart();

    if (currentOrderType === 'takeaway') renderTakeawayCards();
    if (currentOrderType === 'online') renderOnlineCards();
}

function renderCart() {
    const container = document.getElementById('cart-items');
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted my-5 py-4">
                <span class="d-block text-secondary fw-semibold" style="font-size: 13px;">Vui lòng chọn bàn/thẻ vị trí và chọn món ăn</span>
            </div>`;
        updateTotals(0);
        return;
    }

    let html = '<div class="list-group list-group-flush">';
    let subTotal = 0;

    cart.forEach((item, index) => {
        let itemTotal = item.price * item.qty;
        subTotal += itemTotal;

        let printedCount = item.printedQty || 0;
        let isMinusDisabled = (item.qty <= printedCount) ? 'disabled' : '';

        let optionsText = item.isCake ? '' : `Size ${item.size || 'M'}, ${item.sugar}, ${item.ice}`;
        if (item.toppings && item.toppings.length > 0) {
            optionsText += (optionsText ? ' + ' : '') + item.toppings.join(', ');
        }
        if (item.note) optionsText += (optionsText ? ` (${item.note})` : `${item.note}`);

        html += `
            <div class="list-group-item px-0 py-2 border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div style="max-width: 60%;">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 13px;">${item.name}</div>
                        ${optionsText ? `<small class="text-muted d-block" style="font-size: 11px;">${optionsText}</small>` : ''}
                        <small class="fw-semibold" style="color: var(--pos-primary);">${item.price.toLocaleString()} đ</small>
                        ${printedCount > 0 ? `<small class="badge bg-secondary opacity-75 d-block mt-1" style="font-size:9px;">Đã in tem: ${printedCount}</small>` : ''}
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-light border btn-qty" ${isMinusDisabled} onclick="changeQty(${index}, -1)">-</button>
                        <span class="fw-bold px-1" style="font-size: 13px;">${item.qty}</span>
                        <button class="btn btn-sm btn-light border btn-qty" onclick="changeQty(${index}, 1)">+</button>
                        ${printedCount > 0 ? 
                            `<span class="text-muted opacity-50 ms-1" title="Món ăn đã khóa lệnh báo bếp"><i class="bi bi-lock-fill"></i></span>` : 
                            `<button class="btn btn-sm text-danger border-0 ms-1 fw-bold" onclick="removeItem(${index})">✕</button>`
                        }
                    </div>
                </div>
            </div>`;
    });

    html += '</div>';
    container.innerHTML = html;
    updateTotals(subTotal);
}

function changeQty(index, delta) {
    let item = cart[index];
    let printedCount = item.printedQty || 0;

    if (delta < 0 && item.qty <= printedCount) {
        showCustomAlert("Sản phẩm đã được gửi lệnh in tem chế biến, không thể giảm số lượng!", "Quy Trình Khóa Đơn", "danger");
        return;
    }

    item.qty += delta;
    if (item.qty <= 0) cart.splice(index, 1);
    if (currentTableId) activeOrders[currentTableId] = cart;
    renderCart();

    if (currentOrderType === 'takeaway') renderTakeawayCards();
    if (currentOrderType === 'online') renderOnlineCards();
}

function removeItem(index) {
    let item = cart[index];
    if (item.printedQty && item.printedQty > 0) {
        showCustomAlert("Sản phẩm đã được gửi lệnh in tem chế biến, không thể hủy khỏi hóa đơn!", "Quy Trình Khóa Đơn", "danger");
        return;
    }
    cart.splice(index, 1);
    if (currentTableId) activeOrders[currentTableId] = cart;
    renderCart();

    if (currentOrderType === 'takeaway') renderTakeawayCards();
    if (currentOrderType === 'online') renderOnlineCards();
}

function clearCart() {
    let hasPrintedItems = cart.some(item => item.printedQty && item.printedQty > 0);
    if (hasPrintedItems) {
        showCustomAlert("Hóa đơn đã có sản phẩm gửi lệnh in chế biến. Không thể thực hiện hủy toàn bộ!", "Quy Trình Khóa Đơn", "danger");
        return;
    }
    cart = [];
    if (currentTableId) delete activeOrders[currentTableId];
    renderCart();

    if (currentOrderType === 'takeaway') renderTakeawayCards();
    if (currentOrderType === 'online') renderOnlineCards();
}

// TÍNH TỔNG TIỀN THEO VAT ĐỘNG DÙNG THỰC TẾ
function updateTotals(subTotal) {
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;

    let pointsDiscountMoney = 0;
    let tierDiscountMoney = 0;

    if (currentOrderType !== 'online') {
        pointsDiscountMoney = appliedPointsDiscount;
        let remainingAfterPoints = Math.max(0, baseGrandTotal - pointsDiscountMoney);

        if (selectedCustomer) {
            let discountPercent = selectedCustomer.discount_percent || 0;
            if (discountPercent > 0) {
                tierDiscountMoney = Math.round(remainingAfterPoints * (discountPercent / 100));
            }
        }
    }

    let totalDiscount = pointsDiscountMoney + tierDiscountMoney;
    let grandTotal = Math.max(0, Math.round(baseGrandTotal - totalDiscount));

    document.getElementById('sub-total').innerText = subTotal.toLocaleString() + " đ";
    document.getElementById('vat-total').innerText = Math.round(vat).toLocaleString() + " đ";

    if (totalDiscount > 0) {
        document.getElementById('loyalty-discount-row').classList.remove('d-none');
        document.getElementById('loyalty-discount-total').innerText = "-" + totalDiscount.toLocaleString() + " đ";
    } else {
        document.getElementById('loyalty-discount-row').classList.add('d-none');
    }

    document.getElementById('grand-total').innerText = grandTotal.toLocaleString() + " đ";

    updateCheckoutButton();
}

// ĐỔI ĐIỂM THỦ CÔNG (1 pt = 100đ)
function applyManualPointsRedeem() {
    if (!selectedCustomer) return;

    let inputPts = parseInt(document.getElementById('manual-redeem-pts').value || 0);
    let availablePts = parseInt(selectedCustomer.total_points || 0);

    if (isNaN(inputPts) || inputPts <= 0) {
        showCustomAlert("Vui lòng nhập số điểm muốn đổi hợp lệ!", "Lỗi Nhập Điểm", "danger");
        return;
    }

    if (inputPts > availablePts) {
        showCustomAlert(`Khách hàng chỉ có ${availablePts} pt khả dụng!`, "Không Đủ Điểm", "danger");
        return;
    }

    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;

    let discountMoney = inputPts * 100; // 1 pt = 100đ

    if (discountMoney > baseGrandTotal) {
        discountMoney = baseGrandTotal;
        inputPts = Math.ceil(baseGrandTotal / 100);
    }

    pointsToRedeem = inputPts;
    appliedPointsDiscount = discountMoney;

    document.getElementById('redeem-points-input').value = pointsToRedeem;
    document.getElementById('loyalty-discount-msg').innerText = `Đổi quà: -${appliedPointsDiscount.toLocaleString()}đ (${pointsToRedeem} pt)`;
    document.getElementById('loyalty-discount-msg').classList.remove('d-none');

    renderCart();
}

// HÀM MỞ MODAL & LƯU % VAT HỆ THỐNG
function adminUpdateVatSetting() {
    if (currentUserRole !== 'admin') {
        showCustomAlert("Chỉ tài khoản Admin mới có quyền điều chỉnh Thuế VAT hệ thống!", "Không Có Quyền", "danger");
        return;
    }

    document.getElementById('vat-setting-input').value = currentVatPercent;
    if (vatSettingModal) {
        vatSettingModal.show();
    }
}

async function saveVatSettingFromModal() {
    let inputVal = document.getElementById('vat-setting-input').value;
    let newVat = parseFloat(inputVal);

    if (isNaN(newVat) || newVat < 0 || newVat > 100) {
        showCustomAlert("% VAT nhập vào không hợp lệ! Vui lòng nhập từ 0 đến 100.", "Lỗi Dữ Liệu", "danger");
        return;
    }

    try {
        let res = await fetch('<?= BASE_URL ?>/order/updateVatSetting', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ vat_percent: newVat })
        }).then(r => r.json());

        if (vatSettingModal) vatSettingModal.hide();

        if (res.success) {
            currentVatPercent = newVat;
            let vatLbl = document.getElementById('vat-label-display');
            if (vatLbl) vatLbl.innerText = currentVatPercent;

            renderCart();
            showCustomAlert(res.message, "Thành Công", "success");
        } else {
            showCustomAlert(res.message || "Không thể cập nhật Thuế VAT!", "Lỗi Cập Nhật", "danger");
        }
    } catch (e) {
        showCustomAlert("Lỗi kết nối máy chủ khi cập nhật VAT!", "Lỗi Hệ Thống", "danger");
    }
}

function updateCheckoutButton() {
    const btnCheckout = document.getElementById('btn-checkout');
    const btnPayDirect = document.getElementById('btn-pay-direct');
    const btnKitchen = document.getElementById('btn-kitchen');
    const btnReady = document.getElementById('btn-ready-notify');
    
    let isValid = (currentTableId !== null) && cart.length > 0;
    if (btnCheckout) btnCheckout.disabled = !isValid;
    if (btnPayDirect) btnPayDirect.disabled = !isValid;
    if (btnKitchen) btnKitchen.disabled = !isValid;
    if (btnReady) btnReady.disabled = !isValid;
}

function sendToKitchen() {
    if (cart.length === 0 || !currentTableId) return;

    activeOrders[currentTableId] = cart;

    if (typeof currentTableId === 'number') {
        let tableBadge = document.getElementById('table-btn-' + currentTableId);
        if (tableBadge && !tableBadge.classList.contains('status-reserved')) {
            tableBadge.className = "table-badge status-occupied";
            tableBadge.querySelector('.status-label').innerText = "Có khách";
            syncTableStatusToDB(currentTableId, 'occupied');
        }
    }

    let itemsToPrint = [];
    cart.forEach(item => {
        let newQty = item.qty - (item.printedQty || 0);
        if (newQty > 0) {
            itemsToPrint.push({ item: item, printCount: newQty });
        }
    });

    if (itemsToPrint.length === 0) {
        showCustomAlert("Tất cả sản phẩm trong hóa đơn đã gửi lệnh in tem chế biến trước đó!", "Thông Báo", "info");
        return;
    }

    let stickerListHtml = '';
    itemsToPrint.forEach(p => {
        let item = p.item;
        for (let i = 0; i < p.printCount; i++) {
            let toppingStr = item.toppings && item.toppings.length > 0 ? item.toppings.join(', ') : '';
            stickerListHtml += `
                <div class="sticker-mockup text-start shadow-sm mb-2">
                    <div class="d-flex justify-content-between border-bottom pb-1 mb-1 fw-bold" style="font-size: 11px;">
                        <span>VỊ TRÍ: ${currentTableId}</span>
                        <span>${new Date().toLocaleTimeString()}</span>
                    </div>
                    <div class="fw-bold fs-6 text-truncate">${item.name}</div>
                    ${item.isCake ? '' : `<div style="font-size: 11px;">Size: ${item.size || 'M'} | ${item.sugar}</div><div style="font-size: 11px;">Đá: ${item.ice}</div>`}
                    ${toppingStr ? `<div style="font-size: 11px; font-weight: bold;">+ ${toppingStr}</div>` : ''}
                    ${item.note ? `<div style="font-size: 11px; font-style: italic;">Ghi chú: ${item.note}</div>` : ''}
                </div>`;
        }
    });

    document.getElementById('sticker-list-content').innerHTML = stickerListHtml;
    stickerPrintModal.show();
}

function executeStickerPrint() {
    cart.forEach(item => {
        item.printedQty = item.qty;
    });

    stickerPrintModal.hide();
    renderCart();
    showCustomAlert("Lệnh in tem chế biến dán ly đã được gửi thành công!", "Thành Công", "success");
}

function notifyKitchenReady() {
    if (!currentTableId || cart.length === 0) return;
    let pagerNumber = Math.floor(Math.random() * 20) + 1;
    showCustomAlert(`Phát tín hiệu thông báo hoàn tất món ăn cho Thẻ báo số [${pagerNumber}]!`, "Thông Báo Khách Hàng", "info");
}

function openCheckoutModal(isDirect = false) {
    if (cart.length === 0 || !currentTableId) return;

    isPayDirectMode = isDirect;

    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;

    let pointsDiscountMoney = 0;
    let tierDiscountMoney = 0;

    if (currentOrderType !== 'online') {
        pointsDiscountMoney = appliedPointsDiscount;
        let remainingAfterPoints = Math.max(0, baseGrandTotal - pointsDiscountMoney);

        if (selectedCustomer) {
            let discountPercent = selectedCustomer.discount_percent || 0;
            if (discountPercent > 0) tierDiscountMoney = Math.round(remainingAfterPoints * (discountPercent / 100));
        }
    }
    let totalDiscount = pointsDiscountMoney + tierDiscountMoney;

    let grandTotal = Math.max(0, Math.round(baseGrandTotal - totalDiscount));

    document.getElementById('modal-pay-amount').innerText = grandTotal.toLocaleString() + " đ";

    const standardBox = document.getElementById('checkout-standard-payment-box');
    const onlineBox = document.getElementById('checkout-online-app-box');
    const confirmBtn = document.getElementById('btn-confirm-checkout-action');

    if (currentOrderType === 'online') {
        standardBox.classList.add('d-none');
        onlineBox.classList.remove('d-none');
        document.getElementById('app-order-code-input').value = '';
        document.getElementById('app-driver-name-input').value = '';
        document.getElementById('app-driver-phone-input').value = '';
        document.getElementById('app-commission-rate-select').value = '20';
        calculateAppNetPrice();

        document.getElementById('checkoutModalTitle').innerHTML = `<i class="bi bi-box-seam text-success"></i> XÁC NHẬN ĐƠN APP ${onlinePlatform.toUpperCase()} & IN BILL`;
        confirmBtn.innerHTML = `<i class="bi bi-printer"></i> XÁC NHẬN & IN HÓA ĐƠN GIAO HÀNG`;

    } else {
        standardBox.classList.remove('d-none');
        onlineBox.classList.add('d-none');
        document.getElementById('cash-given').value = '';
        document.getElementById('cash-change').innerText = '0 đ';
        document.getElementById('inline-payment-error').classList.add('d-none');

        let titleText = isDirect ? "THANH TOÁN TẠI CHỖ (GIỮ BÀN)" : "THANH TOÁN & IN HÓA ĐƠN";
        document.getElementById('checkoutModalTitle').innerHTML = `<i class="bi bi-credit-card-2-front text-success"></i> ${titleText}`;
        confirmBtn.innerHTML = `<i class="bi bi-printer"></i> HOÀN TẤT THANH TOÁN & IN HÓA ĐƠN`;
    }

    let itemsHtml = '';
    cart.forEach(item => {
        itemsHtml += `
            <div class="d-flex justify-content-between py-1 border-bottom-dashed">
                <div style="max-width: 65%;">
                    <div>${item.qty}x ${item.name} ${item.isCake ? '' : `(Size ${item.size || 'M'})`}</div>
                    ${item.isCake ? '' : `<small style="font-size: 9px;">${item.sugar}, ${item.ice}</small>`}
                    ${item.toppings && item.toppings.length > 0 ? `<br><small style="font-size: 9px;">+ ${item.toppings.join(', ')}</small>` : ''}
                </div>
                <div>${(item.price * item.qty).toLocaleString()}đ</div>
            </div>`;
    });

    let receiptTitle = (currentOrderType === 'online') ? `HÓA ĐƠN ĐƠN APP (${onlinePlatform.toUpperCase()})` : `HÓA ĐƠN THANH TOÁN`;

    let receiptHtml = `
        <div class="text-center mb-2">
            <h6 class="fw-bold m-0">QUÁN CÀ PHÊ BẤT ỔN</h6>
            <small style="font-size: 10px;">${receiptTitle}</small><br>
            <small style="font-size: 9px;">Thời gian: ${new Date().toLocaleString()}</small>
        </div>
        <div class="border-top border-bottom py-1 mb-2 fw-bold" style="font-size: 11px;">
            VỊ TRÍ: ${currentTableId} | Thu ngân: ${currentShiftInfo.cashierId}
        </div>
        <div class="mb-2">${itemsHtml}</div>
        <div class="border-top pt-1" style="font-size: 11px;">
            <div class="d-flex justify-content-between"><span>Tạm tính:</span><span>${subTotal.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between"><span>Thuế VAT (${currentVatPercent}%):</span><span>${Math.round(vat).toLocaleString()}đ</span></div>
            ${totalDiscount > 0 ? `<div class="d-flex justify-content-between text-success"><span>Chiết khấu / Giảm giá:</span><span>-${totalDiscount.toLocaleString()}đ</span></div>` : ''}
            <div class="d-flex justify-content-between fw-bold fs-6 mt-1"><span>TỔNG TIỀN:</span><span>${grandTotal.toLocaleString()}đ</span></div>
        </div>
    `;

    document.getElementById('receipt-preview-content').innerHTML = receiptHtml;
    checkoutModal.show();
}

function calculateAppNetPrice() {
    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let grandTotal = Math.round(subTotal + vat);
    
    let rate = parseFloat(document.getElementById('app-commission-rate-select').value) || 0;

    let commissionFee = Math.round(grandTotal * (rate / 100));
    let netRevenue = grandTotal - commissionFee;

    document.getElementById('app-commission-fee-val').innerText = "-" + commissionFee.toLocaleString() + " đ";
    document.getElementById('app-net-revenue-val').innerText = netRevenue.toLocaleString() + " đ";
}

function calculateChange() {
    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;

    let pointsDiscountMoney = 0;
    let tierDiscountMoney = 0;

    if (currentOrderType !== 'online') {
        pointsDiscountMoney = appliedPointsDiscount;
        let remainingAfterPoints = Math.max(0, baseGrandTotal - pointsDiscountMoney);

        if (selectedCustomer) {
            let discountPercent = selectedCustomer.discount_percent || 0;
            if (discountPercent > 0) tierDiscountMoney = Math.round(remainingAfterPoints * (discountPercent / 100));
        }
    }
    let totalDiscount = pointsDiscountMoney + tierDiscountMoney;
    let grandTotal = Math.max(0, Math.round(baseGrandTotal - totalDiscount));

    let given = parseFloat(document.getElementById('cash-given').value) || 0;
    let change = given - grandTotal;

    const errorDiv = document.getElementById('inline-payment-error');

    if (given > 0 && change < 0) {
        errorDiv.classList.remove('d-none');
        document.getElementById('cash-change').innerText = "Còn thiếu " + Math.abs(change).toLocaleString() + " đ";
        document.getElementById('cash-change').className = "text-danger fw-bold";
    } else {
        errorDiv.classList.add('d-none');
        document.getElementById('cash-change').innerText = change >= 0 ? change.toLocaleString() + " đ" : "0 đ";
        document.getElementById('cash-change').className = "text-success fw-bold";
    }
}

function processCheckout() {
    if (cart.length === 0 || !currentTableId) return;

    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;

    let pointsDiscountMoney = 0;
    let tierDiscountMoney = 0;

    if (currentOrderType !== 'online') {
        pointsDiscountMoney = appliedPointsDiscount;
        let remainingAfterPoints = Math.max(0, baseGrandTotal - pointsDiscountMoney);

        if (selectedCustomer) {
            let discountPercent = selectedCustomer.discount_percent || 0;
            if (discountPercent > 0) tierDiscountMoney = Math.round(remainingAfterPoints * (discountPercent / 100));
        }
    }
    let totalDiscount = pointsDiscountMoney + tierDiscountMoney;
    let grandTotal = Math.max(0, Math.round(baseGrandTotal - totalDiscount));

    if (currentOrderType === 'online') {
        let appCode = document.getElementById('app-order-code-input').value.trim();
        if (!appCode) {
            showCustomAlert("Vui lòng nhập mã đơn tên sàn ứng dụng để đảm bảo quy trình đối soát!", "Thiếu Thông Tin", "danger");
            document.getElementById('app-order-code-input').focus();
            return;
        }

        let rate = parseFloat(document.getElementById('app-commission-rate-select').value) || 0;
        let driverName = document.getElementById('app-driver-name-input').value.trim() || 'Tài xế App';
        let driverPhone = document.getElementById('app-driver-phone-input').value.trim() || '';

        let extraData = {
            order_type: onlinePlatform,
            payment_method: 'app',
            app_order_code: appCode,
            driver_name: driverName,
            driver_phone: driverPhone,
            commission_rate: rate,
            vat_percent: currentVatPercent
        };

        sendOrderToMySQLBackend(extraData);

    } else {
        let given = parseFloat(document.getElementById('cash-given').value) || 0;
        let activeTab = document.querySelector('#pills-cash-tab.active');

        if (activeTab && given < grandTotal) {
            document.getElementById('inline-payment-error').classList.remove('d-none');
            document.getElementById('cash-given').focus();
            return;
        }

        let payMethod = activeTab ? 'cash' : 'banking';

        sendOrderToMySQLBackend({
            order_type: currentOrderType,
            payment_method: payMethod,
            vat_percent: currentVatPercent
        });
    }
}

// TRẢ BÀN THEO CỜ XOAY TUA VS BÀN HẸN CHÍNH
function finishPaymentSuccess(paidGrandTotal, payMethod) {
    checkoutModal.hide();

    shiftStats.totalRevenue += paidGrandTotal;
    if (currentOrderType === 'online') {
        shiftStats.onlineRevenue += paidGrandTotal;
        shiftStats.totalAppSales += paidGrandTotal;
    } else {
        shiftStats.offlineRevenue += paidGrandTotal;
        if (payMethod === 'cash') shiftStats.totalCashSales += paidGrandTotal;
        else shiftStats.totalBankSales += paidGrandTotal;
    }
    shiftStats.totalOrders += 1;

    // Thống kê Ly / Bánh
    cart.forEach(item => {
        let pCat = String(item.category_id || '');
        if ((DB_TOPPING_CAT_ID && pCat === String(DB_TOPPING_CAT_ID)) || pCat === '4') {
            return;
        }
        if (item.isCake || pCat === '5') {
            shiftStats.totalCakes += item.qty;
        } else {
            shiftStats.totalCups += item.qty;
        }
    });

    showCustomAlert("Hóa đơn đã được chốt và lưu dữ liệu thành công!", "Thanh Toán Thành Công", "success");

    if (typeof currentTableId === 'number') {
        let tableBadge = document.getElementById('table-btn-' + currentTableId);
        
        if (isPayDirectMode) {
            if (tableBadge) {
                tableBadge.className = "table-badge status-paid";
                if (tableReservationData[currentTableId]) {
                    tableBadge.querySelector('.status-label').innerText = `[Đã TT - Hẹn ${tableReservationData[currentTableId].time}]`;
                } else {
                    tableBadge.querySelector('.status-label').innerText = "Đã thanh toán";
                }
            }
            document.getElementById('btn-release-table').classList.remove('d-none');
        } 
        else {
            let nextStatus = 'available';
            
            // Nếu là Bàn xoay tua -> trả về Bàn Đã Đặt (reserved / status = 2)
            if (turnaroundTablesMap[currentTableId]) {
                delete turnaroundTablesMap[currentTableId];
                if (tableReservationData[currentTableId]) tableReservationData[currentTableId].isTurnaround = false;

                nextStatus = 'reserved';
                if (tableBadge) {
                    tableBadge.className = "table-badge status-reserved";
                    let rTime = tableReservationData[currentTableId] ? tableReservationData[currentTableId].time : '';
                    tableBadge.querySelector('.status-label').innerText = rTime ? `Đã đặt (${rTime})` : "Đã đặt";
                }
            } else {
                // Nếu là khách hẹn chính hoặc bàn thường -> trả về Trống
                if (tableBadge) {
                    tableBadge.className = "table-badge status-available";
                    tableBadge.querySelector('.status-label').innerText = "Trống";
                }
                delete tableReservationData[currentTableId];
            }

            syncTableStatusToDB(currentTableId, nextStatus);
            document.getElementById('btn-release-table').classList.add('d-none');
        }

        if (tableMergeSourcesMap[currentTableId]) {
            tableMergeSourcesMap[currentTableId].forEach(srcId => {
                let srcBadge = document.getElementById('table-btn-' + srcId);
                if (srcBadge) {
                    if (tableReservationData[srcId] && tableReservationData[srcId].time) {
                        srcBadge.className = "table-badge status-reserved";
                        srcBadge.querySelector('.status-label').innerText = `Đã đặt (${tableReservationData[srcId].time})`;
                        syncTableStatusToDB(srcId, 'reserved');
                    } else {
                        srcBadge.className = "table-badge status-available";
                        srcBadge.querySelector('.status-label').innerText = "Trống";
                        syncTableStatusToDB(srcId, 'available');
                    }
                }
                delete tableMergedTargetMap[srcId];
            });
            delete tableMergeSourcesMap[currentTableId];
        }
    }

    delete activeOrders[currentTableId];

    if (currentOrderType === 'takeaway') {
        activeTakeawayCards = activeTakeawayCards.filter(c => c !== currentTableId);
        if (activeTakeawayCards.length === 0) {
            takeawayCounter++;
            activeTakeawayCards.push("MV-" + String(takeawayCounter).padStart(2, '0'));
        }
    } else if (currentOrderType === 'online') {
        if (onlinePlatform === 'grab') {
            activeGrabCards = activeGrabCards.filter(c => c !== currentTableId);
            if (activeGrabCards.length === 0) {
                onlineGrabCounter++;
                activeGrabCards.push("GRAB-" + onlineGrabCounter);
            }
        } else {
            activeShopeeCards = activeShopeeCards.filter(c => c !== currentTableId);
            if (activeShopeeCards.length === 0) {
                onlineShopeeCounter++;
                activeShopeeCards.push("SHOPEE-" + onlineShopeeCounter);
            }
        }
    }

    cart = [];
    renderCart();
    detachCustomer();

    switchOrderType(currentOrderType);
    fetchReservationsFromDB();
}

function openOrderHistoryModal() {
    let tbody = document.getElementById('order-history-list');
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center text-muted py-4">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải lịch sử đơn hàng trong ca...
            </td>
        </tr>`;

    orderHistoryModal.show();

    let url = '<?= BASE_URL ?>/order/getOrderHistory';
    if (currentShiftInfo.shift_id) {
        url += '?shift_id=' + currentShiftInfo.shift_id;
    }

    fetch(url)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.orders && res.orders.length > 0) {
                tbody.innerHTML = '';
                completedOrdersHistory = res.orders;

                res.orders.forEach((order, index) => {
                    let orderDisplayCode = order.order_code || ('Đơn #' + order.id);
                    let tableDisplay = order.table_id ? ('Bàn ' + order.table_id) : 'Mang về/App';
                    let orderTime = new Date(order.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                    let finalAmount = parseFloat(order.final_amount || 0);

                    let typeBadge = '<span class="badge bg-dark text-white fw-bold px-2 py-1">Tại chỗ</span>';
                    if (order.order_type === 'takeaway') {
                        typeBadge = '<span class="badge bg-warning text-dark fw-bold px-2 py-1">Mang về</span>';
                    } else if (order.order_type === 'grab' || order.order_type === 'shopee') {
                        let shopeeStyle = (order.order_type === 'shopee') ? 'style="background-color:#ff5722 !important;"' : '';
                        typeBadge = `<span class="badge bg-success text-white fw-bold px-2 py-1" ${shopeeStyle}>` + order.order_type.toUpperCase() + '</span>';
                    }

                    tbody.innerHTML += `
                        <tr>
                            <td class="ps-3 fw-bold text-dark">
                                ${orderDisplayCode}
                                <small class="d-block text-muted font-normal" style="font-size: 10px;">${tableDisplay}</small>
                            </td>
                            <td>${typeBadge}</td>
                            <td class="text-muted"><i class="bi bi-clock me-1"></i>${orderTime}</td>
                            <td class="fw-bold text-danger">${finalAmount.toLocaleString()} đ</td>
                            <td class="text-center pe-3">
                                <button class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" onclick="openReprintBillModal(${index})">
                                    <i class="bi bi-printer"></i> In Lại Hóa Đơn
                                </button>
                            </td>
                        </tr>`;
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 text-secondary d-block mb-2"></i>
                            Chưa có đơn hàng nào được ghi nhận trong ca làm việc này.
                        </td>
                    </tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle me-1"></i> Không thể tải dữ liệu lịch sử!
                    </td>
                </tr>`;
        });
}

function openReprintBillModal(index) {
    selectedOrderForReprint = completedOrdersHistory[index];
    if (!selectedOrderForReprint) return;

    let items = selectedOrderForReprint.items || [];
    let itemsHtml = '';

    if (items.length > 0) {
        items.forEach(item => {
            let pName = item.name || item.product_name || 'Sản phẩm';
            let qty = item.qty || item.quantity || 1;
            let price = parseFloat(item.price || 0);
            let isCake = item.isCake || false;

            let sizeText = (isCake || !item.size) ? '' : ` (Size ${item.size})`;
            let sugarIceText = (isCake || (!item.sugar && !item.ice)) ? '' : `<small style="font-size: 9px; display:block; color:#555;">${item.sugar || '100% Đường'}, ${item.ice || '100% Đá'}</small>`;
            
            let toppingText = '';
            if (item.toppings && item.toppings.length > 0) {
                let topList = Array.isArray(item.toppings) ? item.toppings.join(', ') : item.toppings;
                toppingText = `<small style="font-size: 9px; display:block; color:#555;">+ ${topList}</small>`;
            }

            let noteText = item.note ? `<small style="font-size: 9px; display:block; font-style:italic; color:#666;">Note: ${item.note}</small>` : '';

            itemsHtml += `
                <div class="d-flex justify-content-between py-1 border-bottom-dashed text-start">
                    <div style="max-width: 65%;">
                        <div><strong>${qty}x</strong> ${pName}${sizeText}</div>
                        ${sugarIceText}
                        ${toppingText}
                        ${noteText}
                    </div>
                    <div class="fw-semibold">${(price * qty).toLocaleString()}đ</div>
                </div>`;
        });
    } else {
        itemsHtml = `<div class="text-muted text-center py-2" style="font-size:11px;">(Chi tiết sản phẩm đã được lưu trong hệ thống)</div>`;
    }

    let subTotal = parseFloat(selectedOrderForReprint.total_amount || 0);
    let finalAmount = parseFloat(selectedOrderForReprint.final_amount || 0);
    let billVatPercent = parseFloat(selectedOrderForReprint.vat_percent || currentVatPercent);
    
    let vat = subTotal > 0 ? (subTotal * (billVatPercent / 100)) : 0;
    let discount = parseFloat(selectedOrderForReprint.discount || 0);

    if (subTotal === 0 && finalAmount > 0) {
        subTotal = Math.round(finalAmount / (1 + billVatPercent / 100));
        vat = finalAmount - subTotal;
    }

    let receiptTitle = (selectedOrderForReprint.order_type === 'grab' || selectedOrderForReprint.order_type === 'shopee')
        ? `HÓA ĐƠN ĐƠN APP (${(selectedOrderForReprint.order_type).toUpperCase()})`
        : `HÓA ĐƠN THANH TOÁN (SAO BẢN)`;

    let tableInfo = selectedOrderForReprint.table_id ? `BÀN ${selectedOrderForReprint.table_id}` : (selectedOrderForReprint.order_type || 'Mang về').toUpperCase();

    let receiptHtml = `
        <div class="text-center mb-2">
            <h6 class="fw-bold m-0">QUÁN CÀ PHÊ BẤT ỔN</h6>
            <small style="font-size: 10px;">${receiptTitle}</small><br>
            <small style="font-size: 9px;">Thời gian: ${new Date(selectedOrderForReprint.created_at || Date.now()).toLocaleString('vi-VN')}</small>
        </div>
        <div class="border-top border-bottom py-1 mb-2 fw-bold text-start" style="font-size: 11px;">
            VỊ TRÍ: ${tableInfo} | Mã Đơn: ${selectedOrderForReprint.order_code || selectedOrderForReprint.id}
        </div>
        <div class="mb-2">${itemsHtml}</div>
        <div class="border-top pt-1 text-start" style="font-size: 11px;">
            <div class="d-flex justify-content-between"><span>Tạm tính:</span><span>${subTotal.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between"><span>Thuế VAT (${billVatPercent}%):</span><span>${Math.round(vat).toLocaleString()}đ</span></div>
            ${discount > 0 ? `<div class="d-flex justify-content-between text-success"><span>Chiết khấu / Giảm giá:</span><span>-${discount.toLocaleString()}đ</span></div>` : ''}
            <div class="d-flex justify-content-between fw-bold fs-6 mt-1 text-dark border-top pt-1"><span>TỔNG TIỀN:</span><span class="text-danger">${finalAmount.toLocaleString()}đ</span></div>
        </div>
    `;

    document.getElementById('reprint-receipt-content').innerHTML = receiptHtml;
    orderHistoryModal.hide();
    reprintBillModal.show();
}

function confirmReprintBill() {
    reprintBillModal.hide();
    showCustomAlert(`Lệnh in lại hóa đơn [${selectedOrderForReprint.order_code || selectedOrderForReprint.id}] đã được gửi thành công!`, "Thành Công", "success");
}

function openReservationManagerModal() {
    let targetSelect = document.getElementById('reserve-target-table');
    targetSelect.innerHTML = '<option value="">-- Chọn bàn đặt --</option>';

    fetch('<?= BASE_URL ?>/order/getReservations')
        .then(res => res.json())
        .then(res => {
            if (res.success && res.tables) {
                res.tables.forEach(table => {
                    let tid = table.table_id;
                    let tableBadge = document.getElementById('table-btn-' + tid);
                    
                    let isOccupiedOnUI = tableBadge ? tableBadge.classList.contains('status-occupied') : (parseInt(table.table_status) === 1);
                    let hasActiveOrder = activeOrders[tid] && activeOrders[tid].length > 0;

                    let alertText = '';
                    if (table.reservation_id) {
                        alertText = ` ⚠️ (Đã có hẹn: ${table.customer_name} - ${table.reservation_time.substr(11,5)})`;
                    } else if (isOccupiedOnUI || hasActiveOrder) {
                        alertText = ` (Đang có khách)`;
                    }

                    targetSelect.innerHTML += `<option value="${tid}" data-has-res="${table.reservation_id ? 1 : 0}" data-info="${table.customer_name || ''} (${table.reservation_time || ''})">
                        ${table.table_name || ('Bàn ' + tid)} ${alertText}
                    </option>`;
                });
            }
        });

    const now = new Date();
    const todayStr = now.toISOString().split('T')[0];
    const dateInput = document.getElementById('reserve-date');
    dateInput.value = todayStr;
    dateInput.min = todayStr;

    const futureTime = new Date(now.getTime() + 2 * 60 * 60 * 1000);
    const hoursStr = String(futureTime.getHours()).padStart(2, '0');
    const minsStr = String(futureTime.getMinutes()).padStart(2, '0');
    document.getElementById('reserve-time').value = `${hoursStr}:${minsStr}`;

    document.getElementById('reserve-customer-name').value = '';
    document.getElementById('reserve-customer-phone').value = '';
    document.getElementById('reserve-note').value = '';
    document.getElementById('reservation-table-alert-box').classList.add('d-none');

    renderReservationTableList();

    let listTabBtn = new bootstrap.Tab(document.getElementById('res-tab-list-btn'));
    listTabBtn.show();

    reservationManagerModal.show();
}

function checkReservationTableWarning(selectElem) {
    let selectedOption = selectElem.options[selectElem.selectedIndex];
    let alertBox = document.getElementById('reservation-table-alert-box');
    
    if (selectedOption && selectedOption.getAttribute('data-has-res') === '1') {
        let info = selectedOption.getAttribute('data-info');
        alertBox.innerHTML = `⚠️ CẢNH BÁO: Bàn này đã có người đặt trước trong ngày!<br>Thông tin khách cũ: <strong>${info}</strong>. Nhân viên chú ý chủ động thu xếp thời gian.`;
        alertBox.classList.remove('d-none');
    } else {
        alertBox.classList.add('d-none');
    }
}

function renderReservationTableList() {
    let tbody = document.getElementById('reservation-manager-tbody');
    tbody.innerHTML = '';

    let resKeys = Object.keys(tableReservationData).filter(k => tableReservationData[k] && tableReservationData[k].name);
    document.getElementById('resCountBadgeDisplay').innerText = resKeys.length + " Lượt đặt";

    if (resKeys.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Chưa có thông tin đặt bàn trước.</td></tr>`;
    } else {
        resKeys.forEach(tid => {
            let res = tableReservationData[tid];
            let tableBadge = document.getElementById('table-btn-' + tid);
            let tableName = tableBadge ? tableBadge.querySelector('.fw-bold').innerText : ('Bàn ' + tid);
            tbody.innerHTML += `
                <tr>
                    <td class="ps-3 fw-bold text-dark">${tableName}</td>
                    <td class="fw-semibold">${res.name}</td>
                    <td>${res.phone}</td>
                    <td><span class="badge bg-amber-subtle text-amber fw-bold">${res.date} ${res.time}</span></td>
                    <td class="text-muted">${res.note || '-'}</td>
                    <td class="text-center pe-3">
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1" onclick="openCancelModalForTable(${tid}, '${tableName}')">
                            <i class="bi bi-x-circle me-1"></i> Hủy Đặt
                        </button>
                    </td>
                </tr>`;
        });
    }
}

function confirmReserveTable() {
    let targetElem = document.getElementById('reserve-target-table');
    let tid = targetElem ? targetElem.value : null;
    let cName = document.getElementById('reserve-customer-name').value.trim();
    let cPhone = document.getElementById('reserve-customer-phone').value.trim();
    let rDate = document.getElementById('reserve-date').value;
    let rTime = document.getElementById('reserve-time').value;
    let rNote = document.getElementById('reserve-note').value.trim();

    if (!tid || isNaN(tid) || parseInt(tid) <= 0) {
        showCustomAlert("Vui lòng chọn bàn hợp lệ để đặt!", "Chưa Chọn Bàn", "danger");
        return;
    }

    if (!cName || !cPhone || !rDate || !rTime) {
        showCustomAlert("Vui lòng điền đầy đủ Tên khách hàng, Số điện thoại, Ngày và Giờ hẹn!", "Thiếu Thông Tin", "danger");
        return;
    }

    if (cPhone.length !== 10 || !cPhone.startsWith('0')) {
        showCustomAlert("Số điện thoại phải đúng 10 chữ số định dạng Việt Nam!", "SĐT Không Hợp Lệ", "danger");
        return;
    }

    let parsedTableId = parseInt(tid);

    fetch('<?= BASE_URL ?>/order/reserveTable', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            table_id: parsedTableId,
            customer_name: cName,
            customer_phone: cPhone,
            reserve_date: rDate,
            reserve_time: rTime,
            reserve_note: rNote
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            tableReservationData[parsedTableId] = {
                name: cName,
                phone: cPhone,
                date: rDate,
                time: rTime,
                note: rNote,
                isTurnaround: false
            };

            let tableBadge = document.getElementById('table-btn-' + parsedTableId);
            if (tableBadge) {
                tableBadge.className = "table-badge status-reserved";
                tableBadge.querySelector('.status-label').innerText = `Đã đặt (${rTime})`;
            }

            reservationManagerModal.hide();
            showCustomAlert(`Đã tạo lịch đặt bàn thành công cho khách hàng ${cName} vào ${rTime} ngày ${rDate}!`, "Thành Công", "success");
        } else {
            showCustomAlert(res.message || "Không thể lưu lịch đặt bàn!", "Lỗi Đặt Bàn", "danger");
        }
    })
    .catch(err => {
        showCustomAlert("Đã xảy ra lỗi hệ thống khi lưu dữ liệu đặt bàn!", "Lỗi Máy Chủ", "danger");
    });
}

function openCancelModalForTable(tid, tableName) {
    document.getElementById('cancel-target-table-id').value = tid;
    document.getElementById('cancel-table-title-id').innerText = `(${tableName})`;
    document.getElementById('cancel-reserve-reason-select').value = 'Khách báo hủy lịch';
    document.getElementById('cancel-other-reason-box').classList.add('d-none');
    document.getElementById('cancel-reserve-other-reason').value = '';

    cancelReserveModal.show();
}

function toggleCancelOtherReason(elem) {
    let box = document.getElementById('cancel-other-reason-box');
    if (elem.value === 'other') {
        box.classList.remove('d-none');
    } else {
        box.classList.add('d-none');
    }
}

function confirmCancelReserve() {
    let tid = document.getElementById('cancel-target-table-id').value;
    let reasonSelect = document.getElementById('cancel-reserve-reason-select').value;
    let reasonStr = reasonSelect;

    if (reasonSelect === 'other') {
        reasonStr = document.getElementById('cancel-reserve-other-reason').value.trim() || "Lý do khác";
    }

    fetch('<?= BASE_URL ?>/order/cancelReservation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            table_id: parseInt(tid),
            reason: reasonStr
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            delete tableReservationData[tid];
            delete turnaroundTablesMap[tid];

            let tableBadge = document.getElementById('table-btn-' + tid);
            if (tableBadge) {
                tableBadge.className = "table-badge status-available";
                tableBadge.querySelector('.status-label').innerText = "Trống";
            }

            cancelReserveModal.hide();
            if (reservationManagerModal) reservationManagerModal.hide();
            showCustomAlert(`Đã hủy lịch đặt bàn thành công. Lý do: ${reasonStr}`, "Thành Công", "success");
        } else {
            showCustomAlert(res.message || "Không thể cập nhật hủy đặt bàn!", "Thất Bại", "danger");
        }
    })
    .catch(err => {
        showCustomAlert("Không thể hủy lịch đặt bàn. Vui lòng kiểm tra lại!", "Thất Bại", "danger");
    });
}

async function openShiftModal() {
    if (!isShiftOpened) {
        document.getElementById('shiftModalTitle').innerHTML = `<i class="bi bi-door-open text-primary"></i> MỞ CA LÀM VIỆC`;
        document.getElementById('shift-open-form').classList.remove('d-none');
        document.getElementById('shift-close-summary-tabs').classList.add('d-none');
        document.getElementById('shift-modal-close-btn').classList.add('d-none');

        const currentHour = new Date().getHours();
        let defaultShift = 'morning';
        if (currentHour >= 14 && currentHour < 22) defaultShift = 'afternoon';
        if (currentHour >= 22 || currentHour < 6) defaultShift = 'night';
        document.getElementById('shift-type-select').value = defaultShift;
        document.getElementById('shift-initial-cash').value = '0';

    } else {
        await checkOpenShiftFromDB();

        document.getElementById('shiftModalTitle').innerHTML = `<i class="bi bi-clock-history text-primary"></i> QUẢN LÝ & KẾT CA LÀM VIỆC`;
        document.getElementById('shift-open-form').classList.add('d-none');
        document.getElementById('shift-close-summary-tabs').classList.remove('d-none');
        document.getElementById('shift-modal-close-btn').classList.remove('d-none');

        document.getElementById('stat-total-revenue').innerText = shiftStats.totalRevenue.toLocaleString() + " đ";
        document.getElementById('stat-cash-sales').innerText    = shiftStats.totalCashSales.toLocaleString() + " đ";
        document.getElementById('stat-bank-sales').innerText    = shiftStats.totalBankSales.toLocaleString() + " đ";
        document.getElementById('stat-app-sales').innerText     = shiftStats.totalAppSales.toLocaleString() + " đ";
        document.getElementById('stat-total-orders').innerText   = shiftStats.totalOrders + " Đơn";
        document.getElementById('stat-total-cups').innerText     = shiftStats.totalCups + " Ly";
        document.getElementById('stat-total-cakes').innerText    = shiftStats.totalCakes + " Bánh";

        document.getElementById('shift-ending-cash-actual').value = '';
        calculateShiftDifference();
        renderShiftReportPreview();
    }

    shiftModal.show();
}

function calculateShiftDifference() {
    let expectedCash = currentShiftInfo.initialCash + shiftStats.totalCashSales;
    let actualInput = document.getElementById('shift-ending-cash-actual');
    let actualCash = actualInput ? (parseFloat(actualInput.value) || 0) : 0;

    let diff = actualCash - expectedCash;
    let diffElem = document.getElementById('shift-cash-diff-display');

    if (diffElem) {
        if (diff === 0) {
            diffElem.innerText = "0 đ (Khớp két)";
            diffElem.className = "form-control form-control-lg rounded-3 fw-bold bg-light text-success";
        } else if (diff > 0) {
            diffElem.innerText = "+" + diff.toLocaleString() + " đ (Thừa tiền)";
            diffElem.className = "form-control form-control-lg rounded-3 fw-bold bg-light text-primary";
        } else {
            diffElem.innerText = diff.toLocaleString() + " đ (Thiếu tiền)";
            diffElem.className = "form-control form-control-lg rounded-3 fw-bold bg-light text-danger";
        }
    }

    renderShiftReportPreview(actualCash, diff);
}

function renderShiftReportPreview(actualCash = 0, diff = 0) {
    let expectedCash = currentShiftInfo.initialCash + shiftStats.totalCashSales;
    let reportReceiptHtml = `
        <div class="text-center mb-2">
            <h6 class="fw-bold m-0">QUÁN CÀ PHÊ BẤT ỔN</h6>
            <small style="font-size: 10px;">BÁO CÁO KẾT CA BÀN GIAO KÉT TIỀN</small><br>
            <small style="font-size: 9px;">Thời gian: ${new Date().toLocaleString()}</small>
        </div>
        <div class="border-top border-bottom py-1 mb-2 fw-bold" style="font-size: 11px;">
            CA: ${currentShiftInfo.shiftName} | NV: ${currentShiftInfo.cashierId}
        </div>
        <div class="mb-2" style="font-size: 11px;">
            <div class="d-flex justify-content-between"><span>Tiền đầu ca (Initial):</span><span>${currentShiftInfo.initialCash.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between"><span>Doanh thu Tiền mặt:</span><span>${shiftStats.totalCashSales.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between"><span>Doanh thu Chuyển khoản:</span><span>${shiftStats.totalBankSales.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between"><span>Doanh thu App Online:</span><span>${shiftStats.totalAppSales.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between fw-bold text-dark border-top pt-1 mt-1"><span>TỔNG DOANH THU:</span><span>${shiftStats.totalRevenue.toLocaleString()}đ</span></div>
            <div class="border-top pt-1 mt-1">
                <div class="d-flex justify-content-between"><span>Tiền mặt lý thuyết:</span><span>${expectedCash.toLocaleString()}đ</span></div>
                <div class="d-flex justify-content-between"><span>Tiền mặt kiểm kê:</span><span>${actualCash.toLocaleString()}đ</span></div>
                <div class="d-flex justify-content-between fw-bold ${diff < 0 ? 'text-danger' : 'text-success'}"><span>Chênh lệch két:</span><span>${diff.toLocaleString()}đ</span></div>
            </div>
        </div>
    `;
    let previewElem = document.getElementById('shift-report-thermal-preview');
    if (previewElem) previewElem.innerHTML = reportReceiptHtml;
}

function confirmOpenShift() {
    let cashierId = document.getElementById('shift-cashier-id').value.trim();
    let pass = document.getElementById('shift-cashier-pass').value.trim();
    let shiftType = document.getElementById('shift-type-select').value;
    let initialCash = parseFloat(document.getElementById('shift-initial-cash').value) || 0;

    if (!cashierId || !pass) {
        showCustomAlert("Vui lòng nhập đầy đủ Mã Thu Ngân và Mật khẩu xác thực ca!", "Xác Thực Thất Bại", "danger");
        return;
    }

    let correctPass = '';
    let shiftText = '';
    if (shiftType === 'morning') { correctPass = '123'; shiftText = 'Ca Sáng (06:00 - 14:00)'; }
    if (shiftType === 'afternoon') { correctPass = '456'; shiftText = 'Ca Chiều (14:00 - 22:00)'; }
    if (shiftType === 'night') { correctPass = '789'; shiftText = 'Ca Đêm (22:00 - 06:00)'; }

    if (pass !== correctPass) {
        showCustomAlert(`Mật khẩu xác thực ca làm việc không chính xác! (Gợi ý: Ca Sáng: 123 | Ca Chiều: 456 | Ca Đêm: 789)`, "Sai Mật Khẩu", "danger");
        return;
    }

    fetch('<?= BASE_URL ?>/order/openShift', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cashier_id: cashierId,
            shift_type: shiftType,
            shift_name: shiftText,
            initial_cash: initialCash
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            isShiftOpened = true;
            currentShiftInfo = { 
                shift_id: res.shift_id || null,
                cashierId: cashierId, 
                shiftType: shiftType, 
                shiftName: shiftText,
                initialCash: initialCash
            };

            sessionStorage.setItem('pos_active_shift', JSON.stringify(currentShiftInfo));

            document.getElementById('shift-status-badge').className = "badge bg-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
            document.getElementById('current-shift-name-label').innerText = shiftText;
            document.getElementById('cashier-id-display').innerText = cashierId;
            document.getElementById('right-panel-cashier').innerText = cashierId;

            shiftModal.hide();
            showCustomAlert(`Mở ca làm việc thành công cho Thu ngân [${cashierId}]!`, "Thành Công", "success");
        } else {
            showCustomAlert(res.message || "Không thể mở ca làm việc!", "Lỗi Giờ Ca", "danger");
        }
    })
    .catch(err => {
        showCustomAlert("Không thể mở ca làm việc. Vui lòng kiểm tra lại!", "Lỗi Hệ Thống", "danger");
    });
}

function confirmCloseShift() {
    let actualInput = document.getElementById('shift-ending-cash-actual');
    let endingCashActual = actualInput ? (parseFloat(actualInput.value) || 0) : 0;

    let payload = {
        shift_id: currentShiftInfo.shift_id || null,
        total_revenue: shiftStats.totalRevenue,
        offline_revenue: shiftStats.offlineRevenue,
        online_revenue: shiftStats.onlineRevenue,
        total_cash_sales: shiftStats.totalCashSales,
        total_bank_sales: shiftStats.totalBankSales,
        total_app_sales: shiftStats.totalAppSales,
        ending_cash_actual: endingCashActual,
        total_orders: shiftStats.totalOrders,
        total_cups: shiftStats.totalCups,
        total_cakes: shiftStats.totalCakes
    };

    fetch('<?= BASE_URL ?>/order/closeShift', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            isShiftOpened = false;
            sessionStorage.removeItem('pos_active_shift');
            shiftStats = { totalRevenue: 0, offlineRevenue: 0, onlineRevenue: 0, totalCashSales: 0, totalBankSales: 0, totalAppSales: 0, totalOrders: 0, totalCups: 0, totalCakes: 0 };
            completedOrdersHistory = [];

            document.getElementById('shift-status-badge').className = "badge bg-danger rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
            document.getElementById('current-shift-name-label').innerText = "Chưa Mở Ca";
            document.getElementById('cashier-id-display').innerText = "--";
            document.getElementById('right-panel-cashier').innerText = "--";

            shiftModal.hide();
            showCustomAlert("Đã hoàn tất kết ca và lưu báo cáo thành công!", "Kết Ca Thành Công", "success");
            
            setTimeout(() => {
                openShiftModal();
            }, 500);
        } else {
            showCustomAlert(res.message || "Không thể thực hiện kết ca!", "Thất Bại", "danger");
        }
    })
    .catch(err => {
        showCustomAlert("Không thể thực hiện kết ca. Vui lòng kiểm tra lại!", "Thất Bại", "danger");
    });
}

function syncTableStatusToDB(tableId, status, keepStatus = false) {
    if (typeof tableId !== 'number') return;
    fetch('<?= BASE_URL ?>/order/updateTableStatus', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ table_id: tableId, status: status, keep_status: keepStatus })
    });
}

function checkMergeSameArea() {
    let srcId = document.getElementById('merge-source-table').value;
    let targetId = document.getElementById('merge-target-table').value;
    let keepBox = document.getElementById('same-area-keep-source-box');

    let srcBtn = document.getElementById('table-btn-' + srcId);
    let targetBtn = document.getElementById('table-btn-' + targetId);

    if (srcBtn && targetBtn) {
        let srcArea = srcBtn.getAttribute('data-area-id');
        let targetArea = targetBtn.getAttribute('data-area-id');
        if (srcArea === targetArea) {
            keepBox.classList.remove('d-none');
            return;
        }
    }
    keepBox.classList.add('d-none');
    document.getElementById('chk-keep-source-state').checked = false;
}

function openMergeTableModal() {
    let sourceSelect = document.getElementById('merge-source-table');
    let targetSelect = document.getElementById('merge-target-table');

    sourceSelect.innerHTML = '';
    targetSelect.innerHTML = '';

    Object.keys(activeOrders).forEach(tableId => {
        if (activeOrders[tableId] && activeOrders[tableId].length > 0) {
            let tableBadge = document.getElementById('table-btn-' + tableId);
            if (tableBadge && tableBadge.classList.contains('status-occupied')) {
                let tableName = tableBadge.querySelector('.fw-bold').innerText;
                sourceSelect.innerHTML += `<option value="${tableId}">${tableName} (Đang có đơn)</option>`;
                targetSelect.innerHTML += `<option value="${tableId}">${tableName} (Đang có đơn)</option>`;
            }
        }
    });

    if (sourceSelect.children.length === 0) {
        showCustomAlert("Yêu cầu tối thiểu 2 bàn đang phục vụ để thực hiện gộp hóa đơn!", "Không Đủ Điều Kiện", "info");
        return;
    }

    checkMergeSameArea();
    mergeTableModal.show();
}

function confirmMergeTable() {
    let src = document.getElementById('merge-source-table').value;
    let target = document.getElementById('merge-target-table').value;
    let isKeepState = document.getElementById('chk-keep-source-state').checked;

    if (src === target) {
        showCustomAlert("Bàn nguồn và Bàn đích không được trùng lặp!", "Thao Tác Không Hợp Lệ", "danger");
        return;
    }

    if (!activeOrders[target]) activeOrders[target] = [];
    
    activeOrders[target] = activeOrders[target].concat(activeOrders[src]);
    delete activeOrders[src];

    let srcBadge = document.getElementById('table-btn-' + src);
    let targetBadge = document.getElementById('table-btn-' + target);

    let srcName = srcBadge ? srcBadge.querySelector('.fw-bold').innerText : ("Bàn " + src);
    let targetName = targetBadge ? targetBadge.querySelector('.fw-bold').innerText : ("Bàn " + target);

    if (targetBadge) {
        targetBadge.className = "table-badge status-occupied";
        targetBadge.querySelector('.status-label').innerText = "Có khách";
        syncTableStatusToDB(parseInt(target), 'occupied', true);
    }

    if (!isKeepState) {
        if (srcBadge) {
            srcBadge.className = "table-badge status-available";
            srcBadge.querySelector('.status-label').innerText = "Trống";
        }
        syncTableStatusToDB(parseInt(src), 'available', false);
    } else {
        if (!tableMergeSourcesMap[target]) tableMergeSourcesMap[target] = [];
        tableMergeSourcesMap[target].push(src);
        tableMergedTargetMap[src] = target;

        if (srcBadge) {
            srcBadge.className = "table-badge status-occupied";
            srcBadge.querySelector('.status-label').innerText = `[Gộp -> ${targetName}]`;
        }
        syncTableStatusToDB(parseInt(src), 'occupied', true);
    }

    mergeTableModal.hide();
    showCustomAlert(`Gộp hóa đơn thành công từ ${srcName} sang ${targetName}!`, "Thành Công", "success");
    
    if (String(currentTableId) === String(src)) {
        selectTable(parseInt(target), targetName);
    } else {
        loadCartForCurrentTable();
    }
}

function openSplitTableModal() {
    let sourceSelect = document.getElementById('split-source-table');
    sourceSelect.innerHTML = '';

    Object.keys(activeOrders).forEach(tableId => {
        if (activeOrders[tableId] && activeOrders[tableId].length > 0) {
            let tableBadge = document.getElementById('table-btn-' + tableId);
            if (tableBadge && tableBadge.classList.contains('status-occupied')) {
                let tableName = tableBadge.querySelector('.fw-bold').innerText;
                sourceSelect.innerHTML += `<option value="${tableId}">${tableName}</option>`;
            }
        }
    });

    if (sourceSelect.children.length === 0) {
        showCustomAlert("Hiện chưa có bàn phục vụ để thực hiện chuyển món!", "Thông Báo", "info");
        return;
    }

    document.getElementById('chk-select-all-split').checked = false;
    loadSplitItems();
    splitTableModal.show();
}

function loadSplitItems() {
    let srcTable = document.getElementById('split-source-table').value;
    let container = document.getElementById('split-items-list');
    let targetSelect = document.getElementById('split-target-table');

    container.innerHTML = '';
    targetSelect.innerHTML = '';

    if (activeOrders[srcTable]) {
        activeOrders[srcTable].forEach((item, idx) => {
            container.innerHTML += `
                <div class="form-check py-1 border-bottom">
                    <input class="form-check-input split-item-chk" type="checkbox" value="${idx}" id="chk-split-${idx}">
                    <label class="form-check-label d-flex justify-content-between w-100" for="chk-split-${idx}">
                        <span>${item.name} ${item.isCake ? '' : `(${item.size || 'M'})`}</span>
                        <span class="fw-bold">${item.price.toLocaleString()}đ</span>
                    </label>
                </div>`;
        });
    }

    let now = new Date();

    document.querySelectorAll('#table-section .table-badge').forEach(badge => {
        let tid = badge.id.replace('table-btn-', '');
        let tname = badge.querySelector('.fw-bold').innerText;
        let cleanName = removeVietnameseTones(tname);

        if (cleanName.includes('mang ve') || cleanName.includes('mv-') || cleanName.includes('grab') || cleanName.includes('shopee') || cleanName.includes('app')) {
            return;
        }

        let isAvailable = badge.classList.contains('status-available');
        let isReserved = badge.classList.contains('status-reserved');

        if (tid != srcTable) {
            if (isAvailable) {
                targetSelect.innerHTML += `<option value="${tid}">${tname} (Bàn trống)</option>`;
            } else if (isReserved) {
                let resData = tableReservationData[tid] || {};
                if (resData.date && resData.time) {
                    let resTime = new Date(`${resData.date}T${resData.time}`);
                    let diffHours = (resTime - now) / (1000 * 60 * 60);
                    if (diffHours >= 1.0) {
                        targetSelect.innerHTML += `<option value="${tid}">${tname} (Bàn đã đặt - Xoay tua)</option>`;
                    }
                }
            }
        }
    });
}

function toggleSelectAllSplitItems(masterChk) {
    document.querySelectorAll('.split-item-chk').forEach(chk => {
        chk.checked = masterChk.checked;
    });
}

function confirmSplitTable() {
    let srcTable = document.getElementById('split-source-table').value;
    let targetTable = document.getElementById('split-target-table').value;
    let selectedChks = document.querySelectorAll('.split-item-chk:checked');

    if (!targetTable) {
        showCustomAlert("Hiện không có bàn trống hợp lệ để thực hiện chuyển tới!", "Thiếu Bàn Đích", "danger");
        return;
    }

    if (selectedChks.length === 0) {
        showCustomAlert("Vui lòng chọn tối thiểu 1 sản phẩm để thực hiện chuyển bàn!", "Thiếu Sản Phẩm", "danger");
        return;
    }

    let itemsToMove = [];
    let indexesToMove = Array.from(selectedChks).map(c => parseInt(c.value)).sort((a,b) => b-a);

    indexesToMove.forEach(idx => {
        itemsToMove.push(activeOrders[srcTable][idx]);
        activeOrders[srcTable].splice(idx, 1);
    });

    if (!activeOrders[targetTable]) activeOrders[targetTable] = [];
    activeOrders[targetTable] = activeOrders[targetTable].concat(itemsToMove);

    let srcBadge = document.getElementById('table-btn-' + srcTable);
    let targetBadge = document.getElementById('table-btn-' + targetTable);

    if (targetBadge) {
        if (targetBadge.classList.contains('status-reserved')) {
            let resData = tableReservationData[targetTable] || {};
            confirmTurnaroundTable(targetTable, targetBadge.querySelector('.fw-bold').innerText, resData.time || 'giờ hẹn');
        } else {
            targetBadge.className = "table-badge status-occupied";
            targetBadge.querySelector('.status-label').innerText = "Có khách";
            syncTableStatusToDB(parseInt(targetTable), 'occupied');
        }
    }

    if (srcBadge) {
        if (!activeOrders[srcTable] || activeOrders[srcTable].length === 0) {
            delete activeOrders[srcTable];
            srcBadge.className = "table-badge status-available";
            srcBadge.querySelector('.status-label').innerText = "Trống";
            syncTableStatusToDB(parseInt(srcTable), 'available');
        }
    }

    splitTableModal.hide();
    showCustomAlert(`Đã chuyển ${itemsToMove.length} sản phẩm sang bàn mới thành công!`, "Thành Công", "success");
    loadCartForCurrentTable();
}

function filterCategory(catId, btn) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active-cat'));
    btn.classList.add('active-cat');

    const items = document.querySelectorAll('.product-item');
    items.forEach(item => {
        let itemCat = item.getAttribute('data-category');
        if (catId === 'all') {
            item.style.display = 'block';
        } else {
            if (itemCat == catId) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        }
    });
}

function searchProduct() {
    let rawKeyword = document.getElementById('search-product').value;
    let keyword = removeVietnameseTones(rawKeyword);

    const items = document.querySelectorAll('.product-item');
    items.forEach(item => {
        let rawName = item.getAttribute('data-name');
        let cleanName = removeVietnameseTones(rawName);
        if (cleanName.includes(keyword)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function updateProductPricesDisplay() {
    document.querySelectorAll('[id^="product-price-"]').forEach(priceElem => {
        let basePrice = parseFloat(priceElem.getAttribute('data-base-price'));
        let productId = priceElem.id.replace('product-price-', '');
        let onlineBadge = document.getElementById('online-badge-' + productId);

        if (currentOrderType === 'online') {
            let onlinePrice = basePrice * 1.1;
            priceElem.innerText = Math.round(onlinePrice).toLocaleString() + " đ";
            if (onlineBadge) onlineBadge.classList.remove('d-none');
        } else {
            priceElem.innerText = basePrice.toLocaleString() + " đ";
            if (onlineBadge) onlineBadge.classList.add('d-none');
        }
    });
}

function searchCustomerByPhone() {
    let phone = document.getElementById('cust-phone-input').value.trim();
    if (!phone) {
        showCustomAlert("Vui lòng nhập số điện thoại để tra cứu thành viên!", "Thiếu Số Điện Thoại", "info");
        return;
    }

    fetch('<?= BASE_URL ?>/order/searchCustomer?phone=' + encodeURIComponent(phone))
        .then(res => res.json())
        .then(res => {
            if (res.success && res.customer) {
                selectedCustomer = res.customer;
                displayCustomerInfo();
                showCustomAlert(`Xác nhận thông tin thành viên: ${selectedCustomer.customer_name}`, "Thành Công", "success");
            } else {
                showCustomAlert(res.message || "Không tìm thấy thông tin thành viên!", "Không Tìm Thấy", "danger");
                detachCustomer();
            }
        })
        .catch(err => {
            showCustomAlert("Đã xảy ra lỗi kết nối khi tra cứu thành viên!", "Lỗi Hệ Thống", "danger");
        });
}

// DISPLAY CUSTOMER INFO VỚI QUY CHUẨN 5 HẠNG
function displayCustomerInfo() {
    if (!selectedCustomer) return;
    
    let cName = selectedCustomer.customer_name || 'Khách hàng';
    let cPoints = parseInt(selectedCustomer.total_points || 0);
    let cRank = selectedCustomer.member_rank || 'Bronze';

    document.getElementById('cust-name-val').innerText = cName;
    document.getElementById('cust-points-val').innerText = cPoints.toLocaleString();

    let rankBadgeElem = document.getElementById('cust-rank-badge');

    if (cRank === 'VIP') {
        rankBadgeElem.className = "badge bg-dark text-warning fw-bold shadow-sm px-2 py-1";
        rankBadgeElem.innerHTML = `<i class="bi bi-crown-fill me-1"></i> VIP (-15%)`;
    } else if (cRank === 'Diamond') {
        rankBadgeElem.className = "badge bg-primary text-white fw-bold shadow-sm px-2 py-1";
        rankBadgeElem.innerHTML = `<i class="bi bi-gem me-1"></i> DIAMOND (-10%)`;
    } else if (cRank === 'Gold') {
        rankBadgeElem.className = "badge bg-warning text-dark fw-bold shadow-sm px-2 py-1";
        rankBadgeElem.innerHTML = `<i class="bi bi-trophy-fill me-1"></i> GOLD (-5%)`;
    } else if (cRank === 'Silver') {
        rankBadgeElem.className = "badge bg-info text-dark fw-bold px-2 py-1";
        rankBadgeElem.innerHTML = `<i class="bi bi-award-fill me-1"></i> SILVER (0%)`;
    } else {
        rankBadgeElem.className = "badge bg-secondary text-white px-2 py-1";
        rankBadgeElem.innerHTML = `<i class="bi bi-person-fill me-1"></i> BRONZE (0%)`;
    }

    document.getElementById('cust-info-display').classList.remove('d-none');
    document.getElementById('cust-info-display').classList.add('d-flex');

    renderCart();
}

function detachCustomer() {
    selectedCustomer = null;
    appliedPointsDiscount = 0;
    pointsToRedeem = 0;
    document.getElementById('cust-info-display').classList.add('d-none');
    document.getElementById('cust-phone-input').value = '';
    document.getElementById('redeem-points-input').value = '0';
    let manualElem = document.getElementById('manual-redeem-pts');
    if (manualElem) manualElem.value = '';
    document.getElementById('loyalty-discount-msg').classList.add('d-none');
    document.getElementById('loyalty-discount-row').classList.add('d-none');
    renderCart();
}

function openRegisterCustomerModal() {
    document.getElementById('reg-cust-phone').value = '';
    document.getElementById('reg-cust-name').value = '';
    if (registerCustomerModal) registerCustomerModal.show();
}

function confirmRegisterCustomer() {
    let phone = document.getElementById('reg-cust-phone').value.trim();
    let name = document.getElementById('reg-cust-name').value.trim();

    if (!phone || !name) {
        showCustomAlert("Vui lòng điền đầy đủ Họ tên và Số điện thoại!", "Thiếu Thông Tin", "danger");
        return;
    }

    if (phone.length !== 10 || !phone.startsWith('0')) {
        showCustomAlert("Số điện thoại phải đúng 10 chữ số hợp lệ!", "SĐT Không Hợp Lệ", "danger");
        return;
    }

    fetch('<?= BASE_URL ?>/order/registerCustomer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name, phone: phone })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            selectedCustomer = res.customer;
            displayCustomerInfo();
            if (registerCustomerModal) registerCustomerModal.hide();
            showCustomAlert("Đăng ký thành viên mới thành công!", "Thành Công", "success");
        } else {
            showCustomAlert(res.message || "Không thể hoàn tất đăng ký!", "Lỗi Đăng Ký", "danger");
        }
    })
    .catch(err => {
        showCustomAlert("Lỗi hệ thống trong quá trình tạo tài khoản thành viên!", "Lỗi Máy Chủ", "danger");
    });
}

// GỬI % VAT ĐỘNG SANG BACKEND KHI TẠO ĐƠN
function sendOrderToMySQLBackend(extraData = {}) {
    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;

    let pointsDiscountMoney = 0;
    let tierDiscountMoney = 0;

    if (currentOrderType !== 'online') {
        pointsDiscountMoney = appliedPointsDiscount;
        let remainingAfterPoints = Math.max(0, baseGrandTotal - pointsDiscountMoney);

        if (selectedCustomer) {
            let discountPercent = selectedCustomer.discount_percent || 0;
            if (discountPercent > 0) tierDiscountMoney = Math.round(remainingAfterPoints * (discountPercent / 100));
        }
    }

    let totalDiscount = pointsDiscountMoney + tierDiscountMoney;
    let grandTotal = Math.max(0, Math.round(baseGrandTotal - totalDiscount));

    let generatedOrderCode = '';
    if (typeof currentTableId === 'string') {
        generatedOrderCode = currentTableId + '-' + Date.now();
    } else if (typeof currentTableId === 'number') {
        generatedOrderCode = 'TB' + currentTableId + '-' + Date.now();
    } else {
        generatedOrderCode = 'HD' + Date.now();
    }

    let payload = {
        table_id: typeof currentTableId === 'number' ? currentTableId : null,
        shift_id: currentShiftInfo.shift_id || null,
        order_code: extraData.app_order_code || generatedOrderCode,
        customer_id: selectedCustomer ? selectedCustomer.id : null,
        items: cart,
        total_amount: subTotal,
        discount: totalDiscount,
        vat_percent: currentVatPercent,
        final_amount: grandTotal,
        points_redeemed: pointsToRedeem,
        customer_pay: parseFloat(document.getElementById('cash-given')?.value || grandTotal),
        change_amount: parseFloat(document.getElementById('cash-change')?.innerText.replace(/[^0-9]/g, '') || 0),
        ...extraData
    };

    fetch('<?= BASE_URL ?>/order/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            finishPaymentSuccess(grandTotal, extraData.payment_method || 'cash');
            detachCustomer();
        } else {
            showCustomAlert(res.message || "Không thể lưu hóa đơn. Vui lòng thử lại!", "Thanh Toán Thất Bại", "danger");
        }
    })
    .catch(err => {
        showCustomAlert("Đã xảy ra lỗi hệ thống khi kết nối!", "Lỗi Máy Chủ", "danger");
    });
}
</script>

<?php 
$footerPath = __DIR__ . '/../inc/footer.php';
if (!file_exists($footerPath)) {
    $footerPath = __DIR__ . '/../layouts/footer.php';
}
if (file_exists($footerPath)) {
    require_once $footerPath;
}
?>
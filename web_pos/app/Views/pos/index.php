<?php 
$headerPath = __DIR__ . '/../inc/header.php';
if (!file_exists($headerPath)) {
    $headerPath = __DIR__ . '/../layouts/header.php';
}
if (file_exists($headerPath)) {
    require_once $headerPath;
}

// XÁC ĐỊNH ID DANH MỤC TOPPING NẾU CÓ TRONG BIẾN $categories
$toppingCatId = null;
if (!empty($categories)) {
    foreach ($categories as $cat) {
        $catNameLower = mb_strtolower($cat['name'], 'UTF-8');
        if (strpos($catNameLower, 'topping') !== false || strpos($catNameLower, 'thạch') !== false) {
            $toppingCatId = $cat['id'];
            break;
        }
    }
}
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

    /* Top Bar Segmented Control Redesign - Clean & Flat */
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

    /* Segmented Control Order Types */
    .order-type-tabs { background: #f1f5f9; padding: 4px; border-radius: 12px; display: flex; gap: 4px; border: 1px solid var(--pos-border); }
    .order-type-btn { flex: 1; border: none; background: transparent; padding: 9px 12px; font-weight: 700; font-size: 0.85rem; color: var(--pos-text-muted); border-radius: 9px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
    .order-type-btn.active { background: var(--pos-dark); color: #ffffff; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.15); }

    /* Subtabs Online */
    .online-subtabs { display: flex; gap: 8px; margin-top: 8px; }
    .subtab-btn { flex: 1; border: 1px solid var(--pos-border); background: #ffffff; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--pos-text-muted); }
    .subtab-btn.active-grab { background: #00b14f; color: #ffffff; border-color: #00b14f; }
    .subtab-btn.active-shopee { background: #ee4d2d; color: #ffffff; border-color: #ee4d2d; }

    /* Cards */
    .pos-card { background: var(--pos-card-bg); border: 1px solid var(--pos-border); border-radius: 14px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02); }

    /* Bàn Badges Slate */
    .table-badge { cursor: pointer; transition: all 0.2s ease; border: 1px solid var(--pos-border); border-radius: 10px; padding: 8px 10px; background-color: #ffffff; position: relative; }
    .table-badge:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05); border-color: var(--pos-dark); }
    .table-badge.active { border: 2px solid var(--pos-dark) !important; background-color: #f8fafc !important; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.1) !important; }
    .status-available { border-left: 4px solid #10b981; }
    .status-occupied { border-left: 4px solid #ef4444; background-color: #fef2f2; }
    .status-paid { border-left: 4px solid #10b981; background-color: #ecfdf5; }
    .status-reserved { border-left: 4px solid #f59e0b; background-color: #fffbeb; }

    /* Category Buttons */
    .cat-btn { border-radius: 20px; padding: 6px 16px; font-weight: 600; font-size: 0.82rem; transition: all 0.2s ease; border: 1px solid var(--pos-border); background: #ffffff; color: var(--pos-text-muted); }
    .cat-btn:hover { background: #f1f5f9; color: var(--pos-text-main); }
    .cat-btn.active-cat { background: var(--pos-dark) !important; color: #ffffff !important; border-color: var(--pos-dark) !important; }

    /* Product Cards */
    .product-card { cursor: pointer; transition: all 0.2s ease; border: 1px solid var(--pos-border); border-radius: 12px; overflow: hidden; background: #ffffff; }
    .product-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(15, 23, 42, 0.06); border-color: var(--pos-dark); }
    .product-img-wrapper { overflow: hidden; height: 105px; background-color: #f1f5f9; position: relative; }
    .product-img { height: 100%; width: 100%; object-fit: cover; }

    /* Right Column */
    .pos-right-sticky { height: 100%; display: flex; flex-direction: column; background: #ffffff; border-radius: 14px; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03); border: 1px solid var(--pos-border); }
    .cart-items-list { flex: 1; overflow-y: auto; }
    .btn-qty { width: 24px; height: 24px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 12px; }

    /* Sticker Print Style Mockup */
    .sticker-list-container { max-height: 380px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
    .sticker-mockup { width: 220px; border: 2px dashed #0f172a; padding: 12px; background: #fffdf0; font-family: 'Courier New', Courier, monospace; border-radius: 8px; margin: 0 auto; color: #000; }
    .option-pill-group { display: flex; flex-wrap: wrap; gap: 6px; }
    .option-pill { border: 1px solid var(--pos-border); border-radius: 20px; padding: 5px 12px; font-size: 12px; font-weight: 600; cursor: pointer; background: #f8fafc; color: var(--pos-text-muted); }
    .option-pill.selected { background: var(--pos-dark); color: #ffffff; border-color: var(--pos-dark); }

    /* Bill Receipt Thermal Mockup */
    .receipt-mockup { width: 280px; margin: 0 auto; padding: 16px; background: #fff; border: 1px solid #cbd5e1; font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

    /* Custom Shift Dashboard Cards */
    .shift-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; text-align: center; }
    .shift-card-title { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; margin-bottom: 4px; }
    .shift-card-value { font-size: 15px; font-weight: 800; color: #0f172a; }

    /* Redesigned Dynamic Alert Modal Theme (Amber Gold & Modern UI) */
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
    
    /* Badges Custom */
    .bg-amber-subtle { background-color: #fef3c7; }
    .text-amber { color: #d97706; }

    /* Z-Index Fix */
    #customAlertModal { z-index: 10650 !important; }
</style>

<div class="container-fluid pos-container py-3">
    <div class="row h-100 g-3">
        
        <!-- CỘT TRÁI -->
        <div class="col-lg-8 col-md-7 pos-left-scroll">
            
            <!-- TOP BAR CA LÀM VIỆC & SEGMENTED CONTROL MENU -->
            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1" id="shift-status-badge">
                        <i class="bi bi-clock-history"></i> <span id="current-shift-name-label">Chưa Mở Ca</span>
                    </span>
                    <small class="text-muted fw-semibold" id="cashier-name-label">
                        <i class="bi bi-person-badge"></i> Thu ngân: <strong class="text-dark" id="cashier-id-display">--</strong>
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

            <!-- 1. TAB HÌNH THỨC BÁN -->
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

            <!-- 2. SƠ ĐỒ BÀN & CÔNG CỤ TÁCH/GỘP BÀN -->
            <div class="pos-card p-3 mb-3" id="table-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;">SƠ ĐỒ BÀN & KHU VỰC</h6>
                        <button class="btn btn-sm btn-outline-dark px-3 py-1 fw-semibold rounded-pill" style="font-size: 11px;" onclick="openMergeTableModal()">
                            <i class="bi bi-arrow-merge"></i> Gộp Bàn / Bill
                        </button>
                        <button class="btn btn-sm btn-outline-dark px-3 py-1 fw-semibold rounded-pill" style="font-size: 11px;" onclick="openSplitTableModal()">
                            <i class="bi bi-arrow-right-left"></i> Chuyển Bàn / Bill
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
                                    KHU VỰC: <?= htmlspecialchars($area['name']) ?>
                                </div>
                                <div class="row g-2">
                                    <?php foreach ($area['tables'] as $table): ?>
                                        <?php 
                                            $statusClass = 'status-available';
                                            $statusText  = 'Trống';
                                            if ($table['status'] === 'occupied') {
                                                $statusClass = 'status-occupied';
                                                $statusText  = 'Có khách';
                                            } else if ($table['status'] === 'reserved') {
                                                $statusClass = 'status-reserved';
                                                $statusText  = 'Đã đặt';
                                            }
                                        ?>
                                        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                                            <div class="table-badge <?= $statusClass ?>" 
                                                 id="table-btn-<?= $table['id'] ?>"
                                                 data-area-id="<?= $area['id'] ?>"
                                                 onclick="selectTable(<?= $table['id'] ?>, '<?= htmlspecialchars($table['name']) ?>')">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="fw-bold text-dark text-truncate" style="font-size: 13px;"><?= htmlspecialchars($table['name']) ?></div>
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

            <!-- 3. DANH MỤC & TÌM KIẾM -->
            <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                <div class="d-flex gap-2 overflow-auto py-1">
                    <button class="btn cat-btn active-cat" onclick="filterCategory('all', this)">Tất cả</button>
                    <button class="btn cat-btn text-nowrap" onclick="filterCategory('bestseller', this)">Best Seller</button>
                    <button class="btn cat-btn text-nowrap" onclick="filterCategory('combo', this)">Combo Ưu Đãi</button>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <button class="btn cat-btn text-nowrap" onclick="filterCategory(<?= $cat['id'] ?>, this)">
                                <?= htmlspecialchars($cat['name']) ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <input type="text" id="search-product" class="form-control rounded-pill px-3 shadow-sm border-0 w-25" placeholder="Tìm món ăn..." onkeyup="searchProduct()">
            </div>

            <!-- 4. DANH SÁCH MÓN ĂN -->
            <div class="row g-3" id="product-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-6 col-sm-4 col-md-3 product-item" 
                             data-category="<?= $product['category_id'] ?>" 
                             data-name="<?= htmlspecialchars($product['name']) ?>"
                             data-price="<?= $product['price'] ?>">
                            <div class="card h-100 product-card shadow-sm" onclick="handleProductClick(<?= htmlspecialchars(json_encode($product)) ?>)">
                                <?php 
                                    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                                    $imgSrc = !empty($product['image_url']) ? $baseUrl . '/uploads/' . $product['image_url'] : 'https://placehold.co/150x110?text=No+Image';
                                ?>
                                <div class="product-img-wrapper">
                                    <img src="<?= $imgSrc ?>" class="product-img" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='https://placehold.co/150x110?text=No+Image'">
                                    <span class="online-price-badge d-none" id="online-badge-<?= $product['id'] ?>">Giá App</span>
                                </div>
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <h6 class="card-title fs-7 fw-bold text-dark text-truncate mb-1" style="font-size: 13px;"><?= htmlspecialchars($product['name']) ?></h6>
                                    <div class="fw-bold fs-6" style="color: var(--pos-primary);" id="product-price-<?= $product['id'] ?>" data-base-price="<?= $product['price'] ?>">
                                        <?= number_format($product['price']) ?> đ
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center text-muted py-5">Không có món ăn nào trong hệ thống.</div>
                <?php endif; ?>
            </div>

        </div>

        <!-- CỘT PHẢI: HÓA ĐƠN -->
        <div class="col-lg-4 col-md-5 h-100">
            <div class="pos-right-sticky p-3">
                
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <div>
                        <span class="badge bg-dark text-white mb-1 fw-bold px-2 py-1" id="order-type-badge">TẠI CHỖ</span>
                        <h5 class="fw-bold m-0 text-dark" id="selected-table-name" style="font-size: 16px;">Chưa Chọn Bàn</h5>
                        <small class="text-muted" style="font-size: 11px;">Thu ngân: <span id="right-panel-cashier">--</span></small>
                        <div id="table-reservation-note" class="text-warning fw-semibold mt-1 d-none" style="font-size: 11px;"></div>
                    </div>
                    <div class="d-flex gap-1 align-items-center">
                        <button class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 py-1 shadow-sm d-none" id="btn-release-table" onclick="releaseCurrentTable()">Trả bàn</button>
                        <button class="btn btn-sm btn-outline-danger border-0 rounded-pill px-2" onclick="clearCart()">Xóa tất cả</button>
                    </div>
                </div>

                <div class="cart-items-list mb-2" id="cart-items">
                    <div class="text-center text-muted my-5 py-4">
                        <span class="d-block text-secondary fw-semibold" style="font-size: 13px;">Chọn bàn / hình thức & chọn món</span>
                    </div>
                </div>

                <div class="border-top pt-2">
                    <div class="d-flex justify-content-between text-secondary mb-1" style="font-size: 12px;">
                        <span>Tạm tính:</span>
                        <span id="sub-total" class="fw-semibold">0 đ</span>
                    </div>
                    <div class="d-flex justify-content-between text-secondary mb-2" style="font-size: 12px;">
                        <span>VAT (10%):</span>
                        <span id="vat-total" class="fw-semibold">0 đ</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-5 text-dark border-top pt-2 mb-3">
                        <span>TỔNG CỘNG:</span>
                        <span class="text-danger fw-extrabold" id="grand-total">0 đ</span>
                    </div>

                    <div class="mb-2">
                        <button class="btn btn-sm btn-outline-success fw-bold w-100 rounded-3 d-inline-flex align-items-center justify-content-center gap-1" id="btn-ready-notify" onclick="notifyKitchenReady()" disabled>
                            <i class="bi bi-bell"></i> BÁO XONG MÓN & RUNG THẺ KHÁCH
                        </button>
                    </div>
                    
                    <div class="d-flex gap-2 mb-2">
                        <button class="btn btn-outline-dark fw-bold py-2 px-2 rounded-3 w-50 d-inline-flex align-items-center justify-content-center gap-1" id="btn-kitchen" onclick="sendToKitchen()" disabled style="font-size: 12px;">
                            <i class="bi bi-printer"></i> BÁO BẾP / TEM
                        </button>
                        <button class="btn btn-success fw-bold py-2 px-2 rounded-3 w-50 shadow d-inline-flex align-items-center justify-content-center gap-1" id="btn-pay-direct" onclick="openCheckoutModal(true)" disabled style="font-size: 12px;">
                            <i class="bi bi-lightning-charge-fill"></i> THANH TOÁN NGAY
                        </button>
                    </div>

                    <div>
                        <button class="btn btn-dark fw-bold py-2 px-3 rounded-3 w-100 shadow d-inline-flex align-items-center justify-content-center gap-1" id="btn-checkout" onclick="openCheckoutModal(false)" disabled>
                            <i class="bi bi-credit-card"></i> THANH TOÁN (TRẢ BÀN)
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
                <label class="fw-bold text-secondary d-block mb-1" style="font-size: 12px;">CHỌN SIZE MÓN</label>
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
            <input type="text" id="item-note" class="form-control rounded-3" placeholder="Ghi chú thêm...">
        </div>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-dark w-100 fw-bold py-2 rounded-3" onclick="confirmAddToCart()">XÁC NHẬN THÊM MÓN</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL 2: IN CHUỖI TEM DÁN LY DỌC -->
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
            <i class="bi bi-arrow-merge"></i> GỘP BÀN / CHUYỂN BILL BÀN HIỆN TẠI
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHỌN BÀN NGUỒN (CHUYỂN BILL ĐI)</label>
            <select id="merge-source-table" class="form-select rounded-3" onchange="checkMergeSameArea()"></select>
        </div>
        <div class="mb-3">
            <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHỌN BÀN ĐÍCH (NHẬN TẤT CẢ BILL)</label>
            <select id="merge-target-table" class="form-select rounded-3" onchange="checkMergeSameArea()"></select>
        </div>
        <div class="form-check d-none" id="same-area-keep-source-box">
            <input class="form-check-input" type="checkbox" id="chk-keep-source-state">
            <label class="form-check-label fw-semibold text-dark" style="font-size: 12px;" for="chk-keep-source-state">
                Giữ trạng thái có khách cho bàn nguồn (Cùng khu vực)
            </label>
        </div>
      </div>
      <div class="modal-footer border-top-0">
        <button class="btn btn-dark w-100 fw-bold py-2 rounded-3" onclick="confirmMergeTable()">XÁC NHẬN GỘP BÀN & BILL</button>
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
            <i class="bi bi-arrow-right-left"></i> CHUYỂN BÀN / CHUYỂN MÓN SANG BILL MỚI
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
                <small class="text-muted d-block text-center py-3">Chọn bàn để xem danh sách món</small>
            </div>
        </div>
        <div class="mb-2">
            <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">BÀN ĐÍCH NHẬN MÓN CHUYỂN (CHỈ BÀN TRỐNG HỢP LỆ)</label>
            <select id="split-target-table" class="form-select rounded-3"></select>
        </div>
      </div>
      <div class="modal-footer border-top-0">
        <button class="btn btn-dark w-100 fw-bold py-2 rounded-3" onclick="confirmSplitTable()">XÁC NHẬN CHUYỂN MÓN</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL TOÀN NĂNG 2 TAB: QUẢN LÝ ĐẶT BÀN -->
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
                        <tr><td colspan="6" class="text-center text-muted py-4">Chưa có bàn nào được đặt trước.</td></tr>
                    </tbody>
                </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-res-form">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHỌN BÀN TRỐNG ĐỂ ĐẶT</label>
                    <select id="reserve-target-table" class="form-select rounded-3"></select>
                </div>
                <div class="col-md-6">
                    <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">TÊN KHÁCH HÀNG</label>
                    <input type="text" id="reserve-customer-name" class="form-control rounded-3" placeholder="Nhập tên khách...">
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
                    <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">GHI CHÚ ĐẶT BÀN (DECOR, SINH NHẬT...)</label>
                    <input type="text" id="reserve-note" class="form-control rounded-3" placeholder="Nhập ghi chú đặt bàn nếu có...">
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
                <option value="Khách không đến đúng giờ (Quá 30p)">Khách không đến đúng giờ (Quá 30p)</option>
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

<!-- MODAL 6: LỊCH SỬ ĐƠN HÀNG -->
<div class="modal fade" id="orderHistoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-receipt-cutoff fs-5 text-primary"></i>
            <h6 class="fw-bold m-0 text-dark">LỊCH SỬ ĐƠN HÀNG TRONG CA</h6>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3 py-3">Mã Đơn / Bàn</th>
                        <th class="py-3">Hình Thức</th>
                        <th class="py-3">Thời Gian</th>
                        <th class="py-3">Thành Tiền</th>
                        <th class="text-center pe-3 py-3">Thao Tác</th>
                    </tr>
                </thead>
                <tbody id="order-history-list">
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 text-secondary d-block mb-2"></i>
                            Chưa có đơn hàng nào được thanh toán trong ca làm việc này.
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
            <i class="bi bi-printer"></i> ĐỒNG Ý IN BILL NGAY
        </button>
        <button class="btn btn-outline-secondary w-100 fw-bold rounded-3" data-bs-dismiss="modal">ĐÓNG</button>
    </div>
  </div>
</div>

<!-- MODAL 7: THANH TOÁN & XEM TRƯỚC HÓA ĐƠN -->
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
                    <small class="text-muted d-block">TỔNG TIỀN CẦN THANH TOÁN</small>
                    <h3 class="fw-extrabold text-danger m-0" id="modal-pay-amount">0 đ</h3>
                </div>

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
                        <span>TIỀN THỪA:</span>
                        <span class="text-success" id="cash-change">0 đ</span>
                    </div>
                  </div>
                  <div class="tab-pane fade text-center" id="pills-qr">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=QUAN_CA_PHE_BAT_ON_CHECKOUT" class="img-fluid rounded-3 mb-2 border p-2">
                    <small class="d-block text-muted">Quét mã QR VietQR để thanh toán</small>
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
            <i class="bi bi-printer"></i> HOÀN TẤT THANH TOÁN & IN BILL
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL 8: QUẢN LÝ CA LÀM VIỆC & MỞ/KẾT CA -->
<div class="modal fade" id="shiftModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2" id="shiftModalTitle">
            <i class="bi bi-clock-history text-primary"></i> QUẢN LÝ CA LÀM VIỆC 24/7
        </h6>
        <button type="button" class="btn-close" id="shift-modal-close-btn" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <!-- FORM MỞ CA (KHI CHƯA MỞ CA) -->
        <div id="shift-open-form">
            <div class="alert alert-light border text-muted py-2 mb-3" style="font-size: 12px;">
                <i class="bi bi-info-circle text-primary me-1"></i> Quán hoạt động 24h. Vui lòng chọn Ca phù hợp với khung giờ hiện tại và nhập mật khẩu xác thực.
            </div>
            <div class="mb-3">
                <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHỌN CA LÀM VIỆC</label>
                <select id="shift-type-select" class="form-select rounded-3 fw-bold">
                    <option value="morning">Ca Sáng (06:00 - 14:00)</option>
                    <option value="afternoon">Ca Chiều (14:00 - 22:00)</option>
                    <option value="night">Ca Đêm (22:00 - 06:00)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">MÃ NHÂN VIÊN THU NGÂN</label>
                <input type="text" id="shift-cashier-id" class="form-control rounded-3 fw-semibold" placeholder="Nhập mã NV (ví dụ: NV01)" value="NV01">
            </div>
            <div class="mb-3">
                <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">MẬT KHẨU CA (Sáng: 123 | Chiều: 456 | Đêm: 789)</label>
                <input type="password" id="shift-cashier-pass" class="form-control rounded-3" placeholder="Nhập mật khẩu ca">
            </div>
            <button class="btn btn-dark w-100 fw-bold py-2 rounded-3 d-inline-flex align-items-center justify-content-center gap-2" onclick="confirmOpenShift()">
                <i class="bi bi-box-arrow-in-right"></i> MỞ CA & BẮT ĐẦU BÁN HÀNG
            </button>
        </div>

        <!-- TÁCH 2 TAB KHI ĐÃ MỞ CA -->
        <div id="shift-close-summary-tabs" class="d-none">
            <ul class="nav nav-tabs nav-fill mb-3">
              <li class="nav-item">
                <button class="nav-link active fw-bold" id="shift-tab-stats-btn" data-bs-toggle="tab" data-bs-target="#tab-shift-stats">
                    <i class="bi bi-graph-up me-1"></i> SỐ LIỆU TRONG CA
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link fw-bold" id="shift-tab-print-btn" data-bs-toggle="tab" data-bs-target="#tab-shift-print">
                    <i class="bi bi-printer me-1"></i> IN BÁO CÁO & KẾT CA
                </button>
              </li>
            </ul>

            <div class="tab-content">
              <div class="tab-pane fade show active" id="tab-shift-stats">
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <div class="shift-card">
                            <div class="shift-card-title">TỔNG DOANH THU CA</div>
                            <div class="shift-card-value text-danger" id="stat-total-revenue">0 đ</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="shift-card">
                            <div class="shift-card-title">ĐƠN THƯỜNG (TẠI CHỖ/MV)</div>
                            <div class="shift-card-value text-primary" id="stat-offline-revenue">0 đ</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="shift-card">
                            <div class="shift-card-title">ĐƠN APP (GRAB/SHOPEE)</div>
                            <div class="shift-card-value text-success" id="stat-online-revenue">0 đ</div>
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
                            <div class="shift-card-title">TỔNG SỐ LY BÁN RA</div>
                            <div class="shift-card-value text-dark" id="stat-total-cups">0 Ly</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="shift-card">
                            <div class="shift-card-title">TỔNG SỐ BÁNH BÁN RA</div>
                            <div class="shift-card-value text-dark" id="stat-total-cakes">0 Bánh</div>
                        </div>
                    </div>
                </div>
              </div>

              <div class="tab-pane fade text-center" id="tab-shift-print">
                <div class="receipt-mockup text-start mb-3" id="shift-report-thermal-preview"></div>
                <button class="btn btn-danger w-100 fw-bold py-2 rounded-3 d-inline-flex align-items-center justify-content-center gap-2" onclick="confirmCloseShift()">
                    <i class="bi bi-lock-fill"></i> XÁC NHẬN KẾT CA & ĐĂNG XUẤT THU NGÂN
                </button>
              </div>
            </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- MODAL REDESIGNED ALERT -->
<div class="modal fade" id="customAlertModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-3">
      <div class="modal-body p-0">
        <div class="alert-icon-circle" id="alertIconCircle">
            <i class="bi bi-clock-history" id="alertIcon"></i>
        </div>
        <h5 class="fw-bold text-dark mb-2" id="alertTitle">Thông Báo</h5>
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
let onlineCounter = 101;

let activeOrders = {}; 
let tableReservationData = {}; 
let tableMergeSourcesMap = {}; 
let tableMergedTargetMap = {}; 
let completedOrdersHistory = [];
let cart = [];
let selectedProductForModal = null;
let isShiftOpened = false;
let isPayDirectMode = false;

let currentShiftInfo = { cashierId: '', shiftType: '', shiftName: '' };
let selectedOrderForReprint = null;

const DB_TOPPING_CAT_ID = <?= json_encode($toppingCatId) ?>;

let shiftStats = { 
    totalRevenue: 0, 
    offlineRevenue: 0,
    onlineRevenue: 0,
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

    let savedShift = localStorage.getItem('pos_current_shift');
    if (savedShift) {
        try {
            let parsedInfo = JSON.parse(savedShift);
            isShiftOpened = true;
            currentShiftInfo = parsedInfo;

            document.getElementById('shift-status-badge').className = "badge bg-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
            document.getElementById('current-shift-name-label').innerText = currentShiftInfo.shiftName;
            document.getElementById('cashier-id-display').innerText = currentShiftInfo.cashierId;
            document.getElementById('right-panel-cashier').innerText = currentShiftInfo.cashierId;
        } catch(e) {
            openShiftModal();
        }
    } else {
        openShiftModal();
    }
});

function showCustomAlert(msg, title = "Thông Báo", type = "danger") {
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
        showCustomAlert("Vui lòng mở ca làm việc trước khi thực hiện bán hàng!", "Chưa Mở Ca", "danger");
        openShiftModal();
        return;
    }

    currentOrderType = type;
    document.querySelectorAll('.order-type-btn').forEach(btn => btn.classList.remove('active'));
    
    const tableSection = document.getElementById('table-section');
    const onlineSubtabs = document.getElementById('online-subtabs-container');
    const badge = document.getElementById('order-type-badge');
    const tableNameLabel = document.getElementById('selected-table-name');
    const noteLabel = document.getElementById('table-reservation-note');

    noteLabel.classList.add('d-none');

    if (type === 'dine_in') {
        document.getElementById('tab-dine-in').classList.add('active');
        tableSection.classList.remove('d-none');
        onlineSubtabs.classList.add('d-none');
        badge.innerText = "TẠI CHỖ";
        badge.className = "badge bg-dark text-white mb-1 fw-bold px-2 py-1";
        
        if (currentTableId && typeof currentTableId === 'number') {
            let tableBtn = document.getElementById('table-btn-' + currentTableId);
            tableNameLabel.innerText = tableBtn ? tableBtn.querySelector('.fw-bold').innerText : "Chưa Chọn Bàn";
        } else {
            currentTableId = null;
            tableNameLabel.innerText = "Chưa Chọn Bàn";
        }

    } else if (type === 'takeaway') {
        document.getElementById('tab-takeaway').classList.add('active');
        tableSection.classList.add('d-none');
        onlineSubtabs.classList.add('d-none');
        badge.innerText = "MANG VỀ";
        badge.className = "badge bg-warning text-dark mb-1 fw-bold px-2 py-1";
        
        currentTableId = "MV-" + String(takeawayCounter).padStart(2, '0');
        tableNameLabel.innerText = "Thẻ Mang Về: " + currentTableId;

    } else if (type === 'online') {
        document.getElementById('tab-online').classList.add('active');
        tableSection.classList.add('d-none');
        onlineSubtabs.classList.remove('d-none');
        switchOnlinePlatform(onlinePlatform);
    }

    loadCartForCurrentTable();
    updateProductPricesDisplay();
    updateCheckoutButton();
}

function switchOnlinePlatform(platform) {
    onlinePlatform = platform;
    const badge = document.getElementById('order-type-badge');
    const tableNameLabel = document.getElementById('selected-table-name');
    const btnGrab = document.getElementById('subtab-grab');
    const btnShopee = document.getElementById('subtab-shopee');

    if (platform === 'grab') {
        btnGrab.className = "subtab-btn active-grab";
        btnShopee.className = "subtab-btn";
        badge.innerText = "GRABFOOD";
        badge.className = "badge bg-success text-white mb-1 fw-bold px-2 py-1";
        currentTableId = "GRAB-" + onlineCounter;
        tableNameLabel.innerText = "Mã Đơn: " + currentTableId;
    } else {
        btnGrab.className = "subtab-btn";
        btnShopee.className = "subtab-btn active-shopee";
        badge.innerText = "SHOPEEFOOD";
        badge.className = "badge bg-danger text-white mb-1 fw-bold px-2 py-1";
        currentTableId = "SHOPEE-" + onlineCounter;
        tableNameLabel.innerText = "Mã Đơn: " + currentTableId;
    }

    loadCartForCurrentTable();
    updateProductPricesDisplay();
    updateCheckoutButton();
}

// -------------------------------------------------------------
// TÍNH NĂNG 3 & 4 & 5: CẬP NHẬT SELECTTABLE VÀ NÚT "TRẢ BÀN" LINH HOẠT
// -------------------------------------------------------------
function selectTable(tableId, tableName) {
    if (!isShiftOpened) {
        showCustomAlert("Vui lòng mở ca làm việc trước khi thực hiện bán hàng!", "Chưa Mở Ca", "danger");
        openShiftModal();
        return;
    }

    // 1. KIỂM TRA BÀN NGUỒN ĐÃ GỘP BILL (CÙNG KHU VỰC)
    if (tableMergedTargetMap[tableId]) {
        let targetId = tableMergedTargetMap[tableId];
        let targetBtn = document.getElementById('table-btn-' + targetId);
        let targetName = targetBtn ? targetBtn.querySelector('.fw-bold').innerText : ('Bàn ' + targetId);

        document.getElementById('alertTitle').innerText = "Bàn Đã Gộp Bill";
        document.getElementById('alertMessage').innerHTML = `Bàn <strong class="text-dark">${tableName}</strong> đã được gộp toàn bộ bill sang <strong class="text-primary">${targetName}</strong>.<br><span class="text-muted d-block mt-1">Bạn muốn <strong>TRẢ BÀN</strong> về trạng thái Trống hay <strong>SANG ${targetName.toUpperCase()}</strong> để nạp thêm món?</span>`;
        
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
    noteLabel.classList.add('d-none');

    // 2. BÀN ĐÃ ĐẶT TRƯỚC
    if (selectedBtn && selectedBtn.classList.contains('status-reserved')) {
        let resData = tableReservationData[tableId] || {};
        let rTime = resData.time || 'giờ hẹn';
        let cName = resData.name || 'Khách';

        document.getElementById('alertTitle').innerText = "Bàn Đã Đặt Trước";
        document.getElementById('alertMessage').innerHTML = `Bàn này đã được đặt bởi <strong class="text-dark">${cName}</strong> lúc <strong class="text-dark">${rTime}</strong>.<br><span class="text-muted d-block mt-1">Bạn có muốn mở bàn <strong class="text-warning">XOAY TUA</strong> phục vụ khách uống nhanh không?</span>`;
        
        let circle = document.getElementById('alertIconCircle');
        circle.className = "alert-icon-circle";
        document.getElementById('alertIcon').className = "bi bi-clock-history";

        document.getElementById('alert-action-buttons').innerHTML = `
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary w-50 fw-bold rounded-3 py-2" data-bs-dismiss="modal">ĐÓNG</button>
                <button type="button" class="btn btn-warning w-50 fw-bold rounded-3 py-2 text-dark shadow-sm" onclick="customAlertModal.hide(); confirmTurnaroundTable(${tableId}, '${tableName}', '${rTime}');">MỞ BÀN XOAY TUA</button>
            </div>
        `;

        customAlertModal.show();
        return;
    }

    // 3. BÀN ĐÃ THANH TOÁN -> NÚT HỦY ĐỔI THÀNH "TRẢ BÀN"
    if (selectedBtn && selectedBtn.classList.contains('status-paid')) {
        document.getElementById('alertTitle').innerText = "Bàn Đã Thanh Toán";
        document.getElementById('alertMessage').innerHTML = `Bàn <strong class="text-dark">${tableName}</strong> hiện đã thanh toán hoàn tất.<br><span class="text-muted d-block mt-1">Bạn muốn <strong>TRẢ BÀN</strong> về trạng thái Trống hay <strong>GỌI THÊM MÓN</strong> mới?</span>`;
        
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

// Hàm bổ trợ: Trả bàn nguồn đã gộp về trạng thái Trống
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
    showCustomAlert(`Bàn ${sourceId} đã được đưa về trạng thái TRỐNG thành công!`, "Trả Bàn Thành Công", "success");
}

// Hàm bổ trợ: Trả bàn đã thanh toán về trạng thái Trống
function releasePaidTable(tableId) {
    let tableBadge = document.getElementById('table-btn-' + tableId);
    if (tableBadge) {
        if (tableReservationData[tableId] && tableReservationData[tableId].isTurnaround) {
            tableReservationData[tableId].isTurnaround = false;
            tableBadge.className = "table-badge status-reserved";
            tableBadge.querySelector('.status-label').innerText = `Đã đặt (${tableReservationData[tableId].time})`;
        } else {
            tableBadge.className = "table-badge status-available";
            tableBadge.querySelector('.status-label').innerText = "Trống";
        }
    }
    document.getElementById('btn-release-table').classList.add('d-none');
    delete activeOrders[tableId];
    cart = [];
    renderCart();
    showCustomAlert(`Đã giải phóng bàn về trạng thái Trống thành công!`, "Trả Bàn Thành Công", "success");
}

function proceedSelectPaidTable(tableId, tableName) {
    let selectedBtn = document.getElementById('table-btn-' + tableId);
    let noteLabel = document.getElementById('table-reservation-note');

    currentTableId = tableId;
    document.getElementById('selected-table-name').innerText = tableName;
    
    document.querySelectorAll('.table-badge').forEach(badge => badge.classList.remove('active'));
    if (selectedBtn) {
        selectedBtn.classList.add('active');
        if (tableReservationData[tableId] && tableReservationData[tableId].isTurnaround) {
            noteLabel.innerText = `[Bàn Xoay Tua - Hẹn khách: ${tableReservationData[tableId].time}]`;
            noteLabel.classList.remove('d-none');
        } else if (tableReservationData[tableId] && tableReservationData[tableId].note) {
            noteLabel.innerText = `[Note: ${tableReservationData[tableId].note}]`;
            noteLabel.classList.remove('d-none');
        }
    }

    let releaseBtn = document.getElementById('btn-release-table');
    let isPaidStatus = selectedBtn && selectedBtn.classList.contains('status-paid');
    let isSourceEmptyBill = selectedBtn && selectedBtn.classList.contains('status-occupied') && (!activeOrders[tableId] || activeOrders[tableId].length === 0);

    if (isPaidStatus || isSourceEmptyBill) {
        releaseBtn.classList.remove('d-none');
    } else {
        releaseBtn.classList.add('d-none');
    }

    loadCartForCurrentTable();
    updateCheckoutButton();
}

function confirmTurnaroundTable(tableId, tableName, rTime) {
    if (!tableReservationData[tableId]) tableReservationData[tableId] = {};
    tableReservationData[tableId].isTurnaround = true;

    let selectedBtn = document.getElementById('table-btn-' + tableId);
    if (selectedBtn) {
        selectedBtn.className = "table-badge status-occupied";
        selectedBtn.querySelector('.status-label').innerText = `[Xoay Tua - Hẹn ${rTime}]`;
    }

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
        showCustomAlert("Vui lòng mở ca làm việc trước khi gọi món!", "Chưa Mở Ca", "danger");
        openShiftModal();
        return;
    }

    if (currentOrderType === 'dine_in' && !currentTableId) {
        showCustomAlert("Vui lòng chọn bàn trống trước khi chọn món!", "Chưa Chọn Bàn", "danger");
        return;
    }

    let pCat = String(product.category_id);
    let pNameClean = removeVietnameseTones(product.name);
    let isToppingByCat = (DB_TOPPING_CAT_ID && pCat === String(DB_TOPPING_CAT_ID));
    let isCakeOrFlan = pNameClean.includes('banh') || pNameClean.includes('flan');

    if (isToppingByCat || isCakeOrFlan) {
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
            isCake: true,
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

        if (DB_TOPPING_CAT_ID && itemCat === String(DB_TOPPING_CAT_ID)) {
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
}

function renderCart() {
    const container = document.getElementById('cart-items');
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted my-5 py-4">
                <span class="d-block text-secondary fw-semibold" style="font-size: 13px;">Chọn bàn / hình thức & chọn món</span>
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
                            `<span class="text-muted opacity-50 ms-1" title="Món đã báo bếp, không thể xóa"><i class="bi bi-lock-fill"></i></span>` : 
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
        showCustomAlert("Món này đã gửi lệnh báo bếp / in tem dán ly, không thể giảm số lượng!", "Chống Gian Lận Đơn", "danger");
        return;
    }

    item.qty += delta;
    if (item.qty <= 0) cart.splice(index, 1);
    if (currentTableId) activeOrders[currentTableId] = cart;
    renderCart();
}

function removeItem(index) {
    let item = cart[index];
    if (item.printedQty && item.printedQty > 0) {
        showCustomAlert("Món này đã gửi lệnh in tem dán ly chế biến, không thể xóa khỏi hóa đơn!", "Chống Gian Lận Đơn", "danger");
        return;
    }
    cart.splice(index, 1);
    if (currentTableId) activeOrders[currentTableId] = cart;
    renderCart();
}

// -------------------------------------------------------------
// TÍNH NĂNG 1: ĐỔI THÔNG BÁO CHẶN XÓA MÓN SANG CÂU TỪ CHUYÊN NGHIỆP
// -------------------------------------------------------------
function clearCart() {
    let hasPrintedItems = cart.some(item => item.printedQty && item.printedQty > 0);
    if (hasPrintedItems) {
        showCustomAlert("Đơn hàng đã chứa món gửi báo bếp / in tem. Không thể thực hiện xóa tất cả!", "Không Thể Xóa Đơn", "danger");
        return;
    }
    cart = [];
    if (currentTableId) delete activeOrders[currentTableId];
    renderCart();
}

function updateTotals(subTotal) {
    let vat = subTotal * 0.10;
    let grandTotal = subTotal + vat;

    document.getElementById('sub-total').innerText = subTotal.toLocaleString() + " đ";
    document.getElementById('vat-total').innerText = vat.toLocaleString() + " đ";
    document.getElementById('grand-total').innerText = Math.round(grandTotal).toLocaleString() + " đ";

    updateCheckoutButton();
}

function updateCheckoutButton() {
    const btnCheckout = document.getElementById('btn-checkout');
    const btnPayDirect = document.getElementById('btn-pay-direct');
    const btnKitchen = document.getElementById('btn-kitchen');
    const btnReady = document.getElementById('btn-ready-notify');
    
    let isValid = (currentTableId !== null) && cart.length > 0;
    btnCheckout.disabled = !isValid;
    btnPayDirect.disabled = !isValid;
    btnKitchen.disabled = !isValid;
    btnReady.disabled = !isValid;
}

function sendToKitchen() {
    if (cart.length === 0 || !currentTableId) return;

    activeOrders[currentTableId] = cart;

    if (typeof currentTableId === 'number') {
        let tableBadge = document.getElementById('table-btn-' + currentTableId);
        if (tableBadge && !tableBadge.classList.contains('status-reserved')) {
            tableBadge.className = "table-badge status-occupied";
            tableBadge.querySelector('.status-label').innerText = "Có khách";
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
        showCustomAlert("Tất cả món trong đơn này đã được gửi lệnh in tem dán ly trước đó!", "Tất Cả Món Đã In", "info");
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
                        <span>MÃ: ${currentTableId}</span>
                        <span>${new Date().toLocaleTimeString()}</span>
                    </div>
                    <div class="fw-bold fs-6 text-truncate">${item.name}</div>
                    ${item.isCake ? '' : `<div style="font-size: 11px;">Size: ${item.size || 'M'} | ${item.sugar}</div><div style="font-size: 11px;">Đá: ${item.ice}</div>`}
                    ${toppingStr ? `<div style="font-size: 11px; font-weight: bold;">+ ${toppingStr}</div>` : ''}
                    ${item.note ? `<div style="font-size: 11px; font-style: italic;">Note: ${item.note}</div>` : ''}
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
    showCustomAlert("Đã gửi lệnh in tem dán ly chế biến thành công!", "Báo Bếp Thành Công", "success");
}

function notifyKitchenReady() {
    if (!currentTableId || cart.length === 0) return;
    let pagerNumber = Math.floor(Math.random() * 20) + 1;
    showCustomAlert(`Đã phát tín hiệu RUNG THẺ PAGER SỐ [${pagerNumber}] cho đơn ${currentTableId}!`, "Báo Xong Món", "info");
}

function openCheckoutModal(isDirect = false) {
    if (cart.length === 0 || !currentTableId) return;

    isPayDirectMode = isDirect;

    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * 0.10;
    let grandTotal = Math.round(subTotal + vat);

    document.getElementById('modal-pay-amount').innerText = grandTotal.toLocaleString() + " đ";
    document.getElementById('cash-given').value = '';
    document.getElementById('cash-change').innerText = '0 đ';
    document.getElementById('inline-payment-error').classList.add('d-none');

    let titleText = isDirect ? "THANH TOÁN NGAY (GIỮ BÀN)" : "THANH TOÁN & IN HÓA ĐƠN (TRẢ BÀN)";
    document.getElementById('checkoutModalTitle').innerHTML = `<i class="bi bi-credit-card-2-front text-success"></i> ${titleText}`;

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

    let receiptHtml = `
        <div class="text-center mb-2">
            <h6 class="fw-bold m-0">QUÁN CÀ PHÊ BẤT ỔN</h6>
            <small style="font-size: 10px;">HÓA ĐƠN THANH TOÁN</small><br>
            <small style="font-size: 9px;">Ngày: ${new Date().toLocaleString()}</small>
        </div>
        <div class="border-top border-bottom py-1 mb-2 fw-bold" style="font-size: 11px;">
            ĐƠN: ${currentTableId} | Thu ngân: ${currentShiftInfo.cashierId}
        </div>
        <div class="mb-2">${itemsHtml}</div>
        <div class="border-top pt-1" style="font-size: 11px;">
            <div class="d-flex justify-content-between"><span>Tạm tính:</span><span>${subTotal.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between"><span>VAT (10%):</span><span>${vat.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between fw-bold fs-6 mt-1"><span>TỔNG TIỀN:</span><span>${grandTotal.toLocaleString()}đ</span></div>
        </div>
    `;

    document.getElementById('receipt-preview-content').innerHTML = receiptHtml;
    checkoutModal.show();
}

function calculateChange() {
    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let grandTotal = Math.round(subTotal * 1.1);
    let given = parseFloat(document.getElementById('cash-given').value) || 0;
    let change = given - grandTotal;

    const errorDiv = document.getElementById('inline-payment-error');

    if (given > 0 && change < 0) {
        errorDiv.classList.remove('d-none');
        document.getElementById('cash-change').innerText = "Thiếu " + Math.abs(change).toLocaleString() + " đ";
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
    let grandTotal = Math.round(subTotal * 1.1);
    let given = parseFloat(document.getElementById('cash-given').value) || 0;

    let activeTab = document.querySelector('#pills-cash-tab.active');
    if (activeTab && given < grandTotal) {
        document.getElementById('inline-payment-error').classList.remove('d-none');
        document.getElementById('cash-given').focus();
        return;
    }

    shiftStats.totalRevenue += grandTotal;
    if (currentOrderType === 'online') {
        shiftStats.onlineRevenue += grandTotal;
    } else {
        shiftStats.offlineRevenue += grandTotal;
    }
    shiftStats.totalOrders += 1;

    cart.forEach(item => {
        let pNameClean = removeVietnameseTones(item.name);
        let pCat = String(item.category_id || '');
        let isTopping = (DB_TOPPING_CAT_ID && pCat === String(DB_TOPPING_CAT_ID)) || pNameClean.includes('topping') || pNameClean.includes('thach');

        if (isTopping) {
            return; 
        }

        if (item.isCake || pNameClean.includes('banh') || pNameClean.includes('flan')) {
            shiftStats.totalCakes += item.qty;
        } else {
            shiftStats.totalCups += item.qty;
        }
    });

    completedOrdersHistory.unshift({
        id: currentTableId,
        type: currentOrderType === 'dine_in' ? 'Tại chỗ' : (currentOrderType === 'takeaway' ? 'Mang về' : 'Online'),
        time: new Date().toLocaleTimeString(),
        total: grandTotal,
        items: [...cart]
    });

    finishPaymentSuccess();
}

// -------------------------------------------------------------
// TÍNH NĂNG 2: FIX LỖI THANH TOÁN XONG BỊ VĂNG THÔNG BÁO CHẶN XÓA MÓN
// -------------------------------------------------------------
function finishPaymentSuccess() {
    checkoutModal.hide();
    showCustomAlert("Thanh toán hoàn tất & Đã xuất hóa đơn!", "Thanh Toán Thành Công", "success");

    if (typeof currentTableId === 'number') {
        let tableBadge = document.getElementById('table-btn-' + currentTableId);
        
        if (isPayDirectMode) {
            if (tableBadge) {
                tableBadge.className = "table-badge status-paid";
                if (tableReservationData[currentTableId] && tableReservationData[currentTableId].isTurnaround) {
                    tableBadge.querySelector('.status-label').innerText = `[Đã TT - Hẹn ${tableReservationData[currentTableId].time}]`;
                } else {
                    tableBadge.querySelector('.status-label').innerText = "Đã thanh toán";
                }
            }
            document.getElementById('btn-release-table').classList.remove('d-none');
        } 
        else {
            if (tableReservationData[currentTableId] && tableReservationData[currentTableId].isTurnaround) {
                tableReservationData[currentTableId].isTurnaround = false;
                if (tableBadge) {
                    tableBadge.className = "table-badge status-reserved";
                    tableBadge.querySelector('.status-label').innerText = `Đã đặt (${tableReservationData[currentTableId].time})`;
                }
            } else {
                if (tableBadge) {
                    tableBadge.className = "table-badge status-available";
                    tableBadge.querySelector('.status-label').innerText = "Trống";
                }
            }
            document.getElementById('btn-release-table').classList.add('d-none');
        }

        if (tableMergeSourcesMap[currentTableId]) {
            tableMergeSourcesMap[currentTableId].forEach(srcId => {
                let srcBadge = document.getElementById('table-btn-' + srcId);
                if (srcBadge && !srcBadge.classList.contains('status-reserved')) {
                    srcBadge.className = "table-badge status-available";
                    srcBadge.querySelector('.status-label').innerText = "Trống";
                }
                delete tableMergedTargetMap[srcId];
            });
            delete tableMergeSourcesMap[currentTableId];
        }
    }

    delete activeOrders[currentTableId];
    if (currentOrderType === 'takeaway') takeawayCounter++;
    if (currentOrderType === 'online') onlineCounter++;

    // Reset giỏ trực tiếp để tránh kích hoạt cảnh báo anti-fraud của clearCart()
    cart = [];
    renderCart();

    switchOrderType(currentOrderType);
}

function openOrderHistoryModal() {
    let tbody = document.getElementById('order-history-list');
    tbody.innerHTML = '';

    if (completedOrdersHistory.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-2 text-secondary d-block mb-2"></i>
                    Chưa có đơn hàng nào được thanh toán trong ca làm việc này.
                </td>
            </tr>`;
    } else {
        completedOrdersHistory.forEach((order, index) => {
            let badgeClass = order.type === 'Tại chỗ' ? 'bg-dark text-white' : (order.type === 'Mang về' ? 'bg-warning text-dark' : 'bg-success text-white');
            tbody.innerHTML += `
                <tr>
                    <td class="ps-3 fw-bold text-dark">${order.id}</td>
                    <td><span class="badge ${badgeClass} fw-bold px-2 py-1">${order.type}</span></td>
                    <td class="text-muted"><i class="bi bi-clock me-1"></i>${order.time}</td>
                    <td class="fw-bold text-danger">${order.total.toLocaleString()} đ</td>
                    <td class="text-center pe-3">
                        <button class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" onclick="openReprintBillModal(${index})">
                            <i class="bi bi-printer"></i> In Lại Bill
                        </button>
                    </td>
                </tr>`;
        });
    }
    orderHistoryModal.show();
}

function openReprintBillModal(index) {
    selectedOrderForReprint = completedOrdersHistory[index];
    if (!selectedOrderForReprint) return;

    let itemsHtml = '';
    selectedOrderForReprint.items.forEach(item => {
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

    let receiptHtml = `
        <div class="text-center mb-2">
            <h6 class="fw-bold m-0">QUÁN CÀ PHÊ BẤT ỔN</h6>
            <small style="font-size: 10px;">HÓA ĐƠN IN LẠI (SAO SÁCH)</small><br>
            <small style="font-size: 9px;">Thời gian in: ${new Date().toLocaleString()}</small>
        </div>
        <div class="border-top border-bottom py-1 mb-2 fw-bold" style="font-size: 11px;">
            MÃ ĐƠN: ${selectedOrderForReprint.id} | LOẠI: ${selectedOrderForReprint.type}
        </div>
        <div class="mb-2 text-start">${itemsHtml}</div>
        <div class="border-top pt-1 text-start" style="font-size: 11px;">
            <div class="d-flex justify-content-between fw-bold fs-6 mt-1"><span>TỔNG TIỀN:</span><span>${selectedOrderForReprint.total.toLocaleString()}đ</span></div>
        </div>
    `;

    document.getElementById('reprint-receipt-content').innerHTML = receiptHtml;
    orderHistoryModal.hide();
    reprintBillModal.show();
}

function confirmReprintBill() {
    reprintBillModal.hide();
    showCustomAlert(`Đã lệnh in lại thành công hóa đơn đơn hàng [${selectedOrderForReprint.id}]!`, "In Lại Bill Thành Công", "success");
}

function openReservationManagerModal() {
    let targetSelect = document.getElementById('reserve-target-table');
    targetSelect.innerHTML = '';

    document.querySelectorAll('.table-badge').forEach(badge => {
        let tid = badge.id.replace('table-btn-', '');
        let tname = badge.querySelector('.fw-bold').innerText;
        let isOccupied = badge.classList.contains('status-occupied');
        let isReserved = badge.classList.contains('status-reserved');
        let isPaid = badge.classList.contains('status-paid');
        
        if (!isOccupied && !isReserved && !isPaid) {
            targetSelect.innerHTML += `<option value="${tid}">${tname}</option>`;
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

    renderReservationTableList();

    let listTabBtn = new bootstrap.Tab(document.getElementById('res-tab-list-btn'));
    listTabBtn.show();

    reservationManagerModal.show();
}

function renderReservationTableList() {
    let tbody = document.getElementById('reservation-manager-tbody');
    tbody.innerHTML = '';

    let resKeys = Object.keys(tableReservationData).filter(k => tableReservationData[k] && tableReservationData[k].name);
    document.getElementById('resCountBadgeDisplay').innerText = resKeys.length + " Lượt đặt";

    if (resKeys.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Chưa có bàn nào được đặt trước.</td></tr>`;
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
    let tid = document.getElementById('reserve-target-table').value;
    let cName = document.getElementById('reserve-customer-name').value.trim();
    let cPhone = document.getElementById('reserve-customer-phone').value.trim();
    let rDate = document.getElementById('reserve-date').value;
    let rTime = document.getElementById('reserve-time').value;
    let rNote = document.getElementById('reserve-note').value.trim();

    if (!cName || !cPhone || !rDate || !rTime) {
        showCustomAlert("Vui lòng điền đầy đủ Tên khách hàng, Số điện thoại, Ngày và Giờ hẹn!", "Thông Tin Chưa Đủ", "danger");
        return;
    }

    if (cPhone.length !== 10 || !cPhone.startsWith('0')) {
        showCustomAlert("Số điện thoại phải đúng 10 chữ số chuẩn Việt Nam (bắt đầu bằng đầu số 0)!", "SĐT Không Hợp Lệ", "danger");
        return;
    }

    let isDuplicatePhone = false;
    Object.keys(tableReservationData).forEach(tKey => {
        if (tableReservationData[tKey] && tableReservationData[tKey].phone === cPhone) {
            isDuplicatePhone = true;
        }
    });

    if (isDuplicatePhone) {
        showCustomAlert(`Số điện thoại [${cPhone}] đã tồn tại trong một lịch đặt bàn khác trên hệ thống!`, "Trùng SĐT Đặt Bàn", "danger");
        return;
    }

    const now = new Date();
    const [year, month, day] = rDate.split('-');
    const [hours, minutes] = rTime.split(':');
    const bookingTime = new Date(year, month - 1, day, hours, minutes);

    const minAllowedTime = new Date(now.getTime() + 2 * 60 * 60 * 1000); 
    const maxAllowedTime = new Date(now.getTime() + 24 * 60 * 60 * 1000); 

    if (bookingTime < minAllowedTime) {
        showCustomAlert("Thời gian đặt bàn phải trước ít nhất 2 tiếng so với thời điểm hiện tại!", "Thời Gian Không Hợp Lệ", "danger");
        return;
    }

    if (bookingTime > maxAllowedTime) {
        showCustomAlert("Thời gian đặt bàn không được vượt quá 24 tiếng tính từ thời điểm hiện tại!", "Vượt Quá 24 Giờ", "danger");
        return;
    }

    tableReservationData[tid] = {
        name: cName,
        phone: cPhone,
        date: rDate,
        time: rTime,
        note: rNote,
        isTurnaround: false
    };

    let tableBadge = document.getElementById('table-btn-' + tid);
    if (tableBadge) {
        tableBadge.className = "table-badge status-reserved";
        tableBadge.querySelector('.status-label').innerText = `Đã đặt (${rTime})`;
    }

    reservationManagerModal.hide();
    showCustomAlert(`Đã ghi nhận lịch đặt trước cho khách ${cName} (${cPhone}) lúc ${rTime} ngày ${rDate}!`, "Đặt Bàn Thành Công", "success");
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

    delete tableReservationData[tid];

    let tableBadge = document.getElementById('table-btn-' + tid);
    if (tableBadge) {
        tableBadge.className = "table-badge status-available";
        tableBadge.querySelector('.status-label').innerText = "Trống";
    }

    cancelReserveModal.hide();
    if (reservationManagerModal) reservationManagerModal.hide();
    showCustomAlert(`Đã hủy lịch đặt bàn thành công! Lý do: ${reasonStr}`, "Hủy Đặt Bàn Thành Công", "success");
}

function openShiftModal() {
    if (!isShiftOpened) {
        document.getElementById('shiftModalTitle').innerHTML = `<i class="bi bi-door-open text-primary"></i> MỞ CA LÀM VIỆC BẮT BUỘC`;
        document.getElementById('shift-open-form').classList.remove('d-none');
        document.getElementById('shift-close-summary-tabs').classList.add('d-none');
        document.getElementById('shift-modal-close-btn').classList.add('d-none');

        const currentHour = new Date().getHours();
        let defaultShift = 'morning';
        if (currentHour >= 14 && currentHour < 22) defaultShift = 'afternoon';
        if (currentHour >= 22 || currentHour < 6) defaultShift = 'night';
        document.getElementById('shift-type-select').value = defaultShift;

    } else {
        document.getElementById('shiftModalTitle').innerHTML = `<i class="bi bi-clock-history text-primary"></i> QUẢN LÝ & KẾT CA LÀM VIỆC`;
        document.getElementById('shift-open-form').classList.add('d-none');
        document.getElementById('shift-close-summary-tabs').classList.remove('d-none');
        document.getElementById('shift-modal-close-btn').classList.remove('d-none');

        document.getElementById('stat-total-revenue').innerText = shiftStats.totalRevenue.toLocaleString() + " đ";
        document.getElementById('stat-offline-revenue').innerText = shiftStats.offlineRevenue.toLocaleString() + " đ";
        document.getElementById('stat-online-revenue').innerText = shiftStats.onlineRevenue.toLocaleString() + " đ";
        document.getElementById('stat-total-orders').innerText = shiftStats.totalOrders + " Đơn";
        document.getElementById('stat-total-cups').innerText = shiftStats.totalCups + " Ly";
        document.getElementById('stat-total-cakes').innerText = shiftStats.totalCakes + " Bánh";

        let reportReceiptHtml = `
            <div class="text-center mb-2">
                <h6 class="fw-bold m-0">QUÁN CÀ PHÊ BẤT ỔN</h6>
                <small style="font-size: 10px;">BÁO CÁO KẾT CA BÀN GIAO</small><br>
                <small style="font-size: 9px;">Thời gian: ${new Date().toLocaleString()}</small>
            </div>
            <div class="border-top border-bottom py-1 mb-2 fw-bold" style="font-size: 11px;">
                CA: ${currentShiftInfo.shiftName} | NV: ${currentShiftInfo.cashierId}
            </div>
            <div class="mb-2" style="font-size: 11px;">
                <div class="d-flex justify-content-between"><span>TỔNG DOANH THU:</span><span class="fw-bold">${shiftStats.totalRevenue.toLocaleString()}đ</span></div>
                <div class="d-flex justify-content-between text-muted"><span>- Doanh thu Tại chỗ/MV:</span><span>${shiftStats.offlineRevenue.toLocaleString()}đ</span></div>
                <div class="d-flex justify-content-between text-muted"><span>- Doanh thu App Online:</span><span>${shiftStats.onlineRevenue.toLocaleString()}đ</span></div>
                <div class="d-flex justify-content-between mt-1"><span>TỔNG SỐ ĐƠN:</span><span class="fw-bold">${shiftStats.totalOrders} Đơn</span></div>
                <div class="d-flex justify-content-between"><span>TỔNG SỐ LY BÁN RA:</span><span class="fw-bold">${shiftStats.totalCups} Ly</span></div>
                <div class="d-flex justify-content-between"><span>TỔNG SỐ BÁNH BÁN RA:</span><span class="fw-bold">${shiftStats.totalCakes} Bánh</span></div>
            </div>
        `;
        document.getElementById('shift-report-thermal-preview').innerHTML = reportReceiptHtml;
    }

    shiftModal.show();
}

function confirmOpenShift() {
    let cashierId = document.getElementById('shift-cashier-id').value.trim();
    let pass = document.getElementById('shift-cashier-pass').value.trim();
    let shiftType = document.getElementById('shift-type-select').value;

    if (!cashierId || !pass) {
        showCustomAlert("Vui lòng nhập đầy đủ Mã Nhân Viên và Mật khẩu ca!", "Mở Ca Thất Bại", "danger");
        return;
    }

    const currentHour = new Date().getHours();
    let isTimeValid = false;

    if (shiftType === 'morning' && currentHour >= 6 && currentHour < 14) isTimeValid = true;
    if (shiftType === 'afternoon' && currentHour >= 14 && currentHour < 22) isTimeValid = true;
    if (shiftType === 'night' && (currentHour >= 22 || currentHour < 6)) isTimeValid = true;

    if (!isTimeValid) {
        showCustomAlert("Ca được chọn không đúng với thời gian thực tế hiện tại trên hệ thống máy tính!", "Sai Khung Giờ Ca", "danger");
        return;
    }

    let correctPass = '';
    let shiftText = '';
    if (shiftType === 'morning') { correctPass = '123'; shiftText = 'Ca Sáng (06:00 - 14:00)'; }
    if (shiftType === 'afternoon') { correctPass = '456'; shiftText = 'Ca Chiều (14:00 - 22:00)'; }
    if (shiftType === 'night') { correctPass = '789'; shiftText = 'Ca Đêm (22:00 - 06:00)'; }

    if (pass !== correctPass) {
        showCustomAlert(`Mật khẩu xác thực ca không đúng! (Gợi ý: Ca Sáng: 123 | Ca Chiều: 456 | Ca Đêm: 789)`, "Sai Mật Khẩu Ca", "danger");
        return;
    }

    isShiftOpened = true;
    currentShiftInfo = { cashierId: cashierId, shiftType: shiftType, shiftName: shiftText };

    localStorage.setItem('pos_current_shift', JSON.stringify(currentShiftInfo));

    document.getElementById('shift-status-badge').className = "badge bg-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
    document.getElementById('current-shift-name-label').innerText = shiftText;
    document.getElementById('cashier-id-display').innerText = cashierId;
    document.getElementById('right-panel-cashier').innerText = cashierId;

    shiftModal.hide();
    showCustomAlert(`Mở ca làm việc thành công cho Thu ngân [${cashierId}]!`, "Mở Ca Thành Công", "success");
}

function confirmCloseShift() {
    isShiftOpened = false;
    shiftStats = { totalRevenue: 0, offlineRevenue: 0, onlineRevenue: 0, totalOrders: 0, totalCups: 0, totalCakes: 0 };
    completedOrdersHistory = [];

    localStorage.removeItem('pos_current_shift');

    document.getElementById('shift-status-badge').className = "badge bg-danger rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
    document.getElementById('current-shift-name-label').innerText = "Chưa Mở Ca";
    document.getElementById('cashier-id-display').innerText = "--";
    document.getElementById('right-panel-cashier').innerText = "--";

    shiftModal.hide();
    showCustomAlert("Đã hoàn tất kết ca & đăng xuất! Hệ thống chuyển về màn hình Mở Ca tiếp theo.", "Kết Ca Thành Công", "success");
    
    setTimeout(() => {
        openShiftModal();
    }, 500);
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
        showCustomAlert("Hiện không có ít nhất 2 bàn đang có đơn hàng để thực hiện gộp!", "Không Thể Gộp Bàn", "info");
        return;
    }

    checkMergeSameArea();
    mergeTableModal.show();
}

// -------------------------------------------------------------
// TÍNH NĂNG 3 & 5: CẬP NHẬT GỘP BÀN KHÁC KHU VỰC VỀ TRỐNG NGAY LẬP TỨC
// -------------------------------------------------------------
function confirmMergeTable() {
    let src = document.getElementById('merge-source-table').value;
    let target = document.getElementById('merge-target-table').value;

    if (src === target) {
        showCustomAlert("Bàn nguồn và Bàn đích không thể trùng nhau!", "Thao Tác Sai", "danger");
        return;
    }

    if (!activeOrders[target]) activeOrders[target] = [];
    
    activeOrders[target] = activeOrders[target].concat(activeOrders[src]);
    delete activeOrders[src];

    let srcBadge = document.getElementById('table-btn-' + src);
    let targetBadge = document.getElementById('table-btn-' + target);

    let srcArea = srcBadge ? srcBadge.getAttribute('data-area-id') : null;
    let targetArea = targetBadge ? targetBadge.getAttribute('data-area-id') : null;

    let srcName = srcBadge ? srcBadge.querySelector('.fw-bold').innerText : ("Bàn " + src);
    let targetName = targetBadge ? targetBadge.querySelector('.fw-bold').innerText : ("Bàn " + target);

    if (targetBadge) {
        targetBadge.className = "table-badge status-occupied";
        targetBadge.querySelector('.status-label').innerText = "Có khách";
    }

    if (srcArea !== targetArea) {
        // KHÁC KHU VỰC: Bàn nguồn tự động về TRỐNG ngay lập tức
        if (srcBadge) {
            srcBadge.className = "table-badge status-available";
            srcBadge.querySelector('.status-label').innerText = "Trống";
        }
    } else {
        // CÙNG KHU VỰC: Giữ map để chọn bàn sẽ hỏi Trả bàn hoặc Sang bàn
        if (!tableMergeSourcesMap[target]) tableMergeSourcesMap[target] = [];
        tableMergeSourcesMap[target].push(src);
        tableMergedTargetMap[src] = target;

        if (srcBadge) {
            srcBadge.className = "table-badge status-occupied";
            srcBadge.querySelector('.status-label').innerText = `[Gộp -> ${targetName}]`;
        }
    }

    mergeTableModal.hide();
    showCustomAlert(`Gộp thành công từ ${srcName} qua ${targetName}!`, "Gộp Bàn Thành Công", "success");
    
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
        showCustomAlert("Hiện chưa có bàn nào có món để thực hiện chuyển!", "Không Có Đơn", "info");
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

    document.querySelectorAll('.table-badge').forEach(badge => {
        let tid = badge.id.replace('table-btn-', '');
        let tname = badge.querySelector('.fw-bold').innerText;
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
        showCustomAlert("Hiện không có bàn trống hoặc bàn đặt xoay tua hợp lệ để chuyển tới!", "Không Có Bàn Đích", "danger");
        return;
    }

    if (selectedChks.length === 0) {
        showCustomAlert("Vui lòng tích chọn ít nhất 1 món để thực hiện chuyển bàn!", "Chưa Chọn Món", "danger");
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
        }
    }

    if (srcBadge) {
        if (!activeOrders[srcTable] || activeOrders[srcTable].length === 0) {
            delete activeOrders[srcTable];
            srcBadge.className = "table-badge status-available";
            srcBadge.querySelector('.status-label').innerText = "Trống";
        }
    }

    splitTableModal.hide();
    showCustomAlert(`Đã chuyển ${itemsToMove.length} món sang bàn mới thành công!`, "Chuyển Món Thành Công", "success");
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
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
$baseUrl  = defined('BASE_URL') ? BASE_URL : '';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/css/pos.css?v=<?= time() ?>">

<div class="container-fluid pos-container py-2">
    <div class="row h-100 g-3">
        
        <!-- CỘT TRÁI: ĐIỀU HƯỚNG, SƠ ĐỒ BÀN & THỰC ĐƠN -->
        <div class="col-lg-8 col-md-7 pos-left-scroll">
            
            <!-- HEADER BAR + MULTI-BRANCH + 3 TAB BÁN HÀNG + 3 NÚT CHỨC NĂNG -->
            <div class="pos-card p-2 mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pb-2 mb-2 border-bottom px-1">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge <?= !empty($activeShift) ? 'bg-success' : 'bg-danger' ?> rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1" id="shift-status-badge">
                            <i class="bi bi-clock-history"></i> <span id="current-shift-name-label"><?= !empty($activeShift) ? htmlspecialchars($activeShift['shift_name']) : 'Chưa Mở Ca' ?></span>
                        </span>
                        <small class="text-muted fw-semibold" id="cashier-name-label">
                            <i class="bi bi-person-badge"></i> Thu ngân: <strong class="text-dark" id="cashier-id-display"><?= !empty($activeShift) ? htmlspecialchars($activeShift['cashier_id']) : '--' ?></strong>
                        </small>
                    </div>

                    <!-- ĐA CHI NHÁNH (MULTI-BRANCH) GÓC PHẢI HEADER -->
                    <div class="branch-selector-wrap">
                        <i class="bi bi-shop text-primary"></i>
                        <select id="pos-branch-select" onchange="switchBranch(this.value)" title="Chọn chi nhánh hoạt động">
                            <option value="CN01">CN Bình Thạnh (Trụ sở)</option>
                            <option value="CN02">CN Quận 1 (Nguyễn Huệ)</option>
                            <option value="CN03">CN Quận 3 (Võ Văn Tần)</option>
                            <option value="CN04">CN Thủ Đức (Võ Văn Ngân)</option>
                        </select>
                    </div>
                </div>

                <!-- 3 TAB KÊNH BÁN HÀNG -->
                <div class="order-type-tabs mb-2">
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

                <div class="online-subtabs d-none mb-2" id="online-subtabs-container">
                    <button class="subtab-btn active-grab" id="subtab-grab" onclick="switchOnlinePlatform('grab')">
                        GrabFood
                    </button>
                    <button class="subtab-btn" id="subtab-shopee" onclick="switchOnlinePlatform('shopee')">
                        ShopeeFood
                    </button>
                </div>

                <!-- 3 NÚT CHỨC NĂNG CỐ ĐỊNH GIỐNG LAYOUT MẪU -->
                <div class="topbar-action-row">
                    <button type="button" class="topbar-pill-btn" onclick="openOrderHistoryModal()">
                        <i class="bi bi-clock-history text-primary"></i> Lịch sử đơn trong ca
                    </button>
                    <button type="button" class="topbar-pill-btn btn-reserve-style" onclick="openReservationManagerModal()">
                        <i class="bi bi-calendar-event"></i> Quản lý Đặt bàn
                        <span class="badge bg-warning text-dark rounded-pill ms-1 d-none" id="header-res-count-badge">0</span>
                    </button>
                    <button type="button" class="topbar-pill-btn btn-shift-style" onclick="openShiftModal()">
                        <i class="bi bi-door-open"></i> Mở ca / Kết ca
                    </button>
                </div>
            </div>

            <!-- SƠ ĐỒ BÀN & KHU VỰC (CÓ NÚT THU GỌN / MỞ RỘNG CHỦ ĐỘNG) -->
            <div class="pos-card p-3 mb-3" id="table-section">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h6 class="fw-bold m-0 text-dark d-inline-flex align-items-center gap-1" style="font-size: 13px;">
                            <i class="bi bi-grid-fill text-primary"></i> SƠ ĐỒ BÀN
                        </h6>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 fw-bold" id="table-selected-header-badge" style="font-size: 11px;">Chưa chọn bàn</span>
                        <div class="d-inline-flex align-items-center gap-2 ms-1" style="font-size: 11px;">
                            <span class="text-success fw-semibold"><span class="status-dot" style="background:#10b981;width:7px;height:7px;border-radius:50%;display:inline-block;"></span> Trống</span>
                            <span class="text-danger fw-semibold"><span class="status-dot" style="background:#ef4444;width:7px;height:7px;border-radius:50%;display:inline-block;"></span> Có khách</span>
                            <span class="text-warning fw-semibold"><span class="status-dot" style="background:#f59e0b;width:7px;height:7px;border-radius:50%;display:inline-block;"></span> Đã đặt</span>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button class="btn btn-outline-primary btn-table-touch" onclick="openMergeTableModal()">
                            <i class="bi bi-arrow-merge"></i> Gộp Bàn
                        </button>
                        <button class="btn btn-outline-dark btn-table-touch" onclick="openSplitTableModal()">
                            <i class="bi bi-arrow-left-right"></i> Chuyển Bàn
                        </button>
                        <button class="btn btn-light border btn-table-touch" id="btn-toggle-table-map" onclick="toggleTableMapCollapse()">
                            <i class="bi bi-chevron-up"></i> Thu gọn
                        </button>
                    </div>
                </div>
                
                <div id="table-map-body-collapse" class="mt-3 pt-2 border-top">
                    <?php if (!empty($areas)): ?>
                        <?php foreach ($areas as $area): ?>
                            <?php if (!empty($area['tables'])): ?>
                                <div class="mb-2">
                                    <div class="fw-bold text-uppercase text-secondary mb-1" style="font-size: 11px; letter-spacing: 0.4px;">
                                        <?= htmlspecialchars($area['area_name'] ?? $area['name'] ?? '') ?>
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
                                                        <span class="status-dot"></span>
                                                    </div>
                                                    <small class="text-muted d-block status-label mt-1" style="font-size: 10.5px;"><?= $statusText ?></small>
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
            </div>

            <!-- THẺ MANG VỀ -->
            <div class="pos-card p-3 mb-3 d-none" id="takeaway-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;">DANH SÁCH THẺ MANG VỀ ĐANG PHỤC VỤ</h6>
                    <button class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 py-1 shadow-sm" style="font-size: 11px;" onclick="addNewTakeawayCard()">
                        <i class="bi bi-plus-lg"></i> Tạo Thẻ Mang Về Mới
                    </button>
                </div>
                <div class="row g-2" id="takeaway-cards-grid"></div>
            </div>

            <!-- ĐƠN ONLINE APP -->
            <div class="pos-card p-3 mb-3 d-none" id="online-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;" id="online-section-title">DANH SÁCH ĐƠN APP ONLINE</h6>
                    <button class="btn btn-sm btn-success fw-bold rounded-pill px-3 py-1 shadow-sm" style="font-size: 11px;" onclick="addNewOnlineCard()">
                        <i class="bi bi-plus-lg"></i> Tiếp Nhận Đơn Mới
                    </button>
                </div>
                <div class="row g-2" id="online-cards-grid"></div>
            </div>

            <!-- TÌM MÓN & DANH MỤC (TỐI ƯU CẢM ỨNG) -->
            <div class="mb-3">
                <div class="position-relative mb-2 w-100">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="font-size: 13px;"></i>
                    <input type="text" id="search-product" class="form-control rounded-pill ps-5 pe-3 py-2 shadow-sm border" placeholder="Tìm món nhanh..." onkeyup="searchProduct()">
                </div>

                <div class="d-flex gap-2 overflow-auto py-1 category-touch-bar">
                    <button class="btn cat-btn active-cat text-nowrap" onclick="filterCategory('all', this)">Tất cả món</button>
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
            </div>

            <!-- LƯỚI SẢN PHẨM -->
            <div class="row g-3 pb-3" id="product-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <?php 
                            $pName = $product['product_name'] ?? $product['name'] ?? '';
                            $pPrice = $product['price'] ?? 0;
                            $pImg = $product['image'] ?? $product['image_url'] ?? '';
                            $manageStock = (int)($product['manage_stock'] ?? 0);
                            $stockQty = (float)($product['stock_quantity'] ?? 0);
                            $expiryDate = $product['expiry_date'] ?? '';
                            $isExpired = (!empty($expiryDate) && strtotime($expiryDate . ' 23:59:59') < time());
                            $isOutOfStock = ($manageStock === 1 && $stockQty <= 0);
                            $isUnavailable = ($isExpired || $isOutOfStock);
                        ?>
                        <div class="col-6 col-sm-4 col-md-3 product-item" 
                             data-product-id="<?= $product['id'] ?>"
                             data-category="<?= $product['category_id'] ?>" 
                             data-name="<?= htmlspecialchars($pName) ?>"
                             data-price="<?= $pPrice ?>"
                             data-stock="<?= $stockQty ?>">
                            <div class="card h-100 product-card shadow-sm <?= $isUnavailable ? 'product-unavailable' : '' ?>" 
                                 onclick='handleProductClick(<?= htmlspecialchars(json_encode([
                                'id' => $product['id'],
                                'category_id' => $product['category_id'],
                                'name' => $pName,
                                'price' => $pPrice,
                                'manage_stock' => $manageStock,
                                'stock_quantity' => $stockQty,
                                'expiry_date' => $expiryDate,
                                'modifiers_config' => $product['modifiers_config'] ?? null
                            ]), ENT_QUOTES) ?>)'>
                                <?php 
                                    $imgSrc = !empty($pImg) ? $baseUrl . '/uploads/' . $pImg : 'https://placehold.co/150x110?text=No+Image';
                                ?>
                                <div class="product-img-wrapper">
                                    <img src="<?= $imgSrc ?>" class="product-img" alt="<?= htmlspecialchars($pName) ?>" onerror="this.src='https://placehold.co/150x110?text=No+Image'">
                                    <?php if ($isExpired): ?>
                                        <span class="product-unavailable-tag">HẾT HẠN</span>
                                    <?php elseif ($isOutOfStock): ?>
                                        <span class="product-unavailable-tag">HẾT HÀNG</span>
                                    <?php endif; ?>
                                    <span class="online-price-badge d-none" id="online-badge-<?= $product['id'] ?>">Giá App</span>
                                    <?php if ($isExpired): ?>
                                        <span class="badge bg-danger expiry-warning-badge">Hết HSD</span>
                                    <?php elseif (!empty($expiryDate)): ?>
                                        <span class="badge bg-warning text-dark expiry-warning-badge">HSD: <?= date('d/m', strtotime($expiryDate)) ?></span>
                                    <?php endif; ?>
                                    <?php if ($isOutOfStock): ?>
                                        <span class="badge bg-danger position-absolute bottom-0 start-0 m-1 stock-badge-label" style="font-size: 10px; z-index:2;">Hết kho (<?= $stockQty ?>)</span>
                                    <?php elseif ($manageStock === 1): ?>
                                        <span class="badge bg-dark bg-opacity-75 position-absolute bottom-0 start-0 m-1 stock-badge-label" style="font-size: 10px; z-index:2;">Còn <?= $stockQty ?></span>
                                    <?php endif; ?>
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

        <!-- CỘT PHẢI: GIỎ HÀNG, CRM & THANH TOÁN -->
        <div class="col-lg-4 col-md-5 h-100">
            <div class="pos-right-sticky p-3">
                
                <!-- HEADER GIỎ HÀNG -->
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-dark text-white fw-bold px-2 py-1" id="order-type-badge">TẠI CHỖ</span>
                            <h5 class="fw-bold m-0 text-dark" id="selected-table-name" style="font-size: 15px;">Chưa Chọn Bàn</h5>
                        </div>
                        <small class="text-muted" style="font-size: 11px;">Thu ngân: <span id="right-panel-cashier"><?= !empty($activeShift) ? htmlspecialchars($activeShift['cashier_id']) : '--' ?></span></small>
                        <div id="table-reservation-note" class="text-warning fw-semibold mt-1 d-none" style="font-size: 11px;"></div>
                    </div>
                    <div class="d-flex gap-1 align-items-center">
                        <button class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 py-1 shadow-sm d-none" id="btn-release-table" onclick="releaseCurrentTable()">Trả bàn</button>
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 fw-semibold" style="font-size: 11px;" onclick="clearCart()">
                            <i class="bi bi-trash3"></i> Xóa đơn
                        </button>
                    </div>
                </div>

                <!-- CRM: KHÁCH HÀNG THÂN THIẾT (THANH TÓM TẮT 1 DÒNG GỌN GÀNG, BẤM ĐỂ MỞ POPUP CRM) -->
                <div class="loyalty-card mb-2" id="loyalty-section">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="fw-bold text-secondary" style="font-size: 11px;"><i class="bi bi-crown-fill text-warning"></i> KHÁCH HÀNG THÂN THIẾT</small>
                        <button class="btn btn-link btn-sm p-0 text-decoration-none fw-bold" style="font-size: 11px;" onclick="openRegisterCustomerModal()">+ Thêm Khách</button>
                    </div>
                    <div class="input-group input-group-sm" id="cust-search-bar">
                        <input type="text" id="cust-phone-input" class="form-control" placeholder="Nhập SĐT khách tích điểm..." onkeypress="if(event.key==='Enter') searchCustomerByPhone()">
                        <button class="btn btn-dark fw-bold px-3" type="button" onclick="searchCustomerByPhone()">Tìm</button>
                    </div>
                    
                    <!-- Thanh hiển thị gọn 1 dòng sau khi chọn khách (trả lại không gian rộng cho Giỏ Hàng) -->
                    <div id="cust-info-display" class="d-none mt-1" onclick="openCrmMemberModal()">
                        <input type="hidden" id="redeem-points-input" value="0">
                        <div class="crm-compact-strip">
                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;font-size:12px;">
                                    <i class="bi bi-person-check-fill"></i>
                                </div>
                                <div class="text-truncate">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="fw-bold text-dark text-truncate" style="font-size: 12.5px;" id="cust-name-val">--</span>
                                        <span class="badge bg-secondary" style="font-size: 9.5px;" id="cust-rank-badge">Bronze</span>
                                    </div>
                                    <div style="font-size: 10.5px; color: #475569;">
                                        Điểm: <strong class="text-primary" id="cust-points-val">0</strong> pt
                                        <span id="loyalty-discount-msg" class="text-success fw-bold ms-1 d-none"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                <button type="button" class="btn btn-sm btn-primary fw-bold px-2 py-1 saas-btn-nowrap" style="font-size: 10.5px; border-radius: 7px;" onclick="event.stopPropagation(); openCrmMemberModal();">
                                    <i class="bi bi-gift-fill"></i> Đổi điểm / Quà
                                </button>
                                <button type="button" class="btn btn-sm btn-light border text-danger px-2 py-1" style="font-size: 10.5px; border-radius: 7px;" title="Bỏ chọn khách" onclick="event.stopPropagation(); detachCustomer();">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DANH SÁCH MÓN TRONG GIỎ -->
                <div class="cart-items-list mb-2" id="cart-items">
                    <div class="text-center text-muted my-5 py-4">
                        <span class="d-block text-secondary fw-semibold" style="font-size: 13px;">Vui lòng chọn bàn/thẻ vị trí và chọn món ăn</span>
                    </div>
                </div>

                <!-- KHU VỰC TÍNH TIỀN & NÚT THAO TÁC -->
                <div class="border-top pt-2">
                    <div class="d-flex justify-content-between text-secondary mb-1" style="font-size: 12px;">
                        <span>Tạm tính <span id="cart-item-count-sub">(0 món)</span>:</span>
                        <span id="sub-total" class="fw-semibold text-dark">0 đ</span>
                    </div>
                    <div class="d-flex justify-content-between text-secondary mb-1" style="font-size: 12px;">
                        <span>
                            Thuế VAT (<span id="vat-label-display">8</span>%):
                            <?php if ($userRole === 'admin'): ?>
                                <button type="button" class="btn btn-link btn-xs p-0 text-primary text-decoration-none fw-bold ms-1" onclick="adminUpdateVatSetting()" style="font-size: 10px;" title="Chỉnh sửa % VAT hệ thống (Admin)">
                                    <i class="bi bi-pencil-square"></i> Đổi
                                </button>
                            <?php endif; ?>
                        </span>
                        <span id="vat-total" class="fw-semibold text-dark">0 đ</span>
                    </div>

                    <!-- DÒNG MINH BẠCH ƯU ĐÃI HẠNG THẺ MÀU XANH LÁ -->
                    <div class="d-flex justify-content-between text-success mb-1 d-none" id="tier-discount-row" style="font-size: 12px;">
                        <span class="fw-semibold" id="tier-discount-label">Ưu đãi hạng thẻ:</span>
                        <span class="fw-bold" id="tier-discount-val">-0 đ</span>
                    </div>

                    <!-- DÒNG MINH BẠCH TRỪ TIỀN ĐỔI ĐIỂM -->
                    <div class="d-flex justify-content-between text-success mb-1 d-none" id="points-discount-row" style="font-size: 12px;">
                        <span class="fw-semibold">Quy đổi điểm tiêu dùng:</span>
                        <span class="fw-bold" id="points-discount-val">-0 đ</span>
                    </div>

                    <div class="d-flex justify-content-between fw-bold fs-5 text-dark border-top pt-2 mb-1">
                        <span>TỔNG CỘNG:</span>
                        <span class="text-primary fw-extrabold" id="grand-total">0 đ</span>
                    </div>

                    <!-- DÒNG ĐIỂM TÍCH LŨY DỰ KIẾN -->
                    <div class="d-flex justify-content-between align-items-center bg-light rounded px-2 py-1 mb-2 border" style="font-size: 11px;">
                        <span class="text-muted"><i class="bi bi-star-fill text-warning me-1"></i> Điểm tích lũy đơn này:</span>
                        <span class="fw-bold text-primary" id="order-earned-points-val">+0 điểm</span>
                    </div>

                    <div class="mb-2" id="box-btn-ready">
                        <button class="btn btn-sm btn-outline-success fw-bold w-100 rounded-3 pos-action-btn-nowrap d-inline-flex align-items-center justify-content-center gap-1" id="btn-ready-notify" onclick="notifyKitchenReady()" disabled>
                            <i class="bi bi-bell"></i> THÔNG BÁO HOÀN TẤT & RUNG THẺ
                        </button>
                    </div>
                    
                    <!-- 2 NÚT BÁO BẾP & THANH TOÁN NHANH -->
                    <div class="d-flex gap-2 mb-2">
                        <button class="btn btn-outline-dark fw-bold py-2 rounded-3 w-50 pos-action-btn-nowrap d-inline-flex align-items-center justify-content-center gap-1" id="btn-kitchen" onclick="sendToKitchen()" disabled>
                            <i class="bi bi-printer"></i> BÁO BẾP / IN TEM
                        </button>
                        <button class="btn btn-saas-quickpay fw-bold py-2 rounded-3 w-50 shadow-sm pos-action-btn-nowrap d-inline-flex align-items-center justify-content-center gap-1" id="btn-pay-direct" onclick="openCheckoutModal(true)" disabled title="Thanh toán tiền mặt nhanh tại chỗ">
                            <i class="bi bi-lightning-charge-fill"></i> THANH TOÁN NGAY
                        </button>
                    </div>

                    <!-- NÚT THANH TOÁN ĐA PHƯƠNG THỨC / TRẢ BÀN -->
                    <div>
                        <button class="btn btn-saas-checkout fw-bold py-2 px-3 rounded-3 w-100 shadow-sm pos-action-btn-nowrap d-inline-flex align-items-center justify-content-center gap-1" id="btn-checkout" onclick="openCheckoutModal(false)" disabled title="Thanh toán đa phương thức & trả bàn">
                            <i class="bi bi-credit-card"></i> THANH TOÁN (TRẢ BÀN)
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- =========================================================================
     POPUP KHÁCH HÀNG THÂN THIẾT (CRM MODAL CHUẨN SAAS 2 CỘT)
     - Cột trái: Thẻ hạng + Trừ điểm + Tự động làm tròn tiền (105.500đ -> 105.000đ)
     - Cột phải: Danh sách quà đổi điểm phân cấp theo từng hạng thẻ
     ========================================================================= -->
<div class="modal fade" id="crmMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content saas-modal-content">
      <div class="saas-modal-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
                <i class="bi bi-crown-fill fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold m-0 text-dark">KHÁCH HÀNG THÂN THIẾT & QUY ĐỔI ƯU ĐÃI</h6>
                <small class="text-muted">Trừ điểm làm tròn hóa đơn & đổi quà độc quyền theo từng hạng thẻ</small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" style="background-color: #f8fafc;">
        <div class="row g-3">
            <!-- CỘT TRÁI: THẺ THÀNH VIÊN & QUY ĐỔI ĐIỂM LÀM TRÒN BILL -->
            <div class="col-md-5">
                <div class="rounded-4 p-3 text-white shadow-sm mb-3" id="crm-modal-card-banner" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <small class="opacity-75 d-block" style="font-size: 11px;">Hội viên POS</small>
                            <h5 class="fw-bold mb-0" id="crm-modal-cust-name">--</h5>
                            <small class="opacity-75" id="crm-modal-cust-phone">--</small>
                        </div>
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1" id="crm-modal-rank-pill">BRONZE</span>
                    </div>
                    <hr class="border-light opacity-25 my-2">
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <small class="opacity-75 d-block" style="font-size: 10.5px;">Điểm tiêu dùng</small>
                            <span class="fs-4 fw-extrabold text-warning" id="crm-modal-avail-pts">0 pt</span>
                        </div>
                        <div class="text-end">
                            <small class="opacity-75 d-block" style="font-size: 10.5px;">Chi tiêu xét hạng</small>
                            <span class="fw-bold" id="crm-modal-spent-val">0 đ</span>
                        </div>
                    </div>
                </div>

                <!-- TRỪ ĐIỂM & TỰ ĐỘNG LÀM TRÒN TIỀN -->
                <div class="bg-white p-3 rounded-4 border shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark" style="font-size: 12.5px;"><i class="bi bi-coin text-warning me-1"></i> Trừ điểm vào hóa đơn</span>
                        <span class="badge bg-light text-secondary border" style="font-size: 10px;">1 pt = 100đ</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center bg-light px-2 py-1 rounded-3 border mb-2" style="font-size: 11.5px;">
                        <span class="text-muted">Tổng bill trước trừ điểm:</span>
                        <strong class="text-dark" id="crm-modal-bill-total">0 đ</strong>
                    </div>
                    <div class="input-group input-group-sm mb-2">
                        <input type="number" id="manual-redeem-pts" class="form-control fw-bold" placeholder="Nhập số điểm..." min="0">
                        <button class="btn btn-outline-primary fw-bold saas-btn-nowrap" type="button" onclick="autoRoundBillWithPoints()" title="Tự động tính điểm để làm tròn số lẻ hóa đơn">
                            <i class="bi bi-magic"></i> Tự động làm tròn
                        </button>
                    </div>
                    <div id="crm-auto-round-hint" class="text-muted mb-2" style="font-size: 10.5px;">
                        VD: Bill <b>105.500đ</b> bấm <b>Tự động làm tròn</b> sẽ tự dùng <b>5 pt</b> (-500đ) còn <b>105.000đ</b>.
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-dark btn-sm fw-bold flex-grow-1 py-2 saas-btn-nowrap" onclick="applyManualPointsRedeem()">
                            <i class="bi bi-check2-circle"></i> ÁP DỤNG TRỪ ĐIỂM
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="clearPointsRedeem()" title="Bỏ trừ điểm">
                            Hủy
                        </button>
                    </div>
                </div>
            </div>

            <!-- CỘT PHẢI: DANH SÁCH QUÀ ĐỔI ĐIỂM PHÂN CẤP THEO TỪNG HẠNG THẺ -->
            <div class="col-md-7">
                <div class="bg-white p-3 rounded-4 border shadow-sm h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark m-0" style="font-size: 13.5px;">
                            <i class="bi bi-gift-fill text-danger me-1"></i> Quà Đổi Điểm Theo Hạng Thành Viên
                        </h6>
                        <span class="badge bg-primary-subtle text-primary" style="font-size: 10.5px;">Hạng cao mở khóa quà VIP</span>
                    </div>
                    <div class="d-flex flex-column gap-2 overflow-auto pe-1" id="crm-tier-rewards-container" style="max-height: 330px;">
                        <!-- Render tự động theo hạng của khách hàng -->
                    </div>
                    <small class="text-muted mt-auto pt-2 border-top d-block" style="font-size: 10.5px;">
                        * Quà đổi điểm thêm vào đơn với giá 0đ và trừ Điểm tiêu dùng (không làm tụt hạng thẻ).
                    </small>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL 1: OPTION MÓN -->
<div class="modal fade" id="productOptionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content saas-modal-content">
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
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content saas-modal-content">
      <div class="saas-modal-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark m-0 d-inline-flex align-items-center gap-2">
            <i class="bi bi-person-plus-fill text-primary"></i> ĐĂNG KÝ THÀNH VIÊN MỚI
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
            <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">SỐ ĐIỆN THOẠI (*)</label>
            <input type="text" id="reg-cust-phone" class="form-control rounded-3" placeholder="Nhập số điện thoại (10 số)..." maxlength="10">
        </div>
        <div class="mb-1">
            <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">HỌ VÀ TÊN (*)</label>
            <input type="text" id="reg-cust-name" class="form-control rounded-3" placeholder="Nhập họ và tên khách hàng...">
        </div>
      </div>
      <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-dark w-100 fw-bold py-2 rounded-3" onclick="confirmRegisterCustomer()">LƯU & CHỌN KHÁCH HÀNG</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CẤU HÌNH THUẾ VAT ĐỘNG (DÀNH CHO ADMIN) -->
<div class="modal fade" id="vatSettingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
    <div class="modal-content saas-modal-content">
      <div class="saas-modal-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark m-0 d-inline-flex align-items-center gap-2">
            <i class="bi bi-gear-fill text-primary"></i> CẤU HÌNH THUẾ VAT
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-2">
            <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">MỨC THUẾ VAT HỆ THỐNG (%) (*)</label>
            <div class="input-group">
                <input type="number" id="vat-setting-input" class="form-control rounded-3 fw-bold text-primary" placeholder="8" min="0" max="100" step="0.5">
                <span class="input-group-text fw-bold">%</span>
            </div>
            <small class="text-muted d-block mt-2" style="font-size: 11px;">
                Mức thuế này sẽ áp dụng trực tiếp cho tất cả các đơn hàng mới.
            </small>
        </div>
      </div>
      <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
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
    <div class="modal-content saas-modal-content p-3 text-center">
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
    <div class="modal-content saas-modal-content">
      <div class="saas-modal-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-merge text-primary"></i> GỘP BÀN / CHUYỂN HÓA ĐƠN BÀN HIỆN TẠI
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
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
      <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
        <button class="btn btn-dark w-100 fw-bold py-2 rounded-3" onclick="confirmMergeTable()">XÁC NHẬN GỘP BÀN & HÓA ĐƠN</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL 4: CHUYỂN BÀN & CHUYỂN BILL -->
<div class="modal fade" id="splitTableModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content saas-modal-content">
      <div class="saas-modal-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-right-left text-primary"></i> CHUYỂN BÀN / CHUYỂN MÓN SANG HÓA ĐƠN MỚI
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
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
            <div id="split-items-list" class="border rounded-3 p-2 bg-light" style="max-height: 180px; overflow-y: auto;">
                <small class="text-muted d-block text-center py-3">Chọn bàn để xem danh sách món ăn</small>
            </div>
        </div>
        <div class="mb-2">
            <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">BÀN ĐÍCH NHẬN MÓN CHUYỂN (BÀN TRỐNG HỢP LỆ)</label>
            <select id="split-target-table" class="form-select rounded-3"></select>
        </div>
      </div>
      <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
        <button class="btn btn-dark w-100 fw-bold py-2 rounded-3" onclick="confirmSplitTable()">XÁC NHẬN CHUYỂN MÓN</button>
      </div>
    </div>
  </div>
</div>

<<!-- MODAL QUẢN LÝ ĐẶT BÀN TRƯỚC HẸN KHÁCH (SAAS HIỆN ĐẠI + TAB 50/50 + KHÓA SỐ KHÁCH >= 1) -->
<div class="modal fade" id="reservationManagerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content saas-modal-content">
      <div class="saas-modal-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-3 bg-warning bg-opacity-15 text-warning d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                <i class="bi bi-calendar2-check-fill fs-5"></i>
            </div>
            <div>
                <h5 class="fw-bold m-0 text-dark" style="font-size: 16px;">QUẢN LÝ ĐẶT BÀN TRƯỚC HẸN KHÁCH</h5>
                <small class="text-muted">Hỗ trợ nhiều khung giờ trên cùng 1 bàn • Tự động giữ bàn trước 30–60 phút</small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" style="background-color: #f8fafc;">
        <ul class="nav nav-pills bg-white p-1 rounded-3 border mb-4 shadow-sm" style="display: grid !important; grid-template-columns: 1fr 1fr; gap: 6px; width: 100%;">
          <li class="nav-item" style="width: 100%; margin: 0;">
            <button class="nav-link active fw-bold rounded-3 w-100 d-flex align-items-center justify-content-center gap-2" style="width: 100% !important;" id="res-tab-list-btn" data-bs-toggle="tab" data-bs-target="#tab-res-list">
                <i class="bi bi-list-ul"></i> DANH SÁCH LỊCH ĐẶT BÀN
            </button>
          </li>
          <li class="nav-item" style="width: 100%; margin: 0;">
            <button class="nav-link fw-bold rounded-3 w-100 d-flex align-items-center justify-content-center gap-2" style="width: 100% !important;" id="res-tab-form-btn" data-bs-toggle="tab" data-bs-target="#tab-res-form">
                <i class="bi bi-plus-circle-fill"></i> TẠO LỊCH ĐẶT BÀN MỚI
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade show active" id="tab-res-list">
            <div class="bg-white rounded-4 border shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-secondary" style="font-size: 12px;">CÁC LỊCH ĐẶT BÀN SẮP TỚI</span>
                    <span class="badge bg-amber-subtle text-amber fw-bold px-3 py-1 rounded-pill" id="resCountBadgeDisplay">0 Lượt đặt</span>
                </div>
                <div class="table-responsive border rounded-3" style="max-height: 360px;">
                    <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3 py-2">Bàn</th>
                                <th class="py-2">Tên Khách</th>
                                <th class="py-2">Số Điện Thoại</th>
                                <th class="py-2 text-center">Số Khách</th>
                                <th class="py-2">Thời Gian Hẹn</th>
                                <th class="py-2">Ghi Chú</th>
                                <th class="text-center pe-3 py-2">Thao Tác Nhanh</th>
                            </tr>
                        </thead>
                        <tbody id="reservation-manager-tbody">
                            <tr><td colspan="7" class="text-center text-muted py-4">Chưa có thông tin đặt bàn trước.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-res-form">
            <div class="bg-white rounded-4 border shadow-sm p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHỌN BÀN ĐỂ ĐẶT (*)</label>
                        <select id="reserve-target-table" class="form-select rounded-3 fw-semibold" onchange="checkReservationTableWarning(this)"></select>
                        <div id="reservation-table-alert-box" class="text-primary fw-semibold mt-1 d-none" style="font-size: 11px;"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">SỐ LƯỢNG KHÁCH (*)</label>
                        <input type="number" id="reserve-guest-count" class="form-control rounded-3 fw-bold" min="1" max="50" value="2" oninput="if(this.value !== '' && parseInt(this.value) < 1) this.value = 1;">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">SỐ ĐIỆN THOẠI KHÁCH (*)</label>
                        <input type="text" id="reserve-customer-phone" class="form-control rounded-3" placeholder="Nhập SĐT khách....." maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">TÊN KHÁCH HÀNG (*)</label>
                        <input type="text" id="reserve-customer-name" class="form-control rounded-3" placeholder="Nhập họ tên khách hàng...">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">NGÀY HẸN ĐẾN (*)</label>
                        <input type="date" id="reserve-date" class="form-control rounded-3">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">GIỜ HẸN ĐẾN (*)</label>
                        <input type="time" id="reserve-time" class="form-control rounded-3">
                    </div>
                    <div class="col-12">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">GHI CHÚ ĐẶT BÀN (TRANG TRÍ, SINH NHẬT, VỊ TRÍ...)</label>
                        <input type="text" id="reserve-note" class="form-control rounded-3" placeholder="Nhập ghi chú chi tiết nếu có...">
                    </div>
                </div>
                <div class="alert alert-light border mb-4 py-2" style="font-size: 11.5px;">
                    <i class="bi bi-shield-check text-success me-1"></i> Quy định: Đặt trước <strong>tối thiểu 2 tiếng</strong> và <strong>tối đa 24 tiếng</strong>. Mỗi SĐT chỉ giữ 1 lịch đặt hiệu lực, các khung giờ trên cùng 1 bàn cách nhau tối thiểu 90 phút.
                </div>
                <button class="btn btn-warning w-100 fw-bold py-2 rounded-3 text-dark shadow-sm" onclick="confirmReserveTable()">
                    <i class="bi bi-calendar-plus me-1"></i> XÁC NHẬN TẠO LỊCH ĐẶT BÀN
                </button>
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
    <div class="modal-content saas-modal-content">
      <div class="saas-modal-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2">
            <i class="bi bi-x-circle text-danger"></i> LÝ DO HỦY ĐẶT BÀN <span id="cancel-table-title-id" class="text-primary"></span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="cancel-target-table-id">
        <input type="hidden" id="cancel-target-res-id">
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
      <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
        <button class="btn btn-danger w-100 fw-bold py-2 rounded-3" onclick="confirmCancelReserve()">XÁC NHẬN HỦY ĐẶT BÀN</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL LỊCH SỬ ĐƠN HÀNG (CĂN GIỮA CỘT HÌNH THỨC BÁN + HIỂN THỊ NGÀY GIỜ + BADGE SHOPEE CHỮ TRẮNG) -->
<div class="modal fade" id="orderHistoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content saas-modal-content">
      <div class="saas-modal-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-receipt-cutoff fs-5 text-primary"></i>
            <h6 class="fw-bold m-0 text-dark">LỊCH SỬ ĐƠN HÀNG TRONG CA LÀM VIỆC</h6>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3 py-3">Mã Đơn / Bàn</th>
                        <th class="py-3 text-center">Hình Thức Bán</th>
                        <th class="py-3 text-center">Thời Gian</th>
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
      <div class="modal-footer border-top-0 bg-light py-2">
        <button type="button" class="btn btn-outline-secondary btn-sm fw-bold px-4 rounded-3" data-bs-dismiss="modal">ĐÓNG</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL XEM TRƯỚC VÀ IN LẠI BILL LỊCH SỬ -->
<div class="modal fade" id="reprintBillModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
    <div class="modal-content saas-modal-content p-4 text-center">
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

<!-- MODAL THANH TOÁN & CHỐT ĐƠN (SAAS 2 CỘT CÂN ĐỐI + CHỌN NHANH MỆNH GIÁ TIỀN) -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content saas-modal-content">
      <div class="saas-modal-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2" id="checkoutModalTitle">
            <i class="bi bi-credit-card-2-front text-success"></i> THANH TOÁN & IN HÓA ĐƠN
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" style="background-color: #f8fafc;">
        <div class="row g-3 align-items-stretch">
            <div class="col-md-6">
                <div class="bg-white p-3 rounded-4 border shadow-sm h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="text-center bg-danger bg-opacity-10 rounded-3 py-2 px-3 mb-3 border border-danger border-opacity-10">
                            <small class="text-muted fw-bold d-block" style="font-size: 11px;">TỔNG TIỀN THANH TOÁN</small>
                            <h3 class="fw-extrabold text-danger m-0" id="modal-pay-amount">0 đ</h3>
                        </div>

                        <div id="checkout-standard-payment-box">
                            <ul class="nav nav-pills bg-light p-1 rounded-3 border mb-3" style="display: grid !important; grid-template-columns: 1fr 1fr; gap: 6px; width: 100%;">
                            <li class="nav-item" style="width: 100%; margin: 0;">
                                <button class="nav-link active fw-bold w-100 d-flex align-items-center justify-content-center gap-2" style="width: 100% !important;" id="pills-cash-tab" data-bs-toggle="pill" data-bs-target="#pills-cash">
                                    <i class="bi bi-cash-stack"></i> Tiền Mặt
                                </button>
                            </li>
                            <li class="nav-item" style="width: 100%; margin: 0;">
                                <button class="nav-link fw-bold w-100 d-flex align-items-center justify-content-center gap-2" style="width: 100% !important;" id="pills-qr-tab" data-bs-toggle="pill" data-bs-target="#pills-qr">
                                    <i class="bi bi-qr-code-scan"></i> VietQR
                                </button>
                            </li>
                            </ul>

                            <div class="tab-content">
                              <div class="tab-pane fade show active" id="pills-cash">
                                <div class="mb-2">
                                    <label class="fw-bold text-secondary mb-1" style="font-size: 11.5px;">TIỀN KHÁCH ĐƯA</label>
                                    <input type="number" id="cash-given" class="form-control form-control-lg rounded-3 fw-bold" placeholder="0" oninput="calculateChange()">
                                    <!-- Dải nút chọn nhanh mệnh giá tiền chuẩn SaaS POS -->
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        <button type="button" class="btn btn-xs btn-outline-primary fw-bold px-2 py-1" style="font-size:11px;" onclick="setQuickCashAmount('exact')">Đủ tiền</button>
                                        <button type="button" class="btn btn-xs btn-light border fw-semibold px-2 py-1" style="font-size:11px;" onclick="setQuickCashAmount(50000)">50k</button>
                                        <button type="button" class="btn btn-xs btn-light border fw-semibold px-2 py-1" style="font-size:11px;" onclick="setQuickCashAmount(100000)">100k</button>
                                        <button type="button" class="btn btn-xs btn-light border fw-semibold px-2 py-1" style="font-size:11px;" onclick="setQuickCashAmount(200000)">200k</button>
                                        <button type="button" class="btn btn-xs btn-light border fw-semibold px-2 py-1" style="font-size:11px;" onclick="setQuickCashAmount(500000)">500k</button>
                                    </div>
                                    <div id="inline-payment-error" class="text-danger fw-bold mt-2 d-none" style="font-size: 11.5px;">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Số tiền khách đưa chưa đủ để hoàn tất thanh toán!
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center fw-bold fs-6 text-dark border-top pt-2 mt-3">
                                    <span>TIỀN THỪA KHÁCH:</span>
                                    <span class="text-success" id="cash-change">0 đ</span>
                                </div>
                              </div>
                              <div class="tab-pane fade text-center py-2" id="pills-qr">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=QUAN_CA_PHE_BAT_ON_VIETQR" class="img-fluid rounded-3 mb-2 border p-2 bg-white">
                                <small class="d-block text-muted">Vui lòng quét mã VietQR để chuyển khoản thanh toán</small>
                              </div>
                            </div>
                        </div>

                        <div id="checkout-online-app-box" class="d-none">
                            <div class="alert alert-success border-success bg-success bg-opacity-10 py-2 mb-3" style="font-size: 11px;">
                                <i class="bi bi-check-circle-fill text-success me-1"></i> Đơn hàng đã thanh toán trên ứng dụng. Không thu tiền mặt từ Shipper.
                            </div>
                            <div class="mb-2">
                                <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">MÃ ĐƠN TÊN SÀN (GRAB/SHOPEE) (*)</label>
                                <input type="text" id="app-order-code-input" class="form-control rounded-3" placeholder="VD: MEX-882910...">
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">TÊN TÀI XẾ</label>
                                    <input type="text" id="app-driver-name-input" class="form-control rounded-3" placeholder="Tên shipper...">
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold text-secondary mb-1" style="font-size: 11px;">SĐT TÀI XẾ</label>
                                    <input type="text" id="app-driver-phone-input" class="form-control rounded-3" placeholder="SĐT..." maxlength="10">
                                </div>
                            </div>
                            <div class="mb-2">
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
                </div>
            </div>

            <div class="col-md-6">
                <div class="bg-white p-3 rounded-4 border shadow-sm h-100 d-flex align-items-center justify-content-center">
                    <div class="receipt-mockup" id="receipt-preview-content"></div>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer border-top bg-white px-4 py-3">
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
    <div class="modal-content saas-modal-content">
      <div class="saas-modal-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0 d-inline-flex align-items-center gap-2" id="shiftModalTitle">
            <i class="bi bi-clock-history text-primary"></i> QUẢN LÝ CA LÀM VIỆC
        </h6>
        <button type="button" class="btn-close" id="shift-modal-close-btn" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
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
                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">TIỀN MẶT THỰC TẾ TRONG KÉT (*)</label>
                        <input type="number" id="shift-ending-cash-actual" class="form-control form-control-lg rounded-3 fw-bold text-primary" placeholder="0" oninput="calculateShiftDifference()">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 12px;">CHÊNH LỆCH KÉT (LỆCH TIỀN MẶT)</label>
                        <div class="form-control form-control-lg rounded-3 fw-bold bg-light" id="shift-cash-diff-display">0 đ</div>
                    </div>
                </div>

                <div class="mb-3 d-none" id="shift-cash-diff-reason-box">
                    <label class="fw-bold text-danger mb-1" style="font-size: 12px;">
                        <i class="bi bi-exclamation-circle-fill"></i> LÝ DO / GHI CHÚ CHÊNH LỆCH KÉT (BẮT BUỘC) (*)
                    </label>
                    <input type="text" id="shift-cash-diff-reason" class="form-control border-danger rounded-3" placeholder="Nhập rõ nguyên nhân thừa/thiếu tiền mặt..." oninput="calculateShiftDifference()">
                </div>

                <div class="row g-3 mb-3 p-2 bg-light rounded-3 border mx-0">
                    <div class="col-md-6">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 11.5px;">TIỀN GIỮ LẠI KÉT CHO CA SAU (VỐN LẺ)</label>
                        <input type="number" id="shift-retained-cash-next" class="form-control rounded-3 fw-bold text-dark" placeholder="0" oninput="calculateShiftDifference()">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold text-secondary mb-1" style="font-size: 11.5px;">TIỀN MẶT THỰC NỘP VỀ QUẢN LÝ</label>
                        <div class="form-control rounded-3 fw-bold bg-white text-success" id="shift-handover-cash-display">0 đ</div>
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

<!-- MODAL ALERT (SAAS DIALOG RỘNG RÃI, KHÔNG BÓP CHỮ, NÚT 1 DÒNG CÂN ĐỐI) -->
<div class="modal fade" id="customAlertModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
    <div class="modal-content saas-modal-content text-center p-4">
      <div class="modal-body p-0">
        <div class="alert-icon-circle" id="alertIconCircle">
            <i class="bi bi-clock-history" id="alertIcon"></i>
        </div>
        <h5 class="fw-bold text-dark mb-2" id="alertTitle" style="font-size: 18px; line-height: 1.35;">Thông Báo Hệ Thống</h5>
        <div class="text-secondary mb-4" id="alertMessage" style="font-size: 13.5px; line-height: 1.55;">Nội dung thông báo...</div>
        <div id="alert-action-buttons">
            <button type="button" class="btn btn-dark w-100 fw-bold rounded-3 py-2 saas-btn-nowrap" data-bs-dismiss="modal">ĐỒNG Ý</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
window.POS_CONFIG = {
    baseUrl: <?= json_encode($baseUrl) ?>,
    userRole: <?= json_encode($userRole) ?>,
    toppingCatId: <?= json_encode($toppingCatId) ?>
};
</script>
<script src="<?= $baseUrl ?>/js/pos.js?v=<?= time() ?>"></script>

<?php 
$footerPath = __DIR__ . '/../inc/footer.php';
if (!file_exists($footerPath)) {
    $footerPath = __DIR__ . '/../layouts/footer.php';
}
if (file_exists($footerPath)) {
    require_once $footerPath;
}
?>
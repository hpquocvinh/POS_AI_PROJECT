<?php
// app/views/customer/index.php
require_once '../app/views/layouts/header.php';
$canAdmin = $isAdmin ?? true;
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/customer.css?v=<?= time() ?>">

<div class="main-content" style="background: #f8fafc; min-height: 100vh;">
    <div class="crm-page-wrap p-4">

        <!-- TOAST THÔNG BÁO THƯƠNG MẠI (Thay thế alert trình duyệt) -->
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1090;">
            <div id="crmToast" class="toast align-items-center border-0 shadow-lg rounded-3" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body fw-semibold" id="crmToastMsg" style="font-size: 13.5px;"></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>

        <!-- PAGE HEADER -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Quản Lý Khách Hàng & Chương Trình Thành Viên</h4>
                <p class="text-muted mb-0" style="font-size: 13.5px;">Quản lý hội viên, phân hạng tự động theo chi tiêu, lịch sử tích/đổi điểm và kho quà tặng POS</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($canAdmin): ?>
                <a href="<?= BASE_URL ?>/customer/exportExcel" class="btn btn-outline-success bg-white fw-bold rounded-3 px-3 py-2 shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Xuất Excel
                </a>
                <?php endif; ?>
                <button type="button" class="btn btn-dark fw-bold rounded-3 px-4 py-2 shadow-sm" onclick="openCustomerFormModal()">
                    <i class="fa-solid fa-user-plus me-1"></i> Thêm Hội Viên Mới
                </button>
            </div>
        </div>

        <!-- 4 THẺ KPI TỔNG QUAN -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="crm-kpi-card d-flex align-items-center gap-3">
                    <div class="crm-kpi-icon" style="background: #eef2ff; color: #4f46e5;">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">Tổng Số Thành Viên</small>
                        <h4 class="fw-bold text-dark mb-0" id="kpi-total-members">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="crm-kpi-card d-flex align-items-center gap-3">
                    <div class="crm-kpi-icon" style="background: #dcfce7; color: #16a34a;">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">Khách Mới Trong Tháng</small>
                        <h4 class="fw-bold text-success mb-0" id="kpi-new-month">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="crm-kpi-card d-flex align-items-center gap-3">
                    <div class="crm-kpi-icon" style="background: #fef9c3; color: #ca8a04;">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">Khách Hàng VIP / Diamond</small>
                        <h4 class="fw-bold text-dark mb-0" id="kpi-vip-members">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="crm-kpi-card d-flex align-items-center gap-3">
                    <div class="crm-kpi-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">Tỷ Lệ Khách Quay Lại</small>
                        <h4 class="fw-bold text-primary mb-0" id="kpi-retention-rate">0%</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- NAVIGATION TABS DÀN NGANG ĐỀU BẰNG KHỐI DƯỚI -->
        <ul class="nav nav-pills nav-fill crm-main-tabs mb-4 gap-2" id="crmMainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-customers-btn" data-bs-toggle="pill" data-bs-target="#tab-customers" type="button">
                    <i class="fa-solid fa-address-book me-1"></i> Danh Sách Hội Viên
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-rewards-btn" data-bs-toggle="pill" data-bs-target="#tab-rewards" type="button" onclick="loadRewardsCatalog()">
                    <i class="fa-solid fa-store me-1"></i> Kho Quà Đổi Điểm 
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-tiers-btn" data-bs-toggle="pill" data-bs-target="#tab-tiers" type="button" onclick="loadLoyaltySettings()">
                    <i class="fa-solid fa-award me-1"></i> Chính Sách Hạng Thẻ & Quy Đổi
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- TAB 1: DANH SÁCH HỘI VIÊN -->
            <div class="tab-pane fade show active" id="tab-customers" role="tabpanel">
                <div class="bg-white border rounded-4 shadow-sm overflow-hidden">
                    <!-- FILTER BAR -->
                    <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex flex-wrap align-items-center gap-2" id="crm-rank-pills">
                            <button type="button" class="crm-rank-filter active" data-rank="ALL">Tất cả</button>
                            <button type="button" class="crm-rank-filter" data-rank="Bronze">Bronze</button>
                            <button type="button" class="crm-rank-filter" data-rank="Silver">Silver</button>
                            <button type="button" class="crm-rank-filter" data-rank="Gold">Gold</button>
                            <button type="button" class="crm-rank-filter" data-rank="Diamond">Diamond</button>
                            <button type="button" class="crm-rank-filter" data-rank="VIP">VIP</button>
                        </div>

                        <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0">
                            <select id="crm-sort-select" class="form-select form-select-sm rounded-3" style="width: 185px;" onchange="loadCustomers()">
                                <option value="spend_desc">Chi tiêu cao nhất</option>
                                <option value="points_desc">Điểm tích lũy cao nhất</option>
                                <option value="recent_desc">Ngày ghé gần nhất</option>
                                <option value="orders_desc">Số đơn nhiều nhất</option>
                                <option value="newest">Mới đăng ký gần đây</option>
                            </select>
                            <div class="input-group input-group-sm" style="width: 240px;">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="crm-search-input" class="form-control border-start-0" placeholder="Tìm tên hoặc SĐT...">
                            </div>
                        </div>
                    </div>

                    <!-- DATA TABLE -->
                    <div class="table-responsive">
                        <table class="table crm-table mb-0">
                            <thead>
                                <tr>
                                    <th>Hội Viên</th>
                                    <th class="text-center">Hạng Thẻ</th>
                                    <th class="text-end">Điểm Tiêu Dùng</th>
                                    <th class="text-end">Tổng Chi Tiêu</th>
                                    <th class="text-center">Số Đơn / Ghé Gần Nhất</th>
                                    <th class="text-center">Ngày Sinh / Địa Chỉ</th>
                                    <th class="text-end">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody id="crm-customers-tbody">
                                <tr><td colspan="7" class="text-center py-5 text-muted">Đang tải dữ liệu hội viên...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: KHO QUÀ ĐỔI ĐIỂM -->
            <div class="tab-pane fade" id="tab-rewards" role="tabpanel">
                <div class="bg-white border rounded-4 shadow-sm p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Danh Mục Quà Tặng Đổi Điểm Tại Quầy POS</h6>
                            <small class="text-muted">Các phần quà đang mở sẽ tự động hiển thị trong cửa sổ Đổi điểm </small>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm fw-bold rounded-3 px-3 py-2 shadow-sm" onclick="openRewardModal()">
                            <i class="fa-solid fa-plus me-1"></i> Thêm Quà Đổi Điểm
                        </button>
                    </div>
                    <div class="row g-3" id="crm-rewards-grid">
                        <div class="col-12 text-center py-5 text-muted">Đang tải danh sách quà...</div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: CHÍNH SÁCH HẠNG THẺ & QUY ĐỔI -->
            <div class="tab-pane fade" id="tab-tiers" role="tabpanel">
                <div class="bg-white border rounded-4 shadow-sm p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Quy Tắc Nâng Hạng & Đặc Quyền Hội Viên Smart F&B</h6>
                            <small class="text-muted">Cấu hình tỷ lệ tích điểm, quy đổi tiền mặt và mốc thăng hạng áp dụng toàn hệ thống POS</small>
                        </div>
                        <?php if ($canAdmin): ?>
                        <button type="button" class="btn btn-dark btn-sm fw-bold rounded-3 px-3 py-2" onclick="openPolicyEditModal()">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Chỉnh Sửa Chính Sách
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between">
                                <span class="text-muted fw-semibold" style="font-size: 13px;"><i class="fa-solid fa-arrow-trend-up text-success me-2"></i>Tỷ lệ tích điểm cơ bản:</span>
                                <span class="fw-bold text-dark" id="policy-earn-rate-text">10.000đ chi tiêu = 1 điểm</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between">
                                <span class="text-muted fw-semibold" style="font-size: 13px;"><i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i>Giá trị quy đổi điểm tiêu dùng:</span>
                                <span class="fw-bold text-primary" id="policy-redeem-rate-text">1 điểm = 100đ giảm giá bill</span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-uppercase" style="font-size: 12px;">
                                    <th>Hạng Thẻ</th>
                                    <th>Điều Kiện Chi Tiêu Tích Lũy</th>
                                    <th class="text-center">Ưu Đãi Giảm Giá Bill</th>
                                    <th class="text-center">Hệ Số Nhân Điểm</th>
                                    <th>Đặc Quyền Kho Quà POS</th>
                                </tr>
                            </thead>
                            <tbody id="policy-tiers-tbody" style="font-size: 13.5px;"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: THÊM / SỬA HỘI VIÊN -->
<div class="modal fade" id="customerFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom px-4 py-3">
                <h6 class="modal-title fw-bold" id="customerFormModalTitle">Thêm Hội Viên Mới</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="cust-form-id" value="0">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Họ và Tên Hội Viên <span class="text-danger">*</span></label>
                    <input type="text" id="cust-form-name" class="form-control rounded-3" placeholder="VD: Nguyễn Văn A">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Số Điện Thoại <span class="text-danger">*</span></label>
                        <input type="text" id="cust-form-phone" class="form-control rounded-3" placeholder="0901234567">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Hạng Thẻ</label>
                        <select id="cust-form-rank" class="form-select rounded-3" <?= $canAdmin ? '' : 'disabled' ?>>
                            <option value="Bronze">Bronze</option>
                            <option value="Silver">Silver</option>
                            <option value="Gold">Gold</option>
                            <option value="Diamond">Diamond</option>
                            <option value="VIP">VIP</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Ngày Sinh</label>
                        <input type="date" id="cust-form-dob" class="form-control rounded-3">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Email</label>
                        <input type="email" id="cust-form-email" class="form-control rounded-3" placeholder="khachhang@gmail.com">
                    </div>
                </div>
                <div>
                    <label class="form-label fw-semibold small">Địa Chỉ</label>
                    <input type="text" id="cust-form-address" class="form-control rounded-3" placeholder="Quận 1, TP.HCM">
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-light fw-semibold rounded-3" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-dark fw-bold rounded-3 px-4" onclick="saveCustomerForm()">Lưu Hồ Sơ</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2: HỒ SƠ CHI TIẾT KHÁCH HÀNG & LỊCH SỬ GIAO DỊCH -->
<div class="modal fade" id="customerDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom px-4 py-3">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-id-card text-primary me-2"></i>Hồ Sơ Chi Tiết Hội Viên</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="background: #f8fafc;">
                <div class="row g-4 align-items-stretch">
                    <!-- CỘT TRÁI -->
                    <div class="col-lg-4 d-flex flex-column">
                        <div class="member-virtual-card card-Bronze mb-3" id="detail-virtual-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <small class="opacity-75 d-block" style="font-size: 10.5px; letter-spacing: 1px;">SMART F&B MEMBERSHIP</small>
                                    <h5 class="fw-bold mb-0 mt-1" id="detail-cust-name">--</h5>
                                    <small class="opacity-75" id="detail-cust-phone">--</small>
                                </div>
                                <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill shadow-sm" id="detail-cust-rank">BRONZE</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-end mb-3 pt-2">
                                <div>
                                    <small class="opacity-75 d-block" style="font-size: 11px;">Điểm tiêu dùng hiện có</small>
                                    <h3 class="fw-bold text-warning mb-0 card-points-highlight" id="detail-cust-points">0 pt</h3>
                                </div>
                                <div class="text-end">
                                    <small class="opacity-75 d-block" style="font-size: 11px;">Tổng chi tiêu</small>
                                    <span class="fw-bold fs-6" id="detail-cust-spend">0 đ</span>
                                </div>
                            </div>
                            <div class="progress-tier-bar mb-2">
                                <div class="progress-tier-fill" id="detail-tier-progress" style="width: 0%;"></div>
                            </div>
                            <small class="opacity-75 d-block" style="font-size: 11.5px;" id="detail-tier-next-text">--</small>
                        </div>

                        <!-- KHUNG ĐIỀU CHỈNH ĐIỂM (Kéo bằng đáy cột phải & phân quyền Admin) -->
                        <div class="bg-white p-3 rounded-4 border shadow-sm flex-grow-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom" style="font-size: 12px;">
                                    <span class="text-muted"><i class="fa-solid fa-cake-candles text-warning me-1"></i><span id="detail-cust-dob">Chưa cập nhật</span></span>
                                    <span class="text-muted text-truncate ms-2" style="max-width: 170px;" id="detail-cust-address">--</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom" style="font-size: 12px;">
                                    <span class="text-muted text-truncate" style="max-width: 210px;">
                                        <i class="fa-solid fa-envelope text-primary me-1"></i><span id="detail-cust-email">Chưa có email</span>
                                    </span>
                                    <button type="button" id="btn-send-bday-mail" class="btn btn-xs btn-outline-primary rounded-pill fw-bold px-2 py-0 d-none" style="font-size: 11px;" onclick="sendBirthdayMailNow()">
                                        <i class="fa-solid fa-paper-plane me-1"></i>Gửi mail
                                    </button>
                                </div>
                                <h6 class="fw-bold text-dark mb-2" style="font-size: 13.5px;">
                                    <i class="fa-solid fa-sliders text-primary me-1"></i> Cộng / Trừ Điểm Thủ Công
                                </h6>
                                <?php if ($canAdmin): ?>
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        <button type="button" class="btn btn-xs btn-outline-success fw-bold rounded-pill px-2 py-1" style="font-size: 11px;" onclick="quickFillPoints(10, 'Thưởng điểm tích cực')">+10 pt</button>
                                        <button type="button" class="btn btn-xs btn-outline-success fw-bold rounded-pill px-2 py-1" style="font-size: 11px;" onclick="quickFillPoints(50, 'Quà tặng tri ân')">+50 pt</button>
                                        <button type="button" class="btn btn-xs btn-outline-success fw-bold rounded-pill px-2 py-1" style="font-size: 11px;" onclick="quickFillPoints(100, 'Quà chúc mừng sinh nhật')">+100 pt</button>
                                        <button type="button" class="btn btn-xs btn-outline-danger fw-bold rounded-pill px-2 py-1" style="font-size: 11px;" onclick="quickFillPoints(-20, 'Điều chỉnh trừ điểm')">-20 pt</button>
                                    </div>
                                    <input type="hidden" id="adjust-cust-id" value="0">
                                    <div class="mb-2">
                                        <input type="number" id="adjust-points-input" class="form-control form-control-sm rounded-3 fw-bold" placeholder="Số điểm thay đổi (+ / -)...">
                                    </div>
                                    <div class="mb-3">
                                        <input type="text" id="adjust-reason-input" class="form-control form-control-sm rounded-3" placeholder="Lý do (có thể để trống)...">
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-light border text-muted small mb-0">
                                        <i class="fa-solid fa-lock me-1"></i> Chức năng điều chỉnh điểm thủ công chỉ dành cho Quản trị viên.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($canAdmin): ?>
                            <button type="button" class="btn btn-dark btn-sm fw-bold w-100 rounded-3 py-2 mt-auto" onclick="submitAdjustPoints()">
                                Xác Nhận Điều Chỉnh Điểm
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CỘT PHẢI -->
                    <div class="col-lg-8 d-flex flex-column">
                        <div class="bg-white p-4 rounded-4 border shadow-sm flex-grow-1 d-flex flex-column">
                            <div class="row g-2 mb-3">
                                <div class="col-6 col-md-3">
                                    <div class="detail-insight-box">
                                        <small><i class="fa-solid fa-receipt text-primary me-1"></i>Đơn trung bình</small>
                                        <div class="insight-val" id="insight-avg-order">0 đ</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="detail-insight-box">
                                        <small><i class="fa-solid fa-mug-hot text-warning me-1"></i>Món tủ hay gọi</small>
                                        <div class="insight-val text-truncate" id="insight-fav-item" title="">--</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="detail-insight-box">
                                        <small><i class="fa-solid fa-gift text-danger me-1"></i>Tổng điểm đã dùng</small>
                                        <div class="insight-val text-danger" id="insight-used-pts">0 pt</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="detail-insight-box">
                                        <small><i class="fa-solid fa-clock-rotate-left text-success me-1"></i>Ghé quán gần nhất</small>
                                        <div class="insight-val" id="insight-last-visit">--</div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3 TAB DÀN NGANG 100% CHIỀU RỘNG BẢNG -->
                            <ul class="nav nav-pills nav-fill w-100 bg-light p-1 rounded-3 mb-3 gap-1">
                                <li class="nav-item">
                                    <button class="nav-link active fw-bold py-2 px-2 rounded-2" data-bs-toggle="tab" data-bs-target="#tab-detail-orders" style="font-size: 12.5px;">
                                        <i class="fa-solid fa-bag-shopping me-1"></i> Lịch Sử Mua (<span id="detail-order-count">0</span>)
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link fw-bold py-2 px-2 rounded-2" data-bs-toggle="tab" data-bs-target="#tab-detail-points" style="font-size: 12.5px;">
                                        <i class="fa-solid fa-coins me-1"></i> Biến Động Điểm (<span id="detail-ptlog-count">0</span>)
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link fw-bold py-2 px-2 rounded-2" data-bs-toggle="tab" data-bs-target="#tab-detail-favorites" style="font-size: 12.5px;">
                                        <i class="fa-solid fa-heart text-danger me-1"></i> Món Tủ & Quà Đã Đổi
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content flex-grow-1">
                                <div class="tab-pane fade show active" id="tab-detail-orders">
                                    <div class="table-responsive border rounded-3" style="max-height: 340px;">
                                        <table class="table table-hover align-middle detail-history-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Mã Đơn</th>
                                                    <th>Kênh Bán</th>
                                                    <th>Thời Gian</th>
                                                    <th class="text-end">Thanh Toán</th>
                                                    <th class="text-center">Tích Điểm</th>
                                                    <th class="text-center">Dùng Điểm</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detail-orders-tbody"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tab-detail-points">
                                    <div class="table-responsive border rounded-3" style="max-height: 340px;">
                                        <table class="table table-hover align-middle detail-history-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Thời Gian</th>
                                                    <th>Phân Loại</th>
                                                    <th>Nội Dung Giao Dịch</th>
                                                    <th class="text-end">Thay Đổi</th>
                                                    <th class="text-end">Số Dư Sau</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detail-ptlogs-tbody"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tab-detail-favorites">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="border rounded-3 p-3 h-100 bg-light">
                                                <h6 class="fw-bold text-dark mb-2" style="font-size: 13px;"><i class="fa-solid fa-fire text-warning me-1"></i>Top Món Gọi Nhiều Nhất</h6>
                                                <div id="detail-top-items-list" class="d-flex flex-column gap-2"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded-3 p-3 h-100 bg-light">
                                                <h6 class="fw-bold text-dark mb-2" style="font-size: 13px;"><i class="fa-solid fa-gift text-danger me-1"></i>Lịch Sử Quà Đã Đổi</h6>
                                                <div id="detail-redeemed-gifts-list" class="d-flex flex-column gap-2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 3: THÊM / SỬA QUÀ ĐỔI ĐIỂM POS -->
<div class="modal fade" id="rewardFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom px-4 py-3">
                <h6 class="modal-title fw-bold" id="rewardFormModalTitle">Thêm Quà Đổi Điểm Mới</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="reward-form-id" value="0">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Tên Phần Quà / Voucher <span class="text-danger">*</span></label>
                    <input type="text" id="reward-form-name" class="form-control rounded-3" placeholder="VD: Trà Đào Cam Sả Size M">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Điểm Đổi <span class="text-danger">*</span></label>
                        <input type="number" id="reward-form-pts" class="form-control rounded-3 fw-bold" placeholder="100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Hạng Tối Thiểu</label>
                        <select id="reward-form-rank" class="form-select rounded-3">
                            <option value="Bronze">Bronze</option>
                            <option value="Silver">Silver</option>
                            <option value="Gold">Gold</option>
                            <option value="Diamond">Diamond</option>
                            <option value="VIP">VIP</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Tồn Kho Quà</label>
                        <input type="number" id="reward-form-stock" class="form-control rounded-3" value="100">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Mô Tả Ngắn</label>
                    <input type="text" id="reward-form-desc" class="form-control rounded-3" placeholder="Áp dụng tại quầy hoặc mang về">
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="reward-form-active" checked>
                    <label class="form-check-label fw-semibold small" for="reward-form-active">Hiển thị quà này trên POS</label>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-light fw-semibold rounded-3" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary fw-bold rounded-3 px-4" onclick="saveRewardForm()">Lưu Phần Quà</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 4: CHỈNH SỬA CHÍNH SÁCH HẠNG THẺ -->
<div class="modal fade" id="policyEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom px-4 py-3">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-sliders text-primary me-2"></i>Cấu Hình Chính Sách Hạng Thẻ & Quy Đổi Điểm</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Chi tiêu bao nhiêu VNĐ = 1 điểm cơ bản?</label>
                        <input type="number" id="pol-earn-rate" class="form-control rounded-3 fw-bold" value="10000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">1 điểm tiêu dùng quy đổi bao nhiêu VNĐ?</label>
                        <input type="number" id="pol-redeem-rate" class="form-control rounded-3 fw-bold" value="100">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Hạng Thẻ</th>
                                <th>Mốc Chi Tiêu Tối Thiểu (VNĐ)</th>
                                <th>Giảm Giá Bill (%)</th>
                                <th>Hệ Số Nhân Điểm (x)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="rank-pill rank-Bronze">Bronze</span></td>
                                <td><input type="text" class="form-control form-control-sm bg-light" value="0 (Mặc định)" disabled></td>
                                <td><input type="number" step="0.5" id="pol-bronze-disc" class="form-control form-control-sm" value="0"></td>
                                <td><input type="number" step="0.1" id="pol-bronze-mult" class="form-control form-control-sm" value="1.0"></td>
                            </tr>
                            <tr>
                                <td><span class="rank-pill rank-Silver">Silver</span></td>
                                <td><input type="number" id="pol-silver-min" class="form-control form-control-sm" value="1000000"></td>
                                <td><input type="number" step="0.5" id="pol-silver-disc" class="form-control form-control-sm" value="0"></td>
                                <td><input type="number" step="0.1" id="pol-silver-mult" class="form-control form-control-sm" value="1.2"></td>
                            </tr>
                            <tr>
                                <td><span class="rank-pill rank-Gold">Gold</span></td>
                                <td><input type="number" id="pol-gold-min" class="form-control form-control-sm" value="3000000"></td>
                                <td><input type="number" step="0.5" id="pol-gold-disc" class="form-control form-control-sm" value="5"></td>
                                <td><input type="number" step="0.1" id="pol-gold-mult" class="form-control form-control-sm" value="1.5"></td>
                            </tr>
                            <tr>
                                <td><span class="rank-pill rank-Diamond">Diamond</span></td>
                                <td><input type="number" id="pol-diamond-min" class="form-control form-control-sm" value="6000000"></td>
                                <td><input type="number" step="0.5" id="pol-diamond-disc" class="form-control form-control-sm" value="10"></td>
                                <td><input type="number" step="0.1" id="pol-diamond-mult" class="form-control form-control-sm" value="2.0"></td>
                            </tr>
                            <tr>
                                <td><span class="rank-pill rank-VIP">VIP</span></td>
                                <td><input type="number" id="pol-vip-min" class="form-control form-control-sm" value="10000000"></td>
                                <td><input type="number" step="0.5" id="pol-vip-disc" class="form-control form-control-sm" value="15"></td>
                                <td><input type="number" step="0.1" id="pol-vip-mult" class="form-control form-control-sm" value="2.5"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-light fw-semibold rounded-3" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-dark fw-bold rounded-3 px-4" onclick="saveLoyaltySettings()">Lưu Chính Sách</button>
            </div>
        </div>
    </div>
</div>
<!-- MODAL 5: XÁC NHẬN XÓA PHẦN QUÀ -->
<div class="modal fade" id="deleteRewardConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4 shadow-lg text-center p-2">
            <div class="modal-body p-4">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger" style="width: 54px; height: 54px; font-size: 22px;">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Xóa Phần Quà Này?</h6>
                <p class="text-muted small mb-4">Phần quà <strong class="text-dark" id="del-reward-name-label"></strong> sẽ bị gỡ khỏi danh mục đổi điểm POS.</p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light fw-semibold rounded-3 flex-grow-1" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger fw-bold rounded-3 flex-grow-1" id="btn-confirm-delete-reward">Xóa Ngay</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>const CRM_BASE_URL = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>/js/customer.js?v=<?= time() ?>"></script>
<?php require_once '../app/views/layouts/footer.php'; ?>
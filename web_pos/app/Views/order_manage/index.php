<?php
// app/Views/order_manage/index.php
require_once __DIR__ . '/../layouts/header.php';
$canAdmin = $isAdmin ?? true;
$assetBase = (strpos(BASE_URL, '/public') !== false) ? BASE_URL : (BASE_URL . '/public');
?>
<link rel="stylesheet" href="<?= $assetBase ?>/css/order-manage.css?v=<?= time() ?>">

<div class="main-content" style="background: #f8fafc; min-height: 100vh;">
    <div class="om-page-wrap p-4">

        <!-- TOAST THÔNG BÁO THƯƠNG MẠI -->
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1090;">
            <div id="omToast" class="toast align-items-center border-0 shadow-lg rounded-3" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body fw-semibold" id="omToastMsg" style="font-size: 13.5px;"></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>

        <!-- HEADER TIÊU ĐỀ TRANG -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Quản Lý Đơn Hàng & Lịch Sử Giao Dịch</h4>
                <p class="text-muted mb-0" style="font-size: 13.5px;">Theo dõi toàn bộ hóa đơn phát sinh từ quầy POS, đối soát doanh thu đa kênh và quản lý hoàn hủy</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($canAdmin): ?>
                <button type="button" class="btn btn-outline-success bg-white fw-bold rounded-3 px-3 py-2 shadow-sm" onclick="exportOrdersExcel()">
                    <i class="fa-solid fa-file-excel me-1"></i> Xuất Báo Cáo
                </button>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/pos" class="btn btn-dark fw-bold rounded-3 px-4 py-2 shadow-sm">
                    <i class="fa-solid fa-cash-register me-1"></i> Mở Màn Hình POS
                </a>
            </div>
        </div>

        <!-- 1. BỘ 4 THẺ THỐNG KÊ NHANH (KPI CARDS) -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="om-kpi-card d-flex align-items-center gap-3">
                    <div class="om-kpi-icon" style="background: #eef2ff; color: #4f46e5;">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">Tổng Đơn Hàng Thành Công</small>
                        <h4 class="fw-bold text-dark mb-0" id="kpi-completed-orders">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="om-kpi-card d-flex align-items-center gap-3">
                    <div class="om-kpi-icon" style="background: #dcfce7; color: #16a34a;">
                        <i class="fa-solid fa-sack-dollar"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">Tổng Doanh Thu Thuần</small>
                        <h4 class="fw-bold text-success mb-0" id="kpi-net-revenue">0 đ</h4>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="om-kpi-card d-flex align-items-center gap-3">
                    <div class="om-kpi-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">Giá Trị Trung Bình Đơn (AOV)</small>
                        <h4 class="fw-bold text-primary mb-0" id="kpi-aov-value">0 đ</h4>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="om-kpi-card d-flex align-items-center gap-3">
                    <div class="om-kpi-icon" style="background: #fee2e2; color: #dc2626;">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">Đơn Hủy / Hoàn Tiền</small>
                        <div class="d-flex align-items-baseline gap-2">
                            <h4 class="fw-bold text-danger mb-0" id="kpi-cancelled-orders">0</h4>
                            <small class="text-muted fw-semibold" id="kpi-cancel-rate">(0%)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KHỐI BẢNG DỮ LIỆU & THANH LỌC ĐA ĐIỀU KIỆN -->
        <div class="bg-white border rounded-4 shadow-sm overflow-hidden">

            <!-- 2. THANH TÌM KIẾM VÀ BỘ LỌC ĐA ĐIỀU KIỆN -->
            <div class="p-3 border-bottom bg-light bg-opacity-50">
                <!-- Hàng 1: Chọn nhanh mốc thời gian & Tìm kiếm -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-2" id="om-date-pills">
                        <button type="button" class="om-date-pill active" data-preset="today">Hôm nay</button>
                        <button type="button" class="om-date-pill" data-preset="yesterday">Hôm qua</button>
                        <button type="button" class="om-date-pill" data-preset="7days">7 ngày qua</button>
                        <button type="button" class="om-date-pill" data-preset="month">Tháng này</button>
                        <button type="button" class="om-date-pill" data-preset="ALL">Tất cả thời gian</button>
                    </div>

                    <div class="input-group input-group-sm" style="width: 300px;">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="om-search-input" class="form-control border-start-0" placeholder="Tìm mã đơn, tên khách hoặc SĐT...">
                    </div>
                </div>

                <!-- Hàng 2: Lọc kênh bán, trạng thái, thanh toán và ngày tùy chỉnh -->
                <div class="row g-2 align-items-center">
                    <div class="col-6 col-md-2">
                        <select id="om-filter-channel" class="form-select form-select-sm rounded-3" onchange="resetAndLoadOrders()">
                            <option value="ALL">Kênh: Tất cả</option>
                            <option value="dine_in">Tại chỗ (Dine-in)</option>
                            <option value="takeaway">Mang về (Takeaway)</option>
                            <option value="app">Đơn Online App</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select id="om-filter-status" class="form-select form-select-sm rounded-3" onchange="resetAndLoadOrders()">
                            <option value="ALL">Trạng thái: Tất cả</option>
                            <option value="completed">Đã thanh toán</option>
                            <option value="serving">Đang phục vụ</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="om-filter-payment" class="form-select form-select-sm rounded-3" onchange="resetAndLoadOrders()">
                            <option value="ALL">Thanh toán: Tất cả hình thức</option>
                            <option value="cash">Tiền mặt</option>
                            <option value="banking">Chuyển khoản / QR Code</option>
                            <option value="card_wallet">Thẻ / Ví điện tử</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-5 d-flex align-items-center justify-content-md-end gap-2">
                        <span class="text-muted small text-nowrap">Từ:</span>
                        <input type="date" id="om-date-from" class="form-control form-control-sm rounded-3" style="max-width: 145px;" onchange="onCustomDateChange()">
                        <span class="text-muted small text-nowrap">Đến:</span>
                        <input type="date" id="om-date-to" class="form-control form-control-sm rounded-3" style="max-width: 145px;" onchange="onCustomDateChange()">
                    </div>
                </div>
            </div>

            <!-- 3. BẢNG DANH SÁCH ĐƠN HÀNG (DATA TABLE) -->
            <div class="table-responsive">
                <table class="table om-table mb-0">
                    <thead>
                        <tr>
                            <th>Mã Đơn Hàng</th>
                            <th>Thời Gian Tạo</th>
                            <th>Kênh / Vị Trí</th>
                            <th>Thu Ngân</th>
                            <th class="text-end">Tổng Tiền</th>
                            <th class="text-center">Thanh Toán</th>
                            <th class="text-center">Trạng Thái</th>
                            <th class="text-end">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="om-orders-tbody">
                        <tr><td colspan="8" class="text-center py-5 text-muted">Đang tải dữ liệu giao dịch...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- THANH PHÂN TRANG -->
            <div class="p-3 border-top bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted" id="om-pagination-info">Hiển thị 0 kết quả</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="om-pagination-list"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- 4. MODAL CHI TIẾT ĐƠN HÀNG & IN LẠI HÓA ĐƠN -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-file-invoice text-primary me-2"></i>Chi Tiết Hóa Đơn <span class="text-primary" id="md-order-code">--</span>
                    </h6>
                    <small class="text-muted" id="md-order-subtitle">--</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4" style="background: #f8fafc;">
                <!-- Khối 1: Thông tin chung -->
                <div class="bg-white p-3 rounded-4 border shadow-sm mb-3">
                    <div class="row g-3" style="font-size: 13px;">
                        <div class="col-6 col-md-3">
                            <span class="text-muted d-block small">Giờ tạo / Thanh toán</span>
                            <strong class="text-dark" id="md-created-at">--</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted d-block small">Kênh / Vị trí</span>
                            <strong class="text-dark" id="md-channel-place">--</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted d-block small">Khách hàng thành viên</span>
                            <strong class="text-dark" id="md-customer-info">--</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted d-block small">Điểm tích lũy đơn này</span>
                            <strong class="text-success" id="md-points-earned">+0 điểm</strong>
                        </div>
                    </div>
                </div>

                <!-- Khối 2: Danh sách món đã gọi -->
                <div class="bg-white rounded-4 border shadow-sm overflow-hidden mb-3">
                    <div class="px-3 py-2 border-bottom bg-light d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark small text-uppercase">Danh Sách Món Đã Gọi</span>
                        <span class="badge bg-dark rounded-pill" id="md-total-qty">0 món</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>Tên Món & Tùy Chọn</th>
                                    <th class="text-center">SL</th>
                                    <th class="text-end">Đơn Giá</th>
                                    <th class="text-end">Thành Tiền</th>
                                </tr>
                            </thead>
                            <tbody id="md-items-tbody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Khối 3: Chi tiết tính tiền & Lịch sử thao tác (Audit Log) -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="bg-white p-3 rounded-4 border shadow-sm h-100">
                            <h6 class="fw-bold text-dark mb-3" style="font-size: 13px;">
                                <i class="fa-solid fa-clock-rotate-left text-primary me-1"></i> Lịch Sử Thao Tác Đơn (Audit Log)
                            </h6>
                            <div class="om-audit-timeline" id="md-audit-log-list"></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-white p-3 rounded-4 border shadow-sm h-100">
                            <h6 class="fw-bold text-dark mb-3" style="font-size: 13px;">
                                <i class="fa-solid fa-calculator text-success me-1"></i> Chi Tiết Thanh Toán
                            </h6>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Tạm tính tiền hàng:</span>
                                <span class="fw-semibold text-dark" id="md-subtotal">0 đ</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Giảm giá / Khuyến mãi:</span>
                                <span class="fw-semibold text-danger" id="md-discount">-0 đ</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted" id="md-vat-label">Thuế VAT (7%):</span>
                                <span class="fw-semibold text-dark" id="md-vat-amount">0 đ</span>
                            </div>
                            <div class="d-flex justify-content-between pt-2 border-top mb-2">
                                <span class="fw-bold text-dark">Tổng khách phải trả:</span>
                                <span class="fw-bold text-primary fs-6" id="md-final-amount">0 đ</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">Tiền khách đưa:</span>
                                <span class="fw-semibold text-dark" id="md-customer-pay">0 đ</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Tiền thừa trả khách:</span>
                                <span class="fw-semibold text-success" id="md-change-amount">0 đ</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top px-4 py-3 justify-content-between">
                <div>
                    <?php if ($canAdmin): ?>
                    <button type="button" class="btn btn-outline-danger fw-bold rounded-3 px-3 d-none" id="md-btn-cancel-order">
                        <i class="fa-solid fa-ban me-1"></i> Hủy Đơn / Hoàn Tiền
                    </button>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light fw-semibold rounded-3" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-dark fw-bold rounded-3 px-4" onclick="printCurrentReceipt()">
                        <i class="fa-solid fa-print me-1"></i> In Lại Hóa Đơn
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 5: XÁC NHẬN HỦY ĐƠN / HOÀN TIỀN (CHỈ DÀNH CHO QUẢN TRỊ VIÊN) -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom px-4 py-3">
                <h6 class="modal-title fw-bold text-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Xác Nhận Hủy Hóa Đơn & Hoàn Tiền
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="cancel-order-id" value="0">
                <div class="p-3 rounded-3 small mb-3" style="background-color: #fffbeb; border: 1px solid #fde68a; color: #92400e;">
                    Hệ thống sẽ chuyển trạng thái hóa đơn <strong id="cancel-order-code-label"></strong> sang <strong>Đã hủy</strong>, tự động hoàn trả tồn kho nguyên liệu/sản phẩm và điều chỉnh lại điểm tích lũy của khách hàng (nếu có).
                </div>
                <label class="form-label fw-bold small">Lý do hủy hóa đơn <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1" style="font-size: 11px;" onclick="setCancelReason('Khách hàng yêu cầu đổi/hủy món')">Khách đổi/hủy món</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1" style="font-size: 11px;" onclick="setCancelReason('Thu ngân nhập sai thông tin thanh toán')">Thu ngân nhập sai</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1" style="font-size: 11px;" onclick="setCancelReason('Đơn hàng trùng lặp trên hệ thống')">Đơn trùng lặp</button>
                </div>
                <textarea id="cancel-reason-input" class="form-control rounded-3" rows="2" placeholder="Nhập lý do cụ thể để lưu lịch sử đối soát..."></textarea>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-light fw-semibold rounded-3" data-bs-dismiss="modal">Quay Lại</button>
                <button type="button" class="btn btn-danger fw-bold rounded-3 px-4" onclick="submitCancelOrder()">Xác Nhận Hủy Đơn</button>
            </div>
        </div>
    </div>
</div>
<!-- KHUNG ẨN PHỤC VỤ IN HÓA ĐƠN KHỔ NHIỆT 80MM -->
<div id="omReceiptPrintZone" class="d-none"></div>

<script>
    const OM_BASE_URL = "<?= BASE_URL ?>";
    const OM_IS_ADMIN = <?= $canAdmin ? 'true' : 'false' ?>;
</script>
<script src="<?= $assetBase ?>/js/order-manage.js?v=<?= time() ?>"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
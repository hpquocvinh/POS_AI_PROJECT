<?php require_once "../app/Views/layouts/header.php"; ?>

<!-- 4 THẺ THỐNG KÊ METRIC (TỰ ĐỘNG CÂN BẰNG TRÊN LAPTOP) -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 p-xl-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Doanh Thu Hôm Nay</span>
                    <h4 class="fw-bold text-dark m-0">12,450,000đ</h4>
                    <small class="text-success fw-semibold"><i class="fa-solid fa-arrow-trend-up me-1"></i>+15.4%</small>
                </div>
                <div class="bg-primary-subtle text-primary rounded-3 p-3">
                    <i class="fa-solid fa-sack-dollar fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 p-xl-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Đơn Hàng Hôm Nay</span>
                    <h4 class="fw-bold text-dark m-0">48 đơn</h4>
                    <small class="text-success fw-semibold"><i class="fa-solid fa-arrow-trend-up me-1"></i>+8 đơn mới</small>
                </div>
                <div class="bg-success-subtle text-success rounded-3 p-3">
                    <i class="fa-solid fa-cart-shopping fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 p-xl-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Sản Phẩm Trong Kho</span>
                    <h4 class="fw-bold text-dark m-0">1,240</h4>
                    <small class="text-warning fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i>5 món sắp hết</small>
                </div>
                <div class="bg-warning-subtle text-warning rounded-3 p-3">
                    <i class="fa-solid fa-boxes-stacked fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 p-xl-4" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="small fw-semibold text-white-50 text-uppercase d-block mb-1">Độ Chính Xác AI</span>
                    <h4 class="fw-bold m-0 text-white">94.2%</h4>
                    <small class="text-white-50"><i class="fa-solid fa-sparkles me-1"></i>Holt-Winters</small>
                </div>
                <div class="bg-white bg-opacity-20 text-white rounded-3 p-3">
                    <i class="fa-solid fa-brain fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CHUYỂN BỎ CỘT CHÍNH VÀ AI WIDGET THÀNH 8 - 4 CHUẨN LG -->
<div class="row g-3 mb-4">
    <!-- Biểu Đồ Doanh Thu -->
    <div class="col-12 col-lg-8">
        <div class="card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-dark m-0">Doanh Thu & Dự Báo AI (7 Ngày Tới)</h6>
                    <small class="text-muted">So sánh thực tế và dữ liệu dự báo bởi AI</small>
                </div>
                <span class="badge-ai-pill"><i class="fa-solid fa-chart-line me-1"></i> Live Analytics</span>
            </div>
            <div id="revenueChart" style="min-height: 320px; width: 100%;"></div>
        </div>
    </div>

    <!-- Widget Đề Xuất AI -->
    <div class="col-12 col-lg-4">
        <div class="card-custom p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-robot text-primary me-2"></i>Đề Xuất Nhập Hàng (AI)</h6>
                <small class="text-muted">Tuần tới</small>
            </div>
            
            <div class="d-flex flex-column gap-3">
                <div class="p-3 bg-light rounded-3 border">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-dark">Cà Phê Arabica Cầu Đất</span>
                        <span class="badge bg-danger">Sắp hết</span>
                    </div>
                    <div class="small text-muted mt-1">Dự báo cầu: <strong>150 kg</strong> (Hiện có: 12 kg)</div>
                    <div class="mt-2 text-primary small font-monospace"><i class="fa-solid fa-lightbulb me-1"></i>Gợi ý: Nhập thêm +140 kg trước T5</div>
                </div>

                <div class="p-3 bg-light rounded-3 border">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-dark">Sữa Tươi Nguyên Chất 1L</span>
                        <span class="badge bg-warning text-dark">Nhu cầu cao</span>
                    </div>
                    <div class="small text-muted mt-1">Dự báo cầu: <strong>80 thùng</strong> (Hiện có: 25 thùng)</div>
                    <div class="mt-2 text-primary small font-monospace"><i class="fa-solid fa-lightbulb me-1"></i>Gợi ý: Nhập thêm +60 thùng</div>
                </div>

                <div class="p-3 bg-light rounded-3 border">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-dark">Trà Oolong Thượng Hạng</span>
                        <span class="badge bg-success">Ổn định</span>
                    </div>
                    <div class="small text-muted mt-1">Dự báo cầu: <strong>40 hộp</strong> (Hiện có: 45 hộp)</div>
                    <div class="mt-2 text-success small font-monospace"><i class="fa-solid fa-circle-check me-1"></i>Kho đủ đáp ứng</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var options = {
        series: [{
            name: 'Doanh Thu Thực Tế (VNĐ)',
            data: [8500000, 9200000, 10100000, 11500000, 12450000, null, null]
        }, {
            name: 'Dự Báo AI (VNĐ)',
            data: [null, null, null, null, 12450000, 13800000, 14200000]
        }],
        chart: {
            type: 'line',
            height: 320,
            toolbar: { show: false },
            fontFamily: 'Plus Jakarta Sans, sans-serif'
        },
        stroke: {
            curve: 'smooth',
            width: [3, 3],
            dashArray: [0, 5]
        },
        colors: ['#4f46e5', '#ec4899'],
        xaxis: {
            categories: ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Hôm nay', 'Thứ 7 (Dự báo)', 'CN (Dự báo)']
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return (val / 1000000).toFixed(1) + "M";
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val ? val.toLocaleString('vi-VN') + " VNĐ" : "Chưa có dữ liệu";
                }
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#revenueChart"), options);
    chart.render();
});
</script>

<?php require_once "../app/Views/layouts/footer.php"; ?>
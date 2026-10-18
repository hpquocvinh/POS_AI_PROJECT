<?php require_once APP_PATH . '/Views/layouts/header.php'; ?>

<!-- Notification Alert -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Quản Lý Sản Phẩm & Kho</h4>
        <p class="text-muted small mb-0">Quản lý danh sách thực đơn, giá bán và số lượng tồn kho</p>
    </div>
    <a href="<?= BASE_URL ?>/product/create" class="btn btn-primary rounded-pill px-4 fw-bold">
        <i class="fa-solid fa-plus me-2"></i>Thêm Sản Phẩm Mới
    </a>
</div>

<!-- Search & Filter Bar -->
<div class="card card-custom p-3 mb-4">
    <div class="row g-3 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Tìm theo Mã SKU hoặc Tên sản phẩm...">
            </div>
        </div>

        <div class="col-md-3">
            <select id="categoryFilter" class="form-select">
                <option value="">-- Tất cả Danh mục --</option>
                <?php 
                    $categories = [];$productList = $products ?? ($data['products'] ?? []);
                    foreach ($productList as$p) {
                        if (!empty($p->category_name) && !in_array($p->category_name,$categories)) {
                            $categories[] =$p->category_name;
                        }
                    }
                    foreach ($categories as$catName):
                ?>
                    <option value="<?= htmlspecialchars($catName) ?>"><?= htmlspecialchars($catName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <select id="statusFilter" class="form-select">
                <option value="">-- Trạng thái --</option>
                <option value="1">Đang bán</option>
                <option value="0">Ngừng bán</option>
            </select>
        </div>

        <div class="col-md-2 text-end">
            <button type="button" id="btnResetFilter" class="btn btn-outline-secondary w-100 rounded-pill">
                <i class="fa-solid fa-rotate-left me-1"></i> Xóa bộ lọc
            </button>
        </div>
    </div>
</div>

<!-- Product Table Card -->
<div class="card card-custom p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small">Hiển thị <strong id="resultCount" class="text-dark"><?= count($productList) ?></strong> sản phẩm</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="productTable">
            <thead class="table-light">
                <tr>
                    <th>Mã SP</th>
                    <th>Hình ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá bán</th>
                    <th>Giá Cost</th>
                    <th>Tồn kho</th>
                    <th>Trạng thái</th>
                    <th class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($productList)): ?>
                    <?php foreach ($productList as$item): ?>
                        <tr class="product-row" 
                            data-code="<?= strtolower(htmlspecialchars($item->product_code)) ?>" 
                            data-name="<?= strtolower(htmlspecialchars($item->product_name)) ?>"
                            data-category="<?= htmlspecialchars($item->category_name ?? '') ?>"
                            data-status="<?= $item->status ?>">
                            <td><span class="fw-bold text-dark"><?= htmlspecialchars($item->product_code) ?></span></td>
                            <td>
                                <?php 
                                    $imgName = !empty($item->image) ?$item->image : 'default.jpg';
                                    
                                    if (strpos(BASE_URL, '/public') !== false) {
                                        $imgSrc = BASE_URL . '/uploads/' .$imgName;
                                    } else {
                                        $imgSrc = BASE_URL . '/public/uploads/' .$imgName;
                                    }
                                ?>
                                <img src="<?= $imgSrc ?>" 
                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/45?text=No+Img';"
                                     alt="Thumb" class="rounded" style="width: 45px; height: 45px; object-fit: cover;">
                            </td>
                            <td>
                                <div class="fw-bold product-title"><?= htmlspecialchars($item->product_name) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($item->category_name ?? 'Chưa phân loại') ?></span>
                            </td>
                            <td class="fw-bold text-primary"><?= number_format($item->price) ?> đ</td>
                            <td class="text-muted"><?= number_format($item->cost_price) ?> đ</td>
                            <td>
                                <?php if ($item->stock_quantity <= 5): ?>
                                    <span class="badge bg-danger-subtle text-danger fw-bold"><?= $item->stock_quantity ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success fw-bold"><?= $item->stock_quantity ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item->status == 1): ?>
                                    <span class="badge bg-success">Đang bán</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Ngừng bán</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/product/edit?id=<?= $item->id ?>" class="btn btn-sm btn-outline-warning rounded-pill me-1" title="Chỉnh sửa">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger rounded-pill btn-delete-trigger" 
                                        data-id="<?= $item->id ?>"
                                        data-name="<?= htmlspecialchars($item->product_name) ?>"
                                        title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="noDataRow">
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="fa-solid fa-box-open fa-3x"></i></div>
                            <p class="mb-0">Chưa có sản phẩm nào trong cơ sở dữ liệu.</p>
                        </td>
                    </tr>
                <?php endif; ?>
                
                <tr id="notFoundRow" style="display: none;">
                    <td colspan="9" class="text-center py-5">
                        <div class="text-muted mb-2"><i class="fa-solid fa-magnifying-glass fa-3x"></i></div>
                        <p class="mb-0 text-muted">Không tìm thấy sản phẩm nào phù hợp với bộ lọc!</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-body text-center p-4">
                <div class="text-danger mb-3">
                    <i class="fa-solid fa-circle-exclamation fa-3x"></i>
                </div>
                <h5 class="fw-bold mb-2">Xác Nhận Xóa Sản Phẩm</h5>
                <p class="text-muted mb-4">Bạn có chắc chắn muốn xóa sản phẩm <strong id="deleteProductName" class="text-dark"></strong> không?<br><small class="text-danger">Hành động này sẽ xóa dữ liệu vĩnh viễn và không thể khôi phục!</small></p>
                
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Hủy bỏ</button>
                    <a id="btnConfirmDelete" href="#" class="btn btn-danger rounded-pill px-4 fw-bold">Xóa Sản Phẩm</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Client-side Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const btnResetFilter = document.getElementById('btnResetFilter');
    const productRows = document.querySelectorAll('.product-row');
    const notFoundRow = document.getElementById('notFoundRow');
    const resultCount = document.getElementById('resultCount');

    // Chuyển đổi chuỗi tiếng Việt sang dạng không dấu
    function removeVietnameseTones(str) {
        if (!str) return '';
        str = str.toLowerCase();
        str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
        str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
        str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
        str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
        str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
        str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
        str = str.replace(/đ/g, "d");
        str = str.replace(/\u0300|\u0301|\u0303|\u0309|\u0323/g, ""); 
        str = str.replace(/\u02C6|\u0306|\u031B/g, ""); 
        return str.trim();
    }

    // Xử lý lọc dữ liệu trên bảng
    function filterProducts() {
        const rawKeyword = searchInput.value;
        const keywordNoAccent = removeVietnameseTones(rawKeyword);

        const selectedCat = categoryFilter.value;
        const selectedStatus = statusFilter.value;

        let visibleCount = 0;

        productRows.forEach(row => {
            const rawCode = row.getAttribute('data-code') || '';
            const rawName = row.getAttribute('data-name') || '';
            const cat = row.getAttribute('data-category') || '';
            const status = row.getAttribute('data-status') || '';

            const codeNoAccent = removeVietnameseTones(rawCode);
            const nameNoAccent = removeVietnameseTones(rawName);

            const matchKeyword = (codeNoAccent.includes(keywordNoAccent) || nameNoAccent.includes(keywordNoAccent));
            const matchCategory = (selectedCat === '' || cat === selectedCat);
            const matchStatus = (selectedStatus === '' || status === selectedStatus);

            if (matchKeyword && matchCategory && matchStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (resultCount) resultCount.textContent = visibleCount;

        if (notFoundRow) {
            notFoundRow.style.display = (visibleCount === 0 && productRows.length > 0) ? '' : 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterProducts);
    if (categoryFilter) categoryFilter.addEventListener('change', filterProducts);
    if (statusFilter) statusFilter.addEventListener('change', filterProducts);

    if (btnResetFilter) {
        btnResetFilter.addEventListener('click', function() {
            searchInput.value = '';
            categoryFilter.value = '';
            statusFilter.value = '';
            filterProducts();
        });
    }

    // Event listener cho modal xóa
    const deleteModalEl = document.getElementById('deleteConfirmModal');
    if (deleteModalEl) {
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        const btnConfirmDelete = document.getElementById('btnConfirmDelete');
        const deleteProductName = document.getElementById('deleteProductName');

        document.querySelectorAll('.btn-delete-trigger').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');

                deleteProductName.textContent = `"${name}"`;
                btnConfirmDelete.href = `<?= BASE_URL ?>/product/delete?id=${id}`;

                deleteModal.show();
            });
        });
    }

    // Tự động ẩn thông báo flash message sau 3s
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 3000);
    });
});
</script>

<?php require_once APP_PATH . '/Views/layouts/footer.php'; ?>
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

<?php
$categoryList = $categories ?? ($data['categories'] ?? []);
$totalProductCats = 0;
$totalIngredientCats = 0;
foreach ($categoryList as $c) {
    if (($c->type ?? 'product') === 'ingredient') $totalIngredientCats++;
    else $totalProductCats++;
}
?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Quản Lý Danh Mục & Nhóm Nguyên Liệu</h4>
        <p class="text-muted small mb-0">Phân loại danh mục thực đơn bán hàng (POS) và nhóm nguyên vật liệu kho</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fa-solid fa-plus me-2"></i>Thêm Danh Mục Mới
    </button>
</div>

<!-- Filter Tabs & Search Bar -->
<div class="card card-custom p-3 mb-4">
    <div class="row g-3 align-items-center">
        <!-- Type Pills -->
        <div class="col-md-6">
            <div class="btn-group" role="group" id="typeTabGroup">
                <button type="button" class="btn btn-primary rounded-pill me-2 px-3 active" data-type="">
                    Tất cả <span class="badge bg-white text-primary ms-1"><?= count($categoryList) ?></span>
                </button>
                <button type="button" class="btn btn-outline-primary rounded-pill me-2 px-3" data-type="product">
                    <i class="fa-solid fa-mug-saucer me-1"></i> Danh mục Món <span class="badge bg-primary-subtle text-primary ms-1"><?= $totalProductCats ?></span>
                </button>
                <button type="button" class="btn btn-outline-primary rounded-pill px-3" data-type="ingredient">
                    <i class="fa-solid fa-cubes-stacked me-1"></i> Nhóm Nguyên Liệu <span class="badge bg-primary-subtle text-primary ms-1"><?= $totalIngredientCats ?></span>
                </button>
            </div>
        </div>

        <!-- Search Input -->
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Tìm tên danh mục hoặc mô tả...">
            </div>
        </div>

        <!-- Status Filter -->
        <div class="col-md-2">
            <select id="statusFilter" class="form-select">
                <option value="">-- Trạng thái --</option>
                <option value="1">Đang hoạt động</option>
                <option value="0">Tạm ẩn</option>
            </select>
        </div>
    </div>
</div>

<!-- Category Table Card -->
<div class="card card-custom p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small">Hiển thị <strong id="resultCount" class="text-dark"><?= count($categoryList) ?></strong> danh mục</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Tên danh mục</th>
                    <th>Phân loại</th>
                    <th>Mô tả</th>
                    <th class="text-center">Số lượng mục</th>
                    <th>Trạng thái</th>
                    <th class="text-center" style="width: 130px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categoryList)): ?>
                    <?php foreach ($categoryList as $item): ?>
                        <?php 
                            $type = $item->type ?? 'product';
                            $status = $item->status ?? 1;
                            $itemCount = ($type === 'ingredient') ? ($item->ingredient_count ?? 0) : ($item->product_count ?? 0);
                        ?>
                        <tr class="category-row"
                            data-name="<?= strtolower(htmlspecialchars($item->category_name)) ?>"
                            data-desc="<?= strtolower(htmlspecialchars($item->description ?? '')) ?>"
                            data-type="<?= $type ?>"
                            data-status="<?= $status ?>">
                            <td><span class="fw-bold text-muted">#<?= $item->id ?></span></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($item->category_name) ?></div>
                            </td>
                            <td>
                                <?php if ($type === 'ingredient'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill">
                                        <i class="fa-solid fa-cubes-stacked me-1"></i> Nhóm Nguyên Liệu
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                                        <i class="fa-solid fa-mug-saucer me-1"></i> Danh mục Món
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <?= !empty($item->description) ? htmlspecialchars($item->description) : '<span class="fst-italic text-black-50">Không có mô tả</span>' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-3 py-1">
                                    <?= $itemCount ?> <?= ($type === 'ingredient') ? 'nguyên liệu' : 'món' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($status == 1): ?>
                                    <span class="badge bg-success">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Tạm ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-warning rounded-pill me-1 btn-edit-trigger"
                                        data-id="<?= $item->id ?>"
                                        data-name="<?= htmlspecialchars($item->category_name) ?>"
                                        data-desc="<?= htmlspecialchars($item->description ?? '') ?>"
                                        data-type="<?= $type ?>"
                                        data-status="<?= $status ?>"
                                        title="Chỉnh sửa">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger rounded-pill btn-delete-trigger"
                                        data-id="<?= $item->id ?>"
                                        data-name="<?= htmlspecialchars($item->category_name) ?>"
                                        title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="fa-solid fa-layer-group fa-3x"></i></div>
                            <p class="mb-0">Chưa có danh mục nào trong cơ sở dữ liệu.</p>
                        </td>
                    </tr>
                <?php endif; ?>

                <tr id="notFoundRow" style="display: none;">
                    <td colspan="7" class="text-center py-5">
                        <div class="text-muted mb-2"><i class="fa-solid fa-magnifying-glass fa-3x"></i></div>
                        <p class="mb-0 text-muted">Không tìm thấy danh mục nào phù hợp!</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm Mới Danh Mục -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= BASE_URL ?>/category/store" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-folder-plus text-primary me-2"></i>Thêm Danh Mục Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Phân loại <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        <option value="product">Danh mục Món bán (Hiển thị trên POS)</option>
                        <option value="ingredient">Nhóm Nguyên liệu (Quản lý Kho & Định lượng)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên danh mục / nhóm <span class="text-danger">*</span></label>
                    <input type="text" name="category_name" class="form-control" placeholder="VD: Trà Trái Cây hoặc Sữa & Kem Béo..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả chi tiết</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Ghi chú ngắn gọn về danh mục này..."></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="1" selected>Hoạt động</option>
                        <option value="0">Tạm ẩn</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Lưu Danh Mục</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Chỉnh Sửa Danh Mục -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= BASE_URL ?>/category/update" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square text-warning me-2"></i>Chỉnh Sửa Danh Mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Phân loại <span class="text-danger">*</span></label>
                    <select name="type" id="edit_type" class="form-select" required>
                        <option value="product">Danh mục Món bán (Hiển thị trên POS)</option>
                        <option value="ingredient">Nhóm Nguyên liệu (Quản lý Kho & Định lượng)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên danh mục / nhóm <span class="text-danger">*</span></label>
                    <input type="text" name="category_name" id="edit_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả chi tiết</label>
                    <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Trạng thái</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="1">Hoạt động</option>
                        <option value="0">Tạm ẩn</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Cập Nhật</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Xác Nhận Xóa -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-body text-center p-4">
                <div class="text-danger mb-3">
                    <i class="fa-solid fa-circle-exclamation fa-3x"></i>
                </div>
                <h5 class="fw-bold mb-2">Xác Nhận Xóa Danh Mục</h5>
                <p class="text-muted mb-4">Bạn có chắc chắn muốn xóa danh mục <strong id="deleteCategoryName" class="text-dark"></strong> không?</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Hủy bỏ</button>
                    <a id="btnConfirmDelete" href="#" class="btn btn-danger rounded-pill px-4 fw-bold">Xóa Danh Mục</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const typeButtons = document.querySelectorAll('#typeTabGroup button');
    const rows = document.querySelectorAll('.category-row');
    const notFoundRow = document.getElementById('notFoundRow');
    const resultCount = document.getElementById('resultCount');
    let selectedType = '';

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

    function filterCategories() {
        const keyword = removeVietnameseTones(searchInput.value);
        const status = statusFilter.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const name = removeVietnameseTones(row.getAttribute('data-name') || '');
            const desc = removeVietnameseTones(row.getAttribute('data-desc') || '');
            const type = row.getAttribute('data-type') || '';
            const rowStatus = row.getAttribute('data-status') || '';

            const matchKeyword = name.includes(keyword) || desc.includes(keyword);
            const matchType = (selectedType === '' || type === selectedType);
            const matchStatus = (status === '' || rowStatus === status);

            if (matchKeyword && matchType && matchStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (resultCount) resultCount.textContent = visibleCount;
        if (notFoundRow) {
            notFoundRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
    }

    typeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            typeButtons.forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary', 'active');
            selectedType = this.getAttribute('data-type');
            filterCategories();
        });
    });

    searchInput.addEventListener('input', filterCategories);
    statusFilter.addEventListener('change', filterCategories);

    // Mở Modal Sửa & đổ dữ liệu
    const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    document.querySelectorAll('.btn-edit-trigger').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_desc').value = this.getAttribute('data-desc');
            document.getElementById('edit_type').value = this.getAttribute('data-type');
            document.getElementById('edit_status').value = this.getAttribute('data-status');
            editModal.show();
        });
    });

    // Mở Modal Xóa
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    document.querySelectorAll('.btn-delete-trigger').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            document.getElementById('deleteCategoryName').textContent = `"${name}"`;
            document.getElementById('btnConfirmDelete').href = `<?= BASE_URL ?>/category/delete?id=${id}`;
            deleteModal.show();
        });
    });

    // Tự động ẩn Alert sau 3s
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });
});
</script>

<?php require_once APP_PATH . '/Views/layouts/footer.php'; ?>
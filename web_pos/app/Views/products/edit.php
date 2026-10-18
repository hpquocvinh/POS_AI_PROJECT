<?php require_once APP_PATH . '/Views/layouts/header.php'; ?>

<?php 
    // Hỗ trợ bắt biến $product và $categories dù controller truyền dạng mảng hay biến lẻ
    $product = $product ?? ($data['product'] ?? null);
    $cats    = $categories ?? ($data['categories'] ?? []);
?>

<!-- Khung hiển thị thông báo Success/Error -->
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

<!-- Content Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Chỉnh Sửa Sản Phẩm</h4>
        <p class="text-muted small mb-0">Cập nhật thông tin chi tiết cho sản phẩm trong thực đơn</p>
    </div>
    <a href="<?= BASE_URL ?>/product" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Quay lại
    </a>
</div>

<?php if ($product): ?>
<form action="<?= BASE_URL ?>/product/update" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="id" value="<?= $product->id ?>">

    <div class="row g-4">
        <!-- Cột trái: Thông tin chính -->
        <div class="col-lg-8">
            <div class="card card-custom p-4 mb-4">
                <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-circle-info me-2"></i>Thông Tin Sản Phẩm</h5>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mã SKU (Sản phẩm) <span class="text-danger">*</span></label>
                        <input type="text" name="product_code" class="form-control" value="<?= htmlspecialchars($product->product_code) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php if (!empty($cats)): ?>    
                                <?php foreach ($cats as $cat): ?>
                                    <option value="<?= $cat->id ?>" <?= ($cat->id == $product->category_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat->category_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product->product_name) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="price" class="form-control" value="<?= (float)$product->price ?>" required>
                            <span class="input-group-text">đ</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá Cost (VNĐ)</label>
                        <div class="input-group">
                            <input type="number" name="cost_price" class="form-control" value="<?= (float)$product->cost_price ?>">
                            <span class="input-group-text">đ</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Số lượng Tồn kho <span class="text-danger">*</span></label>
                        <input type="number" name="stock_quantity" class="form-control" value="<?= (float)$product->stock_quantity ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Trạng thái kinh doanh</label>
                        <select name="status" class="form-select">
                            <option value="1" <?= ($product->status == 1) ? 'selected' : '' ?>>Đang bán</option>
                            <option value="0" <?= ($product->status == 0) ? 'selected' : '' ?>>Ngừng bán</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cột phải: Hình ảnh & Lưu -->
        <div class="col-lg-4">
            <div class="card card-custom p-4 mb-4 text-center">
                <h5 class="fw-bold mb-3 text-primary text-start"><i class="fa-solid fa-image me-2"></i>Hình Ảnh</h5>
                
                <div class="mb-3">
                    <?php 
                        $imgName = !empty($product->image) ? $product->image : 'default.jpg';
                        if (strpos(BASE_URL, '/public') !== false) {
                            $imgSrc = BASE_URL . '/uploads/' . $imgName;
                        } else {
                            $imgSrc = BASE_URL . '/public/uploads/' . $imgName;
                        }
                    ?>
                    <img id="imgPreview" 
                         src="<?= $imgSrc ?>" 
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/180?text=No+Image';"
                         alt="Preview" class="img-thumbnail rounded mb-3" 
                         style="width: 180px; height: 180px; object-fit: cover;">
                </div>

                <div class="mb-3 text-start">
                    <label class="form-label fw-semibold">Thay đổi hình ảnh (nếu có)</label>
                    <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-warning btn-lg rounded-pill fw-bold text-white">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Cập Nhật Sản Phẩm
                </button>
            </div>
        </div>
    </div>
</form>
<?php else: ?>
    <div class="alert alert-danger">Không tìm thấy thông tin sản phẩm!</div>
<?php endif; ?>

<!-- JS Scripts: Preview Ảnh & Kiểm Tra Logic Giá/Tồn Kho -->
<script>
    // Preview Hình ảnh mới khi người dùng chọn file
    document.getElementById('imageInput')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imgPreview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    // Validate Form khi Submit
    const form = document.querySelector('form');
    if (form) {
        const priceInput = document.querySelector('input[name="price"]');
        const costInput = document.querySelector('input[name="cost_price"]');
        const stockInput = document.querySelector('input[name="stock_quantity"]');
        const codeInput = document.querySelector('input[name="product_code"]');
        const nameInput = document.querySelector('input[name="product_name"]');
        const categorySelect = document.querySelector('select[name="category_id"]');

        form.addEventListener('submit', function(e) {
            // Reset cảnh báo cũ
            [codeInput, nameInput, categorySelect, priceInput, costInput, stockInput].forEach(el => el && el.setCustomValidity(''));

            const price = parseFloat(priceInput.value) || 0;
            const cost = parseFloat(costInput.value) || 0;
            const stock = parseFloat(stockInput.value) || 0;

            if (!codeInput.value.trim()) {
                codeInput.setCustomValidity("Vui lòng nhập mã SKU!");
            } else if (!categorySelect.value) {
                categorySelect.setCustomValidity("Vui lòng chọn danh mục!");
            } else if (!nameInput.value.trim()) {
                nameInput.setCustomValidity("Vui lòng nhập tên sản phẩm!");
            } else if (price <= 0) {
                priceInput.setCustomValidity("Giá bán phải lớn hơn 0!");
            } else if (cost < 0) {
                costInput.setCustomValidity("Giá cost không được là số âm!");
            } else if (price <= cost) {
                priceInput.setCustomValidity("Giá bán phải lớn hơn giá cost!");
            } else if (stock < 0) {
                stockInput.setCustomValidity("Số lượng tồn kho không được là số âm!");
            }

            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
            }
        });
    }

    // Tự động ẩn thông báo Alert sau 3 giây
    document.addEventListener("DOMContentLoaded", function() {
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
<!-- JavaScript Real-time Validation khi gõ -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const codeInput = document.querySelector('input[name="product_code"]');
    const nameInput = document.querySelector('input[name="product_name"]');
    const idInput   = document.querySelector('input[name="id"]'); // Có giá trị khi ở trang Edit
    const excludeId = idInput ? idInput.value : '';

    // Hàm hiển thị / xóa thông báo lỗi ngay dưới ô input
    function showFieldFeedback(input, isError, message) {
        let feedback = input.parentNode.querySelector('.invalid-feedback-custom');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback-custom text-danger small mt-1';
            input.parentNode.appendChild(feedback);
        }

        if (isError) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            feedback.innerText = message;
        } else {
            input.classList.remove('is-invalid');
            if (input.value.trim() !== '') {
                input.classList.add('is-valid');
            }
            feedback.innerText = '';
        }
    }

    // Debounce chống spam request khi gõ phím liên tục
    function debounce(func, delay = 400) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // 1. Kiểm tra mã SKU khi dừng gõ
    if (codeInput) {
        codeInput.addEventListener('input', debounce(function() {
            const code = this.value.trim();
            if (code === '') {
                showFieldFeedback(codeInput, false, '');
                return;
            }

            fetch(`<?= BASE_URL ?>/product/checkCode?code=${encodeURIComponent(code)}&exclude_id=${excludeId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.exists) {
                        showFieldFeedback(codeInput, true, `Mã SKU '${code}' đã tồn tại trong hệ thống!`);
                    } else {
                        showFieldFeedback(codeInput, false, '');
                    }
                })
                .catch(err => console.error(err));
        }));
    }

    // 2. Kiểm tra tên sản phẩm khi dừng gõ
    if (nameInput) {
        nameInput.addEventListener('input', debounce(function() {
            const name = this.value.trim();
            if (name === '') {
                showFieldFeedback(nameInput, false, '');
                return;
            }

            fetch(`<?= BASE_URL ?>/product/checkName?name=${encodeURIComponent(name)}&exclude_id=${excludeId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.exists) {
                        showFieldFeedback(nameInput, true, `Tên sản phẩm '${name}' đã tồn tại trong hệ thống!`);
                    } else {
                        showFieldFeedback(nameInput, false, '');
                    }
                })
                .catch(err => console.error(err));
        }));
    }
});
</script>
<?php require_once APP_PATH . '/Views/layouts/footer.php'; ?>
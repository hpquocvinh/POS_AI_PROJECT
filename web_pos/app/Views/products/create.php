<?php require_once APP_PATH . '/Views/layouts/header.php'; ?>

<!-- Khung hiển thị thông báo động bằng JS (Không reload trang) -->
<div id="alertContainer"></div>

<!-- Content Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Thêm Sản Phẩm Mới</h4>
        <p class="text-muted small mb-0">Tạo sản phẩm mới vào danh mục thực đơn và quản lý kho</p>
    </div>
    <a href="<?= BASE_URL ?>/product" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Quay lại
    </a>
</div>

<form id="createProductForm" action="<?= BASE_URL ?>/product/store" method="POST" enctype="multipart/form-data" novalidate>
    <div class="row g-4">
        <!-- Cột trái: Thông tin chính -->
        <div class="col-lg-8">
            <div class="card card-custom p-4 mb-4">
                <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-circle-info me-2"></i>Thông Tin Cơ Bản</h5>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mã SKU (Sản phẩm) <span class="text-danger">*</span></label>
                        <input type="text" name="product_code" class="form-control" placeholder="VD: TXKW01" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php 
                                $cats = $categories ?? ($data['categories'] ?? []);
                            ?>
                            <?php if (!empty($cats)): ?>
                                <?php foreach ($cats as$cat): ?>
                                    <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->category_name) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback">Vui lòng chọn danh mục!</div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" class="form-control" placeholder="VD: Trà Xanh Kiwi" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="price" class="form-control" placeholder="0" required>
                            <span class="input-group-text">đ</span>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá Cost (VNĐ)</label>
                        <div class="input-group">
                            <input type="number" name="cost_price" class="form-control" placeholder="0" value="0">
                            <span class="input-group-text">đ</span>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Số lượng Tồn kho <span class="text-danger">*</span></label>
                        <input type="number" name="stock_quantity" class="form-control" placeholder="0" value="0" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Trạng thái kinh doanh</label>
                        <select name="status" class="form-select">
                            <option value="1" selected>Đang bán</option>
                            <option value="0">Ngừng bán</option>
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
                    <img id="imgPreview" 
                         src="https://via.placeholder.com/180?text=Upload+Image" 
                         alt="Preview" class="img-thumbnail rounded mb-3" 
                         style="width: 180px; height: 180px; object-fit: cover;">
                </div>

                <div class="mb-3 text-start">
                    <label class="form-label fw-semibold">Chọn hình ảnh sản phẩm</label>
                    <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" id="btnSubmit" class="btn btn-primary btn-lg rounded-pill fw-bold">
                    <i class="fa-solid fa-plus me-2"></i>Thêm Sản Phẩm Mới
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('createProductForm');
    const imageInput = document.getElementById('imageInput');
    const imgPreview = document.getElementById('imgPreview');
    const alertContainer = document.getElementById('alertContainer');

    // Preview hình ảnh khi chọn file
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Hiển thị alert tự đóng sau 3s
    function showAlert(type, message) {
        alertContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show mb-4" role="alert">
                <i class="fa-solid ${type === 'danger' ? 'fa-triangle-exclamation' : 'fa-circle-check'} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                setTimeout(() => alert.remove(), 500);
            }
        }, 3000);
    }

    // Đánh dấu lỗi ô Input
    function setFieldError(input, message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        let feedback = input.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = input.closest('.col-md-6, .col-md-12').querySelector('.invalid-feedback');
        }
        if (feedback) feedback.innerText = message;
    }

    function clearFieldError(input) {
        input.classList.remove('is-invalid');
    }

    // Xử lý Submit Form - Bắt lỗi 1 LƯỢT ĐỒNG THỜI
    form.addEventListener('submit', async function(e) {
        e.preventDefault(); // CHẶN reload trang

        // Reset toàn bộ trạng thái lỗi cũ
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => clearFieldError(input));

        // Lấy dữ liệu các ô
        const codeInput = form.querySelector('input[name="product_code"]');
        const nameInput = form.querySelector('input[name="product_name"]');
        const categorySelect = form.querySelector('select[name="category_id"]');
        const priceInput = form.querySelector('input[name="price"]');
        const costInput = form.querySelector('input[name="cost_price"]');
        const stockInput = form.querySelector('input[name="stock_quantity"]');

        const code = codeInput.value.trim();
        const name = nameInput.value.trim();
        const category = categorySelect.value;
        const priceStr = priceInput.value.trim();
        const costStr = costInput.value.trim();
        const stockStr = stockInput.value.trim();

        const price = parseFloat(priceStr);
        const cost = parseFloat(costStr);
        const stock = parseFloat(stockStr);

        let hasError = false;

        // --- BẮT ĐẦU KIỂM TRA ĐỒNG THỜI TOÀN BỘ Ô TỪ TRÊN XUỐNG ---

        // 1. Kiểm tra Mã SKU (Rỗng hoặc Trùng CSDL)
        if (!code) {
            setFieldError(codeInput, "Vui lòng nhập mã SKU!");
            hasError = true;
        } else {
            try {
                const resCode = await fetch(`<?= BASE_URL ?>/product/checkCode?code=${encodeURIComponent(code)}`);
                const dataCode = await resCode.json();
                if (dataCode.exists) {
                    setFieldError(codeInput, `Mã SKU '${code}' đã tồn tại trong hệ thống!`);
                    hasError = true;
                }
            } catch (err) { console.error(err); }
        }

        // 2. Kiểm tra Danh mục
        if (!category) {
            setFieldError(categorySelect, "Vui lòng chọn danh mục!");
            hasError = true;
        }

        // 3. Kiểm tra Tên sản phẩm (Rỗng hoặc Trùng CSDL)
        if (!name) {
            setFieldError(nameInput, "Vui lòng nhập tên sản phẩm!");
            hasError = true;
        } else {
            try {
                const resName = await fetch(`<?= BASE_URL ?>/product/checkName?name=${encodeURIComponent(name)}`);
                const dataName = await resName.json();
                if (dataName.exists) {
                    setFieldError(nameInput, `Sản phẩm tên '${name}' đã tồn tại trong hệ thống!`);
                    hasError = true;
                }
            } catch (err) { console.error(err); }
        }

        // 4. Kiểm tra Giá bán
        if (priceStr === '' || isNaN(price) || price <= 0) {
            setFieldError(priceInput, "Giá bán phải lớn hơn 0!");
            hasError = true;
        }

        // 5. Kiểm tra Giá cost
        if (costStr !== '' && !isNaN(cost) && cost < 0) {
            setFieldError(costInput, "Giá cost không được là số âm!");
            hasError = true;
        }

        // 6. Kiểm tra Giá bán vs Giá cost
        if (!isNaN(price) && !isNaN(cost) && price > 0 && cost >= price) {
            setFieldError(priceInput, "Giá bán phải lớn hơn giá cost!");
            hasError = true;
        }

        // 7. Kiểm tra Tồn kho
        if (stockStr === '' || isNaN(stock) || stock < 0) {
            setFieldError(stockInput, "Số lượng tồn kho không được là số âm!");
            hasError = true;
        }

        // Nếu có bất kỳ lỗi nào -> Hiện thông báo chung và giữ nguyên dữ liệu
        if (hasError) {
            showAlert('danger', 'Vui lòng kiểm tra và sửa lại các thông tin bị lỗi bên dưới!');
            return;
        }

        // Nếu tất cả hợp lệ -> Submit form
        form.submit();
    });
});
</script>

<?php require_once APP_PATH . '/Views/layouts/footer.php'; ?>
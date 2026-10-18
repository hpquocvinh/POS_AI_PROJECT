<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Thêm Sản phẩm Mới</h2>
        <a href="?controller=product&action=index" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="card shadow border-0 mb-4">
        <div class="card-body p-4">
            <!-- Lưu ý cực kỳ quan trọng: form có upload file phải có enctype="multipart/form-data" -->
            <form action="?controller=product&action=store" method="POST" enctype="multipart/form-data">
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Mã SKU (Sản phẩm) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="product_code" placeholder="VD: CF003" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="product_name" placeholder="Nhập tên sản phẩm..." required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Danh mục <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->category_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="price" placeholder="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Giá vốn (VNĐ)</label>
                        <input type="number" class="form-control" name="cost_price" placeholder="0" value="0">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Số lượng Tồn kho <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="stock_quantity" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="1">Đang bán</option>
                            <option value="0">Ngừng bán</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Hình ảnh sản phẩm</label>
                        <input type="file" class="form-control mb-2" name="image" id="imageInput" accept="image/*">
                        <!-- Khung hiển thị ảnh preview -->
                        <img id="imagePreview" src="#" alt="Preview" class="img-thumbnail d-none" style="max-height: 150px;">
                    </div>
                </div>

                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save"></i> Lưu Sản phẩm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Code JS để hiển thị ảnh xem trước khi người dùng chọn file
document.getElementById('imageInput').addEventListener('change', function(event) {
    const [file] = event.target.files;
    if (file) {
        const preview = document.getElementById('imagePreview');
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
    }
});
</script>
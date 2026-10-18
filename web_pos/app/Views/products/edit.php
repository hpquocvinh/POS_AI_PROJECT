<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Sửa Sản phẩm</h2>
        <a href="?controller=product&action=index" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="card shadow border-0 mb-4">
        <div class="card-body p-4">
            <form action="?controller=product&action=update" method="POST" enctype="multipart/form-data">
                <!-- ID và Tên ảnh cũ ẩn đi để Controller bắt được -->
                <input type="hidden" name="id" value="<?= $product->id ?>">
                <input type="hidden" name="current_image" value="<?= htmlspecialchars($product->image) ?>">
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Mã SKU <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="product_code" value="<?= htmlspecialchars($product->product_code) ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="product_name" value="<?= htmlspecialchars($product->product_name) ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Danh mục <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" required>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat->id ?>" <?= $cat->id == $product->category_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat->category_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="price" value="<?= $product->price ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Giá vốn (VNĐ)</label>
                        <input type="number" class="form-control" name="cost_price" value="<?= $product->cost_price ?>">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Số lượng Tồn kho <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="stock_quantity" value="<?= $product->stock_quantity ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="1" <?= $product->status == 1 ? 'selected' : '' ?>>Đang bán</option>
                            <option value="0" <?= $product->status == 0 ? 'selected' : '' ?>>Ngừng bán</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Hình ảnh sản phẩm</label>
                        <input type="file" class="form-control mb-2" name="image" id="imageInput" accept="image/*">
                        
                        <!-- Hiển thị ảnh hiện tại -->
                        <div class="mt-2">
                            <small class="text-muted d-block mb-1">Ảnh hiện tại:</small>
                            <img id="imagePreview" src="public/uploads/<?= htmlspecialchars($product->image) ?>" 
                                 alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                    </div>
                </div>

                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-warning px-4 text-dark fw-bold">
                        <i class="bi bi-save"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Cập nhật ảnh preview nếu người dùng chọn ảnh mới
document.getElementById('imageInput').addEventListener('change', function(event) {
    const [file] = event.target.files;
    if (file) {
        document.getElementById('imagePreview').src = URL.createObjectURL(file);
    }
});
</script>
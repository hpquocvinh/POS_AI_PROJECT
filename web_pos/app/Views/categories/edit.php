<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Sửa Danh mục</h2>
        <a href="?controller=category&action=index" class="btn btn-secondary shadow-sm">Quay lại</a>
    </div>

    <div class="card shadow border-0 mb-4 col-md-8 mx-auto">
        <div class="card-body p-4">
            <form action="?controller=category&action=update" method="POST">
                <input type="hidden" name="id" value="<?= $category->id ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Tên danh mục <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="category_name" value="<?= htmlspecialchars($category->category_name) ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Mô tả</label>
                    <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($category->description) ?></textarea>
                </div>
                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-warning px-4 text-dark fw-bold">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>
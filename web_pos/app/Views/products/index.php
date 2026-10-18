<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Quản lý Sản phẩm</h2>
        <a href="?controller=product&action=create" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle"></i> Thêm Sản phẩm
        </a>
    </div>

    <div class="card shadow mb-4 border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Mã SP</th>
                            <th>Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Giá bán</th>
                            <th>Giá vốn</th>
                            <th>Tồn kho</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($products)): ?>
                            <?php foreach($products as $p): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p->product_code) ?></strong></td>
                                <td>
                                    <!-- Giả định ảnh lưu trong thư mục public/uploads/ -->
                                    <img src="public/uploads/<?= htmlspecialchars($p->image) ?>" 
                                         alt="<?= htmlspecialchars($p->product_name) ?>" 
                                         class="img-thumbnail rounded" 
                                         style="width: 60px; height: 60px; object-fit: cover;">
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($p->product_name) ?></td>
                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($p->category_name) ?></span></td>
                                <td class="text-danger fw-bold"><?= number_format($p->price, 0, ',', '.') ?>đ</td>
                                <td class="text-muted"><?= number_format($p->cost_price, 0, ',', '.') ?>đ</td>
                                <td>
                                    <!-- Cảnh báo nếu tồn kho thấp -->
                                    <span class="badge <?= $p->stock_quantity < 10 ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                        <?= $p->stock_quantity ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($p->status == 1): ?>
                                        <span class="badge bg-success">Đang bán</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Ngừng bán</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?controller=product&action=edit&id=<?= $p->id ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square"></i> Sửa
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $p->id ?>)">
                                        <i class="bi bi-trash"></i> Xóa
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Chưa có sản phẩm nào.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Chống tay nhanh xóa nhầm
function confirmDelete(id) {
    if(confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?')) {
        window.location.href = '?controller=product&action=delete&id=' + id;
    }
}
</script>
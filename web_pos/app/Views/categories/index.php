<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Quản lý Danh mục</h2>
        <a href="?controller=category&action=create" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle"></i> Thêm Danh mục
        </a>
    </div>

    <div class="card shadow mb-4 border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">ID</th>
                            <th width="30%">Tên danh mục</th>
                            <th width="45%">Mô tả</th>
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($categories)): ?>
                            <?php foreach($categories as $c): ?>
                            <tr>
                                <td><strong>#<?= $c->id ?></strong></td>
                                <td><span class="badge bg-info text-dark fs-6"><?= htmlspecialchars($c->category_name) ?></span></td>
                                <td class="text-muted"><?= htmlspecialchars($c->description) ?></td>
                                <td>
                                    <a href="?controller=category&action=edit&id=<?= $c->id ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteCat(<?= $c->id ?>)">Xóa</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-4">Chưa có danh mục nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
function confirmDeleteCat(id) {
    if(confirm('Bạn có chắc chắn muốn xóa danh mục này không? Các sản phẩm thuộc danh mục này có thể bị lỗi hiển thị!')) {
        window.location.href = '?controller=category&action=delete&id=' + id;
    }
}
</script>
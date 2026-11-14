<?php require_once APP_PATH . '/Views/layouts/header.php'; ?>

<?php 
    $product    = $product ?? ($data['product'] ?? null);
    $cats       = $categories ?? ($data['categories'] ?? []);
    $ingList    = $ingredients ?? ($data['ingredients'] ?? []);
    $recipeList = $recipes ?? ($data['recipes'] ?? []);
?>

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

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Chỉnh Sửa Sản Phẩm</h4>
        <p class="text-muted small mb-0">Cập nhật thông tin, nhóm tùy chọn POS và định mức công thức nguyên liệu</p>
    </div>
    <a href="<?= BASE_URL ?>/product" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Quay lại
    </a>
</div>

<?php if ($product): ?>
<?php
    $isManageStock = isset($product->manage_stock) && $product->manage_stock == 1; 
    $cleanPrice    = min(100000000, round((float)$product->price));
    $cleanCost     = min(100000000, round((float)$product->cost_price));
    $minStockVal   = isset($product->min_stock) ? (int)$product->min_stock : 5;
    $expiryVal     = $product->expiry_date ?? '';
    $modRaw        = isset($product->modifiers_config) ? (string)$product->modifiers_config : 'size,sugar,ice,topping';
    $activeMods    = array_filter(array_map('trim', explode(',', $modRaw)));

    $initialBom = [];
    foreach ($recipeList as $r) {
        $conv = max(1, (float)($r->conversion_rate ?? 1));
        $initialBom[] = [
            'ingredient_id'     => (int)$r->ingredient_id,
            'ingredient_name'   => $r->ingredient_name,
            'unit'              => $r->unit,
            'unit_cost'         => (float)$r->cost_price / $conv,
            'quantity_required' => (float)$r->quantity_required
        ];
    }
?>
<form id="editProductForm" action="<?= BASE_URL ?>/product/update" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="id" value="<?= $product->id ?>">
    <input type="hidden" name="recipe_items_json" id="recipeItemsJson" value='<?= htmlspecialchars(json_encode($initialBom), ENT_QUOTES, 'UTF-8') ?>'>

    <div class="row g-4">
        <!-- Cột trái -->
        <div class="col-lg-8">
            <div class="card card-custom p-4 mb-4">
                <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-circle-info me-2"></i>Thông Tin Sản Phẩm</h5>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mã SKU (Sản phẩm) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="product_code" id="productCodeInput" class="form-control" value="<?= htmlspecialchars($product->product_code) ?>" required>
                            <button type="button" id="btnAutoSku" class="btn btn-outline-primary" title="Tự động tạo mã SKU mới">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Tạo mã
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($cats as $cat): ?>
                                <option value="<?= $cat->id ?>" <?= ($cat->id == $product->category_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat->category_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" id="productNameInput" class="form-control" value="<?= htmlspecialchars($product->product_name) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="any" min="1" max="100000000" name="price" class="form-control" value="<?= $cleanPrice ?>" required>
                            <span class="input-group-text">đ</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Giá Cost (VNĐ)
                            <span id="autoCostBadge" class="badge bg-info-subtle text-info ms-1 <?= $isManageStock ? 'd-none' : '' ?>">Tự tính theo BOM</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="any" min="0" max="100000000" name="cost_price" id="costPriceInput" class="form-control" value="<?= $cleanCost ?>">
                            <span class="input-group-text">đ</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Quản lý tồn kho trực tiếp</label>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="toggleManageStock" name="manage_stock" value="1" <?= $isManageStock ? 'checked' : '' ?>>
                                <label class="form-check-label small text-muted" for="toggleManageStock" id="manageStockLabel">
                                    <?= $isManageStock ? 'Bật (Đếm chiếc/hộp)' : 'Tắt (Pha chế)' ?>
                                </label>
                            </div>
                        </div>
                        <div id="stockQuantityWrapper" class="<?= $isManageStock ? '' : 'd-none' ?>">
                            <input type="number" step="1" min="0" max="1000000" name="stock_quantity" class="form-control" value="<?= (int)$product->stock_quantity ?>">
                        </div>
                        <div id="recipeStockHint" class="form-control bg-light text-muted small <?= $isManageStock ? 'd-none' : '' ?>">
                            <i class="fa-solid fa-flask text-info me-1"></i> Tự động trừ nguyên liệu khi bán
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Trạng thái kinh doanh</label>
                        <select name="status" class="form-select">
                            <option value="1" <?= ($product->status == 1) ? 'selected' : '' ?>>Đang bán</option>
                            <option value="0" <?= ($product->status == 0) ? 'selected' : '' ?>>Ngừng bán</option>
                        </select>
                    </div>

                    <!-- Khối Định mức tồn tối thiểu & Hạn sử dụng (Hàng bán sẵn) -->
                    <div class="col-12 <?= $isManageStock ? '' : 'd-none' ?>" id="directStockExtraWrapper">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="small fw-bold text-primary mb-2">
                                <i class="fa-solid fa-bell me-1"></i>Cảnh báo tồn kho & Hạn sử dụng hàng bán sẵn
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-danger mb-1">Định mức tồn tối thiểu (Cảnh báo đỏ)</label>
                                    <input type="number" step="1" min="0" name="min_stock" class="form-control form-control-sm" value="<?= $minStockVal ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold mb-1">Hạn sử dụng (Tùy chọn)</label>
                                    <input type="date" name="expiry_date" class="form-control form-control-sm" value="<?= htmlspecialchars($expiryVal) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KHỐI NHÓM TÙY CHỌN POS (MODIFIERS) -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-dark">
                                    <i class="fa-solid fa-sliders text-primary me-1"></i>Nhóm tùy chọn hiển thị khi bán trên POS (Modifiers)
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary">Bỏ chọn hết nếu bán trực tiếp không cần popup</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <div class="form-check bg-white border rounded-2 p-2 ps-4">
                                        <input class="form-check-input" type="checkbox" name="modifiers[]" value="size" id="modSize" <?= in_array('size', $activeMods) ? 'checked' : '' ?>>
                                        <label class="form-check-label small fw-semibold" for="modSize">Chọn Size (M / L)</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check bg-white border rounded-2 p-2 ps-4">
                                        <input class="form-check-input" type="checkbox" name="modifiers[]" value="sugar" id="modSugar" <?= in_array('sugar', $activeMods) ? 'checked' : '' ?>>
                                        <label class="form-check-label small fw-semibold" for="modSugar">Mức Đường</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check bg-white border rounded-2 p-2 ps-4">
                                        <input class="form-check-input" type="checkbox" name="modifiers[]" value="ice" id="modIce" <?= in_array('ice', $activeMods) ? 'checked' : '' ?>>
                                        <label class="form-check-label small fw-semibold" for="modIce">Mức Đá</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check bg-white border rounded-2 p-2 ps-4">
                                        <input class="form-check-input" type="checkbox" name="modifiers[]" value="topping" id="modTopping" <?= in_array('topping', $activeMods) ? 'checked' : '' ?>>
                                        <label class="form-check-label small fw-semibold" for="modTopping">Topping đi kèm</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KHỐI CÔNG THỨC ĐỊNH LƯỢNG (BOM) -->
            <div class="card card-custom p-4 mb-4 border-info <?= $isManageStock ? 'd-none' : '' ?>" id="bomBuilderCard">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-info"><i class="fa-solid fa-flask me-2"></i>Công Thức Định Lượng Nguyên Liệu (BOM)</h5>
                        <small class="text-muted">Định mức 1 phần chuẩn (Size M) — Tự động cộng tổng điền vào ô <strong>Giá Cost</strong></small>
                    </div>
                    <span class="badge bg-info text-white px-3 py-2 rounded-pill fs-6" id="bomTotalCostBadge">
                        Tổng Cost: <?= number_format($cleanCost) ?> đ
                    </span>
                </div>

                <div class="row g-2 align-items-end bg-light p-3 rounded-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small mb-1">Chọn nguyên liệu trong kho</label>
                        <select id="bomIngredientSelect" class="form-select">
                            <option value="">-- Chọn nguyên liệu --</option>
                            <?php foreach ($ingList as $ing): ?>
                                <?php
                                    $conv = max(1, (float)($ing->conversion_rate ?? 1));
                                    $unitCost = (float)$ing->cost_price / $conv;
                                ?>
                                <option value="<?= $ing->id ?>"
                                        data-name="<?= htmlspecialchars($ing->ingredient_name) ?>"
                                        data-unit="<?= htmlspecialchars($ing->unit) ?>"
                                        data-unit-cost="<?= $unitCost ?>">
                                    <?= htmlspecialchars($ing->ingredient_code) ?> - <?= htmlspecialchars($ing->ingredient_name) ?> (<?= rtrim(rtrim(number_format($unitCost, 2, '.', ','), '0'), '.') ?> đ/<?= htmlspecialchars($ing->unit) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small mb-1">Định lượng (<span id="bomUnitLabel">--</span>)</label>
                        <input type="number" step="any" min="0.001" id="bomQtyInput" class="form-control" placeholder="VD: 20, 100...">
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btnAddBomRow" class="btn btn-info text-white w-100 fw-bold">
                            <i class="fa-solid fa-plus"></i> Thêm
                        </button>
                    </div>
                </div>

                <div class="table-responsive border rounded-3">
                    <table class="table table-sm align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Nguyên liệu</th>
                                <th>Định lượng Size M</th>
                                <th>Đơn giá quy đổi</th>
                                <th>Thành tiền Cost</th>
                                <th class="text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="bomTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cột phải -->
        <div class="col-lg-4">
            <div class="card card-custom p-4 mb-4 text-center">
                <h5 class="fw-bold mb-3 text-primary text-start"><i class="fa-solid fa-image me-2"></i>Hình Ảnh</h5>
                
                <div class="mb-3">
                    <?php 
                        $imgName = !empty($product->image) ? $product->image : 'default.jpg';
                        $imgSrc = (strpos(BASE_URL, '/public') !== false) ? (BASE_URL . '/uploads/' . $imgName) : (BASE_URL . '/public/uploads/' . $imgName);
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const imageInput = document.getElementById('imageInput');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imgPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    const btnAutoSku = document.getElementById('btnAutoSku');
    const productCodeInput = document.getElementById('productCodeInput');
    const productNameInput = document.getElementById('productNameInput');
    if (btnAutoSku) {
        btnAutoSku.addEventListener('click', async function() {
            const nameVal = productNameInput.value.trim();
            const res = await fetch(`<?= BASE_URL ?>/product/generateSku?name=${encodeURIComponent(nameVal)}`).then(r => r.json());
            if (res.sku) productCodeInput.value = res.sku;
        });
    }

    const toggleManageStock = document.getElementById('toggleManageStock');
    const stockQuantityWrapper = document.getElementById('stockQuantityWrapper');
    const directStockExtraWrapper = document.getElementById('directStockExtraWrapper');
    const recipeStockHint = document.getElementById('recipeStockHint');
    const manageStockLabel = document.getElementById('manageStockLabel');
    const bomBuilderCard = document.getElementById('bomBuilderCard');
    const autoCostBadge = document.getElementById('autoCostBadge');

    if (toggleManageStock) {
        toggleManageStock.addEventListener('change', function() {
            if (this.checked) {
                stockQuantityWrapper.classList.remove('d-none');
                directStockExtraWrapper.classList.remove('d-none');
                recipeStockHint.classList.add('d-none');
                bomBuilderCard.classList.add('d-none');
                autoCostBadge.classList.add('d-none');
                manageStockLabel.textContent = 'Bật (Đếm chiếc/hộp)';
            } else {
                stockQuantityWrapper.classList.add('d-none');
                directStockExtraWrapper.classList.add('d-none');
                recipeStockHint.classList.remove('d-none');
                bomBuilderCard.classList.remove('d-none');
                autoCostBadge.classList.remove('d-none');
                manageStockLabel.textContent = 'Tắt (Pha chế)';
            }
        });
    }

    // BOM Builder
    const bomSelect = document.getElementById('bomIngredientSelect');
    const bomUnitLabel = document.getElementById('bomUnitLabel');
    const bomQtyInput = document.getElementById('bomQtyInput');
    const btnAddBomRow = document.getElementById('btnAddBomRow');
    const bomTableBody = document.getElementById('bomTableBody');
    const bomTotalCostBadge = document.getElementById('bomTotalCostBadge');
    const costPriceInput = document.getElementById('costPriceInput');
    const recipeItemsJson = document.getElementById('recipeItemsJson');

    let bomItems = [];
    try {
        bomItems = JSON.parse(recipeItemsJson.value || '[]');
    } catch (e) { bomItems = []; }

    if (bomSelect) {
        bomSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            bomUnitLabel.textContent = (opt && opt.dataset.unit) ? opt.dataset.unit : '--';
        });
    }

    function renderBomTable(updateCostField = false) {
        recipeItemsJson.value = JSON.stringify(bomItems.map(i => ({
            ingredient_id: i.ingredient_id,
            quantity_required: i.quantity_required
        })));

        if (bomItems.length === 0) {
            bomTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3 small">Chưa có nguyên liệu nào trong công thức.</td></tr>';
            bomTotalCostBadge.textContent = 'Tổng Cost: 0 đ';
            return;
        }

        let totalCost = 0;
        bomTableBody.innerHTML = bomItems.map((item, idx) => {
            const lineCost = item.quantity_required * item.unit_cost;
            totalCost += lineCost;
            return `
                <tr>
                    <td class="ps-3 fw-semibold">${item.ingredient_name}</td>
                    <td>
                        <div class="input-group input-group-sm" style="width: 135px;">
                            <input type="number" step="any" min="0.001" class="form-control" value="${item.quantity_required}"
                                   onchange="updateBomQty(${idx}, this.value)">
                            <span class="input-group-text">${item.unit}</span>
                        </div>
                    </td>
                    <td class="text-muted small">${Math.round(item.unit_cost).toLocaleString('vi-VN')} đ/${item.unit}</td>
                    <td class="fw-semibold text-primary">${Math.round(lineCost).toLocaleString('vi-VN')} đ</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm text-danger border-0" onclick="removeBomRow(${idx})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        const roundedTotal = Math.round(totalCost);
        bomTotalCostBadge.textContent = `Tổng Cost: ${roundedTotal.toLocaleString('vi-VN')} đ`;
        if (updateCostField) {
            costPriceInput.value = roundedTotal;
        }
    }

    window.updateBomQty = function(idx, val) {
        const num = parseFloat(val);
        if (!isNaN(num) && num > 0) {
            bomItems[idx].quantity_required = num;
        }
        renderBomTable(true);
    };

    window.removeBomRow = function(idx) {
        bomItems.splice(idx, 1);
        renderBomTable(true);
    };

    if (btnAddBomRow) {
        btnAddBomRow.addEventListener('click', function() {
            const opt = bomSelect.options[bomSelect.selectedIndex];
            const ingId = parseInt(bomSelect.value || 0);
            const qty = parseFloat(bomQtyInput.value || 0);

            if (!ingId || isNaN(qty) || qty <= 0) return;

            const existing = bomItems.find(i => i.ingredient_id === ingId);
            if (existing) {
                existing.quantity_required += qty;
            } else {
                bomItems.push({
                    ingredient_id: ingId,
                    ingredient_name: opt.dataset.name,
                    unit: opt.dataset.unit,
                    unit_cost: parseFloat(opt.dataset.unitCost || 0),
                    quantity_required: qty
                });
            }
            bomQtyInput.value = '';
            renderBomTable(true);
        });
    }

    renderBomTable(false);
});
</script>

<?php require_once APP_PATH . '/Views/layouts/footer.php'; ?>
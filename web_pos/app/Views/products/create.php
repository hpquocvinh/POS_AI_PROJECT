<?php require_once APP_PATH . '/Views/layouts/header.php'; ?>

<?php
    $cats = $categories ?? ($data['categories'] ?? []);
    $ingList = $ingredients ?? ($data['ingredients'] ?? []);
?>

<div id="alertContainer"></div>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Thêm Sản Phẩm Mới</h4>
        <p class="text-muted small mb-0">Tạo sản phẩm mới vào danh mục thực đơn, cấu hình tùy chọn POS & định mức BOM</p>
    </div>
    <a href="<?= BASE_URL ?>/product" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Quay lại
    </a>
</div>

<form id="createProductForm" action="<?= BASE_URL ?>/product/store" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="         ipe_items_json" id="recipeItemsJson" value="[]">

    <div class="row g-4">
        <!-- Cột trái -->
        <div class="col-lg-8">
            <div class="card card-custom p-4 mb-4">
                <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-circle-info me-2"></i>Thông Tin Cơ Bản</h5>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mã SKU (Sản phẩm) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="product_code" id="productCodeInput" class="form-control" placeholder="VD: TXKW01" required>
                            <button type="button" id="btnAutoSku" class="btn btn-outline-primary" title="Tự động tạo mã SKU không trùng lặp">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Tạo mã
                            </button>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                        <select name="category_id" id="categorySelect" class="form-select" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($cats as $cat): ?>
                                <option value="<?= $cat->id ?>" data-cat-name="<?= strtolower(htmlspecialchars($cat->category_name)) ?>">
                                    <?= htmlspecialchars($cat->category_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Vui lòng chọn danh mục!</div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" id="productNameInput" class="form-control" placeholder="VD: Trà Xanh Kiwi" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="any" min="1" max="100000000" name="price" class="form-control" placeholder="0" required>
                            <span class="input-group-text">đ</span>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Giá Cost (VNĐ)
                            <span id="autoCostBadge" class="badge bg-info-subtle text-info ms-1">Tự tính theo BOM</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="any" min="0" max="100000000" name="cost_price" id="costPriceInput" class="form-control" placeholder="0" value="0">
                            <span class="input-group-text">đ</span>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Quản lý tồn kho trực tiếp</label>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="toggleManageStock" name="manage_stock" value="1">
                                <label class="form-check-label small text-muted" for="toggleManageStock" id="manageStockLabel">Tắt (Pha chế)</label>
                            </div>
                        </div>
                        <div id="stockQuantityWrapper" class="d-none">
                            <input type="number" step="1" min="0" max="1000000" name="stock_quantity" id="stockQuantityInput" class="form-control" placeholder="Số lượng tồn ban đầu (cái/hộp)..." value="0">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div id="recipeStockHint" class="form-control bg-light text-muted small">
                            <i class="fa-solid fa-flask text-info me-1"></i> Tự động trừ nguyên liệu khi bán
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Trạng thái kinh doanh</label>
                        <select name="status" class="form-select">
                            <option value="1" selected>Đang bán</option>
                            <option value="0">Ngừng bán</option>
                        </select>
                    </div>

                    <!-- Khối Định mức tồn tối thiểu & Hạn sử dụng (Chỉ hiện khi Bật quản lý tồn trực tiếp) -->
                    <div class="col-12 d-none" id="directStockExtraWrapper">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="small fw-bold text-primary mb-2">
                                <i class="fa-solid fa-bell me-1"></i>Cảnh báo tồn kho & Hạn sử dụng hàng bán sẵn
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-danger mb-1">Định mức tồn tối thiểu (Cảnh báo đỏ)</label>
                                    <input type="number" step="1" min="0" name="min_stock" class="form-control form-control-sm" value="5" placeholder="VD: 5">
                                    <div class="form-text" style="font-size: 11px;">Dưới mức này hệ thống sẽ tô đỏ báo cần nhập thêm</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold mb-1">Hạn sử dụng (Tùy chọn)</label>
                                    <input type="date" name="expiry_date" class="form-control form-control-sm">
                                    <div class="form-text" style="font-size: 11px;">Áp dụng cho bánh ngọt, đồ tươi đóng hộp</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KHỐI NHÓM TÙY CHỌN POS (MODIFIERS: SIZE / ĐƯỜNG / ĐÁ / TOPPING) -->
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
                                        <input class="form-check-input mod-checkbox" type="checkbox" name="modifiers[]" value="size" id="modSize" checked>
                                        <label class="form-check-label small fw-semibold" for="modSize">Chọn Size (M / L)</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check bg-white border rounded-2 p-2 ps-4">
                                        <input class="form-check-input mod-checkbox" type="checkbox" name="modifiers[]" value="sugar" id="modSugar" checked>
                                        <label class="form-check-label small fw-semibold" for="modSugar">Mức Đường</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check bg-white border rounded-2 p-2 ps-4">
                                        <input class="form-check-input mod-checkbox" type="checkbox" name="modifiers[]" value="ice" id="modIce" checked>
                                        <label class="form-check-label small fw-semibold" for="modIce">Mức Đá</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check bg-white border rounded-2 p-2 ps-4">
                                        <input class="form-check-input mod-checkbox" type="checkbox" name="modifiers[]" value="topping" id="modTopping" checked>
                                        <label class="form-check-label small fw-semibold" for="modTopping">Topping đi kèm</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KHỐI THIẾT LẬP CÔNG THỨC ĐỊNH LƯỢNG (BOM) -->
            <div class="card card-custom p-4 mb-4 border-info" id="bomBuilderCard">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-info"><i class="fa-solid fa-flask me-2"></i>Thiết Lập Định Mức Nguyên Liệu (BOM)</h5>
                        <small class="text-muted">Chọn nguyên liệu cho 1 phần chuẩn (Size M) — Tự động cộng tổng điền vào ô <strong>Giá Cost</strong></small>
                    </div>
                    <span class="badge bg-info text-white px-3 py-2 rounded-pill fs-6" id="bomTotalCostBadge">
                        Tổng Cost: 0 đ
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
                        <tbody id="bomTableBody">
                            <tr><td colspan="5" class="text-center text-muted py-3 small">Chưa chọn nguyên liệu nào. Bạn có thể thiết lập ngay tại đây hoặc cài sau.</td></tr>
                        </tbody>
                    </table>
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

    // Tự động gợi ý bật/tắt Modifiers khi chọn Danh mục (VD: Bánh ngọt/Topping tự bỏ tick)
    const categorySelect = document.getElementById('categorySelect');
    const modCheckboxes = document.querySelectorAll('.mod-checkbox');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const catName = opt ? (opt.dataset.catName || '') : '';
            const isFoodOrTopping = catName.includes('bánh') || catName.includes('topping') || catName.includes('ăn vặt');
            modCheckboxes.forEach(cb => {
                cb.checked = !isFoodOrTopping;
            });
        });
    }

    // 1. Tự động sinh mã SKU
    const btnAutoSku = document.getElementById('btnAutoSku');
    const productCodeInput = document.getElementById('productCodeInput');
    const productNameInput = document.getElementById('productNameInput');

    if (btnAutoSku) {
        btnAutoSku.addEventListener('click', async function() {
            const nameVal = productNameInput.value.trim();
            try {
                const res = await fetch(`<?= BASE_URL ?>/product/generateSku?name=${encodeURIComponent(nameVal)}`);
                const data = await res.json();
                if (data.sku) {
                    productCodeInput.value = data.sku;
                    productCodeInput.classList.remove('is-invalid');
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    // 2. Bật / tắt ô nhập tồn kho & Khung BOM theo công tắc
    const toggleManageStock = document.getElementById('toggleManageStock');
    const stockQuantityWrapper = document.getElementById('stockQuantityWrapper');
    const directStockExtraWrapper = document.getElementById('directStockExtraWrapper');
    const recipeStockHint = document.getElementById('recipeStockHint');
    const manageStockLabel = document.getElementById('manageStockLabel');
    const bomBuilderCard = document.getElementById('bomBuilderCard');
    const autoCostBadge = document.getElementById('autoCostBadge');

    function syncStockModeUI() {
        if (toggleManageStock.checked) {
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
    }

    if (toggleManageStock) {
        toggleManageStock.addEventListener('change', syncStockModeUI);
        syncStockModeUI();
    }

    // 3. Khung Công Thức BOM & Tự động tính Giá Cost
    const bomSelect = document.getElementById('bomIngredientSelect');
    const bomUnitLabel = document.getElementById('bomUnitLabel');
    const bomQtyInput = document.getElementById('bomQtyInput');
    const btnAddBomRow = document.getElementById('btnAddBomRow');
    const bomTableBody = document.getElementById('bomTableBody');
    const bomTotalCostBadge = document.getElementById('bomTotalCostBadge');
    const costPriceInput = document.getElementById('costPriceInput');
    const recipeItemsJson = document.getElementById('recipeItemsJson');

    let bomItems = [];

    if (bomSelect) {
        bomSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            bomUnitLabel.textContent = (opt && opt.dataset.unit) ? opt.dataset.unit : '--';
        });
    }

    function renderBomTable() {
        recipeItemsJson.value = JSON.stringify(bomItems.map(i => ({
            ingredient_id: i.ingredient_id,
            quantity_required: i.quantity_required
        })));

        if (bomItems.length === 0) {
            bomTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3 small">Chưa chọn nguyên liệu nào. Bạn có thể thiết lập ngay tại đây hoặc cài sau.</td></tr>';
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
        costPriceInput.value = roundedTotal;
    }

    window.updateBomQty = function(idx, val) {
        const num = parseFloat(val);
        if (!isNaN(num) && num > 0) {
            bomItems[idx].quantity_required = num;
        }
        renderBomTable();
    };

    window.removeBomRow = function(idx) {
        bomItems.splice(idx, 1);
        renderBomTable();
    };

    if (btnAddBomRow) {
        btnAddBomRow.addEventListener('click', function() {
            const opt = bomSelect.options[bomSelect.selectedIndex];
            const ingId = parseInt(bomSelect.value || 0);
            const qty = parseFloat(bomQtyInput.value || 0);

            if (!ingId) {
                showAlert('danger', 'Vui lòng chọn một nguyên liệu trong kho!');
                return;
            }
            if (isNaN(qty) || qty <= 0) {
                showAlert('danger', 'Định lượng nguyên liệu phải lớn hơn 0!');
                bomQtyInput.focus();
                return;
            }

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
            renderBomTable();
        });
    }

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

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => clearFieldError(input));

        const codeInput = form.querySelector('input[name="product_code"]');
        const nameInput = form.querySelector('input[name="product_name"]');
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

        if (!category) {
            setFieldError(categorySelect, "Vui lòng chọn danh mục!");
            hasError = true;
        }

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

        if (priceStr === '' || isNaN(price) || price <= 0 || price > 100000000) {
            setFieldError(priceInput, "Giá bán phải từ 1đ đến 100,000,000đ!");
            hasError = true;
        }

        if (costStr !== '' && !isNaN(cost) && (cost < 0 || cost > 100000000)) {
            setFieldError(costInput, "Giá cost phải từ 0đ đến 100,000,000đ!");
            hasError = true;
        }

        if (!isNaN(price) && !isNaN(cost) && price > 0 && cost >= price) {
            setFieldError(priceInput, "Giá bán phải lớn hơn giá cost!");
            hasError = true;
        }

        if (toggleManageStock && toggleManageStock.checked) {
            if (stockStr === '' || isNaN(stock) || stock < 0 || stock > 1000000) {
                setFieldError(stockInput, "Số lượng tồn kho phải từ 0 đến 1,000,000!");
                hasError = true;
            }
        }

        if (hasError) {
            showAlert('danger', 'Vui lòng kiểm tra và sửa lại các thông tin bị lỗi bên dưới!');
            return;
        }

        form.submit();
    });
});
</script>

<?php require_once APP_PATH . '/Views/layouts/footer.php'; ?>
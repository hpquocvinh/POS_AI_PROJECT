<?php require_once APP_PATH . '/Views/layouts/header.php'; ?>

<?php
    $productList = $products ?? ($data['products'] ?? []);
    $ingredientList = $ingredients ?? ($data['ingredients'] ?? []);
    $ingCategories = $ingredientCategories ?? ($data['ingredientCategories'] ?? []);
    $lowCount = $lowStockCount ?? ($data['lowStockCount'] ?? 0);
    $currentTab = $activeTab ?? ($data['activeTab'] ?? 'products');
    $recipeMap = $recipeCounts ?? ($data['recipeCounts'] ?? []);
    $unitOptions = [
        'gam'  => 'gam (g)',
        'kg'   => 'kg',
        'ml'   => 'ml',
        'lít'  => 'lít',
        'cái'  => 'cái / chiếc',
        'hộp'  => 'hộp / gói',
        'chai' => 'chai / lon'
    ];
    $todayStr = date('Y-m-d');
    $soonStr  = date('Y-m-d', strtotime('+7 days'));
?>

<!-- Notification Alert -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4 flash-alert" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 flash-alert" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Header & Action Buttons -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Quản Lý Sản Phẩm & Kho</h4>
        <p class="text-muted small mb-0">Quản lý thực đơn bán hàng, cảnh báo FEFO, nhập kho, kiểm kê & thẻ kho nguyên liệu</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/product/create" id="btnAddProductTop" class="btn btn-primary rounded-pill px-4 fw-bold <?= $currentTab === 'ingredients' ? 'd-none' : '' ?>">
            <i class="fa-solid fa-plus me-2"></i>Thêm Sản Phẩm Mới
        </a>
        <button type="button" id="btnAddIngredientTop" class="btn btn-success rounded-pill px-4 fw-bold <?= $currentTab === 'ingredients' ? '' : 'd-none' ?>" data-bs-toggle="modal" data-bs-target="#addIngredientModal">
            <i class="fa-solid fa-plus me-2"></i>Thêm Nguyên Liệu Mới
        </button>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-pills mb-4 gap-2" id="warehouseTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 fw-semibold <?= $currentTab !== 'ingredients' ? 'active' : 'bg-white text-dark border' ?>" 
                id="products-tab" data-bs-toggle="tab" data-bs-target="#products-pane" type="button" role="tab">
            <i class="fa-solid fa-mug-hot me-2"></i>Sản Phẩm Bán 
            <span class="badge bg-light text-dark ms-1"><?= count($productList) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 fw-semibold <?= $currentTab === 'ingredients' ? 'active' : 'bg-white text-dark border' ?>" 
                id="ingredients-tab" data-bs-toggle="tab" data-bs-target="#ingredients-pane" type="button" role="tab">
            <i class="fa-solid fa-boxes-stacked me-2"></i>Kho Nguyên Liệu 
            <span class="badge bg-light text-dark ms-1"><?= count($ingredientList) ?></span>
            <?php if ($lowCount > 0): ?>
                <span class="badge bg-danger ms-1" title="Nguyên liệu sắp hết hoặc đã hết"><?= $lowCount ?> cảnh báo</span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content" id="warehouseTabsContent">
    <!-- ==================== TAB 1: SẢN PHẨM BÁN ==================== -->
    <div class="tab-pane fade <?= $currentTab !== 'ingredients' ? 'show active' : '' ?>" id="products-pane" role="tabpanel">
        <!-- Search & Filter Bar -->
        <div class="card card-custom p-3 mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Tìm theo Mã SKU hoặc Tên sản phẩm...">
                    </div>
                </div>

                <div class="col-md-3">
                    <select id="categoryFilter" class="form-select">
                        <option value="">-- Tất cả Danh mục --</option>
                        <?php 
                            $categories = [];
                            foreach ($productList as $p) {
                                if (!empty($p->category_name) && !in_array($p->category_name, $categories)) {
                                    $categories[] = $p->category_name;
                                }
                            }
                            foreach ($categories as $catName):
                        ?>
                            <option value="<?= htmlspecialchars($catName) ?>"><?= htmlspecialchars($catName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select id="statusFilter" class="form-select">
                        <option value="">-- Trạng thái --</option>
                        <option value="1">Đang bán</option>
                        <option value="0">Ngừng bán</option>
                    </select>
                </div>

                <div class="col-md-2 text-end">
                    <button type="button" id="btnResetFilter" class="btn btn-outline-secondary w-100 rounded-pill">
                        <i class="fa-solid fa-rotate-left me-1"></i> Xóa bộ lọc
                    </button>
                </div>
            </div>
        </div>

        <!-- Product Table Card -->
        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small">Hiển thị <strong id="resultCount" class="text-dark"><?= count($productList) ?></strong> sản phẩm</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="productTable">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th>Mã SP</th>
                            <th>Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th class="text-center">Danh mục</th>
                            <th>Giá bán</th>
                            <th>Giá Cost</th>
                            <th class="text-center">Tồn kho & HSD</th>
                            <th class="text-center">Công thức (BOM)</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($productList)): ?>
                            <?php foreach ($productList as $item): ?>
                                <?php
                                    $isDirectStock = isset($item->manage_stock) && $item->manage_stock == 1;
                                    $pMinStock     = isset($item->min_stock) ? (int)$item->min_stock : 5;
                                    $pExpiry       = $item->expiry_date ?? null;
                                    $isProdLow     = $isDirectStock && ((float)$item->stock_quantity <= $pMinStock);
                                    $isProdExpired = $isDirectStock && !empty($pExpiry) && ($pExpiry < $todayStr);
                                    $isProdSoon    = $isDirectStock && !empty($pExpiry) && !$isProdExpired && ($pExpiry <= $soonStr);
                                ?>
                                <tr class="product-row <?= ($isProdLow || $isProdExpired) ? 'table-warning' : '' ?>" 
                                    data-code="<?= strtolower(htmlspecialchars($item->product_code)) ?>" 
                                    data-name="<?= strtolower(htmlspecialchars($item->product_name)) ?>"
                                    data-category="<?= htmlspecialchars($item->category_name ?? '') ?>"
                                    data-status="<?= $item->status ?>">
                                    <td class="text-nowrap"><span class="fw-bold text-dark"><?= htmlspecialchars($item->product_code) ?></span></td>
                                    <td>
                                        <?php 
                                            $imgName = !empty($item->image) ? $item->image : 'default.jpg';
                                            if (strpos(BASE_URL, '/public') !== false) {
                                                $imgSrc = BASE_URL . '/uploads/' . $imgName;
                                            } else {
                                                $imgSrc = BASE_URL . '/public/uploads/' . $imgName;
                                            }
                                        ?>
                                        <img src="<?= $imgSrc ?>" 
                                             onerror="this.onerror=null; this.src='https://via.placeholder.com/45?text=No+Img';"
                                             alt="Thumb" class="rounded" style="width: 45px; height: 45px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <div class="fw-bold product-title"><?= htmlspecialchars($item->product_name) ?></div>
                                        <?php if (!empty($item->modifiers_config)): ?>
                                            <small class="text-muted" style="font-size: 11px;">POS: <?= htmlspecialchars($item->modifiers_config) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted" style="font-size: 11px;">Bán trực tiếp</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($item->category_name ?? 'Chưa phân loại') ?></span>
                                    </td>
                                    <td class="fw-bold text-primary text-nowrap"><?= number_format($item->price) ?> đ</td>
                                    <td class="text-muted text-nowrap"><?= number_format($item->cost_price) ?> đ</td>
                                    <td class="text-center text-nowrap">
                                        <?php if ($isDirectStock): ?>
                                            <div>
                                                <?php if ($isProdLow): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold" title="Dưới định mức tối thiểu (<?= $pMinStock ?>)">
                                                        <i class="fa-solid fa-triangle-exclamation me-1"></i><?= (float)$item->stock_quantity ?> (Min: <?= $pMinStock ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success fw-bold"><?= (float)$item->stock_quantity ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($pExpiry)): ?>
                                                <div class="mt-1">
                                                    <?php if ($isProdExpired): ?>
                                                        <span class="badge bg-danger" style="font-size: 10px;">Hết HSD: <?= date('d/m/Y', strtotime($pExpiry)) ?></span>
                                                    <?php elseif ($isProdSoon): ?>
                                                        <span class="badge bg-warning text-dark" style="font-size: 10px;">Cận HSD: <?= date('d/m/Y', strtotime($pExpiry)) ?></span>
                                                    <?php else: ?>
                                                        <small class="text-muted" style="font-size: 11px;">HSD: <?= date('d/m/Y', strtotime($pExpiry)) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle fw-semibold">
                                                <i class="fa-solid fa-flask me-1"></i>Theo nguyên liệu
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <?php if (!$isDirectStock): ?>
                                            <?php $rCount = $recipeMap[$item->id] ?? 0; ?>
                                            <button type="button"
                                                    class="btn btn-sm rounded-pill px-3 fw-semibold btn-recipe-trigger"
                                                    style="<?= $rCount > 0 
                                                        ? 'background-color: #0d6efd; color: #ffffff; border: 1px solid #0d6efd; box-shadow: 0 2px 4px rgba(13,110,253,0.2);' 
                                                        : 'background-color: #fff3cd; color: #856404; border: 1px dashed #f59e0b;' ?>"
                                                    data-id="<?= $item->id ?>"
                                                    data-name="<?= htmlspecialchars($item->product_name) ?>"
                                                    title="Cài đặt định lượng nguyên liệu & tự tính Cost">
                                                <?php if ($rCount > 0): ?>
                                                    <i class="fa-solid fa-circle-check me-1"></i>Công thức (<?= $rCount ?>)
                                                <?php else: ?>
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>Chưa có CT
                                                <?php endif; ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">Bán trực tiếp</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <?php if ($item->status == 1): ?>
                                            <span class="badge bg-success">Đang bán</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Ngừng bán</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap pe-2">
                                        <div class="d-inline-flex align-items-center justify-content-end gap-1">
                                            <a href="<?= BASE_URL ?>/product/edit?id=<?= $item->id ?>" class="btn btn-sm btn-outline-warning rounded-pill" title="Chỉnh sửa">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger rounded-pill btn-delete-trigger" 
                                                    data-id="<?= $item->id ?>"
                                                    data-name="<?= htmlspecialchars($item->product_name) ?>"
                                                    title="Xóa">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="noDataRow">
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="fa-solid fa-box-open fa-3x"></i></div>
                                    <p class="mb-0">Chưa có sản phẩm nào trong cơ sở dữ liệu.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <tr id="notFoundRow" style="display: none;">
                            <td colspan="10" class="text-center py-5">
                                <div class="text-muted mb-2"><i class="fa-solid fa-magnifying-glass fa-3x"></i></div>
                                <p class="mb-0 text-muted">Không tìm thấy sản phẩm nào phù hợp với bộ lọc!</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 2: KHO NGUYÊN LIỆU ==================== -->
    <div class="tab-pane fade <?= $currentTab === 'ingredients' ? 'show active' : '' ?>" id="ingredients-pane" role="tabpanel">
        <!-- Thanh Pill Lọc Nhanh Nhóm Nguyên Liệu (Giống trang Danh mục) -->
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3" id="ingCategoryPills">
            <button type="button" class="ing-cat-pill active" data-cat="">
                Tất cả <span class="badge bg-light text-dark ms-1"><?= count($ingredientList) ?></span>
            </button>
            <?php foreach ($ingCategories as $icat): ?>
                <?php
                    $cnt = 0;
                    foreach ($ingredientList as $ingItem) {
                        if (($ingItem->category_name ?? '') === $icat->category_name) $cnt++;
                    }
                ?>
                <button type="button" class="ing-cat-pill" data-cat="<?= htmlspecialchars($icat->category_name) ?>">
                    <?= htmlspecialchars($icat->category_name) ?> <span class="badge bg-light text-dark ms-1"><?= $cnt ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        <!-- Filter Bar Nguyên Liệu -->
        <div class="card card-custom p-3 mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="ingSearchInput" class="form-control border-start-0 ps-0" placeholder="Tìm theo Mã NL hoặc Tên nguyên liệu...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="ingCategoryFilter" class="form-select">
                        <option value="">-- Tất cả Nhóm nguyên liệu --</option>
                        <?php foreach ($ingCategories as $icat): ?>
                            <option value="<?= htmlspecialchars($icat->category_name) ?>"><?= htmlspecialchars($icat->category_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="ingStockFilter" class="form-select">
                        <option value="">-- Tình trạng Kho & HSD --</option>
                        <option value="danger">Hết hàng / Quá HSD</option>
                        <option value="warning">Sắp hết / Cận HSD (FEFO)</option>
                        <option value="safe">An toàn (Đủ hàng & Còn hạn)</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" id="btnResetIngFilter" class="btn btn-outline-secondary w-100 rounded-pill">
                        <i class="fa-solid fa-rotate-left me-1"></i> Xóa bộ lọc
                    </button>
                </div>
            </div>
        </div>

        <!-- Bảng Kho Nguyên Liệu -->
        <?php
            $kpiDangerCount = 0;
            $kpiWarningCount = 0;
            $kpiSafeCount = 0;
            foreach ($ingredientList as $ingKpi) {
                $_isOut = ($ingKpi->stock_quantity <= 0);
                $_isLow = (!$_isOut && $ingKpi->stock_quantity <= $ingKpi->min_stock);
                $_exp   = $ingKpi->nearest_expiry_date ?? null;
                $_expOver = !empty($_exp) && ($_exp < $todayStr);
                $_expSoon = !empty($_exp) && !$_expOver && ($_exp <= $soonStr);

                if ($_isOut || $_expOver) {
                    $kpiDangerCount++;
                } elseif ($_isLow || $_expSoon) {
                    $kpiWarningCount++;
                } else {
                    $kpiSafeCount++;
                }
            }
        ?>
        <div class="card card-custom p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <span class="text-muted small">Hiển thị <strong id="ingResultCount" class="text-dark"><?= count($ingredientList) ?></strong> nguyên liệu trong kho</span>
                <div class="d-flex flex-wrap align-items-center gap-2 small">
                    <span class="badge bg-danger-subtle text-danger">Hết hàng / Quá HSD (<?= $kpiDangerCount ?>)</span>
                    <span class="badge bg-warning-subtle text-warning">Sắp hết / Cận HSD (FEFO) (<?= $kpiWarningCount ?>)</span>
                    <span class="badge bg-success-subtle text-success">An toàn (<?= $kpiSafeCount ?>)</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="ingredientTable">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th>Mã NL</th>
                            <th style="min-width: 180px; max-width: 230px;">Tên nguyên liệu</th>
                            <th class="text-center">Nhóm</th>
                            <th>Tồn kho</th>
                            <th>Báo động</th>
                            <th>Giá nhập</th>
                            <th class="text-center">HSD (FEFO)</th>
                            <th class="text-center">Tình trạng</th>
                            <th class="text-center">Thao tác kho</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ingredientList)): ?>
                            <?php foreach ($ingredientList as $ing): ?>
                                <?php
                                    $isOut = ($ing->stock_quantity <= 0);
                                    $isLow = (!$isOut && $ing->stock_quantity <= $ing->min_stock);
                                    $stockStatusAttr = ($isOut || $isLow) ? 'low' : 'ok';
                                    $formattedStock = rtrim(rtrim(number_format($ing->stock_quantity, 2, '.', ','), '0'), '.');
                                    $pUnit = $ing->purchase_unit ?? '';
                                    $convRate = (float)($ing->conversion_rate ?? 1);
                                    $hasConv = (!empty($pUnit) && $convRate > 1);
                                    $convertedQty = $hasConv ? rtrim(rtrim(number_format($ing->stock_quantity / $convRate, 2, '.', ','), '0'), '.') : '';

                                    $nearExp = $ing->nearest_expiry_date ?? null;
                                    $isExpOver = !empty($nearExp) && ($nearExp < $todayStr);
                                    $isExpSoon = !empty($nearExp) && !$isExpOver && ($nearExp <= $soonStr);

                                    $kpiGroup = ($isOut || $isExpOver) ? 'danger' : (($isLow || $isExpSoon) ? 'warning' : 'safe');
                                ?>
                                <tr class="ingredient-row <?= ($isOut || $isExpOver) ? 'table-danger' : (($isLow || $isExpSoon) ? 'table-warning' : '') ?>"
                                    data-code="<?= strtolower(htmlspecialchars($ing->ingredient_code)) ?>"
                                    data-name="<?= strtolower(htmlspecialchars($ing->ingredient_name)) ?>"
                                    data-category="<?= htmlspecialchars($ing->category_name ?? '') ?>"
                                    data-stock-status="<?= $stockStatusAttr ?>"
                                    data-kpi="<?= $kpiGroup ?>">
                                    <td class="text-nowrap"><span class="fw-bold text-dark"><?= htmlspecialchars($ing->ingredient_code) ?></span></td>
                                    <td style="min-width: 180px; max-width: 230px;">
                                        <div class="fw-bold text-wrap"><?= htmlspecialchars($ing->ingredient_name) ?></div>
                                        <?php if ($hasConv): ?>
                                            <small class="text-info"><i class="fa-solid fa-right-left me-1"></i>1 <?= htmlspecialchars($pUnit) ?> = <?= rtrim(rtrim(number_format($convRate, 2, '.', ','), '0'), '.') ?> <?= htmlspecialchars($ing->unit) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($ing->category_name ?? 'Chưa phân nhóm') ?></span>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="fw-bold <?= $isOut ? 'text-danger' : ($isLow ? 'text-warning-emphasis' : 'text-success') ?>">
                                            <?= $formattedStock ?>
                                        </span>
                                        <small class="text-muted"><?= htmlspecialchars($ing->unit) ?></small>
                                        <?php if ($hasConv): ?>
                                            <div class="small text-muted">(~ <?= $convertedQty ?> <?= htmlspecialchars($pUnit) ?>)</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small text-nowrap">
                                        &le; <?= rtrim(rtrim(number_format($ing->min_stock, 2, '.', ','), '0'), '.') ?> <?= htmlspecialchars($ing->unit) ?>
                                    </td>
                                    <td class="text-muted text-nowrap">
                                        <?= number_format($ing->cost_price) ?> đ
                                        <?php if ($hasConv): ?>
                                            <small>/ <?= htmlspecialchars($pUnit) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <?php if (!empty($nearExp)): ?>
                                            <?php if ($isExpOver): ?>
                                                <span class="badge bg-danger"><i class="fa-solid fa-skull-crossbones me-1"></i>Quá hạn: <?= date('d/m/Y', strtotime($nearExp)) ?></span>
                                            <?php elseif ($isExpSoon): ?>
                                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half me-1"></i>FEFO: <?= date('d/m/Y', strtotime($nearExp)) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small"><?= date('d/m/Y', strtotime($nearExp)) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">--</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <?php if ($isOut): ?>
                                            <span class="badge bg-danger">Hết hàng</span>
                                        <?php elseif ($isLow): ?>
                                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i>Sắp hết</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Còn hàng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <div class="d-inline-flex align-items-center gap-1 flex-nowrap">
                                            <button type="button" 
                                                    class="btn btn-sm btn-success px-2 btn-import-trigger"
                                                    data-id="<?= $ing->id ?>"
                                                    data-name="<?= htmlspecialchars($ing->ingredient_name) ?>"
                                                    data-unit="<?= htmlspecialchars($ing->unit) ?>"
                                                    data-purchase-unit="<?= htmlspecialchars($pUnit) ?>"
                                                    data-conv-rate="<?= $convRate ?>"
                                                    data-stock="<?= $formattedStock ?>"
                                                    data-cost="<?= (float)$ing->cost_price ?>"
                                                    data-expiry="<?= htmlspecialchars($nearExp ?? '') ?>"
                                                    title="Nhập thêm hàng vào kho">
                                                Nhập
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info text-white px-2 btn-audit-trigger"
                                                    data-id="<?= $ing->id ?>"
                                                    data-name="<?= htmlspecialchars($ing->ingredient_name) ?>"
                                                    data-unit="<?= htmlspecialchars($ing->unit) ?>"
                                                    data-purchase-unit="<?= htmlspecialchars($pUnit) ?>"
                                                    data-conv-rate="<?= $convRate ?>"
                                                    data-stock="<?= (float)$ing->stock_quantity ?>"
                                                    data-stock-label="<?= $formattedStock ?>"
                                                    title="Kiểm kê thực tế & ghi nhận chênh lệch">
                                                Kiểm kê
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary rounded-circle btn-logs-trigger"
                                                    style="width: 30px; height: 30px; padding: 0;"
                                                    data-id="<?= $ing->id ?>"
                                                    data-name="<?= htmlspecialchars($ing->ingredient_name) ?>"
                                                    data-unit="<?= htmlspecialchars($ing->unit) ?>"
                                                    title="Thẻ kho: Xem lịch sử xuất nhập">
                                                <i class="fa-solid fa-clock-rotate-left"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-warning rounded-circle btn-edit-ing-trigger"
                                                    style="width: 30px; height: 30px; padding: 0;"
                                                    data-id="<?= $ing->id ?>"
                                                    data-code="<?= htmlspecialchars($ing->ingredient_code) ?>"
                                                    data-name="<?= htmlspecialchars($ing->ingredient_name) ?>"
                                                    data-category="<?= $ing->category_id ?>"
                                                    data-unit="<?= htmlspecialchars($ing->unit) ?>"
                                                    data-purchase-unit="<?= htmlspecialchars($pUnit) ?>"
                                                    data-conv-rate="<?= $convRate ?>"
                                                    data-min="<?= (float)$ing->min_stock ?>"
                                                    data-cost="<?= (float)$ing->cost_price ?>"
                                                    data-expiry="<?= htmlspecialchars($nearExp ?? '') ?>"
                                                    title="Sửa thông tin nguyên liệu">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger rounded-circle btn-delete-ing-trigger"
                                                    style="width: 30px; height: 30px; padding: 0;"
                                                    data-id="<?= $ing->id ?>"
                                                    data-name="<?= htmlspecialchars($ing->ingredient_name) ?>"
                                                    title="Xóa nguyên liệu">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="fa-solid fa-boxes-stacked fa-3x"></i></div>
                                    <p class="mb-0">Chưa có nguyên liệu nào trong kho.</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <tr id="ingNotFoundRow" style="display: none;">
                            <td colspan="9" class="text-center py-5">
                                <div class="text-muted mb-2"><i class="fa-solid fa-magnifying-glass fa-3x"></i></div>
                                <p class="mb-0 text-muted">Không tìm thấy nguyên liệu nào phù hợp với bộ lọc!</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- ==================== MODAL 1: THÊM NGUYÊN LIỆU MỚI ==================== -->
<div class="modal fade" id="addIngredientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="addIngredientForm" action="<?= BASE_URL ?>/product/storeIngredient" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 15px;" novalidate>
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-plus-circle text-success me-2"></i>Thêm Nguyên Liệu Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="addIngModalAlert" class="alert alert-danger py-2 px-3 small d-none" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    <span id="addIngModalAlertText"></span>
                </div>

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small">Mã nguyên liệu <span class="text-danger">*</span></label>
                        <input type="text" name="ingredient_code" id="addIngCode" class="form-control" placeholder="VD: NL001" required>
                        <div class="invalid-feedback" id="addIngCodeFeedback">Vui lòng nhập mã nguyên liệu!</div>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-semibold small">Nhóm nguyên liệu <span class="text-danger">*</span></label>
                        <select name="category_id" id="addIngCategory" class="form-select" required>
                            <option value="">-- Chọn nhóm --</option>
                            <?php foreach ($ingCategories as $icat): ?>
                                <option value="<?= $icat->id ?>"><?= htmlspecialchars($icat->category_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Vui lòng chọn nhóm nguyên liệu!</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Tên nguyên liệu <span class="text-danger">*</span></label>
                        <input type="text" name="ingredient_name" id="addIngName" class="form-control" placeholder="VD: Hạt cà phê Arabica, Kem béo..." required>
                        <div class="invalid-feedback" id="addIngNameFeedback">Vui lòng nhập tên nguyên liệu!</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Đơn vị pha chế chuẩn <span class="text-danger">*</span></label>
                        <select name="unit" id="addIngUnit" class="form-select" required>
                            <?php foreach ($unitOptions as $uVal => $uLabel): ?>
                                <option value="<?= $uVal ?>"><?= $uLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text" style="font-size: 11px;">Dùng để trừ kho khi bán & cài công thức</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Giá nhập (VNĐ)</label>
                        <input type="number" step="any" min="0" name="cost_price" id="addIngCost" class="form-control" value="0">
                        <div class="invalid-feedback">Giá nhập không được là số âm!</div>
                    </div>

                    <!-- Khối Quy đổi đơn vị nhập kho -->
                    <div class="col-12">
                        <div class="p-2.5 bg-light rounded-3 border">
                            <div class="small fw-semibold text-primary mb-2">
                                <i class="fa-solid fa-right-left me-1"></i>Quy đổi đơn vị nhập kho (Tùy chọn)
                            </div>
                            <div class="row g-2 align-items-center">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Đơn vị nhập lớn</label>
                                    <select name="purchase_unit" id="addIngPurchaseUnit" class="form-select form-select-sm">
                                        <option value="">-- Không quy đổi --</option>
                                        <option value="hộp">hộp</option>
                                        <option value="túi">túi / bịch</option>
                                        <option value="chai">chai / lon</option>
                                        <option value="thùng">thùng</option>
                                        <option value="kg">kg</option>
                                        <option value="lít">lít</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">1 ĐV lớn bằng bao nhiêu <span id="addConvBaseUnitText">gam</span>?</label>
                                    <input type="number" step="any" min="1" name="conversion_rate" id="addIngConvRate" class="form-control form-control-sm" value="1" placeholder="VD: 1000">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Tồn đầu (<span id="addStockUnitLabel">gam</span>)</label>
                        <input type="number" step="any" min="0" name="stock_quantity" id="addIngStock" class="form-control" value="0">
                        <div class="invalid-feedback">Không được âm!</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-danger">Mức báo (<span id="addMinUnitLabel">gam</span>) <span class="text-danger">*</span></label>
                        <input type="number" step="any" min="0" name="min_stock" id="addIngMin" class="form-control" placeholder="500" required>
                        <div class="invalid-feedback">Nhập mức &ge; 0!</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Hạn sử dụng</label>
                        <input type="date" name="nearest_expiry_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" id="btnSubmitAddIng" class="btn btn-success rounded-pill px-4 fw-bold">Lưu Nguyên Liệu</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== MODAL 2: NHẬP THÊM KHO (CÓ Ô HSD & GHI CHÚ) ==================== -->
<div class="modal fade" id="importIngredientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= BASE_URL ?>/product/importIngredient" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <input type="hidden" name="id" id="importIngId">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-truck-ramp-box text-success me-2"></i>Nhập Thêm Nguyên Liệu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded-3 mb-3">
                    <div class="fw-bold text-dark fs-6" id="importIngName"></div>
                    <div class="small text-muted">Tồn kho hiện tại: <strong id="importIngCurrentStock" class="text-primary"></strong></div>
                    <div class="small text-info mt-1 d-none" id="importConvHint"></div>
                </div>
                <div class="mb-3" id="importUnitModeWrapper" style="display: none;">
                    <label class="form-label fw-semibold small">Đơn vị nhập kho lần này</label>
                    <select name="import_unit_mode" id="importUnitMode" class="form-select">
                        <option value="purchase" id="optImportPurchase"></option>
                        <option value="base" id="optImportBase"></option>
                    </select>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Số lượng nhập thêm (<span id="importIngUnit"></span>) <span class="text-danger">*</span></label>
                        <input type="number" step="any" min="0.01" name="add_quantity" class="form-control form-control-lg fw-bold text-success" placeholder="VD: 10" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Hạn sử dụng lô nhập (FEFO)</label>
                        <input type="date" name="expiry_date" id="importIngExpiry" class="form-control form-control-lg">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Cập nhật giá nhập mới (để trống nếu giữ giá cũ)</label>
                    <input type="number" step="any" min="0" name="cost_price" id="importIngCost" class="form-control">
                </div>
                <div>
                    <label class="form-label fw-semibold small">Ghi chú phiếu nhập (Nhà cung cấp, số hóa đơn...)</label>
                    <input type="text" name="import_note" class="form-control" placeholder="VD: Nhập NCC Trung Nguyên - HĐ #102">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Xác Nhận Nhập Kho</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== MODAL 3: KIỂM KÊ TỒN KHO (TÍNH CHÊNH LỆCH + QUY ĐỔI + LÝ DO) ==================== -->
<div class="modal fade" id="auditIngredientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="auditIngredientForm" action="<?= BASE_URL ?>/product/auditIngredient" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <input type="hidden" name="id" id="auditIngId">
            <input type="hidden" name="actual_quantity" id="auditIngActualBase">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-sliders text-info me-2"></i>Kiểm Kê Tồn Kho Thực Tế</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded-3 mb-3">
                    <div class="fw-bold text-dark fs-6" id="auditIngName"></div>
                    <div class="small text-muted mt-1">Số lượng ghi nhận trên sổ sách: <strong id="auditIngCurrentStock" class="text-dark"></strong></div>
                </div>

                <div class="mb-3" id="auditUnitModeWrapper" style="display: none;">
                    <label class="form-label fw-semibold small">Đơn vị cân đong thực tế</label>
                    <select id="auditUnitMode" class="form-select">
                        <option value="base" id="optAuditBase"></option>
                        <option value="purchase" id="optAuditPurchase"></option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Số lượng thực tế trong kho (<span id="auditIngUnit"></span>) <span class="text-danger">*</span></label>
                    <input type="number" step="any" min="0" id="auditIngDisplayInput" class="form-control form-control-lg fw-bold text-info" required>
                    <div class="form-text small text-primary d-none" id="auditConvertedHint"></div>
                </div>

                <div class="p-3 rounded-3 border mb-3 d-flex justify-content-between align-items-center bg-light">
                    <span class="small fw-semibold text-secondary">Chênh lệch (Thực tế - Sổ sách):</span>
                    <span class="fw-bold fs-6 text-muted" id="auditDiffDisplay">0</span>
                </div>

                <div>
                    <label class="form-label fw-semibold small">Lý do điều chỉnh kiểm kê <span id="auditReasonReqStar" class="text-danger d-none">*</span></label>
                    <input type="text" name="audit_reason" id="auditReasonInput" class="form-control" placeholder="VD: Hao hụt pha chế, đổ vỡ, kiểm kê cuối ca...">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-info text-white rounded-pill px-4 fw-bold">Cập Nhật Kiểm Kê & Ghi Thẻ Kho</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== MODAL 4: SỬA THÔNG TIN NGUYÊN LIỆU ==================== -->
<div class="modal fade" id="editIngredientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= BASE_URL ?>/product/updateIngredient" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <input type="hidden" name="id" id="editIngId">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-pen-to-square text-warning me-2"></i>Chỉnh Sửa Nguyên Liệu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small">Mã nguyên liệu <span class="text-danger">*</span></label>
                        <input type="text" name="ingredient_code" id="editIngCode" class="form-control" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-semibold small">Nhóm nguyên liệu <span class="text-danger">*</span></label>
                        <select name="category_id" id="editIngCategory" class="form-select" required>
                            <?php foreach ($ingCategories as $icat): ?>
                                <option value="<?= $icat->id ?>"><?= htmlspecialchars($icat->category_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Tên nguyên liệu <span class="text-danger">*</span></label>
                        <input type="text" name="ingredient_name" id="editIngName" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Đơn vị tính <span class="text-danger">*</span></label>
                        <select name="unit" id="editIngUnit" class="form-select" required>
                            <?php foreach ($unitOptions as $uVal => $uLabel): ?>
                                <option value="<?= $uVal ?>"><?= $uLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Giá nhập</label>
                        <input type="number" step="any" min="0" name="cost_price" id="editIngCost" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-danger">Mức báo động</label>
                        <input type="number" step="any" min="0" name="min_stock" id="editIngMin" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Hạn sử dụng gần nhất (FEFO)</label>
                        <input type="date" name="nearest_expiry_date" id="editIngExpiry" class="form-control">
                    </div>

                    <!-- Quy đổi đơn vị khi chỉnh sửa -->
                    <div class="col-12">
                        <div class="p-2.5 bg-light rounded-3 border">
                            <div class="small fw-semibold text-primary mb-2">
                                <i class="fa-solid fa-right-left me-1"></i>Quy đổi đơn vị nhập kho (Tùy chọn)
                            </div>
                            <div class="row g-2 align-items-center">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Đơn vị nhập lớn</label>
                                    <select name="purchase_unit" id="editIngPurchaseUnit" class="form-select form-select-sm">
                                        <option value="">-- Không quy đổi --</option>
                                        <option value="hộp">hộp</option>
                                        <option value="túi">túi / bịch</option>
                                        <option value="chai">chai / lon</option>
                                        <option value="thùng">thùng</option>
                                        <option value="kg">kg</option>
                                        <option value="lít">lít</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">1 ĐV lớn bằng bao nhiêu <span id="editConvBaseUnitText">gam</span>?</label>
                                    <input type="number" step="any" min="1" name="conversion_rate" id="editIngConvRate" class="form-control form-control-sm" value="1">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Lưu Thay Đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== MODAL 5: THẺ KHO (LỊCH SỬ BIẾN ĐỘNG NGUYÊN LIỆU) ==================== -->
<div class="modal fade" id="ingredientLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i>Thẻ Kho Nguyên Liệu</h5>
                    <small class="text-muted">Nguyên liệu: <strong id="logsIngredientName" class="text-dark"></strong> (ĐVT: <span id="logsIngredientUnit"></span>)</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive border rounded-3" style="max-height: 380px;">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3 py-2">Thời gian</th>
                                <th class="py-2">Loại giao dịch</th>
                                <th class="py-2">Biến động</th>
                                <th class="py-2">Tồn sau GD</th>
                                <th class="py-2">Người thực hiện</th>
                                <th class="py-2 pe-3">Ghi chú / Lý do</th>
                            </tr>
                        </thead>
                        <tbody id="ingredientLogsBody">
                            <tr><td colspan="6" class="text-center text-muted py-4">Đang tải lịch sử thẻ kho...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (Dùng chung cho Sản phẩm & Nguyên liệu) -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-body text-center p-4">
                <div class="text-danger mb-3">
                    <i class="fa-solid fa-circle-exclamation fa-3x"></i>
                </div>
                <h5 class="fw-bold mb-2" id="deleteModalTitle">Xác Nhận Xóa</h5>
                <p class="text-muted mb-4">Bạn có chắc chắn muốn xóa <strong id="deleteProductName" class="text-dark"></strong> không?<br><small class="text-danger">Hành động này sẽ xóa dữ liệu vĩnh viễn và không thể khôi phục!</small></p>
                
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Hủy bỏ</button>
                    <a id="btnConfirmDelete" href="#" class="btn btn-danger rounded-pill px-4 fw-bold">Xác Nhận Xóa</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL 6: BÌNH THÍ NGHIỆM (ĐỊNH LƯỢNG BOM & TỰ TÍNH COST) ==================== -->
<div class="modal fade" id="productRecipeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-flask text-info me-2"></i>Công Thức BOM & Tự Tính Cost</h5>
                    <small class="text-muted">Món: <strong id="recipeProductName" class="text-dark"></strong> (Chuẩn Size M — Chọn Size L tự động nhân <strong>1.3x</strong>)</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="recipeProductId">
                <div id="recipeModalAlert" class="d-none"></div>

                <div class="row g-2 align-items-end bg-light p-3 rounded-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small mb-1">Chọn nguyên liệu trong kho</label>
                        <select id="recipeIngredientSelect" class="form-select">
                            <option value="">-- Chọn nguyên liệu --</option>
                            <?php foreach ($ingredientList as $ing): ?>
                                <?php
                                    $conv = max(1, (float)($ing->conversion_rate ?? 1));
                                    $unitCost = (float)$ing->cost_price / $conv;
                                ?>
                                <option value="<?= $ing->id ?>"
                                        data-unit="<?= htmlspecialchars($ing->unit) ?>"
                                        data-unit-cost="<?= $unitCost ?>"
                                        data-name="<?= htmlspecialchars($ing->ingredient_name) ?>">
                                    <?= htmlspecialchars($ing->ingredient_code) ?> - <?= htmlspecialchars($ing->ingredient_name) ?> (<?= rtrim(rtrim(number_format($unitCost, 2, '.', ','), '0'), '.') ?> đ/<?= htmlspecialchars($ing->unit) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small mb-1">Định lượng Size M (<span id="recipeSelectedUnit">--</span>)</label>
                        <input type="number" step="any" min="0.001" id="recipeQtyInput" class="form-control" placeholder="VD: 20, 150..."
                               onkeydown="if(['-','+','e','E'].includes(event.key)) event.preventDefault();">
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btnAddRecipeRow" class="btn btn-info text-white w-100 fw-bold">
                            <i class="fa-solid fa-plus"></i> Thêm
                        </button>
                    </div>
                </div>

                <div class="table-responsive border rounded-3 mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Nguyên liệu</th>
                                <th>Định lượng Size M</th>
                                <th>Đơn giá quy đổi</th>
                                <th>Thành tiền Cost</th>
                                <th class="text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="recipeTableBody">
                            <tr><td colspan="5" class="text-center text-muted py-3">Chưa có nguyên liệu nào trong công thức.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center bg-info-subtle text-info-emphasis px-3 py-2 rounded-3">
                    <span class="small fw-semibold"><i class="fa-solid fa-calculator me-1"></i>Giá Cost dự kiến (tự động cập nhật vào sản phẩm khi lưu):</span>
                    <span class="fw-bold fs-5 text-primary" id="recipeCalculatedCostDisplay">0 đ</span>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                <button type="button" id="btnSaveProductRecipe" class="btn btn-info text-white rounded-pill px-4 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Công Thức & Cập Nhật Cost
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Client-side Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const productsTab = document.getElementById('products-tab');
    const ingredientsTab = document.getElementById('ingredients-tab');
    const btnAddProductTop = document.getElementById('btnAddProductTop');
    const btnAddIngredientTop = document.getElementById('btnAddIngredientTop');

    if (productsTab && ingredientsTab) {
        productsTab.addEventListener('shown.bs.tab', function() {
            productsTab.classList.remove('bg-white', 'text-dark', 'border');
            ingredientsTab.classList.add('bg-white', 'text-dark', 'border');
            btnAddProductTop.classList.remove('d-none');
            btnAddIngredientTop.classList.add('d-none');
        });
        ingredientsTab.addEventListener('shown.bs.tab', function() {
            ingredientsTab.classList.remove('bg-white', 'text-dark', 'border');
            productsTab.classList.add('bg-white', 'text-dark', 'border');
            btnAddProductTop.classList.add('d-none');
            btnAddIngredientTop.classList.remove('d-none');
        });
    }

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

    // --- LỌC BẢNG SẢN PHẨM ---
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const btnResetFilter = document.getElementById('btnResetFilter');
    const productRows = document.querySelectorAll('.product-row');
    const notFoundRow = document.getElementById('notFoundRow');
    const resultCount = document.getElementById('resultCount');

    function filterProducts() {
        const keywordNoAccent = removeVietnameseTones(searchInput.value);
        const selectedCat = categoryFilter.value;
        const selectedStatus = statusFilter.value;
        let visibleCount = 0;

        productRows.forEach(row => {
            const codeNoAccent = removeVietnameseTones(row.getAttribute('data-code') || '');
            const nameNoAccent = removeVietnameseTones(row.getAttribute('data-name') || '');
            const cat = row.getAttribute('data-category') || '';
            const status = row.getAttribute('data-status') || '';

            const matchKeyword = (codeNoAccent.includes(keywordNoAccent) || nameNoAccent.includes(keywordNoAccent));
            const matchCategory = (selectedCat === '' || cat === selectedCat);
            const matchStatus = (selectedStatus === '' || status === selectedStatus);

            if (matchKeyword && matchCategory && matchStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (resultCount) resultCount.textContent = visibleCount;
        if (notFoundRow) notFoundRow.style.display = (visibleCount === 0 && productRows.length > 0) ? '' : 'none';
    }

    if (searchInput) searchInput.addEventListener('input', filterProducts);
    if (categoryFilter) categoryFilter.addEventListener('change', filterProducts);
    if (statusFilter) statusFilter.addEventListener('change', filterProducts);
    if (btnResetFilter) {
        btnResetFilter.addEventListener('click', function() {
            searchInput.value = '';
            categoryFilter.value = '';
            statusFilter.value = '';
            filterProducts();
        });
    }

   // --- LỌC BẢNG NGUYÊN LIỆU (TÌM KIẾM + NHÓM + TÌNH TRẠNG KHO & HSD) ---
    const ingSearchInput = document.getElementById('ingSearchInput');
    const ingCategoryFilter = document.getElementById('ingCategoryFilter');
    const ingStockFilter = document.getElementById('ingStockFilter');
    const btnResetIngFilter = document.getElementById('btnResetIngFilter');
    const ingredientRows = document.querySelectorAll('.ingredient-row');
    const ingNotFoundRow = document.getElementById('ingNotFoundRow');
    const ingResultCount = document.getElementById('ingResultCount');

    function filterIngredients() {
        const keywordNoAccent = removeVietnameseTones(ingSearchInput.value);
        const selectedCat = ingCategoryFilter.value;
        const selectedStock = ingStockFilter.value;
        let visibleCount = 0;

        ingredientRows.forEach(row => {
            const codeNoAccent = removeVietnameseTones(row.getAttribute('data-code') || '');
            const nameNoAccent = removeVietnameseTones(row.getAttribute('data-name') || '');
            const cat = row.getAttribute('data-category') || '';
            const stockStatus = row.getAttribute('data-stock-status') || '';
            const kpiGroup = row.getAttribute('data-kpi') || '';

            const matchKeyword = (codeNoAccent.includes(keywordNoAccent) || nameNoAccent.includes(keywordNoAccent));
            const matchCategory = (selectedCat === '' || cat === selectedCat);
            const matchStock = (selectedStock === '' || kpiGroup === selectedStock || stockStatus === selectedStock);

            if (matchKeyword && matchCategory && matchStock) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (ingResultCount) ingResultCount.textContent = visibleCount;
        if (ingNotFoundRow) ingNotFoundRow.style.display = (visibleCount === 0 && ingredientRows.length > 0) ? '' : 'none';
    }

    if (ingSearchInput) ingSearchInput.addEventListener('input', filterIngredients);
    if (ingCategoryFilter) {
        ingCategoryFilter.addEventListener('change', function() {
            const val = this.value;
            document.querySelectorAll('.ing-cat-pill').forEach(p => {
                p.classList.toggle('active', (p.dataset.cat || '') === val);
            });
            filterIngredients();
        });
    }
    document.querySelectorAll('.ing-cat-pill').forEach(pill => {
        pill.addEventListener('click', function() {
            document.querySelectorAll('.ing-cat-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            if (ingCategoryFilter) {
                ingCategoryFilter.value = this.dataset.cat || '';
            }
            filterIngredients();
        });
    });

    if (ingStockFilter) ingStockFilter.addEventListener('change', filterIngredients);
    if (btnResetIngFilter) {
        btnResetIngFilter.addEventListener('click', function() {
            ingSearchInput.value = '';
            ingCategoryFilter.value = '';
            ingStockFilter.value = '';
            document.querySelectorAll('.ing-cat-pill').forEach(p => p.classList.toggle('active', (p.dataset.cat || '') === ''));
            filterIngredients();
        });
    }

    // Đồng bộ nhãn đơn vị quy đổi
    const addIngUnit = document.getElementById('addIngUnit');
    const addIngPurchaseUnit = document.getElementById('addIngPurchaseUnit');
    function syncAddUnitLabels() {
        const baseU = addIngUnit ? addIngUnit.value : 'gam';
        const purU = addIngPurchaseUnit ? addIngPurchaseUnit.value : '';
        document.getElementById('addConvBaseUnitText').textContent = baseU;
        document.getElementById('addMinUnitLabel').textContent = baseU;
        document.getElementById('addStockUnitLabel').textContent = purU ? purU : baseU;
    }
    if (addIngUnit) addIngUnit.addEventListener('change', syncAddUnitLabels);
    if (addIngPurchaseUnit) addIngPurchaseUnit.addEventListener('change', syncAddUnitLabels);

    const editIngUnit = document.getElementById('editIngUnit');
    if (editIngUnit) {
        editIngUnit.addEventListener('change', function() {
            document.getElementById('editConvBaseUnitText').textContent = this.value;
        });
    }

    // --- MODAL NHẬP KHO (CÓ HSD) ---
    const importModalEl = document.getElementById('importIngredientModal');
    if (importModalEl) {
        const importModal = new bootstrap.Modal(importModalEl);
        const modeWrapper = document.getElementById('importUnitModeWrapper');
        const modeSelect = document.getElementById('importUnitMode');
        const convHint = document.getElementById('importConvHint');
        const unitSpan = document.getElementById('importIngUnit');

        document.querySelectorAll('.btn-import-trigger').forEach(btn => {
            btn.addEventListener('click', function() {
                const baseUnit = this.dataset.unit;
                const purUnit = this.dataset.purchaseUnit || '';
                const convRate = parseFloat(this.dataset.convRate || 1);

                document.getElementById('importIngId').value = this.dataset.id;
                document.getElementById('importIngName').textContent = this.dataset.name;
                document.getElementById('importIngCurrentStock').textContent = `${this.dataset.stock} ${baseUnit}`;
                document.getElementById('importIngCost').value = this.dataset.cost;
                document.getElementById('importIngExpiry').value = '';

                if (purUnit && convRate > 1) {
                    modeWrapper.style.display = '';
                    document.getElementById('optImportPurchase').textContent = `Nhập theo ${purUnit} (1 ${purUnit} = ${convRate} ${baseUnit})`;
                    document.getElementById('optImportBase').textContent = `Nhập lẻ theo ${baseUnit}`;
                    modeSelect.value = 'purchase';
                    unitSpan.textContent = purUnit;
                    convHint.textContent = `Quy đổi: 1 ${purUnit} = ${convRate} ${baseUnit}`;
                    convHint.classList.remove('d-none');

                    modeSelect.onchange = function() {
                        unitSpan.textContent = (this.value === 'purchase') ? purUnit : baseUnit;
                    };
                } else {
                    modeWrapper.style.display = 'none';
                    modeSelect.value = 'base';
                    unitSpan.textContent = baseUnit;
                    convHint.classList.add('d-none');
                }
                importModal.show();
            });
        });
    }

    // --- MODAL KIỂM KÊ TỒN KHO (HỖ TRỢ QUY ĐỔI + TỰ TÍNH CHÊNH LỆCH + BẮT LÝ DO KHI LỆCH) ---
    const auditModalEl = document.getElementById('auditIngredientModal');
    if (auditModalEl) {
        const auditModal = new bootstrap.Modal(auditModalEl);
        const displayInput = document.getElementById('auditIngDisplayInput');
        const hiddenBaseInput = document.getElementById('auditIngActualBase');
        const diffDisplay = document.getElementById('auditDiffDisplay');
        const unitModeWrapper = document.getElementById('auditUnitModeWrapper');
        const unitModeSelect = document.getElementById('auditUnitMode');
        const convertedHint = document.getElementById('auditConvertedHint');
        const reasonInput = document.getElementById('auditReasonInput');
        const reasonStar = document.getElementById('auditReasonReqStar');

        let currentSystemStock = 0;
        let currentBaseUnit = '';
        let currentPurUnit = '';
        let currentConvRate = 1;

        function updateAuditDiff() {
            const rawVal = parseFloat(displayInput.value);
            if (isNaN(rawVal) || rawVal < 0) {
                hiddenBaseInput.value = '';
                diffDisplay.textContent = '--';
                diffDisplay.className = 'fw-bold fs-6 text-muted';
                convertedHint.classList.add('d-none');
                return;
            }

            const isPurchaseMode = (unitModeSelect.value === 'purchase' && currentPurUnit && currentConvRate > 1);
            const actualBaseVal = isPurchaseMode ? Math.round(rawVal * currentConvRate * 1000) / 1000 : rawVal;
            hiddenBaseInput.value = actualBaseVal;

            if (isPurchaseMode) {
                convertedHint.textContent = `Quy đổi chuẩn kho: ${actualBaseVal.toLocaleString('vi-VN')} ${currentBaseUnit}`;
                convertedHint.classList.remove('d-none');
            } else {
                convertedHint.classList.add('d-none');
            }

            const diff = Math.round((actualBaseVal - currentSystemStock) * 100) / 100;
            if (diff === 0) {
                diffDisplay.textContent = `0 ${currentBaseUnit} (Khớp sổ)`;
                diffDisplay.className = 'fw-bold fs-6 text-success';
                reasonInput.required = false;
                reasonStar.classList.add('d-none');
            } else if (diff > 0) {
                diffDisplay.textContent = `+${diff} ${currentBaseUnit} (Thừa kho)`;
                diffDisplay.className = 'fw-bold fs-6 text-primary';
                reasonInput.required = true;
                reasonStar.classList.remove('d-none');
            } else {
                diffDisplay.textContent = `${diff} ${currentBaseUnit} (Hao hụt)`;
                diffDisplay.className = 'fw-bold fs-6 text-danger';
                reasonInput.required = true;
                reasonStar.classList.remove('d-none');
            }
        }

        displayInput.addEventListener('input', updateAuditDiff);
        unitModeSelect.addEventListener('change', function() {
            const isPur = (this.value === 'purchase' && currentPurUnit && currentConvRate > 1);
            document.getElementById('auditIngUnit').textContent = isPur ? currentPurUnit : currentBaseUnit;
            displayInput.value = isPur ? Math.round((currentSystemStock / currentConvRate) * 100) / 100 : currentSystemStock;
            updateAuditDiff();
        });

        document.querySelectorAll('.btn-audit-trigger').forEach(btn => {
            btn.addEventListener('click', function() {
                currentSystemStock = parseFloat(this.dataset.stock || 0);
                currentBaseUnit = this.dataset.unit || '';
                currentPurUnit = this.dataset.purchaseUnit || '';
                currentConvRate = parseFloat(this.dataset.convRate || 1);

                document.getElementById('auditIngId').value = this.dataset.id;
                document.getElementById('auditIngName').textContent = this.dataset.name;
                document.getElementById('auditIngCurrentStock').textContent = `${this.dataset.stockLabel || currentSystemStock} ${currentBaseUnit}`;
                document.getElementById('auditIngUnit').textContent = currentBaseUnit;

                if (currentPurUnit && currentConvRate > 1) {
                    unitModeWrapper.style.display = '';
                    document.getElementById('optAuditBase').textContent = `Kiểm kê theo đơn vị chuẩn (${currentBaseUnit})`;
                    document.getElementById('optAuditPurchase').textContent = `Kiểm kê theo ${currentPurUnit} (1 ${currentPurUnit} = ${currentConvRate} ${currentBaseUnit})`;
                    unitModeSelect.value = 'base';
                } else {
                    unitModeWrapper.style.display = 'none';
                    unitModeSelect.value = 'base';
                }

                displayInput.value = currentSystemStock;
                reasonInput.value = '';
                updateAuditDiff();
                auditModal.show();
            });
        });
    }

    // --- MODAL THẺ KHO (LỊCH SỬ BIẾN ĐỘNG) ---
    const logsModalEl = document.getElementById('ingredientLogsModal');
    if (logsModalEl) {
        const logsModal = new bootstrap.Modal(logsModalEl);
        const logsBody = document.getElementById('ingredientLogsBody');

        document.querySelectorAll('.btn-logs-trigger').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.dataset.id;
                const unit = this.dataset.unit || '';
                document.getElementById('logsIngredientName').textContent = this.dataset.name;
                document.getElementById('logsIngredientUnit').textContent = unit;
                logsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Đang tải dữ liệu thẻ kho...</td></tr>';
                logsModal.show();

                try {
                    const res = await fetch(`<?= BASE_URL ?>/product/getIngredientLogs?id=${id}`).then(r => r.json());
                    const logs = res.logs || [];
                    if (logs.length === 0) {
                        logsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Chưa có lịch sử xuất nhập kho nào cho nguyên liệu này.</td></tr>';
                        return;
                    }

                    const typeMap = {
                        'init':   '<span class="badge bg-secondary">Khởi tạo</span>',
                        'import': '<span class="badge bg-success">Nhập kho</span>',
                        'export': '<span class="badge bg-primary">Bán POS</span>',
                        'audit':  '<span class="badge bg-info text-dark">Kiểm kê</span>'
                    };

                    logsBody.innerHTML = logs.map(l => {
                        const qty = parseFloat(l.change_qty ?? l.quantity_change ?? 0);
                        const after = parseFloat(l.after_qty ?? l.stock_after ?? 0);
                        const qtyBadge = qty > 0
                            ? `<span class="fw-bold text-success">+${qty} ${unit}</span>`
                            : (qty < 0 ? `<span class="fw-bold text-danger">${qty} ${unit}</span>` : `<span class="text-muted">0 ${unit}</span>`);
                        const typeLabel = typeMap[l.action_type] || `<span class="badge bg-light text-dark border">${l.action_type}</span>`;

                        return `
                            <tr>
                                <td class="ps-3 text-muted text-nowrap">${l.created_at || ''}</td>
                                <td class="text-nowrap">${typeLabel}</td>
                                <td class="text-nowrap">${qtyBadge}</td>
                                <td class="fw-semibold text-nowrap">${after} ${unit}</td>
                                <td class="text-nowrap">${l.created_by || 'Hệ thống'}</td>
                                <td class="pe-3 text-muted">${l.note || '-'}</td>
                            </tr>
                        `;
                    }).join('');
                } catch (e) {
                    logsBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Lỗi khi tải lịch sử thẻ kho!</td></tr>';
                }
            });
        });
    }
    // --- MODAL SỬA NGUYÊN LIỆU ---
    const editIngModalEl = document.getElementById('editIngredientModal');
    if (editIngModalEl) {
        const editIngModal = new bootstrap.Modal(editIngModalEl);
        document.querySelectorAll('.btn-edit-ing-trigger').forEach(btn => {
            btn.addEventListener('click', function() {
                const unitVal = this.dataset.unit || 'gam';
                document.getElementById('editIngId').value = this.dataset.id;
                document.getElementById('editIngCode').value = this.dataset.code;
                document.getElementById('editIngName').value = this.dataset.name;
                document.getElementById('editIngCategory').value = this.dataset.category;
                document.getElementById('editIngUnit').value = unitVal;
                document.getElementById('editConvBaseUnitText').textContent = unitVal;
                document.getElementById('editIngPurchaseUnit').value = this.dataset.purchaseUnit || '';
                document.getElementById('editIngConvRate').value = this.dataset.convRate || 1;
                document.getElementById('editIngCost').value = this.dataset.cost;
                document.getElementById('editIngMin').value = this.dataset.min;
                document.getElementById('editIngExpiry').value = this.dataset.expiry || '';
                editIngModal.show();
            });
        });
    }

    // --- MODAL XÓA ---
    const deleteModalEl = document.getElementById('deleteConfirmModal');
    if (deleteModalEl) {
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        const btnConfirmDelete = document.getElementById('btnConfirmDelete');
        const deleteProductName = document.getElementById('deleteProductName');
        const deleteModalTitle = document.getElementById('deleteModalTitle');

        document.querySelectorAll('.btn-delete-trigger').forEach(button => {
            button.addEventListener('click', function() {
                deleteModalTitle.textContent = 'Xác Nhận Xóa Sản Phẩm';
                deleteProductName.textContent = `"${this.dataset.name}"`;
                btnConfirmDelete.href = `<?= BASE_URL ?>/product/delete?id=${this.dataset.id}`;
                deleteModal.show();
            });
        });

        document.querySelectorAll('.btn-delete-ing-trigger').forEach(button => {
            button.addEventListener('click', function() {
                deleteModalTitle.textContent = 'Xác Nhận Xóa Nguyên Liệu';
                deleteProductName.textContent = `"${this.dataset.name}"`;
                btnConfirmDelete.href = `<?= BASE_URL ?>/product/deleteIngredient?id=${this.dataset.id}`;
                deleteModal.show();
            });
        });
    }

    // --- VALIDATE MODAL THÊM NGUYÊN LIỆU ---
    const addIngForm = document.getElementById('addIngredientForm');
    if (addIngForm) {
        const codeInput = document.getElementById('addIngCode');
        const nameInput = document.getElementById('addIngName');
        const catSelect = document.getElementById('addIngCategory');
        const costInput = document.getElementById('addIngCost');
        const stockInput = document.getElementById('addIngStock');
        const minInput = document.getElementById('addIngMin');
        const modalAlert = document.getElementById('addIngModalAlert');
        const modalAlertText = document.getElementById('addIngModalAlertText');

        function showModalError(msg) {
            modalAlertText.textContent = msg;
            modalAlert.classList.remove('d-none');
        }
        function hideModalError() {
            modalAlert.classList.add('d-none');
        }

        addIngForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideModalError();

            const codeVal = codeInput.value.trim();
            const nameVal = nameInput.value.trim();
            const catVal = catSelect.value;
            const costVal = parseFloat(costInput.value || 0);
            const stockVal = parseFloat(stockInput.value || 0);
            const minVal = minInput.value === '' ? NaN : parseFloat(minInput.value);

            let hasError = false;
            if (!codeVal) { codeInput.classList.add('is-invalid'); hasError = true; } else { codeInput.classList.remove('is-invalid'); }
            if (!catVal) { catSelect.classList.add('is-invalid'); hasError = true; } else { catSelect.classList.remove('is-invalid'); }
            if (!nameVal) { nameInput.classList.add('is-invalid'); hasError = true; } else { nameInput.classList.remove('is-invalid'); }
            if (costVal < 0) { costInput.classList.add('is-invalid'); hasError = true; } else { costInput.classList.remove('is-invalid'); }
            if (stockVal < 0) { stockInput.classList.add('is-invalid'); hasError = true; } else { stockInput.classList.remove('is-invalid'); }
            if (isNaN(minVal) || minVal < 0) { minInput.classList.add('is-invalid'); hasError = true; } else { minInput.classList.remove('is-invalid'); }

            if (hasError) {
                showModalError('Vui lòng kiểm tra lại các ô viền đỏ bên dưới!');
                return;
            }

            const [resCode, resName] = await Promise.all([
                fetch(`<?= BASE_URL ?>/product/checkIngredientCode?code=${encodeURIComponent(codeVal)}`).then(r => r.json()),
                fetch(`<?= BASE_URL ?>/product/checkIngredientName?name=${encodeURIComponent(nameVal)}`).then(r => r.json())
            ]);

            if (resCode.exists) {
                codeInput.classList.add('is-invalid');
                document.getElementById('addIngCodeFeedback').textContent = `Mã "${codeVal}" đã tồn tại trong kho!`;
                showModalError(`Mã nguyên liệu "${codeVal}" đã bị trùng!`);
                return;
            }
            if (resName.exists) {
                nameInput.classList.add('is-invalid');
                document.getElementById('addIngNameFeedback').textContent = `Nguyên liệu "${nameVal}" đã có trong kho!`;
                showModalError(`Tên nguyên liệu "${nameVal}" đã tồn tại!`);
                return;
            }

            addIngForm.submit();
        });

        document.getElementById('addIngredientModal').addEventListener('hidden.bs.modal', function() {
            addIngForm.reset();
            syncAddUnitLabels();
            hideModalError();
            addIngForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        });
    }

    // --- MODAL BÌNH THÍ NGHIỆM (ĐỊNH LƯỢNG BOM & TỰ TÍNH COST) ---
    const recipeModalEl = document.getElementById('productRecipeModal');
    if (recipeModalEl) {
        const recipeModal = new bootstrap.Modal(recipeModalEl);
        const ingSelect = document.getElementById('recipeIngredientSelect');
        const unitLabel = document.getElementById('recipeSelectedUnit');
        const qtyInput = document.getElementById('recipeQtyInput');
        const tbody = document.getElementById('recipeTableBody');
        const alertBox = document.getElementById('recipeModalAlert');
        const costDisplay = document.getElementById('recipeCalculatedCostDisplay');
        let currentRecipeItems = [];
        let initialRecipeCount = 0;

        function showRecipeAlert(msg, type = 'danger') {
            const icon = type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation';
            alertBox.className = `alert alert-${type} py-2 px-3 small mb-3`;
            alertBox.innerHTML = `<i class="fa-solid ${icon} me-2"></i>${msg}`;
        }

        function hideRecipeAlert() {
            alertBox.className = 'd-none';
            alertBox.innerHTML = '';
        }

        ingSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            unitLabel.textContent = opt && opt.dataset.unit ? opt.dataset.unit : '--';
        });

        function renderRecipeRows() {
            if (currentRecipeItems.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Chưa có nguyên liệu nào trong công thức.</td></tr>';
                costDisplay.textContent = '0 đ';
                return;
            }
            let totalCost = 0;
            tbody.innerHTML = currentRecipeItems.map((item, idx) => {
                const lineCost = item.quantity_required * (item.unit_cost || 0);
                totalCost += lineCost;
                return `
                    <tr>
                        <td class="ps-3 fw-semibold">${item.ingredient_name}</td>
                        <td>
                            <div class="input-group input-group-sm" style="width: 130px;">
                                <input type="number" step="any" min="0.001" class="form-control"
                                       value="${item.quantity_required}"
                                       onkeydown="if(['-','+','e','E'].includes(event.key)) event.preventDefault();"
                                       onchange="updateRecipeRowQty(${idx}, this)">
                                <span class="input-group-text">${item.unit}</span>
                            </div>
                        </td>
                        <td class="text-muted small">${Math.round(item.unit_cost || 0).toLocaleString('vi-VN')} đ/${item.unit}</td>
                        <td class="fw-semibold text-primary">${Math.round(lineCost).toLocaleString('vi-VN')} đ</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm text-danger border-0" onclick="removeRecipeRow(${idx})">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            costDisplay.textContent = `${Math.round(totalCost).toLocaleString('vi-VN')} đ`;
        }

        window.updateRecipeRowQty = function(idx, inputEl) {
            const num = parseFloat(inputEl.value);
            if (isNaN(num) || num <= 0) {
                showRecipeAlert('Định lượng nguyên liệu phải lớn hơn 0!', 'danger');
                inputEl.value = currentRecipeItems[idx].quantity_required;
                return;
            }
            hideRecipeAlert();
            currentRecipeItems[idx].quantity_required = num;
            renderRecipeRows();
        };

        window.removeRecipeRow = function(idx) {
            currentRecipeItems.splice(idx, 1);
            renderRecipeRows();
        };

        document.querySelectorAll('.btn-recipe-trigger').forEach(btn => {
            btn.addEventListener('click', async function() {
                const pid = this.dataset.id;
                document.getElementById('recipeProductId').value = pid;
                document.getElementById('recipeProductName').textContent = this.dataset.name;
                hideRecipeAlert();
                ingSelect.value = '';
                unitLabel.textContent = '--';
                qtyInput.value = '';
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Đang tải công thức...</td></tr>';
                costDisplay.textContent = '0 đ';
                recipeModal.show();

                const res = await fetch(`<?= BASE_URL ?>/product/getRecipe?product_id=${pid}`).then(r => r.json());
                currentRecipeItems = (res.recipes || []).map(r => {
                    const conv = Math.max(1, parseFloat(r.conversion_rate || 1));
                    const uCost = r.unit_cost !== undefined ? parseFloat(r.unit_cost) : (parseFloat(r.cost_price || 0) / conv);
                    return {
                        ingredient_id: parseInt(r.ingredient_id),
                        ingredient_name: r.ingredient_name,
                        unit: r.unit,
                        unit_cost: uCost,
                        quantity_required: parseFloat(r.quantity_required)
                    };
                });
                initialRecipeCount = currentRecipeItems.length;
                renderRecipeRows();
            });
        });

        document.getElementById('btnAddRecipeRow').addEventListener('click', function() {
            const opt = ingSelect.options[ingSelect.selectedIndex];
            const ingId = parseInt(ingSelect.value || 0);
            const qty = parseFloat(qtyInput.value || 0);

            if (!ingId) {
                showRecipeAlert('Vui lòng chọn một nguyên liệu trong kho!', 'danger');
                return;
            }
            if (isNaN(qty) || qty <= 0) {
                showRecipeAlert('Định lượng nguyên liệu phải lớn hơn 0!', 'danger');
                qtyInput.focus();
                return;
            }

            hideRecipeAlert();
            const existing = currentRecipeItems.find(i => i.ingredient_id === ingId);
            if (existing) {
                existing.quantity_required += qty;
            } else {
                currentRecipeItems.push({
                    ingredient_id: ingId,
                    ingredient_name: opt.dataset.name,
                    unit: opt.dataset.unit,
                    unit_cost: parseFloat(opt.dataset.unitCost || 0),
                    quantity_required: qty
                });
            }
            qtyInput.value = '';
            renderRecipeRows();
        });

        document.getElementById('btnSaveProductRecipe').addEventListener('click', async function() {
            const pid = document.getElementById('recipeProductId').value;
            if (currentRecipeItems.length === 0 && initialRecipeCount === 0) {
                showRecipeAlert('Vui lòng bấm "+ Thêm" ít nhất 1 nguyên liệu vào bảng trước khi lưu!', 'warning');
                return;
            }

            const res = await fetch('<?= BASE_URL ?>/product/saveRecipe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: pid, items: currentRecipeItems })
            }).then(r => r.json());

            if (res.success) {
                showRecipeAlert(res.message || 'Đã lưu công thức & tự động cập nhật Giá Cost!', 'success');
                setTimeout(() => location.reload(), 900);
            } else {
                showRecipeAlert(res.message || 'Lỗi lưu công thức!', 'danger');
            }
        });
    }

    document.querySelectorAll('.flash-alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(function() { alert.remove(); }, 500);
        }, 3000);
    });
});
</script>

<?php require_once APP_PATH . '/Views/layouts/footer.php'; ?>v
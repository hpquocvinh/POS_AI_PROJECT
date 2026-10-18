<style>
    /* NỀN TẢNG BỐ CỤC 2 CỘT NẰM NGANG BẮT BUỘC */
    .pos-v2-grid {
        display: flex !important;
        flex-direction: row !important;
        gap: 20px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }

    /* CỘT TRÁI - BÀN & THỰC ĐƠN (65%) */
    .pos-v2-left {
        flex: 1 1 65% !important;
        width: 65% !important;
        min-width: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 16px !important;
    }

    /* CỘT PHẢI - GIỎ HÀNG SONG SONG (35%) */
    .pos-v2-right {
        flex: 0 0 35% !important;
        width: 35% !important;
        min-width: 340px !important;
    }

    /* STYLING TAB HÌNH THỨC BAN HÀNG */
    .pos-type-btn {
        border: 1.5px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        font-weight: 700;
        font-size: 13px;
        padding: 9px 20px;
        border-radius: 10px;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .pos-type-btn.active, .pos-type-btn:hover {
        background: #4f46e5;
        color: #ffffff;
        border-color: #4f46e5;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }

    /* LƯỚI CARD BÀN VÀ MÓN */
    .grid-tables {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 12px;
    }

    .grid-products {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
        gap: 12px;
    }

    .card-tb {
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        padding: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .card-tb:hover {
        border-color: #4f46e5;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .card-tb.active-table {
        border-color: #4f46e5 !important;
        background-color: #eef2ff !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2) !important;
    }

    .card-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        padding: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .card-item:hover {
        border-color: #4f46e5;
        background-color: #f8fafc;
        transform: translateY(-2px);
    }
</style>

<div class="w-100">
    <!-- TAB BÁN HÀNG CHÍNH -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            <button class="pos-type-btn active" onclick="switchOrderType('dine_in', this)">
                <i class="fa-solid fa-utensils me-2"></i>Bàn Tại Chỗ
            </button>
            <button class="pos-type-btn" onclick="switchOrderType('takeaway', this)">
                <i class="fa-solid fa-bag-shopping me-2"></i>Mang Về
            </button>
            <button class="pos-type-btn" onclick="switchOrderType('online', this)">
                <i class="fa-solid fa-motorcycle me-2"></i>Đơn App Online
            </button>
        </div>

        <div class="d-flex gap-2" id="statusLegend">
            <span class="badge bg-white text-dark border px-3 py-2 rounded-pill fw-semibold">
                <i class="fa-solid fa-circle text-success me-1" style="font-size: 8px;"></i> Bàn Trống
            </span>
            <span class="badge bg-white text-dark border px-3 py-2 rounded-pill fw-semibold">
                <i class="fa-solid fa-circle text-danger me-1" style="font-size: 8px;"></i> Có Khách
            </span>
        </div>
    </div>

    <!-- CẤU TRÚC BỐ CỤC 2 CỘT MỚI SONG SONG -->
    <div class="pos-v2-grid">
        
        <!-- CỘT TRÁI (65%): BÀN & THỰC ĐƠN -->
        <div class="pos-v2-left">
            
            <!-- KHỐI 1: BÀN TẠI CHỖ -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div id="sectionDineIn">
                    <div class="d-flex gap-2 mb-3 border-bottom pb-2">
                        <button class="btn btn-sm btn-dark rounded-pill px-3" onclick="filterArea('all')">Tất cả khu vực</button>
                        <?php if(!empty($areas)): ?>
                            <?php foreach($areas as$area): ?>
                                <button class="btn btn-sm btn-light border rounded-pill px-3" onclick="filterArea('area-<?= $area['id'] ?>')">
                                    <?= htmlspecialchars($area['area_name']) ?>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="grid-tables" style="max-height: 230px; overflow-y: auto;">
                        <?php if(!empty($tables)): ?>
                            <?php foreach($tables as$tb): ?>
                                <div class="card-tb <?= $tb['status'] == 1 ? 'border-warning' : '' ?>" id="tb-<?= $tb['id'] ?>" onclick="selectTable(<?= $tb['id'] ?>, '<?= htmlspecialchars($tb['table_name']) ?>', <?=$tb['status'] ?>)">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="fw-bold <?= $tb['status'] == 1 ? 'text-danger' : 'text-success' ?>" style="font-size: 10px;">
                                            <?= $tb['status'] == 1 ? '● CÓ KHÁCH' : '● TRỐNG' ?>
                                        </small>
                                        <i class="fa-solid fa-utensils text-muted" style="font-size: 10px;"></i>
                                    </div>
                                    <div class="fw-bold text-dark fs-6 text-truncate"><?= htmlspecialchars($tb['table_name']) ?></div>
                                    <small class="text-muted d-block text-truncate" style="font-size: 11px;"><?= htmlspecialchars($tb['area_name'] ?? 'Khu vực 1') ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="card-tb" onclick="selectTable(1, 'Bàn 01', 0)"><div class="fw-bold">Bàn 01</div><small class="text-success">Trống</small></div>
                            <div class="card-tb" onclick="selectTable(2, 'Bàn 02', 0)"><div class="fw-bold">Bàn 02</div><small class="text-success">Trống</small></div>
                            <div class="card-tb" onclick="selectTable(3, 'Bàn 03', 0)"><div class="fw-bold">Bàn 03</div><small class="text-success">Trống</small></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="sectionTakeaway" style="display:none;" class="py-3 text-center text-muted">
                    <i class="fa-solid fa-bag-shopping fs-1 text-warning mb-2"></i>
                    <p class="m-0 fw-semibold">Đang ở chế độ Đơn Mang Về - Vui lòng chọn món ở bên dưới</p>
                </div>

                <div id="sectionOnline" style="display:none;" class="py-3 text-center text-muted">
                    <i class="fa-solid fa-motorcycle fs-1 text-primary mb-2"></i>
                    <p class="m-0 fw-semibold">Đang ở chế độ Đơn App Online (Grab / ShopeeFood)</p>
                </div>
            </div>

            <!-- KHỐI 2: THỰC ĐƠN MÓN -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-mug-hot text-primary me-2"></i>Thực Đơn Món Ăn & Đồ Uống</h6>
                    <small class="text-muted">Bấm món để thêm vào giỏ hàng</small>
                </div>

                <div class="grid-products" style="max-height: 240px; overflow-y: auto;">
                    <?php if(!empty($products)): ?>
                        <?php foreach($products as$prod): ?>
                            <div class="card-item" onclick="addToCart('<?= htmlspecialchars($prod['name'] ?? 'Món') ?>', <?= $prod['price'] ?? 0 ?>)">
                                <div class="fw-bold text-dark text-truncate" style="font-size: 13px;"><?= htmlspecialchars($prod['name'] ?? 'Món') ?></div>
                                <div class="text-primary fw-bold mt-1" style="font-size: 12px;"><?= number_format($prod['price'] ?? 0) ?> đ</div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card-item" onclick="addToCart('Cà Phê Sữa Đá', 25000)"><div class="fw-bold">Cà Phê Sữa Đá</div><div class="text-primary fw-bold">25,000 đ</div></div>
                        <div class="card-item" onclick="addToCart('Trà Sữa Trân Châu', 35000)"><div class="fw-bold">Trà Sữa Trân Châu</div><div class="text-primary fw-bold">35,000 đ</div></div>
                        <div class="card-item" onclick="addToCart('Trà Trái Cây Kiwi', 40000)"><div class="fw-bold">Trà Trái Cây Kiwi</div><div class="text-primary fw-bold">40,000 đ</div></div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- CỘT PHẢI (35%): GIỎ HÀNG SONG SONG BÊN PHẢI -->
        <div class="pos-v2-right">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-3 d-flex flex-column justify-content-between">
                <div>
                    <!-- THÔNG TIN BÀN ĐANG CHỌN -->
                    <div class="p-3 rounded-3 border bg-light d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold text-dark m-0" id="cartTableName">Chưa Chọn Bàn</h6>
                            <small class="text-muted" id="cartTableSub">Chọn bàn ở sơ đồ bên trái</small>
                        </div>
                        <span class="badge bg-secondary px-2.5 py-1.5" id="cartTableBadge">Trống</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-secondary text-uppercase" style="font-size: 11px;">Danh Sách Đã Chọn</span>
                        <span class="badge bg-primary-subtle text-primary fw-semibold" id="cartCount">0 món</span>
                    </div>

                    <!-- DANH SÁCH ORDER -->
                    <div id="cartItemsList" class="border-top border-bottom py-3" style="min-height: 250px; max-height: 320px; overflow-y: auto;">
                        <div class="text-center text-muted my-5">
                            <i class="fa-solid fa-basket-shopping fs-1 mb-2 opacity-25"></i>
                            <p class="small m-0">Giỏ hàng đang trống</p>
                        </div>
                    </div>
                </div>

                <!-- TỔNG TIỀN & PHẦN THANH TOÁN -->
                <div class="pt-3 border-top mt-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-secondary fw-semibold">Tổng Thanh Toán:</span>
                        <span class="text-danger fw-bold fs-3" id="cartTotal">0 đ</span>
                    </div>
                    <button class="btn btn-success btn-lg w-100 fw-bold rounded-3 shadow-sm py-2.5" style="background:#10b981; border:none;" onclick="alert('Đang tiến hành tạo hóa đơn & thanh toán!')">
                        <i class="fa-solid fa-print me-2"></i>Thanh Toán & In Bill
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
let cart = [];
let selectedTable = null;

function switchOrderType(type, btn) {
    document.querySelectorAll('.pos-type-btn').forEach(b => b.classList.remove('active'));
    if(btn) btn.classList.add('active');

    document.getElementById('sectionDineIn').style.display = (type === 'dine_in') ? 'block' : 'none';
    document.getElementById('sectionTakeaway').style.display = (type === 'takeaway') ? 'block' : 'none';
    document.getElementById('sectionOnline').style.display = (type === 'online') ? 'block' : 'none';
    document.getElementById('statusLegend').style.display = (type === 'dine_in') ? 'flex' : 'none';

    if(type === 'takeaway') {
        document.getElementById('cartTableName').innerText = "Đơn Mang Về";
        document.getElementById('cartTableSub').innerText = "Khách mua đi ngay";
        document.getElementById('cartTableBadge').innerText = "Mang Về";
        document.getElementById('cartTableBadge').className = "badge bg-warning text-dark px-2.5 py-1.5";
    } else if(type === 'online') {
        document.getElementById('cartTableName').innerText = "Đơn App Online";
        document.getElementById('cartTableSub').innerText = "Đơn GrabFood / ShopeeFood";
        document.getElementById('cartTableBadge').innerText = "App Online";
        document.getElementById('cartTableBadge').className = "badge bg-primary px-2.5 py-1.5";
    }
}

function selectTable(id, name, status) {
    selectedTable = { id, name, status };
    document.querySelectorAll('.card-tb').forEach(c => c.classList.remove('active-table'));
    
    let el = document.getElementById('tb-' + id);
    if(el) el.classList.add('active-table');

    document.getElementById('cartTableName').innerText = name;
    document.getElementById('cartTableSub').innerText = "Đơn phục vụ tại chỗ";
    document.getElementById('cartTableBadge').innerText = status == 1 ? 'Có khách' : 'Trống';
    document.getElementById('cartTableBadge').className = status == 1 ? 'badge bg-danger px-2.5 py-1.5' : 'badge bg-success px-2.5 py-1.5';
}

function addToCart(name, price) {
    let item = cart.find(i => i.name === name);
    if(item) {
        item.qty++;
    } else {
        cart.push({ name, price, qty: 1 });
    }
    renderCart();
}

function renderCart() {
    let container = document.getElementById('cartItemsList');
    let total = 0;
    let count = 0;

    if(cart.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted my-5">
                <i class="fa-solid fa-basket-shopping fs-1 mb-2 opacity-25"></i>
                <p class="small m-0">Giỏ hàng đang trống</p>
            </div>`;
    } else {
        let html = '';
        cart.forEach((item, index) => {
            total += item.price * item.qty;
            count += item.qty;
            html += `
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <div class="fw-bold text-dark" style="font-size:13px;">${item.name}</div>
                        <small class="text-muted">${item.price.toLocaleString()} đ x ${item.qty}</small>
                    </div>
                    <div class="fw-bold text-primary">${(item.price * item.qty).toLocaleString()} đ</div>
                </div>`;
        });
        container.innerHTML = html;
    }

    document.getElementById('cartCount').innerText = count + " món";
    document.getElementById('cartTotal').innerText = total.toLocaleString() + " đ";
}
</script>
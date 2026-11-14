const POS_CFG = window.POS_CONFIG || { baseUrl: '', userRole: 'cashier', toppingCatId: null };
const BASE_URL_JS = POS_CFG.baseUrl;
const currentUserRole = POS_CFG.userRole;
const DB_TOPPING_CAT_ID = POS_CFG.toppingCatId;

let currentOrderType = 'dine_in';
let onlinePlatform = 'grab';
let currentTableId = null;
let currentBranchId = localStorage.getItem('pos_selected_branch') || 'CN01';

let takeawayCounter = 1;
let onlineGrabCounter = 101;
let onlineShopeeCounter = 101;

let activeTakeawayCards = ['MV-01'];
let activeGrabCards = ['GRAB-101'];
let activeShopeeCards = ['SHOPEE-101'];

let activeOrders = {}; 
let tableReservationData = {}; 
let tableMergeSourcesMap = {}; 
let tableMergedTargetMap = {}; 
let completedOrdersHistory = [];
let cart = [];
let selectedProductForModal = null;
let activeModalModifiers = [];
let isShiftOpened = false;
let isPayDirectMode = false;
let isTableMapCollapsed = false;

// Lưu các ID sản phẩm đã được nhân viên xác nhận "Vẫn tiếp tục order" trong đơn hiện tại (không hỏi lại khi bấm +)
let confirmedStockBypassIds = new Set();

let selectedCustomer = null;
let registerCustomerModal = null;
let crmMemberModal = null;
let appliedPointsDiscount = 0;
let pointsToRedeem = 0;
let redeemedGiftsList = [];
let allTablesList = [];

// THUẾ VAT ĐỘNG
let currentVatPercent = 8;
let vatSettingModal = null;

// BÀN XOAY TUA
let turnaroundTablesMap = {};

let currentShiftInfo = { shift_id: null, cashierId: '', shiftType: '', shiftName: '', initialCash: 0 };
let selectedOrderForReprint = null;

let shiftStats = { 
    totalRevenue: 0, 
    offlineRevenue: 0,
    onlineRevenue: 0,
    totalCashSales: 0,
    totalBankSales: 0,
    totalAppSales: 0,
    totalOrders: 0,
    totalCups: 0,
    totalCakes: 0
};

let optionModal, stickerPrintModal, checkoutModal, shiftModal, mergeTableModal, splitTableModal, reservationManagerModal, cancelReserveModal, orderHistoryModal, reprintBillModal, customAlertModal;

document.addEventListener('DOMContentLoaded', function() {
    optionModal = new bootstrap.Modal(document.getElementById('productOptionModal'));
    stickerPrintModal = new bootstrap.Modal(document.getElementById('stickerPrintModal'));
    checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
    shiftModal = new bootstrap.Modal(document.getElementById('shiftModal'));
    mergeTableModal = new bootstrap.Modal(document.getElementById('mergeTableModal'));
    splitTableModal = new bootstrap.Modal(document.getElementById('splitTableModal'));
    reservationManagerModal = new bootstrap.Modal(document.getElementById('reservationManagerModal'));
    cancelReserveModal = new bootstrap.Modal(document.getElementById('cancelReserveModal'));
    orderHistoryModal = new bootstrap.Modal(document.getElementById('orderHistoryModal'));
    reprintBillModal = new bootstrap.Modal(document.getElementById('reprintBillModal'));
    customAlertModal = new bootstrap.Modal(document.getElementById('customAlertModal'));

    let regElem = document.getElementById('registerCustomerModal');
    if (regElem) registerCustomerModal = new bootstrap.Modal(regElem);

    let crmElem = document.getElementById('crmMemberModal');
    if (crmElem) crmMemberModal = new bootstrap.Modal(crmElem);

    let vatElem = document.getElementById('vatSettingModal');
    if (vatElem) vatSettingModal = new bootstrap.Modal(vatElem);

    let branchSelect = document.getElementById('pos-branch-select');
    if (branchSelect) {
        branchSelect.value = currentBranchId;
    }
    let globalBranchSelect = document.getElementById('global-branch-selector');
    if (globalBranchSelect) {
        globalBranchSelect.value = currentBranchId;
    }

    restoreShiftFromSession();
    checkOpenShiftFromDB();
    fetchReservationsFromDB();
    
    setInterval(fetchReservationsFromDB, 30000);
    setInterval(checkShiftTimeoutStatus, 60000);

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            restoreShiftFromSession();
            checkOpenShiftFromDB();
        }
    });
});

function switchBranch(branchId) {
    currentBranchId = branchId;
    localStorage.setItem('pos_selected_branch', branchId);
    localStorage.setItem('smart_fnb_active_branch', branchId);

    let branchSelect = document.getElementById('pos-branch-select');
    let globalBranchSelect = document.getElementById('global-branch-selector');
    if (globalBranchSelect && globalBranchSelect.value !== branchId) {
        globalBranchSelect.value = branchId;
    }

    let branchName = branchSelect ? branchSelect.options[branchSelect.selectedIndex].text : branchId;
    showCustomAlert(`Đã chuyển ngữ cảnh vận hành sang: ${branchName}`, "Chuyển Chi Nhánh", "info");
}

function toggleTableMapCollapse(forceState = null) {
    const bodyWrap = document.getElementById('table-map-body-collapse');
    const toggleBtn = document.getElementById('btn-toggle-table-map');
    if (!bodyWrap || !toggleBtn) return;

    if (forceState !== null) {
        isTableMapCollapsed = forceState;
    } else {
        isTableMapCollapsed = !isTableMapCollapsed;
    }

    if (isTableMapCollapsed) {
        bodyWrap.classList.add('d-none');
        toggleBtn.innerHTML = `<i class="bi bi-chevron-down"></i> Mở rộng`;
    } else {
        bodyWrap.classList.remove('d-none');
        toggleBtn.innerHTML = `<i class="bi bi-chevron-up"></i> Thu gọn`;
    }
}

function restoreShiftFromSession() {
    try {
        let savedShift = sessionStorage.getItem('pos_active_shift');
        if (savedShift) {
            let shiftData = JSON.parse(savedShift);
            isShiftOpened = true;
            currentShiftInfo = shiftData;

            document.getElementById('shift-status-badge').className = "badge bg-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
            document.getElementById('current-shift-name-label').innerText = currentShiftInfo.shiftName;
            document.getElementById('cashier-id-display').innerText = currentShiftInfo.cashierId;
            document.getElementById('right-panel-cashier').innerText = currentShiftInfo.cashierId;
        }
    } catch (e) {}
}

async function checkOpenShiftFromDB() {
    let savedShift = sessionStorage.getItem('pos_active_shift');
    if (savedShift) {
        try {
            currentShiftInfo = JSON.parse(savedShift);
            isShiftOpened = true;

            document.getElementById('shift-status-badge').className = "badge bg-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
            document.getElementById('current-shift-name-label').innerText = currentShiftInfo.shiftName;
            document.getElementById('cashier-id-display').innerText = currentShiftInfo.cashierId;
            document.getElementById('right-panel-cashier').innerText = currentShiftInfo.cashierId;
        } catch (e) {}
    }

    try {
        let res = await fetch(BASE_URL_JS + '/order/getShiftStatus').then(r => r.json());
        
        if (res.vat_percent !== undefined) {
            currentVatPercent = parseFloat(res.vat_percent);
            let vatLbl = document.getElementById('vat-label-display');
            if (vatLbl) vatLbl.innerText = currentVatPercent;
        }

        if (res.success && res.has_open_shift && res.shift) {
            isShiftOpened = true;
            currentShiftInfo = {
                shift_id: res.shift.id,
                cashierId: res.shift.cashier_id || 'NV01',
                shiftType: res.shift.shift_type || 'morning',
                shiftName: res.shift.shift_name || 'Ca Sáng',
                initialCash: parseFloat(res.shift.initial_cash || 0)
            };

            sessionStorage.setItem('pos_active_shift', JSON.stringify(currentShiftInfo));

            if (res.stats) {
                shiftStats.totalRevenue   = parseFloat(res.stats.total_revenue || 0);
                shiftStats.offlineRevenue = parseFloat(res.stats.offline_revenue || 0);
                shiftStats.onlineRevenue  = parseFloat(res.stats.online_revenue || 0);
                shiftStats.totalCashSales = parseFloat(res.stats.total_cash_sales || 0);
                shiftStats.totalBankSales = parseFloat(res.stats.total_bank_sales || 0);
                shiftStats.totalAppSales  = parseFloat(res.stats.total_app_sales || 0);
                shiftStats.totalOrders    = parseInt(res.stats.total_orders || 0);
                shiftStats.totalCups      = parseInt(res.stats.total_cups || 0);
                shiftStats.totalCakes     = parseInt(res.stats.total_cakes || 0);
            }

            document.getElementById('shift-status-badge').className = "badge bg-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
            document.getElementById('current-shift-name-label').innerText = currentShiftInfo.shiftName;
            document.getElementById('cashier-id-display').innerText = currentShiftInfo.cashierId;
            document.getElementById('right-panel-cashier').innerText = currentShiftInfo.cashierId;

            checkShiftTimeoutStatus();
        } else {
            if (!sessionStorage.getItem('pos_active_shift')) {
                isShiftOpened = false;
                openShiftModal();
            }
        }
    } catch (err) {
        console.error("Lỗi đồng bộ ca:", err);
    }
}

function checkShiftTimeoutStatus() {
    if (!isShiftOpened || !currentShiftInfo.shiftType) return;

    fetch(BASE_URL_JS + '/order/checkShiftTimeout?shift_type=' + currentShiftInfo.shiftType)
        .then(res => res.json())
        .then(res => {
            if (res.timeout) {
                showCustomAlert(res.message, "Hết Giờ Ca Làm Việc", "danger");
                document.getElementById('btn-checkout').disabled = true;
                document.getElementById('btn-pay-direct').disabled = true;
            }
        });
}

function getNextReservationForTable(tid) {
    let list = tableReservationData[tid] || [];
    if (!Array.isArray(list) || list.length === 0) return null;
    list.sort((a, b) => new Date(`${a.date}T${a.time}`) - new Date(`${b.date}T${b.time}`));
    return list[0];
}

function fetchReservationsFromDB() {
    fetch(BASE_URL_JS + '/order/getReservations')
        .then(res => res.json())
        .then(res => {
            if (res.success && res.tables) {
                allTablesList = res.tables;
                let now = new Date();
                let totalResCount = 0;

                // Đọc trực tiếp từ res.reservations trả về từ OrderController
                let dbGrouped = {};
                let rawReservations = Array.isArray(res.reservations) ? res.reservations : [];

                rawReservations.forEach(resItem => {
                    let tid = resItem.table_id;
                    if (resItem.reservation_id && resItem.reservation_time) {
                        let parts = resItem.reservation_time.split(' ');
                        let resDate = parts[0];
                        let resTime = parts[1] ? parts[1].substring(0, 5) : '00:00';
                        if (!dbGrouped[tid]) dbGrouped[tid] = [];
                        dbGrouped[tid].push({
                            id: resItem.reservation_id,
                            name: resItem.customer_name || 'Khách hàng',
                            phone: resItem.customer_phone || '',
                            guests: resItem.guests || resItem.guest_count || 2,
                            date: resDate,
                            time: resTime,
                            note: resItem.note || '',
                            isTurnaround: turnaroundTablesMap[tid] ? true : false
                        });
                    }
                });

                // Đồng bộ chuẩn 100% từ DB (F5 không bao giờ mất lịch)
                tableReservationData = dbGrouped;

                document.querySelectorAll('#table-section .table-badge').forEach(badge => {
                    let tid = badge.id.replace('table-btn-', '');
                    let resList = Array.isArray(tableReservationData[tid]) ? tableReservationData[tid] : [];
                    totalResCount += resList.length;

                    let nextRes = getNextReservationForTable(tid);
                    let isOccupied = badge.classList.contains('status-occupied') || (activeOrders[tid] && activeOrders[tid].length > 0);
                    let isPaid = badge.classList.contains('status-paid');

                    if (!isOccupied && !isPaid) {
                        if (nextRes) {
                            let resDateTime = new Date(`${nextRes.date}T${nextRes.time}:00`);
                            let diffMins = (resDateTime - now) / (1000 * 60);

                            if (diffMins <= 60) {
                                badge.className = "table-badge status-reserved" + (String(currentTableId) === String(tid) ? " active" : "");
                                let label = badge.querySelector('.status-label');
                                if (diffMins < 0) {
                                    if (label) label.innerText = `Quá giờ hẹn (${nextRes.time})`;
                                } else {
                                    if (label) label.innerText = `Đã đặt (${nextRes.time})`;
                                }
                            } else {
                                badge.className = "table-badge status-available" + (String(currentTableId) === String(tid) ? " active" : "");
                                let label = badge.querySelector('.status-label');
                                if (label) label.innerText = `Trống (Hẹn ${nextRes.time})`;
                            }
                        } else {
                            badge.className = "table-badge status-available" + (String(currentTableId) === String(tid) ? " active" : "");
                            let label = badge.querySelector('.status-label');
                            if (label) label.innerText = "Trống";
                        }
                    }
                });

                let badgeHeaderCount = document.getElementById('header-res-count-badge');
                if (badgeHeaderCount) {
                    badgeHeaderCount.innerText = totalResCount;
                    badgeHeaderCount.classList.toggle('d-none', totalResCount === 0);
                }

                // Cập nhật lại bảng danh sách nếu modal đặt bàn đang mở
                if (document.getElementById('reservationManagerModal')?.classList.contains('show')) {
                    renderReservationTableList();
                }
            }
        })
        .catch(err => console.error("Lỗi đồng bộ lịch đặt bàn:", err));
}
function showCustomAlert(msg, title = "Thông Báo Hệ Thống", type = "danger") {
    let circle = document.getElementById('alertIconCircle');
    let icon = document.getElementById('alertIcon');
    
    circle.className = "alert-icon-circle";
    
    if (type === "success") {
        circle.classList.add("alert-success");
        icon.className = "bi bi-check-lg";
    } else if (type === "info") {
        icon.className = "bi bi-info-lg";
    } else {
        circle.classList.add("alert-danger");
        icon.className = "bi bi-exclamation-lg";
    }

    document.getElementById('alertTitle').innerText = title;
    document.getElementById('alertMessage').innerText = msg;

    document.getElementById('alert-action-buttons').innerHTML = `
        <button type="button" class="btn btn-dark w-100 fw-bold rounded-3 py-2 saas-btn-nowrap" data-bs-dismiss="modal">ĐỒNG Ý</button>
    `;

    customAlertModal.show();
}

function removeVietnameseTones(str) {
    if (!str) return '';
    str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
    str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
    str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
    str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
    str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
    str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
    str = str.replace(/đ/g, "d");
    return str.toLowerCase().trim();
}

function switchOrderType(type) {
    if (!isShiftOpened) {
        showCustomAlert("Vui lòng mở ca làm việc trước khi thực hiện bán hàng!", "Yêu Cầu Mở Ca", "danger");
        openShiftModal();
        return;
    }

    currentOrderType = type;
    document.querySelectorAll('.order-type-btn').forEach(btn => btn.classList.remove('active'));
    
    const tableSection = document.getElementById('table-section');
    const takeawaySection = document.getElementById('takeaway-section');
    const onlineSection = document.getElementById('online-section');
    const onlineSubtabs = document.getElementById('online-subtabs-container');
    const badge = document.getElementById('order-type-badge');
    const tableNameLabel = document.getElementById('selected-table-name');
    const noteLabel = document.getElementById('table-reservation-note');
    const loyaltySection = document.getElementById('loyalty-section');

    const btnPayDirect = document.getElementById('btn-pay-direct');
    const btnCheckout = document.getElementById('btn-checkout');
    const boxBtnReady = document.getElementById('box-btn-ready');
    const btnKitchen = document.getElementById('btn-kitchen');

    if (noteLabel) noteLabel.classList.add('d-none');

    if (type === 'dine_in') {
        document.getElementById('tab-dine-in').classList.add('active');
        if (tableSection) tableSection.classList.remove('d-none');
        if (takeawaySection) takeawaySection.classList.add('d-none');
        if (onlineSection) onlineSection.classList.add('d-none');
        if (onlineSubtabs) onlineSubtabs.classList.add('d-none');
        if (loyaltySection) loyaltySection.classList.remove('d-none');
        
        badge.innerText = "TẠI CHỖ";
        badge.className = "badge bg-dark text-white mb-1 fw-bold px-2 py-1";

        if (btnPayDirect) btnPayDirect.classList.remove('d-none');
        if (boxBtnReady) boxBtnReady.classList.remove('d-none');
        if (btnKitchen) btnKitchen.className = "btn btn-outline-dark fw-bold py-2 rounded-3 w-50 pos-action-btn-nowrap d-inline-flex align-items-center justify-content-center gap-1";
        if (btnCheckout) btnCheckout.innerHTML = `<i class="bi bi-credit-card"></i> THANH TOÁN (TRẢ BÀN)`;
        
        if (currentTableId && typeof currentTableId === 'number') {
            let tableBtn = document.getElementById('table-btn-' + currentTableId);
            tableNameLabel.innerText = tableBtn ? tableBtn.querySelector('.fw-bold').innerText : "Chưa Chọn Bàn";
        } else {
            currentTableId = null;
            tableNameLabel.innerText = "Chưa Chọn Bàn";
        }

    } else if (type === 'takeaway') {
        document.getElementById('tab-takeaway').classList.add('active');
        if (tableSection) tableSection.classList.add('d-none');
        if (takeawaySection) takeawaySection.classList.remove('d-none');
        if (onlineSection) onlineSection.classList.add('d-none');
        if (onlineSubtabs) onlineSubtabs.classList.add('d-none');
        if (loyaltySection) loyaltySection.classList.remove('d-none');
        
        badge.innerText = "MANG VỀ";
        badge.className = "badge bg-warning text-dark mb-1 fw-bold px-2 py-1";

        if (btnPayDirect) btnPayDirect.classList.add('d-none');
        if (boxBtnReady) boxBtnReady.classList.add('d-none');
        if (btnKitchen) btnKitchen.className = "btn btn-outline-dark fw-bold py-2 rounded-3 w-100 pos-action-btn-nowrap d-inline-flex align-items-center justify-content-center gap-1";
        if (btnCheckout) btnCheckout.innerHTML = `<i class="bi bi-cash-stack"></i> THANH TOÁN & CHỐT ĐƠN`;
        
        renderTakeawayCards();
        if (activeTakeawayCards.length > 0) {
            selectTakeawayCard(activeTakeawayCards[0]);
        }

    } else if (type === 'online') {
        document.getElementById('tab-online').classList.add('active');
        if (tableSection) tableSection.classList.add('d-none');
        if (takeawaySection) takeawaySection.classList.add('d-none');
        if (onlineSection) onlineSection.classList.remove('d-none');
        if (onlineSubtabs) onlineSubtabs.classList.remove('d-none');
        if (loyaltySection) loyaltySection.classList.add('d-none');
        detachCustomer();

        if (btnPayDirect) btnPayDirect.classList.add('d-none');
        if (boxBtnReady) boxBtnReady.classList.add('d-none');
        if (btnKitchen) btnKitchen.className = "btn btn-outline-dark fw-bold py-2 rounded-3 w-100 pos-action-btn-nowrap d-inline-flex align-items-center justify-content-center gap-1";
        if (btnCheckout) btnCheckout.innerHTML = `<i class="bi bi-box-seam"></i> CHỐT ĐƠN & IN BILL SHIPPER`;
        
        switchOnlinePlatform(onlinePlatform);
    }

    loadCartForCurrentTable();
    updateProductPricesDisplay();
    updateCheckoutButton();
}

function switchOnlinePlatform(platform) {
    onlinePlatform = platform;
    const badge = document.getElementById('order-type-badge');
    const btnGrab = document.getElementById('subtab-grab');
    const btnShopee = document.getElementById('subtab-shopee');
    const titleLabel = document.getElementById('online-section-title');

    if (platform === 'grab') {
        btnGrab.className = "subtab-btn active-grab";
        btnShopee.className = "subtab-btn";
        badge.innerText = "GRABFOOD";
        badge.className = "badge bg-success text-white mb-1 fw-bold px-2 py-1";
        if (titleLabel) titleLabel.innerText = "DANH SÁCH ĐƠN GRABFOOD TẠO MỚI";
        
        renderOnlineCards();
        if (activeGrabCards.length > 0) selectOnlineCard(activeGrabCards[0]);

    } else {
        btnGrab.className = "subtab-btn";
        btnShopee.className = "subtab-btn active-shopee";
        badge.innerText = "SHOPEEFOOD";
        badge.className = "badge badge-shopee mb-1 fw-bold px-2 py-1";
        if (titleLabel) titleLabel.innerText = "DANH SÁCH ĐƠN SHOPEEFOOD TẠO MỚI";
        
        renderOnlineCards();
        if (activeShopeeCards.length > 0) selectOnlineCard(activeShopeeCards[0]);
    }

    loadCartForCurrentTable();
    updateProductPricesDisplay();
    updateCheckoutButton();
}

function renderTakeawayCards() {
    const grid = document.getElementById('takeaway-cards-grid');
    if (!grid) return;
    grid.innerHTML = '';

    activeTakeawayCards.forEach(cardCode => {
        let itemCount = activeOrders[cardCode] ? activeOrders[cardCode].reduce((sum, i) => sum + i.qty, 0) : 0;
        let isActive = (currentTableId === cardCode) ? 'active' : '';
        let statusClass = itemCount > 0 ? 'status-occupied' : 'status-available';
        let statusText = itemCount > 0 ? `${itemCount} sản phẩm` : 'Chờ phục vụ';

        grid.innerHTML += `
            <div class="col-6 col-sm-4 col-md-3">
                <div class="table-badge ${statusClass} ${isActive}" id="card-btn-${cardCode}" onclick="selectTakeawayCard('${cardCode}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 13px;">Thẻ ${cardCode}</div>
                        <span class="status-dot"></span>
                    </div>
                    <small class="text-muted d-block status-label" style="font-size: 10px;">${statusText}</small>
                </div>
            </div>`;
    });
}

function selectTakeawayCard(cardCode) {
    currentTableId = cardCode;
    document.getElementById('selected-table-name').innerText = "Thẻ Mang Về: " + cardCode;
    let badgeHeader = document.getElementById('table-selected-header-badge');
    if (badgeHeader) badgeHeader.innerText = "Đang chọn: " + cardCode;
    
    document.querySelectorAll('#takeaway-cards-grid .table-badge').forEach(b => b.classList.remove('active'));
    let activeBtn = document.getElementById('card-btn-' + cardCode);
    if (activeBtn) activeBtn.classList.add('active');

    loadCartForCurrentTable();
    updateCheckoutButton();
}

function addNewTakeawayCard() {
    takeawayCounter++;
    let newCode = "MV-" + String(takeawayCounter).padStart(2, '0');
    activeTakeawayCards.push(newCode);
    renderTakeawayCards();
    selectTakeawayCard(newCode);
}

function renderOnlineCards() {
    const grid = document.getElementById('online-cards-grid');
    if (!grid) return;
    grid.innerHTML = '';

    let cardsList = (onlinePlatform === 'grab') ? activeGrabCards : activeShopeeCards;

    cardsList.forEach(cardCode => {
        let itemCount = activeOrders[cardCode] ? activeOrders[cardCode].reduce((sum, i) => sum + i.qty, 0) : 0;
        let isActive = (currentTableId === cardCode) ? 'active' : '';
        let statusClass = itemCount > 0 ? 'status-occupied' : 'status-available';
        let statusText = itemCount > 0 ? `${itemCount} sản phẩm` : 'Đơn mới';

        grid.innerHTML += `
            <div class="col-6 col-sm-4 col-md-3">
                <div class="table-badge ${statusClass} ${isActive}" id="card-btn-${cardCode}" onclick="selectOnlineCard('${cardCode}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 13px;">${cardCode}</div>
                        <span class="status-dot"></span>
                    </div>
                    <small class="text-muted d-block status-label" style="font-size: 10px;">${statusText}</small>
                </div>
            </div>`;
    });
}

function selectOnlineCard(cardCode) {
    currentTableId = cardCode;
    document.getElementById('selected-table-name').innerText = "Mã Đơn: " + cardCode;
    let badgeHeader = document.getElementById('table-selected-header-badge');
    if (badgeHeader) badgeHeader.innerText = "Đang chọn: " + cardCode;
    
    document.querySelectorAll('#online-cards-grid .table-badge').forEach(b => b.classList.remove('active'));
    let activeBtn = document.getElementById('card-btn-' + cardCode);
    if (activeBtn) activeBtn.classList.add('active');

    loadCartForCurrentTable();
    updateCheckoutButton();
}

function addNewOnlineCard() {
    let newCode = '';
    if (onlinePlatform === 'grab') {
        onlineGrabCounter++;
        newCode = "GRAB-" + onlineGrabCounter;
        activeGrabCards.push(newCode);
    } else {
        onlineShopeeCounter++;
        newCode = "SHOPEE-" + onlineShopeeCounter;
        activeShopeeCards.push(newCode);
    }
    renderOnlineCards();
    selectOnlineCard(newCode);
}

function selectTable(tableId, tableName) {
    if (!isShiftOpened) {
        showCustomAlert("Vui lòng mở ca làm việc trước khi thực hiện chọn bàn!", "Yêu Cầu Mở Ca", "danger");
        openShiftModal();
        return;
    }

    if (tableMergedTargetMap[tableId]) {
        let targetId = tableMergedTargetMap[tableId];
        let targetBtn = document.getElementById('table-btn-' + targetId);
        let targetName = targetBtn ? targetBtn.querySelector('.fw-bold').innerText : ('Bàn ' + targetId);

        document.getElementById('alertTitle').innerText = "Hóa Đơn Đã Được Gộp";
        document.getElementById('alertMessage').innerHTML = `Bàn <strong class="text-dark">${tableName}</strong> đã được chuyển toàn bộ hóa đơn sang <strong class="text-primary">${targetName}</strong>.<br><span class="text-muted d-block mt-1">Bạn muốn thực hiện <strong>TRẢ BÀN</strong> hay sang <strong>${targetName.toUpperCase()}</strong> bổ sung món?</span>`;
        
        let circle = document.getElementById('alertIconCircle');
        circle.className = "alert-icon-circle";
        document.getElementById('alertIcon').className = "bi bi-arrow-merge";

        document.getElementById('alert-action-buttons').innerHTML = `
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-danger w-50 fw-bold rounded-3 py-2 saas-btn-nowrap" onclick="customAlertModal.hide(); releaseMergedSourceTable(${tableId});">TRẢ BÀN</button>
                <button type="button" class="btn btn-warning w-50 fw-bold rounded-3 py-2 text-dark shadow-sm saas-btn-nowrap" onclick="customAlertModal.hide(); proceedSelectPaidTable(${targetId}, '${targetName}');">SANG ${targetName.toUpperCase()}</button>
            </div>
        `;

        customAlertModal.show();
        return;
    }

    let selectedBtn = document.getElementById('table-btn-' + tableId);
    let noteLabel = document.getElementById('table-reservation-note');
    if (noteLabel) noteLabel.classList.add('d-none');

    let nextRes = getNextReservationForTable(tableId);

    if (nextRes && (!selectedBtn || !selectedBtn.classList.contains('status-occupied'))) {
        let rTime = nextRes.time || 'giờ hẹn';
        let cName = nextRes.name || 'Khách hàng';
        let cPhone = nextRes.phone || 'Chưa có SĐT';
        let cGuests = nextRes.guests || 2;
        let cNote = nextRes.note || 'Không có';

        let now = new Date();
        let resDateTime = new Date(`${nextRes.date}T${nextRes.time}:00`);
        let diffMins = (resDateTime - now) / (1000 * 60);

        if (diffMins < 0) {
            document.getElementById('alertTitle').innerText = "Khách Đặt Bàn (Đã Quá Giờ)";
            document.getElementById('alertMessage').innerHTML = `
                <div class="text-start bg-light p-3 rounded-3 border mb-2" style="font-size:12.5px;">
                    <p class="mb-1"><strong>Khách hàng:</strong> ${cName} (${cGuests} khách)</p>
                    <p class="mb-1"><strong>Số điện thoại:</strong> ${cPhone}</p>
                    <p class="mb-1"><strong>Giờ hẹn:</strong> ${rTime} (${nextRes.date})</p>
                    <p class="mb-0"><strong>Ghi chú:</strong> ${cNote}</p>
                </div>
                <div class="alert alert-danger p-2 mb-0 fw-bold" style="font-size:11.5px;">⚠️ ĐÃ QUÁ GIỜ HẸN! Khách có thể đã trễ hẹn hoặc đã tới quán.</div>
            `;
            let circle = document.getElementById('alertIconCircle');
            circle.className = "alert-icon-circle alert-danger";
            document.getElementById('alertIcon').className = "bi bi-exclamation-triangle-fill";

            document.getElementById('alert-action-buttons').innerHTML = `
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger w-50 fw-bold rounded-3 py-2 saas-btn-nowrap" onclick="customAlertModal.hide(); openCancelModalForTable(${tableId}, '${tableName}', '${nextRes.id || ''}');">HỦY LỊCH</button>
                    <button type="button" class="btn btn-success w-50 fw-bold rounded-3 py-2 shadow-sm saas-btn-nowrap" onclick="customAlertModal.hide(); checkInReservation(${tableId}, '${nextRes.id || ''}');">KHÁCH ĐÃ ĐẾN</button>
                </div>
            `;
            customAlertModal.show();
            return;
        } else if (diffMins <= 60) {
            document.getElementById('alertTitle').innerText = "Khách Đặt Bàn (Sát Giờ Hẹn)";
            document.getElementById('alertMessage').innerHTML = `
                <div class="text-start bg-light p-3 rounded-3 border mb-2" style="font-size:12.5px;">
                    <p class="mb-1"><strong>Khách hàng:</strong> ${cName} (${cGuests} khách)</p>
                    <p class="mb-1"><strong>Số điện thoại:</strong> ${cPhone}</p>
                    <p class="mb-1"><strong>Giờ hẹn:</strong> ${rTime} (${nextRes.date})</p>
                    <p class="mb-0"><strong>Ghi chú:</strong> ${cNote}</p>
                </div>
                <div class="alert alert-warning p-2 mb-0 fw-bold text-dark" style="font-size:11.5px;">⚠️ Còn ${Math.round(diffMins)} phút nữa tới giờ hẹn! Bàn đang giữ chỗ cho khách hẹn trước.</div>
            `;
            let circle = document.getElementById('alertIconCircle');
            circle.className = "alert-icon-circle";
            document.getElementById('alertIcon').className = "bi bi-bookmark-star-fill";

            document.getElementById('alert-action-buttons').innerHTML = `
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary w-50 fw-bold rounded-3 py-2 saas-btn-nowrap" data-bs-dismiss="modal">ĐÓNG</button>
                    <button type="button" class="btn btn-success w-50 fw-bold rounded-3 py-2 shadow-sm saas-btn-nowrap" onclick="customAlertModal.hide(); checkInReservation(${tableId}, '${nextRes.id || ''}');">KHÁCH ĐÃ ĐẾN</button>
                </div>
            `;
            customAlertModal.show();
            return;
        } else {
            document.getElementById('alertTitle').innerText = "Bàn Có Lịch Đặt Sắp Tới";
            document.getElementById('alertMessage').innerHTML = `
                <div class="text-start bg-light p-3 rounded-3 border mb-2" style="font-size:12.5px;">
                    <p class="mb-1"><strong>Khách hẹn:</strong> ${cName} (${cGuests} khách)</p>
                    <p class="mb-1"><strong>SĐT:</strong> ${cPhone} | <strong>Giờ hẹn:</strong> ${rTime}</p>
                    <p class="mb-0"><strong>Ghi chú:</strong> ${cNote}</p>
                </div>
                <div class="alert alert-info p-2 mb-0" style="font-size:11.5px;"> Vẫn chưa tới giờ hẹn. Có thể mở bàn xoay tua cho khách vãng lai.</div>
            `;
            let circle = document.getElementById('alertIconCircle');
            circle.className = "alert-icon-circle";
            document.getElementById('alertIcon').className = "bi bi-clock-history";

            document.getElementById('alert-action-buttons').innerHTML = `
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary w-50 fw-bold rounded-3 py-2 saas-btn-nowrap" onclick="customAlertModal.hide(); confirmTurnaroundTable(${tableId}, '${tableName}', '${rTime}');">MỞ XOAY TUA</button>
                    <button type="button" class="btn btn-success w-50 fw-bold rounded-3 py-2 shadow-sm saas-btn-nowrap" onclick="customAlertModal.hide(); checkInReservation(${tableId}, '${nextRes.id || ''}');">KHÁCH ĐÃ ĐẾN</button>
                </div>
            `;
            customAlertModal.show();
            return;
        }
    }

    if (selectedBtn && selectedBtn.classList.contains('status-paid')) {
        document.getElementById('alertTitle').innerText = "Trạng Thái Thanh Toán";
        document.getElementById('alertMessage').innerHTML = `Bàn <strong class="text-dark">${tableName}</strong> đã hoàn tất thanh toán.<br><span class="text-muted d-block mt-1">Bạn muốn thực hiện <strong>TRẢ BÀN TRỐNG</strong> hay <strong>GỌI THÊM MÓN MỚI</strong>?</span>`;
        
        let circle = document.getElementById('alertIconCircle');
        circle.className = "alert-icon-circle";
        document.getElementById('alertIcon').className = "bi bi-info-circle";

        document.getElementById('alert-action-buttons').innerHTML = `
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-danger w-50 fw-bold rounded-3 py-2 saas-btn-nowrap" onclick="customAlertModal.hide(); releasePaidTable(${tableId});">TRẢ BÀN</button>
                <button type="button" class="btn btn-success w-50 fw-bold rounded-3 py-2 saas-btn-nowrap" onclick="customAlertModal.hide(); proceedSelectPaidTable(${tableId}, '${tableName}');">GỌI THÊM MÓN</button>
            </div>
        `;

        customAlertModal.show();
        return;
    }

    proceedSelectPaidTable(tableId, tableName);
}

function releaseMergedSourceTable(sourceId) {
    let targetId = tableMergedTargetMap[sourceId];
    delete tableMergedTargetMap[sourceId];
    if (tableMergeSourcesMap[targetId]) {
        tableMergeSourcesMap[targetId] = tableMergeSourcesMap[targetId].filter(id => id != sourceId);
    }
    let srcBadge = document.getElementById('table-btn-' + sourceId);
    if (srcBadge) {
        srcBadge.className = "table-badge status-available";
        srcBadge.querySelector('.status-label').innerText = "Trống";
    }

    syncTableStatusToDB(sourceId, 'available');
    fetchReservationsFromDB();
    showCustomAlert(`Bàn ${sourceId} đã được đưa về trạng thái trống thành công!`, "Thành Công", "success");
}

function releasePaidTable(tableId) {
    let tableBadge = document.getElementById('table-btn-' + tableId);
    let newStatus = 'available';

    if (turnaroundTablesMap[tableId]) {
        delete turnaroundTablesMap[tableId];
    }

    let nextRes = getNextReservationForTable(tableId);
    if (nextRes) {
        let now = new Date();
        let resDateTime = new Date(`${nextRes.date}T${nextRes.time}:00`);
        let diffMins = (resDateTime - now) / (1000 * 60);

        if (diffMins <= 60) {
            newStatus = 'reserved';
            if (tableBadge) {
                tableBadge.className = "table-badge status-reserved";
                tableBadge.querySelector('.status-label').innerText = `Đã đặt (${nextRes.time})`;
            }
        } else {
            newStatus = 'available';
            if (tableBadge) {
                tableBadge.className = "table-badge status-available";
                tableBadge.querySelector('.status-label').innerText = `Trống (Hẹn ${nextRes.time})`;
            }
        }
    } else {
        if (tableBadge) {
            tableBadge.className = "table-badge status-available";
            tableBadge.querySelector('.status-label').innerText = "Trống";
        }
    }

    syncTableStatusToDB(tableId, newStatus);

    document.getElementById('btn-release-table').classList.add('d-none');
    delete activeOrders[tableId];
    cart = [];
    renderCart();
    showCustomAlert(`Đã giải phóng bàn thành công! Lịch đặt sắp tới (nếu có) vẫn được bảo toàn.`, "Thành Công", "success");
}

function proceedSelectPaidTable(tableId, tableName) {
    let selectedBtn = document.getElementById('table-btn-' + tableId);
    let noteLabel = document.getElementById('table-reservation-note');

    currentTableId = tableId;
    document.getElementById('selected-table-name').innerText = tableName;
    let badgeHeader = document.getElementById('table-selected-header-badge');
    if (badgeHeader) badgeHeader.innerText = "Đang chọn: " + tableName;
    
    document.querySelectorAll('.table-badge').forEach(badge => badge.classList.remove('active'));
    if (selectedBtn) {
        selectedBtn.classList.add('active');
        let nextRes = getNextReservationForTable(tableId);
        if (turnaroundTablesMap[tableId] && nextRes) {
            if (noteLabel) {
                noteLabel.innerText = `[Bàn Xoay Tua - Giờ Hẹn Kế Tiếp: ${nextRes.time}]`;
                noteLabel.classList.remove('d-none');
            }
        } else if (nextRes && nextRes.note) {
            if (noteLabel) {
                noteLabel.innerText = `[Khách hẹn: ${nextRes.name} - ${nextRes.note}]`;
                noteLabel.classList.remove('d-none');
            }
        }
    }

    let releaseBtn = document.getElementById('btn-release-table');
    let isPaidStatus = selectedBtn && selectedBtn.classList.contains('status-paid');
    let isSourceEmptyBill = selectedBtn && selectedBtn.classList.contains('status-occupied') && (!activeOrders[tableId] || activeOrders[tableId].length === 0);

    if (releaseBtn) {
        if (isPaidStatus || isSourceEmptyBill) {
            releaseBtn.classList.remove('d-none');
        } else {
            releaseBtn.classList.add('d-none');
        }
    }

    loadCartForCurrentTable();
    updateCheckoutButton();
    // Không tự động thu gọn sơ đồ bàn khi bấm chọn bàn — chỉ thu gọn khi bấm nút chủ động
}

function confirmTurnaroundTable(tableId, tableName, rTime) {
    turnaroundTablesMap[tableId] = true;

    let selectedBtn = document.getElementById('table-btn-' + tableId);
    if (selectedBtn) {
        selectedBtn.className = "table-badge status-occupied active";
        selectedBtn.querySelector('.status-label').innerText = `[Xoay Tua - Hẹn ${rTime}]`;
    }

    syncTableStatusToDB(tableId, 'occupied', true);
    proceedSelectPaidTable(tableId, tableName);
}

function checkInReservation(tableId, resId) {
    let list = Array.isArray(tableReservationData[tableId]) ? tableReservationData[tableId] : [];
    let resIndex = list.findIndex(r => String(r.id) === String(resId));
    if (resIndex === -1 && list.length > 0) resIndex = 0;

    let checkedInData = resIndex >= 0 ? list[resIndex] : null;
    let targetResId = checkedInData ? checkedInData.id : resId;

    if (resIndex >= 0) {
        list.splice(resIndex, 1);
        tableReservationData[tableId] = list;
    }

    delete turnaroundTablesMap[tableId];

    // Truyền kèm reservation_id để PHP chỉ hủy đúng lịch của khách vừa đến, giữ nguyên lịch khách sau
    fetch(BASE_URL_JS + '/order/cancelReservation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            table_id: parseInt(tableId), 
            reservation_id: targetResId,
            reason: 'Khách đã đến nhận bàn (Check-in)' 
        })
    })
    .then(() => fetchReservationsFromDB())
    .catch(() => {});

    let tableBadge = document.getElementById('table-btn-' + tableId);
    let tableName = tableBadge ? tableBadge.querySelector('.fw-bold').innerText : ('Bàn ' + tableId);

    if (tableBadge) {
        tableBadge.className = "table-badge status-occupied active";
        tableBadge.querySelector('.status-label').innerText = "Có khách";
    }
    syncTableStatusToDB(parseInt(tableId), 'occupied', true);

    if (reservationManagerModal) reservationManagerModal.hide();

    if (checkedInData && checkedInData.phone) {
        let phoneInput = document.getElementById('cust-phone-input');
        if (phoneInput) {
            phoneInput.value = checkedInData.phone;
            searchCustomerByPhone(true);
        }
    }

    proceedSelectPaidTable(parseInt(tableId), tableName);
    if (checkedInData) {
        let noteLabel = document.getElementById('table-reservation-note');
        if (noteLabel) {
            noteLabel.innerText = `[Check-in: ${checkedInData.name} - ${checkedInData.phone} (${checkedInData.guests || 2} khách)]`;
            noteLabel.classList.remove('d-none');
        }
    }
    showCustomAlert(`Đã Check-in bàn ${tableName} cho khách hàng ${checkedInData ? checkedInData.name : ''}!`, "Check-in Thành Công", "success");
}
function releaseCurrentTable() {
    if (!currentTableId) return;
    releasePaidTable(currentTableId);
}

function loadCartForCurrentTable() {
    if (currentTableId && activeOrders[currentTableId]) {
        cart = activeOrders[currentTableId];
    } else {
        cart = [];
    }
    renderCart();
}

function getAllowedModifiers(product) {
    if (product.modifiers_config === null || product.modifiers_config === undefined) {
        let pCat = String(product.category_id || '');
        let isToppingOrCake = (DB_TOPPING_CAT_ID && pCat === String(DB_TOPPING_CAT_ID)) || pCat === '4' || pCat === '5';
        return isToppingOrCake ? [] : ['size', 'sugar', 'ice', 'topping'];
    }
    return String(product.modifiers_config)
        .split(',')
        .map(s => s.trim().toLowerCase())
        .filter(Boolean);
}

// KIỂM TRA TỒN KHO & HẠN SỬ DỤNG (CHỈ HỎI 1 LẦN ĐẦU CHO MỖI MÓN TRONG ĐƠN)
function checkStockAndExpiryWarning(product, additionalQty = 1, onProceedCallback) {
    let pid = String(product.id);

    // Nếu nhân viên đã bấm xác nhận "Vẫn tiếp tục order" cho món này trước đó -> tự do tăng số lượng không hỏi lại
    if (confirmedStockBypassIds.has(pid)) {
        onProceedCallback();
        return;
    }

    let manageStock = parseInt(product.manage_stock || 0);
    let stockQty = parseFloat(product.stock_quantity || 0);
    let expiryDateStr = product.expiry_date || '';

    let currentInCart = cart
        .filter(item => String(item.id) === pid)
        .reduce((sum, item) => sum + item.qty, 0);

    let projectedQty = currentInCart + additionalQty;
    let isExpired = false;

    if (expiryDateStr) {
        let expDate = new Date(expiryDateStr + 'T23:59:59');
        if (new Date() > expDate) {
            isExpired = true;
        }
    }

    let isOverStock = (manageStock === 1 && projectedQty > stockQty);

    if (isExpired || isOverStock) {
        let reasons = [];
        if (isExpired) {
            reasons.push(`Sản phẩm đã <strong>HẾT HẠN SỬ DỤNG</strong> (HSD: ${expiryDateStr}).`);
        }
        if (isOverStock) {
            reasons.push(`Số lượng order (<strong>${projectedQty}</strong>) vượt quá tồn kho hiện tại (<strong>${stockQty}</strong>).`);
        }

        document.getElementById('alertTitle').innerText = "Cảnh Báo Tồn Kho & Hạn Sử Dụng";
        document.getElementById('alertMessage').innerHTML = `
            <div class="text-start bg-light p-3 rounded-3 border mb-2" style="font-size:12.5px;">
                <div class="fw-bold text-dark mb-1">${product.name}</div>
                <ul class="mb-0 ps-3 text-danger">
                    ${reasons.map(r => `<li>${r}</li>`).join('')}
                </ul>
            </div>
            <div class="text-muted" style="font-size:11.5px;">Nếu hàng mới vừa nhập về chưa kịp cập nhật kho, chọn <strong>Vẫn tiếp tục order</strong> để tự do điều chỉnh số lượng cho món này.</div>
        `;

        let circle = document.getElementById('alertIconCircle');
        circle.className = "alert-icon-circle alert-danger";
        document.getElementById('alertIcon').className = "bi bi-exclamation-triangle-fill";

        document.getElementById('alert-action-buttons').innerHTML = `
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary w-50 fw-bold rounded-3 py-2 saas-btn-nowrap" data-bs-dismiss="modal">HỦY CHỌN</button>
                <button type="button" class="btn btn-dark w-50 fw-bold rounded-3 py-2 shadow-sm saas-btn-nowrap" id="btn-force-order-stock">VẪN TIẾP TỤC ORDER</button>
            </div>
        `;

        document.getElementById('btn-force-order-stock').onclick = function() {
            confirmedStockBypassIds.add(pid);
            customAlertModal.hide();
            onProceedCallback();
        };

        customAlertModal.show();
        return;
    }

    onProceedCallback();
}

function handleProductClick(product) {
    if (!isShiftOpened) {
        showCustomAlert("Vui lòng mở ca làm việc trước khi thực hiện gọi món!", "Yêu Cầu Mở Ca", "danger");
        openShiftModal();
        return;
    }

    if (currentOrderType === 'dine_in' && !currentTableId) {
        showCustomAlert("Vui lòng lựa chọn bàn phục vụ trước khi chọn món ăn!", "Chưa Chọn Bàn", "danger");
        return;
    }

    checkStockAndExpiryWarning(product, 1, function() {
        let allowedMods = getAllowedModifiers(product);
        let pCat = String(product.category_id || '');
        let isCakeByCat = (pCat === '5') || (allowedMods.length === 0);

        if (allowedMods.length === 0) {
            let basePrice = parseFloat(product.price);
            if (currentOrderType === 'online') basePrice = Math.round(basePrice * 1.1);

            cart.push({
                id: product.id,
                category_id: product.category_id,
                name: product.name,
                price: basePrice,
                manage_stock: parseInt(product.manage_stock || 0),
                stock_quantity: parseFloat(product.stock_quantity || 0),
                expiry_date: product.expiry_date || '',
                size: '',
                sugar: '',
                ice: '',
                toppings: [],
                note: '',
                isCake: isCakeByCat,
                qty: 1,
                printedQty: 0
            });

            if (currentTableId) activeOrders[currentTableId] = cart;
            renderCart();
            if (currentOrderType === 'takeaway') renderTakeawayCards();
            if (currentOrderType === 'online') renderOnlineCards();
            return;
        }

        openProductOptionModal(product, allowedMods);
    });
}

function openProductOptionModal(product, allowedMods) {
    selectedProductForModal = product;
    activeModalModifiers = allowedMods || getAllowedModifiers(product);

    document.getElementById('optionModalTitle').innerText = product.name;
    document.getElementById('item-note').value = '';

    const sizeBox    = document.getElementById('size-options-container');
    const sugarBox   = document.getElementById('sugar-options-container');
    const iceBox     = document.getElementById('ice-options-container');
    const toppingBox = document.getElementById('topping-dynamic-container');

    sizeBox.classList.toggle('d-none', !activeModalModifiers.includes('size'));
    sugarBox.classList.toggle('d-none', !activeModalModifiers.includes('sugar'));
    iceBox.classList.toggle('d-none', !activeModalModifiers.includes('ice'));
    toppingBox.classList.toggle('d-none', !activeModalModifiers.includes('topping'));

    ['size-options', 'sugar-options', 'ice-options'].forEach(groupId => {
        const pills = document.querySelectorAll(`#${groupId} .option-pill`);
        pills.forEach((p, idx) => p.classList.toggle('selected', idx === 0));
    });

    const dynamicToppingList = document.getElementById('dynamic-topping-list');
    dynamicToppingList.innerHTML = '';

    if (activeModalModifiers.includes('topping')) {
        document.querySelectorAll('.product-item').forEach(item => {
            let itemCat = String(item.getAttribute('data-category'));
            let itemName = item.getAttribute('data-name');
            let itemPrice = parseFloat(item.getAttribute('data-price'));

            if ((DB_TOPPING_CAT_ID && itemCat === String(DB_TOPPING_CAT_ID)) || (itemCat === '4')) {
                dynamicToppingList.innerHTML += `
                    <div class="form-check form-check-inline m-0">
                        <input class="btn-check dynamic-topping-chk" type="checkbox" value="${itemPrice}" data-name="${itemName}" id="top-chk-${itemPrice}-${removeVietnameseTones(itemName)}">
                        <label class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1" for="top-chk-${itemPrice}-${removeVietnameseTones(itemName)}" style="font-size:11px;">
                            + ${itemName} (+${itemPrice.toLocaleString()}đ)
                        </label>
                    </div>`;
            }
        });
    }

    optionModal.show();
}

function selectPill(element, group) {
    let parent = element.parentElement;
    parent.querySelectorAll('.option-pill').forEach(p => p.classList.remove('selected'));
    element.classList.add('selected');
}

function confirmAddToCart() {
    if (!selectedProductForModal) return;

    let hasSize    = activeModalModifiers.includes('size');
    let hasSugar   = activeModalModifiers.includes('sugar');
    let hasIce     = activeModalModifiers.includes('ice');
    let hasTopping = activeModalModifiers.includes('topping');

    let sizePill  = hasSize ? document.querySelector('#size-options .selected') : null;
    let sizeName  = sizePill ? sizePill.innerText.split(' ')[1] : '';
    let sizeExtra = sizePill ? parseFloat(sizePill.getAttribute('data-extra')) : 0;
    let sugar     = hasSugar ? (document.querySelector('#sugar-options .selected')?.innerText || '100% Đường') : '';
    let ice       = hasIce ? (document.querySelector('#ice-options .selected')?.innerText || '100% Đá') : '';

    let selectedToppings = [];
    let toppingsExtra = 0;

    if (hasTopping) {
        document.querySelectorAll('.dynamic-topping-chk:checked').forEach(chk => {
            let tPrice = parseFloat(chk.value);
            let tName = chk.getAttribute('data-name');
            selectedToppings.push(tName);
            toppingsExtra += tPrice;
        });
    }

    let note = document.getElementById('item-note').value.trim();
    let basePrice = parseFloat(selectedProductForModal.price);
    if (currentOrderType === 'online') {
        basePrice = Math.round(basePrice * 1.1);
    }
    let finalPrice = basePrice + sizeExtra + toppingsExtra;
    let isFoodOrDirect = !hasSize && !hasSugar && !hasIce;

    cart.push({
        id: selectedProductForModal.id,
        category_id: selectedProductForModal.category_id,
        name: selectedProductForModal.name,
        price: finalPrice,
        manage_stock: parseInt(selectedProductForModal.manage_stock || 0),
        stock_quantity: parseFloat(selectedProductForModal.stock_quantity || 0),
        expiry_date: selectedProductForModal.expiry_date || '',
        size: sizeName,
        sugar: sugar,
        ice: ice,
        toppings: selectedToppings,
        note: note,
        isCake: isFoodOrDirect,
        qty: 1,
        printedQty: 0
    });

    if (currentTableId) {
        activeOrders[currentTableId] = cart;
    }

    optionModal.hide();
    renderCart();

    if (currentOrderType === 'takeaway') renderTakeawayCards();
    if (currentOrderType === 'online') renderOnlineCards();
}

function renderCart() {
    const container = document.getElementById('cart-items');
    let countSubEl = document.getElementById('cart-item-count-sub');
    let totalItemsCount = cart.reduce((sum, i) => sum + i.qty, 0);
    if (countSubEl) countSubEl.innerText = `(${totalItemsCount} món)`;

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted my-5 py-4">
                <span class="d-block text-secondary fw-semibold" style="font-size: 13px;">Vui lòng chọn bàn/thẻ vị trí và chọn món ăn</span>
            </div>`;
        updateTotals(0);
        return;
    }

    let html = '<div class="list-group list-group-flush">';
    let subTotal = 0;

    cart.forEach((item, index) => {
        let itemTotal = item.price * item.qty;
        subTotal += itemTotal;

        let printedCount = item.printedQty || 0;
        let isMinusDisabled = (item.qty <= printedCount) ? 'disabled' : '';

        let optionsText = item.isCake ? '' : [item.size ? `Size ${item.size}` : '', item.sugar, item.ice].filter(Boolean).join(' • ');
        if (item.toppings && item.toppings.length > 0) {
            optionsText += (optionsText ? ' • ' : '') + '+ ' + item.toppings.join(', ');
        }
        if (item.note) optionsText += (optionsText ? ` (${item.note})` : `${item.note}`);

        html += `
            <div class="list-group-item px-0 py-2 border-bottom">
                <div class="d-flex justify-content-between align-items-start">
                    <div style="max-width: 62%;">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 13.5px;">${item.name}</div>
                        ${optionsText ? `<small class="text-muted d-block" style="font-size: 11px;">${optionsText}</small>` : ''}
                        <small class="text-secondary fw-semibold" style="font-size: 11px;">${item.price.toLocaleString()} đ / món</small>
                        ${printedCount > 0 ? `<small class="badge bg-secondary opacity-75 d-inline-block ms-1" style="font-size:9px;">Đã in tem: ${printedCount}</small>` : ''}
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-dark mb-1" style="font-size: 13.5px;">${itemTotal.toLocaleString()} đ</div>
                        <div class="d-flex align-items-center justify-content-end gap-1">
                            <button class="btn btn-sm btn-light border btn-qty" ${isMinusDisabled} onclick="changeQty(${index}, -1)">-</button>
                            <span class="fw-bold px-2" style="font-size: 13px;">${item.qty}</span>
                            <button class="btn btn-sm btn-light border btn-qty" onclick="changeQty(${index}, 1)">+</button>
                            ${printedCount > 0 ? 
                                `<span class="text-muted opacity-50 ms-1" title="Món ăn đã khóa lệnh báo bếp"><i class="bi bi-lock-fill"></i></span>` : 
                                `<button class="btn btn-sm text-danger border-0 ms-1 fw-bold p-0 px-1" onclick="removeItem(${index})">✕</button>`
                            }
                        </div>
                    </div>
                </div>
            </div>`;
    });

    html += '</div>';
    container.innerHTML = html;
    updateTotals(subTotal);
}

function changeQty(index, delta) {
    let item = cart[index];
    let printedCount = item.printedQty || 0;

    if (delta < 0 && item.qty <= printedCount) {
        showCustomAlert("Sản phẩm đã được gửi lệnh in tem chế biến, không thể giảm số lượng!", "Quy Trình Khóa Đơn", "danger");
        return;
    }

    const applyQtyChange = () => {
        item.qty += delta;
        if (item.qty <= 0) cart.splice(index, 1);
        if (currentTableId) activeOrders[currentTableId] = cart;
        renderCart();

        if (currentOrderType === 'takeaway') renderTakeawayCards();
        if (currentOrderType === 'online') renderOnlineCards();
    };

    if (delta > 0) {
        checkStockAndExpiryWarning(item, delta, applyQtyChange);
    } else {
        applyQtyChange();
    }
}

function removeItem(index) {
    let item = cart[index];
    if (item.printedQty && item.printedQty > 0) {
        showCustomAlert("Sản phẩm đã được gửi lệnh in tem chế biến, không thể hủy khỏi hóa đơn!", "Quy Trình Khóa Đơn", "danger");
        return;
    }
    cart.splice(index, 1);
    if (currentTableId) activeOrders[currentTableId] = cart;
    renderCart();

    if (currentOrderType === 'takeaway') renderTakeawayCards();
    if (currentOrderType === 'online') renderOnlineCards();
}

function clearCart() {
    let hasPrintedItems = cart.some(item => item.printedQty && item.printedQty > 0);
    if (hasPrintedItems) {
        showCustomAlert("Hóa đơn đã có sản phẩm gửi lệnh in tem chế biến. Không thể thực hiện hủy toàn bộ!", "Quy Trình Khóa Đơn", "danger");
        return;
    }
    cart = [];
    confirmedStockBypassIds.clear();
    if (currentTableId) delete activeOrders[currentTableId];
    renderCart();

    if (currentOrderType === 'takeaway') renderTakeawayCards();
    if (currentOrderType === 'online') renderOnlineCards();
}

// TÍNH TỔNG TIỀN & MINH BẠCH ƯU ĐÃI HẠNG THẺ + ĐỔI ĐIỂM
function updateTotals(subTotal) {
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;

    let pointsDiscountMoney = 0;
    let tierDiscountMoney = 0;
    let tierPercent = 0;
    let tierName = '';

    if (currentOrderType !== 'online') {
        pointsDiscountMoney = appliedPointsDiscount;
        let remainingAfterPoints = Math.max(0, baseGrandTotal - pointsDiscountMoney);

        if (selectedCustomer) {
            tierName = selectedCustomer.member_rank || 'Bronze';
            tierPercent = parseFloat(selectedCustomer.discount_percent || 0);
            if (tierPercent > 0) {
                tierDiscountMoney = Math.round(remainingAfterPoints * (tierPercent / 100));
            }
        }
    }

    let totalDiscount = pointsDiscountMoney + tierDiscountMoney;
    let grandTotal = Math.max(0, Math.round(baseGrandTotal - totalDiscount));

    document.getElementById('sub-total').innerText = subTotal.toLocaleString() + " đ";
    document.getElementById('vat-total').innerText = Math.round(vat).toLocaleString() + " đ";

    const tierRow = document.getElementById('tier-discount-row');
    if (tierRow) {
        if (tierDiscountMoney > 0 && selectedCustomer) {
            tierRow.classList.remove('d-none');
            document.getElementById('tier-discount-label').innerText = `Ưu đãi hạng ${tierName} (-${tierPercent}%):`;
            document.getElementById('tier-discount-val').innerText = "-" + tierDiscountMoney.toLocaleString() + " đ";
        } else {
            tierRow.classList.add('d-none');
        }
    }

    const ptsRow = document.getElementById('points-discount-row');
    if (ptsRow) {
        if (pointsDiscountMoney > 0) {
            ptsRow.classList.remove('d-none');
            document.getElementById('points-discount-val').innerText = "-" + pointsDiscountMoney.toLocaleString() + " đ";
        } else {
            ptsRow.classList.add('d-none');
        }
    }

    document.getElementById('grand-total').innerText = grandTotal.toLocaleString() + " đ";

    const earnedPtsEl = document.getElementById('order-earned-points-val');
    if (earnedPtsEl) {
        let earnedPts = (currentOrderType !== 'online') ? Math.floor(grandTotal / 10000) : 0;
        earnedPtsEl.innerText = `+${earnedPts} điểm`;
    }

    updateCheckoutButton();
}

// =========================================================================
// POPUP KHÁCH HÀNG THÂN THIẾT (CRM MODAL): QUÀ PHÂN CẤP THEO HẠNG & LÀM TRÒN TIỀN
// =========================================================================
const TIER_LEVEL_ORDER = { Bronze: 1, Silver: 2, Gold: 3, Diamond: 4, VIP: 5 };

const TIER_REWARD_ITEMS = [
    { id: 'GIFT_TOPPING',    name: '1 Phần Topping Tự Chọn',      points: 20,  minRank: 'Bronze',  desc: 'Áp dụng cho tất cả hội viên từ hạng Bronze' },
    { id: 'GIFT_COOKIE',     name: 'Bánh Quy Bơ Pháp Giòn Thơm',   points: 50,  minRank: 'Bronze',  desc: 'Quà tặng tiêu chuẩn hội viên Bronze' },
    { id: 'GIFT_DRINK_M',    name: '1 Ly Trà Sữa / Cà Phê Size M', points: 150, minRank: 'Silver',  desc: 'Mở khóa từ hạng Silver trở lên' },
    { id: 'GIFT_PANNACOTTA', name: 'Panna Cotta Dâu Tây',          points: 180, minRank: 'Silver',  desc: 'Món tráng miệng đặc quyền hạng Silver' },
    { id: 'GIFT_COMBO_GOLD', name: 'Combo Trà Trái Cây & Bánh',    points: 300, minRank: 'Gold',    desc: 'Quà nâng hạng độc quyền cho hội viên Gold' },
    { id: 'GIFT_THERMOS',    name: 'Bình Giữ Nhiệt Smart F&B',     points: 500, minRank: 'Diamond', desc: 'Quà hiện vật cao cấp dành cho hội viên Diamond' },
    { id: 'GIFT_MASCOT_VIP', name: 'Gấu Bông Linh Vật Phiên Bản VIP', points: 800, minRank: 'VIP',  desc: 'Đặc quyền thượng lưu chỉ dành cho Diamond / VIP' }
];

function getBillAmountBeforePointRedeem() {
    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;
    let tierDiscountMoney = 0;

    if (selectedCustomer && currentOrderType !== 'online') {
        let tierPercent = parseFloat(selectedCustomer.discount_percent || 0);
        if (tierPercent > 0) {
            tierDiscountMoney = Math.round(baseGrandTotal * (tierPercent / 100));
        }
    }
    return Math.max(0, Math.round(baseGrandTotal - tierDiscountMoney));
}

function openCrmMemberModal() {
    if (!selectedCustomer) {
        showCustomAlert("Vui lòng tra cứu khách hàng thành viên trước!", "Chưa Chọn Khách Hàng", "info");
        return;
    }

    let cName = selectedCustomer.customer_name || 'Khách hàng';
    let cPhone = selectedCustomer.phone || '';
    let cRank = selectedCustomer.member_rank || 'Bronze';
    let discountPct = parseFloat(selectedCustomer.discount_percent || 0);
    let availPts = Math.max(0, parseInt(selectedCustomer.total_points || 0) - pointsToRedeem);
    let cSpent = parseFloat(selectedCustomer.lifetime_spend || selectedCustomer.total_spent || (parseInt(selectedCustomer.total_points || 0) * 10000));

    document.getElementById('crm-modal-cust-name').innerText = cName;
    document.getElementById('crm-modal-cust-phone').innerText = cPhone;
    document.getElementById('crm-modal-avail-pts').innerText = availPts.toLocaleString() + " pt";
    document.getElementById('crm-modal-spent-val').innerText = cSpent.toLocaleString() + " đ";
    document.getElementById('crm-modal-rank-pill').innerText = `${cRank.toUpperCase()}${discountPct > 0 ? ` (-${discountPct}%)` : ''}`;

    // Đổi màu banner thẻ thành viên theo hạng
    let banner = document.getElementById('crm-modal-card-banner');
    if (banner) {
        if (cRank === 'VIP' || cRank === 'Diamond') {
            banner.style.background = 'linear-gradient(135deg, #312e81 0%, #4338ca 100%)';
        } else if (cRank === 'Gold') {
            banner.style.background = 'linear-gradient(135deg, #78350f 0%, #b45309 100%)';
        } else if (cRank === 'Silver') {
            banner.style.background = 'linear-gradient(135deg, #334155 0%, #64748b 100%)';
        } else {
            banner.style.background = 'linear-gradient(135deg, #451a03 0%, #78350f 100%)';
        }
    }

    let billPreview = getBillAmountBeforePointRedeem();
    document.getElementById('crm-modal-bill-total').innerText = billPreview.toLocaleString() + " đ";
    document.getElementById('manual-redeem-pts').value = (appliedPointsDiscount > 0) ? Math.round(appliedPointsDiscount / 100) : '';

    renderTierRewardsInModal(cRank, availPts);
    if (crmMemberModal) crmMemberModal.show();
}

// TỰ ĐỘNG LÀM TRÒN TIỀN BẰNG ĐIỂM (VD: 105.500đ -> tự dùng 5 điểm trừ 500đ còn 105.000đ)
function autoRoundBillWithPoints() {
    if (!selectedCustomer) return;

    let availPts = Math.max(0, parseInt(selectedCustomer.total_points || 0) - pointsToRedeem);
    let billAmount = getBillAmountBeforePointRedeem();
    let hintEl = document.getElementById('crm-auto-round-hint');

    if (billAmount <= 0 || availPts <= 0) {
        if (hintEl) hintEl.innerHTML = `<span class="text-danger fw-semibold">Không đủ điểm tiêu dùng hoặc đơn hàng đang bằng 0đ.</span>`;
        return;
    }

    // Lấy phần tiền lẻ hàng trăm/nghìn (ưu tiên làm tròn về hàng nghìn, nếu đã chẵn nghìn thì làm tròn về hàng chục nghìn)
    let oddAmount = billAmount % 1000;
    if (oddAmount === 0) {
        oddAmount = billAmount % 10000;
    }

    if (oddAmount === 0) {
        if (hintEl) hintEl.innerHTML = `<span class="text-success fw-semibold">Hóa đơn đã tròn số đẹp (${billAmount.toLocaleString()} đ)!</span>`;
        return;
    }

    let neededPts = Math.ceil(oddAmount / 100);
    let ptsToUse = Math.min(neededPts, availPts);
    let discountMoney = ptsToUse * 100;
    let finalRoundedBill = Math.max(0, billAmount - discountMoney);

    document.getElementById('manual-redeem-pts').value = ptsToUse;
    if (hintEl) {
        hintEl.innerHTML = `<span class="text-primary fw-semibold">Tự động tính: Dùng <b>${ptsToUse} pt</b> (-${discountMoney.toLocaleString()}đ) → Bill tròn còn <b>${finalRoundedBill.toLocaleString()} đ</b></span>`;
    }
}

function applyManualPointsRedeem() {
    if (!selectedCustomer) return;

    let inputPts = parseInt(document.getElementById('manual-redeem-pts').value || 0);
    let giftPtsUsed = redeemedGiftsList.reduce((sum, g) => sum + g.points, 0);
    let availablePts = Math.max(0, parseInt(selectedCustomer.total_points || 0) - giftPtsUsed);

    if (isNaN(inputPts) || inputPts <= 0) {
        showCustomAlert("Vui lòng nhập số điểm tiêu dùng muốn áp dụng hợp lệ!", "Lỗi Nhập Điểm", "danger");
        return;
    }

    if (inputPts > availablePts) {
        showCustomAlert(`Khách hàng chỉ còn ${availablePts} điểm tiêu dùng khả dụng!`, "Không Đủ Điểm", "danger");
        return;
    }

    let billBeforePts = getBillAmountBeforePointRedeem();
    let discountMoney = inputPts * 100;

    if (discountMoney > billBeforePts) {
        discountMoney = billBeforePts;
        inputPts = Math.ceil(billBeforePts / 100);
    }

    appliedPointsDiscount = discountMoney;
    pointsToRedeem = giftPtsUsed + inputPts;

    document.getElementById('redeem-points-input').value = pointsToRedeem;
    document.getElementById('cust-points-val').innerText = Math.max(0, parseInt(selectedCustomer.total_points || 0) - pointsToRedeem).toLocaleString();

    let msgEl = document.getElementById('loyalty-discount-msg');
    if (msgEl) {
        msgEl.innerText = `• Trừ -${appliedPointsDiscount.toLocaleString()}đ`;
        msgEl.classList.remove('d-none');
    }

    if (crmMemberModal) crmMemberModal.hide();
    renderCart();
}

function clearPointsRedeem() {
    let giftPtsUsed = redeemedGiftsList.reduce((sum, g) => sum + g.points, 0);
    appliedPointsDiscount = 0;
    pointsToRedeem = giftPtsUsed;
    document.getElementById('manual-redeem-pts').value = '';
    document.getElementById('redeem-points-input').value = pointsToRedeem;
    if (selectedCustomer) {
        document.getElementById('cust-points-val').innerText = Math.max(0, parseInt(selectedCustomer.total_points || 0) - pointsToRedeem).toLocaleString();
    }
    let msgEl = document.getElementById('loyalty-discount-msg');
    if (msgEl) msgEl.classList.add('d-none');
    renderCart();
}

function renderTierRewardsInModal(customerRank, availPts) {
    let container = document.getElementById('crm-tier-rewards-container');
    if (!container) return;

    let userLevel = TIER_LEVEL_ORDER[customerRank] || 1;
    container.innerHTML = TIER_REWARD_ITEMS.map(item => {
        let reqLevel = TIER_LEVEL_ORDER[item.minRank] || 1;
        let isUnlocked = userLevel >= reqLevel;
        let canRedeem = isUnlocked && availPts >= item.points;

        return `
            <div class="d-flex justify-content-between align-items-center p-2 border rounded-3 ${isUnlocked ? 'bg-white' : 'bg-light opacity-75'}">
                <div class="pe-2">
                    <div class="d-flex align-items-center gap-1">
                        <span class="fw-bold text-dark" style="font-size: 12.5px;">${item.name}</span>
                        <span class="badge ${isUnlocked ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary'}" style="font-size: 9.5px;">${item.minRank}</span>
                    </div>
                    <small class="text-muted d-block" style="font-size: 11px;">${item.desc}</small>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="fw-bold text-primary mb-1" style="font-size: 11.5px;">${item.points} pt</div>
                    ${
                        !isUnlocked
                        ? `<span class="badge bg-secondary text-white" style="font-size: 10px;"><i class="bi bi-lock-fill me-1"></i>Cần hạng ${item.minRank}</span>`
                        : `<button type="button" class="btn btn-sm ${canRedeem ? 'btn-warning text-dark' : 'btn-outline-secondary'} fw-bold rounded-pill px-3 saas-btn-nowrap"
                                style="font-size: 11px;" ${canRedeem ? '' : 'disabled'}
                                onclick="redeemPhysicalGift('${item.name}', ${item.points})">
                                ${canRedeem ? 'Đổi Quà' : 'Thiếu điểm'}
                           </button>`
                    }
                </div>
            </div>
        `;
    }).join('');
}

function redeemPhysicalGift(giftName, costPoints) {
    if (!selectedCustomer) return;
    let totalCustPts = parseInt(selectedCustomer.total_points || 0);
    let totalUsed = pointsToRedeem + costPoints;

    if (totalUsed > totalCustPts) {
        showCustomAlert(`Không đủ điểm tiêu dùng! Khách có ${totalCustPts} điểm, cần ${totalUsed} điểm.`, "Không Đủ Điểm", "danger");
        return;
    }

    pointsToRedeem += costPoints;
    redeemedGiftsList.push({ name: giftName, points: costPoints });

    cart.push({
        id: 'GIFT-' + Date.now(),
        category_id: 0,
        name: `[QUÀ TẶNG] ${giftName}`,
        price: 0,
        size: '',
        sugar: '',
        ice: '',
        toppings: [],
        note: `Đổi ${costPoints} điểm`,
        isCake: true,
        is_gift: true,
        qty: 1,
        printedQty: 0
    });

    if (currentTableId) activeOrders[currentTableId] = cart;
    document.getElementById('redeem-points-input').value = pointsToRedeem;
    document.getElementById('cust-points-val').innerText = Math.max(0, totalCustPts - pointsToRedeem).toLocaleString();
    if (crmMemberModal) crmMemberModal.hide();
    renderCart();
    showCustomAlert(`Đã đổi quà [${giftName}] (-${costPoints} điểm tiêu dùng). Hạng thẻ của khách vẫn được giữ nguyên!`, "Đổi Quà Thành Công", "success");
}

function adminUpdateVatSetting() {
    if (currentUserRole !== 'admin') {
        showCustomAlert("Chỉ tài khoản Admin mới có quyền điều chỉnh Thuế VAT hệ thống!", "Không Có Quyền", "danger");
        return;
    }

    document.getElementById('vat-setting-input').value = currentVatPercent;
    if (vatSettingModal) {
        vatSettingModal.show();
    }
}

async function saveVatSettingFromModal() {
    let inputVal = document.getElementById('vat-setting-input').value;
    let newVat = parseFloat(inputVal);

    if (isNaN(newVat) || newVat < 0 || newVat > 100) {
        showCustomAlert("% VAT nhập vào không hợp lệ! Vui lòng nhập từ 0 đến 100.", "Lỗi Dữ Liệu", "danger");
        return;
    }

    try {
        let res = await fetch(BASE_URL_JS + '/order/updateVatSetting', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ vat_percent: newVat })
        }).then(r => r.json());

        if (vatSettingModal) vatSettingModal.hide();

        if (res.success) {
            currentVatPercent = newVat;
            let vatLbl = document.getElementById('vat-label-display');
            if (vatLbl) vatLbl.innerText = currentVatPercent;

            renderCart();
            showCustomAlert(res.message, "Thành Công", "success");
        } else {
            showCustomAlert(res.message || "Không thể cập nhật Thuế VAT!", "Lỗi Cập Nhật", "danger");
        }
    } catch (e) {
        showCustomAlert("Lỗi kết nối máy chủ khi cập nhật VAT!", "Lỗi Hệ Thống", "danger");
    }
}

function updateCheckoutButton() {
    const btnCheckout = document.getElementById('btn-checkout');
    const btnPayDirect = document.getElementById('btn-pay-direct');
    const btnKitchen = document.getElementById('btn-kitchen');
    const btnReady = document.getElementById('btn-ready-notify');
    
    let isValid = (currentTableId !== null) && cart.length > 0;
    if (btnCheckout) btnCheckout.disabled = !isValid;
    if (btnPayDirect) btnPayDirect.disabled = !isValid;
    if (btnKitchen) btnKitchen.disabled = !isValid;
    if (btnReady) btnReady.disabled = !isValid;
}

function sendToKitchen() {
    if (cart.length === 0 || !currentTableId) return;

    activeOrders[currentTableId] = cart;

    if (typeof currentTableId === 'number') {
        let tableBadge = document.getElementById('table-btn-' + currentTableId);
        if (tableBadge) {
            tableBadge.className = "table-badge status-occupied active";
            tableBadge.querySelector('.status-label').innerText = "Có khách";
            syncTableStatusToDB(currentTableId, 'occupied');
        }
    }

    let itemsToPrint = [];
    cart.forEach(item => {
        let newQty = item.qty - (item.printedQty || 0);
        if (newQty > 0) {
            itemsToPrint.push({ item: item, printCount: newQty });
        }
    });

    if (itemsToPrint.length === 0) {
        showCustomAlert("Tất cả sản phẩm trong hóa đơn đã gửi lệnh in tem chế biến trước đó!", "Thông Báo", "info");
        return;
    }

    let stickerListHtml = '';
    itemsToPrint.forEach(p => {
        let item = p.item;
        for (let i = 0; i < p.printCount; i++) {
            let toppingStr = item.toppings && item.toppings.length > 0 ? item.toppings.join(', ') : '';
            let subOpts = [item.size ? `Size: ${item.size}` : '', item.sugar, item.ice].filter(Boolean).join(' | ');
            stickerListHtml += `
                <div class="sticker-mockup text-start shadow-sm mb-2">
                    <div class="d-flex justify-content-between border-bottom pb-1 mb-1 fw-bold" style="font-size: 11px;">
                        <span>VỊ TRÍ: ${currentTableId}</span>
                        <span>${new Date().toLocaleTimeString()}</span>
                    </div>
                    <div class="fw-bold fs-6 text-truncate">${item.name}</div>
                    ${subOpts ? `<div style="font-size: 11px;">${subOpts}</div>` : ''}
                    ${toppingStr ? `<div style="font-size: 11px; font-weight: bold;">+ ${toppingStr}</div>` : ''}
                    ${item.note ? `<div style="font-size: 11px; font-style: italic;">Ghi chú: ${item.note}</div>` : ''}
                </div>`;
        }
    });

    document.getElementById('sticker-list-content').innerHTML = stickerListHtml;
    stickerPrintModal.show();
}

function executeStickerPrint() {
    cart.forEach(item => {
        item.printedQty = item.qty;
    });

    stickerPrintModal.hide();
    renderCart();
    showCustomAlert("Lệnh in tem chế biến dán ly đã được gửi thành công!", "Thành Công", "success");
}

function notifyKitchenReady() {
    if (!currentTableId || cart.length === 0) return;
    let pagerNumber = Math.floor(Math.random() * 20) + 1;
    showCustomAlert(`Phát tín hiệu thông báo hoàn tất món ăn cho Thẻ báo số [${pagerNumber}]!`, "Thông Báo Khách Hàng", "info");
}

function setQuickCashAmount(amount) {
    const cashInput = document.getElementById('cash-given');
    if (!cashInput) return;
    if (amount === 'exact') {
        const payText = document.getElementById('modal-pay-amount')?.innerText || '0';
        cashInput.value = parseInt(payText.replace(/[^\d]/g, ''), 10) || 0;
    } else {
        cashInput.value = amount;
    }
    calculateChange();
}

function openCheckoutModal(isDirect = false) {
    if (cart.length === 0 || !currentTableId) return;

    isPayDirectMode = isDirect;

    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;

    let pointsDiscountMoney = 0;
    let tierDiscountMoney = 0;

    if (currentOrderType !== 'online') {
        pointsDiscountMoney = appliedPointsDiscount;
        let remainingAfterPoints = Math.max(0, baseGrandTotal - pointsDiscountMoney);

        if (selectedCustomer) {
            let discountPercent = selectedCustomer.discount_percent || 0;
            if (discountPercent > 0) tierDiscountMoney = Math.round(remainingAfterPoints * (discountPercent / 100));
        }
    }
    let totalDiscount = pointsDiscountMoney + tierDiscountMoney;
    let grandTotal = Math.max(0, Math.round(baseGrandTotal - totalDiscount));

    document.getElementById('modal-pay-amount').innerText = grandTotal.toLocaleString() + " đ";

    const standardBox = document.getElementById('checkout-standard-payment-box');
    const onlineBox = document.getElementById('checkout-online-app-box');
    const confirmBtn = document.getElementById('btn-confirm-checkout-action');

    if (currentOrderType === 'online') {
        standardBox.classList.add('d-none');
        onlineBox.classList.remove('d-none');
        document.getElementById('app-order-code-input').value = '';
        document.getElementById('app-driver-name-input').value = '';
        document.getElementById('app-driver-phone-input').value = '';
        document.getElementById('app-commission-rate-select').value = '20';
        calculateAppNetPrice();

        document.getElementById('checkoutModalTitle').innerHTML = `<i class="bi bi-box-seam text-success"></i> XÁC NHẬN ĐƠN APP ${onlinePlatform.toUpperCase()} & IN BILL`;
        confirmBtn.innerHTML = `<i class="bi bi-printer"></i> XÁC NHẬN & IN HÓA ĐƠN GIAO HÀNG`;

    } else {
        standardBox.classList.remove('d-none');
        onlineBox.classList.add('d-none');
        document.getElementById('cash-given').value = isDirect ? grandTotal : '';
        calculateChange();
        document.getElementById('inline-payment-error').classList.add('d-none');

        let titleText = isDirect ? "THANH TOÁN NHANH TẠI CHỖ (GIỮ BÀN)" : "THANH TOÁN ĐA PHƯƠNG THỨC & TRẢ BÀN";
        document.getElementById('checkoutModalTitle').innerHTML = `<i class="bi bi-credit-card-2-front text-primary"></i> ${titleText}`;
        confirmBtn.innerHTML = `<i class="bi bi-printer"></i> HOÀN TẤT THANH TOÁN & IN HÓA ĐƠN`;
    }

    let itemsHtml = '';
    cart.forEach(item => {
        let subLine = [item.sugar, item.ice].filter(Boolean).join(', ');
        itemsHtml += `
            <div class="d-flex justify-content-between py-1 border-bottom-dashed">
                <div style="max-width: 65%;">
                    <div>${item.qty}x ${item.name} ${item.size ? `(Size ${item.size})` : ''}</div>
                    ${subLine ? `<small style="font-size: 9px;">${subLine}</small>` : ''}
                    ${item.toppings && item.toppings.length > 0 ? `<br><small style="font-size: 9px;">+ ${item.toppings.join(', ')}</small>` : ''}
                </div>
                <div>${(item.price * item.qty).toLocaleString()}đ</div>
            </div>`;
    });

    let receiptTitle = (currentOrderType === 'online') ? `HÓA ĐƠN ĐƠN APP (${onlinePlatform.toUpperCase()})` : `HÓA ĐƠN THANH TOÁN`;

    let receiptHtml = `
        <div class="text-center mb-2">
            <h6 class="fw-bold m-0">SMART F&B POS (${currentBranchId})</h6>
            <small style="font-size: 10px;">${receiptTitle}</small><br>
            <small style="font-size: 9px;">Thời gian: ${new Date().toLocaleString('vi-VN')}</small>
        </div>
        <div class="border-top border-bottom py-1 mb-2 fw-bold" style="font-size: 11px;">
            VỊ TRÍ: ${currentTableId} | Thu ngân: ${currentShiftInfo.cashierId}
        </div>
        <div class="mb-2">${itemsHtml}</div>
        <div class="border-top pt-1" style="font-size: 11px;">
            <div class="d-flex justify-content-between"><span>Tạm tính:</span><span>${subTotal.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between"><span>Thuế VAT (${currentVatPercent}%):</span><span>${Math.round(vat).toLocaleString()}đ</span></div>
            ${tierDiscountMoney > 0 ? `<div class="d-flex justify-content-between text-success"><span>Ưu đãi hạng thẻ:</span><span>-${tierDiscountMoney.toLocaleString()}đ</span></div>` : ''}
            ${pointsDiscountMoney > 0 ? `<div class="d-flex justify-content-between text-success"><span>Trừ điểm tiêu dùng:</span><span>-${pointsDiscountMoney.toLocaleString()}đ</span></div>` : ''}
            <div class="d-flex justify-content-between fw-bold fs-6 mt-1"><span>TỔNG TIỀN:</span><span>${grandTotal.toLocaleString()}đ</span></div>
        </div>
    `;

    document.getElementById('receipt-preview-content').innerHTML = receiptHtml;
    checkoutModal.show();
}

function calculateAppNetPrice() {
    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let grandTotal = Math.round(subTotal + vat);
    
    let rate = parseFloat(document.getElementById('app-commission-rate-select').value) || 0;

    let commissionFee = Math.round(grandTotal * (rate / 100));
    let netRevenue = grandTotal - commissionFee;

    document.getElementById('app-commission-fee-val').innerText = "-" + commissionFee.toLocaleString() + " đ";
    document.getElementById('app-net-revenue-val').innerText = netRevenue.toLocaleString() + " đ";
}

function calculateChange() {
    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;

    let pointsDiscountMoney = 0;
    let tierDiscountMoney = 0;

    if (currentOrderType !== 'online') {
        pointsDiscountMoney = appliedPointsDiscount;
        let remainingAfterPoints = Math.max(0, baseGrandTotal - pointsDiscountMoney);

        if (selectedCustomer) {
            let discountPercent = selectedCustomer.discount_percent || 0;
            if (discountPercent > 0) tierDiscountMoney = Math.round(remainingAfterPoints * (discountPercent / 100));
        }
    }
    let totalDiscount = pointsDiscountMoney + tierDiscountMoney;
    let grandTotal = Math.max(0, Math.round(baseGrandTotal - totalDiscount));

    let given = parseFloat(document.getElementById('cash-given').value) || 0;
    let change = given - grandTotal;

    const errorDiv = document.getElementById('inline-payment-error');

    if (given > 0 && change < 0) {
        errorDiv.classList.remove('d-none');
        document.getElementById('cash-change').innerText = "Còn thiếu " + Math.abs(change).toLocaleString() + " đ";
        document.getElementById('cash-change').className = "text-danger fw-bold";
    } else {
        errorDiv.classList.add('d-none');
        document.getElementById('cash-change').innerText = change >= 0 ? change.toLocaleString() + " đ" : "0 đ";
        document.getElementById('cash-change').className = "text-success fw-bold";
    }
}

function processCheckout() {
    if (cart.length === 0 || !currentTableId) return;

    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;

    let pointsDiscountMoney = 0;
    let tierDiscountMoney = 0;

    if (currentOrderType !== 'online') {
        pointsDiscountMoney = appliedPointsDiscount;
        let remainingAfterPoints = Math.max(0, baseGrandTotal - pointsDiscountMoney);

        if (selectedCustomer) {
            let discountPercent = selectedCustomer.discount_percent || 0;
            if (discountPercent > 0) tierDiscountMoney = Math.round(remainingAfterPoints * (discountPercent / 100));
        }
    }
    let totalDiscount = pointsDiscountMoney + tierDiscountMoney;
    let grandTotal = Math.max(0, Math.round(baseGrandTotal - totalDiscount));

    if (currentOrderType === 'online') {
        let appCode = document.getElementById('app-order-code-input').value.trim();
        if (!appCode) {
            showCustomAlert("Vui lòng nhập mã đơn tên sàn ứng dụng để đảm bảo quy trình đối soát!", "Thiếu Thông Tin", "danger");
            document.getElementById('app-order-code-input').focus();
            return;
        }

        let rate = parseFloat(document.getElementById('app-commission-rate-select').value) || 0;
        let driverName = document.getElementById('app-driver-name-input').value.trim() || 'Tài xế App';
        let driverPhone = document.getElementById('app-driver-phone-input').value.trim() || '';

        let extraData = {
            order_type: onlinePlatform,
            payment_method: 'app',
            app_order_code: appCode,
            driver_name: driverName,
            driver_phone: driverPhone,
            commission_rate: rate,
            vat_percent: currentVatPercent
        };

        sendOrderToMySQLBackend(extraData);

    } else {
        let given = parseFloat(document.getElementById('cash-given').value) || 0;
        let activeTab = document.querySelector('#pills-cash-tab.active');

        if (activeTab && given < grandTotal) {
            document.getElementById('inline-payment-error').classList.remove('d-none');
            document.getElementById('cash-given').focus();
            return;
        }

        let payMethod = activeTab ? 'cash' : 'banking';

        sendOrderToMySQLBackend({
            order_type: currentOrderType,
            payment_method: payMethod,
            vat_percent: currentVatPercent
        });
    }
}

function finishPaymentSuccess(paidGrandTotal, payMethod) {
    checkoutModal.hide();

    // Cập nhật hiển thị tồn kho trên thẻ sản phẩm tại UI sau khi trừ kho
    cart.forEach(soldItem => {
        if (soldItem.manage_stock === 1 && !soldItem.is_gift) {
            let cardEl = document.querySelector(`.product-item[data-product-id="${soldItem.id}"]`);
            if (cardEl) {
                let oldStock = parseFloat(cardEl.getAttribute('data-stock') || 0);
                let newStock = oldStock - soldItem.qty;
                cardEl.setAttribute('data-stock', newStock);
                let badgeEl = cardEl.querySelector('.stock-badge-label');
                if (badgeEl) {
                    badgeEl.innerText = newStock <= 0 ? `Hết kho (${newStock})` : `Còn ${newStock}`;
                    if (newStock <= 0) {
                        badgeEl.className = "badge bg-danger position-absolute bottom-0 start-0 m-1 stock-badge-label";
                        cardEl.querySelector('.product-card')?.classList.add('product-unavailable');
                    }
                }
            }
        }
    });

    shiftStats.totalRevenue += paidGrandTotal;
    if (currentOrderType === 'online') {
        shiftStats.onlineRevenue += paidGrandTotal;
        shiftStats.totalAppSales += paidGrandTotal;
    } else {
        shiftStats.offlineRevenue += paidGrandTotal;
        if (payMethod === 'cash') shiftStats.totalCashSales += paidGrandTotal;
        else shiftStats.totalBankSales += paidGrandTotal;
    }
    shiftStats.totalOrders += 1;

    cart.forEach(item => {
        let pCat = String(item.category_id || '');
        if ((DB_TOPPING_CAT_ID && pCat === String(DB_TOPPING_CAT_ID)) || pCat === '4' || pCat === '0') {
            return;
        }
        if (item.isCake || pCat === '5') {
            shiftStats.totalCakes += item.qty;
        } else {
            shiftStats.totalCups += item.qty;
        }
    });

    showCustomAlert("Hóa đơn đã được chốt, trừ tồn kho và lưu dữ liệu thành công!", "Thanh Toán Thành Công", "success");

    if (typeof currentTableId === 'number') {
        let tableBadge = document.getElementById('table-btn-' + currentTableId);
        let nextRes = getNextReservationForTable(currentTableId);
        
        if (isPayDirectMode) {
            if (tableBadge) {
                tableBadge.className = "table-badge status-paid";
                if (nextRes) {
                    tableBadge.querySelector('.status-label').innerText = `[Đã TT - Hẹn ${nextRes.time}]`;
                } else {
                    tableBadge.querySelector('.status-label').innerText = "Đã thanh toán";
                }
            }
            document.getElementById('btn-release-table').classList.remove('d-none');
        } 
        else {
            let nextStatus = 'available';
            if (turnaroundTablesMap[currentTableId]) {
                delete turnaroundTablesMap[currentTableId];
            }

            if (nextRes) {
                let now = new Date();
                let resDateTime = new Date(`${nextRes.date}T${nextRes.time}:00`);
                let diffMins = (resDateTime - now) / (1000 * 60);

                if (diffMins <= 60) {
                    nextStatus = 'reserved';
                    if (tableBadge) {
                        tableBadge.className = "table-badge status-reserved";
                        tableBadge.querySelector('.status-label').innerText = `Đã đặt (${nextRes.time})`;
                    }
                } else {
                    nextStatus = 'available';
                    if (tableBadge) {
                        tableBadge.className = "table-badge status-available";
                        tableBadge.querySelector('.status-label').innerText = `Trống (Hẹn ${nextRes.time})`;
                    }
                }
            } else {
                if (tableBadge) {
                    tableBadge.className = "table-badge status-available";
                    tableBadge.querySelector('.status-label').innerText = "Trống";
                }
            }

            syncTableStatusToDB(currentTableId, nextStatus);
            document.getElementById('btn-release-table').classList.add('d-none');
        }

        if (tableMergeSourcesMap[currentTableId]) {
            tableMergeSourcesMap[currentTableId].forEach(srcId => {
                let srcBadge = document.getElementById('table-btn-' + srcId);
                let srcNextRes = getNextReservationForTable(srcId);
                if (srcBadge) {
                    if (srcNextRes) {
                        srcBadge.className = "table-badge status-reserved";
                        srcBadge.querySelector('.status-label').innerText = `Đã đặt (${srcNextRes.time})`;
                        syncTableStatusToDB(srcId, 'reserved');
                    } else {
                        srcBadge.className = "table-badge status-available";
                        srcBadge.querySelector('.status-label').innerText = "Trống";
                        syncTableStatusToDB(srcId, 'available');
                    }
                }
                delete tableMergedTargetMap[srcId];
            });
            delete tableMergeSourcesMap[currentTableId];
        }
    }

    delete activeOrders[currentTableId];

    if (currentOrderType === 'takeaway') {
        activeTakeawayCards = activeTakeawayCards.filter(c => c !== currentTableId);
        if (activeTakeawayCards.length === 0) {
            takeawayCounter++;
            activeTakeawayCards.push("MV-" + String(takeawayCounter).padStart(2, '0'));
        }
    } else if (currentOrderType === 'online') {
        if (onlinePlatform === 'grab') {
            activeGrabCards = activeGrabCards.filter(c => c !== currentTableId);
            if (activeGrabCards.length === 0) {
                onlineGrabCounter++;
                activeGrabCards.push("GRAB-" + onlineGrabCounter);
            }
        } else {
            activeShopeeCards = activeShopeeCards.filter(c => c !== currentTableId);
            if (activeShopeeCards.length === 0) {
                onlineShopeeCounter++;
                activeShopeeCards.push("SHOPEE-" + onlineShopeeCounter);
            }
        }
    }

    cart = [];
    confirmedStockBypassIds.clear();
    renderCart();
    detachCustomer();

    switchOrderType(currentOrderType);
    fetchReservationsFromDB();
}

function openOrderHistoryModal() {
    let tbody = document.getElementById('order-history-list');
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center text-muted py-4">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải lịch sử đơn hàng trong ca...
            </td>
        </tr>`;

    orderHistoryModal.show();

    let url = BASE_URL_JS + '/order/getOrderHistory';
    if (currentShiftInfo.shift_id) {
        url += '?shift_id=' + currentShiftInfo.shift_id;
    }

    fetch(url)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.orders && res.orders.length > 0) {
                tbody.innerHTML = '';
                completedOrdersHistory = res.orders;

                res.orders.forEach((order, index) => {
                    let orderDisplayCode = order.order_code || ('Đơn #' + order.id);
                    let tableDisplay = order.table_id ? ('Bàn ' + order.table_id) : 'Mang về/App';
                    let dtObj = new Date(order.created_at);
                    let orderDate = dtObj.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
                    let orderTime = dtObj.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                    let finalAmount = parseFloat(order.final_amount || 0);

                    let typeBadge = '<span class="badge bg-dark text-white fw-bold px-2 py-1">Tại chỗ</span>';
                    if (order.order_type === 'takeaway') {
                        typeBadge = '<span class="badge bg-warning text-dark fw-bold px-2 py-1">Mang về</span>';
                    } else if (order.order_type === 'grab') {
                        typeBadge = '<span class="badge bg-success text-white fw-bold px-2 py-1">GRAB</span>';
                    } else if (order.order_type === 'shopee') {
                        typeBadge = '<span class="badge badge-shopee text-white fw-bold px-2 py-1" style="background-color:#ff5722 !important;color:#ffffff !important;">SHOPEE</span>';
                    }

                    tbody.innerHTML += `
                        <tr>
                            <td class="ps-3 fw-bold text-dark">
                                ${orderDisplayCode}
                                <small class="d-block text-muted font-normal" style="font-size: 10px;">${tableDisplay}</small>
                            </td>
                            <td class="text-center">${typeBadge}</td>
                            <td class="text-muted"><i class="bi bi-calendar3 me-1"></i>${orderDate} ${orderTime}</td>
                            <td class="fw-bold text-danger">${finalAmount.toLocaleString()} đ</td>
                            <td class="text-center pe-3">
                                <button class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1 saas-btn-nowrap" onclick="openReprintBillModal(${index})">
                                    <i class="bi bi-printer"></i> In Lại Hóa Đơn
                                </button>
                            </td>
                        </tr>`;
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 text-secondary d-block mb-2"></i>
                            Chưa có đơn hàng nào được ghi nhận trong ca làm việc này.
                        </td>
                    </tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle me-1"></i> Không thể tải dữ liệu lịch sử!
                    </td>
                </tr>`;
        });
}

function openReprintBillModal(index) {
    selectedOrderForReprint = completedOrdersHistory[index];
    if (!selectedOrderForReprint) return;

    let items = selectedOrderForReprint.items || [];
    let itemsHtml = '';

    if (items.length > 0) {
        items.forEach(item => {
            let pName = item.name || item.product_name || 'Sản phẩm';
            let qty = item.qty || item.quantity || 1;
            let price = parseFloat(item.price || 0);
            let isCake = item.isCake || false;

            let sizeText = (isCake || !item.size) ? '' : ` (Size ${item.size})`;
            let sugarIceText = (isCake || (!item.sugar && !item.ice)) ? '' : `<small style="font-size: 9px; display:block; color:#555;">${[item.sugar, item.ice].filter(Boolean).join(', ')}</small>`;
            
            let toppingText = '';
            if (item.toppings && item.toppings.length > 0) {
                let topList = Array.isArray(item.toppings) ? item.toppings.join(', ') : item.toppings;
                toppingText = `<small style="font-size: 9px; display:block; color:#555;">+ ${topList}</small>`;
            }

            let noteText = item.note ? `<small style="font-size: 9px; display:block; font-style:italic; color:#666;">Note: ${item.note}</small>` : '';

            itemsHtml += `
                <div class="d-flex justify-content-between py-1 border-bottom-dashed text-start">
                    <div style="max-width: 65%;">
                        <div><strong>${qty}x</strong> ${pName}${sizeText}</div>
                        ${sugarIceText}
                        ${toppingText}
                        ${noteText}
                    </div>
                    <div class="fw-semibold">${(price * qty).toLocaleString()}đ</div>
                </div>`;
        });
    } else {
        itemsHtml = `<div class="text-muted text-center py-2" style="font-size:11px;">(Chi tiết sản phẩm đã được lưu trong hệ thống)</div>`;
    }

    let subTotal = parseFloat(selectedOrderForReprint.total_amount || 0);
    let finalAmount = parseFloat(selectedOrderForReprint.final_amount || 0);
    let billVatPercent = parseFloat(selectedOrderForReprint.vat_percent || currentVatPercent);
    
    let vat = subTotal > 0 ? (subTotal * (billVatPercent / 100)) : 0;
    let discount = parseFloat(selectedOrderForReprint.discount || 0);

    if (subTotal === 0 && finalAmount > 0) {
        subTotal = Math.round(finalAmount / (1 + billVatPercent / 100));
        vat = finalAmount - subTotal;
    }

    let receiptTitle = (selectedOrderForReprint.order_type === 'grab' || selectedOrderForReprint.order_type === 'shopee')
        ? `HÓA ĐƠN ĐƠN APP (${(selectedOrderForReprint.order_type).toUpperCase()})`
        : `HÓA ĐƠN THANH TOÁN (SAO BẢN)`;

    let tableInfo = selectedOrderForReprint.table_id ? `BÀN ${selectedOrderForReprint.table_id}` : (selectedOrderForReprint.order_type || 'Mang về').toUpperCase();

    let receiptHtml = `
        <div class="text-center mb-2">
            <h6 class="fw-bold m-0">SMART F&B POS</h6>
            <small style="font-size: 10px;">${receiptTitle}</small><br>
            <small style="font-size: 9px;">Thời gian: ${new Date(selectedOrderForReprint.created_at || Date.now()).toLocaleString('vi-VN')}</small>
        </div>
        <div class="border-top border-bottom py-1 mb-2 fw-bold text-start" style="font-size: 11px;">
            VỊ TRÍ: ${tableInfo} | Mã Đơn: ${selectedOrderForReprint.order_code || selectedOrderForReprint.id}
        </div>
        <div class="mb-2">${itemsHtml}</div>
        <div class="border-top pt-1 text-start" style="font-size: 11px;">
            <div class="d-flex justify-content-between"><span>Tạm tính:</span><span>${subTotal.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between"><span>Thuế VAT (${billVatPercent}%):</span><span>${Math.round(vat).toLocaleString()}đ</span></div>
            ${discount > 0 ? `<div class="d-flex justify-content-between text-success"><span>Chiết khấu / Giảm giá:</span><span>-${discount.toLocaleString()}đ</span></div>` : ''}
            <div class="d-flex justify-content-between fw-bold fs-6 mt-1 text-dark border-top pt-1"><span>TỔNG TIỀN:</span><span class="text-danger">${finalAmount.toLocaleString()}đ</span></div>
        </div>
    `;

    document.getElementById('reprint-receipt-content').innerHTML = receiptHtml;
    orderHistoryModal.hide();
    reprintBillModal.show();
}

function confirmReprintBill() {
    reprintBillModal.hide();
    showCustomAlert(`Lệnh in lại hóa đơn [${selectedOrderForReprint.order_code || selectedOrderForReprint.id}] đã được gửi thành công!`, "Thành Công", "success");
}

// ================= QUẢN LÝ ĐẶT BÀN NÂNG CẤP =================
function openReservationManagerModal() {
    let targetSelect = document.getElementById('reserve-target-table');
    targetSelect.innerHTML = '<option value="">-- Chọn bàn đặt --</option>';

    fetch(BASE_URL_JS + '/order/getReservations')
        .then(res => res.json())
        .then(res => {
            if (res.success && res.tables) {
                res.tables.forEach(table => {
                    let tid = table.table_id;
                    let listOnTable = Array.isArray(tableReservationData[tid]) ? tableReservationData[tid] : [];
                    let alertText = listOnTable.length > 0 ? ` (Đã có ${listOnTable.length} lịch hẹn)` : '';

                    targetSelect.innerHTML += `<option value="${tid}" data-count="${listOnTable.length}">
                        ${table.table_name || ('Bàn ' + tid)} ${alertText}
                    </option>`;
                });
            }
        });

    const now = new Date();
    const todayStr = now.toISOString().split('T')[0];
    const maxDate = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    const maxDateStr = maxDate.toISOString().split('T')[0];

    const dateInput = document.getElementById('reserve-date');
    dateInput.value = todayStr;
    dateInput.min = todayStr;
    dateInput.max = maxDateStr;

    const futureTime = new Date(now.getTime() + 2 * 60 * 60 * 1000 + 5 * 60 * 1000);
    const hoursStr = String(futureTime.getHours()).padStart(2, '0');
    const minsStr = String(futureTime.getMinutes()).padStart(2, '0');
    document.getElementById('reserve-time').value = `${hoursStr}:${minsStr}`;

    document.getElementById('reserve-customer-name').value = '';
    document.getElementById('reserve-customer-phone').value = '';
    document.getElementById('reserve-guest-count').value = '2';
    document.getElementById('reserve-note').value = '';
    document.getElementById('reservation-table-alert-box').classList.add('d-none');

    renderReservationTableList();

    let listTabBtn = new bootstrap.Tab(document.getElementById('res-tab-list-btn'));
    listTabBtn.show();

    reservationManagerModal.show();
}

function checkReservationTableWarning(selectElem) {
    let tid = selectElem.value;
    let alertBox = document.getElementById('reservation-table-alert-box');
    let listOnTable = Array.isArray(tableReservationData[tid]) ? tableReservationData[tid] : [];
    
    if (listOnTable.length > 0) {
        let slotsStr = listOnTable.map(r => `${r.time} (${r.date} - ${r.name})`).join(', ');
        alertBox.innerHTML = `<i class="bi bi-info-circle-fill me-1"></i> Bàn này đã có lịch đặt khung giờ: <strong>${slotsStr}</strong>. Hệ thống cho phép đặt thêm nếu cách nhau tối thiểu 90 phút.`;
        alertBox.classList.remove('d-none');
    } else {
        alertBox.classList.add('d-none');
    }
}

function renderReservationTableList() {
    let tbody = document.getElementById('reservation-manager-tbody');
    tbody.innerHTML = '';

    let allFlatReservations = [];
    Object.keys(tableReservationData).forEach(tid => {
        let list = Array.isArray(tableReservationData[tid]) ? tableReservationData[tid] : [];
        list.forEach(item => {
            allFlatReservations.push({ tid: tid, ...item });
        });
    });

    allFlatReservations.sort((a, b) => new Date(`${a.date}T${a.time}`) - new Date(`${b.date}T${b.time}`));
    document.getElementById('resCountBadgeDisplay').innerText = allFlatReservations.length + " Lượt đặt";

    if (allFlatReservations.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Chưa có thông tin đặt bàn trước.</td></tr>`;
    } else {
        allFlatReservations.forEach(res => {
            let tableBadge = document.getElementById('table-btn-' + res.tid);
            let tableName = tableBadge ? tableBadge.querySelector('.fw-bold').innerText : ('Bàn ' + res.tid);
            tbody.innerHTML += `
                <tr>
                    <td class="ps-3 fw-bold text-dark">${tableName}</td>
                    <td class="fw-semibold">${res.name}</td>
                    <td>${res.phone}</td>
                    <td class="text-center"><span class="badge bg-light text-dark border fw-bold">${res.guests || 2} khách</span></td>
                    <td><span class="badge bg-amber-subtle text-amber fw-bold">${res.date} ${res.time}</span></td>
                    <td class="text-muted" style="max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${res.note || ''}">${res.note || '-'}</td>
                    <td class="text-center pe-3">
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-sm btn-success rounded-pill px-2 py-1 fw-bold saas-btn-nowrap" style="font-size: 11px;" onclick="checkInReservation(${res.tid}, '${res.id || ''}')">
                                <i class="bi bi-check2-circle"></i> Khách đã đến
                            </button>
                            <button class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 fw-bold saas-btn-nowrap" style="font-size: 11px;" onclick="openCancelModalForTable(${res.tid}, '${tableName}', '${res.id || ''}')">
                                <i class="bi bi-x-circle"></i> Hủy
                            </button>
                        </div>
                    </td>
                </tr>`;
        });
    }
}

function confirmReserveTable() {
    let targetElem = document.getElementById('reserve-target-table');
    let tid = targetElem ? targetElem.value : null;
    let cName = document.getElementById('reserve-customer-name').value.trim();
    let cPhone = document.getElementById('reserve-customer-phone').value.trim();
    let cGuests = parseInt(document.getElementById('reserve-guest-count').value || 0);
    let rDate = document.getElementById('reserve-date').value;
    let rTime = document.getElementById('reserve-time').value;
    let rNote = document.getElementById('reserve-note').value.trim();

    if (!tid || isNaN(tid) || parseInt(tid) <= 0) {
        showCustomAlert("Vui lòng chọn bàn hợp lệ để đặt!", "Chưa Chọn Bàn", "danger");
        return;
    }

    if (isNaN(cGuests) || cGuests < 1) {
        showCustomAlert("Số lượng khách đặt bàn phải từ 1 khách trở lên!", "Số Khách Không Hợp Lệ", "danger");
        document.getElementById('reserve-guest-count').value = 1;
        document.getElementById('reserve-guest-count').focus();
        return;
    }

    if (!cName || !cPhone || !rDate || !rTime) {
        showCustomAlert("Vui lòng điền đầy đủ Tên khách hàng, Số điện thoại, Ngày và Giờ hẹn!", "Thiếu Thông Tin", "danger");
        return;
    }

    if (cPhone.length !== 10 || !cPhone.startsWith('0')) {
        showCustomAlert("Số điện thoại phải đúng 10 chữ số định dạng Việt Nam!", "SĐT Không Hợp Lệ", "danger");
        return;
    }

    let now = new Date();
    let targetDateTime = new Date(`${rDate}T${rTime}:00`);
    let diffHours = (targetDateTime - now) / (1000 * 60 * 60);

    if (diffHours < 2) {
        showCustomAlert("Chỉ cho phép đặt bàn trước tối thiểu 2 tiếng so với giờ hiện tại!", "Thời Gian Không Hợp Lệ", "danger");
        return;
    }

    if (diffHours > 24) {
        showCustomAlert("Chỉ cho phép đặt bàn trước tối đa 1 ngày (24 tiếng)!", "Vượt Quá Giới Hạn Đặt Trước", "danger");
        return;
    }

    let parsedTableId = parseInt(tid);

    let existingList = Array.isArray(tableReservationData[parsedTableId]) ? tableReservationData[parsedTableId] : [];
    let conflictItem = existingList.find(item => {
        let existDt = new Date(`${item.date}T${item.time}:00`);
        let diffMins = Math.abs(targetDateTime - existDt) / (1000 * 60);
        return diffMins < 90;
    });

    if (conflictItem) {
        showCustomAlert(`Bàn này đã có khách [${conflictItem.name}] đặt lúc ${conflictItem.time} (${conflictItem.date}). Vui lòng chọn giờ cách tối thiểu 90 phút!`, "Trùng Khung Giờ Đặt Bàn", "danger");
        return;
    }

    fetch(BASE_URL_JS + '/order/createReservation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            table_id: parsedTableId,
            customer_name: cName,
            customer_phone: cPhone,
            guest_count: cGuests,
            reservation_time: `${rDate} ${rTime}:00`,
            note: rNote
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            if (!Array.isArray(tableReservationData[parsedTableId])) {
                tableReservationData[parsedTableId] = [];
            }
            tableReservationData[parsedTableId].push({
                id: res.reservation_id || ('RES-' + Date.now()),
                name: cName,
                phone: cPhone,
                guests: cGuests,
                date: rDate,
                time: rTime,
                note: rNote,
                isTurnaround: false
            });

            renderReservationTableList();
            fetchReservationsFromDB();
            
            let listTabBtn = new bootstrap.Tab(document.getElementById('res-tab-list-btn'));
            listTabBtn.show();

            showCustomAlert(`Đã ghi nhận đặt bàn lúc ${rTime} ngày ${rDate} cho khách hàng ${cName} (${cGuests} khách)!`, "Đặt Bàn Thành Công", "success");
        } else {
            showCustomAlert(res.message || "Không thể đặt bàn!", "Lỗi Đặt Bàn", "danger");
        }
    })
    .catch(err => showCustomAlert("Lỗi kết nối máy chủ khi lưu đặt bàn!", "Lỗi Hệ Thống", "danger"));
}

function openCancelModalForTable(tid, tableName, resId = '') {
    document.getElementById('cancel-target-table-id').value = tid;
    document.getElementById('cancel-target-res-id').value = resId;
    document.getElementById('cancel-table-title-id').innerText = tableName || ('Bàn ' + tid);
    document.getElementById('cancel-reserve-reason-select').value = 'Khách báo hủy lịch';
    document.getElementById('cancel-other-reason-box').classList.add('d-none');
    cancelReserveModal.show();
}

function toggleCancelOtherReason(selectElem) {
    let otherBox = document.getElementById('cancel-other-reason-box');
    if (selectElem.value === 'other') otherBox.classList.remove('d-none');
    else otherBox.classList.add('d-none');
}

function confirmCancelReserve() {
    let tid = document.getElementById('cancel-target-table-id').value;
    let resId = document.getElementById('cancel-target-res-id').value;
    let selectReason = document.getElementById('cancel-reserve-reason-select').value;
    let reason = (selectReason === 'other') ? document.getElementById('cancel-reserve-other-reason').value.trim() : selectReason;

    if (!reason) {
        showCustomAlert("Vui lòng nhập lý do hủy đặt bàn!", "Thiếu Thông Tin", "danger");
        return;
    }

    fetch(BASE_URL_JS + '/order/cancelReservation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ table_id: parseInt(tid), reservation_id: resId, reason: reason })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            if (Array.isArray(tableReservationData[tid])) {
                if (resId) {
                    tableReservationData[tid] = tableReservationData[tid].filter(r => String(r.id) !== String(resId));
                } else {
                    tableReservationData[tid].shift();
                }
            }

            cancelReserveModal.hide();
            renderReservationTableList();
            fetchReservationsFromDB();
            showCustomAlert(`Đã hủy lịch đặt bàn (Lý do: ${reason})!`, "Thành Công", "success");
        } else {
            showCustomAlert(res.message || "Không thể hủy đặt bàn!", "Lỗi Hủy Đặt Bàn", "danger");
        }
    })
    .catch(err => showCustomAlert("Lỗi kết nối máy chủ!", "Lỗi Hệ Thống", "danger"));
}

// ================= GỘP BÀN & CHUYỂN BÀN =================
function openMergeTableModal() {
    if (!isShiftOpened) {
        showCustomAlert("Vui lòng mở ca làm việc trước!", "Yêu Cầu Mở Ca", "danger");
        openShiftModal();
        return;
    }

    let sourceSelect = document.getElementById('merge-source-table');
    let targetSelect = document.getElementById('merge-target-table');
    sourceSelect.innerHTML = '';
    targetSelect.innerHTML = '';

    let occupiedCount = 0;

    document.querySelectorAll('#table-section .table-badge').forEach(badge => {
        let tid = badge.id.replace('table-btn-', '');
        let tname = badge.querySelector('.fw-bold').innerText;
        let areaId = badge.getAttribute('data-area-id');
        let hasItems = activeOrders[tid] && activeOrders[tid].length > 0;

        if (hasItems) {
            occupiedCount++;
            sourceSelect.innerHTML += `<option value="${tid}" data-area="${areaId}" ${tid == currentTableId ? 'selected' : ''}>${tname} (${activeOrders[tid].length} món)</option>`;
        }
        targetSelect.innerHTML += `<option value="${tid}" data-area="${areaId}">${tname}</option>`;
    });

    if (occupiedCount === 0) {
        showCustomAlert("Không có bàn nào đang có hóa đơn để thực hiện gộp bàn!", "Thông Báo", "info");
        return;
    }

    document.getElementById('chk-keep-source-state').checked = false;
    checkMergeSameArea();
    mergeTableModal.show();
}

function checkMergeSameArea() {
    let sourceElem = document.getElementById('merge-source-table');
    let targetElem = document.getElementById('merge-target-table');
    if (!sourceElem || !targetElem) return;

    let sourceOption = sourceElem.options[sourceElem.selectedIndex];
    let targetOption = targetElem.options[targetElem.selectedIndex];

    let sameAreaBox = document.getElementById('same-area-keep-source-box');
    if (sourceOption && targetOption && sourceOption.getAttribute('data-area') === targetOption.getAttribute('data-area')) {
        sameAreaBox.classList.remove('d-none');
    } else {
        sameAreaBox.classList.add('d-none');
    }
}

function confirmMergeTable() {
    let sourceId = document.getElementById('merge-source-table').value;
    let targetId = document.getElementById('merge-target-table').value;
    let keepSourceOccupied = document.getElementById('chk-keep-source-state').checked;

    if (sourceId == targetId) {
        showCustomAlert("Bàn nguồn và bàn đích không được phép trùng nhau!", "Thao Tác Không Hợp Lệ", "danger");
        return;
    }

    let sourceBadge = document.getElementById('table-btn-' + sourceId);
    let targetBadge = document.getElementById('table-btn-' + targetId);
    let sourceName = sourceBadge ? sourceBadge.querySelector('.fw-bold').innerText : ('Bàn ' + sourceId);
    let targetName = targetBadge ? targetBadge.querySelector('.fw-bold').innerText : ('Bàn ' + targetId);

    if (activeOrders[sourceId]) {
        if (!activeOrders[targetId]) activeOrders[targetId] = [];
        activeOrders[targetId] = activeOrders[targetId].concat(activeOrders[sourceId]);
        delete activeOrders[sourceId];
    }

    if (keepSourceOccupied) {
        if (!tableMergeSourcesMap[targetId]) tableMergeSourcesMap[targetId] = [];
        if (!tableMergeSourcesMap[targetId].includes(sourceId)) {
            tableMergeSourcesMap[targetId].push(sourceId);
        }
        tableMergedTargetMap[sourceId] = targetId;

        if (sourceBadge) {
            sourceBadge.className = "table-badge status-occupied";
            sourceBadge.querySelector('.status-label').innerText = `Có khách (Gộp -> ${targetName})`;
        }
        syncTableStatusToDB(parseInt(sourceId), 'occupied');
    } else {
        if (sourceBadge) {
            sourceBadge.className = "table-badge status-available";
            sourceBadge.querySelector('.status-label').innerText = "Trống";
        }
        syncTableStatusToDB(parseInt(sourceId), 'available');
    }

    if (targetBadge) {
        targetBadge.className = "table-badge status-occupied active";
        targetBadge.querySelector('.status-label').innerText = "Có khách";
    }
    syncTableStatusToDB(parseInt(targetId), 'occupied');

    currentTableId = parseInt(targetId);
    document.getElementById('selected-table-name').innerText = targetName;
    loadCartForCurrentTable();
    mergeTableModal.hide();
    fetchReservationsFromDB();
    showCustomAlert(`Đã chuyển toàn bộ hóa đơn từ [${sourceName}] sang [${targetName}]!`, "Gộp Bàn Thành Công", "success");
}

function openSplitTableModal() {
    if (!isShiftOpened) {
        showCustomAlert("Vui lòng mở ca làm việc trước!", "Yêu Cầu Mở Ca", "danger");
        openShiftModal();
        return;
    }

    let sourceSelect = document.getElementById('split-source-table');
    let targetSelect = document.getElementById('split-target-table');
    sourceSelect.innerHTML = '';
    targetSelect.innerHTML = '';

    let occupiedCount = 0;
    let availableCount = 0;

    document.querySelectorAll('#table-section .table-badge').forEach(badge => {
        let tid = badge.id.replace('table-btn-', '');
        let tname = badge.querySelector('.fw-bold').innerText;
        let hasItems = activeOrders[tid] && activeOrders[tid].length > 0;
        let isOccupied = badge.classList.contains('status-occupied') || hasItems;
        let isPaid = badge.classList.contains('status-paid');
        let isReserved = badge.classList.contains('status-reserved');

        if (hasItems) {
            occupiedCount++;
            sourceSelect.innerHTML += `<option value="${tid}" ${tid == currentTableId ? 'selected' : ''}>${tname} (${activeOrders[tid].length} món)</option>`;
        } else if (!isOccupied && !isPaid && !isReserved) {
            availableCount++;
            targetSelect.innerHTML += `<option value="${tid}">${tname}</option>`;
        }
    });

    if (occupiedCount === 0) {
        showCustomAlert("Không có bàn nào đang phục vụ món ăn để thực hiện chuyển món!", "Thông Báo", "info");
        return;
    }
    if (availableCount === 0) {
        showCustomAlert("Hiện tại không còn bàn trống hợp lệ nào để chuyển món tới!", "Hết Bàn Trống", "danger");
        return;
    }

    document.getElementById('chk-select-all-split').checked = false;
    loadSplitItems();
    splitTableModal.show();
}

function loadSplitItems() {
    let sourceId = document.getElementById('split-source-table').value;
    let itemsListDiv = document.getElementById('split-items-list');
    document.getElementById('chk-select-all-split').checked = false;
    itemsListDiv.innerHTML = '';

    let items = activeOrders[sourceId] || [];
    if (items.length === 0) {
        itemsListDiv.innerHTML = '<small class="text-muted d-block text-center py-3">Bàn không có món ăn nào</small>';
        return;
    }

    items.forEach((item, idx) => {
        itemsListDiv.innerHTML += `
            <div class="d-flex justify-content-between align-items-center p-2 border-bottom bg-white mb-1 rounded">
                <div class="form-check">
                    <input class="form-check-input split-item-chk" type="checkbox" value="${idx}" id="split-chk-${idx}">
                    <label class="form-check-label fw-bold text-dark" style="font-size: 12px;" for="split-chk-${idx}">
                        ${item.name} ${item.size ? `(Size ${item.size})` : ''}
                    </label>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <small class="text-muted">SL chuyển:</small>
                    <input type="number" id="split-qty-${idx}" class="form-control form-control-sm text-center fw-bold" style="width: 55px;" value="${item.qty}" min="1" max="${item.qty}">
                </div>
            </div>`;
    });
}

function toggleSelectAllSplitItems(checkbox) {
    let isChecked = checkbox.checked;
    document.querySelectorAll('.split-item-chk').forEach(chk => {
        chk.checked = isChecked;
    });
}

function confirmSplitTable() {
    let sourceId = document.getElementById('split-source-table').value;
    let targetId = document.getElementById('split-target-table').value;

    let targetBadge = document.getElementById('table-btn-' + targetId);
    if (!targetBadge || targetBadge.classList.contains('status-occupied') || targetBadge.classList.contains('status-paid') || targetBadge.classList.contains('status-reserved')) {
        showCustomAlert("Bàn đích đang có khách hoặc đã được đặt trước. Không thể thực hiện chuyển món tới bàn này!", "Bàn Đích Không Hợp Lệ", "danger");
        return;
    }

    let checkboxes = document.querySelectorAll('.split-item-chk:checked');
    if (checkboxes.length === 0) {
        showCustomAlert("Vui lòng chọn ít nhất 1 món ăn để chuyển sang bàn mới!", "Chưa Chọn Món", "danger");
        return;
    }

    if (!activeOrders[targetId]) activeOrders[targetId] = [];
    let sourceItems = activeOrders[sourceId];
    let itemsToKeep = [];

    sourceItems.forEach((item, idx) => {
        let chk = document.getElementById(`split-chk-${idx}`);
        if (chk && chk.checked) {
            let splitQty = parseInt(document.getElementById(`split-qty-${idx}`).value) || 1;
            if (splitQty >= item.qty) {
                activeOrders[targetId].push({ ...item });
            } else {
                activeOrders[targetId].push({ ...item, qty: splitQty, printedQty: Math.min(item.printedQty || 0, splitQty) });
                itemsToKeep.push({ ...item, qty: item.qty - splitQty });
            }
        } else {
            itemsToKeep.push(item);
        }
    });

    if (itemsToKeep.length === 0) {
        delete activeOrders[sourceId];
        let sourceBadge = document.getElementById('table-btn-' + sourceId);
        if (sourceBadge) {
            sourceBadge.className = "table-badge status-available";
            sourceBadge.querySelector('.status-label').innerText = "Trống";
        }
        syncTableStatusToDB(parseInt(sourceId), 'available');
    } else {
        activeOrders[sourceId] = itemsToKeep;
    }

    let targetName = targetBadge ? targetBadge.querySelector('.fw-bold').innerText : ('Bàn ' + targetId);
    if (targetBadge) {
        targetBadge.className = "table-badge status-occupied active";
        targetBadge.querySelector('.status-label').innerText = "Có khách";
    }
    syncTableStatusToDB(parseInt(targetId), 'occupied');

    currentTableId = parseInt(targetId);
    document.getElementById('selected-table-name').innerText = targetName;
    loadCartForCurrentTable();
    splitTableModal.hide();
    fetchReservationsFromDB();
    showCustomAlert(`Đã chuyển món sang [${targetName}] và tạo hóa đơn mới thành công!`, "Chuyển Bàn Thành Công", "success");
}

// ================= QUẢN LÝ CA LÀM VIỆC =================
function openShiftModal() {
    let closeBtn = document.getElementById('shift-modal-close-btn');
    let modalEl = document.getElementById('shiftModal');

    if (!isShiftOpened) {
        if (closeBtn) closeBtn.classList.add('d-none');
        if (modalEl) {
            modalEl.setAttribute('data-bs-backdrop', 'static');
            modalEl.setAttribute('data-bs-keyboard', 'false');
        }
        document.getElementById('shiftModalTitle').innerHTML = `<i class="bi bi-box-arrow-in-right text-success"></i> BẮT BUỘC MỞ CA LÀM VIỆC ĐỂ BÁN HÀNG`;
        document.getElementById('shift-open-form').classList.remove('d-none');
        document.getElementById('shift-close-summary-tabs').classList.add('d-none');

        let currentHour = new Date().getHours();
        let shiftSelect = document.getElementById('shift-type-select');
        if (currentHour >= 6 && currentHour < 14) shiftSelect.value = 'morning';
        else if (currentHour >= 14 && currentHour < 22) shiftSelect.value = 'afternoon';
        else shiftSelect.value = 'night';

        document.getElementById('shift-initial-cash').value = '0';
        document.getElementById('shift-cashier-id').value = 'NV01';
        document.getElementById('shift-cashier-pass').value = '';
    } else {
        if (closeBtn) closeBtn.classList.remove('d-none');
        if (modalEl) {
            modalEl.removeAttribute('data-bs-backdrop');
            modalEl.removeAttribute('data-bs-keyboard');
        }
        document.getElementById('shiftModalTitle').innerHTML = `<i class="bi bi-clipboard-data text-primary"></i> BÁO CÁO & KIỂM KÊ KẾT CA (${currentShiftInfo.shiftName})`;
        document.getElementById('shift-open-form').classList.add('d-none');
        document.getElementById('shift-close-summary-tabs').classList.remove('d-none');

        fetch(BASE_URL_JS + '/order/getShiftStatus')
            .then(res => res.json())
            .then(res => {
                if (res.success && res.stats) {
                    shiftStats.totalRevenue   = parseFloat(res.stats.total_revenue || 0);
                    shiftStats.offlineRevenue = parseFloat(res.stats.offline_revenue || 0);
                    shiftStats.onlineRevenue  = parseFloat(res.stats.online_revenue || 0);
                    shiftStats.totalCashSales = parseFloat(res.stats.total_cash_sales || 0);
                    shiftStats.totalBankSales = parseFloat(res.stats.total_bank_sales || 0);
                    shiftStats.totalAppSales  = parseFloat(res.stats.total_app_sales || 0);
                    shiftStats.totalOrders    = parseInt(res.stats.total_orders || 0);
                    shiftStats.totalCups      = parseInt(res.stats.total_cups || 0);
                    shiftStats.totalCakes     = parseInt(res.stats.total_cakes || 0);
                }

                document.getElementById('stat-total-revenue').innerText = shiftStats.totalRevenue.toLocaleString() + " đ";
                document.getElementById('stat-cash-sales').innerText = shiftStats.totalCashSales.toLocaleString() + " đ";
                document.getElementById('stat-bank-sales').innerText = shiftStats.totalBankSales.toLocaleString() + " đ";
                document.getElementById('stat-app-sales').innerText = shiftStats.totalAppSales.toLocaleString() + " đ";
                document.getElementById('stat-total-orders').innerText = shiftStats.totalOrders + " Đơn";
                document.getElementById('stat-total-cups').innerText = shiftStats.totalCups + " Ly";
                document.getElementById('stat-total-cakes').innerText = shiftStats.totalCakes + " Bánh";

                document.getElementById('shift-ending-cash-actual').value = '';
                document.getElementById('shift-cash-diff-reason').value = '';
                document.getElementById('shift-cash-diff-reason-box').classList.add('d-none');
                document.getElementById('shift-retained-cash-next').value = '0';
                calculateShiftDifference();
            });
    }
    shiftModal.show();
}

function confirmOpenShift() {
    let shiftSelect = document.getElementById('shift-type-select');
    let shiftType = shiftSelect.value;
    let shiftName = shiftSelect.options[shiftSelect.selectedIndex].text.split(' (')[0];
    let initialCash = parseFloat(document.getElementById('shift-initial-cash').value) || 0;
    let cashierId = document.getElementById('shift-cashier-id').value.trim();
    let cashierPass = document.getElementById('shift-cashier-pass').value.trim();

    if (!cashierId || !cashierPass) {
        showCustomAlert("Vui lòng nhập đầy đủ Mã nhân viên thu ngân và Mật khẩu xác thực!", "Thiếu Thông Tin", "danger");
        return;
    }

    fetch(BASE_URL_JS + '/order/openShift', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            shift_type: shiftType,
            shift_name: shiftName,
            initial_cash: initialCash,
            cashier_id: cashierId,
            password: cashierPass
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            isShiftOpened = true;
            currentShiftInfo = {
                shift_id: res.shift_id,
                cashierId: cashierId,
                shiftType: shiftType,
                shiftName: shiftName,
                initialCash: initialCash
            };

            sessionStorage.setItem('pos_active_shift', JSON.stringify(currentShiftInfo));

            shiftStats = { 
                totalRevenue: 0, offlineRevenue: 0, onlineRevenue: 0, 
                totalCashSales: 0, totalBankSales: 0, totalAppSales: 0,
                totalOrders: 0, totalCups: 0, totalCakes: 0 
            };

            document.getElementById('shift-status-badge').className = "badge bg-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
            document.getElementById('current-shift-name-label').innerText = shiftName;
            document.getElementById('cashier-id-display').innerText = cashierId;
            document.getElementById('right-panel-cashier').innerText = cashierId;

            let closeBtn = document.getElementById('shift-modal-close-btn');
            if (closeBtn) closeBtn.classList.remove('d-none');

            shiftModal.hide();
            showCustomAlert(`Đã xác nhận mở [${shiftName}] với số dư đầu ca ${initialCash.toLocaleString()}đ!`, "Mở Ca Thành Công", "success");
        } else {
            showCustomAlert(res.message || "Không thể mở ca làm việc!", "Lỗi Mở Ca", "danger");
        }
    })
    .catch(err => showCustomAlert("Lỗi kết nối máy chủ khi mở ca!", "Lỗi Hệ Thống", "danger"));
}

function calculateShiftDifference() {
    let expectedCash = currentShiftInfo.initialCash + shiftStats.totalCashSales;
    let actualInput = document.getElementById('shift-ending-cash-actual').value;
    let actualCash = actualInput !== '' ? parseFloat(actualInput) : expectedCash;
    let diff = actualCash - expectedCash;

    let retainedCash = parseFloat(document.getElementById('shift-retained-cash-next').value) || 0;
    let handoverCash = Math.max(0, actualCash - retainedCash);

    let handoverEl = document.getElementById('shift-handover-cash-display');
    if (handoverEl) {
        handoverEl.innerText = handoverCash.toLocaleString() + " đ";
    }

    let diffDisplay = document.getElementById('shift-cash-diff-display');
    let reasonBox = document.getElementById('shift-cash-diff-reason-box');
    let reasonText = document.getElementById('shift-cash-diff-reason').value.trim();

    if (actualInput === '') {
        diffDisplay.innerText = "0 đ (Chưa nhập tiền thực đếm)";
        diffDisplay.className = "form-control form-control-lg rounded-3 fw-bold bg-light text-muted";
        if (reasonBox) reasonBox.classList.add('d-none');
    } else if (diff === 0) {
        diffDisplay.innerText = "0 đ (Khớp chính xác)";
        diffDisplay.className = "form-control form-control-lg rounded-3 fw-bold bg-light text-success";
        if (reasonBox) reasonBox.classList.add('d-none');
    } else if (diff > 0) {
        diffDisplay.innerText = "+" + diff.toLocaleString() + " đ (Thừa tiền mặt)";
        diffDisplay.className = "form-control form-control-lg rounded-3 fw-bold bg-light text-primary";
        if (reasonBox) reasonBox.classList.remove('d-none');
    } else {
        diffDisplay.innerText = diff.toLocaleString() + " đ (Thiếu tiền mặt)";
        diffDisplay.className = "form-control form-control-lg rounded-3 fw-bold bg-light text-danger";
        if (reasonBox) reasonBox.classList.remove('d-none');
    }

    document.getElementById('shift-report-thermal-preview').innerHTML = `
        <div class="text-center mb-2 border-bottom pb-1">
            <h6 class="fw-bold m-0">PHIẾU BÁO CÁO KẾT CA (${currentBranchId})</h6>
            <small style="font-size: 9px;">Ca làm việc: ${currentShiftInfo.shiftName} | Thời gian: ${new Date().toLocaleTimeString()}</small>
        </div>
        <div style="font-size: 11px;">
            <div class="d-flex justify-content-between"><span>Thu ngân trực ca:</span><span class="fw-bold">${currentShiftInfo.cashierId}</span></div>
            <div class="d-flex justify-content-between"><span>Tiền đầu ca:</span><span>${currentShiftInfo.initialCash.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between"><span>Doanh thu Tiền mặt:</span><span>${shiftStats.totalCashSales.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between"><span>Doanh thu Chuyển khoản:</span><span>${shiftStats.totalBankSales.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between"><span>Doanh thu App Online:</span><span>${shiftStats.totalAppSales.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between fw-bold border-top pt-1 mt-1"><span>TỔNG DOANH THU CA:</span><span>${shiftStats.totalRevenue.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between fw-bold text-primary"><span>Tiền mặt lý thuyết két:</span><span>${expectedCash.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between fw-bold"><span>Tiền mặt thực đếm:</span><span>${actualCash.toLocaleString()}đ</span></div>
            <div class="d-flex justify-content-between fw-bold ${diff !== 0 ? 'text-danger' : 'text-success'}"><span>Chênh lệch két:</span><span>${diff.toLocaleString()}đ</span></div>
            ${diff !== 0 && reasonText ? `<div class="text-danger fst-italic mt-1" style="font-size:10px;">Lý do lệch: ${reasonText}</div>` : ''}
            <div class="border-top mt-1 pt-1">
                <div class="d-flex justify-content-between"><span>Giữ lại két cho ca sau:</span><span class="fw-bold">${retainedCash.toLocaleString()}đ</span></div>
                <div class="d-flex justify-content-between fw-bold text-success"><span>NỘP VỀ QUẢN LÝ:</span><span>${handoverCash.toLocaleString()}đ</span></div>
            </div>
            <div class="d-flex justify-content-between border-top mt-1 pt-1"><span>Số đơn hoàn tất:</span><span>${shiftStats.totalOrders} đơn</span></div>
            <div class="d-flex justify-content-between"><span>Số lượng sản phẩm:</span><span>${shiftStats.totalCups} Ly | ${shiftStats.totalCakes} Bánh</span></div>
        </div>
    `;
}

function confirmCloseShift() {
    let hasUnpaidTables = Object.keys(activeOrders).some(key => activeOrders[key] && activeOrders[key].length > 0);
    if (hasUnpaidTables) {
        showCustomAlert("Cảnh báo: Vẫn còn bàn hoặc thẻ đơn hàng đang phục vụ chưa thanh toán! Vui lòng hoàn tất tất cả đơn hàng trước khi kết ca.", "Không Thể Kết Ca", "danger");
        return;
    }

    let actualInput = document.getElementById('shift-ending-cash-actual').value;
    if (actualInput === '') {
        showCustomAlert("Vui lòng nhập số tiền mặt thực tế kiểm đếm được trong két!", "Thiếu Số Liệu", "danger");
        document.getElementById('shift-ending-cash-actual').focus();
        return;
    }

    let expectedCash = currentShiftInfo.initialCash + shiftStats.totalCashSales;
    let actualCash = parseFloat(actualInput) || 0;
    let diff = actualCash - expectedCash;
    let diffReason = document.getElementById('shift-cash-diff-reason').value.trim();

    if (diff !== 0 && !diffReason) {
        showCustomAlert("Số tiền thực tế trong két đang có CHÊNH LỆCH so với doanh thu hệ thống. Vui lòng nhập rõ lý do / ghi chú chênh lệch két trước khi kết ca!", "Bắt Buộc Nhập Lý Do Lệch Két", "danger");
        document.getElementById('shift-cash-diff-reason').focus();
        return;
    }

    let retainedCash = parseFloat(document.getElementById('shift-retained-cash-next').value) || 0;
    let handoverCash = Math.max(0, actualCash - retainedCash);

    fetch(BASE_URL_JS + '/order/closeShift', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            shift_id: currentShiftInfo.shift_id,
            ending_cash_actual: actualCash,
            cash_diff: diff,
            cash_diff_reason: diffReason,
            retained_cash_next: retainedCash,
            handover_cash: handoverCash
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            sessionStorage.removeItem('pos_active_shift');

            isShiftOpened = false;
            currentShiftInfo = { shift_id: null, cashierId: '', shiftType: '', shiftName: '', initialCash: 0 };

            document.getElementById('shift-status-badge').className = "badge bg-danger rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1";
            document.getElementById('current-shift-name-label').innerText = "Chưa Mở Ca";
            document.getElementById('cashier-id-display').innerText = "--";
            document.getElementById('right-panel-cashier').innerText = "--";

            shiftModal.hide();
            showCustomAlert("Đã xuất báo cáo kết ca, chốt số liệu bàn giao và khóa ca làm việc thành công!", "Kết Ca Thành Công", "success");
            setTimeout(() => {
                openShiftModal();
            }, 800);
        } else {
            showCustomAlert(res.message || "Lỗi kết ca!", "Lỗi Hệ Thống", "danger");
        }
    })
    .catch(err => showCustomAlert("Lỗi kết nối máy chủ khi kết ca!", "Lỗi Hệ Thống", "danger"));
}

// ================= TÌM KIẾM & LỌC SẢN PHẨM =================
function updateProductPricesDisplay() {
    document.querySelectorAll('.product-item').forEach(item => {
        let pid = item.getAttribute('data-product-id');
        let priceDiv = document.getElementById('product-price-' + pid);
        let badgeDiv = document.getElementById('online-badge-' + pid);
        let basePrice = parseFloat(priceDiv.getAttribute('data-base-price'));

        if (currentOrderType === 'online') {
            let onlinePrice = Math.round(basePrice * 1.1);
            priceDiv.innerText = onlinePrice.toLocaleString() + " đ";
            priceDiv.style.color = (onlinePlatform === 'grab') ? '#00b14f' : '#ff5722';
            if (badgeDiv) {
                badgeDiv.classList.remove('d-none');
                badgeDiv.style.backgroundColor = (onlinePlatform === 'grab') ? '#00b14f' : '#ff5722';
            }
        } else {
            priceDiv.innerText = basePrice.toLocaleString() + " đ";
            priceDiv.style.color = 'var(--pos-primary)';
            if (badgeDiv) badgeDiv.classList.add('d-none');
        }
    });
}

function searchProduct() {
    let keyword = removeVietnameseTones(document.getElementById('search-product').value);
    document.querySelectorAll('.product-item').forEach(item => {
        let name = removeVietnameseTones(item.getAttribute('data-name'));
        item.style.display = name.includes(keyword) ? '' : 'none';
    });
}

function filterCategory(categoryId, btnElement) {
    document.querySelectorAll('.cat-btn').forEach(btn => btn.classList.remove('active-cat'));
    btnElement.classList.add('active-cat');

    document.querySelectorAll('.product-item').forEach(item => {
        let itemCat = String(item.getAttribute('data-category'));
        let itemName = removeVietnameseTones(item.getAttribute('data-name'));

        if (categoryId === 'all') item.style.display = '';
        else if (categoryId === 'bestseller') {
            let isBest = itemName.includes('tra sua') || itemName.includes('ca phe') || itemName.includes('matcha') || itemName.includes('dao');
            item.style.display = isBest ? '' : 'none';
        } else if (categoryId === 'combo') {
            let isCombo = itemName.includes('combo') || itemName.includes('set');
            item.style.display = isCombo ? '' : 'none';
        } else {
            item.style.display = (itemCat === String(categoryId)) ? '' : 'none';
        }
    });
}

// ================= HỆ THỐNG KHÁCH HÀNG & TÍCH ĐIỂM =================
function searchCustomerByPhone(silent = false) {
    const phoneInput = document.getElementById('cust-phone-input');
    const phone = phoneInput ? phoneInput.value.trim() : '';

    if (phone.length < 10) {
        if (!silent) showCustomAlert("Vui lòng nhập số điện thoại 10 chữ số để tra cứu!", "SĐT Không Hợp Lệ", "danger");
        return;
    }

    fetch(BASE_URL_JS + '/order/searchCustomer?phone=' + encodeURIComponent(phone))
        .then(res => res.json())
        .then(res => {
            if (res.success && res.customer) {
                selectedCustomer = res.customer;
                displayCustomerInfo(res.customer);
                renderCart();
            } else {
                if (!silent) showCustomAlert("Không tìm thấy khách hàng thành viên này! Vui lòng nhấn '+ Thêm Khách' để đăng ký mới.", "Khách Hàng Mới", "info");
            }
        })
        .catch(err => {
            if (!silent) showCustomAlert("Lỗi kết nối máy chủ khi tìm khách hàng!", "Lỗi Hệ Thống", "danger");
        });
}

function displayCustomerInfo(cust) {
    document.getElementById('cust-search-bar').classList.add('d-none');
    document.getElementById('cust-info-display').classList.remove('d-none');

    document.getElementById('cust-name-val').innerText = cust.customer_name || 'Khách hàng';
    document.getElementById('cust-points-val').innerText = (cust.total_points || 0).toLocaleString();

    let rank = cust.member_rank || 'Bronze';
    let badge = document.getElementById('cust-rank-badge');
    badge.innerText = rank;
    
    if (rank === 'VIP') badge.className = 'badge bg-danger';
    else if (rank === 'Diamond') badge.className = 'badge bg-info text-dark';
    else if (rank === 'Gold') badge.className = 'badge bg-warning text-dark';
    else if (rank === 'Silver') badge.className = 'badge bg-primary';
    else badge.className = 'badge bg-secondary';

    appliedPointsDiscount = 0;
    pointsToRedeem = 0;
    redeemedGiftsList = [];
    document.getElementById('redeem-points-input').value = 0;
    let msgEl = document.getElementById('loyalty-discount-msg');
    if (msgEl) msgEl.classList.add('d-none');
}

function detachCustomer() {
    selectedCustomer = null;
    appliedPointsDiscount = 0;
    pointsToRedeem = 0;
    redeemedGiftsList = [];

    cart = cart.filter(item => !item.is_gift);
    if (currentTableId) activeOrders[currentTableId] = cart;

    let searchBar = document.getElementById('cust-search-bar');
    let infoDisplay = document.getElementById('cust-info-display');
    let phoneInput = document.getElementById('cust-phone-input');

    if (searchBar) searchBar.classList.remove('d-none');
    if (infoDisplay) infoDisplay.classList.add('d-none');
    if (phoneInput) phoneInput.value = '';

    renderCart();
}

function openRegisterCustomerModal() {
    if (!registerCustomerModal) return;
    document.getElementById('reg-cust-phone').value = document.getElementById('cust-phone-input')?.value.trim() || '';
    document.getElementById('reg-cust-name').value = '';
    registerCustomerModal.show();
}

function confirmRegisterCustomer() {
    const phone = document.getElementById('reg-cust-phone').value.trim();
    const name = document.getElementById('reg-cust-name').value.trim();

    if (phone.length < 10 || !name) {
        showCustomAlert("Vui lòng nhập đầy đủ SĐT (10 số) và Họ tên khách hàng!", "Thiếu Thông Tin", "danger");
        return;
    }

    fetch(BASE_URL_JS + '/order/registerCustomer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone: phone, customer_name: name })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success && res.customer) {
            registerCustomerModal.hide();
            selectedCustomer = res.customer;
            displayCustomerInfo(res.customer);
            renderCart();
            showCustomAlert(`Đã đăng ký thành viên mới thành công cho [${name}]!`, "Thành Công", "success");
        } else {
            showCustomAlert(res.message || "Không thể đăng ký khách hàng!", "Lỗi Đăng Ký", "danger");
        }
    })
    .catch(err => showCustomAlert("Lỗi kết nối máy chủ!", "Lỗi Hệ Thống", "danger"));
}

function syncTableStatusToDB(tableId, statusStr, allowOverrideReserved = false) {
    if (!tableId || typeof tableId !== 'number') return;

    if (!allowOverrideReserved && statusStr !== 'reserved') {
        let nextRes = getNextReservationForTable(tableId);
        if (nextRes && statusStr === 'available') {
            let now = new Date();
            let resDateTime = new Date(`${nextRes.date}T${nextRes.time}:00`);
            let diffMins = (resDateTime - now) / (1000 * 60);
            if (diffMins <= 60) statusStr = 'reserved';
        }
    }

    fetch(BASE_URL_JS + '/order/updateTableStatus', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ table_id: tableId, status: statusStr })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            console.warn('Cảnh báo đồng bộ trạng thái bàn DB:', data.message);
        }
    })
    .catch(err => console.error('Lỗi gọi API updateTableStatus:', err));
}

function sendOrderToMySQLBackend(extraData = {}) {
    let subTotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let vat = subTotal * (currentVatPercent / 100);
    let baseGrandTotal = subTotal + vat;

    let pointsDiscountMoney = 0;
    let tierDiscountMoney = 0;

    if (currentOrderType !== 'online') {
        pointsDiscountMoney = appliedPointsDiscount;
        let remainingAfterPoints = Math.max(0, baseGrandTotal - pointsDiscountMoney);

        if (selectedCustomer) {
            let discountPercent = selectedCustomer.discount_percent || 0;
            if (discountPercent > 0) tierDiscountMoney = Math.round(remainingAfterPoints * (discountPercent / 100));
        }
    }
    let totalDiscount = pointsDiscountMoney + tierDiscountMoney;
    let grandTotal = Math.max(0, Math.round(baseGrandTotal - totalDiscount));
    let given = parseFloat(document.getElementById('cash-given')?.value) || grandTotal;

    const payload = {
        shift_id: currentShiftInfo.shift_id || null,
        branch_id: currentBranchId,
        order_type: extraData.order_type || currentOrderType,
        table_id: (typeof currentTableId === 'number') ? currentTableId : null,
        customer_id: selectedCustomer ? selectedCustomer.id : null,
        total_amount: subTotal,
        discount: totalDiscount,
        vat_percent: extraData.vat_percent || currentVatPercent,
        final_amount: grandTotal,
        customer_pay: given,
        change_amount: Math.max(0, given - grandTotal),
        points_earned: (currentOrderType !== 'online') ? Math.floor(grandTotal / 10000) : 0,
        points_redeemed: (currentOrderType !== 'online') ? pointsToRedeem : 0,
        payment_method: extraData.payment_method || 'cash',
        app_order_code: extraData.app_order_code || null,
        driver_name: extraData.driver_name || null,
        driver_phone: extraData.driver_phone || null,
        commission_rate: extraData.commission_rate || 0,
        items: cart
    };

    const confirmBtn = document.getElementById('btn-confirm-checkout-action');
    if (confirmBtn) confirmBtn.disabled = true;

    fetch(BASE_URL_JS + '/order/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (confirmBtn) confirmBtn.disabled = false;
        if (res.success) {
            finishPaymentSuccess(grandTotal, payload.payment_method);
        } else {
            showCustomAlert("Lỗi lưu hóa đơn: " + res.message, "Lỗi Thanh Toán", "danger");
        }
    })
    .catch(err => {
        if (confirmBtn) confirmBtn.disabled = false;
        showCustomAlert("Lỗi kết nối máy chủ khi thanh toán!", "Lỗi Hệ Thống", "danger");
    });
}
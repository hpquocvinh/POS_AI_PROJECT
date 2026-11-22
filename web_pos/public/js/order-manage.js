// public/js/order-manage.js - Xử lý tương tác trang Quản Lý Đơn Hàng

const OM_API = typeof OM_BASE_URL !== 'undefined' ? OM_BASE_URL : '/POS_AI_PROJECT/web_pos/public';
let activeDatePreset  = 'today';
let currentPage       = 1;
let activeReceiptData = null;

let orderDetailModal = null;
let cancelOrderModal = null;
let omToastInstance  = null;

document.addEventListener('DOMContentLoaded', () => {
    orderDetailModal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
    cancelOrderModal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
    omToastInstance  = new bootstrap.Toast(document.getElementById('omToast'), { delay: 3200 });

    // Bắt sự kiện chuyển nút chọn nhanh khoảng thời gian
    document.querySelectorAll('#om-date-pills .om-date-pill').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#om-date-pills .om-date-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeDatePreset = btn.dataset.preset;
            document.getElementById('om-date-from').value = '';
            document.getElementById('om-date-to').value   = '';
            resetAndLoadOrders();
        });
    });

    // Tìm kiếm có độ trễ 300ms để tránh gửi request liên tục khi đang gõ
    let searchTimer = null;
    document.getElementById('om-search-input').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(resetAndLoadOrders, 300);
    });

    loadOrders(1);
});

// Hiển thị thông báo Toast góc phải màn hình
function showOmToast(message, type = 'success') {
    const toastEl = document.getElementById('omToast');
    const msgEl   = document.getElementById('omToastMsg');
    toastEl.className = `toast align-items-center border-0 shadow-lg rounded-3 text-white ${type === 'error' ? 'bg-danger' : 'bg-dark'}`;
    msgEl.innerHTML   = `<i class="fa-solid ${type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check text-success'} me-2"></i>${escapeHtml(message)}`;
    omToastInstance.show();
}

function onCustomDateChange() {
    document.querySelectorAll('#om-date-pills .om-date-pill').forEach(b => b.classList.remove('active'));
    activeDatePreset = 'CUSTOM';
    resetAndLoadOrders();
}

function resetAndLoadOrders() {
    loadOrders(1);
}

// Tải danh sách đơn hàng và cập nhật 4 thẻ KPI
function loadOrders(page = 1) {
    currentPage = page;
    const params = new URLSearchParams({
        page:        page,
        keyword:     document.getElementById('om-search-input').value.trim(),
        date_preset: activeDatePreset,
        date_from:   document.getElementById('om-date-from').value,
        date_to:     document.getElementById('om-date-to').value,
        channel:     document.getElementById('om-filter-channel').value,
        status:      document.getElementById('om-filter-status').value,
        payment:     document.getElementById('om-filter-payment').value
    });

    fetch(`${OM_API}/orderManage/getOrders?${params.toString()}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;

            // Cập nhật số liệu 4 thẻ KPI
            const kpi = res.kpi || {};
            document.getElementById('kpi-completed-orders').innerText = Number(kpi.completed_orders || 0).toLocaleString('vi-VN');
            document.getElementById('kpi-net-revenue').innerText      = Number(kpi.net_revenue || 0).toLocaleString('vi-VN') + ' đ';
            document.getElementById('kpi-aov-value').innerText        = Number(kpi.aov || 0).toLocaleString('vi-VN') + ' đ';
            document.getElementById('kpi-cancelled-orders').innerText = Number(kpi.cancelled_orders || 0).toLocaleString('vi-VN');
            document.getElementById('kpi-cancel-rate').innerText      = `(${kpi.cancel_rate || 0}%)`;

            renderOrdersTable(res.orders || []);
            renderPagination(res.current_page, res.total_pages, res.total_records);
        })
        .catch(() => {
            showOmToast('Không thể kết nối máy chủ để tải danh sách đơn hàng.', 'error');
        });
}

// Đổ dữ liệu đơn hàng ra bảng
function renderOrdersTable(list) {
    const tbody = document.getElementById('om-orders-tbody');
    if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">Không tìm thấy giao dịch nào phù hợp với điều kiện lọc hiện tại.</td></tr>`;
        return;
    }

    const channelMap = {
        dine_in:  { icon: 'fa-utensils',     label: 'Tại chỗ' },
        takeaway: { icon: 'fa-bag-shopping', label: 'Mang về' },
        grab:     { icon: 'fa-motorcycle',   label: 'GrabFood' },
        shopee:   { icon: 'fa-burger',       label: 'ShopeeFood' }
    };

    tbody.innerHTML = list.map(o => {
        const type   = o.order_type || 'dine_in';
        const chInfo = channelMap[type] || { icon: 'fa-receipt', label: type };
        const placeLabel = (type === 'dine_in' && o.table_name) ? o.table_name : chInfo.label;

        // Định dạng badge phương thức thanh toán
        const pm = String(o.payment_method || 'cash').toLowerCase();
        let pmBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle">Tiền mặt</span>`;
        if (['banking', 'bank', 'qr'].includes(pm)) {
            pmBadge = `<span class="badge bg-info-subtle text-info border border-info-subtle">Chuyển khoản</span>`;
        } else if (['card', 'app', 'wallet', 'momo', 'zalopay'].includes(pm) || ['grab', 'shopee'].includes(type)) {
            pmBadge = `<span class="badge bg-warning-subtle text-dark border border-warning-subtle">Thẻ / Ví App</span>`;
        }

        // Định dạng badge trạng thái đơn
        const st = o.order_status || 'completed';
        let stBadge = `<span class="badge bg-success-subtle text-success rounded-pill px-2.5">Hoàn thành</span>`;
        if (st === 'cancelled') {
            stBadge = `<span class="badge bg-danger-subtle text-danger rounded-pill px-2.5">Đã hủy</span>`;
        } else if (['pending', 'preparing', 'serving'].includes(st)) {
            stBadge = `<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2.5">Đang phục vụ</span>`;
        }

        const staffName = o.cashier_name || o.cashier_code || 'NV01';
        const custLine  = o.customer_name
            ? `<small class="text-muted d-block"><i class="fa-solid fa-user-check text-primary me-1" style="font-size:10px;"></i>${escapeHtml(o.customer_name)}</small>`
            : '';

        // Tính và hiển thị Doanh thu ròng (Thực thu) nếu đơn có chiết khấu hoa hồng sàn (Grab/Shopee)
        const finalAmt   = Number(o.final_amount || 0);
        const commRate   = Number(o.commission_rate || 0);
        const netAmt     = (o.net_amount !== null && o.net_amount !== undefined && o.net_amount !== '')
            ? Number(o.net_amount)
            : Math.round(finalAmt * (1 - commRate / 100));

        const netRevLine = (commRate > 0 || (netAmt > 0 && netAmt < finalAmt))
            ? `<small class="text-success fw-semibold d-block">Thực thu: ${Math.round(netAmt).toLocaleString('vi-VN')} đ${commRate > 0 ? ` (-${commRate}%)` : ''}</small>`
            : '';

        // Nút hủy đơn chỉ hiển thị khi người dùng có quyền Quản trị viên và đơn chưa bị hủy
        // Nút hủy đơn dùng data-attribute để tránh lỗi ký tự đặc biệt trên mã đơn cũ
        const cancelBtn = (OM_IS_ADMIN && st !== 'cancelled')
            ? `<button type="button" class="btn btn-sm btn-outline-danger rounded-3 ms-1" title="Hủy đơn / Hoàn tiền"
                       data-order-id="${o.id}" data-order-code="${escapeHtml(o.order_code)}"
                       onclick="openCancelOrderModalFromBtn(this)">
                   <i class="fa-solid fa-ban"></i>
               </button>`
            : '';
        return `
            <tr class="${st === 'cancelled' ? 'om-row-cancelled' : ''}">
                <td>
                    <div class="fw-bold text-primary">${escapeHtml(o.order_code)}</div>
                    ${o.app_order_code ? `<small class="text-muted d-block">Mã sàn: #${escapeHtml(o.app_order_code)}</small>` : ''}
                </td>
                <td>
                    <div class="fw-semibold text-dark">${formatDateTime(o.created_at)}</div>
                    <small class="text-muted">${Number(o.total_items || 0)} món</small>
                </td>
                <td>
                    <span class="om-channel-pill om-ch-${escapeHtml(type)}">
                        <i class="fa-solid ${chInfo.icon}"></i> ${escapeHtml(placeLabel)}
                    </span>
                </td>
                <td>
                    <div class="fw-medium text-dark">${escapeHtml(staffName)}</div>
                    ${custLine}
                </td>
                <td class="text-end">
                    <div class="fw-bold text-dark">${finalAmt.toLocaleString('vi-VN')} đ</div>
                    ${netRevLine}
                    ${Number(o.discount || 0) > 0 ? `<small class="text-danger d-block">Giảm ${Number(o.discount).toLocaleString('vi-VN')} đ</small>` : ''}
                </td>
                <td class="text-center">${pmBadge}</td>
                <td class="text-center">${stBadge}</td>
                <td class="text-end text-nowrap">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3" title="Xem chi tiết hóa đơn" onclick="openOrderDetail(${o.id}, false)">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light border rounded-3 ms-1" title="In lại hóa đơn" onclick="openOrderDetail(${o.id}, true)">
                        <i class="fa-solid fa-print text-dark"></i>
                    </button>
                    ${cancelBtn}
                </td>
            </tr>
        `;
    }).join('');
}

// Vẽ thanh phân trang
function renderPagination(current, totalPages, totalRecords) {
    document.getElementById('om-pagination-info').innerHTML =
        `Trang <strong>${current}</strong> / <strong>${totalPages}</strong> (Tổng cộng <strong>${Number(totalRecords).toLocaleString('vi-VN')}</strong> hóa đơn)`;

    const ul = document.getElementById('om-pagination-list');
    if (totalPages <= 1) {
        ul.innerHTML = '';
        return;
    }

    let html = `
        <li class="page-item ${current <= 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="loadOrders(${current - 1})">&laquo;</button>
        </li>
    `;

    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || Math.abs(p - current) <= 2) {
            html += `
                <li class="page-item ${p === current ? 'active' : ''}">
                    <button class="page-link" onclick="loadOrders(${p})">${p}</button>
                </li>
            `;
        } else if (p === 2 || p === totalPages - 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `
        <li class="page-item ${current >= totalPages ? 'disabled' : ''}">
            <button class="page-link" onclick="loadOrders(${current + 1})">&raquo;</button>
        </li>
    `;
    ul.innerHTML = html;
}

// Mở Modal xem chi tiết đơn hàng (Nếu autoPrint = true thì tự kích hoạt hộp thoại in ngay sau khi tải xong)
function openOrderDetail(orderId, autoPrint = false) {
    fetch(`${OM_API}/orderManage/getOrderDetail?id=${orderId}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.order) {
                showOmToast(res.message || 'Không thể tải chi tiết hóa đơn.', 'error');
                return;
            }

            const o = res.order;
            activeReceiptData = o;

            document.getElementById('md-order-code').innerText     = o.order_code;
            document.getElementById('md-order-subtitle').innerText = `Thu ngân: ${o.cashier_name || o.cashier_code || 'NV01'} • Ca: ${o.shift_name || 'Mặc định'}`;
            document.getElementById('md-created-at').innerText     = formatDateTime(o.created_at);

            const channelMap = { dine_in: 'Tại chỗ', takeaway: 'Mang về', grab: 'GrabFood', shopee: 'ShopeeFood' };
            const placeText = (o.order_type === 'dine_in' && o.table_name)
                ? `Tại chỗ (${o.table_name})`
                : (channelMap[o.order_type] || o.order_type);
            document.getElementById('md-channel-place').innerText = placeText;

            document.getElementById('md-customer-info').innerText = o.customer_name
                ? `${o.customer_name} (${o.customer_phone})`
                : 'Khách vãng lai';
            document.getElementById('md-points-earned').innerText = `+${Number(o.points_earned || 0)} điểm`;

            // Đổ danh sách món đã gọi
            const items = o.items || [];
            const totalQty = items.reduce((sum, it) => sum + Number(it.quantity || 0), 0);
            document.getElementById('md-total-qty').innerText = `${totalQty} món`;

            document.getElementById('md-items-tbody').innerHTML = items.map(it => {
                const optTags = [];
                if (it.size) optTags.push(`Size ${it.size}`);
                if (it.sugar) optTags.push(`${it.sugar} đường`);
                if (it.ice) optTags.push(`${it.ice} đá`);
                if (Array.isArray(it.toppings) && it.toppings.length) {
                    optTags.push('Topping: ' + it.toppings.map(t => t.name || t).join(', '));
                }
                const optLine  = optTags.length ? `<div class="text-muted small">${escapeHtml(optTags.join(' • '))}</div>` : '';
                const noteLine = it.item_note ? `<div class="text-primary small fst-italic">Ghi chú: ${escapeHtml(it.item_note)}</div>` : '';

                return `
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">${escapeHtml(it.product_name)}</div>
                            ${optLine}
                            ${noteLine}
                        </td>
                        <td class="text-center fw-bold">x${it.quantity}</td>
                        <td class="text-end text-muted">${Number(it.unit_price).toLocaleString('vi-VN')} đ</td>
                        <td class="text-end fw-bold text-dark">${Number(it.subtotal).toLocaleString('vi-VN')} đ</td>
                    </tr>
                `;
            }).join('');

            // Đổ chi tiết tính tiền
            const vatPct = Number(o.vat_percent ?? 7);
            const subTotal = Number(o.total_amount || 0);
            const discount = Number(o.discount || 0);
            const finalAmt = Number(o.final_amount || 0);
            const vatAmt   = Math.max(0, finalAmt - (subTotal - discount));

            document.getElementById('md-subtotal').innerText      = subTotal.toLocaleString('vi-VN') + ' đ';
            document.getElementById('md-discount').innerText      = '-' + discount.toLocaleString('vi-VN') + ' đ';
            document.getElementById('md-vat-label').innerText     = `Thuế VAT (${vatPct}%):`;
            document.getElementById('md-vat-amount').innerText    = Math.round(vatAmt).toLocaleString('vi-VN') + ' đ';
            document.getElementById('md-final-amount').innerText  = finalAmt.toLocaleString('vi-VN') + ' đ';
            document.getElementById('md-customer-pay').innerText  = Number(o.customer_pay || finalAmt).toLocaleString('vi-VN') + ' đ';
            document.getElementById('md-change-amount').innerText = Number(o.change_amount || 0).toLocaleString('vi-VN') + ' đ';

            // Đổ timeline Audit Log
            const logs = o.parsed_audit_log || [];
            document.getElementById('md-audit-log-list').innerHTML = logs.map(l => `
                <div class="om-audit-item">
                    <span class="om-audit-dot"></span>
                    <div class="small fw-bold text-dark">${escapeHtml(l.time)} — ${escapeHtml(l.actor)}</div>
                    <div class="small text-muted">${escapeHtml(l.action)}</div>
                </div>
            `).join('');

            // Hiển thị nút Hủy đơn trong Modal nếu có quyền Admin và đơn chưa hủy
            const cancelBtn = document.getElementById('md-btn-cancel-order');
            if (cancelBtn) {
                if (o.order_status !== 'cancelled') {
                    cancelBtn.classList.remove('d-none');
                    cancelBtn.onclick = () => {
                        orderDetailModal.hide();
                        openCancelOrderModal(o.id, o.order_code);
                    };
                } else {
                    cancelBtn.classList.add('d-none');
                }
            }

            if (autoPrint) {
                printCurrentReceipt();
            } else {
                orderDetailModal.show();
            }
        });
}

// Dựng mẫu phiếu in nhiệt 80mm và gọi lệnh in trình duyệt
function printCurrentReceipt() {
    if (!activeReceiptData) return;
    const o = activeReceiptData;
    const printZone = document.getElementById('omReceiptPrintZone');

    const itemsHtml = (o.items || []).map(it => `
        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span>${escapeHtml(it.product_name)} x${it.quantity}</span>
            <span>${Number(it.subtotal).toLocaleString('vi-VN')}</span>
        </div>
    `).join('');

    printZone.innerHTML = `
        <div style="text-align:center; border-bottom:1px dashed #000; padding-bottom:6px; margin-bottom:8px;">
            <strong style="font-size:14px;">SMART F&B POS</strong><br>
            <span>HÓA ĐƠN THANH TOÁN (IN LẠI)</span><br>
            <span>Số HĐ: ${escapeHtml(o.order_code)}</span><br>
            <span>Ngày: ${formatDateTime(o.created_at)}</span>
        </div>
        <div style="margin-bottom:8px; border-bottom:1px dashed #000; padding-bottom:6px;">
            <div>Thu ngân: ${escapeHtml(o.cashier_name || 'NV01')}</div>
            <div>Khách hàng: ${escapeHtml(o.customer_name || 'Khách vãng lai')}</div>
        </div>
        <div style="border-bottom:1px dashed #000; padding-bottom:6px; margin-bottom:8px;">
            ${itemsHtml}
        </div>
        <div style="display:flex; justify-content:space-between;"><span>Tạm tính:</span><span>${Number(o.total_amount).toLocaleString('vi-VN')}đ</span></div>
        <div style="display:flex; justify-content:space-between;"><span>Giảm giá:</span><span>-${Number(o.discount).toLocaleString('vi-VN')}đ</span></div>
        <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:13px; margin-top:4px;">
            <span>TỔNG CỘNG:</span><span>${Number(o.final_amount).toLocaleString('vi-VN')}đ</span>
        </div>
        <div style="text-align:center; margin-top:12px; border-top:1px dashed #000; padding-top:6px;">
            Cảm ơn quý khách & Hẹn gặp lại!
        </div>
    `;

    printZone.classList.remove('d-none');
    window.print();
    printZone.classList.add('d-none');
}

// Mở hộp thoại xác nhận hủy đơn
function openCancelOrderModal(orderId, orderCode) {
    document.getElementById('cancel-order-id').value = orderId;
    document.getElementById('cancel-order-code-label').innerText = orderCode;
    document.getElementById('cancel-reason-input').value = '';
    cancelOrderModal.show();
}
function openCancelOrderModalFromBtn(btn) {
    const orderId   = Number(btn.getAttribute('data-order-id'));
    const orderCode = btn.getAttribute('data-order-code') || '';
    openCancelOrderModal(orderId, orderCode);
}
function setCancelReason(text) {
    document.getElementById('cancel-reason-input').value = text;
}

// Gửi yêu cầu hủy hóa đơn lên máy chủ
function submitCancelOrder() {
    const orderId = Number(document.getElementById('cancel-order-id').value);
    const reason  = document.getElementById('cancel-reason-input').value.trim();

    if (!reason) {
        showOmToast('Vui lòng nhập lý do hủy hóa đơn trước khi xác nhận.', 'error');
        return;
    }

    fetch(`${OM_API}/orderManage/cancelOrder`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, reason: reason })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            showOmToast(res.message || 'Không thể thực hiện hủy hóa đơn.', 'error');
            return;
        }
        cancelOrderModal.hide();
        showOmToast(res.message);
        loadOrders(currentPage);
    });
}

// Xuất báo cáo Excel theo đúng bộ lọc đang áp dụng
function exportOrdersExcel() {
    const params = new URLSearchParams({
        keyword:     document.getElementById('om-search-input').value.trim(),
        date_preset: activeDatePreset,
        date_from:   document.getElementById('om-date-from').value,
        date_to:     document.getElementById('om-date-to').value,
        channel:     document.getElementById('om-filter-channel').value,
        status:      document.getElementById('om-filter-status').value,
        payment:     document.getElementById('om-filter-payment').value
    });
    window.location.href = `${OM_API}/orderManage/exportExcel?${params.toString()}`;
}

function formatDateTime(dateStr) {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    return d.toLocaleString('vi-VN', {
        hour: '2-digit', minute: '2-digit',
        day: '2-digit', month: '2-digit', year: 'numeric'
    });
}

function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
}
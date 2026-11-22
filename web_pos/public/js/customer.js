// public/js/customer.js - Customer & Loyalty CRM Logic

const CRM_API = typeof CRM_BASE_URL !== 'undefined' ? CRM_BASE_URL : '/POS_AI_PROJECT/web_pos/public';
let currentRankFilter   = 'ALL';
let customerListCache   = [];
let rewardsListCache    = [];
let loyaltyPolicyCache  = null;
let activeDetailCustId  = 0;

let customerFormModal   = null;
let customerDetailModal = null;
let rewardFormModal     = null;
let policyEditModal     = null;
let deleteRewardModal   = null;
let pendingDeleteRwId   = 0;
let crmToastInstance    = null;

document.addEventListener('DOMContentLoaded', () => {
    customerFormModal   = new bootstrap.Modal(document.getElementById('customerFormModal'));
    customerDetailModal = new bootstrap.Modal(document.getElementById('customerDetailModal'));
    rewardFormModal     = new bootstrap.Modal(document.getElementById('rewardFormModal'));
    policyEditModal     = new bootstrap.Modal(document.getElementById('policyEditModal'));
    crmToastInstance    = new bootstrap.Toast(document.getElementById('crmToast'), { delay: 3200 });
    deleteRewardModal = new bootstrap.Modal(document.getElementById('deleteRewardConfirmModal'));
    document.getElementById('btn-confirm-delete-reward').addEventListener('click', () => {
        if (!pendingDeleteRwId) return;
        fetch(`${CRM_API}/customer/deleteReward`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: pendingDeleteRwId })
        })
        .then(r => r.json())
        .then(res => {
            deleteRewardModal.hide();
            if (res.success) {
                showCrmToast(res.message);
                loadRewardsCatalog();
            } else {
                showCrmToast(res.message || 'Không thể xóa phần quà!', 'error');
            }
        });
    });

    document.querySelectorAll('#crm-rank-pills .crm-rank-filter').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#crm-rank-pills .crm-rank-filter').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentRankFilter = btn.dataset.rank;
            loadCustomers();
        });
    });

    let searchTimer = null;
    document.getElementById('crm-search-input').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadCustomers, 250);
    });

    loadCustomers();
    loadLoyaltySettings();
});

function showCrmToast(message, type = 'success') {
    const toastEl = document.getElementById('crmToast');
    const msgEl   = document.getElementById('crmToastMsg');
    toastEl.className = `toast align-items-center border-0 shadow-lg rounded-3 text-white ${type === 'error' ? 'bg-danger' : 'bg-dark'}`;
    msgEl.innerHTML   = `<i class="fa-solid ${type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check text-success'} me-2"></i>${escapeHtml(message)}`;
    crmToastInstance.show();
}

function formatRelativeVisit(dateStr) {
    if (!dateStr) return 'Chưa phát sinh đơn';
    const d = new Date(dateStr);
    const now = new Date();
    const diffDays = Math.floor((now - d) / (1000 * 60 * 60 * 24));
    if (diffDays <= 0) return 'Hôm nay';
    if (diffDays === 1) return 'Hôm qua';
    if (diffDays < 30) return `${diffDays} ngày trước`;
    return d.toLocaleDateString('vi-VN');
}

function loadCustomers() {
    const q    = encodeURIComponent(document.getElementById('crm-search-input').value.trim());
    const sort = encodeURIComponent(document.getElementById('crm-sort-select').value);

    fetch(`${CRM_API}/customer/getCustomers?q=${q}&rank=${currentRankFilter}&sort=${sort}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;

            const kpi = res.kpi || {};
            document.getElementById('kpi-total-members').innerText  = Number(kpi.total_members || 0).toLocaleString();
            document.getElementById('kpi-new-month').innerText      = Number(kpi.new_this_month || 0).toLocaleString();
            document.getElementById('kpi-vip-members').innerText    = Number(kpi.vip_diamond_members || 0).toLocaleString();
            document.getElementById('kpi-retention-rate').innerText = Number(kpi.retention_rate || 0) + '%';

            customerListCache = res.customers || [];
            renderCustomersTable(customerListCache);
        });
}

function renderCustomersTable(list) {
    const tbody = document.getElementById('crm-customers-tbody');
    if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted">Không tìm thấy hội viên phù hợp.</td></tr>`;
        return;
    }

    const currentMonth = new Date().getMonth() + 1;

    tbody.innerHTML = list.map(c => {
        const initials = (c.customer_name || 'KH').split(' ').map(w => w[0]).slice(-2).join('').toUpperCase();
        const rank     = c.member_rank || 'Bronze';
        const pts      = Number(c.total_points || 0).toLocaleString();
        const spend    = Number(c.lifetime_spend || 0).toLocaleString() + ' đ';
        const orders   = Number(c.total_orders || 0);
        const lastVisitText = formatRelativeVisit(c.last_order_at);

        let dobHtml = 'Chưa cập nhật';
        let isBdayMonth = false;
        if (c.dob) {
            const dObj = new Date(c.dob);
            dobHtml = dObj.toLocaleDateString('vi-VN');
            if ((dObj.getMonth() + 1) === currentMonth) isBdayMonth = true;
        }
        const addrText = c.address ? escapeHtml(c.address) : '--';

        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="cust-avatar">${initials}</div>
                        <div>
                            <div class="fw-bold text-dark">${escapeHtml(c.customer_name)}</div>
                            <small class="text-muted"><i class="fa-solid fa-phone me-1" style="font-size:10px;"></i>${escapeHtml(c.phone)}</small>
                        </div>
                    </div>
                </td>
                <td class="text-center"><span class="rank-pill rank-${rank}">${rank}</span></td>
                <td class="text-end fw-bold text-primary">${pts} pt</td>
                <td class="text-end fw-bold text-dark">${spend}</td>
                <td class="text-center">
                    <span class="badge bg-light text-dark border fw-semibold">${orders} đơn</span>
                    <small class="text-muted d-block mt-1" style="font-size: 11px;">${lastVisitText}</small>
                </td>
                <td class="text-center">
                    <div class="small text-dark">
                        <i class="fa-solid fa-cake-candles text-warning me-1"></i>${dobHtml}
                        ${isBdayMonth ? '<span class="badge bg-danger-subtle text-danger ms-1" style="font-size:10px;">Sinh nhật tháng này</span>' : ''}
                    </div>
                    <small class="text-muted d-block text-truncate mx-auto" style="max-width: 210px;">${addrText}</small>
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3 fw-semibold me-1" onclick="openCustomerDetail(${c.id})">
                        <i class="fa-solid fa-eye me-1"></i>Chi tiết
                    </button>
                    <button type="button" class="btn btn-sm btn-light border rounded-3" onclick="openCustomerFormModal(${c.id})" title="Chỉnh sửa">
                        <i class="fa-solid fa-pen text-secondary"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function openCustomerFormModal(id = 0) {
    document.getElementById('cust-form-id').value      = id;
    document.getElementById('cust-form-name').value    = '';
    document.getElementById('cust-form-phone').value   = '';
    document.getElementById('cust-form-rank').value    = 'Bronze';
    document.getElementById('cust-form-dob').value     = '';
    document.getElementById('cust-form-email').value   = '';
    document.getElementById('cust-form-address').value = '';
    document.getElementById('customerFormModalTitle').innerText = id > 0 ? 'Chỉnh Sửa Hồ Sơ Hội Viên' : 'Thêm Hội Viên Mới';

    if (id > 0) {
        const c = customerListCache.find(item => Number(item.id) === Number(id));
        if (c) {
            document.getElementById('cust-form-name').value    = c.customer_name || '';
            document.getElementById('cust-form-phone').value   = c.phone || '';
            document.getElementById('cust-form-rank').value    = c.member_rank || 'Bronze';
            document.getElementById('cust-form-dob').value     = c.dob || '';
            document.getElementById('cust-form-email').value   = c.email || '';
            document.getElementById('cust-form-address').value = c.address || '';
        }
    }
    customerFormModal.show();
}

function saveCustomerForm() {
    const emailVal = document.getElementById('cust-form-email').value.trim();
    if (emailVal && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
        showCrmToast('Email không đúng định dạng (ví dụ: khachhang@gmail.com)!', 'error');
        return;
    }

    const payload = {
        id:            document.getElementById('cust-form-id').value,
        customer_name: document.getElementById('cust-form-name').value.trim(),
        phone:         document.getElementById('cust-form-phone').value.trim(),
        member_rank:   document.getElementById('cust-form-rank').value,
        dob:           document.getElementById('cust-form-dob').value,
        email:         emailVal,
        address:       document.getElementById('cust-form-address').value.trim()
    };

    fetch(`${CRM_API}/customer/saveCustomer`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            showCrmToast(res.message || 'Có lỗi xảy ra!', 'error');
            return;
        }
        customerFormModal.hide();
        showCrmToast(res.message || 'Đã lưu hồ sơ hội viên!');
        loadCustomers();
    });
}

function quickFillPoints(pts, reason) {
    document.getElementById('adjust-points-input').value = pts;
    document.getElementById('adjust-reason-input').value = reason;
}

function openCustomerDetail(id) {
    activeDetailCustId = Number(id);
    fetch(`${CRM_API}/customer/getCustomerDetail?id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.customer) return;

            const c     = res.customer;
            const rank  = c.member_rank || 'Bronze';
            const spend = Number(c.lifetime_spend || 0);
            const pts   = Number(c.total_points || 0);

            const idEl = document.getElementById('adjust-cust-id');
            if (idEl) {
                idEl.value = c.id;
                document.getElementById('adjust-points-input').value = '';
                document.getElementById('adjust-reason-input').value = '';
            }

            const cardEl = document.getElementById('detail-virtual-card');
            cardEl.className = `member-virtual-card card-${rank} mb-3`;

            document.getElementById('detail-cust-name').innerText    = c.customer_name;
            document.getElementById('detail-cust-phone').innerText   = c.phone;
            document.getElementById('detail-cust-rank').innerText    = rank.toUpperCase();
            document.getElementById('detail-cust-points').innerText  = pts.toLocaleString() + ' pt';
            document.getElementById('detail-cust-spend').innerText   = spend.toLocaleString() + ' đ';
            document.getElementById('detail-cust-dob').innerText     = c.dob ? new Date(c.dob).toLocaleDateString('vi-VN') : 'Chưa cập nhật';
            document.getElementById('detail-cust-address').innerText = c.address || 'Chưa có địa chỉ';

            const emailEl = document.getElementById('detail-cust-email');
            const mailBtn = document.getElementById('btn-send-bday-mail');
            if (emailEl) {
                emailEl.innerText = c.email || 'Chưa có email';
                emailEl.title = c.email || '';
            }
            if (mailBtn) {
                if (c.email) {
                    mailBtn.classList.remove('d-none');
                    mailBtn.disabled = false;
                    mailBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Gửi mail';
                } else {
                    mailBtn.classList.add('d-none');
                }
            }

            const pol = loyaltyPolicyCache || {};
            const nextMilestones = {
                Bronze:  { next: 'Silver',  target: Number(pol.silver_min_spend || 1000000) },
                Silver:  { next: 'Gold',    target: Number(pol.gold_min_spend || 3000000) },
                Gold:    { next: 'Diamond', target: Number(pol.diamond_min_spend || 6000000) },
                Diamond: { next: 'VIP',     target: Number(pol.vip_min_spend || 10000000) },
                VIP:     { next: 'MAX',     target: Number(pol.vip_min_spend || 10000000) }
            };
            const info = nextMilestones[rank] || nextMilestones.Bronze;
            if (info.next === 'MAX') {
                document.getElementById('detail-tier-progress').style.width = '100%';
                document.getElementById('detail-tier-next-text').innerText = 'Đã đạt hạng thẻ cao nhất hệ thống (VIP)';
            } else if (spend >= info.target) {
                document.getElementById('detail-tier-progress').style.width = '100%';
                document.getElementById('detail-tier-next-text').innerText = `Đủ điều kiện nâng hạng ${info.next} ở đơn tiếp theo`;
            } else {
                const pct = Math.min(100, Math.round((spend / info.target) * 100));
                const remain = Math.max(0, info.target - spend);
                document.getElementById('detail-tier-progress').style.width = pct + '%';
                document.getElementById('detail-tier-next-text').innerText = `Chi tiêu thêm ${remain.toLocaleString()}đ để lên hạng ${info.next}`;
            }

            const st = res.stats || {};
            document.getElementById('insight-avg-order').innerText = Math.round(Number(st.avg_order_value || 0)).toLocaleString() + ' đ';
            const favEl = document.getElementById('insight-fav-item');
            favEl.innerText = st.favorite_item || 'Chưa có';
            favEl.title = st.favorite_item || '';
            document.getElementById('insight-used-pts').innerText = Number(st.total_redeemed || 0).toLocaleString() + ' pt';
            document.getElementById('insight-last-visit').innerText = st.last_visit
                ? new Date(st.last_visit).toLocaleDateString('vi-VN')
                : 'Chưa mua';

            // Lịch sử mua hàng
            const orders = res.orders || [];
            document.getElementById('detail-order-count').innerText = orders.length;
            const typeMap = { dine_in: 'Tại chỗ', takeaway: 'Mang về', grab: 'GrabFood', shopee: 'ShopeeFood' };
            document.getElementById('detail-orders-tbody').innerHTML = orders.length === 0
                ? `<tr><td colspan="6" class="text-center py-4 text-muted">Chưa có đơn hàng nào.</td></tr>`
                : orders.map(o => `
                    <tr>
                        <td class="fw-bold text-primary">${escapeHtml(o.order_code || ('#' + o.id))}</td>
                        <td><span class="badge bg-light text-dark border">${typeMap[o.order_type] || 'Tại chỗ'}</span></td>
                        <td class="text-muted">${new Date(o.created_at).toLocaleString('vi-VN')}</td>
                        <td class="text-end fw-bold text-dark">${Number(o.final_amount || 0).toLocaleString()} đ</td>
                        <td class="text-center"><span class="badge bg-success-subtle text-success fw-bold">+${Number(o.points_earned || 0)} pt</span></td>
                        <td class="text-center">${Number(o.points_redeemed || 0) > 0 ? `<span class="badge bg-danger-subtle text-danger fw-bold">-${o.points_redeemed} pt</span>` : '<span class="text-muted">-</span>'}</td>
                    </tr>
                `).join('');

            // Biến động điểm (Đã hợp nhất log mới + cũ)
            const logs = res.point_logs || [];
            document.getElementById('detail-ptlog-count').innerText = logs.length;
            const logTypeBadge = {
                earn: '<span class="badge bg-success-subtle text-success">Tích điểm</span>',
                redeem_bill: '<span class="badge bg-warning-subtle text-warning">Đổi điểm</span>',
                redeem_gift: '<span class="badge bg-info-subtle text-info">Đổi quà</span>',
                adjust: '<span class="badge bg-primary-subtle text-primary">Điều chỉnh</span>'
            };
            document.getElementById('detail-ptlogs-tbody').innerHTML = logs.length === 0
                ? `<tr><td colspan="5" class="text-center py-4 text-muted">Chưa có lịch sử biến động điểm.</td></tr>`
                : logs.map(l => {
                    const chg = Number(l.points_change || 0);
                    const balStr = (l.balance_after !== null && l.balance_after !== undefined)
                        ? Number(l.balance_after).toLocaleString() + ' pt'
                        : '<span class="text-muted">--</span>';
                    return `
                        <tr>
                            <td class="text-muted">${new Date(l.created_at).toLocaleString('vi-VN')}</td>
                            <td>${logTypeBadge[l.type] || logTypeBadge.earn}</td>
                            <td class="fw-medium text-dark">${escapeHtml(l.description || '')}</td>
                            <td class="text-end fw-bold ${chg >= 0 ? 'text-success' : 'text-danger'}">${chg >= 0 ? '+' + chg : chg} pt</td>
                            <td class="text-end fw-bold text-dark">${balStr}</td>
                        </tr>
                    `;
                }).join('');

            // Tab con 3: Top Món Tủ & Quà Đã Đổi
            const topBox = document.getElementById('detail-top-items-list');
            if (topBox) {
                if (!res.top_items || res.top_items.length === 0) {
                    topBox.innerHTML = '<div class="text-muted small py-3 text-center">Chưa có dữ liệu món yêu thích</div>';
                } else {
                    topBox.innerHTML = res.top_items.map((item, idx) => `
                        <div class="d-flex justify-content-between align-items-center bg-white px-3 py-2 rounded-3 border">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-dark rounded-pill">#${idx + 1}</span>
                                <span class="fw-semibold text-dark">${escapeHtml(item.product_name)}</span>
                            </div>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">x${item.total_qty}</span>
                        </div>
                    `).join('');
                }
            }

            const giftBox = document.getElementById('detail-redeemed-gifts-list');
            if (giftBox) {
                if (!res.redeemed_gifts || res.redeemed_gifts.length === 0) {
                    giftBox.innerHTML = '<div class="text-muted small py-3 text-center">Khách chưa đổi phần quà nào</div>';
                } else {
                    giftBox.innerHTML = res.redeemed_gifts.map(g => {
                        const title = (g.reward_title || g.product_name || 'Quà đổi điểm')
                            .replace(/^Đổi quà:\s*/i, '')
                            .trim();
                        const ptsBadge = Number(g.points_used) > 0 ? `-${Number(g.points_used).toLocaleString()} pt` : 'Đã đổi';
                        const timeStr = g.created_at ? new Date(g.created_at).toLocaleString('vi-VN') : '';
                        return `
                        <div class="d-flex justify-content-between align-items-center bg-white px-3 py-2 rounded-3 border">
                            <div>
                                <div class="fw-semibold text-dark">${escapeHtml(title)}</div>
                                <div class="text-muted" style="font-size:11px;">${timeStr}</div>
                            </div>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">${ptsBadge}</span>
                        </div>`;
                    }).join('');
                }
            }

            customerDetailModal.show();
        });
}
function sendBirthdayMailNow() {
    if (!activeDetailCustId) return;
    const btn = document.getElementById('btn-send-bday-mail');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Đang gửi...';

    fetch(`${CRM_API}/customer/sendBirthdayEmail`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ customer_id: activeDetailCustId })
    })
    .then(async r => {
        const text = await r.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error(text.replace(/<[^>]*>/g, '').trim().slice(0, 180));
        }
    })
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Gửi mail';
        if (!res.success) {
            showCrmToast(res.message || 'Gửi email thất bại!', 'error');
            return;
        }
        showCrmToast(res.message || 'Đã gửi email chúc mừng sinh nhật!');
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Gửi mail';
        showCrmToast(err.message || 'Lỗi kết nối máy chủ gửi mail!', 'error');
    });
}

function submitAdjustPoints() {
    const custId = Number(document.getElementById('adjust-cust-id').value);
    const delta  = Number(document.getElementById('adjust-points-input').value);
    const reason = document.getElementById('adjust-reason-input').value.trim();

    if (!delta || delta === 0) {
        showCrmToast('Vui lòng nhập số điểm thay đổi (+ hoặc -)!', 'error');
        return;
    }

    fetch(`${CRM_API}/customer/adjustPoints`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ customer_id: custId, points_delta: delta, reason: reason })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            showCrmToast(res.message || 'Lỗi điều chỉnh điểm!', 'error');
            return;
        }
        showCrmToast(res.message);
        openCustomerDetail(custId);
        loadCustomers();
    });
}

// ================= KHO QUÀ ĐỔI ĐIỂM =================
function loadRewardsCatalog() {
    fetch(`${CRM_API}/customer/getRewards`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            rewardsListCache = res.rewards || [];
            const grid = document.getElementById('crm-rewards-grid');

            if (!rewardsListCache.length) {
                grid.innerHTML = `<div class="col-12 text-center py-5 text-muted">Chưa có quà đổi điểm nào trong kho.</div>`;
                return;
            }

            grid.innerHTML = rewardsListCache.map(rw => {
                const active = Number(rw.status ?? rw.is_active ?? 1) === 1;
                return `
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="reward-catalog-card ${active ? '' : 'inactive-reward'}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="rank-pill rank-${rw.min_rank}">Từ hạng ${rw.min_rank}</span>
                                <span class="badge ${active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'}">
                                    ${active ? 'Đang mở trên POS' : 'Tạm ẩn'}
                                </span>
                            </div>
                            <h6 class="fw-bold text-dark mt-1 mb-1">${escapeHtml(rw.reward_name)}</h6>
                            <p class="text-muted small mb-3 flex-grow-1">${escapeHtml(rw.description || 'Ưu đãi hội viên Smart F&B')}</p>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <div>
                                    <span class="fw-bold text-primary fs-6">${Number(rw.points_required).toLocaleString()} pt</span>
                                    <small class="text-muted ms-2">Còn ${rw.stock} mã </small>
                                </div>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-light border rounded-3" onclick="openRewardModal(${rw.id})">
                                        <i class="fa-solid fa-pen text-secondary"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-3" onclick="deleteRewardItem(${rw.id})">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        });
}

function openRewardModal(id = 0) {
    document.getElementById('reward-form-id').value       = id;
    document.getElementById('reward-form-name').value     = '';
    document.getElementById('reward-form-pts').value   = '';
    document.getElementById('reward-form-rank').value     = 'Bronze';
    document.getElementById('reward-form-stock').value    = 100;
    document.getElementById('reward-form-desc').value     = '';
    document.getElementById('reward-form-active').checked = true;
    document.getElementById('rewardFormModalTitle').innerText = id > 0 ? 'Chỉnh Sửa Quà Đổi Điểm' : 'Thêm Quà Đổi Điểm Mới';

    if (id > 0) {
        const rw = rewardsListCache.find(item => Number(item.id) === Number(id));
        if (rw) {
            document.getElementById('reward-form-name').value     = rw.reward_name || '';
            document.getElementById('reward-form-pts').value   = rw.points_required || '';
            document.getElementById('reward-form-rank').value     = rw.min_rank || 'Bronze';
            document.getElementById('reward-form-stock').value    = rw.stock ?? 100;
            document.getElementById('reward-form-desc').value     = rw.description || '';
            document.getElementById('reward-form-active').checked = Number(rw.status ?? rw.is_active ?? 1) === 1;
        }
    }
    rewardFormModal.show();
}

function saveRewardForm() {
    const modalEl = document.getElementById('rewardFormModal');
    const ptsInput = modalEl.querySelector('#reward-form-pts') || modalEl.querySelectorAll('input[type="number"]')[0];

    const payload = {
        id:              document.getElementById('reward-form-id').value,
        reward_name:     document.getElementById('reward-form-name').value.trim(),
        points_required: Number(ptsInput ? ptsInput.value : 0),
        min_rank:        document.getElementById('reward-form-rank').value,
        stock:           Number(document.getElementById('reward-form-stock').value),
        description:     document.getElementById('reward-form-desc').value.trim(),
        is_active:       document.getElementById('reward-form-active').checked ? 1 : 0
    };

    fetch(`${CRM_API}/customer/saveReward`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            showCrmToast(res.message || 'Lỗi lưu phần quà!', 'error');
            return;
        }
        rewardFormModal.hide();
        showCrmToast(res.message);
        loadRewardsCatalog();
    });
}
function deleteRewardItem(id) {
    pendingDeleteRwId = Number(id);
    const rw = rewardsListCache.find(item => Number(item.id) === pendingDeleteRwId);
    document.getElementById('del-reward-name-label').innerText = rw ? `"${rw.reward_name}"` : 'này';
    deleteRewardModal.show();
}
// ================= CHÍNH SÁCH HẠNG THẺ =================
function loadLoyaltySettings() {
    fetch(`${CRM_API}/customer/getLoyaltySettings`)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.settings) return;
            const s = res.settings;
            loyaltyPolicyCache = s;

            document.getElementById('policy-earn-rate-text').innerText   = `${Number(s.vnd_per_point_earn).toLocaleString()}đ chi tiêu = 1 điểm`;
            document.getElementById('policy-redeem-rate-text').innerText = `1 điểm = ${Number(s.vnd_per_point_redeem).toLocaleString()}đ giảm giá bill`;

            const rows = [
                { rank: 'Bronze',  cond: 'Thành viên đăng ký mới (0đ)', disc: s.bronze_discount, mult: s.bronze_multiplier, priv: 'Đổi các phần quà hạng Bronze' },
                { rank: 'Silver',  cond: `Từ ${Number(s.silver_min_spend).toLocaleString()}đ trở lên`, disc: s.silver_discount, mult: s.silver_multiplier, priv: 'Mở khóa quà hạng Silver & ưu đãi sinh nhật' },
                { rank: 'Gold',    cond: `Từ ${Number(s.gold_min_spend).toLocaleString()}đ trở lên`, disc: s.gold_discount, mult: s.gold_multiplier, priv: 'Mở khóa quà hạng Gold & tặng thức uống miễn phí' },
                { rank: 'Diamond', cond: `Từ ${Number(s.diamond_min_spend).toLocaleString()}đ trở lên`, disc: s.diamond_discount, mult: s.diamond_multiplier, priv: 'Mở khóa quà cao cấp hạng Diamond' },
                { rank: 'VIP',     cond: `Từ ${Number(s.vip_min_spend).toLocaleString()}đ trở lên`, disc: s.vip_discount, mult: s.vip_multiplier, priv: 'Mở khóa toàn bộ kho quà VIP & ưu tiên phục vụ' }
            ];

            document.getElementById('policy-tiers-tbody').innerHTML = rows.map(r => `
                <tr>
                    <td><span class="rank-pill rank-${r.rank}">${r.rank}</span></td>
                    <td>${r.cond}</td>
                    <td class="text-center fw-bold ${Number(r.disc) > 0 ? 'text-success' : 'text-muted'}">${Number(r.disc) > 0 ? '-' + r.disc + '% tự động' : '0%'}</td>
                    <td class="text-center fw-bold text-primary">x${Number(r.mult).toFixed(1)} điểm</td>
                    <td>${r.priv}</td>
                </tr>
            `).join('');
        });
}

function openPolicyEditModal() {
    if (loyaltyPolicyCache) {
        const s = loyaltyPolicyCache;
        document.getElementById('pol-earn-rate').value    = s.vnd_per_point_earn;
        document.getElementById('pol-redeem-rate').value  = s.vnd_per_point_redeem;
        document.getElementById('pol-silver-min').value   = s.silver_min_spend;
        document.getElementById('pol-gold-min').value     = s.gold_min_spend;
        document.getElementById('pol-diamond-min').value  = s.diamond_min_spend;
        document.getElementById('pol-vip-min').value      = s.vip_min_spend;
        document.getElementById('pol-bronze-disc').value  = s.bronze_discount;
        document.getElementById('pol-silver-disc').value  = s.silver_discount;
        document.getElementById('pol-gold-disc').value    = s.gold_discount;
        document.getElementById('pol-diamond-disc').value = s.diamond_discount;
        document.getElementById('pol-vip-disc').value     = s.vip_discount;
        document.getElementById('pol-bronze-mult').value  = s.bronze_multiplier;
        document.getElementById('pol-silver-mult').value  = s.silver_multiplier;
        document.getElementById('pol-gold-mult').value    = s.gold_multiplier;
        document.getElementById('pol-diamond-mult').value = s.diamond_multiplier;
        document.getElementById('pol-vip-mult').value     = s.vip_multiplier;
    }
    policyEditModal.show();
}

function saveLoyaltySettings() {
    const payload = {
        vnd_per_point_earn:   document.getElementById('pol-earn-rate').value,
        vnd_per_point_redeem: document.getElementById('pol-redeem-rate').value,
        silver_min_spend:     document.getElementById('pol-silver-min').value,
        gold_min_spend:       document.getElementById('pol-gold-min').value,
        diamond_min_spend:    document.getElementById('pol-diamond-min').value,
        vip_min_spend:        document.getElementById('pol-vip-min').value,
        bronze_discount:      document.getElementById('pol-bronze-disc').value,
        silver_discount:      document.getElementById('pol-silver-disc').value,
        gold_discount:        document.getElementById('pol-gold-disc').value,
        diamond_discount:     document.getElementById('pol-diamond-disc').value,
        vip_discount:         document.getElementById('pol-vip-disc').value,
        bronze_multiplier:    document.getElementById('pol-bronze-mult').value,
        silver_multiplier:    document.getElementById('pol-silver-mult').value,
        gold_multiplier:      document.getElementById('pol-gold-mult').value,
        diamond_multiplier:   document.getElementById('pol-diamond-mult').value,
        vip_multiplier:       document.getElementById('pol-vip-mult').value
    };

    fetch(`${CRM_API}/customer/saveLoyaltySettings`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            showCrmToast(res.message || 'Lỗi lưu chính sách!', 'error');
            return;
        }
        policyEditModal.hide();
        showCrmToast(res.message);
        loadLoyaltySettings();
    });
}

function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
}
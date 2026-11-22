<?php
// app/Controllers/CustomerController.php

class CustomerController extends Controller {

    public function __construct() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
    }

    private function isAdminRole() {
        $role = strtolower(trim($_SESSION['user']['role'] ?? 'admin'));
        return ($role === 'admin' || $role === 'manager' || $role === 'quản trị viên');
    }

    public function index() {
        $this->view('customer/index', [
            'isAdmin' => $this->isAdminRole()
        ]);
    }

    // API 1: Lấy thống kê KPI (Tổng TV, Khách mới tháng này, VIP/Diamond, Tỷ lệ quay lại) + Danh sách
    public function getCustomers() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance();
            $keyword = trim($_GET['q'] ?? '');
            $rank    = trim($_GET['rank'] ?? 'ALL');
            $sort    = trim($_GET['sort'] ?? 'spend_desc');

            $kpiStmt = $db->query("SELECT 
                COUNT(*) AS total_members,
                SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) AS new_this_month,
                SUM(CASE WHEN member_rank IN ('Diamond','VIP') THEN 1 ELSE 0 END) AS vip_diamond_members
                FROM customers");
            $kpi = $kpiStmt->fetch(PDO::FETCH_ASSOC);

            // Tính tỷ lệ khách quay lại (% khách mua từ 2 đơn completed trở lên)
            $retStmt = $db->query("SELECT 
                COUNT(DISTINCT customer_id) AS buyers_count,
                SUM(CASE WHEN cnt >= 2 THEN 1 ELSE 0 END) AS repeat_buyers
                FROM (
                    SELECT customer_id, COUNT(*) AS cnt 
                    FROM orders 
                    WHERE customer_id IS NOT NULL AND order_status = 'completed' 
                    GROUP BY customer_id
                ) t");
            $ret = $retStmt->fetch(PDO::FETCH_ASSOC);
            $totalMembers = max(1, (int)($kpi['total_members'] ?? 0));
            $repeatBuyers = (int)($ret['repeat_buyers'] ?? 0);
            $kpi['retention_rate'] = round(($repeatBuyers / $totalMembers) * 100, 1);

            $where  = [];
            $params = [];

            if ($keyword !== '') {
                $where[]  = "(c.customer_name LIKE ? OR c.phone LIKE ?)";
                $params[] = "%$keyword%";
                $params[] = "%$keyword%";
            }
            if ($rank !== '' && $rank !== 'ALL') {
                $where[]  = "c.member_rank = ?";
                $params[] = $rank;
            }

            $whereSql = count($where) > 0 ? ("WHERE " . implode(" AND ", $where)) : "";

            $orderSql = match ($sort) {
                'points_desc' => "c.total_points DESC, c.lifetime_spend DESC",
                'recent_desc' => "last_order_at DESC, c.lifetime_spend DESC",
                'orders_desc' => "total_orders DESC, c.lifetime_spend DESC",
                'newest'      => "c.id DESC",
                default       => "c.lifetime_spend DESC, c.total_points DESC"
            };

            $sql = "SELECT c.*, c.birthday AS dob,
                    COUNT(o.id) AS total_orders,
                    MAX(o.created_at) AS last_order_at
                    FROM customers c
                    LEFT JOIN orders o ON o.customer_id = c.id AND o.order_status = 'completed'
                    $whereSql
                    GROUP BY c.id
                    ORDER BY $orderSql";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'   => true,
                'kpi'       => $kpi,
                'customers' => $customers
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // API 2: Xem chi tiết 1 khách hàng (Hợp nhất lịch sử điểm mới + cũ, Top món tủ & Quà đã đổi)
    public function getCustomerDetail() {
        header('Content-Type: application/json');
        try {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID khách hàng không hợp lệ!']);
                exit;
            }

            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT *, birthday AS dob FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng!']);
                exit;
            }

            // 1. Lịch sử 40 đơn hàng gần nhất
            $stmtOrders = $db->prepare("SELECT id, order_code, order_type, final_amount, discount, points_earned, points_redeemed, payment_method, created_at
                                        FROM orders 
                                        WHERE customer_id = ? 
                                        ORDER BY id DESC LIMIT 40");
            $stmtOrders->execute([$id]);
            $orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

            // 2. Thống kê Insights khách hàng
            $stmtStat = $db->prepare("SELECT 
                COUNT(*) AS total_orders,
                COALESCE(AVG(final_amount), 0) AS avg_order_value,
                COALESCE(SUM(points_redeemed), 0) AS total_redeemed,
                MAX(created_at) AS last_visit
                FROM orders WHERE customer_id = ? AND order_status = 'completed'");
            $stmtStat->execute([$id]);
            $stats = $stmtStat->fetch(PDO::FETCH_ASSOC);

            // Top 5 Món khách hay gọi nhất (Món tủ)
            $stmtFav = $db->prepare("SELECT p.product_name, SUM(od.quantity) AS total_qty, SUM(od.subtotal) AS total_spent
                FROM order_details od
                INNER JOIN orders o ON od.order_id = o.id
                INNER JOIN products p ON od.product_id = p.id
                WHERE o.customer_id = ? AND od.unit_price > 0
                GROUP BY od.product_id
                ORDER BY total_qty DESC LIMIT 5");
            $stmtFav->execute([$id]);
            $topItems = $stmtFav->fetchAll(PDO::FETCH_ASSOC);
            $stats['favorite_item'] = !empty($topItems)
                ? ($topItems[0]['product_name'] . ' (x' . $topItems[0]['total_qty'] . ')')
                : 'Chưa có dữ liệu';

            // Lịch sử quà tặng đã đổi (Ưu tiên lấy tên quà chuẩn từ point_transactions)
            $stmtGifts = $db->prepare("SELECT description AS reward_title, ABS(points_change) AS points_used, created_at
                FROM point_transactions
                WHERE customer_id = ? AND type = 'redeem_gift'
                ORDER BY id DESC LIMIT 20");
            $stmtGifts->execute([$id]);
            $redeemedGifts = $stmtGifts->fetchAll(PDO::FETCH_ASSOC);

            if (empty($redeemedGifts)) {
                $stmtOldGifts = $db->prepare("SELECT 
                        CASE 
                            WHEN od.item_note IS NOT NULL AND od.item_note != '' THEN od.item_note
                            ELSE CONCAT(p.product_name, ' (x', od.quantity, ')') 
                        END AS reward_title, 
                        0 AS points_used, 
                        o.created_at
                    FROM order_details od
                    INNER JOIN orders o ON od.order_id = o.id
                    INNER JOIN products p ON od.product_id = p.id
                    WHERE o.customer_id = ? AND od.unit_price = 0
                    ORDER BY o.id DESC LIMIT 20");
                $stmtOldGifts->execute([$id]);
                $redeemedGifts = $stmtOldGifts->fetchAll(PDO::FETCH_ASSOC);
            }

            // 3. Hợp nhất lịch sử biến động điểm (từ point_transactions + các đơn hàng cũ chưa có trong point_transactions)
            $stmtPts = $db->prepare("SELECT id, order_id, type, points_change, balance_after, description, created_by, created_at 
                                     FROM point_transactions WHERE customer_id = ? ORDER BY id DESC LIMIT 50");
            $stmtPts->execute([$id]);
            $pointLogs = $stmtPts->fetchAll(PDO::FETCH_ASSOC);

            $loggedOrderIds = [];
            foreach ($pointLogs as $pl) {
                if (!empty($pl['order_id'])) $loggedOrderIds[(int)$pl['order_id']] = true;
            }

            foreach ($orders as $ord) {
                $oid = (int)$ord['id'];
                if (isset($loggedOrderIds[$oid])) continue;

                $earned   = (int)($ord['points_earned'] ?? 0);
                $redeemed = (int)($ord['points_redeemed'] ?? 0);
                $codeStr  = $ord['order_code'] ?: ('#' . $oid);

                if ($earned > 0) {
                    $pointLogs[] = [
                        'type'          => 'earn',
                        'points_change' => $earned,
                        'balance_after' => null,
                        'description'   => 'Tích điểm đơn hàng ' . $codeStr,
                        'created_by'    => 'POS',
                        'created_at'    => $ord['created_at']
                    ];
                }
                if ($redeemed > 0) {
                    $pointLogs[] = [
                        'type'          => 'redeem_bill',
                        'points_change' => -$redeemed,
                        'balance_after' => null,
                        'description'   => 'Đổi điểm / Quà tại đơn ' . $codeStr,
                        'created_by'    => 'POS',
                        'created_at'    => $ord['created_at']
                    ];
                }
            }

            usort($pointLogs, function($a, $b) {
                return strtotime($b['created_at']) <=> strtotime($a['created_at']);
            });

            echo json_encode([
                'success'        => true,
                'customer'       => $customer,
                'stats'          => $stats,
                'top_items'      => $topItems,
                'redeemed_gifts' => $redeemedGifts,
                'orders'         => $orders,
                'point_logs'     => $pointLogs
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    // API 3: Tạo mới hoặc Cập nhật thông tin hội viên (Dùng chung cho cả CRM & POS)
    public function saveCustomer() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        $id       = (int)($data['id'] ?? 0);
        $name     = trim($data['customer_name'] ?? '');
        $phone    = preg_replace('/\s+/', '', trim($data['phone'] ?? ''));
        $email    = trim($data['email'] ?? '');
        $birthday = trim($data['birthday'] ?? ($data['dob'] ?? ''));
        $address  = trim($data['address'] ?? '');
        $rank     = trim($data['member_rank'] ?? '');

        if ($name === '' || $phone === '') {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ họ tên và số điện thoại!']);
            exit;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Định dạng Email không hợp lệ (ví dụ: ten@gmail.com)!']);
            exit;
        }   

        try {
            $db = Database::getInstance();

            if ($id > 0) {
                $chk = $db->prepare("SELECT id FROM customers WHERE phone = ? AND id != ?");
                $chk->execute([$phone, $id]);
                if ($chk->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Số điện thoại này đã thuộc về hội viên khác!']);
                    exit;
                }

                $sql = "UPDATE customers SET customer_name = ?, phone = ?, email = ?, birthday = ?, address = ?"
                     . ($rank !== '' ? ", member_rank = ?" : "")
                     . " WHERE id = ?";
                $params = [$name, $phone, $email ?: null, $birthday ?: null, $address ?: null];
                if ($rank !== '') $params[] = $rank;
                $params[] = $id;

                $db->prepare($sql)->execute($params);
                echo json_encode(['success' => true, 'message' => 'Cập nhật hồ sơ hội viên thành công!']);
            } else {
                $chk = $db->prepare("SELECT id FROM customers WHERE phone = ?");
                $chk->execute([$phone]);
                if ($chk->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Số điện thoại này đã tồn tại trên hệ thống!']);
                    exit;
                }

                $stmt = $db->prepare("INSERT INTO customers (customer_name, phone, email, birthday, address, member_rank, total_points, lifetime_spend, created_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, 0, 0, NOW())");
                $stmt->execute([$name, $phone, $email ?: null, $birthday ?: null, $address ?: null, $rank ?: 'Bronze']);
                $newId = $db->lastInsertId();

                echo json_encode([
                    'success'  => true,
                    'message'  => 'Đăng ký hội viên mới thành công!',
                    'customer' => [
                        'id'             => $newId,
                        'customer_name'  => $name,
                        'phone'          => $phone,
                        'birthday'       => $birthday ?: null,
                        'dob'            => $birthday ?: null,
                        'member_rank'    => $rank ?: 'Bronze',
                        'total_points'   => 0,
                        'lifetime_spend' => 0
                    ]
                ]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // API 4: Cộng / Trừ điểm thủ công (Khóa quyền Cashier, cho phép lý do mặc định)
    public function adjustPoints() {
        header('Content-Type: application/json');
        if (!$this->isAdminRole()) {
            echo json_encode(['success' => false, 'message' => 'Tài khoản Thu ngân không có quyền điều chỉnh điểm thủ công!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $customerId = (int)($data['customer_id'] ?? 0);
        $delta      = (int)($data['points_delta'] ?? 0);
        $reason     = trim($data['reason'] ?? '');

        if ($customerId <= 0 || $delta === 0) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập số điểm điều chỉnh khác 0!']);
            exit;
        }
        if ($reason === '') {
            $reason = $delta > 0 ? 'Cộng điểm thủ công bởi Quản trị viên' : 'Trừ điểm thủ công bởi Quản trị viên';
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT total_points FROM customers WHERE id = ? FOR UPDATE");
            $stmt->execute([$customerId]);
            $cust = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cust) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy hội viên!']);
                exit;
            }

            $currentPts = (int)$cust['total_points'];
            $newPts     = max(0, $currentPts + $delta);
            $actualDiff = $newPts - $currentPts;

            $db->prepare("UPDATE customers SET total_points = ? WHERE id = ?")->execute([$newPts, $customerId]);

            $staffName = $_SESSION['user']['full_name'] ?? 'Admin CRM';
            $logStmt = $db->prepare("INSERT INTO point_transactions (customer_id, type, points_change, balance_after, description, created_by, created_at)
                                     VALUES (?, 'adjust', ?, ?, ?, ?, NOW())");
            $logStmt->execute([$customerId, $actualDiff, $newPts, $reason, $staffName]);

            $db->commit();
            echo json_encode([
                'success'    => true,
                'new_points' => $newPts,
                'message'    => ($actualDiff > 0 ? "Đã cộng +{$actualDiff} điểm" : "Đã trừ {$actualDiff} điểm") . " thành công!"
            ]);
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // API 5: Xuất Excel (CSV UTF-8 BOM mở chuẩn tiếng Việt - Chỉ Admin)
    public function exportExcel() {
        if (!$this->isAdminRole()) {
            http_response_code(403);
            echo "Bạn không có quyền xuất dữ liệu khách hàng!";
            exit;
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT c.id, c.customer_name, c.phone, c.member_rank, c.total_points, c.lifetime_spend, c.birthday, c.address,
                            COUNT(o.id) AS total_orders, MAX(o.created_at) AS last_visit
                            FROM customers c
                            LEFT JOIN orders o ON o.customer_id = c.id AND o.order_status = 'completed'
                            GROUP BY c.id
                            ORDER BY c.lifetime_spend DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Danh_Sach_Hoi_Vien_' . date('Ymd_Hi') . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM cho Excel
        fputcsv($out, ['ID', 'Họ Tên Hội Viên', 'Số Điện Thoại', 'Hạng Thẻ', 'Điểm Tiêu Dùng', 'Tổng Chi Tiêu (VNĐ)', 'Số Đơn Hàng', 'Ngày Sinh', 'Địa Chỉ', 'Ghé Quán Gần Nhất']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['customer_name'],
                $r['phone'],
                $r['member_rank'],
                $r['total_points'],
                $r['lifetime_spend'],
                $r['total_orders'],
                $r['birthday'] ?: '',
                $r['address'] ?: '',
                $r['last_visit'] ?: ''
            ]);
        }
        fclose($out);
        exit;
    }

    // API 6: Lấy danh sách Kho Quà Đổi Điểm
    public function getRewards() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT * FROM rewards ORDER BY FIELD(min_rank, 'Bronze','Silver','Gold','Diamond','VIP'), points_required ASC");
            echo json_encode(['success' => true, 'rewards' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // API 7: Thêm / Sửa phần quà đổi điểm
    public function saveReward() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        $id       = (int)($data['id'] ?? 0);
        $name     = trim($data['reward_name'] ?? '');
        $points   = (int)($data['points_required'] ?? 0);
        $minRank  = trim($data['min_rank'] ?? 'Bronze');
        $stock    = (int)($data['stock'] ?? 999);
        $desc     = trim($data['description'] ?? '');
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if ($name === '' || $points <= 0) {
            echo json_encode(['success' => false, 'message' => 'Tên phần quà và số điểm đổi phải hợp lệ!']);
            exit;
        }

        try {
            $db = Database::getInstance();
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE rewards SET reward_name = ?, points_required = ?, min_rank = ?, stock = ?, description = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $points, $minRank, $stock, $desc, $isActive, $id]);
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật cấu hình quà đổi điểm!']);
            } else {
                $stmt = $db->prepare("INSERT INTO rewards (reward_name, points_required, min_rank, stock, description, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $points, $minRank, $stock, $desc, $isActive]);
                echo json_encode(['success' => true, 'message' => 'Đã thêm quà mới vào kho POS!']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // API 8: Xóa phần quà đổi điểm
    public function deleteReward() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID quà không hợp lệ!']);
            exit;
        }

        try {
            $db = Database::getInstance();
            $db->prepare("DELETE FROM rewards WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Đã xóa quà khỏi danh mục!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // API 9: Lấy cấu hình Chính sách hạng thẻ
    public function getLoyaltySettings() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT * FROM loyalty_settings WHERE id = 1");
            echo json_encode(['success' => true, 'settings' => $stmt->fetch(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // API 10: Lưu cấu hình Chính sách hạng thẻ (Chỉ Admin)
    public function saveLoyaltySettings() {
        header('Content-Type: application/json');
        if (!$this->isAdminRole()) {
            echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên mới được chỉnh sửa chính sách hạng thẻ!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE loyalty_settings SET 
                vnd_per_point_earn = ?, vnd_per_point_redeem = ?,
                silver_min_spend = ?, gold_min_spend = ?, diamond_min_spend = ?, vip_min_spend = ?,
                bronze_discount = ?, silver_discount = ?, gold_discount = ?, diamond_discount = ?, vip_discount = ?,
                bronze_multiplier = ?, silver_multiplier = ?, gold_multiplier = ?, diamond_multiplier = ?, vip_multiplier = ?
                WHERE id = 1");
            $stmt->execute([
                max(1000, (int)($data['vnd_per_point_earn'] ?? 10000)),
                max(10, (int)($data['vnd_per_point_redeem'] ?? 100)),
                max(0, (int)($data['silver_min_spend'] ?? 1000000)),
                max(0, (int)($data['gold_min_spend'] ?? 3000000)),
                max(0, (int)($data['diamond_min_spend'] ?? 6000000)),
                max(0, (int)($data['vip_min_spend'] ?? 10000000)),
                max(0, (float)($data['bronze_discount'] ?? 0)),
                max(0, (float)($data['silver_discount'] ?? 0)),
                max(0, (float)($data['gold_discount'] ?? 5)),
                max(0, (float)($data['diamond_discount'] ?? 10)),
                max(0, (float)($data['vip_discount'] ?? 15)),
                max(1, (float)($data['bronze_multiplier'] ?? 1.0)),
                max(1, (float)($data['silver_multiplier'] ?? 1.2)),
                max(1, (float)($data['gold_multiplier'] ?? 1.5)),
                max(1, (float)($data['diamond_multiplier'] ?? 2.0)),
                max(1, (float)($data['vip_multiplier'] ?? 2.5))
            ]);
            echo json_encode(['success' => true, 'message' => 'Đã cập nhật chính sách hạng thẻ & quy đổi điểm!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    // API 11: Gửi Email chúc mừng sinh nhật chuẩn SaaS hiện đại, Responsive 100% & Tương thích hoàn hảo Light/Dark Mode
    public function sendBirthdayEmail() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['customer_id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID hội viên không hợp lệ!']);
            exit;
        }

        // === CẤU HÌNH GMAIL SMTP CỦA QUÁN ===
        $smtpUser = 'hpqvinh1204@gmail.com';   
        $smtpPass = 'bckd btdy hpoz pyce';           
        $shopName = 'SMART F&B COFFEE & TEA';
        // ====================================

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT customer_name, email, phone, member_rank, total_points FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $cust = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cust || empty($cust['email']) || !filter_var($cust['email'], FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Hội viên chưa có địa chỉ Email hợp lệ!']);
                exit;
            }

            $toEmail = $cust['email'];
            $toName  = htmlspecialchars($cust['customer_name'], ENT_QUOTES, 'UTF-8');
            $phone   = htmlspecialchars($cust['phone'] ?? '', ENT_QUOTES, 'UTF-8');
            $rank    = htmlspecialchars($cust['member_rank'] ?: 'Bronze', ENT_QUOTES, 'UTF-8');
            $pts     = number_format((int)$cust['total_points']);
            $month   = date('m/Y');

            $subject = "🎂 Chúc Mừng Sinh Nhật {$cust['customer_name']} - Đặc Quyền Hội Viên {$rank}";
            $htmlBody = "
            <!DOCTYPE html>
            <html lang='vi'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <meta name='color-scheme' content='light dark'>
                <meta name='supported-color-schemes' content='light dark'>
            </head>
            <body style='margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing:antialiased;'>
                <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='background-color:#f1f5f9; padding:20px 10px;'>
                    <tr>
                        <td align='center'>
                            <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='max-width:580px; background-color:#ffffff; border-radius:20px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 10px 25px rgba(15,23,42,0.06);'>
                                
                                <!-- Top Brand Bar (Chống rớt dòng trên mobile) -->
                                <tr>
                                    <td style='background-color:#0f172a; padding:16px 20px; border-bottom:1px solid #1e293b;'>
                                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0'>
                                            <tr>
                                                <td align='left' style='vertical-align:middle; white-space:nowrap;'>
                                                    <span style='display:inline-block; background-color:#2563eb; color:#ffffff; font-weight:800; font-size:11px; padding:5px 9px; border-radius:7px; letter-spacing:0.4px; white-space:nowrap;'>SMART F&amp;B</span>
                                                    <span style='color:#94a3b8; font-size:11px; font-weight:600; margin-left:6px; white-space:nowrap;'>LOYALTY</span>
                                                </td>
                                                <td align='right' style='vertical-align:middle; white-space:nowrap;'>
                                                    <span style='display:inline-block; background-color:#1e293b; color:#fbbf24; border:1px solid #334155; font-size:11px; font-weight:700; padding:4px 11px; border-radius:999px; letter-spacing:0.4px; white-space:nowrap;'>HẠNG {$rank}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Hero Greeting -->
                                <tr>
                                    <td style='padding:28px 22px 12px 22px; background-color:#ffffff;'>
                                        <div style='display:inline-block; background-color:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:700; padding:5px 12px; border-radius:999px; letter-spacing:0.5px; margin-bottom:12px; white-space:nowrap;'>
                                            HAPPY BIRTHDAY • THÁNG {$month}
                                        </div>
                                        <h1 style='margin:0 0 10px 0; font-size:24px; line-height:1.3; font-weight:800; color:#0f172a; letter-spacing:-0.4px;'>
                                            Chúc mừng sinh nhật, {$toName}! 🎂
                                        </h1>
                                        <p style='margin:0; font-size:14.5px; line-height:1.65; color:#475569;'>
                                            Nhân dịp bước sang tuổi mới, <strong style='color:#0f172a;'>{$shopName}</strong> kính chúc bạn thật nhiều sức khỏe, niềm vui và những khoảnh khắc trọn vẹn bên người thân yêu.
                                        </p>
                                    </td>
                                </tr>

                                <!-- SaaS Member Stats Strip -->
                                <tr>
                                    <td style='padding:14px 22px; background-color:#ffffff;'>
                                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:14px;'>
                                            <tr>
                                                <td width='50%' style='padding:13px 14px; border-right:1px solid #e2e8f0; vertical-align:top;'>
                                                    <div style='font-size:10.5px; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.4px;'>Hạng Thẻ</div>
                                                    <div style='font-size:15px; font-weight:800; color:#0f172a; margin-top:4px; white-space:nowrap;'>{$rank} Member</div>
                                                </td>
                                                <td width='50%' style='padding:13px 14px; vertical-align:top;'>
                                                    <div style='font-size:10.5px; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.4px;'>Điểm Tích Lũy</div>
                                                    <div style='font-size:15px; font-weight:800; color:#2563eb; margin-top:4px; white-space:nowrap;'>{$pts} điểm</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Birthday Privilege Card -->
                                <tr>
                                    <td style='padding:6px 22px 22px 22px; background-color:#ffffff;'>
                                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='background-color:#fffbeb; border:1.5px solid #f59e0b; border-radius:16px;'>
                                            <tr>
                                                <td style='padding:20px 16px; text-align:center;'>
                                                    <div style='font-size:10.5px; font-weight:800; color:#b45309; letter-spacing:1px; text-transform:uppercase;'>
                                                        🎁 ĐẶC QUYỀN SINH NHẬT DÀNH RIÊNG CHO BẠN
                                                    </div>
                                                    <div style='font-size:17.5px; font-weight:800; color:#0f172a; margin:10px 0 8px 0; line-height:1.4;'>
                                                        Tặng 01 Thức Uống Bất Kỳ<br>
                                                        <span style='color:#d97706;'>+ Nhân đôi điểm tích lũy</span>
                                                    </div>
                                                    <div style='display:inline-block; background-color:#ffffff; border:1px dashed #d97706; border-radius:10px; padding:7px 14px; margin:6px 0 10px 0; white-space:nowrap;'>
                                                        <span style='font-size:11.5px; color:#64748b;'>Mã định danh: </span>
                                                        <strong style='font-size:13.5px; color:#0f172a; letter-spacing:0.5px;'>{$phone}</strong>
                                                    </div>
                                                    <p style='margin:0; font-size:12px; line-height:1.5; color:#78350f;'>
                                                        Đọc số điện thoại cho thu ngân khi thanh toán để nhận quà trong suốt tháng sinh nhật.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Footer -->
                                <tr>
                                    <td style='padding:18px 22px 24px 22px; background-color:#f8fafc; border-top:1px solid #e2e8f0;'>
                                        <p style='margin:0 0 6px 0; font-size:13px; color:#334155;'>
                                            Hẹn sớm được đón tiếp bạn tại quán,<br>
                                            <strong style='color:#0f172a;'>Đội ngũ {$shopName}</strong>
                                        </p>
                                        <p style='margin:10px 0 0 0; font-size:11px; color:#94a3b8;'>
                                            Email tự động từ hệ thống chăm sóc hội viên Smart F&amp;B POS.
                                        </p>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

            $sent = $this->sendSmtpMail($smtpUser, str_replace(' ', '', $smtpPass), $shopName, $toEmail, $subject, $htmlBody);
            if ($sent === true) {
                echo json_encode(['success' => true, 'message' => "Đã gửi thư chúc mừng sinh nhật tới {$toEmail}!"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi gửi SMTP: ' . $sent]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    private function sendSmtpMail($user, $pass,$fromName, $to,$subject, $html) {$socket = @fsockopen('ssl://smtp.gmail.com', 465, $errno,$errstr, 15);
        if (!$socket) return "Không kết nối được Gmail SMTP ($errstr)";

        $read = function() use ($socket) {$data = '';
            while ($str = fgets($socket, 515)) {
                $data .=$str;
                if (substr($str, 3, 1) === ' ') break;
            }
            return $data;
        };
        $cmd = function($c) use ($socket,$read) {
            fwrite($socket,$c . "\r\n");
            return $read();
        };

        $read();$cmd("EHLO localhost");
        $cmd("AUTH LOGIN");
        $cmd(base64_encode($user));$authRes = $cmd(base64_encode($pass));
        if (strpos($authRes, '235') === false) {
            fclose($socket);
            return "Sai Gmail hoặc Mật khẩu ứng dụng (App Password)!";
        }

        $cmd("MAIL FROM:<{$user}>");
        $cmd("RCPT TO:<{$to}>");
        $cmd("DATA");

        $encodedSubj = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFrom = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $msgId       = '<' . uniqid('smartfb_', true) . '@gmail.com>';
        $dateHeader  = date('r');

        $headers  = "Date: {$dateHeader}\r\n";
        $headers .= "Message-ID: {$msgId}\r\n";
        $headers .= "From: {$encodedFrom} <{$user}>\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= "Subject: {$encodedSubj}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";

        $res =$cmd($headers .$html . "\r\n.");
        $cmd("QUIT");
        fclose($socket);

        return (strpos($res, '250') !== false) ? true : "Máy chủ từ chối gửi: $res";
    }
}
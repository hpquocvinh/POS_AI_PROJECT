<?php
// app/Controllers/OrderController.php

class OrderController extends Controller {
    private $orderModel;

    public function __construct() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->orderModel = $this->model('OrderModel');
        $this->ensureDatabaseSchema();
    }

    /**
     * Tự động kiểm tra và nâng cấp cấu trúc cột an toàn (không làm mất dữ liệu cũ)
     */
    private function ensureDatabaseSchema() {
        try {
            $db = Database::getInstance();
            $db->exec("ALTER TABLE orders MODIFY COLUMN total_amount DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE orders MODIFY COLUMN discount DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE orders MODIFY COLUMN final_amount DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE orders MODIFY COLUMN customer_pay DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE orders MODIFY COLUMN change_amount DECIMAL(15,2) DEFAULT 0");

            $checkVatCol = $db->query("SHOW COLUMNS FROM orders LIKE 'vat_percent'");
            if (!$checkVatCol || $checkVatCol->rowCount() == 0) {
                $db->exec("ALTER TABLE orders ADD COLUMN vat_percent DECIMAL(5,2) DEFAULT 8.00");
            }

            $db->exec("ALTER TABLE shifts MODIFY COLUMN initial_cash DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN total_revenue DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN offline_revenue DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN online_revenue DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN total_cash_sales DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN total_bank_sales DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN total_app_sales DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN ending_cash_actual DECIMAL(15,2) DEFAULT 0");

            // Cột mới cho phiếu kết ca (tiền giữ két & ghi chú chênh lệch)
            $checkVarNote = $db->query("SHOW COLUMNS FROM shifts LIKE 'variance_note'");
            if (!$checkVarNote || $checkVarNote->rowCount() == 0) {
                $db->exec("ALTER TABLE shifts ADD COLUMN variance_note VARCHAR(255) NULL");
            }
            $checkRetained = $db->query("SHOW COLUMNS FROM shifts LIKE 'retained_cash'");
            if (!$checkRetained || $checkRetained->rowCount() == 0) {
                $db->exec("ALTER TABLE shifts ADD COLUMN retained_cash DECIMAL(15,2) DEFAULT 0");
            }

            // Nâng cấp bảng customers
            $db->exec("ALTER TABLE customers MODIFY COLUMN total_points INT DEFAULT 0");
            $db->exec("ALTER TABLE customers MODIFY COLUMN lifetime_spend DECIMAL(15,2) DEFAULT 0");

            $checkRankUpdateCol = $db->query("SHOW COLUMNS FROM customers LIKE 'rank_updated_at'");
            if (!$checkRankUpdateCol || $checkRankUpdateCol->rowCount() == 0) {
                $db->exec("ALTER TABLE customers ADD COLUMN rank_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP");
            }

            $checkMaintPointsCol = $db->query("SHOW COLUMNS FROM customers LIKE 'maintained_points'");
            if (!$checkMaintPointsCol || $checkMaintPointsCol->rowCount() == 0) {
                $db->exec("ALTER TABLE customers ADD COLUMN maintained_points INT DEFAULT 0");
            }

            // Đồng bộ dữ liệu cũ: Nếu khách đã có điểm mà lifetime_spend = 0 thì quy đổi mốc chi tiêu tương ứng để không bị tụt hạng khi tiêu điểm
            $db->exec("UPDATE customers SET lifetime_spend = total_points * 10000 WHERE lifetime_spend = 0 AND total_points > 0");

            // Bảng system_settings
            $db->exec("CREATE TABLE IF NOT EXISTS system_settings (
                setting_key VARCHAR(50) PRIMARY KEY,
                setting_value VARCHAR(255) NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $checkSetting = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'vat_percent'");
            if (!$checkSetting || $checkSetting->rowCount() == 0) {
                $db->exec("INSERT INTO system_settings (setting_key, setting_value) VALUES ('vat_percent', '8.00')");
            }

            // Bảng reservations: thêm cột cancel_reason & guest_count
            $checkCancelCol = $db->query("SHOW COLUMNS FROM reservations LIKE 'cancel_reason'");
            if (!$checkCancelCol || $checkCancelCol->rowCount() == 0) {
                $db->exec("ALTER TABLE reservations ADD COLUMN cancel_reason VARCHAR(255) NULL");
            }
            $checkGuestCol = $db->query("SHOW COLUMNS FROM reservations LIKE 'guest_count'");
            if (!$checkGuestCol || $checkGuestCol->rowCount() == 0) {
                $db->exec("ALTER TABLE reservations ADD COLUMN guest_count INT DEFAULT 2 AFTER customer_phone");
            }
        } catch (Exception $e) {
            // Bỏ qua nếu đã nâng cấp
        }
    }

    public function index() {
        $db = Database::getInstance();
        $stmtShift = $db->prepare("SELECT * FROM shifts WHERE status = 'open' ORDER BY id DESC LIMIT 1");
        $stmtShift->execute();
        $activeShift = $stmtShift->fetch(PDO::FETCH_ASSOC);

        $data = [
            'areas'       => $this->orderModel->getAreasWithTables(),
            'categories'  => $this->orderModel->getCategories(),
            'products'    => $this->orderModel->getProducts(),
            'activeShift' => $activeShift
        ];
        $this->view('pos/index', $data);
    }

    // TRA CỨU KHÁCH HÀNG (XÉT HẠNG DỰA TRÊN TỔNG CHI TIÊU TÍCH LŨY - KHÔNG BỊ TỤT HẠNG KHI ĐỔI ĐIỂM)
    public function searchCustomer() {
        header('Content-Type: application/json');
        try {
            $phone = trim($_GET['phone'] ?? '');

            if (empty($phone)) {
                echo json_encode(['success' => false, 'message' => 'SĐT không được để trống!']);
                exit;
            }

            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM customers WHERE phone = ? LIMIT 1");
            $stmt->execute([$phone]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer) {
                $customer['total_points'] = (int)($customer['total_points'] ?? 0);
                $customer['lifetime_spend'] = (float)($customer['lifetime_spend'] ?? 0);
                $customer['maintained_points'] = (int)($customer['maintained_points'] ?? 0);

                // Tính hạng chuẩn dựa trên tổng chi tiêu tích lũy (10.000đ = 1 điểm xét hạng)
                $rankPoints = (int)floor($customer['lifetime_spend'] / 10000);
                $computedRank = 'Bronze';
                if ($rankPoints >= 1000) $computedRank = 'VIP';
                else if ($rankPoints >= 600) $computedRank = 'Diamond';
                else if ($rankPoints >= 300) $computedRank = 'Gold';
                else if ($rankPoints >= 100) $computedRank = 'Silver';

                // Ưu tiên hạng cao hơn giữa hạng hiện tại và hạng theo tổng chi tiêu
                $rankOrder = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3, 'Diamond' => 4, 'VIP' => 5];
                $dbRank = $customer['member_rank'] ?? 'Bronze';
                if (($rankOrder[$computedRank] ?? 1) > ($rankOrder[$dbRank] ?? 1)) {
                    $dbRank = $computedRank;
                    $upd = $db->prepare("UPDATE customers SET member_rank = ? WHERE id = ?");
                    $upd->execute([$dbRank, $customer['id']]);
                }

                $customer['member_rank'] = $dbRank;
                $customer['rank_points'] = $rankPoints;

                $rankDiscounts = [
                    'Bronze'  => 0,
                    'Silver'  => 0,
                    'Gold'    => 5,
                    'Diamond' => 10,
                    'VIP'     => 15
                ];
                $customer['discount_percent'] = $rankDiscounts[$customer['member_rank']] ?? 0;

                echo json_encode(['success' => true, 'customer' => $customer]);
            } else {
                echo json_encode(['success' => false, 'message' => 'SĐT chưa được đăng ký thành viên!']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()]);
        }
        exit;
    }

    public function registerCustomer() {
        header('Content-Type: application/json');
        try {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            $name = trim($data['name'] ?? '');
            $phone = trim($data['phone'] ?? '');

            if (empty($name) || empty($phone)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền tên và SĐT!']);
                exit;
            }

            $db = Database::getInstance();
            $check = $db->prepare("SELECT id FROM customers WHERE phone = ?");
            $check->execute([$phone]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Số điện thoại này đã tồn tại!']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO customers (customer_name, phone, total_points, lifetime_spend, member_rank, rank_updated_at, maintained_points) VALUES (?, ?, 0, 0, 'Bronze', NOW(), 0)");
            if ($stmt->execute([$name, $phone])) {
                $newId = $db->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'customer' => [
                        'id' => $newId,
                        'customer_name' => $name,
                        'phone' => $phone,
                        'total_points' => 0,
                        'lifetime_spend' => 0,
                        'rank_points' => 0,
                        'member_rank' => 'Bronze',
                        'discount_percent' => 0
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể tạo thành viên mới. Vui lòng thử lại!']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
        exit;
    }

    // LẤY DANH SÁCH QUÀ TẶNG TỪ BẢNG REWARDS
    public function getRewards() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT * FROM rewards WHERE status = 1 ORDER BY points_required ASC");
            $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'rewards' => $rewards]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ĐỔI QUÀ TẶNG BẰNG ĐIỂM TIÊU DÙNG (KHÔNG ẢNH HƯỞNG TỔNG CHI TIÊU / HẠNG THẺ)
    public function redeemGift() {
        header('Content-Type: application/json');
        try {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            $customerId = (int)($data['customer_id'] ?? 0);
            $rewardId   = (int)($data['reward_id'] ?? 0);

            if ($customerId <= 0 || $rewardId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu đổi quà không hợp lệ!']);
                exit;
            }

            $db = Database::getInstance();
            $stmtC = $db->prepare("SELECT * FROM customers WHERE id = ?");
            $stmtC->execute([$customerId]);
            $customer = $stmtC->fetch(PDO::FETCH_ASSOC);

            $stmtR = $db->prepare("SELECT * FROM rewards WHERE id = ? AND status = 1");
            $stmtR->execute([$rewardId]);
            $reward = $stmtR->fetch(PDO::FETCH_ASSOC);

            if (!$customer || !$reward) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng hoặc phần quà!']);
                exit;
            }

            $currentPts = (int)$customer['total_points'];
            $reqPts     = (int)$reward['points_required'];

            if ($currentPts < $reqPts) {
                echo json_encode(['success' => false, 'message' => "Khách chỉ có {$currentPts} điểm, cần {$reqPts} điểm để đổi quà này!"]);
                exit;
            }

            $newPts = $currentPts - $reqPts;
            $upd = $db->prepare("UPDATE customers SET total_points = ? WHERE id = ?");
            $upd->execute([$newPts, $customerId]);

            echo json_encode([
                'success' => true,
                'new_points' => $newPts,
                'reward' => $reward,
                'message' => "Đã đổi thành công \"{$reward['reward_name']}\" (-{$reqPts} điểm)!"
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
        exit;
    }

    public function checkout() {
        header('Content-Type: application/json');
        
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data || empty($data['items'])) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu giỏ hàng trống hoặc không hợp lệ!']);
            exit;
        }

        $data['user_id'] = $_SESSION['user']['id'] ?? 1;
        $finalAmount = (float)($data['final_amount'] ?? 0);

        $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
        $multiplier = 1.0;

        if ($customerId) {
            $db = Database::getInstance();
            $stmtC = $db->prepare("SELECT member_rank FROM customers WHERE id = ?");
            $stmtC->execute([$customerId]);
            $cRow = $stmtC->fetch(PDO::FETCH_ASSOC);
            $cRank = $cRow['member_rank'] ?? 'Bronze';

            if ($cRank === 'VIP') $multiplier = 2.5;
            else if ($cRank === 'Diamond') $multiplier = 2.0;
            else if ($cRank === 'Gold') $multiplier = 1.5;
            else if ($cRank === 'Silver') $multiplier = 1.2;
        }
        
        $basePoints = floor($finalAmount / 10000);
        $pointsEarned = floor($basePoints * $multiplier);
        $data['points_earned'] = $pointsEarned;

        try {
            $db = Database::getInstance();
            $result = $this->orderModel->createOrder($data);
            
            if (isset($result['success']) && !$result['success']) {
                $msg = $result['message'] ?? '';
                if (strpos($msg, '1062') !== false || strpos($msg, 'Duplicate entry') !== false) {
                    $orderCode = htmlspecialchars($data['order_code'] ?? '');
                    echo json_encode([
                        'success' => false, 
                        'message' => "Mã đơn sàn \"{$orderCode}\" đã tồn tại trên hệ thống! Vui lòng nhập mã đơn khác."
                    ]);
                    exit;
                }
            }

            // Khi thanh toán thành công: hoàn tất lịch đặt đã Check-in hoặc đang trong khung giờ phục vụ
            if (isset($result['success']) && $result['success']) {
                $tableId = !empty($data['table_id']) ? (int)$data['table_id'] : 0;
                $reservationId = !empty($data['reservation_id']) ? (int)$data['reservation_id'] : 0;
                if ($reservationId > 0) {
                    $stmtRes = $db->prepare("UPDATE reservations SET status = 'completed' WHERE id = ?");
                    $stmtRes->execute([$reservationId]);
                } else if ($tableId > 0) {
                    // Chỉ đóng lịch đặt nếu lịch đó đã tới giờ (trong vòng 60p tới hoặc đã qua)
                    $stmtRes = $db->prepare("UPDATE reservations SET status = 'completed' 
                                             WHERE table_id = ? AND status IN ('confirmed', 'pending') 
                                               AND TIMESTAMPDIFF(MINUTE, NOW(), reservation_time) <= 60");
                    $stmtRes->execute([$tableId]);
                }
            }

            echo json_encode($result);
            exit;

        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, '1062') !== false || strpos($errorMsg, 'Duplicate entry') !== false) {
                $orderCode = htmlspecialchars($data['order_code'] ?? '');
                echo json_encode([
                    'success' => false, 
                    'message' => "Mã đơn sàn \"{$orderCode}\" đã tồn tại trên hệ thống! Vui lòng nhập mã đơn khác."
                ]);
                exit;
            }
            echo json_encode(['success' => false, 'message' => 'Lỗi SQL CSDL: ' . $errorMsg]);
            exit;
        }
    }

    public function openShift() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        header('Content-Type: application/json');
        
        try {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            $cashierId   = trim($data['cashier_id'] ?? '');
            $shiftType   = trim($data['shift_type'] ?? '');
            $shiftName   = trim($data['shift_name'] ?? '');
            $initialCash = (float)($data['initial_cash'] ?? 0);

            if (empty($cashierId) || empty($shiftType)) {
                echo json_encode(['success' => false, 'message' => 'Thông tin mở ca không hợp lệ!']);
                exit;
            }

            $currentHour = (int)date('H');
            if ($shiftType === 'morning' && ($currentHour < 6 || $currentHour >= 14)) {
                echo json_encode(['success' => false, 'message' => 'Hiện tại không thuộc khung giờ Ca Sáng (06:00 - 14:00)!']);
                exit;
            }
            if ($shiftType === 'afternoon' && ($currentHour < 14 || $currentHour >= 22)) {
                echo json_encode(['success' => false, 'message' => 'Hiện tại không thuộc khung giờ Ca Chiều (14:00 - 22:00)!']);
                exit;
            }
            if ($shiftType === 'night' && ($currentHour >= 6 && $currentHour < 22)) {
                echo json_encode(['success' => false, 'message' => 'Hiện tại không thuộc khung giờ Ca Đêm (22:00 - 06:00)!']);
                exit;
            }

            $db = Database::getInstance();
            $db->exec("UPDATE shifts SET status = 'closed', end_time = NOW() WHERE status = 'open'");

            $sql = "INSERT INTO shifts (cashier_id, shift_type, shift_name, initial_cash, start_time, status) VALUES (?, ?, ?, ?, NOW(), 'open')";
            $stmt = $db->prepare($sql);
            
            if ($stmt->execute([$cashierId, $shiftType, $shiftName, $initialCash])) {
                $shiftId = $db->lastInsertId();
                echo json_encode([
                    'success' => true, 
                    'shift_id' => $shiftId,
                    'message' => 'Mở ca làm việc thành công!'
                ]);
            } else {
                $errorInfo = $stmt->errorInfo();
                echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . ($errorInfo[2] ?? 'Không thể lưu bản ghi!')]);
            }
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
        exit;
    }

    public function getShiftStatus() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance();

            $vatPercent = 8.0;
            $stmtVat = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'vat_percent' LIMIT 1");
            if ($stmtVat && $rowVat = $stmtVat->fetch(PDO::FETCH_ASSOC)) {
                $vatPercent = (float)$rowVat['setting_value'];
            }

            $stmt = $db->query("SELECT * FROM shifts WHERE status = 'open' ORDER BY id DESC LIMIT 1");
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($shift) {
                $shiftId = $shift['id'];
                
                $statStmt = $db->prepare("SELECT 
                    COALESCE(SUM(final_amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN order_type IN ('dine_in','takeaway') THEN final_amount ELSE 0 END), 0) as offline_revenue,
                    COALESCE(SUM(CASE WHEN order_type IN ('grab','shopee') THEN final_amount ELSE 0 END), 0) as online_revenue,
                    COALESCE(SUM(CASE WHEN (LOWER(payment_method) = 'cash' OR LOWER(payment_method) = 'tien_mat') AND order_type NOT IN ('grab','shopee') THEN final_amount ELSE 0 END), 0) as total_cash_sales,
                    COALESCE(SUM(CASE WHEN (LOWER(payment_method) IN ('banking','bank','card')) AND order_type NOT IN ('grab','shopee') THEN final_amount ELSE 0 END), 0) as total_bank_sales,
                    COALESCE(SUM(CASE WHEN order_type IN ('grab','shopee') OR LOWER(payment_method) = 'app' THEN final_amount ELSE 0 END), 0) as total_app_sales,
                    COUNT(id) as total_orders
                    FROM orders WHERE shift_id = ?");
                $statStmt->execute([$shiftId]);
                $stats = $statStmt->fetch(PDO::FETCH_ASSOC);

                $cupCakeStmt = $db->prepare("SELECT 
                    COALESCE(SUM(CASE WHEN p.category_id != 5 AND p.category_id != 4 THEN od.quantity ELSE 0 END), 0) as total_cups,
                    COALESCE(SUM(CASE WHEN p.category_id = 5 THEN od.quantity ELSE 0 END), 0) as total_cakes
                    FROM order_details od
                    JOIN orders o ON od.order_id = o.id
                    LEFT JOIN products p ON od.product_id = p.id
                    WHERE o.shift_id = ?");
                $cupCakeStmt->execute([$shiftId]);
                $cupCakeStats = $cupCakeStmt->fetch(PDO::FETCH_ASSOC);

                $stats['total_cups'] = (int)($cupCakeStats['total_cups'] ?? 0);
                $stats['total_cakes'] = (int)($cupCakeStats['total_cakes'] ?? 0);

                echo json_encode([
                    'success' => true,
                    'has_open_shift' => true,
                    'shift' => $shift,
                    'stats' => $stats,
                    'vat_percent' => $vatPercent
                ]);
            } else {
                echo json_encode(['success' => true, 'has_open_shift' => false, 'vat_percent' => $vatPercent]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function updateVatSetting() {
        header('Content-Type: application/json');
        
        $role = $_SESSION['user']['role'] ?? 'cashier';
        if ($role !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Chỉ tài khoản Admin mới có quyền điều chỉnh VAT!']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        $vat = (float)($data['vat_percent'] ?? 8.0);

        if ($vat < 0 || $vat > 100) {
            echo json_encode(['success' => false, 'message' => 'Tỷ lệ % VAT phải nằm trong khoảng từ 0% đến 100%!']);
            exit;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('vat_percent', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$vat, $vat]);

            echo json_encode(['success' => true, 'message' => "Cập nhật Thuế VAT hệ thống thành {$vat}% thành công!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
        }
        exit;
    }

    public function closeShift() {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        $shiftId          = (int)($data['shift_id'] ?? 0);
        $totalRevenue     = (float)($data['total_revenue'] ?? 0);
        $offlineRevenue   = (float)($data['offline_revenue'] ?? 0);
        $onlineRevenue    = (float)($data['online_revenue'] ?? 0);
        $totalCashSales   = (float)($data['total_cash_sales'] ?? 0);
        $totalBankSales   = (float)($data['total_bank_sales'] ?? 0);
        $totalAppSales    = (float)($data['total_app_sales'] ?? 0);
        $endingCashActual = (float)($data['ending_cash_actual'] ?? 0);
        $retainedCash     = (float)($data['retained_cash'] ?? 0);
        $varianceNote     = trim($data['variance_note'] ?? '');
        $totalOrders      = (int)($data['total_orders'] ?? 0);
        $totalCups        = (int)($data['total_cups'] ?? 0);
        $totalCakes       = (int)($data['total_cakes'] ?? 0);

        $db = Database::getInstance();
        
        if ($shiftId > 0) {
            $sql = "UPDATE shifts SET 
                        end_time = NOW(), 
                        total_revenue = ?, 
                        offline_revenue = ?, 
                        online_revenue = ?, 
                        total_cash_sales = ?,
                        total_bank_sales = ?,
                        total_app_sales = ?,
                        ending_cash_actual = ?,
                        retained_cash = ?,
                        variance_note = ?,
                        total_orders = ?, 
                        total_cups = ?, 
                        total_cakes = ?, 
                        status = 'closed' 
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $totalRevenue, $offlineRevenue, $onlineRevenue, $totalCashSales, $totalBankSales, $totalAppSales, $endingCashActual, 
                $retainedCash, $varianceNote, $totalOrders, $totalCups, $totalCakes, $shiftId
            ]);
        } else {
            $sql = "UPDATE shifts SET 
                        end_time = NOW(), 
                        total_revenue = ?, 
                        offline_revenue = ?, 
                        online_revenue = ?, 
                        total_cash_sales = ?,
                        total_bank_sales = ?,
                        total_app_sales = ?,
                        ending_cash_actual = ?,
                        retained_cash = ?,
                        variance_note = ?,
                        total_orders = ?, 
                        total_cups = ?, 
                        total_cakes = ?, 
                        status = 'closed' 
                    WHERE status = 'open' ORDER BY id DESC LIMIT 1";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $totalRevenue, $offlineRevenue, $onlineRevenue, $totalCashSales, $totalBankSales, $totalAppSales, $endingCashActual, 
                $retainedCash, $varianceNote, $totalOrders, $totalCups, $totalCakes
            ]);
        }

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Kết ca và lưu báo cáo doanh thu thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể kết ca. Vui lòng thử lại!']);
        }
        exit;
    }

    public function getOrderHistory() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance();
            $shiftId = isset($_GET['shift_id']) ? (int)$_GET['shift_id'] : 0;

            if ($shiftId > 0) {
                $stmt = $db->prepare("SELECT * FROM orders WHERE shift_id = ? ORDER BY id DESC");
                $stmt->execute([$shiftId]);
            } else {
                $shiftStmt = $db->query("SELECT id FROM shifts WHERE status = 'open' ORDER BY id DESC LIMIT 1");
                $openShift = $shiftStmt->fetch(PDO::FETCH_ASSOC);

                if ($openShift) {
                    $stmt = $db->prepare("SELECT * FROM orders WHERE shift_id = ? ORDER BY id DESC");
                    $stmt->execute([$openShift['id']]);
                } else {
                    $stmt = $db->query("SELECT * FROM orders WHERE DATE(created_at) = CURDATE() ORDER BY id DESC");
                }
            }

            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($orders as &$order) {
                $itemStmt = $db->prepare("SELECT od.*, p.product_name, p.category_id 
                                          FROM order_details od 
                                          LEFT JOIN products p ON od.product_id = p.id 
                                          WHERE od.order_id = ?");
                $itemStmt->execute([$order['id']]);
                $details = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $order['items'] = array_map(function($d) {
                    $options = !empty($d['options_json']) ? json_decode($d['options_json'], true) : [];
                    return [
                        'name'     => $d['product_name'] ?? $d['name'] ?? $d['item_name'] ?? 'Sản phẩm',
                        'qty'      => (int)($d['quantity'] ?? $d['qty'] ?? 1),
                        'price'    => (float)($d['unit_price'] ?? $d['price'] ?? 0),
                        'size'     => $options['size'] ?? '',
                        'sugar'    => $options['sugar'] ?? '',
                        'ice'      => $options['ice'] ?? '',
                        'toppings' => $options['toppings'] ?? [],
                        'note'     => $d['item_note'] ?? '',
                        'isCake'   => (isset($d['category_id']) && $d['category_id'] == 5)
                    ];
                }, $details);
            }

            echo json_encode(['success' => true, 'orders' => $orders]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== HỆ THỐNG ĐẶT BÀN NÂNG CẤP (ĐA LỊCH, KHUNG GIỜ 2H - 24H, CHECK-IN) ====================

    public function reserveTable() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        $tableId    = (int)($data['table_id'] ?? 0);
        $name       = trim($data['customer_name'] ?? '');
        $phone      = trim($data['customer_phone'] ?? '');
        $guestCount = max(1, (int)($data['guest_count'] ?? 2));
        $date       = trim($data['reserve_date'] ?? '');
        $time       = trim($data['reserve_time'] ?? '');
        $note       = trim($data['reserve_note'] ?? '');
        $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;

        if ($tableId <= 0 || empty($name) || empty($phone) || empty($date) || empty($time)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ Tên, SĐT, Ngày và Giờ hẹn!']);
            exit;
        }

        $reservationTimeStr = "$date $time:00";
        $targetTs = strtotime($reservationTimeStr);
        $nowTs    = time();

        if (!$targetTs) {
            echo json_encode(['success' => false, 'message' => 'Định dạng ngày giờ đặt bàn không hợp lệ!']);
            exit;
        }

        // Quy định thời gian: Tối thiểu trước 2 tiếng (120p), tối đa 24 tiếng (1440p)
        $diffMinutes = ($targetTs - $nowTs) / 60;
        if ($diffMinutes < 115) {
            echo json_encode(['success' => false, 'message' => 'Thời gian đặt bàn phải trước giờ hẹn tối thiểu 2 tiếng!']);
            exit;
        }
        if ($diffMinutes > 1445) {
            echo json_encode(['success' => false, 'message' => 'Chỉ nhận đặt bàn trước tối đa 1 ngày (24 tiếng)!']);
            exit;
        }

        $db = Database::getInstance();

        // 1. Kiểm tra SĐT này đã có lịch đặt đang chờ trong khung giờ gần nhau chưa (cách < 2 tiếng)
        $stmtPhoneCheck = $db->prepare("SELECT r.reservation_time, t.table_name FROM reservations r 
                                        LEFT JOIN tables t ON r.table_id = t.id 
                                        WHERE r.customer_phone = ? 
                                          AND r.status IN ('confirmed', 'pending')
                                          AND ABS(TIMESTAMPDIFF(MINUTE, r.reservation_time, ?)) < 120
                                        LIMIT 1");
        $stmtPhoneCheck->execute([$phone, $reservationTimeStr]);
        $existingPhoneRes = $stmtPhoneCheck->fetch(PDO::FETCH_ASSOC);

        if ($existingPhoneRes) {
            $existingTable = $existingPhoneRes['table_name'] ?? 'bàn khác';
            $existingTime = date('H:i d/m', strtotime($existingPhoneRes['reservation_time']));
            echo json_encode([
                'success' => false, 
                'message' => "SĐT {$phone} đã có lịch đặt tại {$existingTable} lúc {$existingTime} (cách dưới 2 tiếng)!"
            ]);
            exit;
        }

        // 2. Kiểm tra xung đột khung giờ trên cùng 1 bàn (cho phép đặt nhiều lịch trên cùng 1 bàn nếu cách nhau >= 2 tiếng)
        $checkSql = "SELECT reservation_time, customer_name FROM reservations 
                     WHERE table_id = ? 
                       AND status IN ('confirmed', 'pending') 
                       AND ABS(TIMESTAMPDIFF(MINUTE, reservation_time, ?)) < 120";
        $stmtCheck = $db->prepare($checkSql);
        $stmtCheck->execute([$tableId, $reservationTimeStr]);
        $conflict = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($conflict) {
            $existingTime = date('H:i d/m', strtotime($conflict['reservation_time']));
            echo json_encode([
                'success' => false, 
                'message' => "Bàn này đã có lịch của khách {$conflict['customer_name']} lúc {$existingTime}. Vui lòng chọn giờ cách ít nhất 2 tiếng!"
            ]);
            exit;
        }

        // Tự động liên kết customer_id nếu SĐT đã là thành viên
        if (!$customerId) {
            $stmtCust = $db->prepare("SELECT id FROM customers WHERE phone = ? LIMIT 1");
            $stmtCust->execute([$phone]);
            $custRow = $stmtCust->fetch(PDO::FETCH_ASSOC);
            if ($custRow) $customerId = (int)$custRow['id'];
        }

        $sql = "INSERT INTO reservations (table_id, customer_id, customer_name, customer_phone, guest_count, reservation_time, note, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')";
        $stmt = $db->prepare($sql);
        $executed = $stmt->execute([$tableId, $customerId, $name, $phone, $guestCount, $reservationTimeStr, $note]);

        if ($executed) {
            $resId = $db->lastInsertId();
            echo json_encode([
                'success' => true,
                'reservation_id' => $resId,
                'message' => 'Tạo lịch đặt bàn thành công!'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể lưu lịch đặt bàn. Vui lòng thử lại!']);
        }
        exit;
    }

    // KHÁCH ĐÃ ĐẾN (CHECK-IN NHANH TỪ DANH SÁCH ĐẶT BÀN)
    public function checkInReservation() {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        $reservationId = (int)($data['reservation_id'] ?? 0);
        if ($reservationId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã lịch đặt không hợp lệ!']);
            exit;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT r.*, t.table_name FROM reservations r LEFT JOIN tables t ON r.table_id = t.id WHERE r.id = ?");
            $stmt->execute([$reservationId]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$res) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy lịch đặt bàn này!']);
                exit;
            }

            // Đánh dấu lịch đặt đã hoàn tất nhận bàn & chuyển trạng thái bàn thành Có khách (1)
            $updRes = $db->prepare("UPDATE reservations SET status = 'completed' WHERE id = ?");
            $updRes->execute([$reservationId]);

            $updTbl = $db->prepare("UPDATE tables SET status = 1 WHERE id = ?");
            $updTbl->execute([(int)$res['table_id']]);

            echo json_encode([
                'success' => true,
                'reservation' => $res,
                'message' => "Đã Check-in cho khách {$res['customer_name']} vào {$res['table_name']}!"
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
        exit;
    }

    public function cancelReservation() {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        $reservationId = (int)($data['reservation_id'] ?? 0);
        $tableId       = (int)($data['table_id'] ?? 0);
        $reason        = trim($data['reason'] ?? 'Khách báo hủy');

        if ($reservationId <= 0 && $tableId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Thông tin lịch đặt không hợp lệ!']);
            exit;
        }

        try {
            $db = Database::getInstance();
            if ($reservationId > 0) {
                $stmtGet = $db->prepare("SELECT table_id FROM reservations WHERE id = ?");
                $stmtGet->execute([$reservationId]);
                $row = $stmtGet->fetch(PDO::FETCH_ASSOC);
                if ($row) $tableId = (int)$row['table_id'];

                $sql = "UPDATE reservations SET status = 'cancelled', cancel_reason = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([$reason, $reservationId]);
            } else {
                $sql = "UPDATE reservations SET status = 'cancelled', cancel_reason = ? WHERE table_id = ? AND status IN ('confirmed', 'pending')";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([$reason, $tableId]);
            }

            // Nếu bàn đang ở trạng thái 2 (Đã đặt) và không còn lịch nào sắp tới trong 60p thì trả về Trống (0)
            if ($tableId > 0) {
                $stmtCheckNext = $db->prepare("SELECT COUNT(*) FROM reservations 
                                               WHERE table_id = ? AND status IN ('confirmed', 'pending') 
                                                 AND TIMESTAMPDIFF(MINUTE, NOW(), reservation_time) BETWEEN -30 AND 60");
                $stmtCheckNext->execute([$tableId]);
                $hasUpcoming = (int)$stmtCheckNext->fetchColumn() > 0;

                if (!$hasUpcoming) {
                    $stmtTable = $db->prepare("UPDATE tables SET status = 0 WHERE id = ? AND status = 2");
                    $stmtTable->execute([$tableId]);
                }
            }

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Đã hủy lịch đặt bàn thành công!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể hủy lịch đặt bàn. Vui lòng thử lại!']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Không thể hủy lịch đặt bàn. Vui lòng kiểm tra lại!']);
        }
        exit;
    }

    public function updateTableStatus() {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        $tableId = (int)($data['table_id'] ?? 0);
        $status  = trim($data['status'] ?? 'available');

        if ($tableId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID bàn không hợp lệ!']);
            exit;
        }

        $statusVal = ($status === 'occupied') ? 1 : (($status === 'reserved') ? 2 : 0);

        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE tables SET status = ? WHERE id = ?");
        if ($stmt->execute([$statusVal, $tableId])) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái bàn thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái bàn!']);
        }
        exit;
    }

    // LẤY DANH SÁCH ĐẶT BÀN ĐA LỊCH & TRẠNG THÁI BÀN THÔNG MINH (CHỈ ĐỔI MÀU VÀNG TRƯỚC 60 PHÚT)
    public function getReservations() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance();

            // 1. Lấy danh sách toàn bộ bàn dine_in
            $stmtTables = $db->query("SELECT t.id as table_id, t.table_name, t.status as table_status, a.area_name
                                      FROM tables t
                                      LEFT JOIN areas a ON t.area_id = a.id
                                      WHERE t.table_type = 'dine_in'
                                      ORDER BY t.id ASC");
            $tables = $stmtTables->fetchAll(PDO::FETCH_ASSOC);

            // 2. Lấy toàn bộ lịch đặt đang chờ (confirmed/pending), sắp xếp theo giờ hẹn tăng dần
            $stmtRes = $db->query("SELECT r.id as reservation_id, r.table_id, t.table_name, a.area_name,
                                          r.customer_id, r.customer_name, r.customer_phone, r.guest_count,
                                          DATE_FORMAT(r.reservation_time, '%Y-%m-%d %H:%i:%s') as reservation_time, 
                                          r.note, r.status,
                                          TIMESTAMPDIFF(MINUTE, NOW(), r.reservation_time) as mins_until
                                   FROM reservations r
                                   INNER JOIN tables t ON r.table_id = t.id
                                   LEFT JOIN areas a ON t.area_id = a.id
                                   WHERE r.status IN ('confirmed', 'pending')
                                   ORDER BY r.reservation_time ASC");
            $allReservations = $stmtRes->fetchAll(PDO::FETCH_ASSOC);

            // Gom lịch theo table_id & đồng bộ màu bàn thông minh
            $resByTable = [];
            foreach ($allReservations as $r) {
                $tid = (int)$r['table_id'];
                if (!isset($resByTable[$tid])) $resByTable[$tid] = [];
                $resByTable[$tid][] = $r;
            }

            foreach ($tables as &$tbl) {
                $tid = (int)$tbl['table_id'];
                $tbl['upcoming_reservations'] = $resByTable[$tid] ?? [];
                $nextRes = $tbl['upcoming_reservations'][0] ?? null;

                // Nếu bàn đang Trống (0) mà có lịch hẹn trong vòng 60 phút tới -> tự động chuyển sang 2 (Đã đặt)
                // Ngược lại nếu bàn đang 2 (Đã đặt) nhưng lịch hẹn còn > 60 phút nữa -> trả về 0 (Trống) để khách vãng lai vẫn ngồi xoay tua được!
                if ((int)$tbl['table_status'] !== 1) {
                    if ($nextRes && (int)$nextRes['mins_until'] <= 60 && (int)$nextRes['mins_until'] >= -30) {
                        if ((int)$tbl['table_status'] !== 2) {
                            $db->prepare("UPDATE tables SET status = 2 WHERE id = ?")->execute([$tid]);
                            $tbl['table_status'] = 2;
                        }
                    } else {
                        if ((int)$tbl['table_status'] === 2) {
                            $db->prepare("UPDATE tables SET status = 0 WHERE id = ?")->execute([$tid]);
                            $tbl['table_status'] = 0;
                        }
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'tables' => $tables,
                'reservations' => $allReservations
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    // TẠO LỊCH ĐẶT BÀN MỚI (CÓ CHECK TRÙNG SĐT & ĐỒNG BỘ KHÁCH HÀNG)
    public function createReservation() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $tableId = (int)($input['table_id'] ?? 0);
            $customerName = trim($input['customer_name'] ?? '');
            $customerPhone = trim($input['customer_phone'] ?? '');
            $guestCount = max(1, (int)($input['guest_count'] ?? 1));
            $reservationTime = trim($input['reservation_time'] ?? '');
            $note = trim($input['note'] ?? '');

            if ($tableId <= 0 || $customerName === '' || $customerPhone === '' || $reservationTime === '') {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin đặt bàn!']);
                exit;
            }

            $db = Database::getInstance();

            // 1. Kiểm tra trùng SĐT đang có lịch đặt bàn hiệu lực (confirmed/pending)
            $stmtDupPhone = $db->prepare("SELECT r.id, r.reservation_time, t.table_name 
                                          FROM reservations r
                                          LEFT JOIN tables t ON r.table_id = t.id
                                          WHERE r.customer_phone = ? 
                                            AND r.status IN ('confirmed', 'pending')
                                            AND r.reservation_time >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                                          LIMIT 1");
            $stmtDupPhone->execute([$customerPhone]);
            $existingPhoneRes = $stmtDupPhone->fetch(PDO::FETCH_ASSOC);

            if ($existingPhoneRes) {
                $tName = $existingPhoneRes['table_name'] ?: ('Bàn ' . $tableId);
                $tTime = date('H:i d/m', strtotime($existingPhoneRes['reservation_time']));
                echo json_encode([
                    'success' => false,
                    'message' => "SĐT {$customerPhone} đã có lịch đặt tại {$tName} lúc {$tTime}. Không thể đặt trùng nhiều bàn cùng lúc!"
                ]);
                exit;
            }

            // 2. Kiểm tra trùng khung giờ trên cùng 1 bàn (cách nhau tối thiểu 90 phút)
            $stmtConflict = $db->prepare("SELECT customer_name, reservation_time 
                                          FROM reservations 
                                          WHERE table_id = ? 
                                            AND status IN ('confirmed', 'pending')
                                            AND ABS(TIMESTAMPDIFF(MINUTE, reservation_time, ?)) < 90
                                          LIMIT 1");
            $stmtConflict->execute([$tableId, $reservationTime]);
            $conflict = $stmtConflict->fetch(PDO::FETCH_ASSOC);

            if ($conflict) {
                $cTime = date('H:i d/m', strtotime($conflict['reservation_time']));
                echo json_encode([
                    'success' => false,
                    'message' => "Bàn này đã có khách [{$conflict['customer_name']}] đặt lúc {$cTime}. Vui lòng chọn giờ cách tối thiểu 90 phút!"
                ]);
                exit;
            }

            // 3. Tự động liên kết customer_id nếu SĐT đã là thành viên
            $stmtCus = $db->prepare("SELECT id FROM customers WHERE phone = ? LIMIT 1");
            $stmtCus->execute([$customerPhone]);
            $customerId = $stmtCus->fetchColumn() ?: null;

            // 4. Lưu lịch đặt bàn vào DB
            $stmt = $db->prepare("INSERT INTO reservations (table_id, customer_id, customer_name, customer_phone, guest_count, reservation_time, note, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')");
            $stmt->execute([$tableId, $customerId, $customerName, $customerPhone, $guestCount, $reservationTime, $note]);
            $newResId = $db->lastInsertId();

            echo json_encode([
                'success' => true,
                'reservation_id' => $newResId,
                'message' => 'Đã tạo lịch đặt bàn thành công!'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    public function checkShiftTimeout() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        header('Content-Type: application/json');
        $shiftType = trim($_GET['shift_type'] ?? '');

        if (empty($shiftType)) {
            echo json_encode(['timeout' => false]);
            exit;
        }

        $currentHour = (int)date('H');
        $currentMinute = (int)date('i');
        $totalMinutes = $currentHour * 60 + $currentMinute;

        $isTimeout = false;
        if ($shiftType === 'morning' && $totalMinutes >= 870) {
            $isTimeout = true;
        } elseif ($shiftType === 'afternoon' && $totalMinutes >= 1350) {
            $isTimeout = true;
        } elseif ($shiftType === 'night' && ($totalMinutes >= 390 && $totalMinutes < 1320)) {
            $isTimeout = true;
        }

        echo json_encode([
            'timeout' => $isTimeout,
            'message' => $isTimeout ? 'Đã quá 30 phút so với giờ kết thúc ca làm việc! Vui lòng thực hiện KẾT CA trước khi tiếp tục bán hàng.' : ''
        ]);
        exit;
    }
}
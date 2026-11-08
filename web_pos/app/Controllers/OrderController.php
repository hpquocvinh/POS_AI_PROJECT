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
     * Tự động kiểm tra và nâng cấp kiểu dữ liệu cột tiền/điểm/VAT để tránh lỗi tràn số và lưu cấu hình
     */
    private function ensureDatabaseSchema() {
        try {
            $db = Database::getInstance();
            // Nâng cấp cột lưu tiền trong bảng orders
            $db->exec("ALTER TABLE orders MODIFY COLUMN total_amount DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE orders MODIFY COLUMN discount DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE orders MODIFY COLUMN final_amount DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE orders MODIFY COLUMN customer_pay DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE orders MODIFY COLUMN change_amount DECIMAL(15,2) DEFAULT 0");

            // Đảm bảo cột vat_percent có trong bảng orders
            $checkVatCol = $db->query("SHOW COLUMNS FROM orders LIKE 'vat_percent'");
            if (!$checkVatCol || $checkVatCol->rowCount() == 0) {
                $db->exec("ALTER TABLE orders ADD COLUMN vat_percent DECIMAL(5,2) DEFAULT 8.00");
            }

            // Nâng cấp cột doanh thu ca trong bảng shifts
            $db->exec("ALTER TABLE shifts MODIFY COLUMN initial_cash DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN total_revenue DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN offline_revenue DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN online_revenue DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN total_cash_sales DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN total_bank_sales DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN total_app_sales DECIMAL(15,2) DEFAULT 0");
            $db->exec("ALTER TABLE shifts MODIFY COLUMN ending_cash_actual DECIMAL(15,2) DEFAULT 0");

            // Nâng cấp cột điểm tích lũy và theo dõi tụt hạng 30 ngày cho khách hàng
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

            // Tạo bảng system_settings nếu chưa có để lưu % VAT động
            $db->exec("CREATE TABLE IF NOT EXISTS system_settings (
                setting_key VARCHAR(50) PRIMARY KEY,
                setting_value VARCHAR(255) NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $checkSetting = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'vat_percent'");
            if (!$checkSetting || $checkSetting->rowCount() == 0) {
                $db->exec("INSERT INTO system_settings (setting_key, setting_value) VALUES ('vat_percent', '8.00')");
            }

            // Đảm bảo bảng reservations có cột cancel_reason
            $checkCancelCol = $db->query("SHOW COLUMNS FROM reservations LIKE 'cancel_reason'");
            if (!$checkCancelCol || $checkCancelCol->rowCount() == 0) {
                $db->exec("ALTER TABLE reservations ADD COLUMN cancel_reason VARCHAR(255) NULL");
            }
        } catch (Exception $e) {
            // Bỏ qua nếu đã được nâng cấp trước đó
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

    // TRA CỨU KHÁCH HÀNG (5 HẠNG & KIỂM TRA TỤT HẠNG CUỐN CHIẾU 30 NGÀY)
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
                $customer['member_rank'] = $customer['member_rank'] ?? 'Bronze';
                $customer['total_points'] = (int)($customer['total_points'] ?? 0);
                $customer['lifetime_spend'] = (float)($customer['lifetime_spend'] ?? 0);
                $customer['maintained_points'] = (int)($customer['maintained_points'] ?? 0);

                // KIỂM TRA VÀ XỬ LÝ TỤT HẠNG 30 NGÀY
                $rankUpdatedAt = $customer['rank_updated_at'] ? new DateTime($customer['rank_updated_at']) : new DateTime();
                $now = new DateTime();
                $interval = $now->diff($rankUpdatedAt);

                if ($interval->days >= 30 && $customer['member_rank'] !== 'Bronze') {
                    $thresholds = [
                        'VIP'     => 1000,
                        'Diamond' => 600,
                        'Gold'    => 300,
                        'Silver'  => 100
                    ];

                    $downgrades = [
                        'VIP'     => 'Diamond',
                        'Diamond' => 'Gold',
                        'Gold'    => 'Silver',
                        'Silver'  => 'Bronze'
                    ];

                    $requiredPts = $thresholds[$customer['member_rank']] ?? 0;
                    $halfRequired = floor($requiredPts * 0.5);

                    // Nếu điểm duy trì 30 ngày không đạt 50% mốc -> Rớt 1 cấp
                    if ($customer['maintained_points'] < $halfRequired) {
                        $newRank = $downgrades[$customer['member_rank']] ?? 'Bronze';
                        $updateRank = $db->prepare("UPDATE customers SET member_rank = ?, rank_updated_at = NOW(), maintained_points = 0 WHERE id = ?");
                        $updateRank->execute([$newRank, $customer['id']]);

                        $customer['member_rank'] = $newRank;
                    } else {
                        // Đạt mốc duy trì -> Reset chu kỳ 30 ngày mới
                        $resetMaint = $db->prepare("UPDATE customers SET rank_updated_at = NOW(), maintained_points = 0 WHERE id = ?");
                        $resetMaint->execute([$customer['id']]);
                    }
                }

                // CẤU HÌNH % GIẢM GIÁ THEO 5 HẠNG CHUẨN
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

        // TÍCH ĐIỂM DỰA TRÊN HẠNG THÀNH VIÊN KHÁCH HÀNG (10.000đ = 1pt x Hệ số)
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

            // KHI THANH TOÁN THÀNH CÔNG -> CẬP NHẬT TRẠNG THÁI
            if (isset($result['success']) && $result['success']) {
                $tableId = !empty($data['table_id']) ? (int)$data['table_id'] : 0;
                if ($tableId > 0) {
                    $stmtRes = $db->prepare("UPDATE reservations SET status = 'completed' WHERE table_id = ? AND status IN ('confirmed', 'pending')");
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

            // KIỂM TRA BẮT BUỘC ĐÚNG KHUNG GIỜ MỞ CA
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
            
            // Tự động đóng các ca đang mở cũ
            $db->exec("UPDATE shifts SET status = 'closed', end_time = NOW() WHERE status = 'open'");

            // Thêm bản ghi mở ca mới
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

    // LỌC BÁO CÁO DOANH THU CA CHÍNH XÁC VÀ TẢI MỨC VAT HỆ THỐNG
    public function getShiftStatus() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance();

            // Lấy % VAT từ CSDL
            $vatPercent = 8.0;
            $stmtVat = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'vat_percent' LIMIT 1");
            if ($stmtVat && $rowVat = $stmtVat->fetch(PDO::FETCH_ASSOC)) {
                $vatPercent = (float)$rowVat['setting_value'];
            }

            $stmt = $db->query("SELECT * FROM shifts WHERE status = 'open' ORDER BY id DESC LIMIT 1");
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($shift) {
                $shiftId = $shift['id'];
                
                // Cố định lọc doanh thu và số lượng món theo đúng shift_id của ca đang mở
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

    // API CẬP NHẬT % VAT HỆ THỐNG DÀNH CHO ADMIN
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
                        total_orders = ?, 
                        total_cups = ?, 
                        total_cakes = ?, 
                        status = 'closed' 
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $totalRevenue, $offlineRevenue, $onlineRevenue, $totalCashSales, $totalBankSales, $totalAppSales, $endingCashActual, 
                $totalOrders, $totalCups, $totalCakes, $shiftId
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
                        total_orders = ?, 
                        total_cups = ?, 
                        total_cakes = ?, 
                        status = 'closed' 
                    WHERE status = 'open' ORDER BY id DESC LIMIT 1";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $totalRevenue, $offlineRevenue, $onlineRevenue, $totalCashSales, $totalBankSales, $totalAppSales, $endingCashActual, $totalOrders, $totalCups, $totalCakes
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

    public function reserveTable() {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        $tableId = (int)($data['table_id'] ?? 0);
        $name    = trim($data['customer_name'] ?? '');
        $phone   = trim($data['customer_phone'] ?? '');
        $date    = trim($data['reserve_date'] ?? '');
        $time    = trim($data['reserve_time'] ?? '');
        $note    = trim($data['reserve_note'] ?? '');
        $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;

        if ($tableId <= 0 || empty($name) || empty($phone) || empty($date) || empty($time)) {
            echo json_encode(['success' => false, 'message' => 'Thông tin đặt bàn không đầy đủ!']);
            exit;
        }

        $reservationTimeStr = "$date $time:00";
        $db = Database::getInstance();

        // 1. Kiểm tra trùng SĐT đang chờ đặt
        $stmtPhoneCheck = $db->prepare("SELECT r.reservation_time, t.table_name FROM reservations r 
                                        LEFT JOIN tables t ON r.table_id = t.id 
                                        WHERE r.customer_phone = ? AND r.status IN ('confirmed', 'pending') LIMIT 1");
        $stmtPhoneCheck->execute([$phone]);
        $existingPhoneRes = $stmtPhoneCheck->fetch(PDO::FETCH_ASSOC);

        if ($existingPhoneRes) {
            $existingTable = $existingPhoneRes['table_name'] ?? 'bàn khác';
            $existingTime = date('H:i d/m', strtotime($existingPhoneRes['reservation_time']));
            echo json_encode([
                'success' => false, 
                'message' => "Số điện thoại {$phone} đã có lịch đặt đang chờ tại {$existingTable} lúc {$existingTime}!"
            ]);
            exit;
        }

        // 2. Kiểm tra xung đột khung giờ trên cùng 1 bàn
        $checkSql = "SELECT reservation_time FROM reservations 
                     WHERE table_id = ? 
                       AND status IN ('confirmed', 'pending') 
                       AND ABS(TIMESTAMPDIFF(SECOND, reservation_time, ?)) < 7200";
        $stmtCheck = $db->prepare($checkSql);
        $stmtCheck->execute([$tableId, $reservationTimeStr]);
        $conflict = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($conflict) {
            $existingTime = date('H:i', strtotime($conflict['reservation_time']));
            echo json_encode([
                'success' => false, 
                'message' => "Bàn này đã có lịch đặt lúc {$existingTime}. Vui lòng chọn khung giờ cách ít nhất 2 tiếng!"
            ]);
            exit;
        }

        $sql = "INSERT INTO reservations (table_id, customer_id, customer_name, customer_phone, reservation_time, note, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')";
        $stmt = $db->prepare($sql);
        $executed = $stmt->execute([$tableId, $customerId, $name, $phone, $reservationTimeStr, $note]);

        if ($executed) {
            $resId = $db->lastInsertId();
            $stmtTable = $db->prepare("UPDATE tables SET status = 2 WHERE id = ?");
            $stmtTable->execute([$tableId]);

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

    public function cancelReservation() {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        $tableId = (int)($data['table_id'] ?? 0);
        $reason  = trim($data['reason'] ?? 'Khách báo hủy');

        if ($tableId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã bàn không hợp lệ!']);
            exit;
        }

        try {
            $db = Database::getInstance();
            $sql = "UPDATE reservations SET status = 'cancelled', cancel_reason = ? WHERE table_id = ? AND status IN ('confirmed', 'pending')";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$reason, $tableId]);

            $stmtTable = $db->prepare("UPDATE tables SET status = 0 WHERE id = ?");
            $stmtTable->execute([$tableId]);

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

        $tableId    = (int)($data['table_id'] ?? 0);
        $status     = trim($data['status'] ?? 'available');
        $keepStatus = isset($data['keep_status']) ? (bool)$data['keep_status'] : false;

        if ($tableId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID bàn không hợp lệ!']);
            exit;
        }

        $statusVal = 0;
        if ($keepStatus) {
            $statusVal = ($status === 'occupied') ? 1 : (($status === 'reserved') ? 2 : 0);
        } else {
            $statusVal = ($status === 'occupied') ? 1 : (($status === 'reserved') ? 2 : 0);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE tables SET status = ? WHERE id = ?");
        if ($stmt->execute([$statusVal, $tableId])) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái bàn thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái bàn!']);
        }
        exit;
    }

    public function getReservations() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT t.id as table_id, t.table_name, t.status as table_status, 
                                       r.id as reservation_id, r.customer_name, r.customer_phone, 
                                       DATE_FORMAT(r.reservation_time, '%Y-%m-%d %H:%i:%s') as reservation_time, r.note
                                FROM tables t
                                LEFT JOIN reservations r ON t.id = r.table_id AND r.status IN ('confirmed', 'pending')
                                WHERE LOWER(t.table_name) NOT LIKE '%mang về%' 
                                  AND LOWER(t.table_name) NOT LIKE '%mv-%' 
                                  AND LOWER(t.table_name) NOT LIKE '%grab%' 
                                  AND LOWER(t.table_name) NOT LIKE '%shopee%' 
                                  AND LOWER(t.table_name) NOT LIKE '%app%'
                                ORDER BY t.id ASC");
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'tables' => $tables, 'reservations' => $tables]);
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
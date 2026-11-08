<?php
// app/Models/OrderModel.php

class OrderModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // 1. Lấy sơ đồ Bàn tại chỗ (Dine-in)
    public function getAreasWithTables() {
        $stmtAreas = $this->db->query("SELECT id, area_name AS name FROM areas WHERE status = 1 ORDER BY id ASC");
        $areas = $stmtAreas->fetchAll(PDO::FETCH_ASSOC);

        if (empty($areas)) {
            return [];
        }

        foreach ($areas as &$area) {
            $stmtTables = $this->db->prepare("SELECT id, table_name AS name, status FROM tables WHERE area_id = ? AND table_type = 'dine_in' ORDER BY id ASC");
            $stmtTables->execute([$area['id']]);
            $rawTables = $stmtTables->fetchAll(PDO::FETCH_ASSOC);

            $area['tables'] = array_map(function($t) {
                $statusStr = 'available';
                if ($t['status'] == 1) $statusStr = 'occupied';
                else if ($t['status'] == 2) $statusStr = 'reserved';

                return [
                    'id'     => $t['id'],
                    'name'   => $t['name'],
                    'status' => $statusStr
                ];
            }, $rawTables);
        }

        return $areas;
    }

    // 2. Lấy Danh mục sản phẩm
    public function getCategories() {
        $stmt = $this->db->query("SELECT id, category_name AS name FROM categories ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Lấy Danh sách Sản phẩm
    public function getProducts() {
        $stmt = $this->db->query("SELECT id, category_id, product_name AS name, price, stock_quantity, image AS image_url FROM products WHERE status = 1 ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. TÌM KIẾM HOẶC TẠO KHÁCH HÀNG THEO SĐT (TÍCH ĐIỂM LOYALTY)
    public function getOrCreateCustomerByPhone($phone, $name = 'Khách Lẻ') {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE phone = ?");
        $stmt->execute([$phone]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            $stmtInsert = $this->db->prepare("INSERT INTO customers (customer_name, phone, total_points, member_rank, rank_updated_at, maintained_points) VALUES (?, ?, 0, 'Bronze', NOW(), 0)");
            $stmtInsert->execute([$name, $phone]);
            $customerId = $this->db->lastInsertId();

            return [
                'id' => $customerId,
                'customer_name' => $name,
                'phone' => $phone,
                'total_points' => 0,
                'member_rank' => 'Bronze'
            ];
        }

        return $customer;
    }

    // 5. TẠO ĐƠN HÀNG TOÀN NĂNG (TẠI CHỖ, MANG VỀ, GRAB/SHOPEE, TÍCH ĐIỂM & TRỪ TỒN KHO)
    public function createOrder($data) {
        try {
            $this->db->beginTransaction();

            $orderType      = $data['order_type'] ?? 'dine_in'; // dine_in, takeaway, grab, shopee
            $tableId        = !empty($data['table_id']) ? (int)$data['table_id'] : null;
            $orderCode      = $data['order_code'] ?? ('HD' . time());
            $appOrderCode   = $data['app_order_code'] ?? null;
            $driverName     = $data['driver_name'] ?? null;
            $driverPhone    = $data['driver_phone'] ?? null;
            $userId         = $data['user_id'] ?? 1;
            $customerId     = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
            
            $totalAmount    = (float)($data['total_amount'] ?? 0);
            $customerPay    = (float)($data['customer_pay'] ?? 0);
            $changeAmount   = (float)($data['change_amount'] ?? 0);
            $discount       = (float)($data['discount'] ?? 0);
            $vatPercent     = (float)($data['vat_percent'] ?? 0);
            $finalAmount    = (float)($data['final_amount'] ?? $totalAmount);
            $commissionRate = (float)($data['commission_rate'] ?? 0);
            
            // Tính Doanh thu thực nhận sau trừ hoa hồng sàn (nếu là đơn App)
            $netAmount = $finalAmount - ($finalAmount * ($commissionRate / 100));

            // Logic Tích điểm theo Hạng Thành Viên
            $pointsEarned   = (int)($data['points_earned'] ?? 0);
            $pointsRedeemed = (int)($data['points_redeemed'] ?? 0);

            $paymentMethod = $data['payment_method'] ?? 'cash';
            $orderStatus   = $data['order_status'] ?? 'completed';
            $shiftId       = $data['shift_id'] ?? null;

            // 5.1 Thêm đơn hàng vào bảng orders
            $sqlOrder = "INSERT INTO orders (
                shift_id, table_id, order_type, app_order_code, driver_name, driver_phone, 
                order_code, user_id, customer_id, total_amount, customer_pay, change_amount, 
                discount, vat_percent, commission_rate, final_amount, net_amount, points_earned, points_redeemed, 
                payment_method, order_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmtOrder = $this->db->prepare($sqlOrder);
            $stmtOrder->execute([
                $shiftId, $tableId, $orderType, $appOrderCode, $driverName, $driverPhone,
                $orderCode, $userId, $customerId, $totalAmount, $customerPay, $changeAmount,
                $discount, $vatPercent, $commissionRate, $finalAmount, $netAmount, $pointsEarned, $pointsRedeemed,
                $paymentMethod, $orderStatus
            ]);

            $orderId = $this->db->lastInsertId();

            // 5.2 Lưu chi tiết đơn hàng & Tự động trừ Tồn kho
            if (!empty($data['items'])) {
                $sqlItem = "INSERT INTO order_details (order_id, product_id, quantity, options_json, item_note, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmtItem = $this->db->prepare($sqlItem);

                $sqlUpdateStock = $this->db->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?");

                foreach ($data['items'] as $item) {
                    $productId   = (int)$item['id'];
                    $quantity    = (int)$item['qty'];
                    $unitPrice   = (float)$item['price'];
                    $subtotal    = $unitPrice * $quantity;
                    $itemNote    = $item['note'] ?? '';
                    
                    $optionsArr  = [
                        'size'     => $item['size'] ?? '',
                        'sugar'    => $item['sugar'] ?? '',
                        'ice'      => $item['ice'] ?? '',
                        'toppings' => $item['toppings'] ?? []
                    ];
                    $optionsJson = json_encode($optionsArr, JSON_UNESCAPED_UNICODE);

                    // Insert chi tiết
                    $stmtItem->execute([$orderId, $productId, $quantity, $optionsJson, $itemNote, $unitPrice, $subtotal]);

                    // Trừ kho nguyên liệu/sản phẩm
                    $sqlUpdateStock->execute([$quantity, $productId]);
                }
            }

            // 5.3 Cập nhật điểm tích lũy và tổng chi tiêu tích lũy cho Khách hàng
            if ($customerId) {
                $netPointChange = $pointsEarned - $pointsRedeemed;
                $stmtPoints = $this->db->prepare("UPDATE customers SET 
                    total_points = GREATEST(0, total_points + ?), 
                    maintained_points = GREATEST(0, maintained_points + ?),
                    lifetime_spend = lifetime_spend + ? 
                    WHERE id = ?");
                $stmtPoints->execute([$netPointChange, max(0, $netPointChange), $finalAmount, $customerId]);

                // Cập nhật hạng thành viên tự động
                $this->updateCustomerRank($customerId);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Lưu đơn hàng thành công!', 'order_id' => $orderId, 'order_code' => $orderCode];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }

    // 6. Cập nhật Hạng Thành Viên (Quy chuẩn 5 Hạng)
    private function updateCustomerRank($customerId) {
        $stmt = $this->db->prepare("SELECT total_points FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $cust = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cust) {
            $pts = (int)$cust['total_points'];
            $rank = 'Bronze';
            if ($pts >= 1000) $rank = 'VIP';
            else if ($pts >= 600) $rank = 'Diamond';
            else if ($pts >= 300) $rank = 'Gold';
            else if ($pts >= 100) $rank = 'Silver';

            $stmtRank = $this->db->prepare("UPDATE customers SET member_rank = ?, rank_updated_at = NOW() WHERE id = ?");
            $stmtRank->execute([$rank, $customerId]);
        }
    }

    // 7. Cập nhật trạng thái bàn
    public function updateTableStatus($tableId, $status) {
        $stmt = $this->db->prepare("UPDATE tables SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $tableId]);
    }

}
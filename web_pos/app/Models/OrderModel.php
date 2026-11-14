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
        $stmt = $this->db->query("SELECT * FROM categories WHERE type = 'product' AND status = 1 ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Lấy Danh sách Sản phẩm (Kèm min_stock & expiry_date để cảnh báo Real-time trên POS)
    public function getProducts() {
        $sql = "SELECT p.id, p.category_id, p.product_name AS name, p.price, p.stock_quantity, p.min_stock, p.expiry_date, p.manage_stock, p.image AS image_url, p.modifiers_config
                FROM products p
                INNER JOIN categories c ON p.category_id = c.id
                WHERE p.status = 1 AND c.type = 'product' AND c.status = 1 
                ORDER BY p.id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. TÌM KIẾM HOẶC TẠO KHÁCH HÀNG THEO SĐT
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

            $orderType      = $data['order_type'] ?? 'dine_in';
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
            
            $netAmount = $finalAmount - ($finalAmount * ($commissionRate / 100));

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

            // 5.2 Lưu chi tiết đơn hàng & Tự động trừ Tồn kho (xử lý an toàn khóa ngoại cho Quà Đổi Điểm)
            if (!empty($data['items'])) {
                $sqlItem = "INSERT INTO order_details (order_id, product_id, quantity, options_json, item_note, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmtItem = $this->db->prepare($sqlItem);

                // Lấy sẵn 1 product_id hợp lệ mặc định trong bảng products để tránh lỗi FOREIGN KEY (order_details_ibfk_2) với quà tặng ảo
                $fallbackStmt = $this->db->query("SELECT id FROM products ORDER BY id ASC LIMIT 1");
                $fallbackProductId = $fallbackStmt ? (int)$fallbackStmt->fetchColumn() : 1;

                $stmtFindById      = $this->db->prepare("SELECT id, product_name, manage_stock, stock_quantity FROM products WHERE id = ? FOR UPDATE");
                $stmtFindByName    = $this->db->prepare("SELECT id FROM products WHERE product_name LIKE ? LIMIT 1");
                $stmtDeductProduct = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmtGetRecipes    = $this->db->prepare("SELECT ingredient_id, quantity_required FROM product_recipes WHERE product_id = ?");
                $stmtDeductIngredient = $this->db->prepare("UPDATE ingredients SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?");

                foreach ($data['items'] as $item) {
                    $rawId       = $item['id'] ?? 0;
                    $itemName    = trim($item['name'] ?? 'Sản phẩm');
                    $quantity    = max(1, (int)($item['qty'] ?? 1));
                    $unitPrice   = (float)($item['price'] ?? 0);
                    $subtotal    = $unitPrice * $quantity;
                    $itemNote    = trim($item['note'] ?? '');
                    $isGift      = !empty($item['is_gift']) || strpos((string)$rawId, 'GIFT') === 0 || strpos($itemName, '[QUÀ TẶNG]') === 0;

                    $optionsArr  = [
                        'size'     => $item['size'] ?? '',
                        'sugar'    => $item['sugar'] ?? '',
                        'ice'      => $item['ice'] ?? '',
                        'toppings' => $item['toppings'] ?? []
                    ];
                    $optionsJson = json_encode($optionsArr, JSON_UNESCAPED_UNICODE);

                    $validProductId = 0;
                    $prodInfo = null;

                    if (!$isGift && is_numeric($rawId) && (int)$rawId > 0) {
                        $stmtFindById->execute([(int)$rawId]);
                        $prodInfo = $stmtFindById->fetch(PDO::FETCH_ASSOC);
                        if ($prodInfo) {
                            $validProductId = (int)$prodInfo['id'];
                        }
                    }

                    // Nếu là quà tặng hoặc ID không khớp bảng products -> map sang ID hợp lệ để không dính lỗi khóa ngoại
                    if ($validProductId <= 0) {
                        $cleanGiftName = trim(preg_replace('/^\[QUÀ TẶNG\]\s*/iu', '', $itemName));
                        $stmtFindByName->execute(['%' . $cleanGiftName . '%']);
                        $matchedId = (int)$stmtFindByName->fetchColumn();
                        $validProductId = ($matchedId > 0) ? $matchedId : $fallbackProductId;

                        $giftTag = '[QUÀ TẶNG: ' . $cleanGiftName . ']';
                        if (strpos($itemNote, $giftTag) === false) {
                            $itemNote = trim($giftTag . ($itemNote ? ' - ' . $itemNote : ''));
                        }
                    }

                    if (!$isGift && $prodInfo) {
                        if ((int)($prodInfo['manage_stock'] ?? 0) === 1) {
                            // Nhánh 1: Quản lý tồn kho trực tiếp -> Trừ đúng số lượng thực tế
                            $stmtDeductProduct->execute([$quantity, $validProductId]);
                        } else {
                            // Nhánh 2: Món pha chế -> Trừ kho nguyên liệu theo công thức BOM (Size L nhân hệ số 1.3x)
                            $sizeStr = strtoupper(trim((string)($item['size'] ?? '')));
                            $sizeMultiplier = ($sizeStr === 'L' || strpos($sizeStr, 'SIZE L') !== false) ? 1.3 : 1.0;

                            $stmtGetRecipes->execute([$validProductId]);
                            $recipes = $stmtGetRecipes->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($recipes as $rec) {
                                $deductQty = round((float)$rec['quantity_required'] * $quantity * $sizeMultiplier, 3);
                                if ($deductQty > 0) {
                                    $stmtDeductIngredient->execute([$deductQty, (int)$rec['ingredient_id']]);
                                }
                            }
                        }
                    }

                    $stmtItem->execute([$orderId, $validProductId, $quantity, $optionsJson, $itemNote, $unitPrice, $subtotal]);
                }
            }

            // 5.3 Cập nhật điểm tiêu dùng & tổng chi tiêu tích lũy cho Khách hàng
            if ($customerId) {
                $netPointChange = $pointsEarned - $pointsRedeemed;
                $stmtPoints = $this->db->prepare("UPDATE customers SET 
                    total_points = GREATEST(0, total_points + ?), 
                    maintained_points = GREATEST(0, maintained_points + ?),
                    lifetime_spend = lifetime_spend + ? 
                    WHERE id = ?");
                $stmtPoints->execute([$netPointChange, max(0, $pointsEarned), $finalAmount, $customerId]);

                // Cập nhật hạng thành viên dựa trên tổng chi tiêu tích lũy (không tụt hạng khi khách tiêu điểm)
                $this->updateCustomerRank($customerId);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Lưu đơn hàng thành công!', 'order_id' => $orderId, 'order_code' => $orderCode];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }

    // 6. Cập nhật Hạng Thành Viên (Xét theo tổng chi tiêu lũy kế: 10.000đ = 1 điểm xét hạng)
    private function updateCustomerRank($customerId) {
        $stmt = $this->db->prepare("SELECT total_points, lifetime_spend, member_rank FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $cust = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cust) {
            $rankPts = max((int)$cust['total_points'], (int)floor((float)$cust['lifetime_spend'] / 10000));
            $rank = 'Bronze';
            if ($rankPts >= 1000) $rank = 'VIP';
            else if ($rankPts >= 600) $rank = 'Diamond';
            else if ($rankPts >= 300) $rank = 'Gold';
            else if ($rankPts >= 100) $rank = 'Silver';

            $rankOrder = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3, 'Diamond' => 4, 'VIP' => 5];
            $currentRank = $cust['member_rank'] ?? 'Bronze';
            if (($rankOrder[$rank] ?? 1) >= ($rankOrder[$currentRank] ?? 1)) {
                $stmtRank = $this->db->prepare("UPDATE customers SET member_rank = ?, rank_updated_at = NOW() WHERE id = ?");
                $stmtRank->execute([$rank, $customerId]);
            }
        }
    }

    // 7. Cập nhật trạng thái bàn
    public function updateTableStatus($tableId, $status) {
        $stmt = $this->db->prepare("UPDATE tables SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $tableId]);
    }
}
<?php
// app/Models/OrderManageModel.php
// Model xử lý truy vấn danh sách hóa đơn, thống kê KPI, chi tiết món và nghiệp vụ hủy/hoàn tiền

class OrderManageModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Lấy danh sách đơn hàng có phân trang, lọc đa điều kiện và tính 4 chỉ số KPI tổng quan
    public function getOrdersWithStats($filters = [], $limit = 15, $offset = 0) {
        $where  = ["1=1"];
        $params = [];

        // Tìm kiếm theo mã hóa đơn, mã đơn sàn App, tên hoặc số điện thoại khách hàng
        if (!empty($filters['keyword'])) {
            $kw = '%' . trim($filters['keyword']) . '%';
            $where[] = "(o.order_code LIKE ? OR o.app_order_code LIKE ? OR c.customer_name LIKE ? OR c.phone LIKE ?)";
            array_push($params, $kw, $kw, $kw, $kw);
        }

        // Lọc theo mốc thời gian chọn nhanh hoặc khoảng ngày tùy chỉnh
        $datePreset = $filters['date_preset'] ?? '';
        if ($datePreset === 'today') {
            $where[] = "DATE(o.created_at) = CURDATE()";
        } elseif ($datePreset === 'yesterday') {
            $where[] = "DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        } elseif ($datePreset === '7days') {
            $where[] = "DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
        } elseif ($datePreset === 'month') {
            $where[] = "YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())";
        } else {
            if (!empty($filters['date_from'])) {
                $where[] = "DATE(o.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = "DATE(o.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
        }

        // Lọc theo kênh bán hàng (Tại chỗ, Mang về, Đơn Online App)
        if (!empty($filters['channel']) && $filters['channel'] !== 'ALL') {
            if ($filters['channel'] === 'app') {
                $where[] = "o.order_type IN ('grab', 'shopee')";
            } else {
                $where[] = "o.order_type = ?";
                $params[] = $filters['channel'];
            }
        }

        // Lọc theo trạng thái thanh toán của đơn hàng
        if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
            if ($filters['status'] === 'serving') {
                $where[] = "o.order_status IN ('pending', 'preparing', 'serving')";
            } else {
                $where[] = "o.order_status = ?";
                $params[] = $filters['status'];
            }
        }

        // Lọc theo phương thức thanh toán
        if (!empty($filters['payment']) && $filters['payment'] !== 'ALL') {
            $pm = $filters['payment'];
            if ($pm === 'cash') {
                $where[] = "LOWER(o.payment_method) IN ('cash', 'tien_mat')";
            } elseif ($pm === 'banking') {
                $where[] = "LOWER(o.payment_method) IN ('banking', 'bank', 'qr')";
            } elseif ($pm === 'card_wallet') {
                $where[] = "(LOWER(o.payment_method) IN ('card', 'app', 'wallet', 'momo', 'zalopay') OR o.order_type IN ('grab', 'shopee'))";
            }
        }

        $whereSql = implode(" AND ", $where);

        // Bước 1: Đếm tổng số bản ghi khớp điều kiện để chia trang
        $sqlCount = "SELECT COUNT(*) 
                     FROM orders o 
                     LEFT JOIN customers c ON o.customer_id = c.id 
                     WHERE {$whereSql}";
        $stmtCount = $this->db->prepare($sqlCount);
        $stmtCount->execute($params);
        $totalRecords = (int)$stmtCount->fetchColumn();

        // Bước 2: Tính toán 4 thẻ KPI (Tổng đơn thành công, Doanh thu thuần, AOV, Đơn hủy/Hoàn tiền)
        $sqlKpi = "SELECT 
                    COUNT(o.id) AS total_all,
                    COALESCE(SUM(CASE WHEN o.order_status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_orders,
                    COALESCE(SUM(CASE WHEN o.order_status = 'completed' THEN o.net_amount ELSE 0 END), 0) AS net_revenue,
                    COALESCE(SUM(CASE WHEN o.order_status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_orders
                   FROM orders o
                   LEFT JOIN customers c ON o.customer_id = c.id
                   WHERE {$whereSql}";
        $stmtKpi = $this->db->prepare($sqlKpi);
        $stmtKpi->execute($params);
        $kpiRow = $stmtKpi->fetch(PDO::FETCH_ASSOC);

        $completedCount = (int)($kpiRow['completed_orders'] ?? 0);
        $netRevenue     = (float)($kpiRow['net_revenue'] ?? 0);
        $cancelledCount = (int)($kpiRow['cancelled_orders'] ?? 0);
        $totalAll       = (int)($kpiRow['total_all'] ?? 0);

        $aov        = $completedCount > 0 ? round($netRevenue / $completedCount) : 0;
        $cancelRate = $totalAll > 0 ? round(($cancelledCount / $totalAll) * 100, 1) : 0;

        // Bước 3: Truy vấn danh sách đơn hàng hiển thị lên bảng
        $sqlList = "SELECT o.*,
                           c.customer_name, c.phone AS customer_phone, c.member_rank,
                           t.table_name, a.area_name,
                           u.fullname AS cashier_name, u.username AS cashier_code,
                           (SELECT COALESCE(SUM(od.quantity), 0) FROM order_details od WHERE od.order_id = o.id) AS total_items
                    FROM orders o
                    LEFT JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN tables t ON o.table_id = t.id
                    LEFT JOIN areas a ON t.area_id = a.id
                    LEFT JOIN users u ON o.user_id = u.id
                    WHERE {$whereSql}
                    ORDER BY o.created_at DESC, o.id DESC
                    LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmtList = $this->db->prepare($sqlList);
        $stmtList->execute($params);

        return [
            'orders'        => $stmtList->fetchAll(PDO::FETCH_ASSOC),
            'total_records' => $totalRecords,
            'kpi'           => [
                'completed_orders' => $completedCount,
                'net_revenue'      => $netRevenue,
                'aov'              => $aov,
                'cancelled_orders' => $cancelledCount,
                'cancel_rate'      => $cancelRate
            ]
        ];
    }

    // Lấy dữ liệu chi tiết một hóa đơn (Khách hàng, danh sách món, tính tiền, lịch sử thao tác)
    public function getOrderDetail($orderId) {
        $sql = "SELECT o.*,
                       c.customer_name, c.phone AS customer_phone, c.member_rank,
                       t.table_name, a.area_name,
                       u.fullname AS cashier_name, u.username AS cashier_code,
                       s.shift_name
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN tables t ON o.table_id = t.id
                LEFT JOIN areas a ON t.area_id = a.id
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN shifts s ON o.shift_id = s.id
                WHERE o.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) return null;

        // Lấy các món đã gọi trong order_details và giải mã JSON topping/đường/đá
        $stmtItems = $this->db->prepare("SELECT od.*, p.product_name, p.product_code 
                                         FROM order_details od 
                                         LEFT JOIN products p ON od.product_id = p.id 
                                         WHERE od.order_id = ? ORDER BY od.id ASC");
        $stmtItems->execute([$orderId]);
        $rawItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $order['items'] = array_map(function($item) {
            $opts = !empty($item['options_json']) ? json_decode($item['options_json'], true) : [];
            return [
                'product_code' => $item['product_code'] ?? '',
                'product_name' => $item['product_name'] ?? 'Sản phẩm',
                'quantity'     => (int)$item['quantity'],
                'unit_price'   => (float)$item['unit_price'],
                'subtotal'     => (float)$item['subtotal'],
                'size'         => $opts['size'] ?? '',
                'sugar'        => $opts['sugar'] ?? '',
                'ice'          => $opts['ice'] ?? '',
                'toppings'     => $opts['toppings'] ?? [],
                'item_note'    => $item['item_note'] ?? ''
            ];
        }, $rawItems);

        // Khởi tạo dữ liệu Audit Log (Nếu đơn cũ chưa có log JSON thì dựng lại từ thông tin tạo đơn)
        $logs = !empty($order['audit_log']) ? json_decode($order['audit_log'], true) : [];
        if (!is_array($logs) || empty($logs)) {
            $staffLabel = $order['cashier_name'] ?: ($order['cashier_code'] ?: 'NV01');
            $createdTime = date('H:i', strtotime($order['created_at']));
            $logs = [
                ['time' => $createdTime, 'actor' => $staffLabel, 'action' => 'Khởi tạo giao dịch tại quầy POS'],
                ['time' => $createdTime, 'actor' => $staffLabel, 'action' => 'Chuyển phiếu chế biến xuống quầy pha chế']
            ];
            if ($order['order_status'] === 'completed') {
                $pmLabel = $this->getPaymentMethodName($order['payment_method'], $order['order_type']);
                $logs[] = ['time' => $createdTime, 'actor' => $staffLabel, 'action' => "Hoàn tất thanh toán ({$pmLabel})"];
            } elseif ($order['order_status'] === 'cancelled') {
                $reason = $order['cancel_reason'] ?: 'Hủy giao dịch';
                $logs[] = ['time' => $createdTime, 'actor' => 'Quản trị viên', 'action' => "Hủy hóa đơn & hoàn tiền — Lý do: {$reason}"];
            }
        }
        $order['parsed_audit_log'] = $logs;

        return $order;
    }

    // Nghiệp vụ Hủy hóa đơn / Hoàn tiền: Cập nhật trạng thái, hoàn tồn kho và điều chỉnh lại điểm thành viên
    public function cancelAndRefundOrder($orderId, $reason, $actorName = 'Quản trị viên') {
        try {
            $this->db->beginTransaction();

            // Khóa dòng dữ liệu đơn hàng để tránh xung đột khi nhiều thao tác cùng lúc
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Không tìm thấy dữ liệu hóa đơn trên hệ thống.'];
            }
            if ($order['order_status'] === 'cancelled') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Hóa đơn này đã được hủy và hoàn tiền trước đó.'];
            }

            // Ghi nhận dấu vết hủy đơn vào Audit Log
            $existingLogs = !empty($order['audit_log']) ? json_decode($order['audit_log'], true) : [];
            if (!is_array($existingLogs)) $existingLogs = [];
            if (empty($existingLogs)) {
                $existingLogs[] = [
                    'time'   => date('H:i', strtotime($order['created_at'])),
                    'actor'  => 'Thu ngân',
                    'action' => 'Khởi tạo & thanh toán hóa đơn tại quầy POS'
                ];
            }
            $existingLogs[] = [
                'time'   => date('H:i d/m'),
                'actor'  => $actorName,
                'action' => "Xác nhận hủy hóa đơn & hoàn tiền — Lý do: {$reason}"
            ];

            // Bước 1: Cập nhật trạng thái đơn sang cancelled
            $stmtUpd = $this->db->prepare("UPDATE orders SET order_status = 'cancelled', cancel_reason = ?, audit_log = ? WHERE id = ?");
            $stmtUpd->execute([$reason, json_encode($existingLogs, JSON_UNESCAPED_UNICODE), $orderId]);

            // Bước 2: Hoàn trả tồn kho (Sản phẩm đóng gói trừ thẳng hoặc nguyên liệu theo công thức pha chế)
            $stmtDetails = $this->db->prepare("SELECT od.*, p.manage_stock 
                                               FROM order_details od 
                                               LEFT JOIN products p ON od.product_id = p.id 
                                               WHERE od.order_id = ?");
            $stmtDetails->execute([$orderId]);
            $details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

            $stmtRestoreProd = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $stmtGetRecipes  = $this->db->prepare("SELECT ingredient_id, quantity_required FROM product_recipes WHERE product_id = ?");
            $stmtRestoreIng  = $this->db->prepare("UPDATE ingredients SET stock_quantity = stock_quantity + ? WHERE id = ?");

            foreach ($details as $d) {
                if (strpos($d['item_note'] ?? '', '[QUÀ TẶNG') !== false) continue;

                $qty = (int)$d['quantity'];
                $pid = (int)$d['product_id'];

                if ((int)($d['manage_stock'] ?? 0) === 1) {
                    $stmtRestoreProd->execute([$qty, $pid]);
                } else {
                    $opts = !empty($d['options_json']) ? json_decode($d['options_json'], true) : [];
                    $sizeStr = strtoupper(trim((string)($opts['size'] ?? '')));
                    $multiplier = ($sizeStr === 'L' || strpos($sizeStr, 'SIZE L') !== false) ? 1.3 : 1.0;

                    $stmtGetRecipes->execute([$pid]);
                    foreach ($stmtGetRecipes->fetchAll(PDO::FETCH_ASSOC) as $rec) {
                        $restoreQty = round((float)$rec['quantity_required'] * $qty * $multiplier, 3);
                        if ($restoreQty > 0) {
                            $stmtRestoreIng->execute([$restoreQty, (int)$rec['ingredient_id']]);
                        }
                    }
                }
            }

            // Bước 3: Điều chỉnh lại điểm thưởng & doanh số tích lũy nếu hóa đơn có gắn thẻ thành viên
            if (!empty($order['customer_id'])) {
                $cid = (int)$order['customer_id'];
                $earned = (int)($order['points_earned'] ?? 0);
                $redeemed = (int)($order['points_redeemed'] ?? 0);
                $netPointDelta = $redeemed - $earned;
                $finalAmt = (float)($order['final_amount'] ?? 0);

                $stmtCust = $this->db->prepare("UPDATE customers SET 
                    total_points = GREATEST(0, total_points + ?),
                    maintained_points = GREATEST(0, maintained_points - ?),
                    lifetime_spend = GREATEST(0, lifetime_spend - ?)
                    WHERE id = ?");
                $stmtCust->execute([$netPointDelta, max(0, $earned), $finalAmt, $cid]);

                if ($netPointDelta !== 0) {
                    $stmtBal = $this->db->prepare("SELECT total_points FROM customers WHERE id = ?");
                    $stmtBal->execute([$cid]);
                    $balAfter = (int)$stmtBal->fetchColumn();

                    $stmtLog = $this->db->prepare("INSERT INTO point_transactions (customer_id, order_id, type, points_change, balance_after, description, created_by) 
                                                   VALUES (?, ?, 'adjust', ?, ?, ?, ?)");
                    $stmtLog->execute([
                        $cid,
                        $orderId,
                        $netPointDelta,
                        $balAfter,
                        "Điều chỉnh điểm do hủy hóa đơn {$order['order_code']} ({$reason})",
                        $actorName
                    ]);
                }
            }

            $this->db->commit();
            return [
                'success' => true,
                'message' => "Đã hủy hóa đơn {$order['order_code']}, hoàn trả tồn kho và cập nhật điểm thành viên thành công."
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Không thể hoàn tất thao tác hủy đơn: ' . $e->getMessage()];
        }
    }

    // Lấy toàn bộ danh sách đơn theo bộ lọc để xuất file báo cáo Excel/CSV
    public function getOrdersForExport($filters = []) {
        $res = $this->getOrdersWithStats($filters, 5000, 0);
        return $res['orders'] ?? [];
    }

    // Chuẩn hóa tên phương thức thanh toán sang tiếng Việt thương mại
    private function getPaymentMethodName($method, $orderType) {
        $pm = strtolower($method ?? 'cash');
        if (in_array($orderType, ['grab', 'shopee']) || $pm === 'app') return 'Ví / Sàn ứng dụng';
        if (in_array($pm, ['banking', 'bank', 'qr'])) return 'Chuyển khoản / QR Code';
        if (in_array($pm, ['card', 'wallet', 'momo', 'zalopay'])) return 'Thẻ / Ví điện tử';
        return 'Tiền mặt';
    }
}
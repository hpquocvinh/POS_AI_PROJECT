<?php
// app/Models/OrderModel.php

class OrderModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // 1. Chỉ lấy Bàn Tại Chỗ (loại bỏ Mang Về & Grab khỏi sơ đồ bàn)
    public function getAreasWithTables() {
        $stmtAreas = $this->db->query("SELECT id, area_name AS name FROM areas ORDER BY id ASC");
        $areas = $stmtAreas->fetchAll(PDO::FETCH_ASSOC);

        if (empty($areas)) {
            return [];
        }

        foreach ($areas as $key => &$area) {
            // Lọc chỉ lấy bàn ăn tại chỗ (table_type = 'dine_in' hoặc không chứa chữ Mang về/Grab)
            $stmtTables = $this->db->prepare("SELECT id, table_name AS name, status FROM tables WHERE area_id = ? AND (table_type = 'dine_in' OR table_type IS NULL OR table_type = '') ORDER BY id ASC");
            $stmtTables->execute([$area['id']]);
            $rawTables = $stmtTables->fetchAll(PDO::FETCH_ASSOC);

            // Bỏ các bàn có tên Mang Về hoặc Đơn Grab nếu lỡ sót
            $filteredTables = array_filter($rawTables, function($t) {
                $nameLower = mb_strtolower($t['name'], 'UTF-8');
                return strpos($nameLower, 'mang về') === false && strpos($nameLower, 'grab') === false;
            });

            $area['tables'] = array_map(function($t) {
                $statusStr = 'available';
                if ($t['status'] == 1) {
                    $statusStr = 'occupied';
                } else if ($t['status'] == 2) {
                    $statusStr = 'reserved';
                }

                return [
                    'id'     => $t['id'],
                    'name'   => $t['name'],
                    'status' => $statusStr
                ];
            }, array_values($filteredTables));
        }

        return $areas;
    }

    // 2. Lấy Danh mục
    public function getCategories() {
        $stmt = $this->db->query("SELECT id, category_name AS name FROM categories ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Lấy Sản phẩm
    public function getProducts() {
        $stmt = $this->db->query("SELECT id, category_id, product_name AS name, price, image AS image_url FROM products WHERE status = 1 ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
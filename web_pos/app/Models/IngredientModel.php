<?php
class IngredientModel {
    private $db;

    public function __construct() {
        $dbInstance = Database::getInstance();
        if (method_exists($dbInstance, 'getConnection')) {
            $this->db = $dbInstance->getConnection();
        } else {
            $this->db = $dbInstance;
        }
        $this->ensureSchema();
    }

    /**
     * Đảm bảo cột nearest_expiry_date và bảng thẻ kho ingredient_logs luôn tồn tại
     */
    private function ensureSchema() {
        try {
            $colCheck = $this->db->query("SHOW COLUMNS FROM ingredients LIKE 'nearest_expiry_date'");
            if ($colCheck && $colCheck->rowCount() === 0) {
                $this->db->exec("ALTER TABLE ingredients ADD COLUMN nearest_expiry_date DATE NULL AFTER min_stock");
            }

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS ingredient_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ingredient_id INT NOT NULL,
                    action_type VARCHAR(30) NOT NULL DEFAULT 'import',
                    change_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
                    before_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
                    after_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
                    note VARCHAR(255) NULL,
                    created_by VARCHAR(100) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ing_id (ingredient_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Exception $e) {
            // Bỏ qua nếu quyền DB hạn chế
        }
    }

    /**
     * Ghi 1 dòng biến động vào Thẻ kho (ingredient_logs)
     */
    public function addLog($ingredientId, $actionType, $changeQty, $beforeQty, $afterQty, $note = '', $createdBy = 'Hệ thống') {
        try {
            $sql = "INSERT INTO ingredient_logs (ingredient_id, action_type, change_qty, before_qty, after_qty, note, created_by)
                    VALUES (:ingredient_id, :action_type, :change_qty, :before_qty, :after_qty, :note, :created_by)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':ingredient_id' => $ingredientId,
                ':action_type'   => $actionType,
                ':change_qty'    => $changeQty,
                ':before_qty'    => $beforeQty,
                ':after_qty'     => $afterQty,
                ':note'          => $note,
                ':created_by'    => $createdBy
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Lấy lịch sử Thẻ kho của 1 nguyên liệu (phục vụ API getIngredientLogs)
     */
    public function getLogsByIngredient($ingredientId, $limit = 50) {
        try {
            $limit = max(1, min(200, (int)$limit));
            $sql = "SELECT * FROM ingredient_logs 
                    WHERE ingredient_id = :id 
                    ORDER BY id DESC 
                    LIMIT {$limit}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $ingredientId]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            return [];
        }
    }

    // Lấy toàn bộ nguyên liệu kèm tên nhóm nguyên liệu
    public function getAllIngredients($categoryId = null, $keyword = '') {
        $sql = "SELECT i.*, c.category_name 
                FROM ingredients i
                LEFT JOIN categories c ON i.category_id = c.id";
        
        $conditions = [];
        $params = [];

        if (!empty($categoryId)) {
            $conditions[] = "i.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        if (!empty($keyword)) {
            $conditions[] = "(i.ingredient_name LIKE :keyword OR i.ingredient_code LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY (i.stock_quantity <= i.min_stock) DESC, i.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM ingredients WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Kiểm tra trùng mã nguyên liệu
    public function checkCodeExists($code, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM ingredients WHERE LOWER(ingredient_code) = LOWER(:code) AND id != :id");
            $stmt->execute([':code' => trim($code), ':id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM ingredients WHERE LOWER(ingredient_code) = LOWER(:code)");
            $stmt->execute([':code' => trim($code)]);
        }
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        return $row->count > 0;
    }

    // Kiểm tra trùng tên nguyên liệu
    public function checkNameExists($name, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM ingredients WHERE LOWER(ingredient_name) = LOWER(:name) AND id != :id");
            $stmt->execute([':name' => trim($name), ':id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM ingredients WHERE LOWER(ingredient_name) = LOWER(:name)");
            $stmt->execute([':name' => trim($name)]);
        }
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        return $row->count > 0;
    }

    // Thêm nguyên liệu mới (lưu đầy đủ HSD FEFO + ghi Thẻ kho khởi tạo)
    public function create($data, $createdBy = 'Quản Trị Viên') {
        $sql = "INSERT INTO ingredients (
                    category_id, ingredient_code, ingredient_name, unit, 
                    purchase_unit, conversion_rate, cost_price, stock_quantity, min_stock, nearest_expiry_date, status
                ) VALUES (
                    :category_id, :ingredient_code, :ingredient_name, :unit, 
                    :purchase_unit, :conversion_rate, :cost_price, :stock_quantity, :min_stock, :nearest_expiry_date, 1
                )";
        $stmt = $this->db->prepare($sql);
        $initStock = (float)($data['stock_quantity'] ?? 0);
        $expiryDate = !empty($data['nearest_expiry_date']) ? $data['nearest_expiry_date'] : null;

        $ok = $stmt->execute([
            ':category_id'         => $data['category_id'],
            ':ingredient_code'     => $data['ingredient_code'],
            ':ingredient_name'     => $data['ingredient_name'],
            ':unit'                => $data['unit'],
            ':purchase_unit'       => !empty($data['purchase_unit']) ? $data['purchase_unit'] : null,
            ':conversion_rate'     => max(1, (float)($data['conversion_rate'] ?? 1)),
            ':cost_price'          => $data['cost_price'] ?? 0,
            ':stock_quantity'      => $initStock,
            ':min_stock'           => $data['min_stock'] ?? 0,
            ':nearest_expiry_date' => $expiryDate
        ]);

        if ($ok) {
            $newId = (int)$this->db->lastInsertId();
            if ($newId > 0 && $initStock > 0) {
                $note = "Khởi tạo tồn đầu kỳ" . ($expiryDate ? " (HSD: " . date('d/m/Y', strtotime($expiryDate)) . ")" : "");
                $this->addLog($newId, 'init', $initStock, 0, $initStock, $note, $createdBy);
            }
        }
        return $ok;
    }

    // Nhập thêm kho (cộng dồn số lượng, cập nhật giá nhập, cập nhật HSD FEFO & ghi Thẻ kho)
    public function importStock($id, $addBaseQuantity, $newCostPrice = null, $expiryDate = '', $note = '', $createdBy = 'Quản Trị Viên') {
        $ing = $this->getById($id);
        if (!$ing) return false;

        $beforeQty = (float)$ing->stock_quantity;
        $afterQty  = $beforeQty + (float)$addBaseQuantity;

        // Xử lý ngày HSD FEFO:
        // - Nếu kho đang trống (<= 0) hoặc chưa có HSD: lấy ngày HSD mới nhập
        // - Nếu kho còn hàng và đã có HSD cũ: ưu tiên giữ ngày HSD gần hơn (FEFO)
        $finalExpiry = $ing->nearest_expiry_date ?? null;
        if (!empty($expiryDate)) {
            if ($beforeQty <= 0 || empty($finalExpiry)) {
                $finalExpiry = $expiryDate;
            } else {
                $finalExpiry = (strtotime($expiryDate) < strtotime($finalExpiry)) ? $expiryDate : $finalExpiry;
            }
            $note .= ($note ? " | " : "") . "HSD lô nhập: " . date('d/m/Y', strtotime($expiryDate));
        }

        if ($newCostPrice !== null && $newCostPrice > 0) {
            $sql = "UPDATE ingredients 
                    SET stock_quantity = :after_qty, 
                        cost_price = :cost,
                        nearest_expiry_date = :expiry
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([
                ':after_qty' => $afterQty,
                ':cost'      => $newCostPrice,
                ':expiry'    => $finalExpiry,
                ':id'        => $id
            ]);
        } else {
            $sql = "UPDATE ingredients 
                    SET stock_quantity = :after_qty,
                        nearest_expiry_date = :expiry
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([
                ':after_qty' => $afterQty,
                ':expiry'    => $finalExpiry,
                ':id'        => $id
            ]);
        }

        if ($ok) {
            $this->addLog($id, 'import', (float)$addBaseQuantity, $beforeQty, $afterQty, $note ?: 'Nhập thêm kho', $createdBy);
        }
        return $ok;
    }

    // Kiểm kê / Điều chỉnh tồn kho thực tế + ghi Thẻ kho
    public function auditStock($id, $actualQuantity, $reason = '', $createdBy = 'Quản Trị Viên') {
        $ing = $this->getById($id);
        if (!$ing) return false;

        $beforeQty = (float)$ing->stock_quantity;
        $afterQty  = (float)$actualQuantity;
        $diffQty   = $afterQty - $beforeQty;

        $sql = "UPDATE ingredients SET stock_quantity = :qty WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([':qty' => $afterQty, ':id' => $id]);

        if ($ok) {
            $logNote = "Kiểm kê thực tế" . ($reason ? ": {$reason}" : "");
            $this->addLog($id, 'audit', $diffQty, $beforeQty, $afterQty, $logNote, $createdBy);
        }
        return $ok;
    }

    // Cập nhật thông tin nguyên liệu (bao gồm đơn vị tính, quy đổi & HSD FEFO)
    public function update($id, $data) {
        $sql = "UPDATE ingredients 
                SET category_id = :category_id, 
                    ingredient_code = :ingredient_code, 
                    ingredient_name = :ingredient_name, 
                    unit = :unit, 
                    purchase_unit = :purchase_unit,
                    conversion_rate = :conversion_rate,
                    cost_price = :cost_price, 
                    min_stock = :min_stock,
                    nearest_expiry_date = :nearest_expiry_date
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':category_id'         => $data['category_id'],
            ':ingredient_code'     => $data['ingredient_code'],
            ':ingredient_name'     => $data['ingredient_name'],
            ':unit'                => $data['unit'],
            ':purchase_unit'       => !empty($data['purchase_unit']) ? $data['purchase_unit'] : null,
            ':conversion_rate'     => max(1, (float)($data['conversion_rate'] ?? 1)),
            ':cost_price'          => $data['cost_price'] ?? 0,
            ':min_stock'           => $data['min_stock'] ?? 0,
            ':nearest_expiry_date' => !empty($data['nearest_expiry_date']) ? $data['nearest_expiry_date'] : null,
            ':id'                  => $id
        ]);
    }

    // Xóa nguyên liệu (và dọn log thẻ kho đi kèm)
    public function delete($id) {
        try {
            $delLogs = $this->db->prepare("DELETE FROM ingredient_logs WHERE ingredient_id = :id");
            $delLogs->execute([':id' => $id]);
        } catch (Exception $e) {}

        $stmt = $this->db->prepare("DELETE FROM ingredients WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
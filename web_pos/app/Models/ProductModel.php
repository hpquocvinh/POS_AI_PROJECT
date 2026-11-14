<?php
class ProductModel {
    private $db;

    public function __construct() {
        $dbInstance = Database::getInstance();
        if (method_exists($dbInstance, 'getConnection')) {
            $this->db = $dbInstance->getConnection();
        } else {
            $this->db = $dbInstance;
        }
    }

    public function getAllProducts() {
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getProductById($id) {
        $sql = "SELECT * FROM products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function checkCodeExists($code, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM products WHERE LOWER(product_code) = LOWER(:code) AND id != :id");
            $stmt->execute([':code' => trim($code), ':id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM products WHERE LOWER(product_code) = LOWER(:code)");
            $stmt->execute([':code' => trim($code)]);
        }
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        return $row->count > 0;
    }

    public function checkNameExists($name, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM products WHERE LOWER(product_name) = LOWER(:name) AND id != :id");
            $stmt->execute([':name' => trim($name), ':id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM products WHERE LOWER(product_name) = LOWER(:name)");
            $stmt->execute([':name' => trim($name)]);
        }
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        return $row->count > 0;
    }

    public function insertProduct($data) {
        $sql = "INSERT INTO products (
                    category_id, product_code, product_name, price, cost_price, 
                    stock_quantity, min_stock, expiry_date, manage_stock, modifiers_config, image, status
                ) VALUES (
                    :category_id, :product_code, :product_name, :price, :cost_price, 
                    :stock_quantity, :min_stock, :expiry_date, :manage_stock, :modifiers_config, :image, :status
                )";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($data)) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function updateProduct($data) {
        $sql = "UPDATE products 
                SET category_id = :category_id,
                    product_code = :product_code,
                    product_name = :product_name,
                    price = :price,
                    cost_price = :cost_price,
                    stock_quantity = :stock_quantity,
                    min_stock = :min_stock,
                    expiry_date = :expiry_date,
                    manage_stock = :manage_stock,
                    modifiers_config = :modifiers_config,
                    image = :image,
                    status = :status
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function deleteProduct($id) {
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // ==================== QUẢN LÝ CÔNG THỨC ĐỊNH LƯỢNG (BOM) ====================

    public function getProductRecipes($productId) {
        $sql = "SELECT pr.id, pr.product_id, pr.ingredient_id, pr.quantity_required,
                       i.ingredient_code, i.ingredient_name, i.unit, i.cost_price, i.conversion_rate,
                       (i.cost_price / GREATEST(i.conversion_rate, 1)) AS unit_cost
                FROM product_recipes pr
                INNER JOIN ingredients i ON pr.ingredient_id = i.id
                WHERE pr.product_id = :product_id
                ORDER BY pr.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Lưu công thức BOM cho sản phẩm:
     * - Bảo toàn dữ liệu: Nếu $items không phải mảng hợp lệ thì không xóa nhầm BOM cũ
     * - Hỗ trợ cập nhật lại Giá Cost tự động dựa trên giá nguyên liệu & tỷ lệ quy đổi
     */
    public function saveProductRecipes($productId, $items, $autoUpdateCost = true, $allowClearAll = true) {
        if (!is_array($items)) {
            return ['success' => false, 'calculated_cost' => 0];
        }

        // Lọc trước danh sách item hợp lệ trước khi chạm vào DB
        $validItems = [];
        foreach ($items as $item) {
            $ingId = (int)($item['ingredient_id'] ?? 0);
            $qty   = (float)($item['quantity_required'] ?? 0);
            if ($ingId > 0 && $qty > 0) {
                if (isset($validItems[$ingId])) {
                    $validItems[$ingId] += $qty;
                } else {
                    $validItems[$ingId] = $qty;
                }
            }
        }

        // Nếu không cho phép xóa trắng mà danh sách rỗng -> giữ nguyên BOM hiện có
        if (empty($validItems) && !$allowClearAll) {
            return ['success' => true, 'calculated_cost' => 0, 'preserved' => true];
        }

        try {
            $this->db->beginTransaction();

            $stmtDel = $this->db->prepare("DELETE FROM product_recipes WHERE product_id = :product_id");
            $stmtDel->execute([':product_id' => $productId]);

            if (!empty($validItems)) {
                $stmtIns = $this->db->prepare("INSERT INTO product_recipes (product_id, ingredient_id, quantity_required) VALUES (:product_id, :ingredient_id, :qty)");
                foreach ($validItems as $ingId => $qty) {
                    $stmtIns->execute([
                        ':product_id'    => $productId,
                        ':ingredient_id' => $ingId,
                        ':qty'           => $qty
                    ]);
                }
            }

            $calculatedCost = 0;
            if ($autoUpdateCost) {
                $stmtCalc = $this->db->prepare("
                    SELECT COALESCE(SUM(pr.quantity_required * (i.cost_price / GREATEST(i.conversion_rate, 1))), 0) AS total_cost
                    FROM product_recipes pr
                    INNER JOIN ingredients i ON pr.ingredient_id = i.id
                    WHERE pr.product_id = :product_id
                ");
                $stmtCalc->execute([':product_id' => $productId]);
                $row = $stmtCalc->fetch(PDO::FETCH_OBJ);
                $calculatedCost = round((float)($row->total_cost ?? 0));

                $stmtUpd = $this->db->prepare("UPDATE products SET cost_price = :cost WHERE id = :id");
                $stmtUpd->execute([':cost' => $calculatedCost, ':id' => $productId]);
            }

            $this->db->commit();
            return ['success' => true, 'calculated_cost' => $calculatedCost];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'calculated_cost' => 0];
        }
    }

    /**
     * Tự động tính lại Giá Cost cho tất cả món bán pha chế đang sử dụng nguyên liệu $ingredientId
     */
    public function syncProductCostsByIngredient($ingredientId) {
        try {
            $stmt = $this->db->prepare("SELECT DISTINCT product_id FROM product_recipes WHERE ingredient_id = :ing_id");
            $stmt->execute([':ing_id' => $ingredientId]);
            $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($productIds)) return 0;

            $stmtCalc = $this->db->prepare("
                SELECT COALESCE(SUM(pr.quantity_required * (i.cost_price / GREATEST(i.conversion_rate, 1))), 0) AS total_cost
                FROM product_recipes pr
                INNER JOIN ingredients i ON pr.ingredient_id = i.id
                WHERE pr.product_id = :product_id
            ");
            $stmtUpd = $this->db->prepare("UPDATE products SET cost_price = :cost WHERE id = :id AND manage_stock = 0");

            $updatedCount = 0;
            foreach ($productIds as $pid) {
                $stmtCalc->execute([':product_id' => $pid]);
                $row = $stmtCalc->fetch(PDO::FETCH_OBJ);
                $newCost = round((float)($row->total_cost ?? 0));
                if ($stmtUpd->execute([':cost' => $newCost, ':id' => $pid])) {
                    $updatedCount++;
                }
            }
            return $updatedCount;
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getRecipeCountsByProduct() {
        try {
            $stmt = $this->db->query("SELECT product_id, COUNT(*) as total FROM product_recipes GROUP BY product_id");
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
            $map = [];
            foreach ($rows as $r) {
                $map[$r->product_id] = (int)$r->total;
            }
            return $map;
        } catch (Exception $e) {
            return [];
        }
    }
}
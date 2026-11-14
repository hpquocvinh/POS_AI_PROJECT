<?php
class CategoryModel {
    private $db;

    public function __construct() {
        $dbInstance = Database::getInstance();
        if (method_exists($dbInstance, 'getConnection')) {
            $this->db = $dbInstance->getConnection();
        } else {
            $this->db = $dbInstance;
        }
    }

    // Lấy tất cả danh mục kèm số lượng sản phẩm / nguyên liệu đang thuộc danh mục đó
    public function getAllCategories($type = null, $onlyActive = false) {
        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count,
                (SELECT COUNT(*) FROM ingredients i WHERE i.category_id = c.id) AS ingredient_count
                FROM categories c";
        
        $conditions = [];
        $params = [];

        if ($type) {
            $conditions[] = "c.type = :type";
            $params[':type'] = $type;
        }
        if ($onlyActive) {
            $conditions[] = "c.status = 1";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY c.type ASC, c.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    // chỉ lấy món bán
    public function getProductCategories($onlyActive = false) {
        return $this->getAllCategories('product', $onlyActive);
    }
    // chỉ lấy nhóm nguyên liệu
    public function getIngredientCategories($onlyActive = false) {
        return $this->getAllCategories('ingredient', $onlyActive);
    }

    // Lấy 1 danh mục theo ID
    public function getCategoryById($id) {
        $sql = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Kiểm tra trùng tên danh mục trong cùng phân loại
    public function checkNameExists($name, $type, $excludeId = null) {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM categories WHERE LOWER(category_name) = LOWER(:name) AND type = :type AND id != :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':name' => trim($name), ':type' => $type, ':id' => $excludeId]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM categories WHERE LOWER(category_name) = LOWER(:name) AND type = :type";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':name' => trim($name), ':type' => $type]);
        }
        return $stmt->fetch(PDO::FETCH_OBJ)->count > 0;
    }

    // Kiểm tra danh mục có đang chứa sản phẩm hoặc nguyên liệu không trước khi xóa
    public function hasRelatedItems($id) {
        $stmt1 = $this->db->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = :id");
        $stmt1->execute([':id' => $id]);
        if ($stmt1->fetch(PDO::FETCH_OBJ)->count > 0) return true;

        $stmt2 = $this->db->prepare("SELECT COUNT(*) as count FROM ingredients WHERE category_id = :id");
        $stmt2->execute([':id' => $id]);
        if ($stmt2->fetch(PDO::FETCH_OBJ)->count > 0) return true;

        return false;
    }

    // Thêm danh mục mới
    public function insertCategory($data) {
        $sql = "INSERT INTO categories (category_name, description, type, status) 
                VALUES (:category_name, :description, :type, :status)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    // Cập nhật danh mục
    public function updateCategory($data) {
        $sql = "UPDATE categories 
                SET category_name = :category_name,
                    description = :description,
                    type = :type,
                    status = :status
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    // Xóa danh mục
    public function deleteCategory($id) {
        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
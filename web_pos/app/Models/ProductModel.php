<?php
class ProductModel {
    private $db;

    public function __construct() {
        // Lấy đối tượng kết nối CSDL (Xử lý linh hoạt cả 2 trường hợp lớp Database)
        $dbInstance = Database::getInstance();
        if (method_exists($dbInstance, 'getConnection')) {
            $this->db = $dbInstance->getConnection();
        } else {
            $this->db = $dbInstance;
        }
    }

    // Lấy tất cả sản phẩm kèm tên danh mục
    public function getAllProducts() {
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Lấy chi tiết 1 sản phẩm theo ID
    public function getProductById($id) {
        $sql = "SELECT * FROM products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Kiểm tra Mã SKU đã tồn tại chưa (không phân biệt hoa/thường, hỗ trợ loại trừ sản phẩm đang sửa)
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

    // Kiểm tra Tên sản phẩm đã tồn tại chưa (không phân biệt hoa/thường, hỗ trợ loại trừ sản phẩm đang sửa)
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

    // Thêm sản phẩm mới
    public function insertProduct($data) {
        $sql = "INSERT INTO products (category_id, product_code, product_name, price, cost_price, stock_quantity, image, status) 
                VALUES (:category_id, :product_code, :product_name, :price, :cost_price, :stock_quantity, :image, :status)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    // Cập nhật thông tin sản phẩm
    public function updateProduct($data) {
        $sql = "UPDATE products 
                SET category_id = :category_id,
                    product_code = :product_code,
                    product_name = :product_name,
                    price = :price,
                    cost_price = :cost_price,
                    stock_quantity = :stock_quantity,
                    image = :image,
                    status = :status
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    // Xóa sản phẩm theo ID
    public function deleteProduct($id) {
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
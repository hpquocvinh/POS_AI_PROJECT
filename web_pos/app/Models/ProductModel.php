<?php
class ProductModel {
    private $conn;

    // Nhận kết nối PDO từ ngoài truyền vào
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // Lấy tất cả sản phẩm kèm tên danh mục (JOIN 2 bảng)
    public function getAllProducts() {
        // Dùng chuẩn 3NF: JOIN bảng products với categories qua category_id
        $sql = "SELECT p.id, p.product_code, p.product_name, p.price, p.cost_price, 
                       p.stock_quantity, p.image, p.status, c.category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.id DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        // Trả về mảng dữ liệu dạng Object
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    // Thêm sản phẩm mới vào CSDL
    public function insertProduct($data) {
        $sql = "INSERT INTO products (category_id, product_code, product_name, price, cost_price, stock_quantity, image, status) 
                VALUES (:category_id, :product_code, :product_name, :price, :cost_price, :stock_quantity, :image, :status)";
        
        $stmt = $this->conn->prepare($sql);
        // Bind mảng data trực tiếp vào câu lệnh PDO để chống SQL Injection
        return $stmt->execute($data);
    }
    // Lấy thông tin 1 sản phẩm theo ID để đổ ra form Sửa
    public function getProductById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Cập nhật sản phẩm
    public function updateProduct($data) {
        $sql = "UPDATE products SET 
                category_id = :category_id, product_code = :product_code, 
                product_name = :product_name, price = :price, cost_price = :cost_price, 
                stock_quantity = :stock_quantity, image = :image, status = :status 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }
    // Xóa sản phẩm khỏi CSDL
    public function deleteProduct($id) {
        $stmt = $this->conn->prepare("DELETE FROM products WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
?>
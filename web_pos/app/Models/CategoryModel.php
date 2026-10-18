<?php
class CategoryModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function getAllCategories() {
        $stmt = $this->conn->prepare("SELECT * FROM categories ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getCategoryById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function insertCategory($data) {
        $stmt = $this->conn->prepare("INSERT INTO categories (category_name, description) VALUES (:category_name, :description)");
        return $stmt->execute($data);
    }

    public function updateCategory($data) {
        $stmt = $this->conn->prepare("UPDATE categories SET category_name = :category_name, description = :description WHERE id = :id");
        return $stmt->execute($data);
    }

    public function deleteCategory($id) {
        $stmt = $this->conn->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
?>
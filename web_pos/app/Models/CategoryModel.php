<?php
class CategoryModel {
    private $db;

    public function __construct() {
        // Tự lấy Instance PDO từ class Database dùng chung
        $this->db = Database::getInstance();
    }

    public function getAllCategories() {
        $stmt = $this->db->prepare("SELECT * FROM categories ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getCategoryById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function insertCategory($data) {
        $stmt = $this->db->prepare("INSERT INTO categories (category_name, description) VALUES (:category_name, :description)");
        return $stmt->execute($data);
    }

    public function updateCategory($data) {
        $stmt = $this->db->prepare("UPDATE categories SET category_name = :category_name, description = :description WHERE id = :id");
        return $stmt->execute($data);
    }

    public function deleteCategory($id) {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
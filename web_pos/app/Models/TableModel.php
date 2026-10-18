<?php
class TableModel {
    private $db;

    public function __construct($conn = null) {
        if ($conn !== null) {
            $this->db = $conn;
        } else {
            // Tự động kết nối DB nếu chưa truyền biến connection
            $this->db = new mysqli('localhost', 'root', '', 'pos_ai_db');
            if ($this->db->connect_error) {
                die("Kết nối CSDL thất bại: " . $this->db->connect_error);
            }
            $this->db->set_charset("utf8mb4");
        }
    }

    // Lấy danh sách các Khu vực
    public function getAllAreas() {
        $sql = "SELECT * FROM areas ORDER BY id ASC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Lấy danh sách tất cả các Bàn (Tại chỗ, Mang về, Online App) kèm tên Khu vực
    public function getAllTables() {
        $sql = "SELECT t.*, a.area_name 
                FROM tables t 
                LEFT JOIN areas a ON t.area_id = a.id 
                ORDER BY t.table_type ASC, a.id ASC, t.id ASC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
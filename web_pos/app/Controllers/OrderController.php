<?php
// app/controllers/OrderController.php

require_once __DIR__ . '/../models/OrderModel.php';

class OrderController {
    private $orderModel;

    public function __construct($pdo) {
        $this->orderModel = new OrderModel($pdo);
    }

    // Hiển thị giao diện POS
    public function index() {
        $areas = $this->orderModel->getAreasWithTables();
        $categories = $this->orderModel->getCategories();
        $products = $this->orderModel->getProducts();

        // Load View
        require_once __DIR__ . '/../views/pos/index.php';
    }

    // Endpoint nhận request POST từ AJAX để lưu hóa đơn
    public function checkout() {
        header('Content-Type: application/json');
        
        $rawInput = file_get_contents('php_input');
        $data = json_decode($rawInput, true);

        if (!$data || empty($data['items']) || empty($data['table_id'])) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu đơn hàng không hợp lệ!']);
            exit;
        }

        $tableId = (int)$data['table_id'];
        $items = $data['items'];
        $totalAmount = (float)$data['total_amount'];
        $userId = $_SESSION['user_id'] ?? 1; // Default Admin/Staff ID

        $result = $this->orderModel->createOrder($tableId, $userId, $items, $totalAmount);
        echo json_encode($result);
        exit;
    }
}
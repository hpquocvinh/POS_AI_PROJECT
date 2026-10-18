<?php
require_once __DIR__ . '/../Models/TableModel.php';
require_once __DIR__ . '/../Models/ProductModel.php'; 

class PosController {
    private $tableModel;
    private $productModel;

    public function __construct($conn = null) {
        global $db_connect, $conn;
        $db = $conn ?? $db_connect ?? null;

        $this->tableModel = new TableModel($db);
        $this->productModel = new ProductModel($db);
    }

    public function index() {
        $areas = $this->tableModel->getAllAreas();
        $tables = $this->tableModel->getAllTables();
        
        if (method_exists($this->productModel, 'getAllProducts')) {
            $products = $this->productModel->getAllProducts();
        } else if (method_exists($this->productModel, 'getProducts')) {
            $products = $this->productModel->getProducts();
        } else {
            $products = [];
        }

        $viewsPath = __DIR__ . '/../Views/';
        
        require_once $viewsPath . 'layouts/header.php';
        require_once $viewsPath . 'pos/index.php';
        require_once $viewsPath . 'layouts/footer.php';
    }
}
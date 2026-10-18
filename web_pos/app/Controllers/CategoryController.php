<?php
require_once 'Models/CategoryModel.php';

class CategoryController {
    private $categoryModel;

    public function __construct($dbConnection) {
        $this->categoryModel = new CategoryModel($dbConnection);
    }

    public function index() {
        $categories = $this->categoryModel->getAllCategories();
        require_once 'Views/categories/index.php';
    }

    public function create() {
        require_once 'Views/categories/create.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                ':category_name' => $_POST['category_name'],
                ':description' => $_POST['description']
            ];
            if ($this->categoryModel->insertCategory($data)) {
                header("Location: ?controller=category&action=index");
                exit();
            }
        }
    }

    public function edit() {
        $id = $_GET['id'] ?? 0;
        $category = $this->categoryModel->getCategoryById($id);
        if($category) {
            require_once 'Views/categories/edit.php';
        } else {
            header("Location: ?controller=category&action=index");
        }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                ':id' => $_POST['id'],
                ':category_name' => $_POST['category_name'],
                ':description' => $_POST['description']
            ];
            if ($this->categoryModel->updateCategory($data)) {
                header("Location: ?controller=category&action=index");
                exit();
            }
        }
    }

    public function delete() {
        $id = $_GET['id'] ?? 0;
        if ($id) {
            $this->categoryModel->deleteCategory($id);
        }
        header("Location: ?controller=category&action=index");
        exit();
    }
}
?>
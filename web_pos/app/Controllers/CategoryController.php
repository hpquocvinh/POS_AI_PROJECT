<?php
require_once APP_PATH . '/Models/CategoryModel.php';

class CategoryController extends Controller {
    private $categoryModel;

    public function __construct() {
        $this->categoryModel = $this->model('CategoryModel');
    }

    // Hiển thị danh sách Danh mục món & Nhóm nguyên liệu
    public function index() {
        $categories = $this->categoryModel->getAllCategories();
        $this->view('categories/index', [
            'categories' => $categories
        ]);
    }

    // Xử lý thêm mới (Submit từ Modal)
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category_name = trim($_POST['category_name'] ?? '');
            $description   = trim($_POST['description'] ?? '');
            $type          = in_array($_POST['type'] ?? '', ['product', 'ingredient']) ? $_POST['type'] : 'product';
            $status        = (int)($_POST['status'] ?? 1);

            if (empty($category_name)) {
                $_SESSION['error'] = "Tên danh mục không được để trống!";
                header("Location: " . BASE_URL . "/category");
                exit;
            }

            if ($this->categoryModel->checkNameExists($category_name, $type)) {
                $label = ($type === 'ingredient') ? 'Nhóm nguyên liệu' : 'Danh mục món';
                $_SESSION['error'] = "{$label} '{$category_name}' đã tồn tại!";
                header("Location: " . BASE_URL . "/category");
                exit;
            }

            $data = [
                ':category_name' => $category_name,
                ':description'   => $description,
                ':type'          => $type,
                ':status'        => $status
            ];

            if ($this->categoryModel->insertCategory($data)) {
                $_SESSION['success'] = "Thêm danh mục '{$category_name}' thành công!";
            } else {
                $_SESSION['error'] = "Có lỗi xảy ra khi thêm danh mục!";
            }
        }
        header("Location: " . BASE_URL . "/category");
        exit;
    }

    // Xử lý cập nhật (Submit từ Modal Sửa)
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id            = (int)($_POST['id'] ?? 0);
            $category_name = trim($_POST['category_name'] ?? '');
            $description   = trim($_POST['description'] ?? '');
            $type          = in_array($_POST['type'] ?? '', ['product', 'ingredient']) ? $_POST['type'] : 'product';
            $status        = (int)($_POST['status'] ?? 1);

            if (!$id || empty($category_name)) {
                $_SESSION['error'] = "Dữ liệu cập nhật không hợp lệ!";
                header("Location: " . BASE_URL . "/category");
                exit;
            }

            if ($this->categoryModel->checkNameExists($category_name, $type, $id)) {
                $_SESSION['error'] = "Tên '{$category_name}' đã trùng với danh mục khác!";
                header("Location: " . BASE_URL . "/category");
                exit;
            }

            $data = [
                ':id'            => $id,
                ':category_name' => $category_name,
                ':description'   => $description,
                ':type'          => $type,
                ':status'        => $status
            ];

            if ($this->categoryModel->updateCategory($data)) {
                $_SESSION['success'] = "Cập nhật danh mục thành công!";
            } else {
                $_SESSION['error'] = "Cập nhật danh mục thất bại!";
            }
        }
        header("Location: " . BASE_URL . "/category");
        exit;
    }

    // Xử lý xóa danh mục
    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            if ($this->categoryModel->hasRelatedItems($id)) {
                $_SESSION['error'] = "Không thể xóa! Danh mục này đang chứa sản phẩm hoặc nguyên liệu.";
            } else {
                if ($this->categoryModel->deleteCategory($id)) {
                    $_SESSION['success'] = "Đã xóa danh mục thành công!";
                } else {
                    $_SESSION['error'] = "Không thể xóa danh mục này!";
                }
            }
        }
        header("Location: " . BASE_URL . "/category");
        exit;
    }
}
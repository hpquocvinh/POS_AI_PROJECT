<?php
require_once APP_PATH . '/Models/ProductModel.php';

class ProductController extends Controller {
    private $productModel;

    public function __construct() {
        $this->productModel = $this->model('ProductModel');
    }

    // Action: Hiển thị danh sách sản phẩm
    public function index() {
        $products = $this->productModel->getAllProducts();
        
        $this->view('products/index', [
            'products' => $products
        ]);
    }

    // Action: Hiển thị Form thêm sản phẩm
    public function create() {
        $categoryModel = $this->model('CategoryModel');
        $categories = $categoryModel ? $categoryModel->getAllCategories() : [];

        $this->view('products/create', [
            'categories' => $categories
        ]);
    }

    // Action: Xử lý dữ liệu Submit từ Form thêm mới
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product_code   = trim($_POST['product_code'] ?? '');
            $product_name   = trim($_POST['product_name'] ?? '');
            $category_id    = $_POST['category_id'] ?? null;
            $price          = (float)($_POST['price'] ?? 0);
            $cost_price     = (float)($_POST['cost_price'] ?? 0);
            $stock_quantity = (float)($_POST['stock_quantity'] ?? 0);
            $status         = (int)($_POST['status'] ?? 1);

            // 1. CHẶN DỮ LIỆU BẮT BUỘC BỊ TRỐNG
            if (empty($product_code) || empty($product_name) || empty($category_id)) {
                $_SESSION['error'] = "Vui lòng điền đầy đủ các trường bắt buộc (*)!";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            // 2. CHECK GIÁ & TỒN KHO TRƯỚC
            if ($price <= 0) {
                $_SESSION['error'] = "Giá bán phải lớn hơn 0!";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            if ($cost_price < 0) {
                $_SESSION['error'] = "Giá Cost không được là số âm!";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            if ($stock_quantity < 0) {
                $_SESSION['error'] = "Số lượng tồn kho không được là số âm!";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            if ($cost_price >= $price) {
                $_SESSION['error'] = "Giá bán phải lớn hơn giá cost!";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            // 3. CHECK TRÙNG MÃ SKU IN DATABASE
            if ($this->productModel->checkCodeExists($product_code)) {
                $_SESSION['error'] = "Mã SKU '{$product_code}' đã tồn tại trong hệ thống! Vui lòng nhập mã khác.";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            // 4. CHECK TRÙNG TÊN SẢN PHẨM IN DATABASE
            if ($this->productModel->checkNameExists($product_name)) {
                $_SESSION['error'] = "Sản phẩm tên '{$product_name}' đã tồn tại trong hệ thống! Vui lòng nhập tên khác.";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            // 5. XỬ LÝ UPLOAD HÌNH ẢNH
            $imageName = 'default.jpg';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/';
                
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imageName = time() . '_' . uniqid() . '.' . $fileExtension;
                $targetFilePath = $uploadDir . $imageName;

                move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath);
            }

            // 6. THÊM VÀO CƠ SỞ DỮ LIỆU
            $data = [
                ':category_id'    => $category_id,
                ':product_code'   => $product_code,
                ':product_name'   => $product_name,
                ':price'          => $price,
                ':cost_price'     => $cost_price,
                ':stock_quantity' => $stock_quantity,
                ':image'          => $imageName,
                ':status'         => $status
            ];

            if ($this->productModel->insertProduct($data)) {
                $_SESSION['success'] = "Thêm sản phẩm mới thành công!";
                header("Location: " . BASE_URL . "/product");
                exit;
            } else {
                $_SESSION['error'] = "Đã xảy ra lỗi khi lưu sản phẩm vào CSDL!";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }
        }
    }

    // Action: Gọi giao diện Form Sửa
    public function edit() {
        $id = $_GET['id'] ?? 0;
        $product = $this->productModel->getProductById($id);
        
        $categoryModel = $this->model('CategoryModel');
        $categories = $categoryModel ? $categoryModel->getAllCategories() : [];

        if ($product) {
            $this->view('products/edit', [
                'product'    => $product,
                'categories' => $categories
            ]);
        } else {
            $_SESSION['error'] = "Không tìm thấy sản phẩm!";
            header("Location: " . BASE_URL . "/product");
            exit();
        }
    }

    // Action: Xử lý submit từ Form Sửa
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id           = $_POST['id'];
            $product_code = trim($_POST['product_code'] ?? '');
            $product_name = trim($_POST['product_name'] ?? '');
            $category_id  = $_POST['category_id'] ?? null;
            $price        = (float)($_POST['price'] ?? 0);
            $cost_price   = (float)($_POST['cost_price'] ?? 0);
            $stock_quantity = (float)($_POST['stock_quantity'] ?? 0);
            $status       = (int)($_POST['status'] ?? 1);

            $currentProduct = $this->productModel->getProductById($id);
            $currentImage   = $currentProduct ? $currentProduct->image : 'default.jpg';
            $imageName      = $currentImage;

            // 1. VALIDATE BẮT BUỘC
            if (empty($product_code) || empty($product_name) || empty($category_id)) {
                $_SESSION['error'] = "Vui lòng điền đầy đủ các trường bắt buộc (*)!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

            // 2. LOGIC GIÁ & TỒN KHO
            if ($price <= 0) {
                $_SESSION['error'] = "Giá bán phải lớn hơn 0!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

            if ($cost_price < 0) {
                $_SESSION['error'] = "Giá Cost không được là số âm!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

            if ($stock_quantity < 0) {
                $_SESSION['error'] = "Số lượng tồn kho không được là số âm!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

            if ($cost_price >= $price) {
                $_SESSION['error'] = "Giá bán phải lớn hơn giá cost!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

            // 3. CHECK TRÙNG MÃ SKU VÀ TÊN SẢN PHẨM (Đã truyền ID để loại trừ sản phẩm đang sửa)
            if ($this->productModel->checkCodeExists($product_code, $id)) {
                $_SESSION['error'] = "Mã SKU '{$product_code}' đã trùng với sản phẩm khác!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

            if ($this->productModel->checkNameExists($product_name, $id)) {
                $_SESSION['error'] = "Tên sản phẩm '{$product_name}' đã trùng với sản phẩm khác!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

            // 4. XỬ LÝ UPLOAD ẢNH MỚI NẾU CÓ
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/';
                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imageName = time() . '_' . uniqid() . '.' . $fileExtension;
                $targetFilePath = $uploadDir . $imageName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
                    if ($currentImage !== 'default.jpg' && file_exists($uploadDir . $currentImage)) {
                        unlink($uploadDir . $currentImage);
                    }
                }
            }

            $data = [
                ':id'            => $id,
                ':category_id'   => $category_id,
                ':product_code'  => $product_code,
                ':product_name'  => $product_name,
                ':price'         => $price,
                ':cost_price'    => $cost_price,
                ':stock_quantity'=> $stock_quantity,
                ':image'         => $imageName,
                ':status'        => $status
            ];

            if ($this->productModel->updateProduct($data)) {
                $_SESSION['success'] = "Cập nhật sản phẩm thành công!";
                header("Location: " . BASE_URL . "/product");
                exit();
            } else {
                $_SESSION['error'] = "Cập nhật sản phẩm thất bại!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit();
            }
        }
    }
    // API Check trùng mã SKU qua AJAX
    public function checkCode() {
        header('Content-Type: application/json');
        $code = trim($_GET['code'] ?? '');
        $excludeId = $_GET['exclude_id'] ?? null;

        if (empty($code)) {
            echo json_encode(['exists' => false]);
            exit;
        }

        $exists = $this->productModel->checkCodeExists($code, $excludeId);
        echo json_encode(['exists' => $exists]);
        exit;
    }

    // API Check trùng tên sản phẩm qua AJAX
    public function checkName() {
        header('Content-Type: application/json');
        $name = trim($_GET['name'] ?? '');
        $excludeId = $_GET['exclude_id'] ?? null;

        if (empty($name)) {
            echo json_encode(['exists' => false]);
            exit;
        }

        $exists = $this->productModel->checkNameExists($name, $excludeId);
        echo json_encode(['exists' => $exists]);
        exit;
    }

    // Action: Xóa sản phẩm
    public function delete() {
        $id = $_GET['id'] ?? 0;
        
        if ($id) {
            $product = $this->productModel->getProductById($id);
            $uploadDir = __DIR__ . '/../../public/uploads/';
            
            if ($product && !empty($product->image) && $product->image != 'default.jpg') {
                $imagePath = $uploadDir . $product->image;
                if (file_exists($imagePath)) {
                    unlink($imagePath); 
                }
            }

            if ($this->productModel->deleteProduct($id)) {
                $_SESSION['success'] = "Xóa sản phẩm thành công!";
            } else {
                $_SESSION['error'] = "Không thể xóa sản phẩm này!";
            }
        }

        header("Location: " . BASE_URL . "/product");
        exit();
    }
}
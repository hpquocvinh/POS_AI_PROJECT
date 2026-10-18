<?php
// Gọi Model vào để sử dụng
require_once 'Models/ProductModel.php';

class ProductController {
    private $db;
    private $productModel;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
        // Khởi tạo Model ngay khi Controller được gọi
        $this->productModel = new ProductModel($this->db);
    }

    // Action mặc định: Hiển thị danh sách sản phẩm
    public function index() {
        // 1. Nhờ Model lấy toàn bộ dữ liệu (đã JOIN với bảng categories)
        $products = $this->productModel->getAllProducts();
        
        // 2. Gọi View (giao diện HTML) và "bơm" biến $products sang đó để in ra bảng
        require_once 'Views/products/index.php';
    }
    // Action: Hiển thị Form thêm sản phẩm
    public function create() {
        // Cần lấy danh sách Category để đổ vào thẻ <select>
        // Giả sử ông đã có CategoryModel (nếu chưa có thì gọi truy vấn tạm hoặc tui sẽ cấp sau)
        $stmt = $this->db->prepare("SELECT id, category_name FROM categories");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_OBJ);

        require_once 'Views/products/create.php';
    }

    // Action: Xử lý dữ liệu Submit từ Form
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $imageName = 'default.jpg'; // Tên ảnh mặc định nếu không upload

            // Xử lý upload ảnh (nếu có)
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $targetDir = "public/uploads/";
                // Đổi tên file để tránh trùng lặp
                $imageName = time() . '_' . basename($_FILES["image"]["name"]); 
                $targetFilePath = $targetDir . $imageName;
                
                // Di chuyển file vào thư mục
                move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath);
            }

            // Gom dữ liệu từ Form gửi lên
            $data = [
                ':category_id' => $_POST['category_id'],
                ':product_code' => $_POST['product_code'],
                ':product_name' => $_POST['product_name'],
                ':price' => $_POST['price'],
                ':cost_price' => $_POST['cost_price'],
                ':stock_quantity' => $_POST['stock_quantity'],
                ':image' => $imageName,
                ':status' => $_POST['status']
            ];

            // Gọi Model để lưu vào DB
            if ($this->productModel->insertProduct($data)) {
                // Thêm thành công thì chuyển hướng về trang danh sách
                header("Location: ?controller=product&action=index");
                exit();
            }
        }
    }
    // Action: Gọi giao diện Form Sửa
    public function edit() {
        $id = $_GET['id'] ?? 0;
        $product = $this->productModel->getProductById($id);
        
        // Vẫn phải lấy danh sách Categories để đổ vào thẻ <select>
        $stmt = $this->db->prepare("SELECT id, category_name FROM categories");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_OBJ);

        if($product) {
            require_once 'Views/products/edit.php';
        } else {
            header("Location: ?controller=product&action=index");
        }
    }

    // Action: Xử lý submit từ Form Sửa
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $currentImage = $_POST['current_image'];
            $imageName = $currentImage; // Mặc định giữ ảnh cũ nếu không up hình mới

            // Nếu người dùng chọn upload ảnh mới
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $imageName = time() . '_' . basename($_FILES["image"]["name"]); 
                move_uploaded_file($_FILES["image"]["tmp_name"], "public/uploads/" . $imageName);
            }

            $data = [
                ':id' => $id,
                ':category_id' => $_POST['category_id'],
                ':product_code' => $_POST['product_code'],
                ':product_name' => $_POST['product_name'],
                ':price' => $_POST['price'],
                ':cost_price' => $_POST['cost_price'],
                ':stock_quantity' => $_POST['stock_quantity'],
                ':image' => $imageName,
                ':status' => $_POST['status']
            ];

            if ($this->productModel->updateProduct($data)) {
                header("Location: ?controller=product&action=index");
                exit();
            }
        }
    }
    // Action: Xóa sản phẩm
    public function delete() {
        $id = $_GET['id'] ?? 0;
        
        if ($id) {
            // 1. Lấy thông tin sản phẩm để biết tên file ảnh cần xóa
            $product = $this->productModel->getProductById($id);
            
            if ($product && !empty($product->image) && $product->image != 'default.jpg') {
                $imagePath = "public/uploads/" . $product->image;
                // Kiểm tra file có tồn tại không thì xóa file vật lý
                if (file_exists($imagePath)) {
                    unlink($imagePath); 
                }
            }

            // 2. Xóa dữ liệu trong Database
            $this->productModel->deleteProduct($id);
        }

        // Xóa xong thì quay về trang danh sách
        header("Location: ?controller=product&action=index");
        exit();
    }
}
?>
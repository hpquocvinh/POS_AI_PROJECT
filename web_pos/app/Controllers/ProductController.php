<?php
require_once APP_PATH . '/Models/ProductModel.php';
require_once APP_PATH . '/Models/IngredientModel.php';

class ProductController extends Controller {
    private $productModel;
    private $ingredientModel;

    public function __construct() {
        $this->productModel = $this->model('ProductModel');
        $this->ingredientModel = $this->model('IngredientModel');
    }

    private function getCurrentUserName() {
        if (!empty($_SESSION['user']['fullname'])) return $_SESSION['user']['fullname'];
        if (!empty($_SESSION['fullname'])) return $_SESSION['fullname'];
        return 'Quản Trị Viên';
    }

    public function index() {
        $products = $this->productModel->getAllProducts();
        $ingredients = $this->ingredientModel->getAllIngredients();
        
        $categoryModel = $this->model('CategoryModel');
        $ingredientCategories = $categoryModel ? $categoryModel->getIngredientCategories(true) : [];
      
        $lowStockCount = 0;
        foreach ($ingredients as $ing) {
            if ($ing->stock_quantity <= $ing->min_stock) {
                $lowStockCount++;
            }
        }
        $recipeCounts = $this->productModel->getRecipeCountsByProduct();
        $activeTab = $_GET['tab'] ?? 'products';
        
        $this->view('products/index', [
            'products'             => $products,
            'ingredients'          => $ingredients,
            'ingredientCategories' => $ingredientCategories,
            'lowStockCount'        => $lowStockCount,
            'recipeCounts'         => $recipeCounts,
            'activeTab'            => $activeTab
        ]);
    }

    // ==================== QUẢN LÝ NGUYÊN LIỆU ====================

    public function storeIngredient() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code            = trim($_POST['ingredient_code'] ?? '');
            $name            = trim($_POST['ingredient_name'] ?? '');
            $category_id     = $_POST['category_id'] ?? null;
            $unit            = trim($_POST['unit'] ?? '');
            $purchase_unit   = trim($_POST['purchase_unit'] ?? '');
            $conversion_rate = (float)($_POST['conversion_rate'] ?? 1);
            $cost_price      = (float)($_POST['cost_price'] ?? 0);
            $input_stock     = (float)($_POST['stock_quantity'] ?? 0);
            $min_stock       = (float)($_POST['min_stock'] ?? 0);
            $expiry_date     = trim($_POST['nearest_expiry_date'] ?? '');

            if (empty($code) || empty($name) || empty($category_id) || empty($unit)) {
                $_SESSION['error'] = "Vui lòng điền đầy đủ Mã, Tên, Nhóm nguyên liệu và Đơn vị tính!";
                header("Location: " . BASE_URL . "/product?tab=ingredients");
                exit;
            }

            if (empty($purchase_unit) || $purchase_unit === $unit || $conversion_rate <= 1) {
                $purchase_unit = null;
                $conversion_rate = 1;
            }

            if ($input_stock < 0 || $min_stock < 0 || $cost_price < 0) {
                $_SESSION['error'] = "Số lượng tồn, mức cảnh báo và giá nhập không được là số âm!";
                header("Location: " . BASE_URL . "/product?tab=ingredients");
                exit;
            }

            if ($this->ingredientModel->checkCodeExists($code)) {
                $_SESSION['error'] = "Mã nguyên liệu '{$code}' đã tồn tại!";
                header("Location: " . BASE_URL . "/product?tab=ingredients");
                exit;
            }

            $baseStockQuantity = $purchase_unit ? ($input_stock * $conversion_rate) : $input_stock;

            $data = [
                'category_id'         => $category_id,
                'ingredient_code'     => $code,
                'ingredient_name'     => $name,
                'unit'                => $unit,
                'purchase_unit'       => $purchase_unit,
                'conversion_rate'     => $conversion_rate,
                'cost_price'          => $cost_price,
                'stock_quantity'      => $baseStockQuantity,
                'min_stock'           => $min_stock,
                'nearest_expiry_date' => !empty($expiry_date) ? $expiry_date : null
            ];

            if ($this->ingredientModel->create($data, $this->getCurrentUserName())) {
                $_SESSION['success'] = "Đã thêm nguyên liệu '{$name}' vào kho!";
            } else {
                $_SESSION['error'] = "Lỗi khi lưu nguyên liệu!";
            }
            header("Location: " . BASE_URL . "/product?tab=ingredients");
            exit;
        }
    }

    public function importIngredient() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id           = (int)($_POST['id'] ?? 0);
            $add_quantity = (float)($_POST['add_quantity'] ?? 0);
            $import_mode  = trim($_POST['import_unit_mode'] ?? 'base');
            $cost_price   = isset($_POST['cost_price']) && $_POST['cost_price'] !== '' ? (float)$_POST['cost_price'] : null;
            $expiry_date  = trim($_POST['expiry_date'] ?? '');
            $note         = trim($_POST['import_note'] ?? '');

            if ($id <= 0 || $add_quantity <= 0) {
                $_SESSION['error'] = "Số lượng nhập thêm phải lớn hơn 0!";
                header("Location: " . BASE_URL . "/product?tab=ingredients");
                exit;
            }

            $ing = $this->ingredientModel->getById($id);
            if (!$ing) {
                $_SESSION['error'] = "Không tìm thấy nguyên liệu!";
                header("Location: " . BASE_URL . "/product?tab=ingredients");
                exit;
            }

            $convRate = (float)($ing->conversion_rate ?? 1);
            $hasPurchaseUnit = !empty($ing->purchase_unit) && $convRate > 1;

            if ($import_mode === 'purchase' && $hasPurchaseUnit) {
                $addBaseQty = $add_quantity * $convRate;
                $unitLabel  = "{$add_quantity} {$ing->purchase_unit} (+{$addBaseQty} {$ing->unit})";
                $logNote    = "Nhập {$add_quantity} {$ing->purchase_unit}" . ($note ? " - {$note}" : '');
            } else {
                $addBaseQty = $add_quantity;
                $unitLabel  = "+{$addBaseQty} {$ing->unit}";
                $logNote    = "Nhập lẻ {$add_quantity} {$ing->unit}" . ($note ? " - {$note}" : '');
                if ($hasPurchaseUnit) {
                    $cost_price = null;
                }
            }

            if ($this->ingredientModel->importStock($id, $addBaseQty, $cost_price, $expiry_date, $logNote, $this->getCurrentUserName())) {
                $this->productModel->syncProductCostsByIngredient($id);
                $_SESSION['success'] = "Nhập kho thành công ({$unitLabel})!";
            } else {
                $_SESSION['error'] = "Không thể cập nhật nhập kho!";
            }
            header("Location: " . BASE_URL . "/product?tab=ingredients");
            exit;
        }
    }

    public function auditIngredient() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id              = (int)($_POST['id'] ?? 0);
            $actual_quantity = (float)($_POST['actual_quantity'] ?? 0);
            $audit_reason    = trim($_POST['audit_reason'] ?? '');

            if ($id <= 0 || $actual_quantity < 0) {
                $_SESSION['error'] = "Số lượng kiểm kê thực tế không hợp lệ!";
                header("Location: " . BASE_URL . "/product?tab=ingredients");
                exit;
            }

            if ($this->ingredientModel->auditStock($id, $actual_quantity, $audit_reason, $this->getCurrentUserName())) {
                $_SESSION['success'] = "Đã cập nhật số liệu kiểm kê & lưu vào Thẻ kho!";
            } else {
                $_SESSION['error'] = "Lỗi khi cập nhật kiểm kê!";
            }
            header("Location: " . BASE_URL . "/product?tab=ingredients");
            exit;
        }
    }

    public function getIngredientLogs() {
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'logs' => []]);
            exit;
        }
        $logs = $this->ingredientModel->getLogsByIngredient($id, 50);
        echo json_encode(['success' => true, 'logs' => $logs]);
        exit;
    }

    public function updateIngredient() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id              = (int)($_POST['id'] ?? 0);
            $code            = trim($_POST['ingredient_code'] ?? '');
            $name            = trim($_POST['ingredient_name'] ?? '');
            $category_id     = $_POST['category_id'] ?? null;
            $unit            = trim($_POST['unit'] ?? '');
            $purchase_unit   = trim($_POST['purchase_unit'] ?? '');
            $conversion_rate = (float)($_POST['conversion_rate'] ?? 1);
            $cost_price      = (float)($_POST['cost_price'] ?? 0);
            $min_stock       = (float)($_POST['min_stock'] ?? 0);
            $expiry_date     = trim($_POST['nearest_expiry_date'] ?? '');

            if ($id <= 0 || empty($code) || empty($name) || empty($category_id) || empty($unit)) {
                $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin bắt buộc!";
                header("Location: " . BASE_URL . "/product?tab=ingredients");
                exit;
            }

            if (empty($purchase_unit) || $purchase_unit === $unit || $conversion_rate <= 1) {
                $purchase_unit = null;
                $conversion_rate = 1;
            }

            if ($this->ingredientModel->checkCodeExists($code, $id)) {
                $_SESSION['error'] = "Mã nguyên liệu '{$code}' bị trùng!";
                header("Location: " . BASE_URL . "/product?tab=ingredients");
                exit;
            }

            $data = [
                'category_id'         => $category_id,
                'ingredient_code'     => $code,
                'ingredient_name'     => $name,
                'unit'                => $unit,
                'purchase_unit'       => $purchase_unit,
                'conversion_rate'     => $conversion_rate,
                'cost_price'          => $cost_price,
                'min_stock'           => $min_stock,
                'nearest_expiry_date' => !empty($expiry_date) ? $expiry_date : null
            ];

            if ($this->ingredientModel->update($id, $data)) {
                $this->productModel->syncProductCostsByIngredient($id);
                $_SESSION['success'] = "Cập nhật nguyên liệu thành công!";
            } else {
                $_SESSION['error'] = "Cập nhật nguyên liệu thất bại!";
            }
            header("Location: " . BASE_URL . "/product?tab=ingredients");
            exit;
        }
    }

    public function deleteIngredient() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0 && $this->ingredientModel->delete($id)) {
            $_SESSION['success'] = "Đã xóa nguyên liệu khỏi kho!";
        } else {
            $_SESSION['error'] = "Không thể xóa nguyên liệu này!";
        }
        header("Location: " . BASE_URL . "/product?tab=ingredients");
        exit;
    }

    public function checkIngredientCode() {
        header('Content-Type: application/json');
        $code = trim($_GET['code'] ?? '');
        $excludeId = $_GET['exclude_id'] ?? null;
        if (empty($code)) {
            echo json_encode(['exists' => false]);
            exit;
        }
        $exists = $this->ingredientModel->checkCodeExists($code, $excludeId);
        echo json_encode(['exists' => $exists]);
        exit;
    }

    public function checkIngredientName() {
        header('Content-Type: application/json');
        $name = trim($_GET['name'] ?? '');
        $excludeId = $_GET['exclude_id'] ?? null;
        if (empty($name)) {
            echo json_encode(['exists' => false]);
            exit;
        }
        $exists = $this->ingredientModel->checkNameExists($name, $excludeId);
        echo json_encode(['exists' => $exists]);
        exit;
    }

    // ==================== QUẢN LÝ SẢN PHẨM BÁN ====================

    public function create() {
        $categoryModel = $this->model('CategoryModel');
        $categories = $categoryModel ? $categoryModel->getProductCategories(true) : [];
        $ingredients = $this->ingredientModel->getAllIngredients();

        $this->view('products/create', [
            'categories'  => $categories,
            'ingredients' => $ingredients
        ]);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product_code   = trim($_POST['product_code'] ?? '');
            $product_name   = trim($_POST['product_name'] ?? '');
            $category_id    = $_POST['category_id'] ?? null;
            $price          = (float)($_POST['price'] ?? 0);
            $cost_price     = (float)($_POST['cost_price'] ?? 0);
            $manage_stock   = isset($_POST['manage_stock']) ? 1 : 0;
            $stock_quantity = $manage_stock ? (int)($_POST['stock_quantity'] ?? 0) : 0;
            $min_stock      = $manage_stock ? max(0, (int)($_POST['min_stock'] ?? 5)) : 5;
            $expiry_date    = ($manage_stock && !empty($_POST['expiry_date'])) ? trim($_POST['expiry_date']) : null;
            $status         = (int)($_POST['status'] ?? 1);
            $recipe_json    = $_POST['recipe_items_json'] ?? '[]';

            // Nhóm tùy chọn POS (Modifiers: size, sugar, ice, topping)
            $allowedMods    = ['size', 'sugar', 'ice', 'topping'];
            $postedMods     = isset($_POST['modifiers']) && is_array($_POST['modifiers']) ? $_POST['modifiers'] : [];
            $cleanMods      = array_values(array_intersect($allowedMods, $postedMods));
            $modifiers_cfg  = implode(',', $cleanMods);

            $maxLimit = 100000000;

            if (empty($product_code) || empty($product_name) || empty($category_id)) {
                $_SESSION['error'] = "Vui lòng điền đầy đủ các trường bắt buộc (*)!";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            if ($price <= 0 || $price > $maxLimit) {
                $_SESSION['error'] = "Giá bán phải lớn hơn 0 và không vượt quá 100,000,000 đ!";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            if ($cost_price < 0 || $cost_price > $maxLimit) {
                $_SESSION['error'] = "Giá Cost phải từ 0 đến 100,000,000 đ!";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            if ($manage_stock && ($stock_quantity < 0 || $stock_quantity > 1000000)) {
                $_SESSION['error'] = "Số lượng tồn kho không hợp lệ (từ 0 đến 1,000,000)!";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            if ($cost_price >= $price) {
                $_SESSION['error'] = "Giá bán phải lớn hơn giá cost!";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            if ($this->productModel->checkCodeExists($product_code)) {
                $_SESSION['error'] = "Mã SKU '{$product_code}' đã tồn tại trong hệ thống! Vui lòng nhập mã khác.";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

            if ($this->productModel->checkNameExists($product_name)) {
                $_SESSION['error'] = "Sản phẩm tên '{$product_name}' đã tồn tại trong hệ thống! Vui lòng nhập tên khác.";
                header("Location: " . BASE_URL . "/product/create");
                exit;
            }

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

            $data = [
                ':category_id'      => $category_id,
                ':product_code'     => $product_code,
                ':product_name'     => $product_name,
                ':price'            => $price,
                ':cost_price'       => $cost_price,
                ':stock_quantity'   => $stock_quantity,
                ':min_stock'        => $min_stock,
                ':expiry_date'      => $expiry_date,
                ':manage_stock'     => $manage_stock,
                ':modifiers_config' => $modifiers_cfg,
                ':image'            => $imageName,
                ':status'           => $status
            ];

            $newProductId = $this->productModel->insertProduct($data);
            if ($newProductId) {
                if ($manage_stock === 0) {
                    $recipeItems = json_decode($recipe_json, true);
                    if (is_array($recipeItems) && !empty($recipeItems)) {
                        $this->productModel->saveProductRecipes($newProductId, $recipeItems, true);
                    }
                }
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

    public function edit() {
        $id = $_GET['id'] ?? 0;
        $product = $this->productModel->getProductById($id);
        $categoryModel = $this->model('CategoryModel');
        $categories = $categoryModel ? $categoryModel->getProductCategories() : [];
        $ingredients = $this->ingredientModel->getAllIngredients();
        $recipes = $product ? $this->productModel->getProductRecipes($product->id) : [];

        if ($product) {
            $this->view('products/edit', [
                'product'     => $product,
                'categories'  => $categories,
                'ingredients' => $ingredients,
                'recipes'     => $recipes
            ]);
        } else {
            $_SESSION['error'] = "Không tìm thấy sản phẩm!";
            header("Location: " . BASE_URL . "/product");
            exit();
        }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id             = (int)$_POST['id'];
            $product_code   = trim($_POST['product_code'] ?? '');
            $product_name   = trim($_POST['product_name'] ?? '');
            $category_id    = $_POST['category_id'] ?? null;
            $price          = (float)($_POST['price'] ?? 0);
            $cost_price     = (float)($_POST['cost_price'] ?? 0);
            $manage_stock   = isset($_POST['manage_stock']) ? 1 : 0;
            $stock_quantity = $manage_stock ? (int)($_POST['stock_quantity'] ?? 0) : 0;
            $min_stock      = $manage_stock ? max(0, (int)($_POST['min_stock'] ?? 5)) : 5;
            $expiry_date    = ($manage_stock && !empty($_POST['expiry_date'])) ? trim($_POST['expiry_date']) : null;
            $status         = (int)($_POST['status'] ?? 1);
            $recipe_json    = $_POST['recipe_items_json'] ?? null;

            $allowedMods    = ['size', 'sugar', 'ice', 'topping'];
            $postedMods     = isset($_POST['modifiers']) && is_array($_POST['modifiers']) ? $_POST['modifiers'] : [];
            $cleanMods      = array_values(array_intersect($allowedMods, $postedMods));
            $modifiers_cfg  = implode(',', $cleanMods);

            $maxLimit = 100000000;

            $currentProduct = $this->productModel->getProductById($id);
            $currentImage   = $currentProduct ? $currentProduct->image : 'default.jpg';
            $imageName      = $currentImage;

            if (empty($product_code) || empty($product_name) || empty($category_id)) {
                $_SESSION['error'] = "Vui lòng điền đầy đủ các trường bắt buộc (*)!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

            if ($price <= 0 || $price > $maxLimit) {
                $_SESSION['error'] = "Giá bán phải lớn hơn 0 và không vượt quá 100,000,000 đ!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

            if ($cost_price < 0 || $cost_price > $maxLimit) {
                $_SESSION['error'] = "Giá Cost phải từ 0 đến 100,000,000 đ!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

            if ($manage_stock && ($stock_quantity < 0 || $stock_quantity > 1000000)) {
                $_SESSION['error'] = "Số lượng tồn kho không hợp lệ (từ 0 đến 1,000,000)!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

            if ($cost_price >= $price) {
                $_SESSION['error'] = "Giá bán phải lớn hơn giá cost!";
                header("Location: " . BASE_URL . "/product/edit?id=" . $id);
                exit;
            }

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
                ':id'               => $id,
                ':category_id'      => $category_id,
                ':product_code'     => $product_code,
                ':product_name'     => $product_name,
                ':price'            => $price,
                ':cost_price'       => $cost_price,
                ':stock_quantity'   => $stock_quantity,
                ':min_stock'        => $min_stock,
                ':expiry_date'      => $expiry_date,
                ':manage_stock'     => $manage_stock,
                ':modifiers_config' => $modifiers_cfg,
                ':image'            => $imageName,
                ':status'           => $status
            ];

            if ($this->productModel->updateProduct($data)) {
                if ($manage_stock === 0 && $recipe_json !== null) {
                    $recipeItems = json_decode($recipe_json, true);
                    if (is_array($recipeItems)) {
                        $this->productModel->saveProductRecipes($id, $recipeItems, !empty($recipeItems));
                    }
                } elseif ($manage_stock === 1) {
                    $this->productModel->saveProductRecipes($id, [], false);
                }
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

    public function generateSku() {
        header('Content-Type: application/json');
        $name = trim($_GET['name'] ?? '');
        $prefix = 'SP';

        if (!empty($name)) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
            $ascii = preg_replace('/[^A-Za-z0-9\s]/', '', $ascii);
            $words = preg_split('/\s+/', trim($ascii));
            $initials = '';
            foreach ($words as $w) {
                if (!empty($w)) {
                    $initials .= strtoupper($w[0]);
                }
            }
            if (strlen($initials) >= 2) {
                $prefix = substr($initials, 0, 4);
            }
        }

        $sku = '';
        for ($i = 1; $i <= 999; $i++) {
            $candidate = $prefix . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            if (!$this->productModel->checkCodeExists($candidate)) {
                $sku = $candidate;
                break;
            }
        }

        if (empty($sku)) {
            $sku = $prefix . rand(100, 999);
        }

        echo json_encode(['sku' => $sku]);
        exit;
    }

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

    public function getRecipe() {
        header('Content-Type: application/json');
        $productId = (int)($_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'recipes' => []]);
            exit;
        }
        $recipes = $this->productModel->getProductRecipes($productId);
        echo json_encode(['success' => true, 'recipes' => $recipes]);
        exit;
    }

    public function saveRecipe() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            exit;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $productId = (int)($data['product_id'] ?? 0);
        $items     = $data['items'] ?? [];

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ!']);
            exit;
        }

        $result = $this->productModel->saveProductRecipes($productId, $items, true);
        if ($result['success']) {
            $costFormatted = number_format($result['calculated_cost']);
            echo json_encode([
                'success'         => true,
                'calculated_cost' => $result['calculated_cost'],
                'message'         => "Đã lưu công thức & tự động cập nhật Giá Cost: {$costFormatted} đ!"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu công thức!']);
        }
        exit;
    }
}
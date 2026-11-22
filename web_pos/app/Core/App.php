<?php
// app/Core/App.php
// Bộ định tuyến URL trung tâm của hệ thống MVC

class App {
    protected $controller = DEFAULT_CONTROLLER;
    protected $action     = DEFAULT_ACTION;
    protected $params     = [];

    public function __construct() {
        $url = $this->parseUrl();

        if (!empty($url[0])) {
            // Chuyển đổi linh hoạt các dạng URL (vd: orderManage, order-manage, ordermanage) sang chuẩn PascalCase
            $rawSegment     = str_replace(['-', '_'], ' ', $url[0]);
            $pascalSegment  = str_replace(' ', '', ucwords($rawSegment));
            $directSegment  = ucfirst($url[0]);

            $controllerDir  = "../app/Controllers/";
            $matchedFile    = null;

            // Ưu tiên khớp chính xác tên file Controller trước (giữ nguyên tương thích 100% với các trang cũ)
            if (file_exists($controllerDir . $directSegment . "Controller.php")) {
                $matchedFile = $directSegment . "Controller";
            } elseif (file_exists($controllerDir . $pascalSegment . "Controller.php")) {
                $matchedFile = $pascalSegment . "Controller";
            } else {
                // Quét không phân biệt hoa/thường trong thư mục Controllers để tránh lỗi gõ sai kiểu chữ
                $targetLower = strtolower($pascalSegment . "Controller.php");
                foreach (scandir($controllerDir) as $file) {
                    if (strtolower($file) === $targetLower) {
                        $matchedFile = pathinfo($file, PATHINFO_FILENAME);
                        break;
                    }
                }
            }

            if ($matchedFile !== null) {
                $this->controller = $matchedFile;
                unset($url[0]);
            }
        }

        require_once "../app/Controllers/" . $this->controller . ".php";
        $this->controller = new $this->controller;

        // Xác định phương thức (Action) cần gọi trong Controller
        if (isset($url[1]) && method_exists($this->controller, $url[1])) {
            $this->action = $url[1];
            unset($url[1]);
        }

        $this->params = $url ? array_values($url) : [];
        call_user_func_array([$this->controller, $this->action], $this->params);
    }

    // Tách chuỗi URL từ tham số ?url= do .htaccess truyền vào
    private function parseUrl() {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }
}
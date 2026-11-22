<?php
// app/Controllers/OrderManageController.php
// Controller điều phối giao diện Quản lý đơn hàng và cung cấp API xử lý bất đồng bộ

class OrderManageController extends Controller {
    private $orderManageModel;

    public function __construct() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->orderManageModel = $this->model('OrderManageModel');
        $this->ensureOrderManageColumns();
    }

    // Tự động bổ sung cột cancel_reason và audit_log vào bảng orders nếu hệ thống chưa có
    private function ensureOrderManageColumns() {
        try {
            $db = Database::getInstance();
            $checkCancel = $db->query("SHOW COLUMNS FROM orders LIKE 'cancel_reason'");
            if (!$checkCancel || $checkCancel->rowCount() == 0) {
                $db->exec("ALTER TABLE orders ADD COLUMN cancel_reason VARCHAR(255) NULL AFTER order_status");
            }
            $checkLog = $db->query("SHOW COLUMNS FROM orders LIKE 'audit_log'");
            if (!$checkLog || $checkLog->rowCount() == 0) {
                $db->exec("ALTER TABLE orders ADD COLUMN audit_log TEXT NULL AFTER cancel_reason");
            }
        } catch (Exception $e) {
            // Bỏ qua nếu cột đã tồn tại
        }
    }

    // Hiển thị trang khung Quản lý đơn hàng (Dữ liệu bảng sẽ được tải qua AJAX)
    public function index() {
        $role = $_SESSION['user']['role'] ?? 'admin';
        $this->view('order_manage/index', [
            'isAdmin' => ($role === 'admin')
        ]);
    }

    // API trả về danh sách đơn hàng, phân trang và 4 thẻ KPI theo bộ lọc
    public function getOrders() {
        header('Content-Type: application/json');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 15;
        $offset = ($page - 1) * $limit;

        $filters = [
            'keyword'     => $_GET['keyword'] ?? '',
            'date_preset' => $_GET['date_preset'] ?? 'today',
            'date_from'   => $_GET['date_from'] ?? '',
            'date_to'     => $_GET['date_to'] ?? '',
            'channel'     => $_GET['channel'] ?? 'ALL',
            'status'      => $_GET['status'] ?? 'ALL',
            'payment'     => $_GET['payment'] ?? 'ALL'
        ];

        $result     = $this->orderManageModel->getOrdersWithStats($filters, $limit, $offset);
        $totalPages = max(1, (int)ceil($result['total_records'] / $limit));

        echo json_encode([
            'success'       => true,
            'orders'        => $result['orders'],
            'kpi'           => $result['kpi'],
            'current_page'  => $page,
            'total_pages'   => $totalPages,
            'total_records' => $result['total_records']
        ]);
        exit;
    }

    // API trả về thông tin chi tiết 1 hóa đơn cho Modal xem chi tiết & in bill
    public function getOrderDetail() {
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã định danh hóa đơn không hợp lệ.']);
            exit;
        }

        $order = $this->orderManageModel->getOrderDetail($id);
        if ($order) {
            echo json_encode(['success' => true, 'order' => $order]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin hóa đơn yêu cầu.']);
        }
        exit;
    }

    // API xử lý Hủy hóa đơn / Hoàn tiền (Kiểm tra phân quyền Quản trị viên)
    public function cancelOrder() {
        header('Content-Type: application/json');
        $role = $_SESSION['user']['role'] ?? 'admin';
        if ($role !== 'admin') {
            echo json_encode([
                'success' => false,
                'message' => 'Tài khoản của bạn không có quyền thực hiện nghiệp vụ Hủy đơn / Hoàn tiền.'
            ]);
            exit;
        }

        $input   = json_decode(file_get_contents('php://input'), true);
        $orderId = (int)($input['order_id'] ?? 0);
        $reason  = trim($input['reason'] ?? '');

        if ($orderId <= 0 || $reason === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng nhập đầy đủ lý do hủy hóa đơn để lưu vết đối soát.'
            ]);
            exit;
        }

        $actorName = $_SESSION['user']['fullname'] ?? ($_SESSION['user']['username'] ?? 'Quản trị viên');
        $res = $this->orderManageModel->cancelAndRefundOrder($orderId, $reason, $actorName);
        echo json_encode($res);
        exit;
    }

    // Xuất danh sách hóa đơn ra file CSV mở trực tiếp bằng Excel
    public function exportExcel() {
        $filters = [
            'keyword'     => $_GET['keyword'] ?? '',
            'date_preset' => $_GET['date_preset'] ?? 'ALL',
            'date_from'   => $_GET['date_from'] ?? '',
            'date_to'     => $_GET['date_to'] ?? '',
            'channel'     => $_GET['channel'] ?? 'ALL',
            'status'      => $_GET['status'] ?? 'ALL',
            'payment'     => $_GET['payment'] ?? 'ALL'
        ];

        $orders = $this->orderManageModel->getOrdersForExport($filters);
        $filename = 'Bao_Cao_Don_Hang_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 hiển thị chuẩn tiếng Việt trong Excel

        fputcsv($output, ['Mã Hóa Đơn', 'Thời Gian Tạo', 'Kênh Bán Hàng', 'Khách Hàng', 'SĐT', 'Thu Ngân', 'Tổng Tiền Hàng', 'Giảm Giá', 'Thực Thu (Net)', 'Thanh Toán', 'Trạng Thái']);

        $channelNames = ['dine_in' => 'Tại chỗ', 'takeaway' => 'Mang về', 'grab' => 'GrabFood', 'shopee' => 'ShopeeFood'];
        $statusNames  = ['completed' => 'Hoàn thành', 'serving' => 'Đang phục vụ', 'pending' => 'Đang phục vụ', 'cancelled' => 'Đã hủy'];

        foreach ($orders as $o) {
            fputcsv($output, [
                $o['order_code'],
                date('d/m/Y H:i:s', strtotime($o['created_at'])),
                $channelNames[$o['order_type']] ?? $o['order_type'],
                $o['customer_name'] ?: 'Khách vãng lai',
                $o['customer_phone'] ?: '',
                $o['cashier_name'] ?: ($o['cashier_code'] ?: 'NV01'),
                $o['total_amount'],
                $o['discount'],
                $o['net_amount'],
                strtoupper($o['payment_method']),
                $statusNames[$o['order_status']] ?? $o['order_status']
            ]);
        }
        fclose($output);
        exit;
    }
}
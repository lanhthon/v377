<?php
// File: api/get_revenue_over_time_report.php
// API để lấy dữ liệu doanh thu theo thời gian cho biểu đồ.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Sử dụng kết nối CSDL chung từ tệp cấu hình
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Lấy các tham số từ request GET
    $start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : null;
    $customer_name = isset($_GET['customer_name']) && !empty($_GET['customer_name']) ? $_GET['customer_name'] : null;

    // Kiểm tra các tham số ngày tháng có hợp lệ không
    if (!$start_date || !$end_date) {
        throw new Exception("Vui lòng cung cấp ngày bắt đầu và ngày kết thúc.");
    }

    // Xây dựng câu lệnh SQL cơ bản
    $sql = "
        SELECT 
            DATE(NgayBaoGia) as date,
            SUM(TongTienSauThue) as total_revenue
        FROM 
            baogia
        WHERE 
            TrangThai = 'Chốt'
            AND DATE(NgayBaoGia) BETWEEN ? AND ?
    ";

    $params = [$start_date, $end_date];
    $types = "ss";

    // Thêm điều kiện lọc theo khách hàng nếu có
    if ($customer_name) {
        $sql .= " AND TenCongTy = ?";
        $params[] = $customer_name;
        $types .= "s";
    }

    // Hoàn thiện câu lệnh SQL
    $sql .= "
        GROUP BY 
            DATE(NgayBaoGia)
        ORDER BY 
            date ASC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh: " . $conn->error);
    }

    // Gắn các tham số vào câu lệnh
    $stmt->bind_param($types, ...$params);
    
    $stmt->execute();
    $result = $stmt->get_result();

    // Lấy tất cả dữ liệu một lần
    $revenue_data = $result->fetch_all(MYSQLI_ASSOC);

    $response['success'] = true;
    $response['data'] = $revenue_data;

    $stmt->close();

} catch (Exception $e) {
    // Trả về mã lỗi 500 khi có lỗi server
    http_response_code(500);
    $response['message'] = 'Lỗi server: ' . $e->getMessage();
}

// Đóng kết nối CSDL
$conn->close();

// Trả về kết quả dưới dạng JSON
echo json_encode($response);
?>

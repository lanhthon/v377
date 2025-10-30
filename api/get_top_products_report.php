<?php
// File: api/get_top_products_report.php
// API để lấy danh sách các sản phẩm được báo giá nhiều nhất.

// Báo cáo lỗi để gỡ lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Sử dụng kết nối CSDL chung từ tệp cấu hình
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Lấy số lượng sản phẩm top cần hiển thị, mặc định là 10
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    // Câu lệnh SQL để đếm số lần xuất hiện của mỗi sản phẩm trong chi tiết báo giá
    // Sử dụng prepared statement để bảo mật
    $sql = "
        SELECT 
            MaHang,
            TenSanPham,
            COUNT(*) AS quote_count
        FROM 
            chitietbaogia
        WHERE 
            MaHang IS NOT NULL AND TRIM(MaHang) <> ''
        GROUP BY 
            MaHang, TenSanPham
        ORDER BY 
            quote_count DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh: " . $conn->error);
    }
    
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    // Lấy tất cả dữ liệu một lần
    $top_products = $result->fetch_all(MYSQLI_ASSOC);

    $response['success'] = true;
    $response['data'] = $top_products;

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

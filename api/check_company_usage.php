<?php
// api/check_company_usage.php

header('Content-Type: application/json; charset=utf-8');

// Sử dụng file cấu hình database của bạn
require_once '../config/database.php'; 

// Khởi tạo một mảng phản hồi mặc định
$response = [
    'success' => false,
    'in_use' => false,
    'message' => ''
];

// Lấy dữ liệu từ body của request (dạng JSON)
$input = json_decode(file_get_contents('php://input'), true);
$companyId = $input['id'] ?? null;

// Kiểm tra xem ID công ty có được cung cấp hay không
if (!$companyId) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Không có ID công ty nào được cung cấp.';
    echo json_encode($response);
    exit;
}

try {
    // Câu lệnh SQL không đổi
    $sql = "SELECT COUNT(*) as count FROM baogia WHERE CongTyID = ?";
    
    // 1. Chuẩn bị câu lệnh với MySQLi
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Lỗi khi chuẩn bị câu lệnh: ' . $conn->error);
    }

    // 2. Gán tham số
    $stmt->bind_param("i", $companyId); // "i" là kiểu integer

    // 3. Thực thi
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi thực thi câu lệnh: ' . $stmt->error);
    }
    
    // 4. Lấy kết quả
    $result_set = $stmt->get_result();
    $result = $result_set->fetch_assoc();
    
    // Đóng câu lệnh sau khi hoàn tất
    $stmt->close();
    
    // Nếu quá trình kiểm tra thành công
    $response['success'] = true;

    // Nếu có ít nhất một bản ghi được tìm thấy (count > 0)
    if ($result && $result['count'] > 0) {
        $response['in_use'] = true;
        $response['message'] = 'Công ty đang được sử dụng trong ' . $result['count'] . ' báo giá.';
    } else {
        // Nếu không tìm thấy bản ghi nào
        $response['in_use'] = false;
        $response['message'] = 'Công ty an toàn để xóa.';
    }

} catch (Exception $e) {
    // Xử lý nếu có lỗi xảy ra
    http_response_code(500); // Internal Server Error
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Đóng kết nối
$conn->close();

// Trả về kết quả dưới dạng JSON
echo json_encode($response);
?>
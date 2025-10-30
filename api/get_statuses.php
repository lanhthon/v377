<?php
// api/get_statuses.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Đảm bảo đường dẫn này chính xác

global $conn;

if (!isset($conn) || $conn->connect_error) {
    // Trả về lỗi nếu không kết nối được CSDL
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

try {
    // Câu lệnh SQL để lấy các giá trị 'TrangThai' duy nhất, không rỗng
    $sql = "SELECT DISTINCT TrangThai FROM donhang WHERE TrangThai IS NOT NULL AND TrangThai != '' ORDER BY TrangThai ASC";
    
    $result = $conn->query($sql);

    $statuses = [];
    if ($result) {
        // Lặp qua kết quả và thêm vào mảng
        while ($row = $result->fetch_assoc()) {
            $statuses[] = $row['TrangThai'];
        }
    }

    // Trả về danh sách trạng thái dưới dạng JSON
    echo json_encode(['success' => true, 'statuses' => $statuses]);

} catch (Exception $e) {
    // Ghi lại lỗi và trả về thông báo lỗi chung
    error_log("Lỗi trong get_statuses.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi phía máy chủ khi lấy danh sách trạng thái.']);
}

// Đóng kết nối
$conn->close();
?>

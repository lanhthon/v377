<?php
// api/get_transaction_types.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Đảm bảo đường dẫn này chính xác

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Câu lệnh SQL để lấy các loại giao dịch duy nhất từ bảng lịch sử
    $sql = "SELECT DISTINCT LoaiGiaoDich FROM lichsunhapxuat WHERE LoaiGiaoDich IS NOT NULL ORDER BY LoaiGiaoDich ASC";

    // Sử dụng kết nối từ file config của bạn, giả sử là $conn
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $transactionTypes = [];
    while ($row = $result->fetch_assoc()) {
        $transactionTypes[] = $row['LoaiGiaoDich'];
    }

    $stmt->close();

    $response['success'] = true;
    $response['data'] = $transactionTypes;
    $response['message'] = 'Tải danh sách loại giao dịch thành công.';

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi truy vấn CSDL: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

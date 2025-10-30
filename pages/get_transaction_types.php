<?php
// api/get_transaction_types.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Query to get distinct, non-empty transaction types from the history table
    $sql = "SELECT DISTINCT LoaiGiaoDich FROM lichsunhapxuat WHERE LoaiGiaoDich IS NOT NULL AND LoaiGiaoDich != '' ORDER BY LoaiGiaoDich ASC";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $types = [];
    while ($row = $result->fetch_assoc()) {
        $types[] = $row['LoaiGiaoDich'];
    }

    $response['success'] = true;
    $response['data'] = $types;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi server: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

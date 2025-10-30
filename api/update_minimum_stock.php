<?php
// api/update_minimum_stock.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// Get data from JSON body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    $response['message'] = 'Dữ liệu không hợp lệ.';
    echo json_encode($response);
    exit;
}

// Assign variables and validate
$variant_id = $data['variant_id'] ?? null;
$new_min_stock = $data['new_min_stock'] ?? null;
$userID = $_SESSION['user']['UserID'] ?? 0;

if ($variant_id === null || $new_min_stock === null || !is_numeric($new_min_stock)) {
    http_response_code(400);
    $response['message'] = 'Vui lòng cung cấp đầy đủ thông tin (variant_id, new_min_stock).';
    echo json_encode($response);
    exit;
}

$new_min_stock = (int)$new_min_stock;
if ($new_min_stock < 0) {
    http_response_code(400);
    $response['message'] = 'Định mức tối thiểu không thể là số âm.';
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();

try {
    // Use INSERT...ON DUPLICATE KEY UPDATE to handle products that might not have an inventory record yet
    $stmt = $conn->prepare("
        INSERT INTO variant_inventory (variant_id, minimum_stock_level) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE minimum_stock_level = ?
    ");
    $stmt->bind_param("iii", $variant_id, $new_min_stock, $new_min_stock);

    if ($stmt->execute()) {
        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Cập nhật định mức tối thiểu thành công!';
    } else {
        throw new Exception("Lỗi khi cập nhật CSDL: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    $response['message'] = 'Lỗi giao dịch: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>

<?php
/**
 * File: api/get_lsx_statuses.php
 * Description: API để lấy danh sách các trạng thái LSX duy nhất từ CSDL
 * dựa trên loại tab (đang xử lý hoặc đã hoàn thành).
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

global $conn;

// Lấy loại tab (inprogress hoặc completed) từ request
$type = $_GET['type'] ?? 'inprogress';

$response = ['success' => false, 'statuses' => [], 'message' => ''];

try {
    if ($conn->connect_error) {
        throw new Exception('Lỗi kết nối CSDL: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    $status_group = "";
    if ($type === 'inprogress') {
        $status_group = "('Chờ duyệt', 'Đã duyệt (đang sx)')";
    } elseif ($type === 'completed') {
        $status_group = "('Hoàn thành', 'Hủy')";
    } else {
        throw new Exception('Loại trạng thái không hợp lệ.');
    }

    // Truy vấn các trạng thái duy nhất dựa trên nhóm
    $query = "SELECT DISTINCT TrangThai FROM lenh_san_xuat WHERE TrangThai IN $status_group ORDER BY TrangThai ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Lỗi truy vấn lấy trạng thái: ' . $conn->error);
    }
    
    $statuses = [];
    while ($row = $result->fetch_assoc()) {
        $statuses[] = $row['TrangThai'];
    }
    
    $response['success'] = true;
    $response['statuses'] = $statuses;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
    error_log("Lỗi trong get_lsx_statuses.php: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>

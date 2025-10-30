<?php
// File: api/delete_dinh_muc.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data) || !isset($data['id'])) {
    $response['message'] = 'Dữ liệu không hợp lệ hoặc thiếu ID.';
    echo json_encode($response);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM dinh_muc_cat WHERE DinhMucID = ?");
    $stmt->bind_param("i", $data['id']);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Xóa định mức thành công!';
    } else {
        $response['message'] = 'Lỗi khi xóa: ' . $stmt->error;
    }
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
<?php
// File: api/delete_dinh_muc_dong_thung.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    $response['message'] = 'Thiếu ID của định mức.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM bang_dinh_muc_dong_thung WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Xóa định mức thành công!';
    } else {
        http_response_code(500);
        $response['message'] = 'Lỗi khi xóa định mức: ' . $stmt->error;
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
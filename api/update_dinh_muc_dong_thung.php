<?php
// File: api/update_dinh_muc_dong_thung.php

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
    $stmt = $conn->prepare("UPDATE bang_dinh_muc_dong_thung SET duong_kinh_trong = ?, ban_rong = ?, do_day = ?, loai_thung = ?, so_luong = ? WHERE id = ?");
    $stmt->bind_param("iiisii", $data['duong_kinh_trong'], $data['ban_rong'], $data['do_day'], $data['loai_thung'], $data['so_luong'], $data['id']);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Cập nhật định mức thành công!';
        } else {
            $response['success'] = true; 
            $response['message'] = 'Không có thay đổi nào được ghi nhận.';
        }
    } else {
        http_response_code(409); // Conflict
        if ($conn->errno == 1062) {
             $response['message'] = 'Lỗi: Cập nhật thất bại, định mức với các thông số này đã tồn tại.';
        } else {
            $response['message'] = 'Lỗi khi cập nhật định mức: ' . $stmt->error;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
<?php
// File: api/add_dinh_muc_dong_thung.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => null, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào
if (empty($data['duong_kinh_trong']) || empty($data['ban_rong']) || empty($data['do_day']) || empty($data['loai_thung']) || !isset($data['so_luong'])) {
    http_response_code(400);
    $response['message'] = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO bang_dinh_muc_dong_thung (duong_kinh_trong, ban_rong, do_day, loai_thung, so_luong) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisi", $data['duong_kinh_trong'], $data['ban_rong'], $data['do_day'], $data['loai_thung'], $data['so_luong']);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $response['success'] = true;
        $response['message'] = 'Thêm định mức thành công!';
        
        $selectStmt = $conn->prepare("SELECT * FROM bang_dinh_muc_dong_thung WHERE id = ?");
        $selectStmt->bind_param("i", $newId);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
        $response['data'] = $result->fetch_assoc();
        $selectStmt->close();
        
    } else {
        http_response_code(409); // Conflict
        if ($conn->errno == 1062) { // 1062 là mã lỗi cho duplicate entry
             $response['message'] = 'Lỗi: Định mức với các thông số này đã tồn tại.';
        } else {
            $response['message'] = 'Lỗi khi thêm định mức: ' . $stmt->error;
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
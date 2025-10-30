<?php
// File: api/update_dinh_muc.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data) || !isset($data['DinhMucID'])) {
    $response['message'] = 'Dữ liệu không hợp lệ hoặc thiếu ID.';
    echo json_encode($response);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE dinh_muc_cat SET TenNhomDN=?, HinhDang=?, MinDN=?, MaxDN=?, BanRong=?, SoBoTrenCay=? WHERE DinhMucID=?");
    $stmt->bind_param(
        "ssiiiii",
        $data['TenNhomDN'],
        $data['HinhDang'],
        $data['MinDN'],
        $data['MaxDN'],
        $data['BanRong'],
        $data['SoBoTrenCay'],
        $data['DinhMucID']
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Cập nhật định mức thành công!';
    } else {
        $response['message'] = 'Lỗi khi cập nhật: ' . $stmt->error;
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
<?php
// File: api/add_dinh_muc.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data) || !isset($data['TenNhomDN'])) {
    $response['message'] = 'Dữ liệu không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO dinh_muc_cat (TenNhomDN, HinhDang, MinDN, MaxDN, BanRong, SoBoTrenCay) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "ssiiii",
        $data['TenNhomDN'],
        $data['HinhDang'],
        $data['MinDN'],
        $data['MaxDN'],
        $data['BanRong'],
        $data['SoBoTrenCay']
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Thêm định mức thành công!';
        $new_id = $stmt->insert_id;
        // Trả về dữ liệu vừa thêm để cập nhật bảng
        $response['data'] = [
            'DinhMucID' => $new_id,
            'TenNhomDN' => $data['TenNhomDN'],
            'HinhDang' => $data['HinhDang'],
            'MinDN' => (int)$data['MinDN'],
            'MaxDN' => (int)$data['MaxDN'],
            'BanRong' => (int)$data['BanRong'],
            'SoBoTrenCay' => (int)$data['SoBoTrenCay'],
        ];
    } else {
        $response['message'] = 'Lỗi khi thêm định mức: ' . $stmt->error;
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
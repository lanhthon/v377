<?php
// File: api/get_dinh_muc.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $sql = "SELECT DinhMucID, TenNhomDN, HinhDang, MinDN, MaxDN, BanRong, SoBoTrenCay FROM dinh_muc_cat ORDER BY DinhMucID DESC";
    $result = $conn->query($sql);
    
    $data = [];
    if ($result) {
        $data = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $response['success'] = true;
    $response['data'] = $data;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi truy vấn CSDL: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
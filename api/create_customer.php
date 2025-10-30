<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => 'Invalid data provided.'];

if (isset($data['TenCongTy'])) {
    $sql = "INSERT INTO khachhang (TenCongTy, NguoiLienHe, SoDienThoai, SoFax, SoDiDong, Email, DiaChi, MaSoThue, CoCheGiaID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $coCheGiaID = !empty($data['CoCheGiaID']) ? $data['CoCheGiaID'] : null;
        
        $stmt->bind_param(
            "ssssssssi",
            $data['TenCongTy'],
            $data['NguoiLienHe'],
            $data['SoDienThoai'],
            $data['SoFax'],
            $data['SoDiDong'],
            $data['Email'],
            $data['DiaChi'],
            $data['MaSoThue'],
            $coCheGiaID
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Customer created successfully!';
        } else {
            http_response_code(500);
            $response['message'] = 'Execute failed: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        http_response_code(500);
        $response['message'] = 'Prepare failed: ' . $conn->error;
    }
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
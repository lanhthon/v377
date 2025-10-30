<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => 'Dữ liệu không hợp lệ.'];

if (!empty($data['MaCongTy']) && !empty($data['TenCongTy'])) {
    // Kiểm tra xem Mã hoặc Tên công ty đã tồn tại chưa
    $checkSql = "SELECT CongTyID FROM congty WHERE MaCongTy = ? OR TenCongTy = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $data['MaCongTy'], $data['TenCongTy']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $response['message'] = 'Mã hoặc Tên công ty đã tồn tại.';
    } else {
        $sql = "INSERT INTO congty (MaCongTy, TenCongTy) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "ss",
                $data['MaCongTy'],
                $data['TenCongTy']
            );

            if ($stmt->execute()) {
                $newCompanyId = $stmt->insert_id;
                $response['success'] = true;
                $response['message'] = 'Thêm khách hàng thành công!';
                // Trả về thông tin khách hàng vừa tạo để JS cập nhật
                $response['newCustomer'] = [
                    'CongTyID' => $newCompanyId,
                    'MaCongTy' => $data['MaCongTy'],
                    'TenCongTy' => $data['TenCongTy'],
                    'DiaChi' => '',
                    'SoDiDong' => '',
                    'TenNguoiLienHe' => ''
                ];
            } else {
                http_response_code(500);
                $response['message'] = 'Lỗi thực thi: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            http_response_code(500);
            $response['message'] = 'Lỗi chuẩn bị câu lệnh: ' . $conn->error;
        }
    }
    $checkStmt->close();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
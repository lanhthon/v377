<?php
// File: api/manage_customer.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
    exit;
}

$action = $data['action'];
$conn->begin_transaction();

try {
    switch ($action) {
        case 'add':
            $sql = "INSERT INTO khachhang (TenCongTy, NguoiLienHe, SoDienThoai, SoFax, SoDiDong, Email, DiaChi, MaSoThue, CoCheGiaID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssi", $data['TenCongTy'], $data['NguoiLienHe'], $data['SoDienThoai'], $data['SoFax'], $data['SoDiDong'], $data['Email'], $data['DiaChi'], $data['MaSoThue'], $data['CoCheGiaID']);
            $stmt->execute();
            $message = 'Thêm khách hàng thành công.';
            break;

        case 'update':
            $sql = "UPDATE khachhang SET TenCongTy = ?, NguoiLienHe = ?, SoDienThoai = ?, SoFax = ?, SoDiDong = ?, Email = ?, DiaChi = ?, MaSoThue = ?, CoCheGiaID = ? WHERE KhachHangID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssii", $data['TenCongTy'], $data['NguoiLienHe'], $data['SoDienThoai'], $data['SoFax'], $data['SoDiDong'], $data['Email'], $data['DiaChi'], $data['MaSoThue'], $data['CoCheGiaID'], $data['KhachHangID']);
            $stmt->execute();
             $message = 'Cập nhật khách hàng thành công.';
            break;

        case 'delete':
            $sql = "DELETE FROM khachhang WHERE KhachHangID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $data['KhachHangID']);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                 throw new Exception('Không tìm thấy khách hàng để xóa.');
            }
            $message = 'Xóa khách hàng thành công.';
            break;

        default:
            throw new Exception('Hành động không được hỗ trợ.');
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $conn->rollback();
    $errorMessage = $e->getMessage();
    if ($e->getCode() == 1451) {
        $errorMessage = 'Không thể xóa khách hàng này vì đã có báo giá liên quan.';
    }
    if ($e->getCode() == 1062) {
        $errorMessage = 'Tên công ty hoặc thông tin định danh khác đã tồn tại.';
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}

$conn->close();
?>
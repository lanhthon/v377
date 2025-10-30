<?php
// api/update_company.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Đảm bảo đường dẫn đến file kết nối CSDL là chính xác

$response = [
    'success' => false,
    'message' => 'Đã có lỗi xảy ra.'
];

// Lấy dữ liệu từ body của request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Dữ liệu gửi lên không hợp lệ.';
    echo json_encode($response);
    exit;
}

// Lấy và làm sạch dữ liệu đầu vào
$congTyID = $input['CongTyID'] ?? null;
$maCongTy = trim($input['MaCongTy'] ?? '');
$tenCongTy = trim($input['TenCongTy'] ?? '');
$diaChi = $input['DiaChi'] ?? null;
$website = $input['Website'] ?? null;
$maSoThue = $input['MaSoThue'] ?? null;
$soDienThoai = $input['SoDienThoaiChinh'] ?? null;
$coCheGiaID = !empty($input['CoCheGiaID']) ? (int)$input['CoCheGiaID'] : null;
$nhomKhachHang = $input['NhomKhachHang'] ?? 'Tiềm năng'; 
// ===== BẮT ĐẦU MÃ MỚI =====
$soNgayThanhToan = isset($input['SoNgayThanhToan']) && is_numeric($input['SoNgayThanhToan']) ? (int)$input['SoNgayThanhToan'] : 30; // Mặc định là 30 nếu không có hoặc không phải là số
// ===== KẾT THÚC MÃ MỚI =====


// Kiểm tra các trường bắt buộc
if (empty($congTyID) || empty($maCongTy) || empty($tenCongTy)) {
    http_response_code(400);
    $response['message'] = 'ID, Mã công ty và Tên công ty không được để trống.';
    echo json_encode($response);
    exit;
}

try {
    // 1. KIỂM TRA MÃ CÔNG TY TRÙNG LẶP VỚI CÁC CÔNG TY KHÁC
    $sql_check = "SELECT COUNT(*) FROM congty WHERE MaCongTy = ? AND CongTyID != ?";
    $stmt_check = $conn->prepare($sql_check);
    if ($stmt_check === false) throw new Exception('Lỗi khi chuẩn bị câu lệnh kiểm tra.');

    $stmt_check->bind_param("si", $maCongTy, $congTyID);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        http_response_code(409); // Conflict
        $response['message'] = 'Mã công ty "' . htmlspecialchars($maCongTy) . '" đã tồn tại ở một công ty khác. Vui lòng chọn một mã khác.';
        echo json_encode($response);
        exit;
    }
    
    // 2. CẬP NHẬT THÔNG TIN CÔNG TY
    // SỬA: Thêm SoNgayThanhToan = ? vào câu lệnh SQL
    $sql_update = "UPDATE congty SET 
                    MaCongTy = ?, 
                    TenCongTy = ?, 
                    DiaChi = ?, 
                    Website = ?,
                    MaSoThue = ?, 
                    SoDienThoaiChinh = ?, 
                    CoCheGiaID = ?, 
                    NhomKhachHang = ?,
                    SoNgayThanhToan = ?
                   WHERE CongTyID = ?";
                   
    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update === false) throw new Exception('Lỗi khi chuẩn bị câu lệnh cập nhật.');
    
    // SỬA: Thêm biến $soNgayThanhToan và kiểu 'i' vào bind_param
    // Kiểu dữ liệu sẽ là "ssssssisii"
    $stmt_update->bind_param("ssssssisii", 
        $maCongTy, 
        $tenCongTy, 
        $diaChi, 
        $website,
        $maSoThue, 
        $soDienThoai, 
        $coCheGiaID, 
        $nhomKhachHang, 
        $soNgayThanhToan, // Biến mới
        $congTyID
    );
    
    if ($stmt_update->execute()) {
        $response['success'] = true;
        $response['message'] = 'Cập nhật thông tin công ty thành công!';
    } else {
        throw new Exception('Không thể cập nhật thông tin công ty.');
    }
    $stmt_update->close();

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
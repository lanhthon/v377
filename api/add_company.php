<?php
// api/add_company.php

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
$maCongTy = trim($input['MaCongTy'] ?? '');
$tenCongTy = trim($input['TenCongTy'] ?? '');
$diaChi = $input['DiaChi'] ?? null;
$website = $input['Website'] ?? null; 
$maSoThue = $input['MaSoThue'] ?? null;
$soDienThoai = $input['SoDienThoaiChinh'] ?? null;
$coCheGiaID = !empty($input['CoCheGiaID']) ? (int)$input['CoCheGiaID'] : null;
$nhomKhachHang = trim($input['NhomKhachHang'] ?? '');
// ===== BẮT ĐẦU MÃ MỚI =====
$soNgayThanhToan = isset($input['SoNgayThanhToan']) && is_numeric($input['SoNgayThanhToan']) ? (int)$input['SoNgayThanhToan'] : 30; // Mặc định là 30
// ===== KẾT THÚC MÃ MỚI =====


// Nếu không có nhóm khách hàng được gửi lên, mặc định là "Tiềm năng"
if (empty($nhomKhachHang)) {
    $nhomKhachHang = 'Tiềm năng';
}

// Kiểm tra các trường bắt buộc
if (empty($maCongTy) || empty($tenCongTy)) {
    http_response_code(400);
    $response['message'] = 'Mã công ty và Tên công ty không được để trống.';
    echo json_encode($response);
    exit;
}

try {
    // 1. KIỂM TRA MÃ CÔNG TY TRÙNG LẶP
    $sql_check = "SELECT COUNT(*) FROM congty WHERE MaCongTy = ?";
    $stmt_check = $conn->prepare($sql_check);
    if ($stmt_check === false) throw new Exception('Lỗi khi chuẩn bị câu lệnh kiểm tra.');
    
    $stmt_check->bind_param("s", $maCongTy);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        // Nếu mã đã tồn tại, trả về lỗi
        http_response_code(409); // Conflict
        $response['message'] = 'Mã công ty "' . htmlspecialchars($maCongTy) . '" đã tồn tại. Vui lòng chọn một mã khác.';
        echo json_encode($response);
        exit;
    }

    // 2. THÊM MỚI CÔNG TY
    // SỬA: Thêm cột SoNgayThanhToan và placeholder '?' tương ứng
    $sql_insert = "INSERT INTO congty (MaCongTy, TenCongTy, DiaChi, Website, MaSoThue, SoDienThoaiChinh, CoCheGiaID, NhomKhachHang, SoNgayThanhToan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    if ($stmt_insert === false) throw new Exception('Lỗi khi chuẩn bị câu lệnh thêm mới.');

    // SỬA: Thêm biến $soNgayThanhToan và kiểu 'i' vào bind_param
    // Kiểu dữ liệu sẽ là "ssssssisi"
    $stmt_insert->bind_param("ssssssisi", 
        $maCongTy, 
        $tenCongTy, 
        $diaChi, 
        $website,
        $maSoThue, 
        $soDienThoai, 
        $coCheGiaID,
        $nhomKhachHang,
        $soNgayThanhToan // Biến mới
    );
    
    if ($stmt_insert->execute()) {
        $response['success'] = true;
        $response['message'] = 'Thêm công ty mới thành công!';
        $response['new_id'] = $conn->insert_id;
    } else {
        throw new Exception('Không thể thêm công ty vào CSDL.');
    }
    $stmt_insert->close();

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
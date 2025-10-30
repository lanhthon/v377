<?php
// api/get_daily_production_report.php

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Lỗi xác thực: Bạn cần đăng nhập.']);
    exit();
}

// 2. KẾT NỐI CSDL
require_once '../config/database.php';

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    $errorMessage = isset($conn) ? $conn->connect_error : 'Biến $conn không được định nghĩa.';
    echo json_encode(['success' => false, 'message' => 'Lỗi cấu hình: Không thể kết nối đến cơ sở dữ liệu.', 'debug_info' => $errorMessage]);
    exit();
}

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // 3. LẤY VÀ KIỂM TRA THAM SỐ
    $startDate = $_GET['start_date'] ?? date('Y-m-d');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $startDateObj = DateTime::createFromFormat('Y-m-d', $startDate);
    $endDateObj = DateTime::createFromFormat('Y-m-d', $endDate);

    if (!$startDateObj || !$endDateObj) {
        throw new Exception("Định dạng ngày không hợp lệ. Vui lòng dùng định dạng YYYY-MM-DD.");
    }
    
    // 4. TRUY VẤN CSDL VỚI CẤU TRÚC BẢNG ĐÚNG
    // [ĐÃ SỬA] Thay thế JOIN san_pham bằng JOIN variants
    $sql = "
        SELECT 
            nk.NgayBaoCao,
            lsx.SoLenhSX,
            v.variant_sku AS MaBTP,       -- [ĐÃ SỬA] Lấy từ bảng variants
            v.variant_name AS TenBTP,    -- [ĐÃ SỬA] Lấy từ bảng variants
            nk.SoLuongHoanThanh,
            u.HoTen AS NguoiThucHien,
            nk.GhiChu AS GhiChuNhatKy
        FROM nhat_ky_san_xuat nk
        JOIN chitiet_lenh_san_xuat clsx ON nk.ChiTiet_LSX_ID = clsx.ChiTiet_LSX_ID
        JOIN lenh_san_xuat lsx ON clsx.LenhSX_ID = lsx.LenhSX_ID
        JOIN variants v ON clsx.SanPhamID = v.variant_id -- [ĐÃ SỬA] Join với variants qua variant_id
        LEFT JOIN nguoidung u ON nk.NguoiThucHien_ID = u.UserID
        WHERE nk.NgayBaoCao BETWEEN ? AND ?
        ORDER BY nk.NgayBaoCao DESC, lsx.SoLenhSX ASC
    ";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi cú pháp SQL: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $response['data'] = $data;
    $response['success'] = true;
    
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi: ' . $e->getMessage();
}

// 5. ĐÓNG KẾT NỐI VÀ TRẢ VỀ KẾT QUẢ
$conn->close();
echo json_encode($response);
?>
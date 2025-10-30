<?php
/**
 * File: api/get_ycsx_details.php
 * API để lấy thông tin chi tiết của một YCSX để hiển thị hoặc in.
 * PHIÊN BẢN NÂNG CẤP VỚI BÁO CÁO LỖI CHI TIẾT
 */

// BẬT HIỂN THỊ LỖI ĐẦY ĐỦ ĐỂ CHẨN ĐOÁN SỰ CỐ
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Kiểm tra xem ID có được cung cấp và có phải là số nguyên hợp lệ không
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp ID YCSX hợp lệ.']);
    exit;
}

$ycsxID = (int)$_GET['id'];

// Cấu trúc phản hồi JSON
$response = [
    'success' => false,
    'ycsx' => [
        'info' => null,
        'items' => []
    ]
];

try {
    // Lấy thông tin chung của YCSX và thông tin khách hàng từ báo giá liên quan
    $sql_info = "
        SELECT 
            y.YCSX_ID, y.SoYCSX, y.NgayTao, y.TrangThai,
            b.TenCongTy, b.NguoiNhan, b.Email, b.SoDiDongKhach, b.DiaChiGiaoHang, b.ThoiGianGiaoHang
        FROM yeucausanxuat y
        JOIN baogia b ON y.BaoGiaID = b.BaoGiaID
        WHERE y.YCSX_ID = ?
    ";
    $stmt_info = $conn->prepare($sql_info);
    if ($stmt_info === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh (prepare statement) cho thông tin YCSX: " . $conn->error);
    }
    $stmt_info->bind_param("i", $ycsxID);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();
    
    if ($result_info->num_rows > 0) {
        $response['ycsx']['info'] = $result_info->fetch_assoc();
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy YCSX với ID: ' . $ycsxID . '. Có thể do YCSX này liên kết với một Báo giá đã bị xóa.']);
        $conn->close();
        exit;
    }
    $stmt_info->close();

    // Lấy chi tiết các sản phẩm từ bảng chitiet_ycsx
    $sql_items = "
        SELECT * FROM chitiet_ycsx
        WHERE YCSX_ID = ?
        ORDER BY ThuTuHienThi ASC, ChiTiet_YCSX_ID ASC
    ";
    $stmt_items = $conn->prepare($sql_items);
     if ($stmt_items === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh (prepare statement) cho chi tiết YCSX: " . $conn->error);
    }
    $stmt_items->bind_param("i", $ycsxID);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    while($item = $result_items->fetch_assoc()) {
        $response['ycsx']['items'][] = $item;
    }
    $stmt_items->close();

    // Nếu mọi thứ thành công, đặt cờ success thành true
    $response['success'] = true;
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    // Trả về lỗi chi tiết hơn
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi Server: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString() // Thêm trace để debug
    ]);
}

// Đóng kết nối CSDL
$conn->close();
?>
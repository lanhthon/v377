<?php
/**
 * File: api/get_chi_tiet_dinh_muc.php
 * Version: 2.1 - Sửa lỗi không tương thích dữ liệu với frontend
 * Description: API lấy danh sách định mức đóng thùng cho sản phẩm PUR.
 * - [CẬP NHẬT V2.1] Gỡ bỏ logic nhóm (pivot) dữ liệu để trả về cấu trúc phẳng,
 * giúp tương thích với định nghĩa cột của Tabulator ở frontend.
 * Mỗi dòng tương ứng với một loại thùng (Thùng nhỏ hoặc Thùng to).
 */

header('Content-Type: application/json; charset=utf-8');
// Giả định 'database.php' cung cấp biến kết nối $conn (mysqli)
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    if ($conn->connect_error) {
        throw new Exception("Lỗi kết nối CSDL: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");

    // Lấy dữ liệu thô từ database, bao gồm cả 'id' để phục vụ cho việc xóa/sửa.
    // Dữ liệu không cần xử lý pivot nữa.
    $sql = "SELECT id, duong_kinh_trong, ban_rong, do_day, loai_thung, so_luong 
            FROM bang_dinh_muc_dong_thung 
            ORDER BY duong_kinh_trong, ban_rong, do_day, loai_thung";
    
    $result = $conn->query($sql);
    
    $data = [];
    if ($result) {
        // Lấy tất cả các dòng dưới dạng mảng kết hợp
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
    
    $response['success'] = true;
    $response['data'] = $data;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Đóng kết nối
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
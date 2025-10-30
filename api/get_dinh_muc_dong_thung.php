<?php
// File: api/get_dinh_muc_dong_thung.php
// Version: 2.0 - Sửa lỗi xử lý dữ liệu định mức
// Description: API lấy và xử lý định mức đóng thùng cho sản phẩm PUR.
// - [CẬP NHẬT V2.0] Viết lại logic để nhóm (pivot) dữ liệu định mức 'Thùng nhỏ' và 'Thùng to'
//   vào cùng một đối tượng cho mỗi sản phẩm, đúng với định dạng mà frontend yêu cầu.

header('Content-Type: application/json; charset=utf-8');
// Giả định 'database.php' cung cấp biến kết nối $conn (mysqli)
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    if ($conn->connect_error) {
        throw new Exception("Lỗi kết nối CSDL: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");

    // Lấy dữ liệu thô từ database
    $sql = "SELECT duong_kinh_trong, ban_rong, do_day, loai_thung, so_luong 
            FROM bang_dinh_muc_dong_thung 
            ORDER BY duong_kinh_trong, ban_rong, do_day";
    $result = $conn->query($sql);
    
    $rows = [];
    if ($result) {
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
    
    // Xử lý (pivot) dữ liệu để nhóm theo sản phẩm
    $processedData = [];
    foreach ($rows as $row) {
        // Tạo một khóa duy nhất cho mỗi combination kích thước
        $key = $row['duong_kinh_trong'] . '-' . $row['ban_rong'] . '-' . $row['do_day'];
        
        // Nếu chưa có, tạo một mục mới
        if (!isset($processedData[$key])) {
            $processedData[$key] = [
                'duong_kinh_trong' => (int)$row['duong_kinh_trong'],
                'ban_rong' => (int)$row['ban_rong'],
                'do_day' => (int)$row['do_day'],
                'so_luong_thung_nho' => 0,
                'so_luong_thung_to' => 0
            ];
        }

        // Gán số lượng vào đúng loại thùng
        if (mb_strtolower(trim($row['loai_thung']), 'UTF-8') === 'thùng nhỏ') {
            $processedData[$key]['so_luong_thung_nho'] = (int)$row['so_luong'];
        } elseif (mb_strtolower(trim($row['loai_thung']), 'UTF-8') === 'thùng to') {
            $processedData[$key]['so_luong_thung_to'] = (int)$row['so_luong'];
        }
    }

    $response['success'] = true;
    // Chuyển đổi mảng kết hợp thành mảng tuần tự để có định dạng JSON đúng
    $response['data'] = array_values($processedData);

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

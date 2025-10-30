<?php
// File: api/get_customer_report.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    // ---- SỬA ĐỔI Ở ĐÂY ----
    // Chúng ta JOIN bảng `baogia` (đặt tên là `b`) với bảng `congty` (đặt tên là `c`)
    // qua khóa ngoại `CongTyID`.
    // Sau đó, lấy `MaCongTy` từ bảng `c` và đổi tên nó thành `MaKhachHang` để JavaScript có thể sử dụng.
    $sql = "SELECT 
                c.TenCongTy, 
                c.MaCongTy AS MaKhachHang, 
                SUM(b.TongTienSauThue) as total_value 
            FROM baogia AS b
            JOIN congty AS c ON b.CongTyID = c.CongTyID
            WHERE b.TrangThai = 'Chốt' 
            GROUP BY c.CongTyID, c.TenCongTy, c.MaCongTy 
            ORDER BY total_value DESC 
            LIMIT 10";
            
    $result = $conn->query($sql);
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
$conn->close();
?>
<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $sql = "SELECT lsx.*, sp.MaHang, sp.TenSanPham 
            FROM lenhsanxuat lsx
            JOIN sanpham sp ON lsx.SanPhamID = sp.SanPhamID
            WHERE lsx.TrangThai != 'Đã hoàn thành'
            ORDER BY lsx.NgayHoanThanhDuKien ASC";
    
    $result = $conn->query($sql);
    $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}
$conn->close();
?>
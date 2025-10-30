<?php
// File: api/get_received_slips.php
// API này lấy danh sách các phiếu nhập kho đã được tạo để hiển thị trên trang danh sách.
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => []];

try {
    $sql = "SELECT 
                pnk.PhieuNhapKhoID,
                pnk.SoPhieuNhapKho,
                pnk.NgayNhap,
                ncc.TenNhaCungCap AS NhaCungCap,
                pnk.NguoiGiaoHang,
                pnk.LyDoNhap
            FROM phieunhapkho pnk
            LEFT JOIN nhacungcap ncc ON pnk.NhaCungCapID = ncc.NhaCungCapID
            ORDER BY pnk.NgayNhap DESC, pnk.PhieuNhapKhoID DESC";
    
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $response['success'] = true;
    $response['data'] = $data;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
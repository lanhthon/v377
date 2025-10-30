<?php
// File: api/get_phieunhapkho_vattu.php
// API này chỉ lấy danh sách các phiếu nhập kho VẬT TƯ.
require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'data' => []];

try {
    $pdo = get_db_connection();
    $sql = "SELECT 
                pnk.PhieuNhapKhoID, 
                pnk.SoPhieuNhapKho, 
                pnk.NgayNhap, 
                pnk.LyDoNhap, 
                pnk.TongTien, 
                ncc.TenNhaCungCap
            FROM phieunhapkho pnk
            LEFT JOIN nhacungcap ncc ON pnk.NhaCungCapID = ncc.NhaCungCapID
            -- ==========================================================
            -- ĐIỀU KIỆN LỌC QUAN TRỌNG:
            -- Dòng này đảm bảo chỉ lấy các phiếu có loại là 'nhap_mua_hang' (Nhập vật tư).
            WHERE pnk.LoaiPhieu = 'nhap_mua_hang'
            -- ==========================================================
            ORDER BY pnk.NgayNhap DESC, pnk.PhieuNhapKhoID DESC";
            
    $stmt = $pdo->query($sql);
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;
} catch (PDOException $e) {
    $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
    error_log($e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

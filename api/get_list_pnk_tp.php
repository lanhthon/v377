<?php
// File: api/get_list_pnk_tp.php
// CẬP NHẬT: Lấy lịch sử các phiếu nhập kho thành phẩm, bao gồm cả nhập từ sản xuất và nhập ngoài.

require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $pdo = get_db_connection();
    $sql = "SELECT 
                pnk.PhieuNhapKhoID,
                pnk.SoPhieuNhapKho,
                pnk.NgayNhap,
                pnk.LyDoNhap,
                dh.SoYCSX,
                nd.HoTen AS TenNguoiTao
            FROM phieunhapkho pnk
            LEFT JOIN donhang dh ON pnk.YCSX_ID = dh.YCSX_ID
            LEFT JOIN nguoidung nd ON pnk.NguoiTaoID = nd.UserID
            -- THAY ĐỔI: Sử dụng IN() để lấy cả hai loại phiếu nhập thành phẩm
            WHERE pnk.LoaiPhieu IN ('nhap_tp_tu_sx', 'nhap_tp_khac')
            ORDER BY pnk.PhieuNhapKhoID DESC
            LIMIT 100";

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $data;
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
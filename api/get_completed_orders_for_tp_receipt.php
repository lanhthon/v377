<?php
// File: api/get_completed_orders_for_tp_receipt.php
require_once '../config/db_config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'data' => []];

try {
    $pdo = get_db_connection();
    // Lấy các đơn hàng (YCSX) đã hoàn thành nhưng chưa có phiếu nhập kho thành phẩm
    $sql = "SELECT 
                dh.YCSX_ID, 
                dh.SoYCSX, 
                dh.TenCongTy, 
                dh.TenDuAn,
                dh.NgayGiaoDuKien
            FROM donhang dh
            LEFT JOIN phieunhapkho pnk ON dh.YCSX_ID = pnk.YCSX_ID AND pnk.LoaiPhieu = 'nhap_tp_tu_sx'
            WHERE dh.TrangThai = 'Hoàn thành' AND pnk.PhieuNhapKhoID IS NULL
            ORDER BY dh.NgayGiaoDuKien ASC";

    $stmt = $pdo->query($sql);
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;

} catch (PDOException $e) {
    $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
}

echo json_encode($response);
?>

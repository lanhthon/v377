<?php
// File: api/get_data_for_delivery_plan.php
require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => '', 'data' => null];

$donhang_id = isset($_GET['donhang_id']) ? intval($_GET['donhang_id']) : 0;

if ($donhang_id === 0) {
    $response['message'] = 'ID đơn hàng không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $pdo = get_db_connection();

    // 1. Lấy thông tin cơ bản của đơn hàng
    $stmt_info = $pdo->prepare("SELECT SoYCSX FROM donhang WHERE YCSX_ID = ?");
    $stmt_info->execute([$donhang_id]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        throw new Exception("Không tìm thấy đơn hàng với ID được cung cấp.");
    }
    
    // 2. Lấy tất cả các sản phẩm trong đơn hàng và số lượng đã lên kế hoạch
    $sql_items = "
        SELECT 
            cd.ChiTiet_YCSX_ID,
            cd.MaHang,
            cd.TenSanPham,
            cd.SoLuong,
            (
                SELECT IFNULL(SUM(ck.SoLuongGiao), 0)
                FROM chitiet_kehoach_giaohang ck
                JOIN kehoach_giaohang kh ON ck.KHGH_ID = kh.KHGH_ID
                WHERE kh.DonHangID = cd.DonHangID AND ck.ChiTiet_DonHang_ID = cd.ChiTiet_YCSX_ID
            ) as SoLuongDaLenKeHoach
        FROM chitiet_donhang cd
        WHERE cd.DonHangID = ?
        ORDER BY cd.ThuTuHienThi
    ";
    
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$donhang_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = ['info' => $info, 'items' => $items];

} catch (Exception $e) {
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

echo json_encode($response);
?>
<?php
// File: api/get_order_details_for_tp_receipt.php
require_once '../config/db_config.php';
header('Content-Type: application/json');

$ycsx_id = isset($_GET['ycsx_id']) ? intval($_GET['ycsx_id']) : 0;
$response = ['success' => false, 'header' => null, 'items' => []];

if ($ycsx_id === 0) {
    $response['message'] = 'ID YCSX không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $pdo = get_db_connection();

    $sql_header = "SELECT SoYCSX FROM donhang WHERE YCSX_ID = :ycsx_id";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([':ycsx_id' => $ycsx_id]);
    $response['header'] = $stmt_header->fetch(PDO::FETCH_ASSOC);

    $sql_items = "SELECT 
                    ct.SanPhamID as variant_id,
                    ct.MaHang,
                    ct.TenSanPham,
                    'Bộ' AS DonViTinh, -- Giả định đơn vị tính là Bộ
                    ct.SoLuong
                FROM chitiet_donhang ct
                WHERE ct.DonHangID = :ycsx_id";

    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([':ycsx_id' => $ycsx_id]);
    $response['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;

} catch (PDOException $e) {
    $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
}

echo json_encode($response);
?>

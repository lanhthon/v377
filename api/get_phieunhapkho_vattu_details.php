<?php
// File: api/get_phieunhapkho_vattu_details.php
require_once '../config/db_config.php';
header('Content-Type: application/json');

$pnk_id = isset($_GET['pnk_id']) ? intval($_GET['pnk_id']) : 0;
$response = ['success' => false, 'data' => null];

if ($pnk_id === 0) {
    $response['message'] = 'ID Phiếu nhập không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $pdo = get_db_connection();
    
    $sql_header = "SELECT * FROM phieunhapkho WHERE PhieuNhapKhoID = ?";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$pnk_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    $sql_items = "SELECT ctpnk.*, v.variant_sku AS MaHang, v.variant_name AS TenSanPham
                  FROM chitietphieunhapkho ctpnk
                  JOIN variants v ON ctpnk.SanPhamID = v.variant_id
                  WHERE ctpnk.PhieuNhapKhoID = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$pnk_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $response['data'] = ['header' => $header, 'items' => $items];
    $response['success'] = true;

} catch (PDOException $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>

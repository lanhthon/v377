<?php
// File: api/get_issued_slip_btp_details.php
// CẬP NHẬT: Sửa lỗi không hiển thị Mã Hàng cho phiếu xuất ngoài.
require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$pxk_id = isset($_GET['pxk_id']) ? intval($_GET['pxk_id']) : 0;
$response = ['success' => false, 'data' => null];

if ($pxk_id === 0) {
    $response['message'] = 'ID Phiếu xuất kho không hợp lệ.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = get_db_connection();
    
    // Lấy thông tin header của phiếu
    $sql_header = "SELECT 
                       pxk.SoPhieuXuat, 
                       pxk.NgayXuat, 
                       pxk.NguoiNhan,
                       pxk.GhiChu,
                       dh.SoYCSX, 
                       nd.HoTen AS NguoiLap
                   FROM phieuxuatkho pxk
                   LEFT JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID
                   LEFT JOIN nguoidung nd ON pxk.NguoiTaoID = nd.UserID
                   WHERE pxk.PhieuXuatKhoID = ?";

    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$pxk_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        throw new Exception("Không tìm thấy phiếu xuất kho với ID: " . $pxk_id);
    }

    // THAY ĐỔI: Cập nhật câu lệnh SQL để lấy Mã Hàng từ bảng 'variants'
    $sql_items = "SELECT 
                    IFNULL(v.variant_sku, ct.MaHang) AS MaHang, -- Lấy mã từ bảng variants nếu có
                    IFNULL(v.variant_name, ct.TenSanPham) AS TenSanPham, 
                    ct.SoLuongThucXuat, 
                    u.name as DonViTinh
                FROM chitiet_phieuxuatkho ct
                LEFT JOIN variants v ON ct.SanPhamID = v.variant_id
                LEFT JOIN products p ON v.product_id = p.product_id
                LEFT JOIN units u ON p.base_unit_id = u.unit_id
                WHERE ct.PhieuXuatKhoID = ?";
                
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$pxk_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $response['data'] = ['header' => $header, 'items' => $items];
    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

<?php
/**
 * File: api/get_cbh_details_for_tp_receipt.php
 * Version: 3.2 - [FIX] SQL parameter binding error
 * Description: 
 * - ULA: Lấy từ LSX
 * - PUR: Lấy từ chitietchuanbihang (SoLuongCanSX > 0)
 */

require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'header' => null, 'items' => [], 'message' => ''];

$cbh_id = isset($_GET['cbh_id']) ? intval($_GET['cbh_id']) : 0;
$item_type = isset($_GET['type']) ? $_GET['type'] : 'all'; // 'ula' hoặc 'pur'

if ($cbh_id <= 0) {
    $response['message'] = 'ID Phiếu chuẩn bị hàng không hợp lệ.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = get_db_connection();

    // 1. Lấy thông tin header
    $stmt_header = $pdo->prepare("
        SELECT dh.SoYCSX, cbh.PhuTrach, cbh.TenCongTy
        FROM chuanbihang cbh 
        JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID 
        WHERE cbh.CBH_ID = ?
    ");
    $stmt_header->execute([$cbh_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        throw new Exception("Không tìm thấy phiếu chuẩn bị hàng.");
    }
    $response['header'] = $header;

    if ($item_type === 'ula') {
        // ⭐ ULA: LẤY TỪ LSX
        $sql_items = "
            SELECT 
                ct_lsx.SanPhamID as variant_id,
                v.variant_sku AS MaHang,
                v.variant_name AS TenSanPham,
                u.name AS DonViTinh,
                ct_lsx.SoLuongBoCanSX AS SoLuong,
                ct_lsx.SoLuongDaNhap
            FROM lenh_san_xuat lsx
            INNER JOIN chitiet_lenh_san_xuat ct_lsx ON lsx.LenhSX_ID = ct_lsx.LenhSX_ID
            LEFT JOIN variants v ON ct_lsx.SanPhamID = v.variant_id
            LEFT JOIN products p ON v.product_id = p.product_id
            LEFT JOIN units u ON p.base_unit_id = u.unit_id
            WHERE lsx.CBH_ID = ?
              AND lsx.LoaiLSX = 'ULA'
              AND lsx.TrangThai = 'Hoàn thành'
        ";
        
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute([$cbh_id]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as &$item) {
            $soLuongCanNhap = max(0, intval($item['SoLuong']) - intval($item['SoLuongDaNhap']));
            $item['SoLuongConLai'] = $soLuongCanNhap;
            unset($item['SoLuongDaNhap']);
        }
        
    } elseif ($item_type === 'pur') {
        // ⭐ PUR: LẤY TỪ CHITIETCHUANBIHANG (Chỉ sản phẩm PUR có SoLuongCanSX > 0)
        $sql_items = "
            SELECT 
                ct.SanPhamID as variant_id,
                v.variant_sku AS MaHang,
                v.variant_name AS TenSanPham,
                u.name AS DonViTinh,
                ct.SoLuongCanSX AS SoLuong,
                COALESCE(nhap.SoLuongDaNhap, 0) AS SoLuongDaNhap
            FROM chitietchuanbihang ct
            LEFT JOIN variants v ON ct.SanPhamID = v.variant_id
            LEFT JOIN products p ON v.product_id = p.product_id
            LEFT JOIN units u ON p.base_unit_id = u.unit_id
            LEFT JOIN (
                SELECT pnk_ct.SanPhamID, SUM(pnk_ct.SoLuong) AS SoLuongDaNhap
                FROM phieunhapkho pnk
                JOIN chitietphieunhapkho pnk_ct ON pnk.PhieuNhapKhoID = pnk_ct.PhieuNhapKhoID
                WHERE pnk.CBH_ID = ? AND pnk.LoaiPhieu = 'TP_PUR'
                GROUP BY pnk_ct.SanPhamID
            ) nhap ON ct.SanPhamID = nhap.SanPhamID
            WHERE ct.CBH_ID = ?
              AND ct.SoLuongCanSX > 0
              AND UPPER(v.variant_sku) LIKE 'PUR%'
        ";
        
        $stmt_items = $pdo->prepare($sql_items);
        // ⭐ FIX: Bind cả 2 tham số (subquery + main query)
        $stmt_items->execute([$cbh_id, $cbh_id]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as &$item) {
            $soLuongConNhap = max(0, intval($item['SoLuong']) - intval($item['SoLuongDaNhap']));
            $item['SoLuongConLai'] = $soLuongConNhap;
            unset($item['SoLuongDaNhap']);
        }
    } else {
        throw new Exception("Loại sản phẩm không hợp lệ (type phải là 'ula' hoặc 'pur').");
    }
    
    // Lọc bỏ các item đã nhập đủ
    $items = array_filter($items, function($item) {
        return $item['SoLuongConLai'] > 0;
    });
    
    $response['items'] = array_values($items);
    $response['success'] = true;
    
    if (empty($items)) {
        $loaiText = $item_type === 'ula' ? 'ULA' : 'PUR';
        $response['message'] = "Tất cả sản phẩm {$loaiText} đã được nhập kho đầy đủ.";
    }

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
    error_log("ERROR get_cbh_details_for_tp_receipt.php: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
<?php
/**
 * File: api/get_issued_slip_details.php
 * Version: 3.0
 * Description: API để xem chi tiết một phiếu xuất kho.
 * - Kết hợp logic fallback cho thông tin header.
 * - Kết hợp logic phân loại nhóm sản phẩm chi tiết.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

$response = ['success' => false, 'header' => null, 'items' => [], 'message' => ''];
$pxk_id = $_GET['pxk_id'] ?? 0;

if (!$pxk_id) {
    http_response_code(400);
    $response['message'] = 'Không có ID Phiếu Xuất Kho.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = get_db_connection();

    // 1. Lấy thông tin header của phiếu, bao gồm cả dữ liệu gốc để fallback
    $sql_header = "
        SELECT 
            pxk.YCSX_ID,
            pxk.SoPhieuXuat,
            pxk.NgayXuat,
            
            pxk.TenCongTy,
            pxk.DiaChiCongTy,
            pxk.NguoiNhan,
            pxk.DiaChiGiaoHang,
            pxk.LyDoXuatKho,
            pxk.NguoiLapPhieu,
            pxk.ThuKho,
            pxk.NguoiGiaoHang,
            pxk.NguoiNhanHang,
            
            bg.TenCongTy AS TenCongTyGoc,
            bg.DiaChiKhach AS DiaChiCongTyGoc,
            dh.NguoiNhan AS NguoiNhanGoc,
            bg.DiaChiGiaoHang AS DiaChiGiaoHangGoc,
            CONCAT('Theo đơn hàng số: ', IFNULL(dh.SoYCSX, '...')) AS LyDoXuatKhoGoc,
            dh.SoYCSX,
            nguoi_tao.HoTen AS TenNguoiLapGoc

        FROM phieuxuatkho pxk
        LEFT JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID
        LEFT JOIN baogia bg ON dh.BaoGiaID = bg.BaoGiaID
        LEFT JOIN nguoidung nguoi_tao ON pxk.NguoiTaoID = nguoi_tao.UserID
        WHERE pxk.PhieuXuatKhoID = :pxk_id
    ";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([':pxk_id' => $pxk_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        throw new Exception("Không tìm thấy phiếu xuất kho ID: " . $pxk_id);
    }
    
    // 2. Áp dụng logic ưu tiên (fallback) để tạo các trường 'HienThi'
    $header['TenCongTyHienThi'] = !empty($header['TenCongTy']) ? $header['TenCongTy'] : $header['TenCongTyGoc'];
    $header['DiaChiCongTyHienThi'] = !empty($header['DiaChiCongTy']) ? $header['DiaChiCongTy'] : $header['DiaChiCongTyGoc'];
    $header['NguoiNhanHienThi'] = !empty($header['NguoiNhan']) ? $header['NguoiNhan'] : $header['NguoiNhanGoc'];
    $header['DiaChiGiaoHangHienThi'] = !empty($header['DiaChiGiaoHang']) ? $header['DiaChiGiaoHang'] : $header['DiaChiGiaoHangGoc'];
    $header['LyDoXuatKhoHienThi'] = !empty($header['LyDoXuatKho']) ? $header['LyDoXuatKho'] : $header['LyDoXuatKhoGoc'];
    $header['NguoiLapPhieuHienThi'] = !empty($header['NguoiLapPhieu']) ? $header['NguoiLapPhieu'] : $header['TenNguoiLapGoc'];
    
    $response['header'] = $header;
    $ycsx_id = $header['YCSX_ID'];

    // 3. Lấy danh sách sản phẩm từ chi tiết phiếu xuất kho
    $sql_items = "
        SELECT 
            ct.ChiTietPXK_ID, ct.SanPhamID as variant_id, ct.MaHang, ct.TenSanPham, 
            ct.SoLuongThucXuat, ct.TaiSo, ct.GhiChu
        FROM chitiet_phieuxuatkho ct
        WHERE ct.PhieuXuatKhoID = :pxk_id
        ORDER BY ct.ChiTietPXK_ID
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([':pxk_id' => $pxk_id]);
    $items_result = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 4. Lấy Tên Nhóm cho từng sản phẩm từ đơn hàng gốc (nếu có)
    if ($ycsx_id && count($items_result) > 0) {
        $product_ids = array_map(function($item) { return $item['variant_id']; }, $items_result);
        if (!empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $sql_groups = "SELECT SanPhamID, TenNhom FROM chitiet_donhang WHERE DonHangID = ? AND SanPhamID IN ($placeholders)";
            $stmt_groups = $pdo->prepare($sql_groups);
            $params = array_merge([$ycsx_id], $product_ids);
            $stmt_groups->execute($params);
            $groups_map = $stmt_groups->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Gán TenNhom vào mảng sản phẩm
            foreach ($items_result as &$item) {
                $item['TenNhom'] = $groups_map[$item['variant_id']] ?? null;
            }
            unset($item);
        }
    }

    // 5. Tái cấu trúc dữ liệu theo nhóm để hiển thị
    $item_groups = [];
    foreach ($items_result as $item) {
        $group_name = '';
        if (isset($item['MaHang']) && $item['MaHang'] === 'VT-ECU') {
            $group_name = 'Ecu cho Cùm Ula';
        } else {
            $group_name = $item['TenNhom'] ?? 'Sản phẩm khác';
        }

        if (!isset($item_groups[$group_name])) {
            $item_groups[$group_name] = ['items' => [], 'ghiChu' => ($group_name === 'Ecu cho Cùm Ula' ? '' : '(+/-)5%')];
        }
        $item_groups[$group_name]['items'][] = $item;
    }

    $response['items'] = $item_groups;
    $response['success'] = true;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi Server: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

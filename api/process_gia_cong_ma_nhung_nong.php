<?php
/**
 * File: api/process_gia_cong_ma_nhung_nong.php
 * Version: 1.0
 * Description: API xử lý xuất kho ULA mạ điện phân để gia công mạ nhúng nóng
 * 
 * Chức năng:
 * - Xuất kho sản phẩm ULA mạ điện phân
 * - Ghi nhận phiếu xuất kho gia công
 * - Cập nhật trạng thái trong bảng chitietchuanbihang
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

/**
 * Lấy mã ULA cơ bản (không bao gồm hậu tố)
 */
function getUlaBaseSku($maHang) {
    $maHang = trim($maHang);
    if (preg_match('/^(ULA\s+\d+(?:\/\d+)?x\d+(?:x[A-Z]\d+)?(?:-[A-Z]\d+)?(?:-[A-Z]+)?)/', $maHang, $matches)) {
        return $matches[1];
    }
    return $maHang;
}

// Nhận dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['cbh_id']) || !isset($input['chi_tiet_cbh_id']) || !isset($input['so_luong_xuat'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Thiếu thông tin: cbh_id, chi_tiet_cbh_id, so_luong_xuat'
    ]);
    exit;
}

$cbhId = intval($input['cbh_id']);
$chiTietCbhId = intval($input['chi_tiet_cbh_id']);
$soLuongXuat = intval($input['so_luong_xuat']);
$nguoiXuat = $input['nguoi_xuat'] ?? 'Hệ thống';
$ghiChu = $input['ghi_chu'] ?? '';

if ($soLuongXuat <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Số lượng xuất phải lớn hơn 0'
    ]);
    exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();
    
    // 1. Lấy thông tin sản phẩm mạ nhúng nóng
    $sql_get_item = "
        SELECT ctcbh.*, v.sku, v.variant_name,
            (SELECT ao.value 
             FROM variant_attributes va 
             JOIN attribute_options ao ON va.option_id = ao.option_id
             JOIN attributes a ON ao.attribute_id = a.attribute_id
             WHERE va.variant_id = v.variant_id AND a.name = 'Xử lý bề mặt'
             LIMIT 1) AS xu_ly_be_mat
        FROM chitietchuanbihang ctcbh
        LEFT JOIN variants v ON ctcbh.SanPhamID = v.variant_id
        WHERE ctcbh.ChiTietCBH_ID = :chiTietCbhId AND ctcbh.CBH_ID = :cbhId
    ";
    
    $stmt = $pdo->prepare($sql_get_item);
    $stmt->execute([':chiTietCbhId' => $chiTietCbhId, ':cbhId' => $cbhId]);
    $itemNhungNong = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$itemNhungNong) {
        throw new Exception('Không tìm thấy sản phẩm trong phiếu chuẩn bị hàng');
    }
    
    if ($itemNhungNong['xu_ly_be_mat'] !== 'Mạ nhúng nóng') {
        throw new Exception('Sản phẩm này không phải loại mạ nhúng nóng');
    }
    
    // 2. Tìm sản phẩm ULA mạ điện phân tương ứng
    $baseSku = getUlaBaseSku($itemNhungNong['MaHang']);
    
    $sql_find_dien_phan = "
        SELECT v.variant_id, v.sku, v.variant_name, vi.quantity AS TonKho
        FROM variants v
        LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
        WHERE v.sku LIKE :baseSku
        AND v.variant_id != :excludeId
        AND EXISTS (
            SELECT 1 FROM variant_attributes va 
            JOIN attribute_options ao ON va.option_id = ao.option_id
            JOIN attributes a ON ao.attribute_id = a.attribute_id
            WHERE va.variant_id = v.variant_id 
            AND a.name = 'Xử lý bề mặt' 
            AND ao.value = 'Mạ điện phân'
        )
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql_find_dien_phan);
    $stmt->execute([
        ':baseSku' => $baseSku . '%',
        ':excludeId' => $itemNhungNong['SanPhamID']
    ]);
    $itemDienPhan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$itemDienPhan) {
        throw new Exception('Không tìm thấy sản phẩm ULA mạ điện phân tương ứng: ' . $baseSku);
    }
    
    // 3. Kiểm tra tồn kho mạ điện phân
    $tonKhoDienPhan = (int)($itemDienPhan['TonKho'] ?? 0);
    
    if ($tonKhoDienPhan < $soLuongXuat) {
        throw new Exception(sprintf(
            'Không đủ tồn kho mạ điện phân. Cần: %d, Tồn: %d',
            $soLuongXuat,
            $tonKhoDienPhan
        ));
    }
    
    // 4. Tạo phiếu xuất kho gia công
    $maPhieuXuat = 'GC-MNN-' . $cbhId . '-' . time();
    
    $sql_insert_phieu = "
        INSERT INTO phieu_xuat_gia_cong (
            MaPhieu, CBH_ID, ChiTietCBH_ID, 
            SanPhamXuatID, MaSanPhamXuat, TenSanPhamXuat,
            SanPhamNhanID, MaSanPhamNhan, TenSanPhamNhan,
            SoLuongXuat, LoaiGiaCong, TrangThai,
            NguoiXuat, NgayXuat, GhiChu
        ) VALUES (
            :maPhieu, :cbhId, :chiTietCbhId,
            :sanPhamXuatId, :maSanPhamXuat, :tenSanPhamXuat,
            :sanPhamNhanId, :maSanPhamNhan, :tenSanPhamNhan,
            :soLuongXuat, 'Mạ nhúng nóng', 'Đã xuất',
            :nguoiXuat, NOW(), :ghiChu
        )
    ";
    
    $stmt = $pdo->prepare($sql_insert_phieu);
    $stmt->execute([
        ':maPhieu' => $maPhieuXuat,
        ':cbhId' => $cbhId,
        ':chiTietCbhId' => $chiTietCbhId,
        ':sanPhamXuatId' => $itemDienPhan['variant_id'],
        ':maSanPhamXuat' => $itemDienPhan['sku'],
        ':tenSanPhamXuat' => $itemDienPhan['variant_name'],
        ':sanPhamNhanId' => $itemNhungNong['SanPhamID'],
        ':maSanPhamNhan' => $itemNhungNong['MaHang'],
        ':tenSanPhamNhan' => $itemNhungNong['TenSanPham'],
        ':soLuongXuat' => $soLuongXuat,
        ':nguoiXuat' => $nguoiXuat,
        ':ghiChu' => $ghiChu ?: "Xuất gia công mạ nhúng nóng từ CBH-{$cbhId}"
    ]);
    
    $phieuXuatId = $pdo->lastInsertId();
    
    // 5. Trừ tồn kho mạ điện phân
    $sql_update_inventory = "
        UPDATE variant_inventory 
        SET quantity = quantity - :soLuong,
            updated_at = NOW()
        WHERE variant_id = :variantId
    ";
    
    $stmt = $pdo->prepare($sql_update_inventory);
    $stmt->execute([
        ':soLuong' => $soLuongXuat,
        ':variantId' => $itemDienPhan['variant_id']
    ]);
    
    // 6. Ghi log xuất kho
    $sql_log = "
        INSERT INTO inventory_logs (
            variant_id, change_type, quantity_change, 
            quantity_before, quantity_after, 
            reference_type, reference_id, notes, created_by
        ) VALUES (
            :variantId, 'XUAT_GIA_CONG', :quantityChange,
            :quantityBefore, :quantityAfter,
            'PHIEU_XUAT_GIA_CONG', :referenceId, :notes, :createdBy
        )
    ";
    
    $stmt = $pdo->prepare($sql_log);
    $stmt->execute([
        ':variantId' => $itemDienPhan['variant_id'],
        ':quantityChange' => -$soLuongXuat,
        ':quantityBefore' => $tonKhoDienPhan,
        ':quantityAfter' => $tonKhoDienPhan - $soLuongXuat,
        ':referenceId' => $phieuXuatId,
        ':notes' => "Xuất {$soLuongXuat} {$itemDienPhan['sku']} để gia công mạ nhúng nóng thành {$itemNhungNong['MaHang']}",
        ':createdBy' => $nguoiXuat
    ]);
    
    // 7. Cập nhật ghi chú trong chitietchuanbihang
    $ghiChuMoi = sprintf(
        "[GC-MNN] Đã xuất %d %s đi gia công. Phiếu: %s",
        $soLuongXuat,
        $itemDienPhan['sku'],
        $maPhieuXuat
    );
    
    $ghiChuCu = $itemNhungNong['GhiChu'] ?? '';
    $ghiChuCombined = trim($ghiChuCu . "\n" . $ghiChuMoi);
    
    $sql_update_chitiet = "
        UPDATE chitietchuanbihang 
        SET GhiChu = :ghiChu,
            updated_at = NOW()
        WHERE ChiTietCBH_ID = :chiTietCbhId
    ";
    
    $stmt = $pdo->prepare($sql_update_chitiet);
    $stmt->execute([
        ':ghiChu' => $ghiChuCombined,
        ':chiTietCbhId' => $chiTietCbhId
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Xuất kho gia công mạ nhúng nóng thành công',
        'data' => [
            'ma_phieu_xuat' => $maPhieuXuat,
            'phieu_xuat_id' => $phieuXuatId,
            'san_pham_xuat' => [
                'id' => $itemDienPhan['variant_id'],
                'ma' => $itemDienPhan['sku'],
                'ten' => $itemDienPhan['variant_name'],
                'ton_kho_truoc' => $tonKhoDienPhan,
                'ton_kho_sau' => $tonKhoDienPhan - $soLuongXuat
            ],
            'san_pham_nhan' => [
                'id' => $itemNhungNong['SanPhamID'],
                'ma' => $itemNhungNong['MaHang'],
                'ten' => $itemNhungNong['TenSanPham']
            ],
            'so_luong_xuat' => $soLuongXuat,
            'ngay_xuat' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ERROR in process_gia_cong_ma_nhung_nong.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
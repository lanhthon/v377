<?php
/**
 * File: api/import_gia_cong_ma_nhung_nong.php
 * Version: 1.0
 * Description: API nhập kho sản phẩm ULA mạ nhúng nóng sau khi gia công xong
 *
 * Chức năng:
 * - Nhập kho sản phẩm mạ nhúng nóng sau khi gia công hoàn tất
 * - Cập nhật trạng thái phiếu xuất gia công
 * - Ghi log vào inventory_logs
 * - Cập nhật số lượng tồn kho
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

// Nhận dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['phieu_xuat_gc_id']) || !isset($input['so_luong_nhap'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin: phieu_xuat_gc_id, so_luong_nhap'
    ]);
    exit;
}

$phieuXuatGcId = intval($input['phieu_xuat_gc_id']);
$soLuongNhap = intval($input['so_luong_nhap']);
$nguoiNhap = $input['nguoi_nhap'] ?? 'Hệ thống';
$ghiChu = $input['ghi_chu'] ?? '';

if ($soLuongNhap <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Số lượng nhập phải lớn hơn 0'
    ]);
    exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // 1. Lấy thông tin phiếu xuất gia công
    $sql_get_phieu = "
        SELECT * FROM phieu_xuat_gia_cong
        WHERE PhieuXuatGC_ID = :phieuId
    ";

    $stmt = $pdo->prepare($sql_get_phieu);
    $stmt->execute([':phieuId' => $phieuXuatGcId]);
    $phieu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$phieu) {
        throw new Exception('Không tìm thấy phiếu xuất gia công');
    }

    // Kiểm tra trạng thái
    if ($phieu['TrangThai'] === 'Đã nhập kho') {
        throw new Exception('Phiếu này đã được nhập kho hoàn tất');
    }

    // 2. Kiểm tra số lượng
    $soLuongXuat = (int)$phieu['SoLuongXuat'];
    $soLuongDaNhap = (int)$phieu['SoLuongNhapVe'];
    $soLuongConLai = $soLuongXuat - $soLuongDaNhap;

    if ($soLuongNhap > $soLuongConLai) {
        throw new Exception(sprintf(
            'Số lượng nhập vượt quá số lượng còn lại. Đã xuất: %d, Đã nhập: %d, Còn lại: %d',
            $soLuongXuat,
            $soLuongDaNhap,
            $soLuongConLai
        ));
    }

    // 3. Lấy tồn kho hiện tại của sản phẩm mạ nhúng nóng
    $sql_get_inventory = "
        SELECT quantity FROM variant_inventory
        WHERE variant_id = :variantId
    ";

    $stmt = $pdo->prepare($sql_get_inventory);
    $stmt->execute([':variantId' => $phieu['SanPhamNhanID']]);
    $tonKhoHienTai = (int)($stmt->fetchColumn() ?? 0);

    // 4. Cập nhật tồn kho sản phẩm mạ nhúng nóng (TĂNG)
    $sql_update_inventory = "
        INSERT INTO variant_inventory (variant_id, quantity, updated_at)
        VALUES (:variantId, :quantity, NOW())
        ON DUPLICATE KEY UPDATE
            quantity = quantity + :quantity,
            updated_at = NOW()
    ";

    $stmt = $pdo->prepare($sql_update_inventory);
    $stmt->execute([
        ':variantId' => $phieu['SanPhamNhanID'],
        ':quantity' => $soLuongNhap
    ]);

    // 5. Ghi log nhập kho
    $sql_log = "
        INSERT INTO inventory_logs (
            variant_id, change_type, quantity_change,
            quantity_before, quantity_after,
            reference_type, reference_id, notes, created_by
        ) VALUES (
            :variantId, 'NHAP_GIA_CONG', :quantityChange,
            :quantityBefore, :quantityAfter,
            'PHIEU_XUAT_GIA_CONG', :referenceId, :notes, :createdBy
        )
    ";

    $stmt = $pdo->prepare($sql_log);
    $stmt->execute([
        ':variantId' => $phieu['SanPhamNhanID'],
        ':quantityChange' => $soLuongNhap,
        ':quantityBefore' => $tonKhoHienTai,
        ':quantityAfter' => $tonKhoHienTai + $soLuongNhap,
        ':referenceId' => $phieuXuatGcId,
        ':notes' => "Nhập {$soLuongNhap} {$phieu['MaSanPhamNhan']} sau gia công mạ nhúng nóng từ {$phieu['MaSanPhamXuat']}. {$ghiChu}",
        ':createdBy' => $nguoiNhap
    ]);

    // 6. Cập nhật phiếu xuất gia công
    $soLuongNhapVeMoi = $soLuongDaNhap + $soLuongNhap;
    $trangThaiMoi = ($soLuongNhapVeMoi >= $soLuongXuat) ? 'Đã nhập kho' : 'Đang gia công';

    $sql_update_phieu = "
        UPDATE phieu_xuat_gia_cong
        SET SoLuongNhapVe = :soLuongNhapVe,
            TrangThai = :trangThai,
            NgayNhapKho = NOW(),
            NguoiNhapKho = :nguoiNhap,
            GhiChu = CONCAT(COALESCE(GhiChu, ''), '\n', :ghiChuMoi),
            updated_at = NOW()
        WHERE PhieuXuatGC_ID = :phieuId
    ";

    $ghiChuMoi = sprintf(
        "[%s] Nhập %d sản phẩm về kho. Tổng đã nhập: %d/%d",
        date('Y-m-d H:i:s'),
        $soLuongNhap,
        $soLuongNhapVeMoi,
        $soLuongXuat
    );

    $stmt = $pdo->prepare($sql_update_phieu);
    $stmt->execute([
        ':soLuongNhapVe' => $soLuongNhapVeMoi,
        ':trangThai' => $trangThaiMoi,
        ':nguoiNhap' => $nguoiNhap,
        ':ghiChuMoi' => $ghiChuMoi,
        ':phieuId' => $phieuXuatGcId
    ]);

    // 7. Ghi lịch sử gia công
    $sql_lich_su = "
        INSERT INTO lich_su_gia_cong (
            PhieuXuatGC_ID, TrangThai, MoTa, NguoiCapNhat, NgayCapNhat
        ) VALUES (
            :phieuId, :trangThai, :moTa, :nguoiCapNhat, NOW()
        )
    ";

    $moTaLichSu = sprintf(
        "Nhập kho %d sản phẩm %s. Tổng đã nhập: %d/%d. %s",
        $soLuongNhap,
        $phieu['MaSanPhamNhan'],
        $soLuongNhapVeMoi,
        $soLuongXuat,
        $ghiChu ?: ''
    );

    $stmt = $pdo->prepare($sql_lich_su);
    $stmt->execute([
        ':phieuId' => $phieuXuatGcId,
        ':trangThai' => $trangThaiMoi,
        ':moTa' => $moTaLichSu,
        ':nguoiCapNhat' => $nguoiNhap
    ]);

    // 8. Cập nhật chi tiết chuẩn bị hàng
    $sql_update_chitiet = "
        UPDATE chitietchuanbihang
        SET SoLuongDaNhapGC = SoLuongDaNhapGC + :soLuongNhap,
            TrangThaiGiaCong = CASE
                WHEN (SoLuongDaNhapGC + :soLuongNhap) >= SoLuongCanSX THEN 'Đã nhập kho'
                ELSE 'Đang gia công'
            END,
            updated_at = NOW()
        WHERE ChiTietCBH_ID = :chiTietCbhId
    ";

    $stmt = $pdo->prepare($sql_update_chitiet);
    $stmt->execute([
        ':soLuongNhap' => $soLuongNhap,
        ':chiTietCbhId' => $phieu['ChiTietCBH_ID']
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Nhập kho sau gia công thành công',
        'data' => [
            'ma_phieu' => $phieu['MaPhieu'],
            'san_pham_nhan' => [
                'id' => $phieu['SanPhamNhanID'],
                'ma' => $phieu['MaSanPhamNhan'],
                'ten' => $phieu['TenSanPhamNhan'],
                'ton_kho_truoc' => $tonKhoHienTai,
                'ton_kho_sau' => $tonKhoHienTai + $soLuongNhap
            ],
            'so_luong_nhap' => $soLuongNhap,
            'so_luong_da_nhap' => $soLuongNhapVeMoi,
            'so_luong_xuat' => $soLuongXuat,
            'trang_thai' => $trangThaiMoi,
            'ngay_nhap' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ERROR in import_gia_cong_ma_nhung_nong.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>


<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/db_config.php';
    
    if (!function_exists('get_db_connection')) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy hàm get_db_connection()']);
        exit();
    }
    
    $pdo = get_db_connection();
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối CSDL: ' . $e->getMessage()]);
    exit();
}

$baoGiaID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($baoGiaID <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID báo giá không hợp lệ.']);
    exit();
}

try {
    // Lấy thông tin báo giá - KHÔNG CÓ DiaChiDuAn
    $sql = "SELECT 
        b.BaoGiaID, b.SoBaoGia, b.NgayBaoGia, b.NgayGiaoDuKien, b.KhachHangID, b.CongTyID,
        b.NguoiLienHeID, b.UserID, b.DuAnID, b.TenCongTy, b.DiaChiKhach,
        b.NguoiNhan, b.Email, b.SoDienThoaiKhach, b.SoFaxKhach, b.SoDiDongKhach,
        b.TenDuAn,
        b.CoCheGiaApDung, b.TongTienTruocThue, b.ThueVAT, b.TongTienSauThue,
        b.SoLuongVanChuyen, b.DonGiaVanChuyen, b.TongTienVanChuyen, b.TrangThai,
        b.NguoiTao, b.DiaChiGiaoHang, b.ThoiGianGiaoHang, b.DieuKienThanhToan,
        b.HieuLucBaoGia, b.NguoiBaoGia, b.ChucVuNguoiBaoGia, b.DiDongNguoiBaoGia,
        b.HangMuc, b.HinhAnh1, b.HinhAnh2, b.XuatXu, b.GhiChuVanChuyen,
        b.PhanTramDieuChinh, b.ThuePhanTram
    FROM baogia b
    WHERE b.BaoGiaID = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$baoGiaID]);
    $quoteInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quoteInfo) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy báo giá.']);
        exit();
    }
    
    // Lấy chi tiết sản phẩm
    $sqlItems = "SELECT 
        ChiTietID, BaoGiaID, TenNhom, ID_ThongSo, variant_id, MaHang, TenSanPham,
        DoDay, ChieuRong, DonViTinh, SoLuong, DonGia, ThanhTien, GhiChu, ThuTuHienThi, KhuVuc
    FROM chitietbaogia
    WHERE BaoGiaID = ?
    ORDER BY ThuTuHienThi ASC";
    
    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute([$baoGiaID]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'quote' => [
            'info' => $quoteInfo,
            'items' => $items
        ]
    ]);
    
} catch (\Exception $e) {
    error_log("Lỗi khi lấy chi tiết báo giá: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>
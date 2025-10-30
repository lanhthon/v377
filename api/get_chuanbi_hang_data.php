<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu ID Đơn hàng (YCSX_ID).']);
    exit;
}

$ycsx_id = (int)$_GET['id'];

try {
    $conn->begin_transaction();

    // Lấy thông tin cơ bản của Đơn hàng và Báo giá
    $sql_info = "
        SELECT 
            d.YCSX_ID, d.SoYCSX, d.BaoGiaID,
            b.TenCongTy, b.NguoiNhan, b.DiaChiGiaoHang, b.TenDuAn,
            cbh.CBH_ID, cbh.SoCBH, cbh.NgayTao AS NgayTaoCBH, cbh.NgayGiao,
            cbh.DangKiCongTruong, cbh.QuyCachThung, cbh.LoaiXe, cbh.XeGrap, cbh.XeTai, cbh.SoLaiXe, cbh.PhuTrach
        FROM donhang d
        JOIN baogia b ON d.BaoGiaID = b.BaoGiaID
        LEFT JOIN chuanbihang cbh ON d.YCSX_ID = cbh.YCSX_ID
        WHERE d.YCSX_ID = ?
    ";
    $stmt_info = $conn->prepare($sql_info);
    $stmt_info->bind_param("i", $ycsx_id);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();
    $stmt_info->close();

    if (!$info) {
        throw new Exception("Không tìm thấy đơn hàng.");
    }

    // Nếu chưa có phiếu chuẩn bị hàng, tạo mới
    if (empty($info['CBH_ID'])) {
        $so_cbh = "CBH-" . date("Y") . "-" . str_pad($ycsx_id, 5, "0", STR_PAD_LEFT);
        $sql_create_cbh = "INSERT INTO chuanbihang (YCSX_ID, BaoGiaID, SoCBH, NgayTao, TenCongTy, DiaDiemGiaoHang, NguoiNhanHang, NgayGuiYCSX) VALUES (?, ?, ?, CURDATE(), ?, ?, ?, CURDATE())";
        $stmt_create = $conn->prepare($sql_create_cbh);
        $stmt_create->bind_param("iissss", $ycsx_id, $info['BaoGiaID'], $so_cbh, $info['TenCongTy'], $info['DiaChiGiaoHang'], $info['NguoiNhan']);
        $stmt_create->execute();
        $cbh_id = $conn->insert_id;
        $stmt_create->close();

        // Lấy lại thông tin sau khi tạo
        $info['CBH_ID'] = $cbh_id;
        $info['SoCBH'] = $so_cbh;

        // Chuyển chi tiết đơn hàng vào chi tiết chuẩn bị hàng
        $sql_copy_details = "
            INSERT INTO chitietchuanbihang (CBH_ID, TenNhom, SanPhamID, MaHang, TenSanPham, SoLuong, ID_ThongSo, DoDay, BanRong, GhiChu, ThuTuHienThi)
            SELECT ?, ctd.TenNhom, ctd.SanPhamID, ctd.MaHang, ctd.TenSanPham, ctd.SoLuong, sp.ID_ThongSo, sp.DoDay, sp.BanRong, ctd.GhiChu, ctd.ThuTuHienThi
            FROM chitiet_donhang ctd
            JOIN sanpham sp ON ctd.SanPhamID = sp.SanPhamID
            WHERE ctd.DonHangID = ?
        ";
        $stmt_copy = $conn->prepare($sql_copy_details);
        $stmt_copy->bind_param("ii", $cbh_id, $ycsx_id);
        $stmt_copy->execute();
        $stmt_copy->close();
    }

    // Lấy chi tiết các sản phẩm cần chuẩn bị
    $sql_details = "SELECT * FROM chitietchuanbihang WHERE CBH_ID = ? ORDER BY ThuTuHienThi";
    $stmt_details = $conn->prepare($sql_details);
    $stmt_details->bind_param("i", $info['CBH_ID']);
    $stmt_details->execute();
    $details = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_details->close();

    $conn->commit();

    echo json_encode(['success' => true, 'info' => $info, 'details' => $details]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}

$conn->close();
?>
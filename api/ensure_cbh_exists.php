<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$ycsxID = isset($_GET['ycsxID']) ? (int)$_GET['ycsxID'] : 0;
if ($ycsxID <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Đơn hàng không hợp lệ.']);
    exit;
}

$conn->begin_transaction();
try {
    $stmt_check = $conn->prepare("SELECT CBH_ID FROM donhang WHERE YCSX_ID = ?");
    $stmt_check->bind_param("i", $ycsxID);
    $stmt_check->execute();
    $order_data = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$order_data) {
        throw new Exception("Không tìm thấy đơn hàng.");
    }

    $cbhID = $order_data['CBH_ID'];

    // Nếu chưa có phiếu CBH, tạo mới
    if ($cbhID === null) {
        $stmt_info = $conn->prepare("SELECT d.BaoGiaID, b.TenCongTy, b.DiaChiGiaoHang, b.NguoiNhan FROM donhang d JOIN baogia b ON d.BaoGiaID = b.BaoGiaID WHERE d.YCSX_ID = ?");
        $stmt_info->bind_param("i", $ycsxID);
        $stmt_info->execute();
        $info = $stmt_info->get_result()->fetch_assoc();
        $stmt_info->close();

        $ngayTao = date('Y-m-d');
        $soCBH = "CBH-" . date('dmy') . "-" . str_pad($ycsxID, 4, '0', STR_PAD_LEFT);
        
        $stmt_cbh = $conn->prepare("INSERT INTO chuanbihang (YCSX_ID, BaoGiaID, SoCBH, NgayTao, TenCongTy, NguoiNhanHang, DiaDiemGiaoHang, NgayGuiYCSX) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_cbh->bind_param("iissssss", $ycsxID, $info['BaoGiaID'], $soCBH, $ngayTao, $info['TenCongTy'], $info['NguoiNhan'], $info['DiaChiGiaoHang'], $ngayTao);
        $stmt_cbh->execute();
        $cbhID = $conn->insert_id;
        $stmt_cbh->close();

        $stmt_items = $conn->prepare("SELECT cd.*, sp.ID_ThongSo, sp.DoDay, sp.BanRong FROM chitiet_donhang cd JOIN sanpham sp ON cd.SanPhamID = sp.SanPhamID WHERE cd.DonHangID = ? ORDER BY cd.ThuTuHienThi ASC");
        $stmt_items->bind_param("i", $ycsxID);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        $stmt_detail = $conn->prepare("INSERT INTO chitietchuanbihang (CBH_ID, TenNhom, SanPhamID, MaHang, TenSanPham, SoLuong, GhiChu, ThuTuHienThi, ID_ThongSo, DoDay, BanRong) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        while ($item = $items_result->fetch_assoc()) {
            $stmt_detail->bind_param("isississsss", $cbhID, $item['TenNhom'], $item['SanPhamID'], $item['MaHang'], $item['TenSanPham'], $item['SoLuong'], $item['GhiChu'], $item['ThuTuHienThi'], $item['ID_ThongSo'], $item['DoDay'], $item['BanRong']);
            $stmt_detail->execute();
        }
        $stmt_detail->close();
        $stmt_items->close();

        $stmt_update_order = $conn->prepare("UPDATE donhang SET CBH_ID = ? WHERE YCSX_ID = ?");
        $stmt_update_order->bind_param("ii", $cbhID, $ycsxID);
        $stmt_update_order->execute();
        $stmt_update_order->close();
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'cbhID' => $cbhID]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
$conn->close();
?>
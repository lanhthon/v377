<?php // api/create_cbh_from_khgh.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$khghId = isset($_POST['khgh_id']) ? intval($_POST['khgh_id']) : 0;

if ($khghId === 0) {
    echo json_encode(['success' => false, 'message' => 'ID Kế hoạch giao hàng không hợp lệ.']);
    exit;
}

$conn->begin_transaction();
try {
    // Kiểm tra xem CBH đã được tạo cho KHGH này chưa
    $check_stmt = $conn->prepare("SELECT CBH_ID FROM chuanbihang WHERE KHGH_ID = ?");
    $check_stmt->bind_param("i", $khghId);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        throw new Exception("Phiếu chuẩn bị hàng đã tồn tại cho kế hoạch này.");
    }
    $check_stmt->close();

    // Lấy thông tin từ KHGH và Đơn hàng gốc
    $info_stmt = $conn->prepare("
        SELECT khgh.*, dh.* FROM kehoach_giaohang khgh
        JOIN donhang dh ON khgh.DonHangID = dh.YCSX_ID
        WHERE khgh.KHGH_ID = ?
    ");
    $info_stmt->bind_param("i", $khghId);
    $info_stmt->execute();
    $sourceInfo = $info_stmt->get_result()->fetch_assoc();
    $info_stmt->close();

    if (!$sourceInfo) throw new Exception("Không tìm thấy kế hoạch giao hàng.");

    // Tạo số phiếu CBH mới
    $soCBH = 'CBH-' . date('ymd') . '-' . str_pad($khghId, 3, '0', STR_PAD_LEFT);

    // INSERT vào bảng chuanbihang
    $stmt_cbh = $conn->prepare(
        "INSERT INTO chuanbihang (KHGH_ID, YCSX_ID, BaoGiaID, SoCBH, NgayTao, TenCongTy, DiaDiemGiaoHang, NguoiNhanHang, TrangThai) 
         VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, 'Mới tạo')"
    );
    $stmt_cbh->bind_param("iiisss", $khghId, $sourceInfo['YCSX_ID'], $sourceInfo['BaoGiaID'], $soCBH, $sourceInfo['TenCongTy'], $sourceInfo['DiaDiemGiaoHang'], $sourceInfo['NguoiNhanHang']);
    $stmt_cbh->execute();
    $cbhId = $stmt_cbh->insert_id;
    $stmt_cbh->close();

    if ($cbhId === 0) throw new Exception("Không thể tạo phiếu chuẩn bị hàng.");

    // Lấy chi tiết từ KHGH để tạo chi tiết CBH
    $details_stmt = $conn->prepare("
        SELECT ctdh.SanPhamID, ctdh.MaHang, ctdh.TenSanPham, ctkhgh.SoLuongGiao
        FROM chitiet_kehoach_giaohang ctkhgh
        JOIN chitiet_donhang ctdh ON ctkhgh.ChiTiet_DonHangID = ctdh.ChiTiet_YCSX_ID
        WHERE ctkhgh.KHGH_ID = ?
    ");
    $details_stmt->bind_param("i", $khghId);
    $details_stmt->execute();
    $itemsToPrepare = $details_stmt->get_result();

    // INSERT vào bảng chitiet_chuanbihang
    $stmt_chitiet_cbh = $conn->prepare("INSERT INTO chitiet_chuanbihang (CBH_ID, SanPhamID, MaHang, TenSanPham, SoLuongYeuCau) VALUES (?, ?, ?, ?, ?)");
    while ($item = $itemsToPrepare->fetch_assoc()) {
        $stmt_chitiet_cbh->bind_param("isssi", $cbhId, $item['SanPhamID'], $item['MaHang'], $item['TenSanPham'], $item['SoLuongGiao']);
        $stmt_chitiet_cbh->execute();
    }
    $stmt_chitiet_cbh->close();
    
    // Cập nhật trạng thái của KHGH
    $update_khgh_stmt = $conn->prepare("UPDATE kehoach_giaohang SET TrangThai = 'Đang chuẩn bị hàng' WHERE KHGH_ID = ?");
    $update_khgh_stmt->bind_param("i", $khghId);
    $update_khgh_stmt->execute();
    $update_khgh_stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Tạo phiếu chuẩn bị hàng thành công!', 'cbh_id' => $cbhId]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?>
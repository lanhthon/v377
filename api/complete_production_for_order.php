<?php
/**
 * File: api/complete_production_for_order.php
 * API để hoàn thành TẤT CẢ các mục đang sản xuất của một đơn hàng.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$ycsxID = (int)($data['ycsxID'] ?? 0);

if ($ycsxID <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Đơn hàng không hợp lệ.']);
    exit;
}

$conn->begin_transaction();
try {
    // Lấy tất cả các mục đang sản xuất của đơn hàng này để cập nhật tồn kho
    $stmt_get_items = $conn->prepare("SELECT SanPhamID, SoLuongCayCanSX FROM lenhsanxuat WHERE YCSX_ID = ? AND TrangThai = 'Đang sản xuất'");
    $stmt_get_items->bind_param("i", $ycsxID);
    $stmt_get_items->execute();
    $items_to_complete = $stmt_get_items->get_result();

    if ($items_to_complete->num_rows === 0) {
        throw new Exception("Không có mục nào đang sản xuất cho đơn hàng này.");
    }

    // Lặp qua từng mục để cập nhật tồn kho cây
    $stmt_inv = $conn->prepare("UPDATE sanpham_tonkho SET TonKhoCay = TonKhoCay + ? WHERE SanPhamID = ?");
    while ($item = $items_to_complete->fetch_assoc()) {
        if ($item['SoLuongCayCanSX'] > 0) {
            $stmt_inv->bind_param("ii", $item['SoLuongCayCanSX'], $item['SanPhamID']);
            $stmt_inv->execute();
        }
    }
    $stmt_get_items->close();
    $stmt_inv->close();


    // Cập nhật trạng thái của tất cả các mục đó thành "Đã hoàn thành"
    $stmt_update_lsx = $conn->prepare("UPDATE lenhsanxuat SET TrangThai = 'Đã hoàn thành', NgayHoanThanhThucTe = CURDATE() WHERE YCSX_ID = ? AND TrangThai = 'Đang sản xuất'");
    $stmt_update_lsx->bind_param("i", $ycsxID);
    $stmt_update_lsx->execute();
    $stmt_update_lsx->close();

    // Cập nhật trạng thái của đơn hàng chính thành "Chờ chuẩn bị hàng"
    $stmt_update_donhang = $conn->prepare("UPDATE donhang SET TrangThai = 'Chờ chuẩn bị hàng' WHERE YCSX_ID = ?");
    $stmt_update_donhang->bind_param("i", $ycsxID);
    $stmt_update_donhang->execute();
    $stmt_update_donhang->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Đã hoàn thành sản xuất cho toàn bộ đơn hàng!']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}
$conn->close();
?>
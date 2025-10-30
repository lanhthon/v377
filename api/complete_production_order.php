<?php
/**
 * File: api/complete_production_order.php
 * Phiên bản nâng cấp:
 * - Hoàn thành một mục sản xuất.
 * - Cập nhật tồn kho cây.
 * - TỰ ĐỘNG KIỂM TRA: Nếu tất cả các mục sản xuất của cùng một đơn hàng đã hoàn thành,
 * sẽ tự động cập nhật trạng thái của đơn hàng đó thành "Chờ chuẩn bị hàng".
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$lenhSX_ItemID = (int)($data['lenhSX_ItemID'] ?? 0);

if ($lenhSX_ItemID <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Lệnh sản xuất không hợp lệ.']);
    exit;
}

$conn->begin_transaction();
try {
    // Bước 1: Lấy thông tin cần thiết từ lệnh sản xuất sắp hoàn thành
    $stmt_get = $conn->prepare("SELECT YCSX_ID, SanPhamID, SoLuongCayCanSX FROM lenhsanxuat WHERE LenhSX_ItemID = ? AND TrangThai != 'Đã hoàn thành'");
    $stmt_get->bind_param("i", $lenhSX_ItemID);
    $stmt_get->execute();
    $lsx = $stmt_get->get_result()->fetch_assoc();
    $stmt_get->close();

    if (!$lsx) {
        throw new Exception("Lệnh sản xuất không tồn tại hoặc đã hoàn thành.");
    }
    
    $ycsx_id = $lsx['YCSX_ID'];

    // Bước 2: Cập nhật trạng thái của chính mục sản xuất này
    $stmt_update_item = $conn->prepare("UPDATE lenhsanxuat SET TrangThai = 'Đã hoàn thành', NgayHoanThanhThucTe = CURDATE() WHERE LenhSX_ItemID = ?");
    $stmt_update_item->bind_param("i", $lenhSX_ItemID);
    $stmt_update_item->execute();
    $stmt_update_item->close();

    // Bước 3: Cập nhật tồn kho bán thành phẩm (cây)
    $stmt_inv = $conn->prepare("UPDATE sanpham_tonkho SET TonKhoCay = TonKhoCay + ? WHERE SanPhamID = ?");
    $stmt_inv->bind_param("ii", $lsx['SoLuongCayCanSX'], $lsx['SanPhamID']);
    $stmt_inv->execute();
    $stmt_inv->close();

    // Bước 4: KIỂM TRA TOÀN BỘ ĐƠN HÀNG
    // Đếm xem còn mục sản xuất nào khác của cùng đơn hàng này chưa hoàn thành không
    $stmt_check_order = $conn->prepare("SELECT COUNT(*) as remaining_items FROM lenhsanxuat WHERE YCSX_ID = ? AND TrangThai != 'Đã hoàn thành'");
    $stmt_check_order->bind_param("i", $ycsx_id);
    $stmt_check_order->execute();
    $result = $stmt_check_order->get_result()->fetch_assoc();
    $stmt_check_order->close();

    $remaining_items = (int)$result['remaining_items'];

    // Nếu không còn mục nào đang chờ hoặc đang sản xuất (remaining_items == 0)
    if ($remaining_items === 0) {
        // Bước 5: CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG CHÍNH
        $stmt_update_donhang = $conn->prepare("UPDATE donhang SET TrangThai = 'Chờ chuẩn bị hàng' WHERE YCSX_ID = ?");
        $stmt_update_donhang->bind_param("i", $ycsx_id);
        $stmt_update_donhang->execute();
        $stmt_update_donhang->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Đã hoàn thành lệnh sản xuất và cập nhật tồn kho!']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}
$conn->close();
?>
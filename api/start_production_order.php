<?php
/**
 * File: api/start_production_order.php
 * Phiên bản sửa lỗi: Đã loại bỏ việc sử dụng cột "DonHangIDs" không còn tồn tại.
 * Logic mới: Lấy YCSX_ID trực tiếp từ dòng lệnh sản xuất để cập nhật trạng thái đơn hàng.
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
    // Lấy YCSX_ID (ID của đơn hàng) từ chính dòng lệnh sản xuất đang được xử lý
    $stmt_get = $conn->prepare("SELECT YCSX_ID FROM lenhsanxuat WHERE LenhSX_ItemID = ? AND TrangThai = 'Chờ sản xuất'");
    $stmt_get->bind_param("i", $lenhSX_ItemID);
    $stmt_get->execute();
    $lsx = $stmt_get->get_result()->fetch_assoc();
    $stmt_get->close();

    if (!$lsx || !isset($lsx['YCSX_ID'])) {
        throw new Exception("Lệnh sản xuất không tồn tại hoặc đã được bắt đầu.");
    }

    $ycsx_id_to_update = $lsx['YCSX_ID'];

    // Bước 1: Cập nhật trạng thái của dòng lệnh sản xuất này -> "Đang sản xuất"
    $stmt_update_lsx = $conn->prepare("UPDATE lenhsanxuat SET TrangThai = 'Đang sản xuất' WHERE LenhSX_ItemID = ?");
    $stmt_update_lsx->bind_param("i", $lenhSX_ItemID);
    $stmt_update_lsx->execute();
    $stmt_update_lsx->close();

    // Bước 2: Cập nhật trạng thái của đơn hàng cha -> "Đang sản xuất"
    // Chỉ cập nhật nếu đơn hàng đó vẫn đang ở trạng thái "Chờ sản xuất"
    $stmt_update_donhang = $conn->prepare("UPDATE donhang SET TrangThai = 'Đang sản xuất' WHERE YCSX_ID = ? AND TrangThai = 'Chờ sản xuất'");
    $stmt_update_donhang->bind_param("i", $ycsx_id_to_update);
    $stmt_update_donhang->execute();
    $stmt_update_donhang->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Đã bắt đầu sản xuất!']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}
$conn->close();
?>
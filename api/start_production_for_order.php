<?php
/**
 * File: api/start_production_for_order.php
 * API để bắt đầu TẤT CẢ các mục sản xuất đang chờ của một đơn hàng.
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
    // Bước 1: Cập nhật tất cả các lệnh sản xuất đang chờ của đơn hàng này thành "Đang sản xuất"
    $stmt_update_lsx = $conn->prepare("UPDATE lenhsanxuat SET TrangThai = 'Đang sản xuất' WHERE YCSX_ID = ? AND TrangThai = 'Chờ sản xuất'");
    $stmt_update_lsx->bind_param("i", $ycsxID);
    $stmt_update_lsx->execute();
    $stmt_update_lsx->close();

    // Bước 2: Cập nhật trạng thái của đơn hàng chính thành "Đang sản xuất"
    $stmt_update_donhang = $conn->prepare("UPDATE donhang SET TrangThai = 'Đang sản xuất' WHERE YCSX_ID = ? AND TrangThai = 'Chờ sản xuất'");
    $stmt_update_donhang->bind_param("i", $ycsxID);
    $stmt_update_donhang->execute();
    $stmt_update_donhang->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Đã bắt đầu sản xuất cho toàn bộ đơn hàng!']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}
$conn->close();
?>
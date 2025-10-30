<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$lenhSX_ItemID = (int)($data['lenhSX_ItemID'] ?? 0);
$ghiChu = trim($data['ghiChu'] ?? '');

if ($lenhSX_ItemID <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ.']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE lenhsanxuat SET GhiChu = ? WHERE LenhSX_ItemID = ?");
    $stmt->bind_param("si", $ghiChu, $lenhSX_ItemID);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Cập nhật ghi chú thành công.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}
?>
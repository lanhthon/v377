<?php
/**
 * API Endpoint để cập nhật trạng thái của một báo giá.
 * - Nhận BaoGiaID và trạng thái mới dưới dạng JSON từ một request POST.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Lấy dữ liệu JSON được gửi từ frontend
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Kiểm tra dữ liệu đầu vào
if ($data === null || !isset($data['baoGiaID']) || !isset($data['trangThai'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

$baoGiaID = (int)$data['baoGiaID'];
$trangThai = $data['trangThai'];

try {
    $sql = "UPDATE BaoGia SET TrangThai = ? WHERE BaoGiaID = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh UPDATE TrangThai: " . $conn->error);
    }
    $stmt->bind_param("si", $trangThai, $baoGiaID);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái báo giá thành công!']);
    } else {
        // Có thể báo cáo là không tìm thấy báo giá hoặc trạng thái đã giống
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy báo giá hoặc trạng thái không thay đổi.']);
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái báo giá: ' . $e->getMessage()]);
}

$conn->close();
?>
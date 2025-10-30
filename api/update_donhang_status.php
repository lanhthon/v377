<?php
header('Content-Type: application/json');

require_once '../config/database.php'; // Ensure this path is correct

global $conn;

if (!isset($conn) || !$conn instanceof mysqli || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['donHangID']) || !isset($data['newStatus'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không đầy đủ (thiếu donHangID hoặc newStatus).']);
    exit;
}

$donHangID = (int)$data['donHangID'];
$newStatus = $data['newStatus'];

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE YCSX_ID = ?");
    $stmt->bind_param("si", $newStatus, $donHangID);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái đơn hàng thành công.']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng hoặc trạng thái không thay đổi.']);
    }
    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Lỗi trong update_donhang_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi cập nhật trạng thái: ' . $e->getMessage()]);
}
?>
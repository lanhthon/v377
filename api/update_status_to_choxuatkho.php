<?php
/**
 * File: api/update_status_to_choxuatkho.php
 * Version: 2.0
 * Description: API để cập nhật trạng thái của đơn hàng VÀ phiếu chuẩn bị hàng
 * thành "Chờ xuất kho". Sử dụng transaction để đảm bảo toàn vẹn dữ liệu.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

global $conn;

// Lấy ID đơn hàng từ dữ liệu POST
$donhang_id = isset($_POST['donhang_id']) ? intval($_POST['donhang_id']) : 0;

if ($donhang_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ.']);
    exit;
}

$response = ['success' => false, 'message' => 'Có lỗi xảy ra.'];
$newStatus = 'Chờ xuất kho';

// Bắt đầu một transaction
$conn->begin_transaction();

try {
    // 1. Cập nhật trạng thái cho bảng `donhang`
    $stmt_donhang = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE YCSX_ID = ?");
    if ($stmt_donhang === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh cho `donhang`: " . $conn->error);
    }
    $stmt_donhang->bind_param("si", $newStatus, $donhang_id);
    $stmt_donhang->execute();
    $donhang_affected_rows = $stmt_donhang->affected_rows;
    $stmt_donhang->close();

    // 2. Cập nhật trạng thái cho bảng `chuanbihang`
    $stmt_cbh = $conn->prepare("UPDATE chuanbihang SET TrangThai = ? WHERE YCSX_ID = ?");
    if ($stmt_cbh === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh cho `chuanbihang`: " . $conn->error);
    }
    $stmt_cbh->bind_param("si", $newStatus, $donhang_id);
    $stmt_cbh->execute();
    $cbh_affected_rows = $stmt_cbh->affected_rows;
    $stmt_cbh->close();

    // Xác nhận transaction nếu có ít nhất một bảng được cập nhật
    if ($donhang_affected_rows > 0 || $cbh_affected_rows > 0) {
        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Đã cập nhật trạng thái đơn hàng và phiếu chuẩn bị hàng.';
    } else {
        // Nếu không có gì thay đổi, không cần commit, chỉ cần thông báo
        $response['message'] = 'Không tìm thấy bản ghi nào để cập nhật hoặc trạng thái đã đúng.';
        // Vẫn coi là thành công vì trạng thái đã đúng như mong đợi
        $response['success'] = true; 
    }

} catch (Exception $e) {
    // Nếu có lỗi, rollback tất cả các thay đổi
    $conn->rollback();
    http_response_code(500); // Internal Server Error
    $response['message'] = "Lỗi hệ thống: " . $e->getMessage();
    error_log("Lỗi trong update_status_to_choxuatkho.php: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>
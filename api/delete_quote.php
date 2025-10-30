<?php
/**
 * API Endpoint để xóa một báo giá và các chi tiết của nó.
 * - Nhận dữ liệu JSON qua phương thức POST.
 * - Thực hiện transaction để đảm bảo toàn vẹn dữ liệu.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Lấy dữ liệu JSON thô từ body của request
$json_data = file_get_contents('php://input');
// Giải mã chuỗi JSON thành một mảng PHP
$data = json_decode($json_data, true);

// Lấy BaoGiaID từ dữ liệu đã giải mã
$quoteId = isset($data['id']) ? (int)$data['id'] : 0;

if ($quoteId === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu ID báo giá để xóa.']);
    exit;
}

// Bắt đầu một transaction
$conn->begin_transaction();

try {
    // 1. Xóa các mục chi tiết của báo giá từ bảng chitietbaogia (SỬA TÊN BẢNG Ở ĐÂY)
    $sql_delete_items = "DELETE FROM chitietbaogia WHERE BaoGiaID = ?";
    $stmt_delete_items = $conn->prepare($sql_delete_items);
    if ($stmt_delete_items === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh DELETE chitietbaogia: " . $conn->error);
    }
    $stmt_delete_items->bind_param("i", $quoteId);
    $stmt_delete_items->execute();
    $stmt_delete_items->close();

    // 2. Xóa báo giá chính từ bảng baogia (SỬA TÊN BẢNG Ở ĐÂY)
    $sql_delete_quote = "DELETE FROM baogia WHERE BaoGiaID = ?";
    $stmt_delete_quote = $conn->prepare($sql_delete_quote);
    if ($stmt_delete_quote === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh DELETE baogia: " . $conn->error);
    }
    $stmt_delete_quote->bind_param("i", $quoteId);
    $stmt_delete_quote->execute();
    $stmt_delete_quote->close();

    // Nếu mọi thứ thành công, commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Báo giá đã được xóa thành công!']);

} catch (Exception $e) {
    // Nếu có lỗi, rollback tất cả các thay đổi
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa báo giá: ' . $e->getMessage()]);
}

$conn->close();
?>
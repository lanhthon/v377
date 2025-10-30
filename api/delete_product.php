<?php
// File: api/delete_product.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ.']);
    exit;
}

$id = (int)$data['id'];

try {
    $sql = "DELETE FROM sanpham WHERE SanPhamID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Kiểm tra xem có dòng nào bị ảnh hưởng không
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công!']);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm để xóa.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Xóa sản phẩm thất bại: ' . $stmt->error]);
    }
} catch (Exception $e) {
    // Bắt lỗi khóa ngoại (nếu sản phẩm đã được dùng trong báo giá, đơn hàng...)
    if ($e->getCode() == 1451) { // Lỗi foreign key constraint
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'Không thể xóa sản phẩm này vì nó đã được sử dụng trong các báo giá hoặc đơn hàng khác.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
    }
}

$conn->close();
?>
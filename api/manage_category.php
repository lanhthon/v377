<?php
// File: api/manage_category.php
// API endpoint for Adding, Updating, and Deleting product categories.

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Adjust path as needed

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ hoặc thiếu dữ liệu.']);
    exit;
}

$action = $data['action'];
$conn->begin_transaction();

try {
    switch ($action) {
        case 'add':
            if (empty($data['TenLoai'])) {
                 throw new Exception('Tên loại sản phẩm không được để trống.');
            }
            $sql = "INSERT INTO loaisanpham (TenLoai) VALUES (?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                 throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
            }
            $stmt->bind_param("s", $data['TenLoai']);
            $stmt->execute();
            $message = 'Thêm loại sản phẩm thành công.';
            break;

        case 'update':
            if (empty($data['LoaiID']) || empty($data['TenLoai'])) {
                throw new Exception('Dữ liệu không đủ để cập nhật.');
            }
            $sql = "UPDATE loaisanpham SET TenLoai = ? WHERE LoaiID = ?";
            $stmt = $conn->prepare($sql);
             if ($stmt === false) {
                 throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
            }
            $stmt->bind_param("si", $data['TenLoai'], $data['LoaiID']);
            $stmt->execute();
            $message = 'Cập nhật loại sản phẩm thành công.';
            break;

        case 'delete':
            if (empty($data['LoaiID'])) {
                throw new Exception('Không có ID để xóa.');
            }
            $sql = "DELETE FROM loaisanpham WHERE LoaiID = ?";
            $stmt = $conn->prepare($sql);
             if ($stmt === false) {
                 throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
            }
            $stmt->bind_param("i", $data['LoaiID']);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                throw new Exception('Không tìm thấy loại sản phẩm để xóa.');
            }
            $message = 'Xóa loại sản phẩm thành công.';
            break;

        default:
            throw new Exception('Hành động không được hỗ trợ.');
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $conn->rollback();
    $errorMessage = $e->getMessage();
    $errorCode = $e->getCode();
    
    // Check for specific MySQL error codes
    if (strpos($e->getMessage(), '1451') !== false) { // Foreign key constraint violation
        http_response_code(409); // Conflict
        $errorMessage = 'Không thể xóa loại này vì đang có sản phẩm sử dụng.';
    } elseif (strpos($e->getMessage(), '1062') !== false) { // Duplicate entry
         http_response_code(409); // Conflict
        $errorMessage = 'Tên loại sản phẩm đã tồn tại. Vui lòng chọn tên khác.';
    } else {
        http_response_code(500); // Internal Server Error
    }
    
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>
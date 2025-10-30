<?php
// File: api/manage_product_categories.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Lấy dữ liệu được gửi đến dưới dạng JSON
$data = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào và hành động
if (!$data || !isset($data['action'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ hoặc thiếu dữ liệu.']);
    exit;
}

$action = $data['action'];

// Bắt đầu một giao dịch để đảm bảo toàn vẹn dữ liệu
$conn->begin_transaction();

try {
    switch ($action) {
        // Trường hợp thêm mới loại sản phẩm
        case 'add':
            if (empty($data['TenLoai'])) {
                throw new Exception('Tên loại sản phẩm không được để trống.');
            }
            $sql = "INSERT INTO loaisanpham (TenLoai) VALUES (?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Lỗi khi chuẩn bị câu lệnh: " . $conn->error);
            }
            $stmt->bind_param("s", $data['TenLoai']);
            $stmt->execute();
            $message = 'Thêm loại sản phẩm thành công.';
            break;

        // Trường hợp cập nhật loại sản phẩm
        case 'update':
            if (empty($data['LoaiID']) || empty($data['TenLoai'])) {
                throw new Exception('Thiếu ID hoặc tên loại sản phẩm.');
            }
            $sql = "UPDATE loaisanpham SET TenLoai = ? WHERE LoaiID = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Lỗi khi chuẩn bị câu lệnh: " . $conn->error);
            }
            $stmt->bind_param("si", $data['TenLoai'], $data['LoaiID']);
            $stmt->execute();
            $message = 'Cập nhật loại sản phẩm thành công.';
            break;

        // Trường hợp xóa loại sản phẩm
        case 'delete':
            if (empty($data['LoaiID'])) {
                throw new Exception('Thiếu ID loại sản phẩm.');
            }
            $sql = "DELETE FROM loaisanpham WHERE LoaiID = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Lỗi khi chuẩn bị câu lệnh: " . $conn->error);
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
    
    // Nếu mọi thứ thành công, commit giao dịch
    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    // Nếu có lỗi, rollback lại tất cả thay đổi
    $conn->rollback();
    $errorMessage = $e->getMessage();
    
    // Xử lý lỗi khóa ngoại (khi không thể xóa loại sản phẩm đã có sản phẩm)
    if ($e->getCode() == 1451) {
        $errorMessage = 'Không thể xóa loại này vì đã có sản phẩm thuộc loại này.';
    }
    // Xử lý lỗi trùng tên
    if ($e->getCode() == 1062) {
        $errorMessage = 'Tên loại sản phẩm này đã tồn tại.';
    }
    
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}

$conn->close();
?>
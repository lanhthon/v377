<?php
// File: api/handle_category.php
header('Content-Type: application/json; charset=utf-8');

// Giả định tệp config của bạn nằm ở thư mục `config` bên ngoài thư mục `api`
require_once '../config/database.php';

// Lấy dữ liệu JSON được gửi từ JavaScript
$input = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào cơ bản
if (!$input || !isset($input['action'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit;
}

$action = $input['action'];

// Xử lý hành động 'get_all' riêng vì nó không cần transaction
if ($action === 'get_all') {
    try {
        $data = [
            'loaiSanPham' => [],
            'nhomSanPham' => []
        ];

        // Lấy tất cả Loại sản phẩm
        $resultLoai = $conn->query("SELECT LoaiID as id, TenLoai as name FROM loaisanpham ORDER BY TenLoai");
        while ($row = $resultLoai->fetch_assoc()) {
            $data['loaiSanPham'][] = $row;
        }

        // Lấy tất cả Nhóm sản phẩm
        $resultNhom = $conn->query("SELECT NhomID as id, TenNhomSanPham as name FROM nhomsanpham ORDER BY TenNhomSanPham");
        while ($row = $resultNhom->fetch_assoc()) {
            $data['nhomSanPham'][] = $row;
        }

        echo json_encode(['success' => true, 'data' => $data]);

    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
    }
    $conn->close();
    exit;
}


// Bắt đầu một transaction cho các hành động CUD (Create, Update, Delete)
$conn->begin_transaction();

try {
    // Xác định tên bảng và các cột dựa trên 'type' được gửi lên
    $type = $input['type'] ?? '';
    if ($type === 'loai') {
        $table = 'loaisanpham';
        $id_col = 'LoaiID';
        $name_col = 'TenLoai';
    } elseif ($type === 'nhom') {
        $table = 'nhomsanpham';
        $id_col = 'NhomID';
        $name_col = 'TenNhomSanPham';
    } else {
        throw new Exception('Loại danh mục không hợp lệ.');
    }

    $message = '';

    switch ($action) {
        case 'add':
            if (empty($input['name'])) {
                throw new Exception('Tên không được để trống.');
            }
            $sql = "INSERT INTO $table ($name_col) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $input['name']);
            $stmt->execute();
            $message = 'Thêm thành công.';
            break;

        case 'update':
            if (empty($input['id']) || empty($input['name'])) {
                throw new Exception('Dữ liệu cập nhật không đầy đủ.');
            }
            $sql = "UPDATE $table SET $name_col = ? WHERE $id_col = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $input['name'], $input['id']);
            $stmt->execute();
            $message = 'Cập nhật thành công.';
            break;

        case 'delete':
            if (empty($input['id'])) {
                throw new Exception('ID không được để trống.');
            }
            $sql = "DELETE FROM $table WHERE $id_col = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $input['id']);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                throw new Exception('Không tìm thấy mục để xóa.');
            }
            $message = 'Xóa thành công.';
            break;

        default:
            throw new Exception('Hành động không được hỗ trợ.');
    }
    
    // Nếu mọi thứ thành công, commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    // Nếu có lỗi, rollback transaction
    $conn->rollback();
    $errorMessage = $e->getMessage();
    
    // Xử lý các lỗi cụ thể từ cơ sở dữ liệu
    if ($e->getCode() == 1451) { // Lỗi khóa ngoại (Foreign Key)
        $errorMessage = 'Không thể xóa mục này vì đã có sản phẩm liên quan.';
    }
    if ($e->getCode() == 1062) { // Lỗi trùng lặp (Duplicate Entry)
        $errorMessage = 'Tên này đã tồn tại trong hệ thống.';
    }
    
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}

$conn->close();
?>
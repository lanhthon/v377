<?php
// File: api/save_permissions.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Xóa tất cả quyền hiện tại
    $conn->query("DELETE FROM vaitro_chucnang");

    // 2. Thêm lại các quyền mới từ dữ liệu gửi lên
    $sql = "INSERT INTO vaitro_chucnang (MaVaiTro, MaChucNang) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);

    foreach ($data as $role => $functions) {
        if (is_array($functions)) {
            foreach ($functions as $function) {
                $stmt->bind_param("ss", $role, $function);
                $stmt->execute();
            }
        }
    }
    
    $stmt->close();
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Cập nhật phân quyền thành công!']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}

$conn->close();
?>
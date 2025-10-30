<?php
// File: api/role_actions.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Hành động không được chỉ định.']);
    exit;
}

try {
    switch ($action) {
        case 'add':
            $maVaiTro = $data['maVaiTro'] ?? '';
            $tenVaiTro = $data['tenVaiTro'] ?? '';
            $moTa = $data['moTa'] ?? '';

            if (empty($maVaiTro) || empty($tenVaiTro)) {
                throw new Exception("Vui lòng điền đầy đủ mã vai trò và tên vai trò.");
            }

            // Kiểm tra mã vai trò đã tồn tại
            $check_stmt = $conn->prepare("SELECT MaVaiTro FROM vaitro WHERE MaVaiTro = ?");
            $check_stmt->bind_param("s", $maVaiTro);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                throw new Exception("Mã vai trò đã tồn tại.");
            }

            $stmt = $conn->prepare("INSERT INTO vaitro (MaVaiTro, TenVaiTro, MoTa) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $maVaiTro, $tenVaiTro, $moTa);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Thêm vai trò thành công!']);
            break;

        case 'update':
            $maVaiTro = $data['maVaiTro'] ?? '';
            $tenVaiTro = $data['tenVaiTro'] ?? '';
            $moTa = $data['moTa'] ?? '';

            if (empty($maVaiTro) || empty($tenVaiTro)) {
                throw new Exception("Vui lòng điền đầy đủ thông tin.");
            }

            // Không cho phép sửa admin
            if ($maVaiTro === 'admin') {
                throw new Exception("Không thể chỉnh sửa vai trò Admin.");
            }

            $stmt = $conn->prepare("UPDATE vaitro SET TenVaiTro = ?, MoTa = ? WHERE MaVaiTro = ?");
            $stmt->bind_param("sss", $tenVaiTro, $moTa, $maVaiTro);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Cập nhật vai trò thành công!']);
            break;

        case 'delete':
            $maVaiTro = $data['maVaiTro'] ?? '';

            if (empty($maVaiTro)) {
                throw new Exception("Mã vai trò không hợp lệ.");
            }

            // Không cho phép xóa admin
            if ($maVaiTro === 'admin') {
                throw new Exception("Không thể xóa vai trò Admin.");
            }

            // Kiểm tra xem có người dùng nào đang sử dụng vai trò này không
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM nguoidung WHERE MaVaiTro = ?");
            $check_stmt->bind_param("s", $maVaiTro);
            $check_stmt->execute();
            $result = $check_stmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                throw new Exception("Không thể xóa vai trò này vì đang có {$result['count']} người dùng sử dụng.");
            }

            // Xóa các quyền liên quan
            $conn->begin_transaction();
            
            $delete_permissions = $conn->prepare("DELETE FROM vaitro_chucnang WHERE MaVaiTro = ?");
            $delete_permissions->bind_param("s", $maVaiTro);
            $delete_permissions->execute();
            
            $delete_role = $conn->prepare("DELETE FROM vaitro WHERE MaVaiTro = ?");
            $delete_role->bind_param("s", $maVaiTro);
            $delete_role->execute();
            
            $conn->commit();

            echo json_encode(['success' => true, 'message' => 'Xóa vai trò thành công!']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
            break;
    }
} catch (Exception $e) {
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
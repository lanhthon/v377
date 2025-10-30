<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Lấy dữ liệu JSON được gửi từ frontend
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
            // Lấy dữ liệu từ payload
            $hoTen = $data['hoTen'] ?? '';
            $chucVu = $data['chucVu'] ?? null; // Chấp nhận giá trị null
            $soDienThoai = $data['soDienThoai'] ?? null; // Chấp nhận giá trị null
            $tenDangNhap = $data['tenDangNhap'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $maVaiTro = $data['maVaiTro'] ?? '';

            // Kiểm tra các trường bắt buộc
            if (empty($hoTen) || empty($tenDangNhap) || empty($password) || empty($maVaiTro)) {
                 throw new Exception("Vui lòng điền đầy đủ các trường bắt buộc.");
            }

            // Mã hóa mật khẩu an toàn
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Cập nhật câu lệnh SQL để bao gồm ChucVu và SoDienThoai
            $stmt = $conn->prepare("INSERT INTO nguoidung (HoTen, ChucVu, SoDienThoai, TenDangNhap, Email, PasswordHash, MaVaiTro) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $hoTen, $chucVu, $soDienThoai, $tenDangNhap, $email, $passwordHash, $maVaiTro);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Thêm người dùng thành công!']);
            break;

        case 'update_info':
            // Hành động mới để cập nhật thông tin chi tiết (Chức vụ, SĐT)
            $userID = $data['userID'] ?? 0;
            $field = $data['field'] ?? '';
            $value = $data['value'] ?? '';

            if (empty($userID) || empty($field)) {
                 throw new Exception("Thiếu thông tin để cập nhật.");
            }

            // Chỉ cho phép cập nhật các trường được chỉ định để bảo mật
            $allowed_fields = ['ChucVu', 'SoDienThoai'];
            if (!in_array($field, $allowed_fields)) {
                throw new Exception("Trường cập nhật không hợp lệ.");
            }
            
            // Tên trường ($field) đã được xác thực, an toàn để sử dụng trong câu lệnh SQL
            $stmt = $conn->prepare("UPDATE nguoidung SET `$field` = ? WHERE UserID = ?");
            $stmt->bind_param("si", $value, $userID);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công!']);
            break;

        case 'update_role':
            $userID = $data['userID'];
            $maVaiTro = $data['maVaiTro'];
            
            $stmt = $conn->prepare("UPDATE nguoidung SET MaVaiTro = ? WHERE UserID = ?");
            $stmt->bind_param("si", $maVaiTro, $userID);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Cập nhật vai trò thành công!']);
            break;

        case 'delete':
            $userID = $data['userID'];
            
            $stmt = $conn->prepare("DELETE FROM nguoidung WHERE UserID = ?");
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Xóa người dùng thành công!']);
            break;
            
        case 'change_password':
            $userID = $data['userID'];
            $newPassword = $data['newPassword'];
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE nguoidung SET PasswordHash = ? WHERE UserID = ?");
            $stmt->bind_param("si", $passwordHash, $userID);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
            break;

        case 'toggle_status':
            $userID = $data['userID'];
            $newStatus = (int)$data['newStatus'];
            
            $stmt = $conn->prepare("UPDATE nguoidung SET TrangThai = ? WHERE UserID = ?");
            $stmt->bind_param("ii", $newStatus, $userID);
            $stmt->execute();
            $statusText = $newStatus === 1 ? 'Mở khóa' : 'Khóa';
            echo json_encode(['success' => true, 'message' => $statusText . ' tài khoản thành công!']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    // Xử lý lỗi trùng tên đăng nhập
    if ($conn->errno == 1062) {
         echo json_encode(['success' => false, 'message' => 'Lỗi: Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.']);
    } else {
         echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
    }
}

$conn->close();
?>
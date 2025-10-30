<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Kết nối CSDL
require_once '../config/database.php'; 

// Nhận dữ liệu JSON từ request
$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.']);
    exit;
}

try {
    // 1. SỬA LẠI: Thêm cột `TrangThai` và `MaVaiTro` vào câu lệnh SELECT
    $stmt = $conn->prepare("SELECT UserID, HoTen, PasswordHash, MaVaiTro, TrangThai FROM nguoidung WHERE TenDangNhap = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Xác thực mật khẩu
        if (password_verify($password, $user['PasswordHash'])) {
            
            // 2. THÊM BƯỚC KIỂM TRA TÀI KHOẢN BỊ KHÓA
            if ($user['TrangThai'] == 0) {
                // Nếu TrangThai = 0 (Bị khóa), trả về lỗi
                echo json_encode(['success' => false, 'message' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.']);
                exit;
            }

            // Đăng nhập thành công, lưu thông tin vào session
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['user_full_name'] = $user['HoTen'];
            $_SESSION['user_role'] = $user['MaVaiTro']; // Sửa từ 'Role' thành 'MaVaiTro' cho nhất quán
            
            echo json_encode(['success' => true, 'message' => 'Đăng nhập thành công!']);

        } else {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu không chính xác.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập không tồn tại.']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}

$conn->close();
?>
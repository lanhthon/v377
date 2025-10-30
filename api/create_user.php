<?php
// File: api/create_user.php
session_start();
header('Content-Type: application/json; charset=utf-8');

// An ninh: Chỉ admin mới có quyền tạo người dùng
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này.']);
    exit;
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

// Lấy và kiểm tra dữ liệu đầu vào
$ho_ten = $data['ho_ten'] ?? '';
$ten_dang_nhap = $data['ten_dang_nhap'] ?? '';
$mat_khau = $data['mat_khau'] ?? '';
$email = $data['email'] ?? null;
$vai_tro = $data['vai_tro'] ?? '';

if (empty($ho_ten) || empty($ten_dang_nhap) || empty($mat_khau) || empty($vai_tro)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ các trường bắt buộc.']);
    exit;
}

// Kiểm tra xem tên đăng nhập đã tồn tại chưa
$stmt_check = $conn->prepare("SELECT UserID FROM NguoiDung WHERE TenDangNhap = ?");
$stmt_check->bind_param("s", $ten_dang_nhap);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.']);
    $stmt_check->close();
    $conn->close();
    exit;
}
$stmt_check->close();

// Mã hóa mật khẩu
$password_hash = password_hash($mat_khau, PASSWORD_DEFAULT);

try {
    // Thêm người dùng mới vào CSDL
    $stmt_insert = $conn->prepare("INSERT INTO NguoiDung (HoTen, TenDangNhap, PasswordHash, Email, Role) VALUES (?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("sssss", $ho_ten, $ten_dang_nhap, $password_hash, $email, $vai_tro);
    
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tạo tài khoản thành công!']);
    } else {
        throw new Exception($stmt_insert->error);
    }
    $stmt_insert->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi khi tạo tài khoản: ' . $e->getMessage()]);
}

$conn->close();
?>
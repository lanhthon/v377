<?php
// File: api/get_users.php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Chỉ admin mới có quyền xem danh sách người dùng
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này.']);
    exit;
}

require_once '../config/database.php';

try {
    $result = $conn->query("SELECT UserID, HoTen, TenDangNhap, Email, Role, CreatedAt FROM NguoiDung ORDER BY HoTen ASC");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ.']);
}

$conn->close();
?>
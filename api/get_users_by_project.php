<?php
/**
 * File: api/get_users_by_project.php
 * Mục đích: Lấy danh sách TẤT CẢ người dùng trong hệ thống.
 * Yêu cầu: Bất kỳ ai cũng có thể truy cập.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// --- KIỂM TRA QUYỀN TRUY CẬP ĐÃ ĐƯỢC GỠ BỎ ---
// Đoạn mã kiểm tra vai trò admin đã được xóa theo yêu cầu.
// Giờ đây bất kỳ ai cũng có thể truy cập API này.

// --- Kết nối CSDL và xử lý logic ---
require_once '../config/database.php'; // Thay đổi đường dẫn nếu cần

try {
    // Lấy tất cả người dùng, sắp xếp theo tên
    $result = $conn->query("SELECT UserID, HoTen, TenDangNhap, Email, CreatedAt FROM nguoidung ORDER BY HoTen ASC");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    // Trả về kết quả
    echo json_encode(['success' => true, 'users' => $users]);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}

$conn->close();
?>

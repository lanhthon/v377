<?php
// api/get_current_user.php
session_start(); // Bắt đầu session để có thể truy cập biến $_SESSION

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra xem người dùng đã đăng nhập và có thông tin trong session chưa
if (isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name'])) {
    // Nếu có, trả về tên người dùng
    echo json_encode(['success' => true, 'HoTen' => $_SESSION['user_full_name']]);
} else {
    // Nếu chưa đăng nhập, trả về một tên mặc định hoặc thông báo lỗi
    echo json_encode(['success' => false, 'message' => 'Người dùng chưa đăng nhập.']);
}
?>

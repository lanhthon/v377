<?php
// Tắt báo cáo lỗi không quan trọng, chỉ báo cáo lỗi nghiêm trọng


// --- THAY ĐỔI THÔNG TIN KẾT NỐI CỦA BẠN TẠI ĐÂY ---
define('DB_HOST', 'localhost');    // Thường là 'localhost'
define('DB_USER', 'eedsyydkhosting_3igreen');         // Tên người dùng database của bạn
define('DB_PASS', '3igreen@Pass11082025');             // Mật khẩu database của bạn
define('DB_NAME', 'eedsyydkhosting_v279'); // Tên database bạn đã tạo

// Tạo kết nối đến database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập charset thành UTF-8 để hỗ trợ tiếng Việt
if (!$conn->set_charset("utf8mb4")) {
    printf("Lỗi khi cài đặt charset utf8mb4: %s\n", $conn->error);
    exit();
}

// Bạn không cần đóng kết nối ở đây, các file khác sẽ sử dụng biến $conn này.
?>
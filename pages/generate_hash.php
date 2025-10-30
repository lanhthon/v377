<?php
// File: generate_hash.php
// Dùng để tạo chuỗi mã hóa mật khẩu mới.

// 1. Mật khẩu bạn muốn đặt (có thể thay đổi)
$password_to_hash = 'admin123';

// 2. Mã hóa mật khẩu
$new_hash = password_hash($password_to_hash, PASSWORD_DEFAULT);

// 3. Hiển thị kết quả ra màn hình
echo "Mật khẩu mới cho '" . $password_to_hash . "' là:<br><br>";
echo "<strong style='font-size: 16px; background-color: #eee; padding: 5px; border: 1px solid #ccc;'>" . $new_hash . "</strong>";

?>
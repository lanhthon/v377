<?php
// File: basic_check.php - Kiểm tra cơ bản nhất
echo "PHP hoạt động bình thường!<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Thời gian: " . date('Y-m-d H:i:s') . "<br>";

// Kiểm tra composer
if (file_exists('composer.json')) {
    echo "✅ File composer.json tồn tại<br>";
} else {
    echo "❌ File composer.json không tồn tại<br>";
}

if (file_exists('vendor/autoload.php')) {
    echo "✅ File vendor/autoload.php tồn tại<br>";
} else {
    echo "❌ File vendor/autoload.php không tồn tại<br>";
    echo "<strong>Cần chạy: composer install</strong><br>";
}

// Hiển thị cấu trúc thư mục
echo "<br>Cấu trúc thư mục hiện tại:<br>";
$files = scandir('.');
foreach($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "- " . $file . (is_dir($file) ? '/' : '') . "<br>";
    }
}
?>
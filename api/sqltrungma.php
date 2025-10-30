<?php
// Hiển thị dưới dạng văn bản thuần để dễ dàng sao chép
header('Content-Type: text/plain; charset=utf-8');

// ===================================================================
// THÔNG SỐ KẾT NỐI CƠ SỞ DỮ LIỆU
// !! Hãy thay đổi các thông số này cho phù hợp với bạn !!
// ===================================================================
require_once '../config/database.php';
$conn->set_charset("utf8mb4");

echo "-- ===================================================================\n";
echo "-- SCRIPT TẠO LỆNH XÓA CÁC SẢN PHẨM TRÙNG LẶP\n";
echo "-- NGÀY TẠO: " . date('Y-m-d H:i:s') . "\n";
echo "-- QUY TẮC: Giữ lại dòng có SanPhamID nhỏ nhất.\n";
echo "-- LƯU Ý: HÃY KIỂM TRA KỸ TRƯỚC KHI THỰC THI!\n";
echo "-- ===================================================================\n\n";

// Bắt đầu một transaction để có thể rollback nếu cần
echo "START TRANSACTION;\n\n";

// 1. Tìm tất cả MaHang bị trùng và ID nhỏ nhất của mỗi nhóm để giữ lại
$sql_find_duplicates = "
    SELECT 
        MaHang, 
        MIN(SanPhamID) as id_to_keep, 
        COUNT(*) as total_count
    FROM 
        sanpham 
    GROUP BY 
        MaHang 
    HAVING 
        COUNT(*) > 1
";
$duplicate_results = $conn->query($sql_find_duplicates);

if ($duplicate_results->num_rows > 0) {
    while($dup_row = $duplicate_results->fetch_assoc()) {
        $maHang = $dup_row['MaHang'];
        $id_to_keep = $dup_row['id_to_keep'];
        $total_count = $dup_row['total_count'];
        $num_to_delete = $total_count - 1;

        echo "-- Xử lý Mã Hàng '{$maHang}' (tìm thấy {$total_count} bản, sẽ xóa {$num_to_delete} bản).\n";
        echo "-- Giữ lại ID nhỏ nhất: {$id_to_keep}.\n";

        // 2. Tạo lệnh DELETE cho tất cả các dòng có cùng MaHang nhưng ID không phải là ID nhỏ nhất
        $maHang_escaped = $conn->real_escape_string($maHang);
        echo "DELETE FROM `sanpham` WHERE `MaHang` = '{$maHang_escaped}' AND `SanPhamID` != {$id_to_keep};\n\n";
    }
} else {
    echo "-- Tuyệt vời! Không tìm thấy mã hàng nào bị trùng lặp.\n";
}

$conn->close();

echo "-- Để xác nhận các thay đổi trên, hãy chạy lệnh COMMIT;\n";
echo "-- Để hủy bỏ, hãy chạy lệnh ROLLBACK;\n";
echo "-- COMMIT;\n";

?>
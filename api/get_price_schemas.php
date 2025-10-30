<?php
// File: api/get_price_schemas.php
// API endpoint để lấy tất cả các cơ chế giá từ bảng cochegia.

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$schemas = [];
// Lấy PhanTramDieuChinh với tên gốc từ DB
$sql = "SELECT MaCoChe, TenCoChe, PhanTramDieuChinh FROM cochegia ORDER BY MaCoChe ASC";

$result = $conn->query($sql);

if ($result) {
    while($row = $result->fetch_assoc()) {
        // Không còn chuyển đổi HeSoGia ở đây, trả về giá trị nguyên bản
        $schemas[] = $row;
    }
    echo json_encode($schemas, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn cơ sở dữ liệu: ' . $conn->error]);
}

$conn->close();
?>
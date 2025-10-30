<?php
// File: api/get_all_prices.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$prices = [];
$sql = "SELECT CoCheGiaID, MaCoChe, TenCoChe, PhanTramDieuChinh FROM cochegia ORDER BY MaCoChe ASC";
$result = $conn->query($sql);

if ($result) {
    while($row = $result->fetch_assoc()) {
        $prices[] = $row;
    }
    echo json_encode($prices, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn CSDL.']);
}
$conn->close();
?>
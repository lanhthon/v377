<?php
// File: api/get_all_customers.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$customers = [];
$sql = "SELECT k.*, c.TenCoChe, c.MaCoChe
        FROM khachhang k 
        LEFT JOIN cochegia c ON k.CoCheGiaID = c.CoCheGiaID 
        ORDER BY k.TenCongTy ASC";
$result = $conn->query($sql);

if ($result) {
    while($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    echo json_encode($customers, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn CSDL.']);
}
$conn->close();
?>
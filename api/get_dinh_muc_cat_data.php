<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
try {
    $sql = "SELECT TenNhomDN, BanRong, SoBoTrenCay FROM dinh_muc_cat ORDER BY MinDN, BanRong";
    $result = $conn->query($sql);
    $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>
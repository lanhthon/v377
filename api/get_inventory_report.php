<?php
// File: api/get_inventory_report.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $sql = "SELECT v.variant_sku, v.variant_name, vi.quantity, vi.minimum_stock_level
            FROM variant_inventory vi
            JOIN variants v ON vi.variant_id = v.variant_id
            WHERE vi.quantity <= vi.minimum_stock_level AND vi.minimum_stock_level > 0
            ORDER BY (vi.quantity - vi.minimum_stock_level) ASC";
    $result = $conn->query($sql);
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
$conn->close();
?>
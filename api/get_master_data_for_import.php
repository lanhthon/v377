<?php
// File: api/get_master_data_for_import.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Đảm bảo đường dẫn này đúng

$response = [
    'success' => false,
    'products' => [],
    'suppliers' => []
];

try {
    // Lấy danh sách sản phẩm
    $result_products = $conn->query("SELECT SanPhamID, MaHang, TenSanPham FROM sanpham ORDER BY TenSanPham ASC");
    while ($row = $result_products->fetch_assoc()) {
        $response['products'][] = $row;
    }

    // Lấy danh sách nhà cung cấp
    $result_suppliers = $conn->query("SELECT NhaCungCapID, TenNhaCungCap FROM NhaCungCap ORDER BY TenNhaCungCap ASC");
    while ($row = $result_suppliers->fetch_assoc()) {
        $response['suppliers'][] = $row;
    }

    $response['success'] = true;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
}

$conn->close();
?>
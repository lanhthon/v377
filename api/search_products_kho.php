<?php
// File: api/search_products_kho.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Lấy từ khóa và xử lý
$rawQuery = trim($_GET['q'] ?? '');

if (empty($rawQuery)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập từ khóa.']);
    exit;
}

try {
    global $conn;

    // Tìm kiếm toàn bộ cụm từ thay vì từng từ riêng lẻ
    $searchTerm = "%" . $rawQuery . "%";
    $params = [$searchTerm, $searchTerm];
    $param_types = 'ss';

    // SỬA LỖI: Loại bỏ hàm REPLACE() để tìm kiếm chính xác chuỗi gốc
    $where_clause = "v.variant_sku LIKE ? OR v.variant_name LIKE ?";
    
    $sql = "SELECT 
                v.variant_id AS productId, 
                v.variant_sku AS code, 
                v.variant_name AS name,
                COALESCE(vi.quantity, 0) AS currentStock
            FROM variants v 
            JOIN products p ON v.product_id = p.product_id
            LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
            LEFT JOIN product_groups pg ON p.group_id = pg.group_id
            WHERE 
                ({$where_clause})
                AND v.variant_sku NOT LIKE 'PUR%' -- THAY ĐỔI: Loại trừ các sản phẩm có mã bắt đầu bằng 'PUR'
            LIMIT 12";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
    }

    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>


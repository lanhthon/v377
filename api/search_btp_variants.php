<?php
/**
 * File: api/search_btp_variants.php
 * API tìm kiếm Bán Thành Phẩm.
 * SAO CHÉP VÀ CHỈNH SỬA TỪ FILE search_products.php ĐANG HOẠT ĐỘNG
 */
header('Content-Type: application/json; charset=utf-8');
// Sử dụng đúng file config và hàm kết nối CSDL của bạn
require_once '../config/db_config.php'; 

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$response = ['success' => false, 'data' => [], 'message' => ''];

if (strlen($query) < 2) {
    $response['message'] = 'Từ khóa tìm kiếm quá ngắn.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Sử dụng đúng hàm kết nối CSDL của bạn
    $pdo = get_db_connection();
    
    $sql = "SELECT 
                v.variant_id, 
                v.variant_sku, 
                v.variant_name
            FROM variants AS v
            JOIN products AS p ON v.product_id = p.product_id
            WHERE p.group_id = 1 
                AND (v.variant_sku LIKE :query_sku OR v.variant_name LIKE :query_name)
            LIMIT 15";
            
    $stmt = $pdo->prepare($sql);
    
    $search_term = "%$query%";
    $stmt->execute([
        ':query_sku' => $search_term,
        ':query_name' => $search_term
    ]);
    
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
    error_log("Lỗi trong api/search_btp_variants.php: " . $e->getMessage()); 
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
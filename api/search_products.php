<?php
/**
 * File: api/search_products.php
 * API này dùng để tìm kiếm các sản phẩm cho chức năng nhập kho vật tư.
 * Nó sẽ chỉ trả về các sản phẩm thuộc nhóm "Vật tư".
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php'; // Sử dụng đường dẫn kết nối CSDL bạn đã chỉ định

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$response = ['success' => false, 'data' => [], 'message' => ''];

// Yêu cầu phải có từ khóa tìm kiếm dài ít nhất 2 ký tự
if (strlen($query) < 2) {
    $response['message'] = 'Từ khóa tìm kiếm quá ngắn.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = get_db_connection();
    
    // SỬA LỖI: Sử dụng hai placeholder khác nhau (:query_sku và :query_name)
    // để tránh lỗi "Invalid parameter number".
    $sql = "SELECT 
                v.variant_id, 
                v.variant_sku, 
                v.variant_name
            FROM variants AS v
            JOIN products AS p ON v.product_id = p.product_id
            WHERE 
                p.group_id = 3 -- Lọc chỉ lấy VẬT TƯ
                AND (v.variant_sku LIKE :query_sku OR v.variant_name LIKE :query_name)
            LIMIT 15"; // Giới hạn 15 kết quả để tối ưu hiệu suất
            
    $stmt = $pdo->prepare($sql);
    
    // SỬA LỖI: Cung cấp giá trị cho cả hai placeholder.
    $search_term = "%$query%";
    $stmt->execute([
        ':query_sku' => $search_term,
        ':query_name' => $search_term
    ]);
    
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
    // Ghi lại lỗi thực tế vào log của server để debug, không hiển thị chi tiết cho người dùng
    error_log($e->getMessage()); 
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

?>

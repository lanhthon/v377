<?php
/**
 * File: api/search_production_items.php
 * Version: 2.1 (Fixed SQL Parameter Bug)
 * Description: API để tìm kiếm các sản phẩm (biến thể) có thể sản xuất 
 * dựa trên từ khóa tìm kiếm.
 * - [CẬP NHẬT V2.1] Sửa lỗi SQL "Invalid parameter number" bằng cách sử dụng
 * hai placeholder (:query_sku và :query_name) riêng biệt cho câu lệnh LIKE.
 * - [CẬP NHẬT V2.0] Gỡ bỏ giới hạn tìm kiếm theo nhóm sản phẩm (BTP, ULA).
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Từ khóa tìm kiếm phải có ít nhất 2 ký tự.']);
    exit;
}

try {
    $pdo = get_db_connection();

    // SỬA LỖI: Sử dụng hai placeholder khác nhau để tránh lỗi PDO
    $sql = "
        SELECT 
            v.variant_sku, 
            v.variant_name
        FROM variants v
        WHERE 
            (v.variant_sku LIKE :query_sku OR v.variant_name LIKE :query_name)
        ORDER BY v.variant_name
        LIMIT 15
    ";

    $stmt = $pdo->prepare($sql);
    
    // Gán giá trị cho cả hai placeholder
    $searchTerm = '%' . $query . '%';
    $stmt->execute([
        ':query_sku' => $searchTerm,
        ':query_name' => $searchTerm
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $results]);

} catch (Exception $e) {
    error_log("Lỗi API search_production_items: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống khi tìm kiếm sản phẩm.']);
}
?>


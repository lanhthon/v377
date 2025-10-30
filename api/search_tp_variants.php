<?php
// File: api/search_tp_variants.php
require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$response = ['success' => false, 'data' => []];
try {
    $pdo = get_db_connection();
    // product_groups: group_id = 2 là Thành phẩm
    $sql = "SELECT v.variant_id, v.variant_sku, v.variant_name
            FROM variants AS v
            JOIN products AS p ON v.product_id = p.product_id
            WHERE p.group_id = 2 AND (v.variant_sku LIKE :q_sku OR v.variant_name LIKE :q_name)
            LIMIT 15";
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->execute([':q_sku' => $searchTerm, ':q_name' => $searchTerm]);
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
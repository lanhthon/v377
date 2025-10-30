<?php
// File: api/get_suppliers.php
require_once '../config/db_config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'data' => []];

try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT NhaCungCapID, TenNhaCungCap FROM nhacungcap ORDER BY TenNhaCungCap ASC");
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;
} catch (PDOException $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>

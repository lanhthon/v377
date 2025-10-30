<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => []];

try {
    $result = $conn->query("SELECT CoCheGiaID, TenCoChe FROM cochegia ORDER BY MaCoChe");
    $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
    $response['success'] = true;
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
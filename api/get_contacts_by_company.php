<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => []];
$company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($company_id <= 0) {
    $response['message'] = "ID công ty không hợp lệ.";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT NguoiLienHeID, HoTen, ChucVu, Email, SoDiDong, CongTyID FROM nguoilienhe WHERE CongTyID = ? ORDER BY HoTen");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }
    
    $response['success'] = true;
    $response['data'] = $contacts;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = "Lỗi CSDL: " . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
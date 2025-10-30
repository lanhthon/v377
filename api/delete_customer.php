<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => 'Invalid ID provided.'];

if (isset($data['id'])) {
    $id = (int)$data['id'];
    $sql = "DELETE FROM khachhang WHERE KhachHangID = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Customer deleted successfully!';
            } else {
                http_response_code(404);
                $response['message'] = 'Customer not found or already deleted.';
            }
        } else {
            http_response_code(500);
            $response['message'] = 'Execute failed: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        http_response_code(500);
        $response['message'] = 'Prepare failed: ' . $conn->error;
    }
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
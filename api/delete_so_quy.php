<?php
/**
 * File: api/delete_so_quy.php
 * Description: Xóa giao dịch sổ quỹ
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        $response['message'] = 'Chưa đăng nhập';
        echo json_encode($response);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        $response['message'] = 'ID không hợp lệ';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();

    // Xóa giao dịch
    $sql = "DELETE FROM so_quy WHERE SoQuyID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi xóa: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('Không tìm thấy giao dịch');
    }

    // Cập nhật lại số dư các giao dịch sau đó
    // (Trong thực tế, bạn nên viết stored procedure để làm việc này)
    
    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Xóa thành công';

    $stmt->close();

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

if (isset($conn)) {
    $conn->close();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
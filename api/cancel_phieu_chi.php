<?php
/* ========================================
   FILE: api/cancel_phieu_chi.php
   ======================================== */
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

    $sql = "UPDATE phieu_chi 
            SET TrangThai = 'da_huy' 
            WHERE PhieuChiID = ? AND TrangThai = 'cho_duyet'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi hủy: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('Không thể hủy phiếu chi này');
    }

    $response['success'] = true;
    $response['message'] = 'Hủy phiếu chi thành công';

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

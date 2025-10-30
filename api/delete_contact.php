<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    $response['message'] = 'ID người liên hệ là bắt buộc.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sql = "DELETE FROM nguoilienhe WHERE NguoiLienHeID = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi prepare statement: " . $conn->error);
    }

    $stmt->bind_param("i", $data['id']);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Xóa người liên hệ thành công!';
        } else {
            throw new Exception("Không tìm thấy người liên hệ để xóa.");
        }
    } else {
        throw new Exception("Lỗi thực thi: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['NguoiLienHeID']) || empty($data['HoTen'])) {
    $response['message'] = 'ID và Họ tên người liên hệ là bắt buộc.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sql = "UPDATE nguoilienhe SET HoTen = ?, ChucVu = ?, Email = ?, SoDiDong = ? WHERE NguoiLienHeID = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi prepare statement: " . $conn->error);
    }

    // SỬA LỖI: Chuyển email rỗng ('') thành NULL
    $email = !empty($data['Email']) ? $data['Email'] : null;

    $stmt->bind_param(
        "ssssi",
        $data['HoTen'],
        $data['ChucVu'],
        $email, // Sử dụng biến đã được xử lý
        $data['SoDiDong'],
        $data['NguoiLienHeID']
    );

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Cập nhật người liên hệ thành công!';
        } else {
            $response['success'] = true;
            $response['message'] = 'Không có thay đổi nào được ghi nhận.';
        }
    } else {
        // Handle potential duplicate entry for non-empty emails
        if ($conn->errno == 1062) {
             throw new Exception("Lỗi: Email này đã tồn tại trong hệ thống.");
        }
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
<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            // SỬA LỖI: Thêm cột PhanTramDieuChinh vào câu lệnh SELECT
            $result = $conn->query("SELECT CoCheGiaID, MaCoChe, TenCoChe, PhanTramDieuChinh FROM cochegia ORDER BY MaCoChe");
            if ($result === false) {
                throw new Exception("Lỗi truy vấn: " . $conn->error);
            }
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
            $response['success'] = true;
            break;

        case 'add':
            if (empty($data['MaCoChe']) || empty($data['TenCoChe']) || !isset($data['PhanTramDieuChinh'])) {
                throw new Exception('Mã, Tên và % Điều chỉnh là bắt buộc.');
            }
            $stmt = $conn->prepare("INSERT INTO cochegia (MaCoChe, TenCoChe, PhanTramDieuChinh) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $data['MaCoChe'], $data['TenCoChe'], $data['PhanTramDieuChinh']);
            $stmt->execute();
            $response['success'] = true;
            $response['message'] = 'Thêm cơ chế giá thành công!';
            break;

        case 'update':
            if (empty($data['id']) || empty($data['MaCoChe']) || empty($data['TenCoChe']) || !isset($data['PhanTramDieuChinh'])) {
                throw new Exception('ID, Mã, Tên và % Điều chỉnh là bắt buộc.');
            }
            $stmt = $conn->prepare("UPDATE cochegia SET MaCoChe = ?, TenCoChe = ?, PhanTramDieuChinh = ? WHERE CoCheGiaID = ?");
            $stmt->bind_param("ssdi", $data['MaCoChe'], $data['TenCoChe'], $data['PhanTramDieuChinh'], $data['id']);
            $stmt->execute();
            $response['success'] = true;
            $response['message'] = 'Cập nhật thành công!';
            break;

        case 'delete':
            if (empty($data['id'])) {
                throw new Exception('ID là bắt buộc.');
            }
            $stmt = $conn->prepare("DELETE FROM cochegia WHERE CoCheGiaID = ?");
            $stmt->bind_param("i", $data['id']);
            $stmt->execute();
            $response['success'] = true;
            $response['message'] = 'Xóa thành công!';
            break;

        default:
            throw new Exception('Hành động không hợp lệ.');
    }
} catch (Exception $e) {
    http_response_code(500);
    // Kiểm tra lỗi ràng buộc khóa ngoại khi xóa
    if ($conn->errno == 1451) {
         $response['message'] = 'Không thể xóa cơ chế giá này vì đang có công ty sử dụng.';
    } else {
         $response['message'] = $e->getMessage();
    }
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
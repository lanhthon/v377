
<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => 'Dữ liệu không hợp lệ.'];

if (!empty($data['MaDuAn']) && !empty($data['TenDuAn'])) {
    // Kiểm tra xem Mã hoặc Tên dự án đã tồn tại chưa
    $checkSql = "SELECT DuAnID FROM DuAn WHERE MaDuAn = ? OR TenDuAn = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $data['MaDuAn'], $data['TenDuAn']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $response['message'] = 'Mã hoặc Tên dự án đã tồn tại.';
    } else {
        // Thêm cả địa chỉ dự án
        $sql = "INSERT INTO DuAn (MaDuAn, TenDuAn, DiaChi) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $diaChi = $data['DiaChi'] ?? ''; // Lấy địa chỉ, mặc định rỗng nếu không có
            $stmt->bind_param(
                "sss",
                $data['MaDuAn'],
                $data['TenDuAn'],
                $diaChi
            );

            if ($stmt->execute()) {
                $newProjectId = $stmt->insert_id;
                $response['success'] = true;
                $response['message'] = 'Thêm dự án thành công!';
                // Trả về thông tin dự án vừa tạo để JS cập nhật
                $response['newProject'] = [
                    'DuAnID' => $newProjectId,
                    'MaDuAn' => $data['MaDuAn'],
                    'TenDuAn' => $data['TenDuAn'],
                    'DiaChi' => $diaChi // Thêm địa chỉ vào response
                ];
            } else {
                http_response_code(500);
                $response['message'] = 'Lỗi thực thi: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            http_response_code(500);
            $response['message'] = 'Lỗi chuẩn bị câu lệnh: ' . $conn->error;
        }
    }
    $checkStmt->close();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

<?php
// api/get_preparations_pending_issuance.php
// API này lấy danh sách các đơn hàng có trạng thái "Chờ xuất kho"
// và join với bảng chuanbihang để lấy CBH_ID.

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Lấy các đơn hàng có trạng thái 'Chờ xuất kho' và chưa có phiếu xuất kho.
    // Quan trọng: Join với `chuanbihang` để lấy `CBH_ID`.
    $sql = "SELECT
                dh.YCSX_ID,
                dh.SoYCSX,
                dh.NgayTao,
                dh.NgayGiaoDuKien,
                dh.TenCongTy,
                cbh.CBH_ID
            FROM donhang dh
            JOIN chuanbihang cbh ON dh.YCSX_ID = cbh.YCSX_ID
            WHERE dh.TrangThai = 'Chờ xuất kho'
            AND dh.YCSX_ID NOT IN (
                SELECT YCSX_ID FROM phieuxuatkho WHERE YCSX_ID IS NOT NULL
            )
            ORDER BY dh.NgayGiaoDuKien ASC, dh.NgayTao ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $response['success'] = true;
    $response['data'] = $data;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi truy vấn CSDL: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
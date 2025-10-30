<?php
// api/get_orders_pending_issuance.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Adjust path if necessary

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // This SQL selects orders that are 'Chờ xuất kho' (Pending Issuance/Ready for Dispatch)
    $sql = "SELECT
                dh.YCSX_ID,
                dh.SoYCSX,
                dh.NgayTao,
                dh.NgayGiaoDuKien,
                bg.TenCongTy,
                dh.TrangThai
            FROM donhang dh
            JOIN baogia bg ON dh.BaoGiaID = bg.BaoGiaID
            WHERE dh.TrangThai = 'Chờ xuất kho' -- Chỉ lấy trạng thái 'Chờ xuất kho'
            AND dh.YCSX_ID NOT IN (SELECT YCSX_ID FROM phieuxuatkho WHERE YCSX_ID IS NOT NULL)
            ORDER BY dh.NgayTao DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

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
<?php
/**
 * File: api/get_orders_for_issue.php
 * Version: 2.0
 * Description: API lấy danh sách các Phiếu Chuẩn Bị Hàng (CBH) đang ở trạng thái 'Chờ xuất kho'.
 * - Thay đổi logic từ lấy theo `donhang` sang lấy theo `chuanbihang` để hỗ trợ nhiều đợt giao.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Đảm bảo đường dẫn này đúng

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Truy vấn các phiếu chuẩn bị hàng có trạng thái 'Chờ xuất kho'
    // JOIN với đơn hàng để lấy thông tin chung
    $sql = "SELECT
                cbh.CBH_ID,
                cbh.SoCBH,
                dh.SoYCSX,
                dh.TenCongTy,
                cbh.NgayTao,
                cbh.NgayGiao,
                cbh.TrangThai
            FROM chuanbihang cbh
            JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
            WHERE cbh.TrangThai = 'Chờ xuất kho'
            ORDER BY cbh.NgayTao DESC";

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

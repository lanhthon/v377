<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    // THÊM d.NeedsProduction vào câu lệnh SELECT
    $sql = "SELECT d.YCSX_ID, d.BaoGiaID, d.SoYCSX, d.NgayTao, d.NgayGiaoDuKien, d.TrangThai, d.NeedsProduction, b.TenCongTy 
            FROM donhang d 
            JOIN baogia b ON d.BaoGiaID = b.BaoGiaID 
            ORDER BY d.NgayTao DESC";
    
    $result = $conn->query($sql);
    $data = [];
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}

$conn->close();
?>
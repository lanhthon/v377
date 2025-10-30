<?php
// File: api/get_production_report.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $report = ['summary' => [], 'recent_orders' => []];
    // Tổng hợp theo trạng thái
    $result = $conn->query("SELECT TrangThai, COUNT(*) as count FROM lenh_san_xuat GROUP BY TrangThai");
    while($row = $result->fetch_assoc()) {
        $report['summary'][$row['TrangThai']] = $row['count'];
    }
    
    // 10 lệnh sản xuất gần nhất
    $result = $conn->query("SELECT SoLenhSX, NgayTao, LoaiLSX, TrangThai FROM lenh_san_xuat ORDER BY NgayTao DESC LIMIT 10");
    $report['recent_orders'] = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $report]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
$conn->close();
?>
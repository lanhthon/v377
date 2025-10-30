<?php
// File: api/get_quote_report.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$customer_name = $_GET['customer_name'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

try {
    $sql = "SELECT SoBaoGia, NgayBaoGia, TenCongTy, TongTienSauThue, TrangThai FROM baogia WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($customer_name)) {
        $sql .= " AND TenCongTy = ?";
        $params[] = $customer_name;
        $types .= 's';
    }
    if (!empty($start_date)) {
        $sql .= " AND NgayBaoGia >= ?";
        $params[] = $start_date;
        $types .= 's';
    }
    if (!empty($end_date)) {
        $sql .= " AND NgayBaoGia <= ?";
        $params[] = $end_date;
        $types .= 's';
    }
    $sql .= " ORDER BY NgayBaoGia DESC, BaoGiaID DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $summary = ['total_quotes' => count($data), 'total_value' => 0, 'status_counts' => []];
    foreach ($data as $quote) {
        $summary['total_value'] += (float)$quote['TongTienSauThue'];
        $status = $quote['TrangThai'] ?: 'Chưa xác định';
        $summary['status_counts'][$status] = ($summary['status_counts'][$status] ?? 0) + 1;
    }
    
    echo json_encode(['success' => true, 'summary' => $summary, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
$conn->close();
?>
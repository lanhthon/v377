<?php
// File: api/get_reports.php
// API để lấy dữ liệu báo cáo tổng hợp.

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Lấy tham số từ request
$customer_name = isset($_GET['customer_name']) ? trim($_GET['customer_name']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

try {
    // Xây dựng câu lệnh SQL động và an toàn
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
    if ($stmt === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh SQL: " . $conn->error);
    }
    
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    
    // Tính toán dữ liệu tổng hợp
    $summary = [
        'total_quotes' => 0,
        'total_value' => 0,
        'status_counts' => []
    ];

    if (count($data) > 0) {
        $summary['total_quotes'] = count($data);
        foreach ($data as $quote) {
            $summary['total_value'] += (float)$quote['TongTienSauThue'];
            $status = $quote['TrangThai'] ?: 'Chưa xác định';
            if (!isset($summary['status_counts'][$status])) {
                $summary['status_counts'][$status] = 0;
            }
            $summary['status_counts'][$status]++;
        }
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
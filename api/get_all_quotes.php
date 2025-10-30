<?php
/**
 * api/get_all_quotes.php
 * API Endpoint to fetch all quotes with optional filters for date range, company name, and status.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = [];

try {
    // Đã loại bỏ cột 'DonHangID' vì nó không tồn tại trực tiếp trong bảng 'baogia'
    $sql = "SELECT BaoGiaID, SoBaoGia, NgayBaoGia, TenCongTy, TongTienSauThue, TrangThai FROM baogia WHERE 1=1";
    $params = [];
    $types = "";

    // Filter by start date
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $sql .= " AND NgayBaoGia >= ?";
        $params[] = $_GET['start_date'];
        $types .= "s";
    }

    // Filter by end date
    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $sql .= " AND NgayBaoGia <= ?";
        $params[] = $_GET['end_date'];
        $types .= "s";
    }

    // Filter by company name
    if (isset($_GET['company_name']) && !empty($_GET['company_name'])) {
        $sql .= " AND TenCongTy LIKE ?";
        $params[] = '%' . $_GET['company_name'] . '%';
        $types .= "s";
    }

    // Filter by status
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $sql .= " AND TrangThai = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }

    $sql .= " ORDER BY NgayBaoGia DESC, BaoGiaID DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Format date for display if needed, or keep as Y-m-d for consistency with input type="date"
        $row['NgayBaoGiaFormatted'] = date('d/m/Y', strtotime($row['NgayBaoGia']));
        $response[] = $row;
    }

    $stmt->close();

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi Server: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

$conn->close();
?>
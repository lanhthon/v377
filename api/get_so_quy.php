<?php
/**
 * File: api/get_so_quy.php
 * Description: Lấy danh sách giao dịch sổ quỹ với bộ lọc
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$response = ['success' => false, 'data' => [], 'summary' => [], 'message' => ''];

try {
    // Kiểm tra quyền truy cập
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        $response['message'] = 'Chưa đăng nhập';
        echo json_encode($response);
        exit;
    }

    // Lấy các tham số lọc
    $dateFrom = isset($_GET['dateFrom']) && !empty($_GET['dateFrom']) ? $_GET['dateFrom'] : null;
    $dateTo = isset($_GET['dateTo']) && !empty($_GET['dateTo']) ? $_GET['dateTo'] : null;
    $loai = isset($_GET['loai']) && !empty($_GET['loai']) ? $_GET['loai'] : null;
    $search = isset($_GET['search']) && !empty($_GET['search']) ? $_GET['search'] : null;

    // Build SQL query
    $sql = "SELECT 
                sq.SoQuyID,
                sq.NgayGhiSo,
                sq.LoaiGiaoDich,
                sq.SoChungTu,
                sq.NoiDung,
                sq.DoiTuong,
                sq.SoTienThu,
                sq.SoTienChi,
                sq.SoDu,
                sq.GhiChu,
                nd.HoTen as NguoiLap,
                sq.created_at
            FROM so_quy sq
            LEFT JOIN nguoidung nd ON sq.NguoiLap = nd.UserID
            WHERE 1=1";
    
    $params = [];
    $types = "";

    if ($dateFrom) {
        $sql .= " AND sq.NgayGhiSo >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }

    if ($dateTo) {
        $sql .= " AND sq.NgayGhiSo <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }

    if ($loai) {
        $sql .= " AND sq.LoaiGiaoDich = ?";
        $params[] = $loai;
        $types .= "s";
    }

    if ($search) {
        $sql .= " AND (sq.NoiDung LIKE ? OR sq.DoiTuong LIKE ? OR sq.SoChungTu LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }

    $sql .= " ORDER BY sq.NgayGhiSo DESC, sq.SoQuyID DESC";

    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Tính tổng kết
    $summarySql = "SELECT 
                    SUM(SoTienThu) as TongThu,
                    SUM(SoTienChi) as TongChi,
                    (SELECT SoDu FROM so_quy ORDER BY NgayGhiSo DESC, SoQuyID DESC LIMIT 1) as TonQuy
                FROM so_quy
                WHERE 1=1";
    
    $summaryParams = [];
    $summaryTypes = "";
    
    if ($dateFrom) {
        $summarySql .= " AND NgayGhiSo >= ?";
        $summaryParams[] = $dateFrom;
        $summaryTypes .= "s";
    }
    
    if ($dateTo) {
        $summarySql .= " AND NgayGhiSo <= ?";
        $summaryParams[] = $dateTo;
        $summaryTypes .= "s";
    }

    $stmtSummary = $conn->prepare($summarySql);
    if (!empty($summaryParams)) {
        $stmtSummary->bind_param($summaryTypes, ...$summaryParams);
    }
    $stmtSummary->execute();
    $summaryResult = $stmtSummary->get_result();
    $summary = $summaryResult->fetch_assoc();

    $response['success'] = true;
    $response['data'] = $data;
    $response['summary'] = [
        'tongThu' => floatval($summary['TongThu'] ?? 0),
        'tongChi' => floatval($summary['TongChi'] ?? 0),
        'tonQuy' => floatval($summary['TonQuy'] ?? 0)
    ];

    $stmt->close();
    $stmtSummary->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi truy vấn CSDL: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
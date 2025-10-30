<?php
/* ========================================
   FILE: api/get_bao_cao_cong_no.php
   ======================================== */
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$response = ['success' => false, 'data' => [], 'summary' => [], 'message' => ''];

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        $response['message'] = 'Chưa đăng nhập';
        echo json_encode($response);
        exit;
    }

    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;

    $sql = "SELECT 
                dh.YCSX_ID,
                dh.SoYCSX,
                dh.TenCongTy,
                dh.TongTien,
                cn.SoTienTamUng,
                cn.GiaTriConLai,
                cn.NgayXuatHoaDon,
                cn.ThoiHanThanhToan,
                cn.TrangThaiThanhToan,
                DATEDIFF(cn.ThoiHanThanhToan, CURDATE()) as SoNgayConLai
            FROM donhang dh
            INNER JOIN quanly_congno cn ON dh.YCSX_ID = cn.YCSX_ID
            WHERE 1=1";
    
    $params = [];
    $types = "";

    if ($status === 'chua_thanh_toan') {
        $sql .= " AND cn.TrangThaiThanhToan = 'Chưa thanh toán'";
    } elseif ($status === 'qua_han') {
        $sql .= " AND cn.TrangThaiThanhToan = 'Quá hạn'";
    } elseif ($status === 'da_thanh_toan') {
        $sql .= " AND cn.TrangThaiThanhToan = 'Đã thanh toán'";
    }

    if ($search) {
        $sql .= " AND (dh.TenCongTy LIKE ? OR dh.SoYCSX LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    $sql .= " ORDER BY cn.ThoiHanThanhToan ASC";

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
                    SUM(GiaTriConLai) as TongCongNo,
                    SUM(CASE WHEN TrangThaiThanhToan = 'Quá hạn' THEN GiaTriConLai ELSE 0 END) as TongQuaHan,
                    SUM(SoTienTamUng) as TongDaThu
                FROM quanly_congno";
    
    $stmtSummary = $conn->prepare($summarySql);
    $stmtSummary->execute();
    $summaryResult = $stmtSummary->get_result();
    $summary = $summaryResult->fetch_assoc();

    $response['success'] = true;
    $response['data'] = $data;
    $response['summary'] = [
        'tongCongNo' => floatval($summary['TongCongNo'] ?? 0),
        'tongQuaHan' => floatval($summary['TongQuaHan'] ?? 0),
        'tongDaThu' => floatval($summary['TongDaThu'] ?? 0)
    ];

    $stmt->close();
    $stmtSummary->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
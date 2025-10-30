<?php
/* ========================================
   FILE: api/get_phieu_chi.php
   ======================================== */
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        $response['message'] = 'Chưa đăng nhập';
        echo json_encode($response);
        exit;
    }

    $dateFrom = $_GET['dateFrom'] ?? null;
    $dateTo = $_GET['dateTo'] ?? null;
    $loaiCP = $_GET['loaiCP'] ?? null;
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;

    $sql = "SELECT 
                pc.*,
                nd.HoTen as NguoiLapPhieu
            FROM phieu_chi pc
            LEFT JOIN nguoidung nd ON pc.NguoiLap = nd.UserID
            WHERE 1=1";
    
    $params = [];
    $types = "";

    if ($dateFrom) {
        $sql .= " AND pc.NgayChi >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }

    if ($dateTo) {
        $sql .= " AND pc.NgayChi <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }

    if ($loaiCP) {
        $sql .= " AND pc.LoaiChiPhi = ?";
        $params[] = $loaiCP;
        $types .= "s";
    }

    if ($status) {
        $sql .= " AND pc.TrangThai = ?";
        $params[] = $status;
        $types .= "s";
    }

    if ($search) {
        $sql .= " AND (pc.SoPhieuChi LIKE ? OR pc.TenDoiTuong LIKE ? OR pc.LyDoChi LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }

    $sql .= " ORDER BY pc.NgayChi DESC, pc.PhieuChiID DESC";

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

    $response['success'] = true;
    $response['data'] = $data;

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>


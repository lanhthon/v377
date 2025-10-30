<?php
/* ========================================
   FILE: api/get_bao_cao_chi_phi.php
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

    $dateFrom = $_GET['dateFrom'] ?? null;
    $dateTo = $_GET['dateTo'] ?? null;
    $loaiChiPhi = $_GET['loaiChiPhi'] ?? null;
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;

    // Query chi tiết phiếu chi
    $sql = "SELECT 
                pc.PhieuChiID,
                pc.SoPhieuChi,
                pc.NgayChi,
                pc.TenDoiTuong,
                pc.LoaiDoiTuong,
                pc.DiaChiDoiTuong,
                pc.LyDoChi,
                pc.LoaiChiPhi as TenLoaiCP,
                pc.SoTien,
                pc.HinhThucThanhToan,
                pc.TrangThai,
                pc.NguoiNhan,
                pc.DienThoaiNguoiNhan,
                pc.SoTaiKhoan,
                pc.NganHang,
                pc.GhiChu,
                pc.NgayDuyet,
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

    if ($loaiChiPhi) {
        $sql .= " AND pc.LoaiChiPhi = ?";
        $params[] = $loaiChiPhi;
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

    $stmt->close();

    // Query tổng hợp theo loại chi phí
    $sqlSummary = "SELECT 
                        COALESCE(pc.LoaiChiPhi, 'Chưa phân loại') as TenLoaiCP,
                        COUNT(pc.PhieuChiID) as SoLuong,
                        SUM(pc.SoTien) as TongTien
                    FROM phieu_chi pc
                    WHERE 1=1";
    
    $paramsSummary = [];
    $typesSummary = "";

    if ($dateFrom) {
        $sqlSummary .= " AND pc.NgayChi >= ?";
        $paramsSummary[] = $dateFrom;
        $typesSummary .= "s";
    }

    if ($dateTo) {
        $sqlSummary .= " AND pc.NgayChi <= ?";
        $paramsSummary[] = $dateTo;
        $typesSummary .= "s";
    }

    if ($loaiChiPhi) {
        $sqlSummary .= " AND pc.LoaiChiPhi = ?";
        $paramsSummary[] = $loaiChiPhi;
        $typesSummary .= "s";
    }

    if ($status) {
        $sqlSummary .= " AND pc.TrangThai = ?";
        $paramsSummary[] = $status;
        $typesSummary .= "s";
    }

    if ($search) {
        $sqlSummary .= " AND (pc.SoPhieuChi LIKE ? OR pc.TenDoiTuong LIKE ? OR pc.LyDoChi LIKE ?)";
        $searchParam = "%{$search}%";
        $paramsSummary[] = $searchParam;
        $paramsSummary[] = $searchParam;
        $paramsSummary[] = $searchParam;
        $typesSummary .= "sss";
    }

    $sqlSummary .= " GROUP BY pc.LoaiChiPhi ORDER BY TongTien DESC";

    $stmtSummary = $conn->prepare($sqlSummary);
    
    if (!empty($paramsSummary)) {
        $stmtSummary->bind_param($typesSummary, ...$paramsSummary);
    }
    
    $stmtSummary->execute();
    $resultSummary = $stmtSummary->get_result();
    
    $summary = [];
    $totalAmount = 0;
    while ($row = $resultSummary->fetch_assoc()) {
        $totalAmount += floatval($row['TongTien']);
        $summary[] = $row;
    }

    // Tính tỷ lệ phần trăm
    foreach ($summary as &$item) {
        $item['TyLe'] = $totalAmount > 0 
            ? number_format(($item['TongTien'] / $totalAmount) * 100, 1) . '%'
            : '0%';
    }

    $stmtSummary->close();

    $response['success'] = true;
    $response['data'] = $data;
    $response['summary'] = $summary;
    $response['total'] = count($data);
    $response['totalAmount'] = $totalAmount;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
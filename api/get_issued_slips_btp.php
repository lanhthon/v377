<?php
// File: api/get_issued_slips_btp.php
require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'data' => [], 'pagination' => null, 'message' => ''];

try {
    $pdo = get_db_connection();

    // Lấy tham số bộ lọc và phân trang
    $soPhieu = $_GET['soPhieu'] ?? '';
    $soYCSX = $_GET['soYCSX'] ?? '';
    $startDate = $_GET['startDate'] ?? '';
    $endDate = $_GET['endDate'] ?? '';
    $ghiChu = $_GET['ghiChu'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
    $offset = ($page - 1) * $limit;

    // Xây dựng điều kiện WHERE động
    $whereClauses = ["pxk.LoaiPhieu IN ('xuat_btp_cat', 'xuat_btp_khac')"];
    $params = [];

    if (!empty($soPhieu)) {
        $whereClauses[] = "pxk.SoPhieuXuat LIKE :soPhieu";
        $params[':soPhieu'] = '%' . $soPhieu . '%';
    }
    if (!empty($soYCSX)) {
        $whereClauses[] = "dh.SoYCSX LIKE :soYCSX";
        $params[':soYCSX'] = '%' . $soYCSX . '%';
    }
    if (!empty($startDate)) {
        $whereClauses[] = "pxk.NgayXuat >= :startDate";
        $params[':startDate'] = $startDate;
    }
    if (!empty($endDate)) {
        $whereClauses[] = "pxk.NgayXuat <= :endDate";
        $params[':endDate'] = $endDate;
    }
    if (!empty($ghiChu)) {
        $whereClauses[] = "pxk.GhiChu LIKE :ghiChu";
        $params[':ghiChu'] = '%' . $ghiChu . '%';
    }

    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

    // Truy vấn đếm tổng số bản ghi
    $countSql = "SELECT COUNT(pxk.PhieuXuatKhoID) 
                 FROM phieuxuatkho pxk 
                 LEFT JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID 
                 $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Truy vấn lấy dữ liệu cho trang hiện tại
    $sql = "SELECT 
                pxk.PhieuXuatKhoID,
                pxk.SoPhieuXuat,
                pxk.NgayXuat,
                pxk.GhiChu,
                dh.SoYCSX,
                nd.HoTen AS NguoiTao
            FROM phieuxuatkho pxk
            LEFT JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID 
            LEFT JOIN nguoidung nd ON pxk.NguoiTaoID = nd.UserID
            $whereSql
            ORDER BY pxk.PhieuXuatKhoID DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    // Bind các tham số của WHERE và thêm các tham số của LIMIT/OFFSET
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['pagination'] = ['currentPage' => $page, 'totalPages' => $totalPages];
    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
    error_log("Lỗi trong get_issued_slips_btp.php: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

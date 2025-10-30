<?php
// api/get_list_pnk_btp.php
require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

// Cấu hình phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15; // 15 dòng mỗi trang
$offset = ($page - 1) * $limit;

// Lấy tham số bộ lọc
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';
$soPhieu = $_GET['soPhieu'] ?? '';
$soLSX = $_GET['soLSX'] ?? '';
$ghiChu = $_GET['ghiChu'] ?? '';


$response = [
    'success' => false,
    'data' => [],
    'pagination' => [
        'currentPage' => $page,
        'totalPages' => 0,
        'totalRecords' => 0
    ],
    'message' => ''
];

try {
    $pdo = get_db_connection();
    
    $whereClauses = [];
    $params = [];

    // Xây dựng điều kiện WHERE
    if (!empty($startDate)) {
        $whereClauses[] = "pnk.NgayNhap >= :startDate";
        $params[':startDate'] = $startDate;
    }
    if (!empty($endDate)) {
        $whereClauses[] = "pnk.NgayNhap <= :endDate";
        $params[':endDate'] = $endDate;
    }
    if (!empty($soPhieu)) {
        $whereClauses[] = "pnk.SoPhieuNhapKhoBTP LIKE :soPhieu";
        $params[':soPhieu'] = '%' . $soPhieu . '%';
    }
    if (!empty($soLSX)) {
        $whereClauses[] = "lsx.SoLenhSX LIKE :soLSX";
        $params[':soLSX'] = '%' . $soLSX . '%';
    }
    if (!empty($ghiChu)) {
        $whereClauses[] = "pnk.GhiChu LIKE :ghiChu";
        $params[':ghiChu'] = '%' . $ghiChu . '%';
    }

    $whereSql = '';
    if (!empty($whereClauses)) {
        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
    }
    
    // === 1. Đếm tổng số bản ghi ===
    $countSql = "SELECT COUNT(pnk.PNK_BTP_ID) 
                 FROM phieunhapkho_btp pnk
                 LEFT JOIN lenh_san_xuat lsx ON pnk.LenhSX_ID = lsx.LenhSX_ID
                 LEFT JOIN nguoidung nd ON pnk.NguoiTaoID = nd.UserID
                 $whereSql";
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    $response['pagination']['totalRecords'] = $totalRecords;
    $response['pagination']['totalPages'] = $totalPages;
    
    // === 2. Lấy dữ liệu cho trang hiện tại ===
    $dataSql = "SELECT 
                    pnk.PNK_BTP_ID,
                    pnk.SoPhieuNhapKhoBTP,
                    pnk.NgayNhap,
                    pnk.LyDoNhap,
                    pnk.GhiChu, -- Thêm GhiChu
                    lsx.SoLenhSX,
                    nd.HoTen AS TenNguoiTao
                FROM phieunhapkho_btp pnk
                LEFT JOIN lenh_san_xuat lsx ON pnk.LenhSX_ID = lsx.LenhSX_ID
                LEFT JOIN nguoidung nd ON pnk.NguoiTaoID = nd.UserID
                $whereSql
                ORDER BY pnk.PNK_BTP_ID DESC
                LIMIT :limit OFFSET :offset";
                
    $dataStmt = $pdo->prepare($dataSql);
    
    // Gắn các tham số của WHERE
    foreach ($params as $key => &$val) {
        $dataStmt->bindParam($key, $val);
    }
    
    // Gắn các tham số của LIMIT và OFFSET
    $dataStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $dataStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    $dataStmt->execute();
    
    $response['data'] = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;

} catch(Exception $e) {
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
    error_log("Lỗi trong get_list_pnk_btp.php: " . $e->getMessage());
    http_response_code(500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

<?php
// File: api/get_list_pnk_tp_filtered.php
// API lấy danh sách phiếu nhập kho thành phẩm với bộ lọc và phân trang

require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'data' => [], 'message' => '', 'pagination' => []];

try {
    $pdo = get_db_connection();
    
    // Get filter parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    $so_phieu = isset($_GET['so_phieu']) ? trim($_GET['so_phieu']) : '';
    $so_ycsx = isset($_GET['so_ycsx']) ? trim($_GET['so_ycsx']) : '';
    $tu_ngay = isset($_GET['tu_ngay']) ? $_GET['tu_ngay'] : '';
    $den_ngay = isset($_GET['den_ngay']) ? $_GET['den_ngay'] : '';
    $ly_do = isset($_GET['ly_do']) ? trim($_GET['ly_do']) : '';
    $nguoi_tao = isset($_GET['nguoi_tao']) ? trim($_GET['nguoi_tao']) : '';
    
    // Build WHERE conditions
    $where = " WHERE pnk.LoaiPhieu IN ('nhap_tp_tu_sx', 'nhap_tp_khac')";
    $params = [];
    
    if (!empty($so_phieu)) {
        $where .= " AND pnk.SoPhieuNhapKho LIKE :so_phieu";
        $params[':so_phieu'] = '%' . $so_phieu . '%';
    }
    
    if (!empty($so_ycsx)) {
        $where .= " AND dh.SoYCSX LIKE :so_ycsx";
        $params[':so_ycsx'] = '%' . $so_ycsx . '%';
    }
    
    if (!empty($tu_ngay)) {
        $where .= " AND pnk.NgayNhap >= :tu_ngay";
        $params[':tu_ngay'] = $tu_ngay;
    }
    
    if (!empty($den_ngay)) {
        $where .= " AND pnk.NgayNhap <= :den_ngay";
        $params[':den_ngay'] = $den_ngay;
    }
    
    if (!empty($ly_do)) {
        $where .= " AND pnk.LyDoNhap = :ly_do";
        $params[':ly_do'] = $ly_do;
    }
    
    if (!empty($nguoi_tao)) {
        $where .= " AND nd.HoTen LIKE :nguoi_tao";
        $params[':nguoi_tao'] = '%' . $nguoi_tao . '%';
    }
    
    // Count total records
    $count_sql = "SELECT COUNT(*) as total
                  FROM phieunhapkho pnk
                  LEFT JOIN donhang dh ON pnk.YCSX_ID = dh.YCSX_ID
                  LEFT JOIN nguoidung nd ON pnk.NguoiTaoID = nd.UserID
                  $where";
    
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    
    // Get paginated data
    $sql = "SELECT 
                pnk.PhieuNhapKhoID,
                pnk.SoPhieuNhapKho,
                pnk.NgayNhap,
                pnk.LyDoNhap,
                dh.SoYCSX,
                nd.HoTen AS TenNguoiTao,
                pnk.TongTien,
                pnk.GhiChu
            FROM phieunhapkho pnk
            LEFT JOIN donhang dh ON pnk.YCSX_ID = dh.YCSX_ID
            LEFT JOIN nguoidung nd ON pnk.NguoiTaoID = nd.UserID
            $where
            ORDER BY pnk.PhieuNhapKhoID DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $total_pages = ceil($total_records / $limit);
    $start = $offset + 1;
    $end = min($offset + $limit, $total_records);
    
    $response['success'] = true;
    $response['data'] = $data;
    $response['pagination'] = [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_records' => $total_records,
        'limit' => $limit,
        'start' => $start,
        'end' => $end
    ];
    
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
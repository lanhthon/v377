<?php
// File: api/get_congno_data.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    global $conn;

    $items_per_page = 15;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $items_per_page;

    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $startDate = $_GET['startDate'] ?? '';
    $endDate = $_GET['endDate'] ?? '';
    $filter_type = $_GET['filter_type'] ?? 'all';

    $base_query = "
        FROM donhang dh
        LEFT JOIN quanly_congno qlcn ON dh.YCSX_ID = qlcn.YCSX_ID
        LEFT JOIN congty ct ON dh.CongTyID = ct.CongTyID
        LEFT JOIN phieuxuatkho pxk ON dh.YCSX_ID = pxk.YCSX_ID AND pxk.LoaiPhieu = 'xuat_thanh_pham'
        LEFT JOIN chuanbihang cbh ON pxk.CBH_ID = cbh.CBH_ID
    ";
    
    $conditions = ["cbh.TrangThai = 'Đã giao hàng'"];
    $params = [];
    $types = '';

    if (!empty($search)) {
        $conditions[] = "(dh.SoYCSX LIKE ? OR ct.MaCongTy LIKE ? OR dh.TenDuAn LIKE ? OR ct.TenCongTy LIKE ?)";
        $search_param = "%{$search}%";
        array_push($params, $search_param, $search_param, $search_param, $search_param);
        $types .= 'ssss';
    }

    if (!empty($status)) {
        $conditions[] = "IFNULL(qlcn.TrangThaiThanhToan, 'Chưa thanh toán') = ?";
        $params[] = $status;
        $types .= 's';
    }

    if (!empty($startDate)) $conditions[] = "pxk.NgayXuat >= ?";
    if (!empty($endDate)) $conditions[] = "pxk.NgayXuat <= ?";
    if (!empty($startDate)) { $params[] = $startDate; $types .= 's'; }
    if (!empty($endDate)) { $params[] = $endDate; $types .= 's'; }

    if ($filter_type === 'overdue') {
        $conditions[] = "IFNULL(qlcn.ThoiHanThanhToan, DATE_ADD(pxk.NgayXuat, INTERVAL IFNULL(ct.SoNgayThanhToan, 30) DAY)) < CURDATE() AND IFNULL(qlcn.TrangThaiThanhToan, 'Chưa thanh toán') != 'Đã thanh toán'";
    }

    $where_clause = " WHERE " . implode(" AND ", $conditions);

    // Summary calculation (always for all data, ignoring overdue filter for summary)
    $summary_conditions = $conditions;
    if(($key = array_search("IFNULL(qlcn.ThoiHanThanhToan, DATE_ADD(pxk.NgayXuat, INTERVAL IFNULL(ct.SoNgayThanhToan, 30) DAY)) < CURDATE() AND IFNULL(qlcn.TrangThaiThanhToan, 'Chưa thanh toán') != 'Đã thanh toán'", $summary_conditions)) !== false) {
        unset($summary_conditions[$key]);
    }
    $summary_where_clause = " WHERE " . implode(" AND ", $summary_conditions);

    $summary_query = "
        SELECT 
            SUM(IFNULL(qlcn.GiaTriConLai, dh.TongTien - IFNULL(qlcn.SoTienTamUng, 0))) as totalDebt,
            SUM(IF(IFNULL(qlcn.ThoiHanThanhToan, DATE_ADD(pxk.NgayXuat, INTERVAL IFNULL(ct.SoNgayThanhToan, 30) DAY)) < CURDATE() AND IFNULL(qlcn.TrangThaiThanhToan, 'Chưa thanh toán') != 'Đã thanh toán', IFNULL(qlcn.GiaTriConLai, dh.TongTien - IFNULL(qlcn.SoTienTamUng, 0)), 0)) as overdueDebt,
            COUNT(DISTINCT IF(IFNULL(qlcn.ThoiHanThanhToan, DATE_ADD(pxk.NgayXuat, INTERVAL IFNULL(ct.SoNgayThanhToan, 30) DAY)) < CURDATE() AND IFNULL(qlcn.TrangThaiThanhToan, 'Chưa thanh toán') != 'Đã thanh toán', dh.YCSX_ID, NULL)) as overdueCount
        " . $base_query . $summary_where_clause;
    
    $stmt_summary = $conn->prepare($summary_query);
    // Bind params for summary without overdue filter
    $summary_params = $params;
    $summary_types = $types;
    if ($filter_type === 'overdue') {
        // remove the last N params according to overdue condition (none in this case)
    }
    if (!empty($summary_types)) $stmt_summary->bind_param($summary_types, ...$summary_params);
    $stmt_summary->execute();
    $summary_data = $stmt_summary->get_result()->fetch_assoc();
    $stmt_summary->close();


    // Count total records for pagination (respecting filters)
    $count_query = "SELECT COUNT(DISTINCT dh.YCSX_ID) as total " . $base_query . $where_clause;
    $stmt_count = $conn->prepare($count_query);
    if ($types) $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
    $total_pages = ceil($total_records / $items_per_page);
    $stmt_count->close();

    // Fetch data for the current page
    $data_query = "
        SELECT
            dh.YCSX_ID, dh.SoYCSX, ct.MaCongTy AS MaKhachHang, ct.TenCongTy,
            qlcn.DonViTra, dh.TenDuAn, pxk.NgayXuat AS NgayGiaoHang,
            IFNULL(qlcn.ThoiHanThanhToan, DATE_ADD(pxk.NgayXuat, INTERVAL IFNULL(ct.SoNgayThanhToan, 30) DAY)) AS ThoiHanThanhToan,
            qlcn.NgayXuatHoaDon, dh.TongTien AS TongGiaTri,
            IFNULL(qlcn.SoTienTamUng, 0) AS SoTienTamUng,
            IFNULL(qlcn.GiaTriConLai, dh.TongTien - IFNULL(qlcn.SoTienTamUng, 0)) AS GiaTriConLai,
            IFNULL(qlcn.TrangThaiThanhToan, 'Chưa thanh toán') AS TrangThaiThanhToan,
            pxk.BBGH_ID, pxk.CCCL_ID
        " . $base_query . $where_clause . "
        GROUP BY dh.YCSX_ID ORDER BY pxk.NgayXuat DESC, dh.YCSX_ID DESC LIMIT ? OFFSET ?";
    
    $stmt_data = $conn->prepare($data_query);
    $data_params = $params;
    $data_types = $types . 'ii';
    array_push($data_params, $items_per_page, $offset);
    $stmt_data->bind_param($data_types, ...$data_params);
    $stmt_data->execute();
    $data = $stmt_data->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_data->close();

    echo json_encode([
        'success' => true, 'data' => $data,
        'summary' => $summary_data,
        'pagination' => ['current_page' => $page, 'total_pages' => (int)$total_pages, 'total_records' => (int)$total_records, 'items_per_page' => $items_per_page]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
$conn->close();
?>


<?php
/**
 * api/get_production_order_list.php
 * Version: 4.0 - Tái cấu trúc xuất Excel sang client-side
 */
ob_start();

require_once '../config/database.php';
global $conn;

// --- Lấy các tham số lọc từ request ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? null;
$status = $_GET['status'] ?? null;
$type = $_GET['type'] ?? null;
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$filter_type = $_GET['filter_type'] ?? 'all';

// --- Xây dựng câu truy vấn SQL động ---
$params = [];
$types = '';
$baseSql = "FROM lenh_san_xuat lsx
            LEFT JOIN donhang dh ON lsx.YCSX_ID = dh.YCSX_ID
            LEFT JOIN nguoidung u ON lsx.NguoiYeuCau_ID = u.UserID
            WHERE 1=1";

if ($filter_type === 'overdue') {
    $baseSql .= " AND lsx.NgayHoanThanhUocTinh < CURDATE() AND lsx.TrangThai NOT IN ('Hoàn thành', 'Đã hủy')";
}

if ($search) {
    $baseSql .= " AND (lsx.SoLenhSX LIKE ? OR dh.SoYCSX LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}
if ($status) {
    $baseSql .= " AND lsx.TrangThai = ?";
    $params[] = $status;
    $types .= 's';
}
if ($type) {
    $baseSql .= " AND lsx.LoaiLSX = ?";
    $params[] = $type;
    $types .= 's';
}
if ($startDate) {
    $baseSql .= " AND lsx.NgayTao >= ?";
    $params[] = $startDate . " 00:00:00";
    $types .= 's';
}
if ($endDate) {
    $baseSql .= " AND lsx.NgayTao <= ?";
    $params[] = $endDate . " 23:59:59";
    $types .= 's';
}

// --- Xử lý chức năng xuất Excel (trả về JSON cho client-side) ---
if (isset($_GET['export'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $exportSql = "SELECT lsx.SoLenhSX, lsx.LoaiLSX, dh.SoYCSX, lsx.NgayTao, 
                         lsx.NgayHoanThanhUocTinh, lsx.NgayHoanThanhThucTe,
                         COALESCE(u.HoTen, 'Hệ thống') as NguoiYeuCau, lsx.TrangThai " 
                     . $baseSql . " ORDER BY lsx.NgayTao DESC";
        
        $stmt_export = $conn->prepare($exportSql);
        if (!empty($types)) {
            $stmt_export->bind_param($types, ...$params);
        }
        $stmt_export->execute();
        $data = $stmt_export->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_export->close();
        $conn->close();

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit; // Dừng thực thi sau khi xuất JSON
}


// --- Xử lý hiển thị JSON cho danh sách (có phân trang) ---
header('Content-Type: application/json; charset=utf-8');

try {
    // Đếm tổng số bản ghi với bộ lọc
    $countSql = "SELECT COUNT(lsx.LenhSX_ID) " . $baseSql;
    $stmt_count = $conn->prepare($countSql);
    if (!empty($types)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $totalRecords = $stmt_count->get_result()->fetch_row()[0];
    $totalPages = ceil($totalRecords / $limit);
    $stmt_count->close();
    
    // Đếm số lượng quá hạn
    $overdueSql = "SELECT COUNT(lsx.LenhSX_ID) FROM lenh_san_xuat lsx WHERE lsx.NgayHoanThanhUocTinh < CURDATE() AND lsx.TrangThai NOT IN ('Hoàn thành', 'Đã hủy')";
    $overdueCount = $conn->query($overdueSql)->fetch_row()[0];

    // Lấy danh sách trạng thái
    $statusSql = "SELECT DISTINCT TrangThai FROM lenh_san_xuat WHERE TrangThai IS NOT NULL AND TrangThai != '' ORDER BY TrangThai";
    $statuses = $conn->query($statusSql)->fetch_all(MYSQLI_ASSOC);
    $statusList = array_column($statuses, 'TrangThai');
    
    // Lấy dữ liệu cho trang hiện tại
    $dataSql = "SELECT lsx.LenhSX_ID, lsx.SoLenhSX, lsx.LoaiLSX, lsx.NgayTao, lsx.TrangThai,
                       lsx.NgayHoanThanhUocTinh, lsx.NgayHoanThanhThucTe,
                       dh.SoYCSX, COALESCE(u.HoTen, 'Hệ thống') as NguoiYeuCau "
             . $baseSql . " ORDER BY lsx.NgayTao DESC LIMIT ? OFFSET ?";
    
    $dataParams = $params;
    $dataTypes = $types;
    $dataParams[] = $limit;
    $dataParams[] = $offset;
    $dataTypes .= 'ii';
    
    $stmt_data = $conn->prepare($dataSql);
    if (!empty($dataTypes)) {
        $stmt_data->bind_param($dataTypes, ...$dataParams);
    }
    $stmt_data->execute();
    $data = $stmt_data->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_data->close();
    
    $conn->close();

    echo json_encode([
        'success' => true, 
        'data' => $data,
        'pagination' => ['page' => $page, 'limit' => $limit, 'totalRecords' => (int)$totalRecords, 'totalPages' => $totalPages],
        'statuses' => $statusList,
        'overdueCount' => (int)$overdueCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>


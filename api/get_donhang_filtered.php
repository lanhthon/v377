<?php
// File: api/get_donhang_filtered.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
global $conn;

// --- Lấy các tham số lọc từ request ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? null;
$status = $_GET['status'] ?? null;
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$filter_type = $_GET['filter_type'] ?? 'all';

// --- Xây dựng câu truy vấn SQL động ---
$params = [];
$types = '';
$baseSql = "FROM donhang AS dh
            LEFT JOIN congty AS ct ON dh.CongTyID = ct.CongTyID
            WHERE 1=1";

if ($filter_type === 'overdue') {
    $baseSql .= " AND dh.NgayGiaoDuKien < CURDATE() AND dh.TrangThai NOT IN ('Đã giao hàng', 'Đã hủy', 'Hoàn thành')";
}

if ($search) {
    $baseSql .= " AND (dh.SoYCSX LIKE ? OR ct.TenCongTy LIKE ? OR ct.MaCongTy LIKE ? OR dh.TenDuAn LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    $types .= 'ssss';
}
if ($status) {
    $baseSql .= " AND dh.TrangThai = ?";
    $params[] = $status;
    $types .= 's';
}
if ($startDate) {
    $baseSql .= " AND dh.NgayTao >= ?";
    $params[] = $startDate . " 00:00:00";
    $types .= 's';
}
if ($endDate) {
    $baseSql .= " AND dh.NgayTao <= ?";
    $params[] = $endDate . " 23:59:59";
    $types .= 's';
}

// --- Xử lý chức năng xuất Excel (trả về JSON cho client-side) ---
if (isset($_GET['export'])) {
    try {
        $exportSql = "SELECT dh.SoYCSX, ct.MaCongTy, dh.TenDuAn, dh.NguoiBaoGia, dh.NgayTao, dh.NgayGiaoDuKien, dh.TongTien, dh.TrangThai "
                     . $baseSql . " ORDER BY dh.NgayTao DESC";
        
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
    exit;
}

// --- Xử lý hiển thị JSON cho danh sách (có phân trang) ---
try {
    // Đếm tổng số bản ghi
    $countSql = "SELECT COUNT(dh.YCSX_ID) " . $baseSql;
    $stmt_count = $conn->prepare($countSql);
    if (!empty($types)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $totalRecords = $stmt_count->get_result()->fetch_row()[0];
    $totalPages = ceil($totalRecords / $limit);
    $stmt_count->close();
    
    $overdueSql = "SELECT COUNT(YCSX_ID) FROM donhang WHERE NgayGiaoDuKien < CURDATE() AND TrangThai NOT IN ('Đã giao hàng', 'Đã hủy', 'Hoàn thành')";
    $overdueCount = $conn->query($overdueSql)->fetch_row()[0];

    // Lấy danh sách trạng thái
    $statusSql = "SELECT DISTINCT TrangThai FROM donhang WHERE TrangThai IS NOT NULL AND TrangThai != '' ORDER BY TrangThai";
    $statuses = $conn->query($statusSql)->fetch_all(MYSQLI_ASSOC);
    $statusList = array_column($statuses, 'TrangThai');
    
    // SỬA LỖI: Lấy địa chỉ mặc định của công ty (ct.DiaChi) theo yêu cầu
    $dataSql = "SELECT 
                    dh.YCSX_ID, dh.SoYCSX, dh.NguoiBaoGia, dh.NgayTao, dh.NgayGiaoDuKien, dh.TongTien, dh.TrangThai, dh.TenDuAn,
                    ct.MaCongTy, ct.TenCongTy, ct.MaSoThue, ct.DiaChi AS DiaChiHienThi, ct.SoDienThoaiChinh
                "
             . $baseSql . " ORDER BY dh.NgayTao DESC, dh.YCSX_ID DESC LIMIT ? OFFSET ?";
    
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
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>
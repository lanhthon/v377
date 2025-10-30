<?php
header('Content-Type: application/json');

require_once '../config/database.php';

// ... (Phần lấy tham số không đổi) ...
$status = isset($_GET['status']) ? $_GET['status'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
$filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'all';
$offset = ($page - 1) * $limit;

// ... (Phần đếm overdue và lấy status không đổi) ...
$overdueSql = "SELECT COUNT(CBH_ID) as count FROM chuanbihang WHERE NgayGiao < CURDATE() AND TrangThai NOT IN ('Đã giao', 'Đã giao hàng', 'Đã hủy', 'Chờ xử lý', 'Kiểm tra tiến độ')";
$overdueResult = $conn->query($overdueSql);
$overdueCount = $overdueResult ? $overdueResult->fetch_assoc()['count'] : 0;
$statusesSql = "SELECT DISTINCT TrangThai FROM chuanbihang WHERE TrangThai IS NOT NULL AND TrangThai != '' AND TrangThai NOT IN ('Chờ xử lý', 'Kiểm tra tiến độ') ORDER BY TrangThai";
$statusesResult = $conn->query($statusesSql);
$statuses = [];
while ($row = $statusesResult->fetch_assoc()) {
    $statuses[] = $row['TrangThai'];
}


// Xây dựng truy vấn chính
$baseSql = "FROM
            chuanbihang cbh
        LEFT JOIN
            donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
        LEFT JOIN
            congty c ON dh.CongTyID = c.CongTyID";

$whereClauses = [];
$params = [];
$types = "";

$whereClauses[] = "cbh.TrangThai NOT IN ('Chờ xử lý', 'Kiểm tra tiến độ')";

if ($filterType === 'overdue') {
    $whereClauses[] = "cbh.NgayGiao < CURDATE()";
    $whereClauses[] = "cbh.TrangThai NOT IN ('Đã giao', 'Đã giao hàng', 'Đã hủy')";
}

if (!empty($status)) {
    $whereClauses[] = "cbh.TrangThai = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($startDate)) {
    $whereClauses[] = "cbh.NgayTao >= ?";
    $params[] = $startDate;
    $types .= "s";
}
if (!empty($endDate)) {
    $whereClauses[] = "cbh.NgayTao <= ?";
    $params[] = $endDate;
    $types .= "s";
}

if (!empty($searchTerm)) {
    $searchTermWildcard = '%' . $searchTerm . '%';
    // THAY ĐỔI: Thêm tìm kiếm theo Tên Dự Án
    $whereClauses[] = "(dh.SoYCSX LIKE ? OR c.MaCongTy LIKE ? OR c.TenCongTy LIKE ? OR dh.TenDuAn LIKE ?)";
    $params[] = $searchTermWildcard;
    $params[] = $searchTermWildcard;
    $params[] = $searchTermWildcard;
    $params[] = $searchTermWildcard; // Thêm param cho TenDuAn
    $types .= "ssss"; // Thêm 's'
}

$whereSql = "";
if (count($whereClauses) > 0) {
    $whereSql = " WHERE " . implode(" AND ", $whereClauses);
}

// Truy vấn đếm tổng số mục
$countSql = "SELECT COUNT(DISTINCT cbh.CBH_ID) as total " . $baseSql . $whereSql;
$stmtCount = $conn->prepare($countSql);
if ($stmtCount && !empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalItems = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $limit);
$stmtCount->close();


// Truy vấn lấy dữ liệu cho trang hiện tại
// THAY ĐỔI: Thêm dh.TenDuAn vào danh sách các trường cần lấy
$dataSql = "SELECT
            cbh.CBH_ID,
            cbh.SoCBH,
            cbh.NgayTao,
            cbh.NgayGiao,
            cbh.TrangThai,
            dh.SoYCSX,
            dh.TenDuAn,
            c.MaCongTy
        " . $baseSql . $whereSql . " ORDER BY cbh.NgayTao DESC, cbh.CBH_ID DESC LIMIT ? OFFSET ?";

$dataParams = $params;
$dataTypes = $types . "ii";
$dataParams[] = $limit;
$dataParams[] = $offset;

$stmtData = $conn->prepare($dataSql);
if ($stmtData && !empty($dataParams)) {
    $stmtData->bind_param($dataTypes, ...$dataParams);
}
$stmtData->execute();
$result = $stmtData->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);
$stmtData->close();

// Trả về kết quả
echo json_encode([
    'success' => true,
    'data' => $data,
    'pagination' => [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems
    ],
    'statuses' => $statuses,
    'overdueCount' => $overdueCount
]);

$conn->close();
?>
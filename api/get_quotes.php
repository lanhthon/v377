<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Tham số phân trang
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

// Tham số lọc
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';
$searchTerm = $_GET['searchTerm'] ?? ''; // THAY ĐỔI 1: Đổi 'companyName' thành 'searchTerm'
$status = $_GET['status'] ?? '';
$creatorId = $_GET['creatorId'] ?? ''; 

$conn->set_charset("utf8mb4");

// === Xây dựng điều kiện WHERE và tham số ===
// THAY ĐỔI 2: Thêm LEFT JOIN vào cả 2 câu lệnh SQL để có thể tìm kiếm theo Mã Công Ty
$baseQuery = "FROM baogia bg LEFT JOIN nguoidung nd ON bg.UserID = nd.UserID LEFT JOIN congty ct ON bg.CongTyID = ct.CongTyID WHERE 1=1";
$whereClause = "";
$params = [];
$types = "";

if (!empty($startDate)) {
    $whereClause .= " AND bg.NgayBaoGia >= ?";
    $params[] = $startDate;
    $types .= "s";
}
if (!empty($endDate)) {
    $whereClause .= " AND bg.NgayBaoGia <= ?";
    $params[] = $endDate;
    $types .= "s";
}
// THAY ĐỔI 3: Cập nhật logic tìm kiếm để hoạt động trên nhiều cột
if (!empty($searchTerm)) {
    $whereClause .= " AND (bg.SoBaoGia LIKE ? OR bg.TenCongTy LIKE ? OR ct.MaCongTy LIKE ?)";
    $likeTerm = "%" . $searchTerm . "%";
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $types .= "sss";
}
if (!empty($status)) {
    $whereClause .= " AND bg.TrangThai = ?";
    $params[] = $status;
    $types .= "s";
}
if (!empty($creatorId)) {
    $whereClause .= " AND bg.UserID = ?";
    $params[] = $creatorId;
    $types .= "i"; 
}

// === ĐẾM TỔNG SỐ BẢN GHI ===
$totalRecords = 0;
$totalPages = 0;
$countSql = "SELECT COUNT(bg.BaoGiaID) as total " . $baseQuery . $whereClause;
$stmt = $conn->prepare($countSql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $totalRecords = (int)$result->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);
    $stmt->close();
}

// === LẤY DỮ LIỆU CHO TRANG HIỆN TẠI ===
$dataSql = "SELECT 
                bg.BaoGiaID, 
                bg.SoBaoGia, 
                bg.TenCongTy,
                bg.TenDuAn,
                COALESCE(nd.HoTen, 'Chưa xác định') AS NguoiTao, 
                bg.NgayBaoGia, 
                bg.TongTienSauThue, 
                bg.TrangThai,
                ct.MaCongTy,
                ct.MaSoThue,
                ct.DiaChi AS DiaChiCongTy,
                ct.SoDienThoaiChinh
            " . $baseQuery . $whereClause . " 
            ORDER BY 
                bg.NgayBaoGia DESC, bg.BaoGiaID DESC 
            LIMIT ?, ?";

$dataParams = $params;
$dataParams[] = $offset;
$dataParams[] = $limit;
$dataTypes = $types . "ii";

$quotes = [];
$stmt = $conn->prepare($dataSql);
if ($stmt) {
    if (!empty($dataParams)) {
        $stmt->bind_param($dataTypes, ...$dataParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $quotes[] = $row;
        }
    }
    $stmt->close();
}

// === TRẢ VỀ KẾT QUẢ ===
echo json_encode([
    'success' => true,
    'quotes' => $quotes,
    'pagination' => [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalRecords' => $totalRecords,
        'itemsPerPage' => $limit
    ]
]);

$conn->close();
?>
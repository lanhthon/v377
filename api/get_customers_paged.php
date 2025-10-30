<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$size = isset($_GET['size']) ? (int)$_GET['size'] : 50;
$offset = ($page - 1) * $size;

$response = [
    'success' => false,
    'message' => '',
    'data' => [],
    'current_page' => $page,
    'last_page' => 1
];

try {
    // Get total number of records
    $total_result = $conn->query("SELECT COUNT(*) as total FROM khachhang");
    $total_rows = $total_result->fetch_assoc()['total'];
    $response['last_page'] = ceil($total_rows / $size);

    // Fetch paged data
    $sql = "
        SELECT 
            kh.KhachHangID,
            kh.TenCongTy,
            kh.NguoiLienHe,
            kh.SoDienThoai,
            kh.SoFax,
            kh.SoDiDong,
            kh.Email,
            kh.DiaChi,
            kh.MaSoThue,
            kh.CoCheGiaID,
            cg.TenCoChe 
        FROM 
            khachhang kh
        LEFT JOIN 
            cochegia cg ON kh.CoCheGiaID = cg.CoCheGiaID
        ORDER BY 
            kh.tencongty ASC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $size, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $customers;
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = "Database query failed: " . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
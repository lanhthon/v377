<?php
// File: api/get_delivery_plans.php
// Version: 2.1 - Sửa lỗi nhân đôi bản ghi và cập nhật đúng giá trị LoaiPhieu.
require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'plans' => []];

$donhang_id = isset($_GET['donhang_id']) ? intval($_GET['donhang_id']) : 0;

if ($donhang_id === 0) {
    $response['message'] = 'ID đơn hàng không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $pdo = get_db_connection();
    
    // Cập nhật câu truy vấn để chỉ JOIN với phiếu xuất kho "xuat_thanh_pham"
    // và sử dụng GROUP BY để đảm bảo mỗi kế hoạch giao hàng chỉ có một dòng duy nhất.
    $stmt = $pdo->prepare("
        SELECT 
            khgh.*, 
            cbh.CBH_ID,
            -- Lấy PhieuXuatKhoID lớn nhất (mới nhất) thuộc loại 'xuat_thanh_pham'
            MAX(pxk.PhieuXuatKhoID) AS PhieuXuatKhoID 
        FROM kehoach_giaohang khgh
        LEFT JOIN chuanbihang cbh ON khgh.KHGH_ID = cbh.KHGH_ID
        LEFT JOIN phieuxuatkho pxk ON cbh.CBH_ID = pxk.CBH_ID AND pxk.LoaiPhieu = 'xuat_thanh_pham'
        WHERE khgh.DonHangID = ? 
        GROUP BY khgh.KHGH_ID -- Nhóm kết quả theo ID kế hoạch để chống trùng lặp
        ORDER BY khgh.NgayGiaoDuKien DESC
    ");
    
    $stmt->execute([$donhang_id]);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['plans'] = $plans;

} catch (PDOException $e) {
    $response['message'] = 'Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage();
}

echo json_encode($response);
?>


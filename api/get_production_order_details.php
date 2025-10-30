<?php
// api/get_production_order_details.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định.'];
$lenhSX_ID = $_GET['id'] ?? 0;

if (!is_numeric($lenhSX_ID) || $lenhSX_ID <= 0) {
    http_response_code(400);
    $response['message'] = "ID Lệnh sản xuất không hợp lệ.";
    echo json_encode($response);
    exit;
}

try {
    global $conn;
    if ($conn->connect_error) {
        throw new Exception("Lỗi kết nối CSDL: " . $conn->connect_error);
    }

    // Câu truy vấn header giữ nguyên
    $stmt_header = $conn->prepare("
        SELECT 
            lsx.*, 
            dh.SoYCSX, 
            COALESCE(u.HoTen, 'Hệ thống') as NguoiYeuCau
        FROM lenh_san_xuat lsx
        LEFT JOIN donhang dh ON lsx.YCSX_ID = dh.YCSX_ID
        LEFT JOIN nguoidung u ON lsx.NguoiYeuCau_ID = u.UserID
        WHERE lsx.LenhSX_ID = ?
    ");
    if (!$stmt_header) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh (header): " . $conn->error);
    }
    $stmt_header->bind_param("i", $lenhSX_ID);
    $stmt_header->execute();
    $header = $stmt_header->get_result()->fetch_assoc();
    $stmt_header->close();

    if (!$header) {
        http_response_code(404);
        $response['message'] = "Không tìm thấy Lệnh sản xuất với ID là " . $lenhSX_ID;
        echo json_encode($response);
        $conn->close();
        exit;
    }

    // [CẬP NHẬT] - Thêm LEFT JOIN với nhat_ky_san_xuat để lấy số lượng đã sản xuất
    $stmt_items = $conn->prepare("
        SELECT 
            ct.*, 
            v.variant_sku AS MaHang,
            v.variant_name AS TenSanPham,
            uom.name as DonViTinh,
            COALESCE(nk.TongDaSanXuat, 0) AS SoLuongDaSanXuat
        FROM chitiet_lenh_san_xuat ct
        JOIN variants v ON ct.SanPhamID = v.variant_id
        LEFT JOIN products p ON v.product_id = p.product_id
        LEFT JOIN units uom ON p.base_unit_id = uom.unit_id
        LEFT JOIN (
            SELECT
                ChiTiet_LSX_ID,
                SUM(SoLuongHoanThanh) AS TongDaSanXuat
            FROM
                nhat_ky_san_xuat
            GROUP BY
                ChiTiet_LSX_ID
        ) nk ON ct.ChiTiet_LSX_ID = nk.ChiTiet_LSX_ID
        WHERE ct.LenhSX_ID = ?
        ORDER BY ct.ChiTiet_LSX_ID
    ");
    if (!$stmt_items) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh (items): " . $conn->error);
    }
    $stmt_items->bind_param("i", $lenhSX_ID);
    $stmt_items->execute();
    $items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();

    $conn->close();
    
    echo json_encode(['success' => true, 'data' => ['info' => $header, 'items' => $items]]);

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
    error_log("Lỗi trong get_production_order_details.php: " . $e->getMessage());
    echo json_encode($response);
}
?>

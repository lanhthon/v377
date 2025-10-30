<?php
/**
 * API: LẤY CHI TIẾT LỆNH SẢN XUẤT (LK) ĐỂ TẠO PHIẾU NHẬP KHO
 */
require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => '', 'header' => null, 'items' => []];
$lsx_id = isset($_GET['lsx_id']) ? intval($_GET['lsx_id']) : 0;

if ($lsx_id <= 0) {
    $response['message'] = 'ID Lệnh Sản Xuất không hợp lệ.';
    echo json_encode($response);
    exit();
}

try {
    $pdo = get_db_connection();

    // 1. Lấy thông tin header của Lệnh Sản Xuất
    $stmt_header = $pdo->prepare("SELECT SoLenhSX FROM lenh_san_xuat WHERE LenhSX_ID = ?");
    $stmt_header->execute([$lsx_id]);
    $header_info = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header_info) {
        $response['message'] = 'Không tìm thấy Lệnh Sản Xuất.';
        echo json_encode($response);
        exit();
    }
    $response['header'] = $header_info;

    // 2. Lấy danh sách sản phẩm cần nhập từ chi tiết LSX
    $sql_items = "
        SELECT 
            clsx.ChiTiet_LSX_ID,
            v.variant_id,
            v.variant_sku AS MaHang,
            v.variant_name AS TenSanPham,
            u.name AS DonViTinh,
            (clsx.SoLuongCayCanSX - clsx.SoLuongDaNhap) AS SoLuongCanNhap -- Giả sử cột này lưu số lượng cần sản xuất
        FROM 
            chitiet_lenh_san_xuat clsx
        JOIN 
            variants v ON clsx.SanPhamID = v.variant_id
        LEFT JOIN
            products p ON v.product_id = p.product_id
        LEFT JOIN
            units u ON p.base_unit_id = u.unit_id
        WHERE 
            clsx.LenhSX_ID = ? AND (clsx.SoLuongCayCanSX - clsx.SoLuongDaNhap) > 0
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$lsx_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['items'] = $items;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

<?php
// File: api/get_pnk_tp_details.php
// CHỨC NĂNG: Lấy chi tiết một phiếu nhập kho thành phẩm ĐÃ TỒN TẠI để xem lại.

require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'data' => null, 'message' => ''];

$pnk_id = isset($_GET['pnk_id']) ? intval($_GET['pnk_id']) : 0;

if ($pnk_id <= 0) {
    $response['message'] = 'ID Phiếu nhập kho không hợp lệ.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = get_db_connection();

    // Lấy thông tin header của phiếu
    // *** SỬA ĐỔI: Thêm pnk.PhieuNhapKhoID vào danh sách các trường được chọn ***
    $sql_header = "SELECT 
                    pnk.PhieuNhapKhoID, 
                    pnk.SoPhieuNhapKho,
                    pnk.NgayNhap,
                    pnk.LyDoNhap,
                    dh.SoYCSX,
                    nd.HoTen AS TenNguoiTao
                 FROM phieunhapkho pnk
                 LEFT JOIN donhang dh ON pnk.YCSX_ID = dh.YCSX_ID
                 LEFT JOIN nguoidung nd ON pnk.NguoiTaoID = nd.UserID
                 WHERE pnk.PhieuNhapKhoID = ?";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$pnk_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        throw new Exception("Không tìm thấy phiếu nhập kho với ID: " . $pnk_id);
    }

    // Lấy các dòng chi tiết của phiếu TỪ BẢNG `chitietphieunhapkho`
    $sql_items = "SELECT 
                    ct.SoLuong,
                    ct.SoLuongTheoDonHang,
                    ct.GhiChu,
                    v.variant_id, -- Thêm variant_id để đảm bảo dữ liệu nhất quán
                    v.variant_sku AS MaHang,
                    v.variant_name AS TenSanPham,
                    u.name AS DonViTinh
                FROM chitietphieunhapkho ct
                LEFT JOIN variants v ON ct.SanPhamID = v.variant_id
                LEFT JOIN products p ON v.product_id = p.product_id
                LEFT JOIN units u ON p.base_unit_id = u.unit_id
                WHERE ct.PhieuNhapKhoID = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$pnk_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $response['data'] = ['header' => $header, 'items' => $items];
    $response['success'] = true;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

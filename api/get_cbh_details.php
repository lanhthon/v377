<?php
// File: api/get_cbh_details.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$cbhId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($cbhId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ.']);
    exit;
}

try {
    // Lấy thông tin chính của phiếu
    $stmt_info = $conn->prepare("SELECT * FROM chuanbihang WHERE CBH_ID = ?");
    $stmt_info->bind_param("i", $cbhId);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();
    $stmt_info->close();

    if (!$info) {
        throw new Exception("Không tìm thấy phiếu chuẩn bị hàng.");
    }

    // Lấy chi tiết các sản phẩm
    $stmt_items = $conn->prepare("
        SELECT 
            ct.*, 
            sp.ID_ThongSo, sp.DoDay, sp.BanRong,
            ls.TenLoai,
            clsx.SoLuongCayCanSX as CayCat
        FROM chitietchuanbihang ct
        JOIN sanpham sp ON ct.SanPhamID = sp.SanPhamID
        LEFT JOIN loaisanpham ls ON sp.LoaiID = ls.LoaiID
        LEFT JOIN donhang dh ON ct.CBH_ID = dh.CBH_ID
        LEFT JOIN lenh_san_xuat lsx ON dh.YCSX_ID = lsx.YCSX_ID
        LEFT JOIN chitiet_lenh_san_xuat clsx ON lsx.LenhSX_ID = clsx.LenhSX_ID AND ct.SanPhamID = clsx.SanPhamID
        WHERE ct.CBH_ID = ?
        ORDER BY ls.TenLoai, ct.ThuTuHienThi ASC
    ");
    $stmt_items->bind_param("i", $cbhId);
    $stmt_items->execute();
    $items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();

    echo json_encode(['success' => true, 'cbh' => ['info' => $info, 'items' => $items]]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}

$conn->close();
?>
<?php
// api/get_btp_details_for_receipt.php
require_once '../config/db_config.php'; // Sử dụng đường dẫn của bạn
header('Content-Type: application/json; charset=utf-8');

/**
 * Gửi phản hồi lỗi dưới dạng JSON và kết thúc script.
 * @param string $message Nội dung lỗi.
 */
function send_error($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// Lấy cbh_id từ tham số GET và kiểm tra tính hợp lệ
$cbh_id = isset($_GET['cbh_id']) ? intval($_GET['cbh_id']) : 0;

if ($cbh_id <= 0) {
    send_error('ID Phiếu Chuẩn Bị Hàng không hợp lệ.');
}

try {
    // Sử dụng hàm get_db_connection() từ file config của bạn
    $pdo = get_db_connection();

    // 1. Lấy thông tin SoYCSX từ bảng chuanbihang và donhang
    // Bước này vẫn cần thiết để hiển thị SoYCSX trên giao diện
    $stmt_cbh = $pdo->prepare(
        "SELECT dh.SoYCSX
         FROM chuanbihang cbh
         JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
         WHERE cbh.CBH_ID = ?"
    );
    $stmt_cbh->execute([$cbh_id]);
    $cbh_info = $stmt_cbh->fetch(PDO::FETCH_ASSOC);

    if (!$cbh_info) {
        send_error('Không tìm thấy Phiếu Chuẩn Bị Hàng.');
    }
    $so_ycsx = $cbh_info['SoYCSX'];

    // 2. TÌM LỆNH SẢN XUẤT BTP DỰA TRÊN CBH_ID (ĐÂY LÀ THAY ĐỔI QUAN TRỌNG)
    // Logic mới: Mỗi phiếu chuẩn bị hàng (đợt giao) sẽ có lệnh sản xuất riêng.
    $stmt_lsx = $pdo->prepare(
        "SELECT LenhSX_ID, SoLenhSX 
         FROM lenh_san_xuat 
         WHERE CBH_ID = ? AND LoaiLSX = 'BTP'"
    );
    $stmt_lsx->execute([$cbh_id]);
    $lsx_info = $stmt_lsx->fetch(PDO::FETCH_ASSOC);

    // Nếu không có Lệnh sản xuất BTP nào cho phiếu CBH này, trả về danh sách rỗng.
    // Điều này là bình thường nếu đợt giao chỉ có hàng ULA hoặc hàng có sẵn trong kho.
    if (!$lsx_info) {
        echo json_encode([
            'success' => true, 
            'header' => ['SoYCSX' => $so_ycsx], 
            'SoLenhSX' => null, // Không có LSX
            'items' => []
        ]);
        exit();
    }
    $lenh_sx_id = $lsx_info['LenhSX_ID'];
    $so_lenh_sx = $lsx_info['SoLenhSX'];

    // 3. Lấy danh sách BTP cần nhập từ chi tiết LSX
    // Câu truy vấn này không thay đổi, vì nó đã dựa trên LenhSX_ID.
    $sql_items = "
        SELECT 
            clsx.ChiTiet_LSX_ID,
            v.variant_id AS BTP_ID,
            v.variant_sku AS MaBTP,
            v.variant_name AS TenBTP,
            u.name AS DonViTinh,
            (clsx.SoLuongCayCanSX - clsx.SoLuongDaNhap) AS SoLuongCanNhap
        FROM 
            chitiet_lenh_san_xuat clsx
        JOIN 
            variants v ON clsx.SanPhamID = v.variant_id
        LEFT JOIN
            products p ON v.product_id = p.product_id
        LEFT JOIN
            units u ON p.base_unit_id = u.unit_id
        WHERE 
            clsx.LenhSX_ID = ?
            AND (clsx.SoLuongCayCanSX - clsx.SoLuongDaNhap) > 0
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$lenh_sx_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 4. Đóng gói và trả về kết quả
    $response = [
        'success' => true,
        'header' => ['SoYCSX' => $so_ycsx],
        'SoLenhSX' => $so_lenh_sx,
        'items' => $items
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Ghi log lỗi để dễ dàng debug
    error_log('API Error in get_btp_details_for_receipt.php: ' . $e->getMessage());
    send_error('Lỗi cơ sở dữ liệu: ' . $e->getMessage());
}
?>

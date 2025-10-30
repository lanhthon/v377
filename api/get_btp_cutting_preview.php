<?php
// File: api/get_btp_cutting_preview.php
// Version: 1.1 - Sửa lỗi SQLSTATE[HY093]
// Lấy dữ liệu xem trước cho việc xuất kho BTP để cắt
require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$cbh_id = $_GET['cbh_id'] ?? 0;

if (empty($cbh_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Phiếu chuẩn bị hàng không hợp lệ.']);
    exit;
}

try {
    $pdo = get_db_connection();

    // Lấy danh sách BTP cần xuất từ bảng chi tiết BTP của phiếu CBH
    // [FIX V1.1] Chuyển sang dùng placeholder vị trí (?) để tránh lỗi "Invalid parameter number"
    $sql = "
        SELECT 
            ctb.MaBTP, 
            ctb.TenBTP, 
            ctb.SoCayCat,
            v.variant_id,
            vi.quantity AS tonKhoVatLy,
            (SELECT COALESCE(SUM(dp.SoLuongPhanBo), 0) 
             FROM donhang_phanbo_tonkho dp 
             WHERE dp.SanPhamID = v.variant_id AND dp.CBH_ID = ?) AS ganChoPhieuNay
        FROM chitiet_btp_cbh ctb
        JOIN variants v ON ctb.MaBTP = v.variant_sku
        LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
        WHERE ctb.CBH_ID = ? AND ctb.SoCayCat IS NOT NULL AND ctb.SoCayCat > 0
    ";
    
    $stmt = $pdo->prepare($sql);
    // [FIX V1.1] Cung cấp giá trị cho cả hai placeholder
    $stmt->execute([$cbh_id, $cbh_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'Không có bán thành phẩm nào cần xuất để cắt cho phiếu này.']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $items]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
?>


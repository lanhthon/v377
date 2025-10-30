<?php
/**
 * File: api/update_delivery_status.php
 * Version: 1.1 - Fixed by AI
 * Description: API để cập nhật trạng thái của một phiếu xuất kho (giao hàng, hủy).
 * - [FIX] Loại bỏ câu lệnh cập nhật cột 'TrangThai' trong bảng 'phieuxuatkho' vì cột này không tồn tại
 * trong schema, gây ra lỗi SQL.
 * - Giữ nguyên logic cập nhật trạng thái cho 'chuanbihang' và kiểm tra 'donhang'.
 */

require_once '../config/db_config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

/**
 * Kiểm tra xem tất cả sản phẩm trong một đơn hàng đã được giao đủ hay chưa và cập nhật trạng thái.
 *
 * @param PDO $pdo Đối tượng kết nối CSDL.
 * @param int $ycsx_id ID của đơn hàng (YCSX_ID) cần kiểm tra.
 * @return string Trạng thái mới của đơn hàng ('Hoàn thành', 'Đang giao hàng', hoặc trạng thái hiện tại).
 */
function check_and_update_order_status($pdo, $ycsx_id) {
    // 1. Lấy tổng số lượng yêu cầu cho từng sản phẩm trong đơn hàng gốc.
    $stmt_required = $pdo->prepare("
        SELECT SanPhamID, SoLuong 
        FROM chitiet_donhang 
        WHERE DonHangID = ? AND SoLuong > 0
    ");
    $stmt_required->execute([$ycsx_id]);
    $required_items = $stmt_required->fetchAll(PDO::FETCH_KEY_PAIR);

    if (empty($required_items)) {
        // Đơn hàng không có sản phẩm nào cần giao, coi như hoàn thành.
        $stmt_update = $pdo->prepare("UPDATE donhang SET TrangThai = 'Hoàn thành' WHERE YCSX_ID = ?");
        $stmt_update->execute([$ycsx_id]);
        return 'Hoàn thành';
    }

    // 2. Lấy tổng số lượng đã thực giao cho từng sản phẩm của đơn hàng đó.
    $stmt_shipped = $pdo->prepare("
        SELECT
            ctpxk.SanPhamID,
            SUM(ctpxk.SoLuongThucXuat) as TongDaGiao
        FROM chitiet_phieuxuatkho ctpxk
        JOIN phieuxuatkho pxk ON ctpxk.PhieuXuatKhoID = pxk.PhieuXuatKhoID
        WHERE pxk.YCSX_ID = ?
        GROUP BY ctpxk.SanPhamID
    ");
    $stmt_shipped->execute([$ycsx_id]);
    $shipped_items = $stmt_shipped->fetchAll(PDO::FETCH_KEY_PAIR);

    // 3. So sánh số lượng yêu cầu và số lượng đã giao.
    $is_fully_complete = true;
    foreach ($required_items as $sanpham_id => $so_luong_can) {
        $so_luong_da_giao = $shipped_items[$sanpham_id] ?? 0;
        if ($so_luong_da_giao < $so_luong_can) {
            $is_fully_complete = false;
            break; 
        }
    }

    // 4. Cập nhật trạng thái đơn hàng gốc dựa trên kết quả kiểm tra.
    $new_order_status = '';
    if ($is_fully_complete) {
        $new_order_status = 'Hoàn thành';
    } else {
        $new_order_status = !empty($shipped_items) ? 'Đang giao hàng' : 'Chờ xuất kho';
    }

    $stmt_update_order = $pdo->prepare("UPDATE donhang SET TrangThai = ? WHERE YCSX_ID = ?");
    $stmt_update_order->execute([$new_order_status, $ycsx_id]);

    return $new_order_status;
}


// --- MAIN EXECUTION ---
$response = ['success' => false, 'message' => ''];

$pxk_id = $_POST['pxk_id'] ?? 0;
$new_status = $_POST['status'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

// --- 1. KIỂM TRA ĐẦU VÀO ---
if ($pxk_id <= 0) {
    $response['message'] = 'ID Phiếu xuất kho không hợp lệ.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
if (empty($new_status)) {
    $response['message'] = 'Trạng thái mới không được để trống.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
if ($userId === null) {
    http_response_code(401);
    $response['message'] = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // --- 2. LẤY THÔNG TIN CẦN THIẾT (YCSX_ID, CBH_ID) TỪ PXK ---
    $stmt_get_ids = $pdo->prepare("SELECT YCSX_ID, CBH_ID FROM phieuxuatkho WHERE PhieuXuatKhoID = ?");
    $stmt_get_ids->execute([$pxk_id]);
    $ids = $stmt_get_ids->fetch(PDO::FETCH_ASSOC);

    if (!$ids) {
        throw new Exception("Không tìm thấy phiếu xuất kho với ID: " . $pxk_id);
    }
    $ycsx_id = $ids['YCSX_ID'];
    $cbh_id = $ids['CBH_ID'];

    // --- 3. CẬP NHẬT TRẠNG THÁI CHO CÁC PHIẾU LIÊN QUAN ---
    
    // [FIX] Dòng lệnh này đã bị vô hiệu hóa vì cột 'TrangThai' không tồn tại trong bảng 'phieuxuatkho'.
    // $stmt_update_pxk = $pdo->prepare("UPDATE phieuxuatkho SET TrangThai = ? WHERE PhieuXuatKhoID = ?");
    // $stmt_update_pxk->execute([$new_status, $pxk_id]);

    // Cập nhật phiếu chuẩn bị hàng tương ứng
    if ($cbh_id) {
        $stmt_update_cbh = $pdo->prepare("UPDATE chuanbihang SET TrangThai = ? WHERE CBH_ID = ?");
        $stmt_update_cbh->execute([$new_status, $cbh_id]);
    }
    
    // --- 4. KIỂM TRA VÀ CẬP NHẬT ĐƠN HÀNG GỐC ---
    if ($ycsx_id) {
        if ($new_status === 'Đã giao hàng') {
            check_and_update_order_status($pdo, $ycsx_id);
        }
    }

    // --- 5. HOÀN TẤT ---
    $pdo->commit();
    $response['success'] = true;
    $response['message'] = "Cập nhật trạng thái thành công!";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    $response['message'] = 'Lỗi: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

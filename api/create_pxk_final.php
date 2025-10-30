<?php
/**
 * File: api/create_pxk_final.php

 * Description: API để thông báo một Phiếu Chuẩn Bị Hàng đã sẵn sàng để xuất kho.
 * - Cập nhật trạng thái của Phiếu Chuẩn Bị Hàng (chuanbihang) thành "Chờ xuất kho".
 * - Cập nhật trạng thái của Đơn Hàng Gốc (donhang) thành "Đang giao hàng" để phản ánh rằng
 * quá trình giao hàng đã bắt đầu, hỗ trợ cho việc có nhiều đợt giao.
 */

require_once '../config/db_config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

$cbh_id = $_POST['cbh_id'] ?? 0;
$userId = $_SESSION['user_id'] ?? null;

// --- 1. KIỂM TRA ĐẦU VÀO ---
if ($cbh_id <= 0) {
    $response['message'] = 'ID Phiếu chuẩn bị hàng không hợp lệ.';
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

    // --- 2. LẤY THÔNG TIN ĐƠN HÀNG GỐC TỪ CBH_ID ---
    $stmt_get_order_info = $pdo->prepare("
        SELECT dh.YCSX_ID, dh.TrangThai 
        FROM chuanbihang cbh
        JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
        WHERE cbh.CBH_ID = ?
    ");
    $stmt_get_order_info->execute([$cbh_id]);
    $order_info = $stmt_get_order_info->fetch(PDO::FETCH_ASSOC);

    if (!$order_info) {
        throw new Exception("Không tìm thấy Phiếu chuẩn bị hàng hoặc đơn hàng liên quan với ID: " . $cbh_id);
    }

    $ycsx_id = $order_info['YCSX_ID'];
    $current_order_status = $order_info['TrangThai'];
    
    // Trạng thái mới cho phiếu chuẩn bị hàng
    $new_cbh_status = 'Chờ xuất kho';
    
    // Trạng thái mới cho đơn hàng tổng
    $new_order_status = 'Đang giao hàng';

    // --- 3. CẬP NHẬT TRẠNG THÁI CỦA PHIẾU CHUẨN BỊ HÀNG ---
    // Phiếu này đã sẵn sàng, chuyển sang trạng thái chờ xuất.
    $stmt_update_cbh = $pdo->prepare("UPDATE chuanbihang SET TrangThai = ? WHERE CBH_ID = ?");
    $stmt_update_cbh->execute([$new_cbh_status, $cbh_id]);

    // --- 4. CẬP NHẬT TRẠNG THÁI CỦA ĐƠN HÀNG GỐC (YCSX) ---
    // Chỉ cập nhật trạng thái đơn hàng nếu nó chưa hoàn thành.
    // Điều này đảm bảo rằng trạng thái 'Hoàn thành' không bị ghi đè.
    if ($current_order_status !== 'Hoàn thành') {
        $stmt_update_donhang = $pdo->prepare("UPDATE donhang SET TrangThai = ? WHERE YCSX_ID = ?");
        $stmt_update_donhang->execute([$new_order_status, $ycsx_id]);
    }

    // --- 5. HOÀN TẤT VÀ GỬI PHẢN HỒI ---
    $pdo->commit();
    $response['success'] = true;
    $response['message'] = "Phiếu chuẩn bị hàng đã sẵn sàng. Trạng thái đã được cập nhật thành công!";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    $response['message'] = 'Lỗi: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

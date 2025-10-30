<?php
/**
 * File: api/update_chuanbihang_to_nhapkho.php
 * Description: API để cập nhật trạng thái của phiếu chuẩn bị hàng sang "Chờ Nhập Kho".
 * Version: 2.0 - [SỬA LỖI] Nhận LoaiLSX trực tiếp từ frontend để tránh nhầm lẫn.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

$input = json_decode(file_get_contents('php://input'), true);
$cbh_id = isset($input['cbh_id']) ? intval($input['cbh_id']) : 0;
// [THAY ĐỔI] Nhận thêm loai_lsx từ request
$loaiLSX = isset($input['loai_lsx']) ? trim($input['loai_lsx']) : '';

// [THAY ĐỔI] Kiểm tra cả cbh_id và loaiLSX
if ($cbh_id === 0 || empty($loaiLSX)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Chuẩn bị hàng hoặc Loại LSX không hợp lệ.']);
    exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    $newStatus = '';
    $targetColumn = '';
    
    // Xác định trạng thái và cột cần cập nhật dựa trên loại LSX được gửi lên
    switch (strtoupper($loaiLSX)) {
        case 'ULA':
            $newStatus = 'Chờ nhập ULA';
            $targetColumn = 'TrangThaiULA';
            break;
        case 'BTP':
            $newStatus = 'Chờ Nhập Kho BTP';
            $targetColumn = 'TrangThai';
            break;
        default:
             throw new Exception('Loại LSX không xác định: ' . htmlspecialchars($loaiLSX));
    }

    // Cập nhật trạng thái trong bảng chuanbihang
    $stmt_update_cbh = $pdo->prepare("UPDATE chuanbihang SET {$targetColumn} = :status WHERE CBH_ID = :cbh_id");
    $stmt_update_cbh->execute([':status' => $newStatus, ':cbh_id' => $cbh_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Đã chuyển trạng thái chuẩn bị hàng sang '{$newStatus}'."]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>

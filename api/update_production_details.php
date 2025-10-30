<?php
/**
 * File: api/update_production_details.php
 * Version: 2.1 - Thêm cập nhật ngày hoàn thành ước tính
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

global $conn;

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$lenhSX_ID = isset($data['LenhSX_ID']) ? intval($data['LenhSX_ID']) : 0;
$items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

// Lấy thêm dữ liệu mới
$nguoiNhanSX = $data['NguoiNhanSX'] ?? null;
$boPhanSX = $data['BoPhanSX'] ?? null;
// [CẬP NHẬT] Lấy ngày hoàn thành, nếu rỗng thì set là NULL
$ngayHoanThanh = !empty($data['NgayHoanThanhUocTinh']) ? $data['NgayHoanThanhUocTinh'] : null;

if ($lenhSX_ID <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

$conn->begin_transaction();
try {
    // [CẬP NHẬT] Cập nhật thông tin chung của LSX bao gồm cả ngày hoàn thành
    $stmt_main = $conn->prepare("UPDATE lenh_san_xuat SET NguoiNhanSX = ?, BoPhanSX = ?, NgayHoanThanhUocTinh = ? WHERE LenhSX_ID = ?");
    // [CẬP NHẬT] Thêm 's' cho kiểu dữ liệu string của ngày
    $stmt_main->bind_param("sssi", $nguoiNhanSX, $boPhanSX, $ngayHoanThanh, $lenhSX_ID);
    $stmt_main->execute();
    $stmt_main->close();

    // Cập nhật chi tiết các dòng sản phẩm (nếu có)
    if (!empty($items)) {
        $stmt_details = $conn->prepare("
            UPDATE chitiet_lenh_san_xuat 
            SET TrangThai = ?, GhiChu = ? 
            WHERE ChiTiet_LSX_ID = ? AND LenhSX_ID = ?
        ");
        
        foreach ($items as $item) {
            $stmt_details->bind_param("ssii", $item['TrangThai'], $item['GhiChu'], $item['ChiTiet_LSX_ID'], $lenhSX_ID);
            $stmt_details->execute();
        }
        $stmt_details->close();
    }
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Đã cập nhật lệnh sản xuất thành công.']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

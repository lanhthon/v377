<?php
/**
 * File: api/get_gia_cong_detail.php
 * Description: API lấy chi tiết phiếu xuất gia công
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

$phieuId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$phieuId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID phiếu không hợp lệ'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = get_db_connection();

    // Lấy thông tin phiếu
    $sql = "
        SELECT
            pxgc.*,
            cbh.SoCBH,
            cbh.TrangThai as TrangThaiCBH,
            v_xuat.variant_name as TenSanPhamXuat,
            v_nhan.variant_name as TenSanPhamNhan
        FROM phieu_xuat_gia_cong pxgc
        LEFT JOIN chuanbihang cbh ON pxgc.CBH_ID = cbh.CBH_ID
        LEFT JOIN variants v_xuat ON pxgc.SanPhamXuatID = v_xuat.variant_id
        LEFT JOIN variants v_nhan ON pxgc.SanPhamNhanID = v_nhan.variant_id
        WHERE pxgc.PhieuXuatGC_ID = :phieuId
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':phieuId' => $phieuId]);
    $phieu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$phieu) {
        throw new Exception('Không tìm thấy phiếu gia công');
    }

    // Lấy lịch sử gia công
    $sqlLichSu = "
        SELECT *
        FROM lich_su_gia_cong
        WHERE PhieuXuatGC_ID = :phieuId
        ORDER BY NgayCapNhat DESC
    ";

    $stmtLichSu = $pdo->prepare($sqlLichSu);
    $stmtLichSu->execute([':phieuId' => $phieuId]);
    $lichSu = $stmtLichSu->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $phieu,
        'lich_su' => $lichSu
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("ERROR in get_gia_cong_detail.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

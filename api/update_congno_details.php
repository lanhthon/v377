<?php
// File: api/update_congno_details.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['YCSX_ID'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

global $conn;

try {
    $ycsx_id = $data['YCSX_ID'];
    $so_tien_tam_ung = $data['SoTienTamUng'] ?? 0;
    $thoi_han_thanh_toan = !empty($data['ThoiHanThanhToan']) ? $data['ThoiHanThanhToan'] : null;
    $don_vi_tra = !empty($data['DonViTra']) ? $data['DonViTra'] : null;
    $trang_thai_thanh_toan = $data['TrangThaiThanhToan'] ?? 'Chưa thanh toán';

    // Fetch TongTien to calculate GiaTriConLai
    $stmt_tongtien = $conn->prepare("SELECT TongTien FROM donhang WHERE YCSX_ID = ?");
    $stmt_tongtien->bind_param("i", $ycsx_id);
    $stmt_tongtien->execute();
    $tong_gia_tri = $stmt_tongtien->get_result()->fetch_assoc()['TongTien'] ?? 0;
    $stmt_tongtien->close();
    $gia_tri_con_lai = $tong_gia_tri - $so_tien_tam_ung;
    
    // Auto-update status based on payment amount
    if ($so_tien_tam_ung <= 0) {
        $trang_thai_thanh_toan = 'Chưa thanh toán';
    } elseif ($gia_tri_con_lai <= 0) {
        $trang_thai_thanh_toan = 'Đã thanh toán';
    } else {
        $trang_thai_thanh_toan = 'Thanh toán 1 phần';
    }

    $stmt = $conn->prepare("
        INSERT INTO quanly_congno (YCSX_ID, SoTienTamUng, GiaTriConLai, ThoiHanThanhToan, DonViTra, TrangThaiThanhToan)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        SoTienTamUng = VALUES(SoTienTamUng),
        GiaTriConLai = VALUES(GiaTriConLai),
        ThoiHanThanhToan = VALUES(ThoiHanThanhToan),
        DonViTra = VALUES(DonViTra),
        TrangThaiThanhToan = VALUES(TrangThaiThanhToan)
    ");

    $stmt->bind_param("iddsss", $ycsx_id, $so_tien_tam_ung, $gia_tri_con_lai, $thoi_han_thanh_toan, $don_vi_tra, $trang_thai_thanh_toan);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật thành công.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật vào cơ sở dữ liệu.']);
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}

$conn->close();
?>


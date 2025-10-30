<?php
// File: api/create_or_get_bbgh.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$pxk_id = $_POST['pxk_id'] ?? 0;
$response = ['success' => false, 'bbgh_id' => null, 'message' => ''];

if (!$pxk_id) {
    $response['message'] = 'Thiếu ID Phiếu xuất kho.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->begin_transaction();
try {
    // Kiểm tra xem BBGH đã tồn tại chưa
    $stmt_check = $conn->prepare("SELECT BBGH_ID FROM bienbangiaohang WHERE PhieuXuatKhoID = ?");
    $stmt_check->bind_param("i", $pxk_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        // Nếu đã có, trả về ID cũ
        $existing = $result_check->fetch_assoc();
        $response['success'] = true;
        $response['bbgh_id'] = $existing['BBGH_ID'];
        $response['message'] = 'Biên bản giao hàng đã tồn tại. Đang chuyển hướng...';
        $stmt_check->close();
        $conn->commit();
    } else {
        // Nếu chưa có, tạo mới
        $stmt_check->close();

        // Lấy thông tin chung
        $stmt_info = $conn->prepare("
            SELECT dh.YCSX_ID, dh.BaoGiaID, dh.TenCongTy, dh.DiaChiGiaoHang, dh.NguoiNhan, dh.TenDuAn
            FROM phieuxuatkho pxk
            JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID
            WHERE pxk.PhieuXuatKhoID = ?
        ");
        $stmt_info->bind_param("i", $pxk_id);
        $stmt_info->execute();
        $info = $stmt_info->get_result()->fetch_assoc();
        $stmt_info->close();

        if (!$info) {
            throw new Exception("Không tìm thấy thông tin đơn hàng liên quan đến phiếu xuất kho ID: $pxk_id");
        }

        // Tạo số biên bản
        $so_bbgh = "BBGH-" . date('ymd') . "-" . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

        // Chèn vào bảng bienbangiaohang
        $stmt_create = $conn->prepare("
            INSERT INTO bienbangiaohang (YCSX_ID, PhieuXuatKhoID, BaoGiaID, SoBBGH, NgayTao, TenCongTy, DiaChiGiaoHang, NguoiNhanHang, DuAn)
            VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?)
        ");
        $stmt_create->bind_param("iiisssss", $info['YCSX_ID'], $pxk_id, $info['BaoGiaID'], $so_bbgh, $info['TenCongTy'], $info['DiaChiGiaoHang'], $info['NguoiNhan'], $info['TenDuAn']);
        $stmt_create->execute();
        $new_bbgh_id = $conn->insert_id;
        $stmt_create->close();
        
        // ====> SỬA LỖI: Sao chép chi tiết sản phẩm vào chitietbienbangiaohang <====
        $stmt_copy_items = $conn->prepare("
            INSERT INTO chitietbienbangiaohang (BBGH_ID, SanPhamID, MaHang, TenSanPham, ID_ThongSo, DoDay, BanRong, DonViTinh, SoLuong, SoThung, GhiChu)
            SELECT 
                ?, 
                cpxk.SanPhamID, 
                cpxk.MaHang, 
                cpxk.TenSanPham, 
                v_attr_id.value, 
                v_attr_day.value, 
                v_attr_rong.value,
                cpxk.DonViTinh, 
                cpxk.SoLuongThucXuat, 
                cpxk.TaiSo, 
                cpxk.GhiChu
            FROM chitiet_phieuxuatkho cpxk
            LEFT JOIN variants v ON cpxk.SanPhamID = v.variant_id
            LEFT JOIN variant_attributes va_id ON v.variant_id = va_id.variant_id AND va_id.option_id IN (SELECT option_id FROM attribute_options WHERE attribute_id = 1)
            LEFT JOIN attribute_options v_attr_id ON va_id.option_id = v_attr_id.option_id
            LEFT JOIN variant_attributes va_day ON v.variant_id = va_day.variant_id AND va_day.option_id IN (SELECT option_id FROM attribute_options WHERE attribute_id = 2)
            LEFT JOIN attribute_options v_attr_day ON va_day.option_id = v_attr_day.option_id
            LEFT JOIN variant_attributes va_rong ON v.variant_id = va_rong.variant_id AND va_rong.option_id IN (SELECT option_id FROM attribute_options WHERE attribute_id = 3)
            LEFT JOIN attribute_options v_attr_rong ON va_rong.option_id = v_attr_rong.option_id
            WHERE cpxk.PhieuXuatKhoID = ?
        ");
        $stmt_copy_items->bind_param("ii", $new_bbgh_id, $pxk_id);
        $stmt_copy_items->execute();
        $stmt_copy_items->close();
        
        $response['success'] = true;
        $response['bbgh_id'] = $new_bbgh_id;
        $response['message'] = 'Đã tạo Biên bản giao hàng và chi tiết thành công.';
        $conn->commit();
    }
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Lỗi Server: ' . $e->getMessage();
    http_response_code(500);
}
$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
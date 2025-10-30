<?php
// File: api/create_or_get_cccl.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$pxk_id = $_POST['pxk_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? null;
$response = ['success' => false, 'cccl_id' => null, 'message' => ''];

if (!$pxk_id || !$user_id) {
    $response['message'] = 'Thiếu ID Phiếu xuất kho hoặc thông tin người dùng.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->begin_transaction();
try {
    // Bước 1: Kiểm tra xem CCCL đã tồn tại cho Phiếu xuất kho này chưa
    $stmt_check = $conn->prepare("SELECT CCCL_ID FROM chungchi_chatluong WHERE PhieuXuatKhoID = ?");
    $stmt_check->bind_param("i", $pxk_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Nếu đã tồn tại, trả về ID cũ
        $existing = $result_check->fetch_assoc();
        $response['success'] = true;
        $response['cccl_id'] = $existing['CCCL_ID'];
        $response['message'] = 'Chứng chỉ chất lượng đã tồn tại. Đang chuyển hướng...';
        $stmt_check->close();
        $conn->commit();
    } else {
        // Nếu chưa tồn tại, tiến hành tạo mới
        $stmt_check->close();

        // Lấy thông tin cần thiết
        $stmt_info = $conn->prepare("
            SELECT dh.TenCongTy, dh.DiaChiGiaoHang, dh.TenDuAn
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

        // Tìm BBGH_ID tương ứng
        $stmt_find_bbgh = $conn->prepare("SELECT BBGH_ID FROM bienbangiaohang WHERE PhieuXuatKhoID = ?");
        $stmt_find_bbgh->bind_param("i", $pxk_id);
        $stmt_find_bbgh->execute();
        $bbgh_info = $stmt_find_bbgh->get_result()->fetch_assoc();
        $bbgh_id = $bbgh_info['BBGH_ID'] ?? null;
        $stmt_find_bbgh->close();

        // Tạo số chứng chỉ ngẫu nhiên
        $so_cccl = "CCCL-" . date('ymd') . "-" . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

        // Chèn bản ghi mới vào bảng chungchi_chatluong
        $stmt_create_cccl = $conn->prepare("
            INSERT INTO chungchi_chatluong (PhieuXuatKhoID, BBGH_ID, SoCCCL, NgayCap, TenCongTyKhach, DiaChiKhach, TenDuAn, NguoiLap)
            VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?)
        ");
        $stmt_create_cccl->bind_param("iissssi", $pxk_id, $bbgh_id, $so_cccl, $info['TenCongTy'], $info['DiaChiGiaoHang'], $info['TenDuAn'], $user_id);
        $stmt_create_cccl->execute();
        $new_cccl_id = $conn->insert_id;
        $stmt_create_cccl->close();

        // Sao chép toàn bộ sản phẩm từ chi tiết phiếu xuất kho sang chi tiết chứng chỉ
        $stmt_copy_items = $conn->prepare("
            INSERT INTO chitiet_chungchi_chatluong (CCCL_ID, SanPhamID, MaHang, TenSanPham, SoLuong, DonViTinh, TaiSo, GhiChuChiTiet)
            SELECT ?, SanPhamID, MaHang, TenSanPham, SoLuongThucXuat, DonViTinh, TaiSo, GhiChu
            FROM chitiet_phieuxuatkho WHERE PhieuXuatKhoID = ?
        ");
        $stmt_copy_items->bind_param("ii", $new_cccl_id, $pxk_id);
        $stmt_copy_items->execute();
        $stmt_copy_items->close();

        $response['success'] = true;
        $response['cccl_id'] = $new_cccl_id;
        $response['message'] = 'Đã tạo Chứng chỉ chất lượng thành công.';
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
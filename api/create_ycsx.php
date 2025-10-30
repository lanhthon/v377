<?php
/**
 * File: api/create_donhang.php
 * API để tạo Đơn hàng từ một báo giá đã chốt.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if ($data === null || !isset($data['baoGiaID'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ. Vui lòng cung cấp ID báo giá.']);
    exit;
}

$baoGiaID = (int)$data['baoGiaID'];

$conn->begin_transaction();

try {
    // *** CẬP NHẬT 1: Kiểm tra bảng `donhang` thay vì `yeucausanxuat` ***
    $sql_check = "
        SELECT b.TrangThai, d.YCSX_ID 
        FROM baogia b
        LEFT JOIN donhang d ON b.BaoGiaID = d.BaoGiaID
        WHERE b.BaoGiaID = ?
    ";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $baoGiaID);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $quote_status = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!$quote_status) {
        throw new Exception("Không tìm thấy báo giá với ID cung cấp.");
    }
    // *** CẬP NHẬT 2: Trạng thái trong DB của bạn là 'Chốt' ***
    if ($quote_status['TrangThai'] !== 'Chốt') {
        throw new Exception("Chỉ có thể tạo Đơn hàng từ một báo giá có trạng thái 'Chốt'.");
    }
    if ($quote_status['YCSX_ID'] !== null) {
        throw new Exception("Đơn hàng cho báo giá này đã được tạo trước đó.");
    }

    // *** CẬP NHẬT 3: Tạo mã đơn hàng mới, ví dụ: DH-2025-00072 ***
    $maDonHang = "DH-" . date("Y") . "-" . str_pad($baoGiaID, 5, "0", STR_PAD_LEFT);

    // *** CẬP NHẬT 4: Thêm vào bảng `donhang` ***
    // Bảng `donhang` của bạn đang dùng các cột SoYCSX, NgayTao, TrangThai
    $sql_donhang = "INSERT INTO donhang (BaoGiaID, SoYCSX, NgayTao, TrangThai) VALUES (?, ?, NOW(), 'Mới tạo')";
    $stmt_donhang = $conn->prepare($sql_donhang);
    $stmt_donhang->bind_param("is", $baoGiaID, $maDonHang);
    $stmt_donhang->execute();
    $donHangID = $conn->insert_id; // Đây chính là YCSX_ID
    if ($donHangID === 0) {
        throw new Exception("Không thể tạo bản ghi Đơn hàng mới.");
    }
    $stmt_donhang->close();

    // Bước 4: Lấy chi tiết sản phẩm từ báo giá gốc
    $sql_items = "
        SELECT 
            ct.TenNhom, ct.MaHang, ct.TenSanPham, ct.SoLuong, ct.GhiChu, ct.ThuTuHienThi,
            sp.ID_ThongSo, sp.DoDay, sp.BanRong
        FROM chitietbaogia ct
        LEFT JOIN sanpham sp ON ct.SanPhamID = sp.SanPhamID
        WHERE ct.BaoGiaID = ? AND ct.SoLuong > 0
        ORDER BY ct.ThuTuHienThi ASC, ct.ChiTietID ASC
    ";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $baoGiaID);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    // *** CẬP NHẬT 5: Thêm chi tiết vào bảng `chitiet_donhang` ***
    // Cột foreign key trong CSDL của bạn là `DonHangID`
    $sql_insert_detail = "
        INSERT INTO chitiet_donhang 
        (DonHangID, TenNhom, MaHang, TenSanPham, ID_ThongSo, DoDay, BanRong, SoLuong, GhiChu, ThuTuHienThi) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt_insert_detail = $conn->prepare($sql_insert_detail);

    while ($item = $result_items->fetch_assoc()) {
        $stmt_insert_detail->bind_param(
            "issssddisi",
            $donHangID, // ID của đơn hàng vừa tạo
            $item['TenNhom'],
            $item['MaHang'],
            $item['TenSanPham'],
            $item['ID_ThongSo'],
            $item['DoDay'],
            $item['BanRong'],
            $item['SoLuong'],
            $item['GhiChu'],
            $item['ThuTuHienThi']
        );
        $stmt_insert_detail->execute();
    }
    $stmt_items->close();
    $stmt_insert_detail->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Đơn hàng đã được tạo thành công!', 'donHangID' => $donHangID]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}

$conn->close();
?>
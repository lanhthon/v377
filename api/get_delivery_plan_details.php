<?php
/**
 * File: api/get_delivery_plan_details.php
 * Version: 2.0 - Sửa lỗi, lấy đầy đủ thông tin từ bảng donhang.
 * Description: API lấy thông tin chi tiết của một Đợt Giao Hàng,
 * bao gồm cả thông tin từ Đơn Hàng gốc và danh sách sản phẩm.
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
global $conn;

$khgh_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($khgh_id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID Kế hoạch giao hàng không hợp lệ.']);
    exit;
}

$response = ['success' => false, 'plan' => null];

try {
    // 1. THAY ĐỔI QUAN TRỌNG: Câu lệnh SELECT đã được cập nhật để lấy TẤT CẢ các cột từ bảng donhang
    // và sử dụng ALIAS (AS) để tránh các cột bị trùng tên (ví dụ: TrangThai, GhiChu...).
    $stmt_info = $conn->prepare("
        SELECT 
            khgh.*,
            dh.YCSX_ID, 
            dh.BaoGiaID, 
            dh.CongTyID, 
            dh.NguoiLienHeID, 
            dh.DuAnID, 
            dh.TenCongTy, 
            dh.NguoiNhan, 
            dh.TenDuAn, 
            dh.SoYCSX, 
            dh.NgayTao, 
            dh.NgayGiaoDuKien AS NgayGiaoDuKien_DonHang, 
            dh.NgayHoanThanhDuKien, 
            dh.TrangThai AS TrangThai_DonHang, 
            dh.NeedsProduction, 
            dh.BBGH_ID, 
            dh.CBH_ID AS CBH_ID_DonHang, 
            dh.GhiChu AS GhiChu_DonHang, 
            dh.NguoiBaoGia, 
            dh.DiaChiGiaoHang AS DiaChiGiaoHang_DonHang, 
            dh.DieuKienThanhToan, 
            dh.TongTien, 
            dh.TrangThaiCBH, 
            dh.PXK_ID
        FROM kehoach_giaohang AS khgh
        JOIN donhang AS dh ON khgh.DonHangID = dh.YCSX_ID
        WHERE khgh.KHGH_ID = ?
    ");
    $stmt_info->bind_param("i", $khgh_id);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();

    if ($result_info->num_rows > 0) {
        $data = $result_info->fetch_assoc();
        
        // Dựng lại cấu trúc JSON chuẩn
        $response['plan'] = [
            'info' => [],
            'order_info' => [],
            'items' => []
        ];

        // 2. Gán dữ liệu vào 'order_info' từ các cột của bảng donhang
        $response['plan']['order_info'] = [
            'YCSX_ID' => $data['YCSX_ID'],
            'BaoGiaID' => $data['BaoGiaID'],
            'CongTyID' => $data['CongTyID'],
            'NguoiLienHeID' => $data['NguoiLienHeID'],
            'DuAnID' => $data['DuAnID'],
            'TenCongTy' => $data['TenCongTy'],
            'NguoiNhan' => $data['NguoiNhan'],
            'TenDuAn' => $data['TenDuAn'],
            'SoYCSX' => $data['SoYCSX'],
            'NgayTao' => $data['NgayTao'],
            'NgayGiaoDuKien' => $data['NgayGiaoDuKien_DonHang'],
            'NgayHoanThanhDuKien' => $data['NgayHoanThanhDuKien'],
            'TrangThai' => $data['TrangThai_DonHang'],
            'NeedsProduction' => $data['NeedsProduction'],
            'BBGH_ID' => $data['BBGH_ID'],
            'CBH_ID' => $data['CBH_ID_DonHang'],
            'GhiChu' => $data['GhiChu_DonHang'],
            'NguoiBaoGia' => $data['NguoiBaoGia'],
            'DiaChiGiaoHang' => $data['DiaChiGiaoHang_DonHang'],
            'DieuKienThanhToan' => $data['DieuKienThanhToan'],
            'TongTien' => $data['TongTien'],
            'TrangThaiCBH' => $data['TrangThaiCBH'],
            'PXK_ID' => $data['PXK_ID'],
        ];

        // 3. Gán dữ liệu vào 'info' từ các cột của bảng kehoach_giaohang
        $response['plan']['info'] = [
             'KHGH_ID' => $data['KHGH_ID'],
             'DonHangID' => $data['DonHangID'],
             'SoKeHoach' => $data['SoKeHoach'],
             'NgayGiaoDuKien' => $data['NgayGiaoDuKien'],
             'TrangThai' => $data['TrangThai'],
             'NguoiNhanHang' => $data['NguoiNhanHang'],
             'DiaDiemGiaoHang' => $data['DiaDiemGiaoHang'],
             'DangKiCongTruong' => $data['DangKiCongTruong'],
             'XeGrap' => $data['XeGrap'],
             'XeTai' => $data['XeTai'],
             'SoLaiXe' => $data['SoLaiXe'],
             'QuyCachThung' => $data['QuyCachThung'],
             'GhiChu' => $data['GhiChu'],
             'CBH_ID' => $data['CBH_ID'],
             'PXK_ID' => $data['PXK_ID'],
             'created_at' => $data['created_at']
        ];


        // 4. Lấy danh sách sản phẩm trong đợt giao này
        $stmt_items = $conn->prepare("
            SELECT ctkhgh.SoLuongGiao, ctdh.TenSanPham, ctdh.MaHang
            FROM chitiet_kehoach_giaohang AS ctkhgh
            JOIN chitiet_donhang AS ctdh ON ctkhgh.ChiTiet_DonHang_ID = ctdh.ChiTiet_YCSX_ID
            WHERE ctkhgh.KHGH_ID = ?
        ");
        $stmt_items->bind_param("i", $khgh_id);
        $stmt_items->execute();
        $items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
        $response['plan']['items'] = $items;
        $stmt_items->close();

        $response['success'] = true;
    } else {
        $response['message'] = "Không tìm thấy đợt giao hàng.";
    }
    $stmt_info->close();

} catch (Exception $e) {
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
$conn->close();
?>
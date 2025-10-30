<?php
/**
 * File: api/create_chuanbihang_from_donhang.php
 * Version: 6.0 - Tự động tạo SoCBH và thêm các trường mới
 * Description: API tạo phiếu CBH, tự động sinh mã số phiếu và cập nhật trạng thái đơn hàng.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

global $conn;

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối CSDL.']);
    exit;
}
$conn->set_charset("utf8mb4");

$donhang_id = isset($_POST['donhang_id']) ? intval($_POST['donhang_id']) : 0;

if ($donhang_id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Kiểm tra phiếu đã tồn tại chưa
    $stmt_check = $conn->prepare("SELECT CBH_ID FROM chuanbihang WHERE YCSX_ID = ?");
    $stmt_check->bind_param("i", $donhang_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $existing = $result_check->fetch_assoc();
        $conn->rollback();
        echo json_encode(['success' => true, 'message' => 'Phiếu CBH đã tồn tại.', 'cbh_id' => $existing['CBH_ID'], 'existed' => true]);
        exit;
    }
    $stmt_check->close();

    // 2. Lấy thông tin đơn hàng
    $stmt_donhang = $conn->prepare("SELECT * FROM donhang WHERE YCSX_ID = ?");
    $stmt_donhang->bind_param("i", $donhang_id);
    $stmt_donhang->execute();
    $donhang_info = $stmt_donhang->get_result()->fetch_assoc();
    $stmt_donhang->close();

    if (!$donhang_info) {
        throw new Exception("Không tìm thấy đơn hàng.");
    }

    // <<< THÊM: Logic tự động tạo Số Phiếu CBH
    $prefix = "CBH-" . date('ym') . "-";
    $stmt_last_socbh = $conn->prepare("SELECT SoCBH FROM chuanbihang WHERE SoCBH LIKE ? ORDER BY SoCBH DESC LIMIT 1");
    $search_prefix = $prefix . '%';
    $stmt_last_socbh->bind_param("s", $search_prefix);
    $stmt_last_socbh->execute();
    $result_last_socbh = $stmt_last_socbh->get_result();
    $last_number = 0;
    if ($result_last_socbh->num_rows > 0) {
        $last_socbh = $result_last_socbh->fetch_assoc()['SoCBH'];
        $last_number = (int)substr($last_socbh, strlen($prefix));
    }
    $new_number = $last_number + 1;
    $new_socbh = $prefix . str_pad($new_number, 3, '0', STR_PAD_LEFT);
    $stmt_last_socbh->close();
    // <<< KẾT THÚC: Logic tạo số phiếu

    // 3. Tạo phiếu CBH mới với các trường đã được cập nhật
    // <<< THAY ĐỔI: Thêm các cột mới vào câu lệnh INSERT
    $stmt_cbh = $conn->prepare("
        INSERT INTO chuanbihang (
            YCSX_ID, BaoGiaID, SoCBH, NgayTao, TenCongTy, PhuTrach, NgayGiao, 
            NguoiNhanHang, SoDon, MaDon, DiaDiemGiaoHang, BoPhan, NgayGuiYCSX, TrangThai
        )
        VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, 'Kho - Logistic', CURDATE(), 'Mới tạo')
    ");
    // <<< THAY ĐỔI: Cập nhật bind_param cho các cột mới
    $stmt_cbh->bind_param("iissssssss",
        $donhang_id, 
        $donhang_info['BaoGiaID'],
        $new_socbh, // Cột mới
        $donhang_info['TenCongTy'], 
        $donhang_info['NguoiBaoGia'],
        $donhang_info['NgayGiaoDuKien'], 
        $donhang_info['NguoiNhan'], 
        $donhang_info['SoYCSX'],
        $donhang_info['YCSX_ID'], 
        $donhang_info['DiaChiGiaoHang']
    );
    $stmt_cbh->execute();
    $cbh_id = $conn->insert_id;
    $stmt_cbh->close();

    // 4. Lấy và sao chép chi tiết đơn hàng
    $stmt_get_order_details = $conn->prepare("SELECT * FROM chitiet_donhang WHERE DonHangID = ?");
    $stmt_get_order_details->bind_param("i", $donhang_id);
    $stmt_get_order_details->execute();
    $items_to_process = $stmt_get_order_details->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_get_order_details->close();
    
    $stmt_insert_cbh_detail = $conn->prepare("
        INSERT INTO chitietchuanbihang (CBH_ID, SanPhamID, MaHang, TenSanPham, SoLuong, ID_ThongSo, DoDay, BanRong, GhiChu, ThuTuHienThi, SoLuongCanSX, SoLuongLayTuKho, TonKho, DaGan) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0)
    ");
    
    // 5. Lặp qua từng sản phẩm và chèn vào chi tiết CBH
    foreach ($items_to_process as $item) {
        $soLuongYeuCau = (int)$item['SoLuong'];
        $soLuongCanSX = $soLuongYeuCau;

        $stmt_insert_cbh_detail->bind_param("iisssissssi", 
            $cbh_id, $item['SanPhamID'], $item['MaHang'], $item['TenSanPham'], 
            $soLuongYeuCau, $item['ID_ThongSo'], $item['DoDay'], $item['BanRong'], 
            $item['GhiChu'], $item['ThuTuHienThi'], $soLuongCanSX
        );
        $stmt_insert_cbh_detail->execute();
    }
    $stmt_insert_cbh_detail->close();

    // 6. Cập nhật trạng thái của đơn hàng gốc thành 'Gửi YCSX'
    $newDonHangStatus = 'Gửi YCSX'; 
    $stmt_update_donhang_status = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE YCSX_ID = ?");
    $stmt_update_donhang_status->bind_param("si", $newDonHangStatus, $donhang_id);
    $stmt_update_donhang_status->execute();
    $stmt_update_donhang_status->close();

    // 7. Hoàn tất giao dịch
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Tạo phiếu chuẩn bị hàng thành công!', 'cbh_id' => $cbh_id, 'donHangStatus' => $newDonHangStatus]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Lỗi khi tạo phiếu CBH từ đơn hàng ID $donhang_id: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>
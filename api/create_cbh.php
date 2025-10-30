<?php
/**
 * File: api/create_cbh.php (Phiên bản đã sửa)
 * API để tạo phiếu Chuẩn bị hàng từ một Đơn hàng (YCSX).
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$ycsxID = isset($data['ycsxID']) ? (int)$data['ycsxID'] : 0;

if ($ycsxID <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Đơn hàng không hợp lệ.']);
    exit;
}

// Bắt đầu transaction để đảm bảo toàn vẹn dữ liệu
$conn->begin_transaction();

try {
    // Bước 1: Kiểm tra xem đơn hàng có tồn tại và đã có phiếu CBH chưa
    $stmt_check = $conn->prepare("SELECT CBH_ID FROM donhang WHERE YCSX_ID = ?");
    $stmt_check->bind_param("i", $ycsxID);
    $stmt_check->execute();
    $order_data = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$order_data) {
        throw new Exception("Không tìm thấy đơn hàng với ID được cung cấp.");
    }

    if ($order_data['CBH_ID'] !== null) {
        http_response_code(409); // 409 Conflict: tài nguyên đã tồn tại
        throw new Exception('Phiếu chuẩn bị hàng đã tồn tại cho đơn hàng này.');
    }

    // Bước 2: Lấy thông tin cần thiết từ Đơn hàng và Báo giá
    $stmt_info = $conn->prepare("
        SELECT d.BaoGiaID, b.TenCongTy, b.DiaChiGiaoHang, b.NguoiNhan
        FROM donhang d
        JOIN baogia b ON d.BaoGiaID = b.BaoGiaID
        WHERE d.YCSX_ID = ?
    ");
    $stmt_info->bind_param("i", $ycsxID);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();
    $stmt_info->close();
    if (!$info) {
        throw new Exception("Không tìm thấy thông tin chi tiết của đơn hàng hoặc báo giá gốc.");
    }

    // Bước 3: Tạo phiếu CBH mới trong bảng `chuanbihang`
    $ngayTao = date('Y-m-d');
    $soCBH = "CBH-" . date('dmy') . "-" . str_pad($ycsxID, 4, '0', STR_PAD_LEFT);
    
    $stmt_cbh = $conn->prepare("
        INSERT INTO chuanbihang (YCSX_ID, BaoGiaID, SoCBH, NgayTao, TenCongTy, NguoiNhanHang, DiaDiemGiaoHang, NgayGuiYCSX)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_cbh->bind_param("iissssss",
        $ycsxID,
        $info['BaoGiaID'],
        $soCBH,
        $ngayTao,
        $info['TenCongTy'],
        $info['NguoiNhan'],
        $info['DiaChiGiaoHang'],
        $ngayTao // Lấy ngày hiện tại làm ngày gửi YCSX mặc định
    );
    $stmt_cbh->execute();
    $cbhID = $conn->insert_id;
    $stmt_cbh->close();

    // Bước 4: Sao chép chi tiết sản phẩm từ `chitiet_donhang` sang `chitietchuanbihang`
    $stmt_items = $conn->prepare("
        SELECT * FROM chitiet_donhang WHERE DonHangID = ? ORDER BY ThuTuHienThi ASC
    ");
    $stmt_items->bind_param("i", $ycsxID);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();

    $stmt_detail = $conn->prepare("
        INSERT INTO chitietchuanbihang (CBH_ID, TenNhom, SanPhamID, MaHang, TenSanPham, SoLuong, GhiChu, ThuTuHienThi, ID_ThongSo, DoDay, BanRong)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    while ($item = $items_result->fetch_assoc()) {
        $stmt_detail->bind_param("isississsss",
            $cbhID,
            $item['TenNhom'],
            $item['SanPhamID'],
            $item['MaHang'],
            $item['TenSanPham'],
            $item['SoLuong'],
            $item['GhiChu'],
            $item['ThuTuHienThi'],
            $item['ID_ThongSo'],
            $item['DoDay'],
            $item['BanRong']
        );
        $stmt_detail->execute();
    }
    $stmt_detail->close();
    $stmt_items->close();

    // Bước 5: Cập nhật lại Đơn hàng gốc, gán CBH_ID và đổi trạng thái
    $stmt_update_order = $conn->prepare("UPDATE donhang SET CBH_ID = ?, TrangThai = 'Chờ giao hàng' WHERE YCSX_ID = ?");
    $stmt_update_order->bind_param("ii", $cbhID, $ycsxID);
    $stmt_update_order->execute();
    $stmt_update_order->close();

    // Nếu mọi thứ thành công, xác nhận transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Tạo phiếu chuẩn bị hàng thành công!', 'cbhID' => $cbhID]);

} catch (Exception $e) {
    // Nếu có lỗi, hủy bỏ mọi thay đổi
    $conn->rollback();
    http_response_code(500);
    error_log("Lỗi Tạo CBH: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi server khi tạo phiếu: ' . $e->getMessage()]);
}

$conn->close();
?>
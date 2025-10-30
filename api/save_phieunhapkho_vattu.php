<?php
// File: api/save_phieunhapkho_vattu.php

// Giả sử file config của bạn nằm ở thư mục gốc /config/
require_once '../config/db_config.php'; 
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => ''];
$userId = $_SESSION['user_id'] ?? null;

// Kiểm tra phiên đăng nhập
if ($userId === null) {
    $response['message'] = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
    echo json_encode($response);
    exit;
}

// Kiểm tra dữ liệu đầu vào cơ bản
if (empty($input['items']) || empty($input['ngay_nhap']) || empty($input['nha_cung_cap_id'])) {
    $response['message'] = 'Vui lòng điền đầy đủ thông tin bắt buộc (Nhà cung cấp, Ngày nhập, và ít nhất một vật tư).';
    echo json_encode($response);
    exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    $pnk_id = $input['pnk_id'] ?? 0;
    $cbh_id = $input['cbh_id'] ?? null; // Lấy cbh_id từ payload để phân biệt
    $is_update = $pnk_id > 0;
    
    // Tính tổng tiền
    $total_amount = 0;
    foreach ($input['items'] as $item) {
        $total_amount += (floatval($item['quantity']) * floatval($item['price']));
    }

    $soPhieuNhapKho = '';

    if ($is_update) {
        // --- LOGIC CẬP NHẬT PHIẾU ---
        
        // Lấy số phiếu cũ để ghi log
        $stmt_old_so = $pdo->prepare("SELECT SoPhieuNhapKho FROM phieunhapkho WHERE PhieuNhapKhoID = ?");
        $stmt_old_so->execute([$pnk_id]);
        $soPhieuNhapKho = $stmt_old_so->fetchColumn();

        $sql = "UPDATE phieunhapkho SET NhaCungCapID=?, NgayNhap=?, SoHoaDon=?, NguoiGiaoHang=?, LyDoNhap=?, TongTien=?, CBH_ID=? WHERE PhieuNhapKhoID=?";
        $pdo->prepare($sql)->execute([
            $input['nha_cung_cap_id'], $input['ngay_nhap'], $input['so_hoa_don'],
            $input['nguoi_giao_hang'], $input['ly_do_nhap'], $total_amount, $cbh_id, $pnk_id
        ]);
        
        // Xóa chi tiết cũ và hoàn trả tồn kho (để đảm bảo tính đúng đắn)
        // Lấy chi tiết cũ để hoàn kho
        $stmt_old_items = $pdo->prepare("SELECT SanPhamID, SoLuong FROM chitietphieunhapkho WHERE PhieuNhapKhoID = ?");
        $stmt_old_items->execute([$pnk_id]);
        $old_items = $stmt_old_items->fetchAll(PDO::FETCH_ASSOC);

        foreach ($old_items as $old_item) {
            $sql_revert_inv = "UPDATE variant_inventory SET quantity = quantity - ? WHERE variant_id = ?";
            $pdo->prepare($sql_revert_inv)->execute([$old_item['SoLuong'], $old_item['SanPhamID']]);
            // (Optional) Có thể ghi log hoàn trả kho ở đây nếu cần
        }

        $pdo->prepare("DELETE FROM chitietphieunhapkho WHERE PhieuNhapKhoID = ?")->execute([$pnk_id]);
        
        $phieuNhapKhoID = $pnk_id;
    } else {
        // --- LOGIC TẠO MỚI PHIẾU ---
        $prefix = 'PNKVT-' . date('Ymd') . '-';
        $stmt_count = $pdo->prepare("SELECT COUNT(*) + 1 FROM phieunhapkho WHERE SoPhieuNhapKho LIKE ?");
        $stmt_count->execute([$prefix . '%']);
        $nextId = $stmt_count->fetchColumn();
        $soPhieuNhapKho = $prefix . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $sql = "INSERT INTO phieunhapkho (SoPhieuNhapKho, LoaiPhieu, NgayNhap, NhaCungCapID, NguoiGiaoHang, SoHoaDon, LyDoNhap, TongTien, CBH_ID, NguoiTaoID) VALUES (?, 'nhap_mua_hang', ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $soPhieuNhapKho, $input['ngay_nhap'], $input['nha_cung_cap_id'],
            $input['nguoi_giao_hang'], $input['so_hoa_don'], $input['ly_do_nhap'], $total_amount, $cbh_id, $userId
        ]);
        $phieuNhapKhoID = $pdo->lastInsertId();
    }

    // --- INSERT CHI TIẾT, CẬP NHẬT TỒN KHO VÀ GHI LOG ---
    $sql_ct = "INSERT INTO chitietphieunhapkho (PhieuNhapKhoID, SanPhamID, SoLuong, DonGiaNhap) VALUES (?, ?, ?, ?)";
    $stmt_ct = $pdo->prepare($sql_ct);

    $sql_inv = "UPDATE variant_inventory SET quantity = quantity + ? WHERE variant_id = ?";
    $stmt_inv = $pdo->prepare($sql_inv);

    $sql_log = "INSERT INTO lichsunhapxuat (SanPhamID, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu, DonGia) VALUES (?, 'NHAP_KHO_VATTU', ?, ?, ?, ?)";
    $stmt_log = $pdo->prepare($sql_log);

    foreach ($input['items'] as $item) {
        $quantity = floatval($item['quantity']);
        $price = floatval($item['price']);
        $variant_id = intval($item['variant_id']);

        if ($quantity > 0) {
            // Thêm chi tiết phiếu
            $stmt_ct->execute([$phieuNhapKhoID, $variant_id, $quantity, $price]);

            // Cập nhật tồn kho
            $stmt_inv->execute([$quantity, $variant_id]);
            
            // Lấy tồn kho sau giao dịch để ghi log
            $stmt_qty = $pdo->prepare("SELECT quantity FROM variant_inventory WHERE variant_id = ?");
            $stmt_qty->execute([$variant_id]);
            $quantity_after = $stmt_qty->fetchColumn();

            // Ghi log lịch sử
            $stmt_log->execute([$variant_id, $quantity, $quantity_after, $soPhieuNhapKho, $price]);
        }
    }

    // --- CẬP NHẬT TRẠNG THÁI PHIẾU CHUẨN BỊ HÀNG (NẾU CÓ CBH_ID) ---
    if ($cbh_id) {
        // Lấy tổng số lượng ECU cần cho CBH này
        $stmt_tong_can = $pdo->prepare("SELECT SUM(SoLuongEcu) FROM chitiet_ecu_cbh WHERE CBH_ID = ?");
        $stmt_tong_can->execute([$cbh_id]);
        $tong_can = $stmt_tong_can->fetchColumn();

        // Lấy tổng số lượng ECU đã nhập từ tất cả các phiếu nhập kho liên quan đến CBH này
        $sql_tong_nhap = "
            SELECT SUM(ct.SoLuong) 
            FROM chitietphieunhapkho ct
            JOIN phieunhapkho pnk ON ct.PhieuNhapKhoID = pnk.PhieuNhapKhoID
            WHERE pnk.CBH_ID = ? 
              AND ct.SanPhamID IN (
                  SELECT v.variant_id FROM variants v 
                  JOIN chitiet_ecu_cbh ce ON v.variant_name = ce.TenSanPhamEcu 
                  WHERE ce.CBH_ID = ?
              )
        ";
        $stmt_tong_nhap = $pdo->prepare($sql_tong_nhap);
        $stmt_tong_nhap->execute([$cbh_id, $cbh_id]);
        $tong_da_nhap = $stmt_tong_nhap->fetchColumn();

        // Xác định trạng thái mới
        $new_status = 'Đang nhập kho VT';
        if ($tong_da_nhap >= $tong_can) {
            $new_status = 'Đã nhập kho VT';
        }

        // Cập nhật trạng thái
        $sql_update_cbh = "UPDATE chuanbihang SET TrangThaiECU = ? WHERE CBH_ID = ?";
        $pdo->prepare($sql_update_cbh)->execute([$new_status, $cbh_id]);
    }

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = $is_update ? 'Cập nhật phiếu nhập thành công!' : 'Tạo phiếu nhập thành công!';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
    // Ghi log lỗi chi tiết cho admin
    error_log("Lỗi khi lưu phiếu nhập vật tư: " . $e->getMessage());
}

echo json_encode($response);
?>
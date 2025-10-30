<?php
/**
 * =================================================================================
 * API: LƯU PHIẾU NHẬP KHO BÁN THÀNH PHẨM (BTP) - VERSION 2.2
 * =================================================================================
 * - [FIX] Sửa lại tên cột và tên bảng cho khớp với cấu trúc cơ sở dữ liệu:
 * - `chitiet_pnk_btp`: Sửa cột `SoLuongTheoLenhSX` -> `so_luong_theo_lenh_sx`.
 * - `banthanhpham`: Sửa thành `variant_inventory` để cập nhật tồn kho.
 * - `lichsunhapxuat_btp`: Sửa thành `lichsunhapxuat` để ghi log.
 * - [FIX] Sửa lỗi tên bảng không chính xác (lenhsanxuat -> lenh_san_xuat).
 * =================================================================================
 */

require_once '../config/db_config.php';
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => ''];

$cbh_id = $input['cbh_id'] ?? 0;
$items = $input['items'] ?? [];
$userId = $_SESSION['user_id'] ?? 1; // Mặc định là 1 nếu không có session

if ($cbh_id === 0 || empty($items)) {
    $response['message'] = 'Dữ liệu đầu vào không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // 1. Lấy thông tin cần thiết từ Lệnh sản xuất liên quan đến CBH_ID
    $stmt_lsx_info = $pdo->prepare("
        SELECT lsx.LenhSX_ID, lsx.SoLenhSX 
        FROM lenh_san_xuat lsx
        JOIN chuanbihang cbh ON lsx.CBH_ID = cbh.CBH_ID
        WHERE cbh.CBH_ID = ? AND lsx.LoaiLSX = 'BTP'
        ORDER BY lsx.LenhSX_ID DESC LIMIT 1
    ");
    $stmt_lsx_info->execute([$cbh_id]);
    $lsx_info = $stmt_lsx_info->fetch(PDO::FETCH_ASSOC);

    if (!$lsx_info) {
        throw new Exception("Không tìm thấy Lệnh sản xuất BTP phù hợp.");
    }
    $lenhSX_ID = $lsx_info['LenhSX_ID'];
    $soLenhSX = $lsx_info['SoLenhSX'];

    // 2. Tạo số phiếu nhập kho BTP mới
    $prefix = 'PNKBTP-' . date('Ymd') . '-';
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM phieunhapkho_btp WHERE SoPhieuNhapKhoBTP LIKE ?");
    $stmt_count->execute([$prefix . '%']);
    $soPhieuNhapKhoBTP = $prefix . str_pad($stmt_count->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);

    // 3. Insert vào bảng phieunhapkho_btp
    $sql_pnk = "INSERT INTO phieunhapkho_btp (SoPhieuNhapKhoBTP, NgayNhap, LenhSX_ID, LyDoNhap, NguoiTaoID) VALUES (?, NOW(), ?, ?, ?)";
    $pdo->prepare($sql_pnk)->execute([$soPhieuNhapKhoBTP, $lenhSX_ID, 'Nhập kho BTP từ LSX ' . $soLenhSX, $userId]);
    $pnkBtpId = $pdo->lastInsertId();

    if (!$pnkBtpId) {
        throw new Exception("Không thể tạo phiếu nhập kho BTP.");
    }

    // 4. Chuẩn bị các câu lệnh SQL
    $stmt_get_lsx_qty = $pdo->prepare(
        "SELECT SoLuongCayCanSX FROM chitiet_lenh_san_xuat WHERE ChiTiet_LSX_ID = ?"
    );
    // [FIX] Sửa tên cột 'SoLuongTheoLenhSX' thành 'so_luong_theo_lenh_sx'
    $stmt_insert_detail = $pdo->prepare(
        "INSERT INTO chitiet_pnk_btp (PNK_BTP_ID, BTP_ID, so_luong_theo_lenh_sx, SoLuong, GhiChu) VALUES (?, ?, ?, ?, ?)"
    );
    // [FIX] Sửa logic cập nhật tồn kho để sử dụng bảng 'variant_inventory'
    $stmt_update_inventory = $pdo->prepare(
        "UPDATE variant_inventory SET quantity = quantity + ? WHERE variant_id = ?"
    );
    // [FIX] Sửa logic ghi log để sử dụng bảng 'lichsunhapxuat'
    $stmt_log = $pdo->prepare(
        "INSERT INTO lichsunhapxuat (SanPhamID, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu, GhiChu) 
         SELECT ?, 'NHAP_KHO_BTP', ?, quantity, ?, ? FROM variant_inventory WHERE variant_id = ?"
    );
    $stmt_update_lsx_detail = $pdo->prepare(
        "UPDATE chitiet_lenh_san_xuat SET SoLuongDaNhap = SoLuongDaNhap + ? WHERE ChiTiet_LSX_ID = ?"
    );

    // 5. Lặp qua các sản phẩm để xử lý
    foreach ($items as $item) {
        $soLuongThucNhap = intval($item['soLuongThucNhap']);
        if ($soLuongThucNhap > 0) {
            $btp_id = $item['btp_id']; // Đây chính là variant_id
            $chitiet_lsx_id = $item['chitiet_lsx_id'];

            // Lấy số lượng cần sản xuất từ chi tiết lệnh sản xuất
            $stmt_get_lsx_qty->execute([$chitiet_lsx_id]);
            $soLuongTheoLenhSX = $stmt_get_lsx_qty->fetchColumn();
            if ($soLuongTheoLenhSX === false) $soLuongTheoLenhSX = 0;

            // Insert chi tiết phiếu nhập
            $stmt_insert_detail->execute([$pnkBtpId, $btp_id, $soLuongTheoLenhSX, $soLuongThucNhap, $item['ghiChu']]);
            
            // Cập nhật tồn kho BTP
            $stmt_update_inventory->execute([$soLuongThucNhap, $btp_id]);
            
            // Ghi log lịch sử nhập xuất
            $log_message = 'Nhập kho BTP từ LSX ' . $soLenhSX;
            $stmt_log->execute([$btp_id, $soLuongThucNhap, $soPhieuNhapKhoBTP, $log_message, $btp_id]);

            // Cập nhật số lượng đã nhập trong chi tiết lệnh sản xuất
            $stmt_update_lsx_detail->execute([$soLuongThucNhap, $chitiet_lsx_id]);
        }
    }

    // 6. Cập nhật trạng thái của Phiếu Chuẩn Bị Hàng
    $pdo->prepare("UPDATE chuanbihang SET TrangThai = 'Đã nhập BTP' WHERE CBH_ID = ?")
        ->execute([$cbh_id]);

    // 7. Kiểm tra và cập nhật trạng thái Lệnh Sản Xuất nếu cần
    $stmt_check_lsx = $pdo->prepare("
        SELECT COUNT(*) 
        FROM chitiet_lenh_san_xuat 
        WHERE LenhSX_ID = ? AND SoLuongCayCanSX > SoLuongDaNhap
    ");
    $stmt_check_lsx->execute([$lenhSX_ID]);
    $remaining_items = $stmt_check_lsx->fetchColumn();

    if ($remaining_items == 0) {
        // Nếu tất cả đã nhập đủ, cập nhật trạng thái LSX
        $pdo->prepare("UPDATE lenh_san_xuat SET TrangThai = 'Hoàn thành', NgayHoanThanhThucTe = NOW() WHERE LenhSX_ID = ?")
            ->execute([$lenhSX_ID]);
    }

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = "Nhập kho BTP thành công! Số phiếu: " . $soPhieuNhapKhoBTP;
    $response['new_pnk_btp_id'] = $pnkBtpId;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    $response['message'] = 'Lỗi phía máy chủ: ' . $e->getMessage();
}

echo json_encode($response);
?>

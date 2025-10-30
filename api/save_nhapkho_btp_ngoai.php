<?php
// api/save_nhapkho_btp_ngoai.php
// CẬP NHẬT: Tính toán và lưu Tổng Tiền vào cột mới.
require_once '../config/db_config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

function send_error($message) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// ... (phần lấy dữ liệu và validation không đổi) ...
$nhaCungCapID = $data['nhaCungCapID'] ?? null;
$ngayNhap = $data['ngayNhap'] ?? null;
$nguoiGiaoHang = $data['nguoiGiaoHang'] ?? null;
$ghiChuChung = $data['ghiChuChung'] ?? null;
$items = $data['items'] ?? [];
$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;

if (empty($nhaCungCapID) || empty($ngayNhap) || empty($items) || empty($userId)) {
    send_error('Dữ liệu không hợp lệ hoặc phiên đăng nhập đã hết hạn.');
}

$pdo = get_db_connection();

try {
    $pdo->beginTransaction();

    $prefix = 'PNKBTP-M-' . date('Ymd', strtotime($ngayNhap)) . '-';
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM phieunhapkho_btp WHERE SoPhieuNhapKhoBTP LIKE ?");
    $stmt_count->execute([$prefix . '%']);
    $soPhieuNhap = $prefix . str_pad($stmt_count->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);

    $stmt_ncc = $pdo->prepare("SELECT TenNhaCungCap FROM nhacungcap WHERE NhaCungCapID = ?");
    $stmt_ncc->execute([$nhaCungCapID]);
    $tenNCC = $stmt_ncc->fetchColumn();
    $lyDoNhap = "Nhập kho BTP mua từ NCC: " . ($tenNCC ?: 'ID ' . $nhaCungCapID);

    $finalGhiChu = $ghiChuChung;
    if (!empty($nguoiGiaoHang)) {
        $finalGhiChu = "Người giao: " . htmlspecialchars($nguoiGiaoHang) . ". " . $ghiChuChung;
    }

    // =========================================================
    // === THAY ĐỔI 1: TÍNH TOÁN TỔNG TIỀN TRƯỚC KHI LƯU     ===
    // =========================================================
    $tongTien = 0;
    foreach ($items as $item) {
        $so_luong = intval($item['soLuong']);
        $don_gia = floatval($item['donGia']);
        if ($so_luong > 0) {
            $tongTien += $so_luong * $don_gia;
        }
    }
    // =========================================================

    // =========================================================
    // === THAY ĐỔI 2: THÊM `TongTien` VÀO CÂU LỆNH INSERT   ===
    // =========================================================
    $sql_pnk = "INSERT INTO phieunhapkho_btp (SoPhieuNhapKhoBTP, NgayNhap, NguoiTaoID, LyDoNhap, GhiChu, TongTien, CBH_ID, LenhSX_ID) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)";
    $pdo->prepare($sql_pnk)->execute([$soPhieuNhap, $ngayNhap, $userId, $lyDoNhap, $finalGhiChu, $tongTien]);
    // =========================================================
    
    $new_pnk_btp_id = $pdo->lastInsertId();

    // ... (Phần xử lý chi tiết và lịch sử không thay đổi) ...
    foreach ($items as $item) {
        $btp_id = intval($item['variant_id']);
        $so_luong_nhap = intval($item['soLuong']);
        $don_gia = floatval($item['donGia']);
        $ghi_chu_item = htmlspecialchars($item['ghiChu']);

        if ($so_luong_nhap <= 0) continue;

        $sql_detail = "INSERT INTO chitiet_pnk_btp (PNK_BTP_ID, BTP_ID, SoLuong, so_luong_theo_lenh_sx, GhiChu) VALUES (?, ?, ?, NULL, ?)";
        $pdo->prepare($sql_detail)->execute([$new_pnk_btp_id, $btp_id, $so_luong_nhap, $ghi_chu_item]);

        $sql_inv = "UPDATE variant_inventory SET quantity = quantity + ? WHERE variant_id = ?";
        $pdo->prepare($sql_inv)->execute([$so_luong_nhap, $btp_id]);
        
        $stmt_current_inv = $pdo->prepare("SELECT quantity FROM variant_inventory WHERE variant_id = ?");
        $stmt_current_inv->execute([$btp_id]);
        $ton_sau_gd = $stmt_current_inv->fetchColumn();
        
        $ghiChuLichSu = "Nhập BTP từ NCC: " . ($tenNCC ?: 'ID ' . $nhaCungCapID);
        $sql_log = "INSERT INTO lichsunhapxuat (SanPhamID, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu, GhiChu) VALUES (?, 'NHAP_KHO_MUA_BTP', ?, ?, ?, ?)";
        $pdo->prepare($sql_log)->execute([$btp_id, $so_luong_nhap, $ton_sau_gd, $soPhieuNhap, $ghiChuLichSu]);
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => "Đã tạo thành công Phiếu nhập kho BTP số: " . $soPhieuNhap,
        'new_pnk_btp_id' => $new_pnk_btp_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Lỗi khi lưu phiếu nhập kho BTP mua ngoài: " . $e->getMessage());
    send_error('Lỗi hệ thống: ' . $e->getMessage());
}
?>
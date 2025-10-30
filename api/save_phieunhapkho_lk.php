<?php
/**
 * API: LƯU PHIẾU NHẬP KHO TỪ LỆNH SẢN XUẤT (LK)
 * CẬP NHẬT: Thêm logic cập nhật cột `TrangThaiNhapKho` mới trong bảng `lenh_san_xuat`.
 */
require_once '../config/db_config.php';

// --- Helper functions ---
function send_json_success($data = null) {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => true];
    if ($data !== null) {
        // Gộp message vào data nếu có
        if (is_array($data) && isset($data['message'])) {
            $response['message'] = $data['message'];
            unset($data['message']);
        }
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}
function send_json_error($message, $http_code = 400) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($http_code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}
// --- End Helper functions ---

$data = json_decode(file_get_contents('php://input'), true);
$lsx_id = $data['lsx_id'] ?? 0;
$items = $data['items'] ?? [];
$user_id = $data['user_id'] ?? 1;

if (empty($lsx_id) || empty($items)) {
    send_json_error('Dữ liệu không hợp lệ.');
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // 1. Tạo số phiếu nhập kho mới
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM phieunhapkho WHERE NgayNhap = CURDATE() AND LoaiPhieu = 'nhap_lk_tu_sx'");
    $stmt_count->execute();
    $today_count = $stmt_count->fetchColumn();
    $so_phieu_nhap_kho = 'PNKLK-' . date('Ymd') . '-' . str_pad($today_count + 1, 3, '0', STR_PAD_LEFT);

    // 2. Tạo phiếu nhập kho
    $stmt_pnk = $pdo->prepare(
        "INSERT INTO phieunhapkho (SoPhieuNhapKho, LoaiPhieu, NgayNhap, LenhSX_ID, LyDoNhap, NguoiTaoID) 
         VALUES (?, 'nhap_lk_tu_sx', CURDATE(), ?, ?, ?)"
    );
    $stmt_pnk->execute([$so_phieu_nhap_kho, $lsx_id, 'Nhập kho từ LSX (LK)', $user_id]);
    $pnk_id = $pdo->lastInsertId();

    // 3. Xử lý chi tiết
    foreach ($items as $item) {
        $variant_id = $item['variant_id'];
        $so_luong_nhap = $item['soLuongThucNhap'];
        $ghi_chu = $item['ghiChu'];

        $stmt_ctpnk = $pdo->prepare("INSERT INTO chitietphieunhapkho (PhieuNhapKhoID, SanPhamID, SoLuong, GhiChu) VALUES (?, ?, ?, ?)");
        $stmt_ctpnk->execute([$pnk_id, $variant_id, $so_luong_nhap, $ghi_chu]);

        $stmt_update_clsx = $pdo->prepare("UPDATE chitiet_lenh_san_xuat SET SoLuongDaNhap = SoLuongDaNhap + ? WHERE SanPhamID = ? AND LenhSX_ID = ?");
        $stmt_update_clsx->execute([$so_luong_nhap, $variant_id, $lsx_id]);

        $stmt_update_inv = $pdo->prepare("INSERT INTO variant_inventory (variant_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
        $stmt_update_inv->execute([$variant_id, $so_luong_nhap]);
        
        $stmt_log = $pdo->prepare("INSERT INTO lichsunhapxuat (SanPhamID, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu) SELECT ?, 'NHAP_KHO', ?, (SELECT quantity FROM variant_inventory WHERE variant_id = ?), ?");
        $stmt_log->execute([$variant_id, $so_luong_nhap, $variant_id, $so_phieu_nhap_kho]);
    }

    // 4. Kiểm tra và cập nhật trạng thái Lệnh Sản Xuất
    $stmt_check_lsx = $pdo->prepare("SELECT SUM(SoLuongCayCanSX - SoLuongDaNhap) as remaining FROM chitiet_lenh_san_xuat WHERE LenhSX_ID = ?");
    $stmt_check_lsx->execute([$lsx_id]);
    $remaining_items = $stmt_check_lsx->fetchColumn();

    $new_status_nhapkho = ($remaining_items <= 0) ? 'Đã nhập đủ' : 'Đang nhập';
    
    $stmt_update_lsx_status = $pdo->prepare("UPDATE lenh_san_xuat SET TrangThaiNhapKho = ? WHERE LenhSX_ID = ?");
    $stmt_update_lsx_status->execute([$new_status_nhapkho, $lsx_id]);

    $pdo->commit();
    send_json_success(['pnk_id' => $pnk_id, 'message' => 'Lưu phiếu nhập kho thành công!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_json_error('Lỗi máy chủ: ' . $e->getMessage(), 500);
}
?>

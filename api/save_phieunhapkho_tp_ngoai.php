<?php
// File: api/save_phieunhapkho_tp_ngoai.php
require_once '../config/db_config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

function send_error($message) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$ngayNhap = $data['ngayNhap'] ?? null;
$nguoiGiao = $data['nguoiGiao'] ?? null;
$lyDoNhap = $data['lyDoNhap'] ?? null;
$items = $data['items'] ?? [];
$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;

if (empty($ngayNhap) || empty($lyDoNhap) || empty($items) || empty($userId)) {
    send_error('Dữ liệu không hợp lệ hoặc phiên đăng nhập đã hết hạn.');
}

$pdo = get_db_connection();

try {
    $pdo->beginTransaction();
    
    // Tạo số phiếu nhập mới
    $prefix = 'PNKTP-N-' . date('Ymd', strtotime($ngayNhap)) . '-'; // N là Ngoài
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM phieunhapkho WHERE SoPhieuNhapKho LIKE ?");
    $stmt_count->execute([$prefix . '%']);
    $soPhieuNhap = $prefix . str_pad($stmt_count->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);

    // Tạo phiếu nhập kho mới
    $sql_pnk = "INSERT INTO phieunhapkho (SoPhieuNhapKho, LoaiPhieu, NgayNhap, NguoiGiaoHang, LyDoNhap, NguoiTaoID, YCSX_ID, CBH_ID) VALUES (?, 'nhap_tp_khac', ?, ?, ?, ?, NULL, NULL)";
    $pdo->prepare($sql_pnk)->execute([$soPhieuNhap, $ngayNhap, $nguoiGiao, $lyDoNhap, $userId]);
    $phieuNhapKhoID = $pdo->lastInsertId();

    foreach ($items as $item) {
        $tp_id = intval($item['variant_id']);
        $so_luong_nhap = intval($item['soLuong']);

        // Thêm chi tiết phiếu
        $sql_detail = "INSERT INTO chitietphieunhapkho (PhieuNhapKhoID, SanPhamID, SoLuong, DonGiaNhap, ThanhTien) VALUES (?, ?, ?, 0, 0)";
        $pdo->prepare($sql_detail)->execute([$phieuNhapKhoID, $tp_id, $so_luong_nhap]);

        // Cộng tồn kho
        $sql_inv = "UPDATE variant_inventory SET quantity = quantity + ? WHERE variant_id = ?";
        $pdo->prepare($sql_inv)->execute([$so_luong_nhap, $tp_id]);
        
        // Ghi lịch sử
        $stmt_current_inv = $pdo->prepare("SELECT quantity FROM variant_inventory WHERE variant_id = ?");
        $stmt_current_inv->execute([$tp_id]);
        $ton_sau_gd = $stmt_current_inv->fetchColumn();
        
        $sql_log = "INSERT INTO lichsunhapxuat (SanPhamID, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu, GhiChu) VALUES (?, 'NHAP_KHO_TP_KHAC', ?, ?, ?, ?)";
        $pdo->prepare($sql_log)->execute([$tp_id, $so_luong_nhap, $ton_sau_gd, $soPhieuNhap, $lyDoNhap]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Tạo phiếu nhập kho thành phẩm thành công!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    send_error($e->getMessage());
}
?>
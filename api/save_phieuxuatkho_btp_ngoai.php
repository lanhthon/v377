<?php
// File: api/save_phieuxuatkho_btp_ngoai.php
require_once '../config/db_config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

function send_error($message) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$ngayXuat = $data['ngayXuat'] ?? null;
$nguoiNhan = $data['nguoiNhan'] ?? null;
$lyDoXuat = $data['lyDoXuat'] ?? null;
$items = $data['items'] ?? [];
$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;

if (empty($ngayXuat) || empty($nguoiNhan) || empty($items) || empty($userId)) {
    send_error('Dữ liệu không hợp lệ hoặc phiên đăng nhập đã hết hạn.');
}

$pdo = get_db_connection();

try {
    $pdo->beginTransaction();

    // Kiểm tra tồn kho trước khi thực hiện
    foreach ($items as $item) {
        $stmt_check = $pdo->prepare("SELECT quantity FROM variant_inventory WHERE variant_id = ?");
        $stmt_check->execute([$item['variant_id']]);
        $tonKho = $stmt_check->fetchColumn();
        if ($tonKho === false || $tonKho < $item['soLuong']) {
            throw new Exception("Không đủ tồn kho cho sản phẩm có ID: " . $item['variant_id'] . ". Tồn kho: " . ($tonKho ?: 0));
        }
    }
    
    // Tạo số phiếu xuất mới
    $prefix = 'PXKBTP-N-' . date('Ymd', strtotime($ngayXuat)) . '-'; // 'N' là Ngoài
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM phieuxuatkho WHERE SoPhieuXuat LIKE ?");
    $stmt_count->execute([$prefix . '%']);
    $soPhieuXuat = $prefix . str_pad($stmt_count->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);

    // Tạo phiếu xuất kho mới
    $sql_pxk = "INSERT INTO phieuxuatkho (SoPhieuXuat, LoaiPhieu, NgayXuat, NguoiNhan, GhiChu, NguoiTaoID, YCSX_ID, CBH_ID) VALUES (?, 'xuat_btp_khac', ?, ?, ?, ?, NULL, NULL)";
    $pdo->prepare($sql_pxk)->execute([$soPhieuXuat, $ngayXuat, $nguoiNhan, $lyDoXuat, $userId]);
    $phieuXuatKhoID = $pdo->lastInsertId();

    // Xử lý chi tiết và cập nhật kho
    foreach ($items as $item) {
        $btp_id = intval($item['variant_id']);
        $so_luong_xuat = intval($item['soLuong']);

        // Thêm chi tiết phiếu xuất
        $sql_detail = "INSERT INTO chitiet_phieuxuatkho (PhieuXuatKhoID, SanPhamID, SoLuongYeuCau, SoLuongThucXuat) VALUES (?, ?, ?, ?)";
        $pdo->prepare($sql_detail)->execute([$phieuXuatKhoID, $btp_id, $so_luong_xuat, $so_luong_xuat]);

        // Trừ tồn kho
        $sql_inv = "UPDATE variant_inventory SET quantity = quantity - ? WHERE variant_id = ?";
        $pdo->prepare($sql_inv)->execute([$so_luong_xuat, $btp_id]);
        
        // Ghi lịch sử
        $stmt_current_inv = $pdo->prepare("SELECT quantity FROM variant_inventory WHERE variant_id = ?");
        $stmt_current_inv->execute([$btp_id]);
        $ton_sau_gd = $stmt_current_inv->fetchColumn();
        
        $sql_log = "INSERT INTO lichsunhapxuat (SanPhamID, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu, GhiChu) VALUES (?, 'XUAT_KHO_BTP_KHAC', ?, ?, ?, ?)";
        $pdo->prepare($sql_log)->execute([$btp_id, -$so_luong_xuat, $ton_sau_gd, $soPhieuXuat, $lyDoXuat]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Tạo phiếu xuất kho thành công!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_error($e->getMessage());
}
?>
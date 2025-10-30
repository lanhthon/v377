<?php
// File: api/save_delivery_plan.php
require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $response['message'] = 'Dữ liệu đầu vào không hợp lệ.';
    echo json_encode($response);
    exit;
}

$donhang_id = $data['donhang_id'] ?? null;
$ngay_giao = $data['ngay_giao_du_kien'] ?? null;
$trang_thai = $data['trang_thai'] ?? 'Bản nháp';
$ghi_chu = $data['ghi_chu'] ?? null;
$items = $data['items'] ?? [];

if (empty($donhang_id) || empty($ngay_giao) || empty($items)) {
    $response['message'] = 'Thiếu thông tin cần thiết (ID đơn hàng, ngày giao, hoặc danh sách sản phẩm).';
    echo json_encode($response);
    exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // Tạo mã kế hoạch giao hàng mới, ví dụ: DH-YYYYMMDD-STT
    $date_prefix = date('Ymd');
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM kehoach_giaohang WHERE SoKeHoach LIKE ?");
    $stmt_count->execute(["DH-{$date_prefix}-%"]);
    $count = $stmt_count->fetchColumn() + 1;
    $so_ke_hoach = sprintf("DH-%s-%03d", $date_prefix, $count);

    // 1. Thêm vào bảng `kehoach_giaohang`
    $sql_plan = "INSERT INTO kehoach_giaohang (DonHangID, SoKeHoach, NgayGiaoDuKien, TrangThai, GhiChu, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt_plan = $pdo->prepare($sql_plan);
    $stmt_plan->execute([$donhang_id, $so_ke_hoach, $ngay_giao, $trang_thai, $ghi_chu]);
    
    $khgh_id = $pdo->lastInsertId();

    if (!$khgh_id) {
        throw new Exception("Không thể tạo bản ghi kế hoạch giao hàng chính.");
    }

    // 2. Thêm vào bảng `chitiet_kehoach_giaohang`
    $sql_item = "INSERT INTO chitiet_kehoach_giaohang (KHGH_ID, ChiTiet_DonHang_ID, SoLuongGiao) VALUES (?, ?, ?)";
    $stmt_item = $pdo->prepare($sql_item);

    foreach ($items as $item) {
        $stmt_item->execute([
            $khgh_id,
            $item['chitiet_donhang_id'],
            $item['so_luong_giao']
        ]);
    }

    // ----------------------------------------------------
    // PHẦN MÃ MỚI: CẬP NHẬT NGÀY GIAO HÀNG DỰ KIẾN TRONG BẢNG `donhang`
    // ----------------------------------------------------
    $sql_update_donhang = "UPDATE donhang SET NgayGiaoDuKien = ? WHERE YCSX_ID = ?";
    $stmt_update_donhang = $pdo->prepare($sql_update_donhang);
    $stmt_update_donhang->execute([$ngay_giao, $donhang_id]);
    // ----------------------------------------------------
    // KẾT THÚC PHẦN MÃ MỚI
    // ----------------------------------------------------
    
    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Kế hoạch giao hàng đã được lưu và đơn hàng đã được cập nhật thành công.';
    $response['khgh_id'] = $khgh_id;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Lỗi khi lưu kế hoạch: ' . $e->getMessage();
}

echo json_encode($response);
?>
<?php
// File: api/create_pxk_btp_for_cutting.php
// Version: 6.0 - Tích hợp modal xác nhận và xóa gán
// Mô tả: Xử lý tạo PXK BTP từ dữ liệu do người dùng xác nhận trên modal.
require_once '../config/db_config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// [FIX] Đọc dữ liệu từ JSON body thay vì $_POST
$input = json_decode(file_get_contents('php://input'), true);
$cbh_id = $input['cbh_id'] ?? 0;
$items_to_issue = $input['items'] ?? [];
$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
$response = ['success' => false, 'message' => ''];

if (empty($cbh_id)) {
    $response['message'] = 'Lỗi: ID Phiếu chuẩn bị hàng (cbh_id) không hợp lệ.';
    echo json_encode($response); exit;
}
if (empty($userId)) {
    $response['message'] = 'Lỗi: Không tìm thấy phiên đăng nhập (user_id).';
    echo json_encode($response); exit;
}
if (empty($items_to_issue)) {
    $response['message'] = 'Lỗi: Không có sản phẩm nào được chọn để xuất kho.';
    echo json_encode($response); exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT YCSX_ID FROM chuanbihang WHERE CBH_ID = ?");
    $stmt->execute([$cbh_id]);
    $ycsx_id = $stmt->fetchColumn();
    if (!$ycsx_id) {
        throw new Exception("Không tìm thấy phiếu chuẩn bị hàng với ID {$cbh_id}.");
    }

    $yearMonth = date('ym');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM phieuxuatkho WHERE SoPhieuXuat LIKE ?");
    $stmt->execute(["PXKBTP-{$yearMonth}-%"]);
    $count = $stmt->fetchColumn();
    $newCount = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    $soPhieuXuat = "PXKBTP-{$yearMonth}-{$newCount}";

    $ghiChu = "Xuất kho BTP (cây) để cắt cho Phiếu CBH ID: {$cbh_id}";
    $lyDoXuat = "Xuất bán thành phẩm để sản xuất thành phẩm theo YCSX.";
    $sql_insert_pxk = "INSERT INTO phieuxuatkho (YCSX_ID, CBH_ID, SoPhieuXuat, LoaiPhieu, NgayXuat, NguoiTaoID, GhiChu, LyDoXuatKho) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_pxk = $pdo->prepare($sql_insert_pxk);
    $stmt_pxk->execute([$ycsx_id, $cbh_id, $soPhieuXuat, 'xuat_btp_cat', date('Y-m-d'), $userId, $ghiChu, $lyDoXuat]);
    $pxk_id = $pdo->lastInsertId();
    if (!$pxk_id) {
        throw new Exception("Không thể tạo phiếu xuất kho mới.");
    }

    // Chuẩn bị các câu lệnh
    $stmt_get_item_info = $pdo->prepare("SELECT variant_sku, variant_name FROM variants WHERE variant_id = ?");
    $stmt_get_qty = $pdo->prepare("SELECT quantity FROM variant_inventory WHERE variant_id = ?");
    $stmt_get_alloc = $pdo->prepare("SELECT COALESCE(SUM(SoLuongPhanBo), 0) FROM donhang_phanbo_tonkho WHERE SanPhamID = ? AND CBH_ID != ?");
    $stmt_insert_detail = $pdo->prepare("INSERT INTO chitiet_phieuxuatkho (PhieuXuatKhoID, SanPhamID, MaHang, TenSanPham, SoLuongYeuCau, SoLuongThucXuat, DonViTinh) VALUES (?, ?, ?, ?, ?, ?, 'Cây')");
    $stmt_delete_alloc = $pdo->prepare("DELETE FROM donhang_phanbo_tonkho WHERE CBH_ID = ? AND SanPhamID = ?");
    $stmt_log = $pdo->prepare("INSERT INTO lichsunhapxuat (SanPhamID, NgayGiaoDich, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, DonGia, MaThamChieu, GhiChu) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $detail_rows_inserted = 0;
    foreach ($items_to_issue as $item) {
        $variant_id = (int)($item['variant_id'] ?? 0);
        $quantity_to_issue = (int)($item['so_luong_thuc_xuat'] ?? 0);
        $original_quantity = (int)($item['so_luong_yeu_cau'] ?? 0);

        if ($variant_id <= 0 || $quantity_to_issue < 0) continue;

        $stmt_get_item_info->execute([$variant_id]);
        $item_info = $stmt_get_item_info->fetch(PDO::FETCH_ASSOC);
        if (!$item_info) throw new Exception("Không tìm thấy BTP với ID {$variant_id}.");

        $stmt_get_qty->execute([$variant_id]);
        $physical_stock = (int)$stmt_get_qty->fetchColumn();

        $stmt_get_alloc->execute([$variant_id, $cbh_id]);
        $allocated_to_others = (int)$stmt_get_alloc->fetchColumn();

        $available_stock = $physical_stock - $allocated_to_others;

        if ($available_stock < $quantity_to_issue) {
            $thieu = $quantity_to_issue - $available_stock;
            throw new Exception("Không đủ BTP '{$item_info['variant_sku']}' để xuất. Bạn cần nhập thêm BTP (thiếu {$thieu} cây).");
        }

        if ($quantity_to_issue > 0) {
            $stmt_insert_detail->execute([
                $pxk_id, $variant_id, $item_info['variant_sku'], $item_info['variant_name'],
                $original_quantity, $quantity_to_issue
            ]);
            $detail_rows_inserted += $stmt_insert_detail->rowCount();

            // Cập nhật tồn kho và ghi log
            $tonKhoTruoc = $physical_stock;
            $pdo->prepare("UPDATE variant_inventory SET quantity = quantity - ? WHERE variant_id = ?")->execute([$quantity_to_issue, $variant_id]);
            $tonKhoSau = $tonKhoTruoc - $quantity_to_issue;

            $ghiChuLog = "Xuất cây BTP để cắt theo PXK {$soPhieuXuat} cho CBH ID {$cbh_id}";
            $stmt_log->execute([
                $variant_id, date('Y-m-d H:i:s'), 'XUAT_KHO',
                -abs($quantity_to_issue), $tonKhoSau, null, $soPhieuXuat, $ghiChuLog
            ]);
        }
        
        // Xóa gán cho phiếu CBH này sau khi đã xuất kho
        $stmt_delete_alloc->execute([$cbh_id, $variant_id]);
    }

    if ($detail_rows_inserted === 0 && count(array_filter($items_to_issue, fn($i) => ($i['so_luong_thuc_xuat'] ?? 0) > 0)) > 0) {
        throw new Exception("Không thể thêm chi tiết sản phẩm vào phiếu xuất.");
    }

    $sql_update_cbh = "UPDATE chuanbihang SET TrangThai = ?, TrangThaiXuatBTP = ? WHERE CBH_ID = ?";
    $stmt_update_cbh = $pdo->prepare($sql_update_cbh);
    $stmt_update_cbh->execute(['Đã xuất kho BTP', 'Đã xuất', $cbh_id]);

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = "Đã tạo thành công Phiếu xuất kho BTP số {$soPhieuXuat}.";
    $response['pxk_id'] = $pxk_id;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response['message'] = "Lỗi: " . $e->getMessage();
}

echo json_encode($response);
?>


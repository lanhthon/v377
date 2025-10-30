<?php
/**
 * File: api/create_progress_check_cbh.php
 * Description: API để tạo một Kế hoạch Giao hàng (KHGH) và Phiếu Chuẩn bị hàng (CBH) tạm thời
 * với trạng thái "Kiểm tra tiến độ". API này phục vụ cho chức năng ước tính ngày giao hàng.
 * Nó giả định giao 100% số lượng của đơn hàng.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

$donhang_id = isset($_POST['donhang_id']) ? intval($_POST['donhang_id']) : 0;

if ($donhang_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Đơn hàng không hợp lệ.']);
    exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // --- Bước 1: Lấy thông tin đơn hàng gốc ---
    $stmt_donhang = $pdo->prepare("SELECT * FROM donhang WHERE YCSX_ID = ?");
    $stmt_donhang->execute([$donhang_id]);
    $donhang = $stmt_donhang->fetch(PDO::FETCH_ASSOC);

    if (!$donhang) {
        throw new Exception("Không tìm thấy đơn hàng với ID: $donhang_id");
    }

    // --- Bước 2: Tạo một Kế hoạch Giao hàng mới với trạng thái "Kiểm tra tiến độ" ---
    $soKeHoach = $donhang['SoYCSX'] . '-KT'; // Suffix -KT để phân biệt đây là phiếu kiểm tra
    $ngayGiaoDuKien = $donhang['NgayGiaoDuKien'] ?? date('Y-m-d', strtotime('+7 days'));

    $stmt_khgh = $pdo->prepare("
        INSERT INTO kehoach_giaohang (DonHangID, SoKeHoach, NgayGiaoDuKien, TrangThai, GhiChu)
        VALUES (?, ?, ?, 'Kiểm tra tiến độ', 'Phiếu tạm thời để kiểm tra tiến độ')
    ");
    $stmt_khgh->execute([$donhang_id, $soKeHoach, $ngayGiaoDuKien]);
    $khgh_id = $pdo->lastInsertId();

    // --- Bước 3: Tạo Phiếu Chuẩn bị hàng tương ứng ---
    $stmt_cbh = $pdo->prepare("
        INSERT INTO chuanbihang (YCSX_ID, KHGH_ID, BaoGiaID, NgayTao, TenCongTy, SoDon, MaDon, TrangThai)
        VALUES (?, ?, ?, CURDATE(), ?, ?, ?, 'Kiểm tra tiến độ')
    ");
    $stmt_cbh->execute([
        $donhang['YCSX_ID'],
        $khgh_id,
        $donhang['BaoGiaID'],
        $donhang['TenCongTy'],
        $donhang['SoYCSX'],
        $donhang['YCSX_ID']
    ]);
    $cbh_id = $pdo->lastInsertId();
    
    // Cập nhật lại KHGH với CBH_ID vừa tạo
    $stmt_update_khgh = $pdo->prepare("UPDATE kehoach_giaohang SET CBH_ID = ? WHERE KHGH_ID = ?");
    $stmt_update_khgh->execute([$cbh_id, $khgh_id]);


    // --- Bước 4: Sao chép chi tiết từ đơn hàng sang chi tiết CBH và chi tiết KHGH ---
    $stmt_items = $pdo->prepare("SELECT * FROM chitiet_donhang WHERE DonHangID = ?");
    $stmt_items->execute([$donhang_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $stmt_insert_ct_cbh = $pdo->prepare("
        INSERT INTO chitietchuanbihang (CBH_ID, SanPhamID, MaHang, TenSanPham, SoLuong, ID_ThongSo, DoDay, BanRong, ThuTuHienThi)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt_insert_ct_khgh = $pdo->prepare("
        INSERT INTO chitiet_kehoach_giaohang (KHGH_ID, ChiTiet_DonHang_ID, SoLuongGiao)
        VALUES (?, ?, ?)
    ");

    foreach ($items as $item) {
        // Thêm vào chi tiết phiếu chuẩn bị hàng
        $stmt_insert_ct_cbh->execute([
            $cbh_id,
            $item['SanPhamID'],
            $item['MaHang'],
            $item['TenSanPham'],
            $item['SoLuong'], // Lấy 100% số lượng
            $item['ID_ThongSo'],
            $item['DoDay'],
            $item['BanRong'],
            $item['ThuTuHienThi']
        ]);
        // Thêm vào chi tiết kế hoạch giao hàng
        $stmt_insert_ct_khgh->execute([
            $khgh_id,
            $item['ChiTiet_YCSX_ID'],
            $item['SoLuong'] // Lấy 100% số lượng
        ]);
    }

    // --- Hoàn tất ---
    $pdo->commit();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Tạo phiếu kiểm tra tiến độ thành công.',
        'cbh_id' => $cbh_id,
        'khgh_id' => $khgh_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

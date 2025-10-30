<?php
/**
 * File: api/create_cbh_from_khgh.php
 * Version: 12.0 - Thêm xử lý SĐT người nhận.
 */
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Sử dụng file cấu hình PDO của bạn
require_once '../config/db_config.php';

// --- Lấy dữ liệu từ POST request ---
$khgh_id = isset($_POST['khgh_id']) ? intval($_POST['khgh_id']) : 0;
if ($khgh_id === 0) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID Kế hoạch giao hàng không hợp lệ.']); exit; }

$formData = [
    'bophan' => $_POST['bophan'] ?? 'Kho - Logistic',
    'ngaygui_ycsx' => $_POST['ngaygui_ycsx'] ?? date('Y-m-d'),
    'phutrach' => $_POST['phutrach'] ?? null,
    'ngaygiao' => $_POST['ngaygiao'] ?? null,
    'nguoinhanhang' => $_POST['nguoinhanhang'] ?? null,
    'sdtnguoinhan' => $_POST['sdtnguoinhan'] ?? null, // Thêm SĐT
    'diadiemgiaohang' => $_POST['diadiemgiaohang'] ?? null,
    'quycachthung' => $_POST['quycachthung'] ?? null,
    'xegrap' => $_POST['xegrap'] ?? null,
    'xetai' => $_POST['xetai'] ?? null,
    'solaixe' => $_POST['solaixe'] ?? null,
    'dangkicongtruong' => $_POST['dangkicongtruong'] ?? null
];

try {
    // Sử dụng PDO connection từ file config của bạn
    $pdo = get_db_connection();

    // Bắt đầu transaction của PDO
    $pdo->beginTransaction();

    // 1. Kiểm tra phiếu đã tồn tại chưa
    $stmt_check = $pdo->prepare("SELECT CBH_ID FROM chuanbihang WHERE KHGH_ID = :khgh_id");
    $stmt_check->execute([':khgh_id' => $khgh_id]);
    if ($stmt_check->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Phiếu CBH cho đợt giao này đã được tạo rồi.']);
        exit;
    }

    // 2. Lấy thông tin gốc từ Đơn hàng
    $stmt_data = $pdo->prepare("SELECT dh.* FROM kehoach_giaohang AS khgh JOIN donhang AS dh ON khgh.DonHangID = dh.YCSX_ID WHERE khgh.KHGH_ID = :khgh_id");
    $stmt_data->execute([':khgh_id' => $khgh_id]);
    $order_info = $stmt_data->fetch(PDO::FETCH_ASSOC);
    if (!$order_info) {
        throw new Exception("Không tìm thấy đơn hàng gốc.");
    }
    $donhang_id_goc = $order_info['YCSX_ID'];

    // 3. Logic tự động tạo Số Phiếu CBH
    $prefix = "CBH-" . date('ym') . "-";
    $stmt_last = $pdo->prepare("SELECT SoCBH FROM chuanbihang WHERE SoCBH LIKE :prefix ORDER BY SoCBH DESC LIMIT 1");
    $stmt_last->execute([':prefix' => $prefix . '%']);
    $last_socbh = $stmt_last->fetch(PDO::FETCH_ASSOC);
    $last_number = 0;
    if ($last_socbh) {
        $last_number = (int)substr($last_socbh['SoCBH'], strlen($prefix));
    }
    $new_number = $last_number + 1;
    $new_socbh = $prefix . str_pad($new_number, 3, '0', STR_PAD_LEFT);

    // 4. Tạo phiếu CBH mới với đầy đủ thông tin (sử dụng named placeholders)
    $sql_insert_cbh = "
        INSERT INTO chuanbihang (
            YCSX_ID, KHGH_ID, BaoGiaID, SoCBH, NgayTao, TenCongTy, SoDon, MaDon,
            BoPhan, NgayGuiYCSX, PhuTrach, NgayGiao, NguoiNhanHang, SdtNguoiNhan, DiaDiemGiaoHang,
            QuyCachThung, XeGrap, XeTai, SoLaiXe, DangKiCongTruong, TrangThai
        ) VALUES (
            :YCSX_ID, :KHGH_ID, :BaoGiaID, :SoCBH, CURDATE(), :TenCongTy, :SoDon, :MaDon,
            :BoPhan, :NgayGuiYCSX, :PhuTrach, :NgayGiao, :NguoiNhanHang, :SdtNguoiNhan, :DiaDiemGiaoHang,
            :QuyCachThung, :XeGrap, :XeTai, :SoLaiXe, :DangKiCongTruong, 'Chờ xử lý'
        )";
    $stmt_cbh = $pdo->prepare($sql_insert_cbh);
    
    // Thực thi câu lệnh và truyền dữ liệu qua một mảng -> An toàn và không bao giờ bị lỗi ArgumentCountError
    $stmt_cbh->execute([
        ':YCSX_ID' => $donhang_id_goc,
        ':KHGH_ID' => $khgh_id,
        ':BaoGiaID' => $order_info['BaoGiaID'],
        ':SoCBH' => $new_socbh,
        ':TenCongTy' => $order_info['TenCongTy'],
        ':SoDon' => $order_info['SoYCSX'],
        ':MaDon' => $donhang_id_goc,
        ':BoPhan' => $formData['bophan'],
        ':NgayGuiYCSX' => $formData['ngaygui_ycsx'],
        ':PhuTrach' => $formData['phutrach'],
        ':NgayGiao' => $formData['ngaygiao'],
        ':NguoiNhanHang' => $formData['nguoinhanhang'],
        ':SdtNguoiNhan' => $formData['sdtnguoinhan'], // Thêm SĐT
        ':DiaDiemGiaoHang' => $formData['diadiemgiaohang'],
        ':QuyCachThung' => $formData['quycachthung'],
        ':XeGrap' => $formData['xegrap'],
        ':XeTai' => $formData['xetai'],
        ':SoLaiXe' => $formData['solaixe'],
        ':DangKiCongTruong' => $formData['dangkicongtruong']
    ]);
    // Lấy ID vừa được chèn bằng lastInsertId()
    $cbh_id = $pdo->lastInsertId();
    if ($cbh_id == 0) {
        throw new Exception("Tạo phiếu CBH không thành công, không lấy được ID.");
    }
    
    // 5. Sao chép chi tiết sản phẩm từ đợt giao hàng vào chi tiết CBH
    $stmt_get_items = $pdo->prepare("
        SELECT ctdh.*, ctkhgh.SoLuongGiao 
        FROM chitiet_kehoach_giaohang AS ctkhgh
        JOIN chitiet_donhang AS ctdh ON ctkhgh.ChiTiet_DonHang_ID = ctdh.ChiTiet_YCSX_ID
        WHERE ctkhgh.KHGH_ID = :khgh_id AND ctkhgh.SoLuongGiao > 0
    ");
    $stmt_get_items->execute([':khgh_id' => $khgh_id]);
    $items_to_process = $stmt_get_items->fetchAll(PDO::FETCH_ASSOC);

    $sql_insert_item = "
        INSERT INTO chitietchuanbihang (
            CBH_ID, SanPhamID, MaHang, TenSanPham, SoLuong, ID_ThongSo, DoDay, BanRong, GhiChu, ThuTuHienThi
        ) VALUES (
            :CBH_ID, :SanPhamID, :MaHang, :TenSanPham, :SoLuong, :ID_ThongSo, :DoDay, :BanRong, :GhiChu, :ThuTuHienThi
        )";
    $stmt_insert_detail = $pdo->prepare($sql_insert_item);

    foreach ($items_to_process as $item) {
        $stmt_insert_detail->execute([
            ':CBH_ID' => $cbh_id,
            ':SanPhamID' => $item['SanPhamID'],
            ':MaHang' => $item['MaHang'],
            ':TenSanPham' => $item['TenSanPham'],
            ':SoLuong' => $item['SoLuongGiao'],
            ':ID_ThongSo' => $item['ID_ThongSo'],
            ':DoDay' => $item['DoDay'],
            ':BanRong' => $item['BanRong'],
            ':GhiChu' => $item['GhiChu'],
            ':ThuTuHienThi' => $item['ThuTuHienThi']
        ]);
    }
    
    // 6. Cập nhật trạng thái của Kế Hoạch Giao Hàng
    $newKHGHStatus = 'Đã tạo phiếu chuẩn bị hàng';
    $stmt_update_khgh = $pdo->prepare("UPDATE kehoach_giaohang SET TrangThai = :status, CBH_ID = :cbh_id WHERE KHGH_ID = :khgh_id");
    $stmt_update_khgh->execute([
        ':status' => $newKHGHStatus,
        ':cbh_id' => $cbh_id,
        ':khgh_id' => $khgh_id
    ]);

    // Commit transaction của PDO
    $pdo->commit();
   echo json_encode(['success' => true, 'message' => 'Tạo phiếu chuẩn bị hàng thành công!', 'donhang_id' => $donhang_id_goc, 'cbh_id' => $cbh_id]);

} catch (Exception $e) {
    // Nếu có lỗi, rollback transaction của PDO
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>

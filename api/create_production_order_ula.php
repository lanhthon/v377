<?php
/**
 * File: api/create_production_order.php
 * Version: 4.1 - Cập nhật trạng thái cho Kế hoạch Giao hàng.
 * Description: API tạo lệnh sản xuất cho BTP và ULA.
 * - [NEW] Tự động cập nhật trạng thái của kehoach_giaohang thành 'Chờ duyệt'.
 */

session_start();

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Sử dụng mysqli như file gốc

global $conn;

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập.']);
    exit;
}
$nguoiYeuCauID = $_SESSION['user_id'];

$ycsx_id = isset($_POST['ycsx_id']) ? intval($_POST['ycsx_id']) : 0;
$loaiLSX = isset($_POST['loai_lsx']) ? $_POST['loai_lsx'] : 'BTP'; 

if ($ycsx_id <= 0 || !in_array($loaiLSX, ['BTP', 'ULA'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ (ID YCSX hoặc loại LSX).']);
    exit;
}

$transactionStarted = false;

try {
    if ($conn->connect_error) {
        throw new Exception('Lỗi kết nối CSDL: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    $conn->begin_transaction();
    $transactionStarted = true;

    // --- Bước 1: Lấy thông tin đơn hàng và CBH_ID ---
    $stmt_check = $conn->prepare("
        SELECT dh.YCSX_ID, dh.SoYCSX, dh.NgayTao, cbh.CBH_ID
        FROM donhang dh
        JOIN chuanbihang cbh ON dh.YCSX_ID = cbh.YCSX_ID
        LEFT JOIN lenh_san_xuat lsx ON cbh.CBH_ID = lsx.CBH_ID AND lsx.LoaiLSX = ?
        WHERE dh.YCSX_ID = ? AND lsx.LenhSX_ID IS NULL
        LIMIT 1
    ");
    $stmt_check->bind_param("si", $loaiLSX, $ycsx_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $demand = $result->fetch_assoc();
    $stmt_check->close();

    if (!$demand) {
        throw new Exception('Đơn hàng không tồn tại, không có phiếu CBH, hoặc đã có lệnh sản xuất ' . $loaiLSX . ' cho phiếu này.');
    }
    $soYCSX = $demand['SoYCSX'];
    $cbh_id = $demand['CBH_ID'];

    // --- Bước 2: Lấy danh sách sản phẩm cần sản xuất ---
    // (Logic không đổi)
    $items_to_produce = [];
    $total_quantity = 0;
    if ($loaiLSX === 'BTP') {
        $sql_items = "SELECT v.variant_id AS SanPhamID, cbtp.MaBTP, cbtp.SoLuongCan AS SoLuong FROM chitiet_btp_cbh cbtp JOIN variants v ON cbtp.MaBTP = v.variant_sku WHERE cbtp.CBH_ID = ? AND cbtp.SoLuongCan > 0";
    } else { // ULA
        $sql_items = "SELECT ct.SanPhamID, ct.MaHang as MaBTP, ct.SoLuongCanSX as SoLuong FROM chitietchuanbihang ct WHERE ct.CBH_ID = ? AND ct.SoLuongCanSX > 0 AND ct.MaHang LIKE 'ULA%'";
    }
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $cbh_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    if ($result_items->num_rows === 0) { throw new Exception("Không tìm thấy sản phẩm nào cần sản xuất cho loại {$loaiLSX} trong phiếu CBH này."); }
    while ($item = $result_items->fetch_assoc()) {
        $items_to_produce[] = ['SanPhamID' => $item['SanPhamID'], 'SoLuongCan' => $item['SoLuong']];
        $total_quantity += $item['SoLuong'];
    }
    $stmt_items->close();

    // --- Bước 3: Lấy cấu hình năng suất và tính NgayHoanThanhUocTinh (LOGIC GỐC) ---
    // (Logic không đổi)
    $nangSuatKey = ($loaiLSX === 'BTP') ? 'NangSuatBTP' : 'NangSuatULA';
    $stmt_config = $conn->prepare("SELECT TenThietLap, GiaTriThietLap FROM cauhinh_sanxuat WHERE TenThietLap IN (?, 'GioLamViecMoiNgay', 'NgayNghiLe')");
    $stmt_config->bind_param("s", $nangSuatKey);
    $stmt_config->execute();
    $result_config = $stmt_config->get_result();
    $configs = [];
    while ($row = $result_config->fetch_assoc()) { $configs[$row['TenThietLap']] = $row['GiaTriThietLap']; }
    $stmt_config->close();
    $nangSuat = (float)($configs[$nangSuatKey] ?? 0);
    $gioLamViecMoiNgay = (float)($configs['GioLamViecMoiNgay'] ?? 8); 
    $ngayNghiLe = json_decode($configs['NgayNghiLe'] ?? '[]', true);
    if ($nangSuat <= 0 || $gioLamViecMoiNgay <= 0) { throw new Exception("Không tìm thấy cấu hình năng suất hoặc giờ làm việc cho loại {$loaiLSX}."); }
    $nangSuatTheoGio = $nangSuat / $gioLamViecMoiNgay;
    $total_hours_needed = ceil($total_quantity / $nangSuatTheoGio);
    $current_date = new DateTime("now", new DateTimeZone('Asia/Ho_Chi_Minh'));
    $hours_remaining = $total_hours_needed;
    while ($hours_remaining > 0) {
        $dayOfWeek = (int)$current_date->format('N');
        $dateString = $current_date->format('Y-m-d');
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && !in_array($dateString, $ngayNghiLe)) {
            $hours_remaining -= $gioLamViecMoiNgay;
        }
        if ($hours_remaining > 0) { $current_date->modify('+1 day'); }
    }
    $ngayHoanThanhUocTinh = $current_date->format('Y-m-d');

    // --- Bước 4: Tạo mã Lệnh sản xuất mới (LOGIC GỐC) ---
    // (Logic không đổi)
    $year = date('Y');
    $prefix = "LSX-{$year}-{$loaiLSX}-";
    $stmt_last_num = $conn->prepare("SELECT MAX(CAST(SUBSTRING(SoLenhSX, LENGTH(?) + 1) AS UNSIGNED)) AS last_num FROM lenh_san_xuat WHERE SoLenhSX LIKE ?");
    $like_prefix = $prefix . '%';
    $stmt_last_num->bind_param("ss", $prefix, $like_prefix);
    $stmt_last_num->execute();
    $last_num = $stmt_last_num->get_result()->fetch_assoc()['last_num'] ?? 0;
    $soLenhSX = $prefix . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    $stmt_last_num->close();

    // --- Bước 5: Chèn bản ghi chính vào `lenh_san_xuat` (ĐÃ SỬA) ---
    $stmt_insert_lsx = $conn->prepare("
        INSERT INTO lenh_san_xuat (YCSX_ID, CBH_ID, SoLenhSX, NgayTao, NgayHoanThanhUocTinh, TrangThai, NgayYCSX, LoaiLSX, NguoiYeuCau_ID) 
        VALUES (?, ?, ?, NOW(), ?, 'Chờ duyệt', ?, ?, ?)
    ");
    $stmt_insert_lsx->bind_param("iissssi", $ycsx_id, $cbh_id, $soLenhSX, $ngayHoanThanhUocTinh, $demand['NgayTao'], $loaiLSX, $nguoiYeuCauID);
    if (!$stmt_insert_lsx->execute()) { throw new Exception("Lỗi khi tạo lệnh sản xuất chính: " . $stmt_insert_lsx->error); }
    $lenhSxId = $stmt_insert_lsx->insert_id;
    $stmt_insert_lsx->close();

    // --- Bước 6: Chèn chi tiết sản phẩm (giữ nguyên logic gốc) ---
    $stmt_insert_ctlsx = $conn->prepare("INSERT INTO chitiet_lenh_san_xuat (LenhSX_ID, SanPhamID, SoLuongBoCanSX, SoLuongCayCanSX) VALUES (?, ?, ?, ?)");
    foreach ($items_to_produce as $item) {
        $sanPhamId = $item['SanPhamID'];
        $soLuongCan = $item['SoLuongCan'];
        $soLuongBo = ($loaiLSX === 'ULA') ? $soLuongCan : 0;
        $soLuongCay = ($loaiLSX === 'BTP') ? $soLuongCan : 0;
        $stmt_insert_ctlsx->bind_param("iidd", $lenhSxId, $sanPhamId, $soLuongBo, $soLuongCay);
        if (!$stmt_insert_ctlsx->execute()) { throw new Exception("Lỗi khi tạo chi tiết LSX cho sản phẩm ID {$sanPhamId}: " . $stmt_insert_ctlsx->error); }
    }
    $stmt_insert_ctlsx->close();
    
    // --- Bước 7: Cập nhật trạng thái của `chuanbihang` (ĐÃ SỬA) ---
    $newStatus = 'Chờ duyệt';
    if ($loaiLSX === 'BTP') {
        $stmt_update_cbh = $conn->prepare("UPDATE chuanbihang SET TrangThai = ? WHERE CBH_ID = ?");
    } else { // ULA
        $stmt_update_cbh = $conn->prepare("UPDATE chuanbihang SET TrangThaiULA = ? WHERE CBH_ID = ?");
    }
    $stmt_update_cbh->bind_param("si", $newStatus, $cbh_id);
    $stmt_update_cbh->execute();
    $stmt_update_cbh->close();

    // =========================================================================
    // [THÊM MỚI] --- Bước 7.5: Cập nhật trạng thái của `kehoach_giaohang` ---
    // =========================================================================
    // Lấy KHGH_ID từ CBH_ID
    $stmt_get_khgh = $conn->prepare("SELECT KHGH_ID FROM chuanbihang WHERE CBH_ID = ?");
    $stmt_get_khgh->bind_param("i", $cbh_id);
    $stmt_get_khgh->execute();
    $result_khgh = $stmt_get_khgh->get_result();
    if ($khgh_row = $result_khgh->fetch_assoc()) {
        $khgh_id = $khgh_row['KHGH_ID'];
        if (!empty($khgh_id)) {
            // Cập nhật trạng thái cho Kế hoạch giao hàng
            $stmt_update_khgh = $conn->prepare("UPDATE kehoach_giaohang SET TrangThai = ? WHERE KHGH_ID = ?");
            // Sử dụng cùng trạng thái $newStatus = 'Chờ duyệt';
            $stmt_update_khgh->bind_param("si", $newStatus, $khgh_id);
            $stmt_update_khgh->execute();
            $stmt_update_khgh->close();
        }
    }
    $stmt_get_khgh->close();

    // --- Bước 8: Hoàn tất ---
    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Đã tạo Lệnh sản xuất {$loaiLSX} '{$soLenhSX}' cho đơn hàng '{$soYCSX}' thành công."]);

} catch (Exception $e) {
    if ($transactionStarted && isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>
<?php
/**
 * File: api/create_production_order.php
 * Version: 3.3 (Fix Status Update Logic)
 * Description: API tạo lệnh sản xuất - Sửa logic cập nhật trạng thái CBH
 * - [CẬP NHẬT V3.3] Loại bỏ việc cập nhật trạng thái CBH thành "Chờ nhập" khi tạo LSX.
 *   Trạng thái này chỉ được cập nhật khi LSX hoàn thành.
 * - [CẬP NHẬT V3.2] Sửa tên cột `NguoiYeuCau` thành `NguoiYeuCau_ID`
 */

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

// --- Hàm trợ giúp ---

function getNangSuatTheoDuongKinh($duongKinh, $nangSuatConfig) {
    $duongKinhSo = intval(preg_replace('/[^0-9]/', '', $duongKinh));
    if ($duongKinhSo <= 0) return 0;

    foreach ($nangSuatConfig as $range => $nangSuat) {
        list($min, $max) = explode('-', $range);
        if ($duongKinhSo >= (int)$min && $duongKinhSo <= (int)$max) {
            return (float)$nangSuat;
        }
    }
    return 0;
}

function generate_production_order_number(PDO $pdo, string $type): string {
    $prefix = ($type === 'BTP') ? 'LSX-BTP-' : 'LSX-ULA-';
    $date_prefix = date('ymd');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lenh_san_xuat WHERE SoLenhSX LIKE ?");
    $stmt->execute(["{$prefix}{$date_prefix}%"]);
    $count = $stmt->fetchColumn() + 1;
    return $prefix . $date_prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
}

// --- Xử lý chính ---

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập.']);
    exit;
}
$nguoiYeuCauID = $_SESSION['user_id'];

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu gửi lên không hợp lệ.']);
    exit;
}

$cbh_id = isset($data['cbh_id']) ? intval($data['cbh_id']) : 0;
$loai_lsx = isset($data['loai_lsx']) ? $data['loai_lsx'] : '';
$items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

if ($cbh_id === 0 || empty($loai_lsx) || !in_array($loai_lsx, ['BTP', 'ULA'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin CBH ID hoặc Loại LSX không hợp lệ.']);
    exit;
}

if (empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Không có sản phẩm nào được chọn để tạo lệnh sản xuất.']);
    exit;
}

$pdo = null;
try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    $stmt_info = $pdo->prepare("
        SELECT dh.YCSX_ID, dh.NgayTao AS NgayYCSX
        FROM chuanbihang cbh
        JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
        WHERE cbh.CBH_ID = ?
    ");
    $stmt_info->execute([$cbh_id]);
    $order_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$order_info) {
        throw new Exception("Không tìm thấy thông tin đơn hàng gốc.");
    }
    $ycsx_id = $order_info['YCSX_ID'];
    $ngayYCSX = $order_info['NgayYCSX'];

    $items_for_calculation = [];
    $stmt_get_variant_id = $pdo->prepare("SELECT variant_id FROM variants WHERE variant_sku = ? LIMIT 1");
    foreach ($items as $item) {
        $ma_hang = $item['maHang'] ?? '';
        $so_luong = $item['soLuong'] ?? 0;
        if (empty($ma_hang) || $so_luong <= 0) continue;

        $stmt_get_variant_id->execute([$ma_hang]);
        $variant = $stmt_get_variant_id->fetch(PDO::FETCH_ASSOC);

        if (!$variant) {
            throw new Exception("Không tìm thấy sản phẩm với mã '{$ma_hang}'.");
        }
        $items_for_calculation[] = ['SanPhamID' => $variant['variant_id'], 'MaBTP' => $ma_hang, 'SoLuongCan' => $so_luong];
    }
    if (empty($items_for_calculation)) {
        throw new Exception("Không có sản phẩm hợp lệ nào để tạo lệnh sản xuất.");
    }

    $stmt_config = $pdo->prepare("SELECT TenThietLap, GiaTriThietLap FROM cauhinh_sanxuat WHERE TenThietLap IN ('NangSuatMaGoiPU', 'NangSuatUla', 'GioLamViecMoiNgay', 'NgayNghiLe', 'SoChuyenSongSong_BTP', 'SoChuyenSongSong_ULA')");
    $stmt_config->execute();
    $configs_raw = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $nangSuatPuConfig = json_decode($configs_raw['NangSuatMaGoiPU'] ?? '{}', true);
    $nangSuatUlaConfig = json_decode($configs_raw['NangSuatUla'] ?? '{}', true);
    $gioLamViecMoiNgay = (float)($configs_raw['GioLamViecMoiNgay'] ?? 8);
    $ngayNghiLe = json_decode($configs_raw['NgayNghiLe'] ?? '[]', true);
    $soChuyenSongSong = ($loai_lsx === 'BTP') ? (int)($configs_raw['SoChuyenSongSong_BTP'] ?? 1) : (int)($configs_raw['SoChuyenSongSong_ULA'] ?? 1);
    $soChuyenSongSong = max(1, $soChuyenSongSong);

    if ($gioLamViecMoiNgay <= 0 || ($loai_lsx === 'BTP' && empty($nangSuatPuConfig)) || ($loai_lsx === 'ULA' && empty($nangSuatUlaConfig))) {
        throw new Exception("Cấu hình năng suất hoặc giờ làm việc không hợp lệ.");
    }

    $stmt_attr = $pdo->prepare("SELECT ao.value FROM variant_attributes va JOIN attribute_options ao ON va.option_id = ao.option_id JOIN attributes a ON ao.attribute_id = a.attribute_id WHERE va.variant_id = ? AND a.name = ? LIMIT 1");
    $item_hours_list = [];

    foreach ($items_for_calculation as $item) {
        $nangSuatTheoNgay = 0;
        $attributeName = ($loai_lsx === 'BTP') ? 'Đường kính trong' : 'Kích thước ren';
        $stmt_attr->execute([$item['SanPhamID'], $attributeName]);
        $attr_row = $stmt_attr->fetch(PDO::FETCH_ASSOC);

        if (!$attr_row || empty($attr_row['value'])) {
            throw new Exception("Không tìm thấy thuộc tính '{$attributeName}' cho sản phẩm {$item['MaBTP']}.");
        }
        $attr_value = $attr_row['value'];

        if ($loai_lsx === 'BTP') {
            $nangSuatTheoNgay = getNangSuatTheoDuongKinh($attr_value, $nangSuatPuConfig);
        } else {
            $nangSuatTheoNgay = (float)($nangSuatUlaConfig[$attr_value] ?? 0);
        }

        if ($nangSuatTheoNgay <= 0) {
            throw new Exception("Năng suất không hợp lệ cho sản phẩm {$item['MaBTP']} với thuộc tính '{$attributeName}' giá trị '{$attr_value}'.");
        }
        $item_hours_list[] = (float)$item['SoLuongCan'] / ($nangSuatTheoNgay / $gioLamViecMoiNgay);
    }

    $chuyenSanXuat = array_fill(0, $soChuyenSongSong, 0);
    rsort($item_hours_list);

    foreach ($item_hours_list as $hours) {
        $chuyenItViecNhat = array_keys($chuyenSanXuat, min($chuyenSanXuat))[0];
        $chuyenSanXuat[$chuyenItViecNhat] += $hours;
    }
    $total_hours_needed = ceil(max($chuyenSanXuat));

    $current_date = new DateTime("now", new DateTimeZone('Asia/Ho_Chi_Minh'));
    $hours_remaining = $total_hours_needed;
    while ($hours_remaining > 0) {
        if ((int)$current_date->format('N') <= 6 && !in_array($current_date->format('Y-m-d'), $ngayNghiLe)) {
            $hours_remaining -= $gioLamViecMoiNgay;
        }
        if ($hours_remaining > 0) $current_date->modify('+1 day');
    }
    $ngayHoanThanhUocTinh = $current_date->format('Y-m-d');
    
    $so_lenh_sx = generate_production_order_number($pdo, $loai_lsx);
    
    // Tạo lệnh sản xuất với trạng thái "Chờ duyệt"
    $sql_lsx = "INSERT INTO lenh_san_xuat (YCSX_ID, CBH_ID, SoLenhSX, NgayTao, NgayHoanThanhUocTinh, TrangThai, NgayYCSX, LoaiLSX, NguoiYeuCau_ID) VALUES (?, ?, ?, NOW(), ?, 'Chờ duyệt', ?, ?, ?)";
    $pdo->prepare($sql_lsx)->execute([$ycsx_id, $cbh_id, $so_lenh_sx, $ngayHoanThanhUocTinh, $ngayYCSX, $loai_lsx, $nguoiYeuCauID]);
    $lsx_id = $pdo->lastInsertId();
    
    // Thêm chi tiết lệnh sản xuất
    $sql_detail = "INSERT INTO chitiet_lenh_san_xuat (LenhSX_ID, SanPhamID, SoLuongBoCanSX, SoLuongCayCanSX, TrangThai) VALUES (?, ?, ?, ?, 'Mới')";
    $stmt_detail = $pdo->prepare($sql_detail);
    foreach ($items_for_calculation as $item) {
        $soLuongBo = ($loai_lsx === 'ULA') ? $item['SoLuongCan'] : 0;
        $soLuongCay = ($loai_lsx === 'BTP') ? $item['SoLuongCan'] : 0;
        $stmt_detail->execute([$lsx_id, $item['SanPhamID'], $soLuongBo, $soLuongCay]);
    }
    
    // ✅ SỬA: CẬP NHẬT TRẠNG THÁI CHỜ DUYỆT THAY VÌ CHỜ NHẬP
    if ($loai_lsx === 'BTP') {
        $pdo->prepare("UPDATE chuanbihang SET TrangThaiPUR = 'Chờ duyệt' WHERE CBH_ID = ?")->execute([$cbh_id]);
    } elseif ($loai_lsx === 'ULA') {
        $pdo->prepare("UPDATE chuanbihang SET TrangThaiULA = 'Chờ duyệt' WHERE CBH_ID = ?")->execute([$cbh_id]);
    }

    // Cập nhật ngày hoàn thành dự kiến của đơn hàng
    $stmt_get_dates = $pdo->prepare("SELECT NgayHoanThanhUocTinh FROM lenh_san_xuat WHERE YCSX_ID = ?");
    $stmt_get_dates->execute([$ycsx_id]);
    $all_dates = $stmt_get_dates->fetchAll(PDO::FETCH_COLUMN);
    $latest_date_str = $ngayHoanThanhUocTinh;
    if($all_dates){
        foreach($all_dates as $date) {
            if ($date > $latest_date_str) $latest_date_str = $date;
        }
    }
    $pdo->prepare("UPDATE donhang SET NgayHoanThanhDuKien = ? WHERE YCSX_ID = ?")->execute([$latest_date_str, $ycsx_id]);
    
    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => "Đã tạo Lệnh sản xuất {$so_lenh_sx} thành công! Vui lòng chờ bộ phận sản xuất duyệt và thực hiện.", 
        'lsx_id' => $lsx_id
    ]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Lỗi khi tạo LSX (CBH ID: {$cbh_id}): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
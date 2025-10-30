<?php
/**
 * File: api/calculate_production_schedule.php
 * Version: 2.0 (Cập nhật logic tính toán chi tiết)
 * Description: API tính toán ngày hoàn thành sản xuất và ngày giao hàng dự kiến
 * dựa trên cấu hình chi tiết từ bảng `cauhinh_sanxuat`.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

// --- Hàm trợ giúp ---

/**
 * Tìm năng suất cho BTP (CV/CT) dựa trên đường kính từ cấu hình JSON.
 * @param string $duongKinh Ví dụ: "DN25"
 * @param array $nangSuatConfig Mảng cấu hình năng suất
 * @return float Năng suất (sản phẩm/ngày)
 */
function getNangSuatTheoDuongKinh($duongKinh, $nangSuatConfig) {
    $duongKinhSo = intval(preg_replace('/[^0-9]/', '', $duongKinh));
    if ($duongKinhSo <= 0) return 0;

    foreach ($nangSuatConfig as $range => $nangSuat) {
        list($min, $max) = explode('-', $range);
        if ($duongKinhSo >= (int)$min && $duongKinhSo <= (int)$max) {
            return (float)$nangSuat;
        }
    }
    return 0; // Trả về 0 nếu không tìm thấy
}

// --- Bắt đầu xử lý ---

$cbh_id = isset($_POST['cbh_id']) ? intval($_POST['cbh_id']) : 0;

if ($cbh_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Phiếu chuẩn bị hàng không hợp lệ.']);
    exit;
}

try {
    $pdo = get_db_connection();

    // --- Bước 1: Lấy tất cả cấu hình sản xuất cần thiết ---
    $stmt_config = $pdo->prepare("SELECT TenThietLap, GiaTriThietLap FROM cauhinh_sanxuat");
    $stmt_config->execute();
    $configs_raw = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

    $nangSuatPuConfig = json_decode($configs_raw['NangSuatMaGoiPU'] ?? '{}', true);
    $nangSuatUlaConfig = json_decode($configs_raw['NangSuatUla'] ?? '{}', true);
    $gioLamViecMoiNgay = (float)($configs_raw['GioLamViecMoiNgay'] ?? 8);
    $ngayNghiLe = json_decode($configs_raw['NgayNghiLe'] ?? '[]', true);
    $soChuyenBtp = (int)($configs_raw['SoChuyenSongSong_BTP'] ?? 1);
    $soChuyenUla = (int)($configs_raw['SoChuyenSongSong_ULA'] ?? 1);
    
    if ($gioLamViecMoiNgay <= 0) throw new Exception("Cấu hình 'Giờ làm việc mỗi ngày' không hợp lệ.");
    if (empty($nangSuatPuConfig)) throw new Exception("Thiếu cấu hình 'NangSuatMaGoiPU'.");
    if (empty($nangSuatUlaConfig)) throw new Exception("Thiếu cấu hình 'NangSuatUla'.");

    // --- Bước 2: Lấy danh sách sản phẩm cần sản xuất và tính tổng giờ ---
    $details = [];
    $warnings = [];
    
    // Hàm tính toán thời gian cho một loại sản phẩm (BTP hoặc ULA)
    function calculate_production_hours($pdo, $cbh_id, $loaiLSX, $configs) {
        $nangSuatPuConfig = $configs['nangSuatPuConfig'];
        $nangSuatUlaConfig = $configs['nangSuatUlaConfig'];
        $gioLamViecMoiNgay = $configs['gioLamViecMoiNgay'];
        
        $items_to_produce = [];
        if ($loaiLSX === 'BTP') {
            $sql_items = "SELECT v.variant_id AS SanPhamID, cbtp.MaBTP, cbtp.SoLuongCan AS SoLuong FROM chitiet_btp_cbh cbtp JOIN variants v ON cbtp.MaBTP = v.variant_sku WHERE cbtp.CBH_ID = ? AND cbtp.SoLuongCan > 0";
        } else { // ULA
            $sql_items = "SELECT ct.SanPhamID, ct.MaHang as MaBTP, ct.SoLuongCanSX as SoLuong FROM chitietchuanbihang ct WHERE ct.CBH_ID = ? AND ct.SoLuongCanSX > 0 AND ct.MaHang LIKE 'ULA%'";
        }
        
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute([$cbh_id]);
        $items_to_produce = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items_to_produce)) {
            return ['hours' => 0, 'total_needed' => 0];
        }

        $total_needed = array_sum(array_column($items_to_produce, 'SoLuong'));
        $item_hours_list = [];
        
        $sql_attr = "SELECT ao.value FROM variant_attributes va JOIN attribute_options ao ON va.option_id = ao.option_id JOIN attributes a ON ao.attribute_id = a.attribute_id WHERE va.variant_id = ? AND a.name = ? LIMIT 1";
        $stmt_attr = $pdo->prepare($sql_attr);

        foreach ($items_to_produce as $item) {
            $nangSuatTheoNgay = 0;
            $attributeName = ($loaiLSX === 'BTP') ? 'Đường kính trong' : 'Kích thước ren';
            
            $stmt_attr->execute([$item['SanPhamID'], $attributeName]);
            $attr_value = $stmt_attr->fetchColumn();
            
            if (!$attr_value) {
                throw new Exception("Không tìm thấy thuộc tính '{$attributeName}' cho sản phẩm {$item['MaBTP']}.");
            }

            if ($loaiLSX === 'BTP') {
                $nangSuatTheoNgay = getNangSuatTheoDuongKinh($attr_value, $nangSuatPuConfig);
            } else {
                $nangSuatTheoNgay = (float)($nangSuatUlaConfig[$attr_value] ?? 0);
            }

            if ($nangSuatTheoNgay <= 0) {
                throw new Exception("Năng suất không hợp lệ cho sản phẩm {$item['MaBTP']} với thuộc tính '{$attr_value}'.");
            }

            $nangSuatTheoGio = $nangSuatTheoNgay / $gioLamViecMoiNgay;
            $item_hours_list[] = (float)$item['SoLuong'] / $nangSuatTheoGio;
        }
        return ['hours_list' => $item_hours_list, 'total_needed' => $total_needed];
    }

    // Tính toán cho ULA
    $ula_result = calculate_production_hours($pdo, $cbh_id, 'ULA', ['nangSuatUlaConfig' => $nangSuatUlaConfig, 'gioLamViecMoiNgay' => $gioLamViecMoiNgay]);
    $total_ula_needed = $ula_result['total_needed'];
    $ula_hours_list = $ula_result['hours_list'] ?? [];
    
    // Tính toán cho BTP
    $btp_result = calculate_production_hours($pdo, $cbh_id, 'BTP', ['nangSuatPuConfig' => $nangSuatPuConfig, 'gioLamViecMoiNgay' => $gioLamViecMoiNgay]);
    $total_btp_needed = $btp_result['total_needed'];
    $btp_hours_list = $btp_result['hours_list'] ?? [];
    
    // --- Bước 3: Phân bổ giờ sản xuất vào các chuyền song song ---
    function distribute_hours($hours_list, $num_lines) {
        if (empty($hours_list)) return 0;
        $lines = array_fill(0, $num_lines, 0);
        rsort($hours_list); // Sắp xếp công việc từ lớn đến nhỏ
        foreach ($hours_list as $hours) {
            $lightest_line = array_keys($lines, min($lines))[0];
            $lines[$lightest_line] += $hours;
        }
        return max($lines); // Thời gian hoàn thành là thời gian của chuyền bận nhất
    }

    $total_ula_hours = distribute_hours($ula_hours_list, $soChuyenUla);
    $total_btp_hours = distribute_hours($btp_hours_list, $soChuyenBtp);

    // Thời gian sản xuất tổng cộng là thời gian dài nhất giữa 2 loại
    $total_production_hours = max($total_ula_hours, $total_btp_hours);
    $total_production_days = ceil($total_production_hours / $gioLamViecMoiNgay);

    if ($total_ula_needed > 0) {
        $details[] = "Tổng số lượng ULA cần sản xuất: <strong>{$total_ula_needed} bộ</strong>. Ước tính <strong>" . round($total_ula_hours) . " giờ</strong> sản xuất trên {$soChuyenUla} chuyền.";
    } else {
        $details[] = "Thành phẩm ULA đã có đủ trong kho.";
    }
    if ($total_btp_needed > 0) {
        $details[] = "Tổng số lượng BTP cần sản xuất: <strong>{$total_btp_needed} cây</strong>. Ước tính <strong>" . round($total_btp_hours) . " giờ</strong> sản xuất trên {$soChuyenBtp} chuyền.";
    } else {
        $details[] = "Bán thành phẩm (BTP) đã có đủ trong kho.";
    }
     $details[] = "Tổng thời gian sản xuất thực tế (lấy max): <strong>{$total_production_days} ngày</strong>.";

    // --- Bước 4: Thêm ngày đệm và tính ngày giao hàng cuối cùng ---
    $material_buffer_days = 3;
    $packaging_buffer_days = 1;
    $details[] = "Thời gian chờ vật tư (dự phòng): <strong>{$material_buffer_days} ngày</strong>.";
    $details[] = "Thời gian đóng gói & chuẩn bị giao: <strong>{$packaging_buffer_days} ngày</strong>.";
    
    $total_days_needed = $total_production_days + $material_buffer_days + $packaging_buffer_days;

    $estimated_date = new DateTime();
    $days_added = 0;
    while ($days_added < $total_days_needed) {
        $estimated_date->modify('+1 day');
        $day_of_week = (int)$estimated_date->format('N');
        $date_string = $estimated_date->format('Y-m-d');
        if ($day_of_week <= 6 && !in_array($date_string, $ngayNghiLe)) { // Giả sử làm việc cả T7
            $days_added++;
        }
    }
    
    $warnings[] = "Tiến độ này chưa tính đến các ngày nghỉ lễ (nếu có).";
    $warnings[] = "Phụ thuộc vào việc vật tư về đúng hẹn (nếu có đặt hàng).";

    // --- Hoàn tất, trả về kết quả ---
    $response_data = [
        'estimatedDeliveryDate' => $estimated_date->format('Y-m-d'),
        'details' => $details,
        'warnings' => $warnings,
        'debug' => [
            'ula_needed' => $total_ula_needed,
            'btp_needed' => $total_btp_needed,
            'total_prod_days' => $total_production_days,
            'total_days_needed' => $total_days_needed
        ]
    ];
    
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $response_data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

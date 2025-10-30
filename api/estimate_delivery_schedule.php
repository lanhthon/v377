<?php
/**
 * File: api/estimate_delivery_schedule.php
 * Version: 3.1 (Tối ưu hóa quy trình)
 * Description: API chuyên dụng để ước tính tiến độ giao hàng.
 * - Sửa đổi: Chỉ tạo một Phiếu Chuẩn Bị Hàng duy nhất cho mỗi đơn hàng để kiểm tra tiến độ.
 * - Nếu phiếu đã tồn tại, nó sẽ được sử dụng lại và cập nhật với dữ liệu mới nhất.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

// --- Bắt đầu lớp xử lý chính ---

class DeliveryEstimator {
    private PDO $pdo;
    private array $configs;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Hàm chính để thực thi toàn bộ quy trình ước tính
     */
    public function estimate(int $donhang_id): array {
        $this->pdo->beginTransaction();
        try {
            // Bước 1: Lấy cấu hình sản xuất
            $this->loadProductionConfigs();

            // Bước 2: Tìm hoặc tạo Phiếu Chuẩn Bị Hàng duy nhất cho việc kiểm tra
            $ids = $this->findOrCreateProgressCheckCbh($donhang_id);
            $cbh_id = $ids['cbh_id'];
            $khgh_id = $ids['khgh_id']; // KHGH này sẽ bị ẩn trên UI

            // Bước 3: Xử lý kiểm tra tồn kho
            $this->processInventoryForEstimation($cbh_id);

            // Bước 4: Tính toán lịch trình sản xuất
            $schedule_data = $this->calculateProductionSchedule($cbh_id);

            $this->pdo->commit();

            return [
                'success' => true,
                'khgh_id' => $khgh_id,
                'data' => $schedule_data
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Tải tất cả cấu hình cần thiết từ bảng cauhinh_sanxuat
     */
    private function loadProductionConfigs(): void {
        $stmt_config = $this->pdo->prepare("SELECT TenThietLap, GiaTriThietLap FROM cauhinh_sanxuat");
        $stmt_config->execute();
        $configs_raw = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->configs = [
            'nangSuatPuConfig' => json_decode($configs_raw['NangSuatMaGoiPU'] ?? '{}', true),
            'nangSuatUlaConfig' => json_decode($configs_raw['NangSuatUla'] ?? '{}', true),
            'gioLamViecMoiNgay' => (float)($configs_raw['GioLamViecMoiNgay'] ?? 8),
            'ngayNghiLe' => json_decode($configs_raw['NgayNghiLe'] ?? '[]', true),
            'soChuyenBtp' => (int)($configs_raw['SoChuyenSongSong_BTP'] ?? 1),
            'soChuyenUla' => (int)($configs_raw['SoChuyenSongSong_ULA'] ?? 1)
        ];

        if ($this->configs['gioLamViecMoiNgay'] <= 0) throw new Exception("Cấu hình 'Giờ làm việc mỗi ngày' không hợp lệ.");
    }

    /**
     * Tìm CBH có trạng thái "Kiểm tra tiến độ". Nếu không có, tạo một CBH mới.
     */
    private function findOrCreateProgressCheckCbh(int $donhang_id): array {
        // Khóa đơn hàng để tránh deadlock
        $stmt_lock = $this->pdo->prepare("SELECT YCSX_ID FROM donhang WHERE YCSX_ID = ? FOR UPDATE");
        $stmt_lock->execute([$donhang_id]);
        if (!$stmt_lock->fetch()) {
            throw new Exception("Không tìm thấy đơn hàng ID: $donhang_id");
        }

        // Tìm CBH có trạng thái đặc biệt
        $stmt_find = $this->pdo->prepare("
            SELECT CBH_ID, KHGH_ID FROM chuanbihang 
            WHERE YCSX_ID = ? AND TrangThai = 'Kiểm tra tiến độ' 
            LIMIT 1
        ");
        $stmt_find->execute([$donhang_id]);
        $existing = $stmt_find->fetch(PDO::FETCH_ASSOC);

        if ($existing && !empty($existing['CBH_ID'])) {
            return ['cbh_id' => $existing['CBH_ID'], 'khgh_id' => $existing['KHGH_ID']];
        }

        // Nếu không có, tạo mới CBH và một KHGH ẩn
        $stmt_donhang = $this->pdo->prepare("SELECT * FROM donhang WHERE YCSX_ID = ?");
        $stmt_donhang->execute([$donhang_id]);
        $donhang = $stmt_donhang->fetch(PDO::FETCH_ASSOC);

        // Tạo một KHGH "ẩn" để liên kết
        $stmt_khgh = $this->pdo->prepare("INSERT INTO kehoach_giaohang (DonHangID, TrangThai, GhiChu) VALUES (?, 'Kiểm tra tiến độ', 'Bản ghi kỹ thuật cho kiểm tra tiến độ')");
        $stmt_khgh->execute([$donhang_id]);
        $khgh_id = $this->pdo->lastInsertId();

        // Tạo CBH với trạng thái "Kiểm tra tiến độ"
        $stmt_cbh = $this->pdo->prepare("INSERT INTO chuanbihang (YCSX_ID, KHGH_ID, BaoGiaID, NgayTao, TenCongTy, SoDon, MaDon, TrangThai) VALUES (?, ?, ?, CURDATE(), ?, ?, ?, 'Kiểm tra tiến độ')");
        $stmt_cbh->execute([$donhang['YCSX_ID'], $khgh_id, $donhang['BaoGiaID'], $donhang['TenCongTy'], $donhang['SoYCSX'], $donhang['YCSX_ID']]);
        $cbh_id = $this->pdo->lastInsertId();
        
        $this->pdo->prepare("UPDATE kehoach_giaohang SET CBH_ID = ? WHERE KHGH_ID = ?")->execute([$cbh_id, $khgh_id]);

        $stmt_items = $this->pdo->prepare("SELECT * FROM chitiet_donhang WHERE DonHangID = ?");
        $stmt_items->execute([$donhang_id]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        $stmt_insert_ct_cbh = $this->pdo->prepare("INSERT INTO chitietchuanbihang (CBH_ID, SanPhamID, MaHang, TenSanPham, SoLuong) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmt_insert_ct_cbh->execute([$cbh_id, $item['SanPhamID'], $item['MaHang'], $item['TenSanPham'], $item['SoLuong']]);
        }
        
        return ['cbh_id' => $cbh_id, 'khgh_id' => $khgh_id];
    }

    private function processInventoryForEstimation(int $cbh_id): void {
        $cbhProcessorPath = __DIR__ . '/includes/CbhProcessor.php';
        if (!file_exists($cbhProcessorPath)) {
            throw new Exception("Lỗi hệ thống: Tệp '{$cbhProcessorPath}' không tồn tại.");
        }
        require_once $cbhProcessorPath;
        
        $processor = new CbhProcessor($this->pdo);
        $result = $processor->process($cbh_id, false); 

        if (!$result['success']) {
            $errorMessage = $result['message'];
            if (!empty($result['errors'])) {
                $errorMessage .= ": " . implode(", ", $result['errors']);
            }
            throw new Exception($errorMessage);
        }
    }

    private function calculateProductionSchedule(int $cbh_id): array {
        $details = [];
        
        $calculate_hours = function($loaiLSX) use ($cbh_id) {
            $items_to_produce = [];
            if ($loaiLSX === 'BTP') {
                $sql_items = "SELECT v.variant_id AS SanPhamID, cbtp.MaBTP, cbtp.SoLuongCan AS SoLuong FROM chitiet_btp_cbh cbtp JOIN variants v ON cbtp.MaBTP = v.variant_sku WHERE cbtp.CBH_ID = ? AND cbtp.SoLuongCan > 0";
            } else { // ULA
                $sql_items = "SELECT ct.SanPhamID, ct.MaHang as MaBTP, ct.SoLuongCanSX as SoLuong FROM chitietchuanbihang ct WHERE ct.CBH_ID = ? AND ct.SoLuongCanSX > 0 AND ct.MaHang LIKE 'ULA%'";
            }
            
            $stmt_items = $this->pdo->prepare($sql_items);
            $stmt_items->execute([$cbh_id]);
            $items_to_produce = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($items_to_produce)) return ['hours_list' => [], 'items_list' => []];
            
            $item_hours_list = [];
            
            $sql_attr = "SELECT ao.value FROM variant_attributes va JOIN attribute_options ao ON va.option_id = ao.option_id JOIN attributes a ON ao.attribute_id = a.attribute_id WHERE va.variant_id = ? AND a.name = ? LIMIT 1";
            $stmt_attr = $this->pdo->prepare($sql_attr);

            foreach ($items_to_produce as $item) {
                $nangSuatTheoNgay = 0;
                $attributeName = ($loaiLSX === 'BTP') ? 'Đường kính trong' : 'Kích thước ren';
                
                $stmt_attr->execute([$item['SanPhamID'], $attributeName]);
                $attr_value = $stmt_attr->fetchColumn();
                if (!$attr_value) throw new Exception("Thiếu thuộc tính '{$attributeName}' cho SP {$item['MaBTP']}.");

                if ($loaiLSX === 'BTP') {
                    $nangSuatTheoNgay = $this->getNangSuatTheoDuongKinh($attr_value, $this->configs['nangSuatPuConfig']);
                } else {
                    $nangSuatTheoNgay = (float)($this->configs['nangSuatUlaConfig'][$attr_value] ?? 0);
                }

                if ($nangSuatTheoNgay <= 0) throw new Exception("Năng suất không hợp lệ cho SP {$item['MaBTP']}.");

                $nangSuatTheoGio = $nangSuatTheoNgay / $this->configs['gioLamViecMoiNgay'];
                $item_hours_list[] = (float)$item['SoLuong'] / $nangSuatTheoGio;
            }
            return ['hours_list' => $item_hours_list, 'items_list' => $items_to_produce];
        };

        $distribute_hours = function($hours_list, $num_lines) {
            if (empty($hours_list)) return 0;
            $lines = array_fill(0, $num_lines, 0);
            rsort($hours_list);
            foreach ($hours_list as $hours) {
                $lightest_line = array_keys($lines, min($lines))[0];
                $lines[$lightest_line] += $hours;
            }
            return max($lines);
        };

        $ula_result = $calculate_hours('ULA');
        $btp_result = $calculate_hours('BTP');
        
        $total_ula_hours = $distribute_hours($ula_result['hours_list'], $this->configs['soChuyenUla']);
        $total_btp_hours = $distribute_hours($btp_result['hours_list'], $this->configs['soChuyenBtp']);

        $total_ula_needed = array_sum(array_column($ula_result['items_list'], 'SoLuong'));
        if ($total_ula_needed > 0) {
            $details[] = "Thành phẩm ULA cần SX: <strong>{$total_ula_needed} bộ</strong>. Ước tính <strong>" . round($total_ula_hours) . " giờ</strong> sản xuất.";
        } else {
            $details[] = "Thành phẩm ULA: <strong>Tồn kho đủ</strong> (đã tính lượng đã gán).";
        }

        $total_btp_needed = array_sum(array_column($btp_result['items_list'], 'SoLuong'));
        if ($total_btp_needed > 0) {
            $details[] = "Bán thành phẩm (BTP) cần SX: <strong>{$total_btp_needed} cây</strong>. Ước tính <strong>" . round($total_btp_hours) . " giờ</strong> sản xuất.";
            $btp_details_list = "<ul style='margin-top: 5px; padding-left: 20px;'>";
            foreach($btp_result['items_list'] as $btp_item) {
                $soLuongFormatted = rtrim(rtrim(sprintf('%.2f', $btp_item['SoLuong']), '0'), '.');
                $btp_details_list .= "<li>&nbsp;&nbsp;&nbsp;- {$btp_item['MaBTP']}: {$soLuongFormatted} cây</li>";
            }
            $btp_details_list .= "</ul>";
            $details[] = $btp_details_list;
        } else {
            $details[] = "Bán thành phẩm (BTP): <strong>Tồn kho đủ</strong> (đã tính lượng đã gán).";
        }

        $total_production_hours = max($total_ula_hours, $total_btp_hours);
        $total_production_days = ceil($total_production_hours / $this->configs['gioLamViecMoiNgay']);
        
        if ($total_production_days > 0) {
            $details[] = "Tổng thời gian sản xuất thực tế: <strong>{$total_production_days} ngày</strong>.";
        }

        $material_buffer_days = 3;
        $packaging_buffer_days = 1;
        $details[] = "Thời gian chờ vật tư (dự phòng): <strong>{$material_buffer_days} ngày</strong>.";
        $details[] = "Thời gian đóng gói & chuẩn bị giao: <strong>{$packaging_buffer_days} ngày</strong>.";
        
        $total_days_needed = $total_production_days + $material_buffer_days + $packaging_buffer_days;

        $estimated_date = new DateTime();
        $days_added = 0;
        $skipped_days_info = [];
        $checked_dates = []; 

        while ($days_added < $total_days_needed) {
            $estimated_date->modify('+1 day');
            $day_of_week = (int)$estimated_date->format('N');
            $date_string = $estimated_date->format('Y-m-d');

            $is_holiday = in_array($date_string, $this->configs['ngayNghiLe']);
            $is_sunday = ($day_of_week == 7);

            if ($is_holiday || $is_sunday) {
                if (!in_array($date_string, $checked_dates)) {
                    $reason = $is_holiday ? "Ngày nghỉ lễ" : "Chủ Nhật";
                    $skipped_days_info[] = "{$reason} (" . $estimated_date->format('d/m/Y') . ")";
                    $checked_dates[] = $date_string;
                }
                continue; 
            }
            $days_added++;
        }
        
        $warnings = [];
        if (!empty($skipped_days_info)) {
            $warnings[] = "Lưu ý: Lịch trình đã bỏ qua các ngày nghỉ sau: " . implode(', ', $skipped_days_info) . ".";
        }
        $warnings[] = "Tiến độ phụ thuộc vào việc vật tư về đúng hẹn (nếu có đặt hàng).";
        
        return [
            'estimatedDeliveryDate' => $estimated_date->format('Y-m-d'),
            'details' => $details,
            'warnings' => $warnings
        ];
    }
    
    private function getNangSuatTheoDuongKinh($duongKinh, $nangSuatConfig) {
        $duongKinhSo = intval(preg_replace('/[^0-9]/', '', $duongKinh));
        if ($duongKinhSo <= 0) return 0;
        foreach ($nangSuatConfig as $range => $nangSuat) {
            list($min, $max) = explode('-', $range);
            if ($duongKinhSo >= (int)$min && $duongKinhSo <= (int)$max) return (float)$nangSuat;
        }
        return 0;
    }
}

// --- Main Execution ---
$donhang_id = isset($_POST['donhang_id']) ? intval($_POST['donhang_id']) : 0;
if ($donhang_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Đơn hàng không hợp lệ.']);
    exit;
}

try {
    $pdo = get_db_connection();
    
    $estimator = new DeliveryEstimator($pdo);
    $result = $estimator->estimate($donhang_id);
    
    http_response_code(200);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

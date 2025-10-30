<?php
/**
 * File: api/process_cbh_details_CBH.php
 * Version: 12.2 (Fixed ULA/DaiTreo status logic)
 * Description: API xử lý, tính toán chi tiết và cập nhật trạng thái cho phiếu CBH.
 * 
 * [CẬP NHẬT V12.2] 
 * - Sửa lỗi logic TrangThaiNhapTP_ULA bị ghi đè khi xử lý Đai Treo.
 * - Logic đúng: Chỉ set "Chờ nhập" khi ULA hoặc Đai Treo cần sản xuất.
 * 
 * [CẬP NHẬT V12.1] 
 * - Hoàn lại logic tìm kiếm BTP về phiên bản cũ (v11) để sửa lỗi không tìm thấy mã BTP.
 * 
 * [CẬP NHẬT V12.0] 
 * - Logic xác định trạng thái (status) cho từng nhóm sản phẩm (PUR, ULA, ECU, DaiTreo).
 * - Kiểm tra cả tồn kho vật lý và tồn kho đã gán.
 * - Xử lý trường hợp không có sản phẩm trong một nhóm.
 * - Cập nhật tất cả các cột trạng thái liên quan trong bảng `chuanbihang`.
 * - Cấu trúc lại class để rõ ràng và dễ bảo trì hơn.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

$cbh_id = isset($_POST['cbh_id']) ? intval($_POST['cbh_id']) : 0;
if ($cbh_id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Phiếu chuẩn bị hàng không hợp lệ.']);
    exit;
}

try {
    $pdo = get_db_connection();
    $processor = new CbhProcessor($pdo);
    $result = $processor->process($cbh_id);
    
    http_response_code(200);
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Lỗi nghiêm trọng khi xử lý CBH ID $cbh_id: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi hệ thống.', 'errors' => [$e->getMessage()]]);
}

class CbhProcessor {
    private PDO $pdo;
    private array $allocations = [];
    private array $btp_requirements = [];
    private array $ecu_requirements = [];
    private array $errors = [];
    
    // Biến cờ cho trạng thái
    private bool $hasPurItems = false;
    private bool $needsCutting = false;
    private bool $needsBtpProduction = false;
    
    private bool $hasUlaItems = false;
    private bool $needsUlaProduction = false;
    
    private bool $hasDaiTreoItems = false;
    private bool $needsDaiTreoProduction = false;

    private bool $hasEcuItems = false;
    private bool $needsEcuImport = false;


    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function process(int $cbh_id): array {
        $this->pdo->beginTransaction();

        try {
            $order_info = $this->getOrderInfo($cbh_id);
            if (!$order_info) {
                throw new Exception("Không tìm thấy thông tin đơn hàng cho CBH ID: $cbh_id.");
            }
            $donhang_id = $order_info['YCSX_ID'];
            $baoGiaId = $order_info['BaoGiaID'];

            $this->clearOldData($cbh_id);

            $items_to_process = $this->getCbhItems($cbh_id);
            $item_updates = [];

            foreach ($items_to_process as $item) {
                $this->processSingleItem($item, $donhang_id, $baoGiaId, $cbh_id);
                $item_updates[] = $this->prepareItemUpdateData($item);
            }

            if (!empty($this->errors)) {
                $this->pdo->rollBack();
                http_response_code(400);
                return ['success' => false, 'message' => 'Lỗi khi xử lý, vui lòng kiểm tra.', 'errors' => $this->errors];
            }

            $this->commitData($cbh_id, $donhang_id, $item_updates);
            
            // Cập nhật trạng thái cuối cùng
            $this->updateFinalStatuses($cbh_id);

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Đã xử lý và cập nhật thông tin thành công!'];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function processSingleItem(array &$item, int $donhang_id, ?int $baoGiaId, int $cbh_id): void {
        $quote_details = $this->getQuoteDetails($baoGiaId, $item['MaHang']);
        
        $item['TenNhom'] = $quote_details['TenNhom'] ?? 'N/A';
        $item['ID_ThongSo'] = $quote_details['ID_ThongSo'] ?? '';
        $item['DoDay'] = $quote_details['DoDay'] ?? ($item['DoDayAttr'] ?? '');
        $item['BanRong'] = $quote_details['ChieuRong'] ?? ($item['BanRong'] ?? '');

        $soLuongYeuCau = (int)$item['SoLuong'];
        $daGanChoDonKhac = $this->getAllocatedQuantity($item['SanPhamID'], $cbh_id);
        $tonKhoVatLy = (int)($item['TonKhoVatLy'] ?? 0);
        $tonKhoKhaDung = max(0, $tonKhoVatLy - $daGanChoDonKhac);
        
        $item['SoLuongLayTuKho'] = min($soLuongYeuCau, $tonKhoKhaDung);
        $item['SoLuongCanSX'] = $soLuongYeuCau - $item['SoLuongLayTuKho'];
        $item['TonKho'] = $tonKhoVatLy;
        $item['DaGan'] = $daGanChoDonKhac;

        if ($item['SoLuongLayTuKho'] > 0) {
            $this->allocations[$item['SanPhamID']] = ($this->allocations[$item['SanPhamID']] ?? 0) + $item['SoLuongLayTuKho'];
        }

        $maHangUpper = strtoupper(trim($item['MaHang'] ?? ''));
        
        if (str_starts_with($maHangUpper, 'PUR')) {
            $this->hasPurItems = true;
            $this->handlePurProduct($item, $donhang_id, $cbh_id);
        } elseif (str_starts_with($maHangUpper, 'ULA')) {
            $this->hasUlaItems = true;
            $this->handleUlaProduct($item, $donhang_id, $cbh_id);
        } elseif (str_starts_with($maHangUpper, 'DT')) {
            $this->hasDaiTreoItems = true;
            if ($item['SoLuongCanSX'] > 0) $this->needsDaiTreoProduction = true;
        }
    }

    private function handlePurProduct(array &$item, int $donhang_id, int $cbh_id): void {
        if ($item['SoLuongCanSX'] > 0) {
            $this->needsCutting = true;
        }

        $maHangUpper = strtoupper($item['MaHang'] ?? '');
        $hinhDangThucTe = str_contains($maHangUpper, 'PUR-C') ? 'Tròn' : 'Vuông';
        $duongKinh = (int)($item['DuongKinhTrong'] ?? 0);
        $banRong = (int)($item['BanRong'] ?? 0);
        
        if ($item['SoLuongCanSX'] <= 0) {
             // Khởi tạo giá trị để tránh lỗi
            $item['SoCayPhaiCat'] = '0';
            $item['CanSanXuatCay'] = 0;
            $item['TonKhoCV'] = 0; $item['DaGanCV'] = 0;
            $item['TonKhoCT'] = 0; $item['DaGanCT'] = 0;
            return;
        }


        $stmt = $this->pdo->prepare("SELECT SoBoTrenCay FROM dinh_muc_cat WHERE HinhDang = ? AND ? BETWEEN MinDN AND MaxDN AND BanRong = ? LIMIT 1");
        $stmt->execute([$hinhDangThucTe, $duongKinh, $banRong]);
        $dinhMucCatValue = $stmt->fetchColumn();

        if (!$dinhMucCatValue || $dinhMucCatValue <= 0) {
            $this->errors[] = "Sản phẩm `{$item['MaHang']}`: Không tìm thấy định mức cắt cho Hình dạng='{$hinhDangThucTe}', DN='{$duongKinh}', BR='{$banRong}'.";
            return;
        }

        $soLuongCanCat = $item['SoLuongCanSX'];
        $soCayCanCat = ceil($soLuongCanCat / $dinhMucCatValue);

        $item['SoCayPhaiCat'] = (string)$soCayCanCat;
        $item['CanSanXuatCay'] = $soCayCanCat; // Giá trị ban đầu, sẽ được điều chỉnh

        $required_btp_configs = [];
        if (str_contains($maHangUpper, 'PUR-S')) {
            $required_btp_configs = [
                ['prefix' => 'CV', 'quantity' => $soCayCanCat],
                ['prefix' => 'CT', 'quantity' => $soCayCanCat]
            ];
        } elseif (str_contains($maHangUpper, 'PUR-C')) {
            $required_btp_configs = [
                ['prefix' => 'CT', 'quantity' => $soCayCanCat * 2]
            ];
            $item['SoCayPhaiCat'] = (string)($soCayCanCat * 2);
        }

        foreach ($required_btp_configs as $btp_config) {
            $btp_component_info = $this->processBtpComponent($item, $btp_config['prefix'], $btp_config['quantity'], $cbh_id);
            if ($btp_component_info) {
                 if ($btp_component_info['SoLuongCan'] > 0) {
                    $this->needsBtpProduction = true;
                }
                if ($btp_config['prefix'] === 'CV') {
                    $item['TonKhoCV'] = $btp_component_info['TonKhoSnapshot'];
                    $item['DaGanCV'] = $btp_component_info['DaGanSnapshot'];
                } elseif ($btp_config['prefix'] === 'CT') {
                    $item['TonKhoCT'] = $btp_component_info['TonKhoSnapshot'];
                    $item['DaGanCT'] = $btp_component_info['DaGanSnapshot'];
                }
            }
        }
    }

    private function processBtpComponent(array $item, string $btp_prefix, int $tongSoCayCan, int $cbh_id): ?array {
        $btp_parts = $this->parsePurSku($item['MaHang']);
        if (!$btp_parts) {
            $this->errors[] = "Không thể phân tích mã hàng BTP cho `{$item['MaHang']}`.";
            return null;
        }

        $found_btp = $this->findBtpVariant($btp_prefix, $btp_parts['dimensions'], $btp_parts['suffix']);

        if (!$found_btp) {
            $skus_tried = $this->generateTriedSkusForError($btp_prefix, $btp_parts['dimensions'], $btp_parts['suffix']);
            $this->errors[] = "Không tìm thấy BTP '{$btp_prefix}' cho `{$item['MaHang']}`. Đã thử: " . implode(', ', $skus_tried);
            return null;
        }
        
        $btp_info_db = $found_btp['info'];
        $final_btp_sku = $found_btp['found_sku'];
        $btpVariantId = $btp_info_db['variant_id'];

        $tonKhoCay = (int)($btp_info_db['quantity'] ?? 0);
        $daGanCay = $this->getAllocatedQuantity($btpVariantId, $cbh_id);
        $tonKhoKhaDungBTP = max(0, $tonKhoCay - $daGanCay);

        $soCayLayTuKhoBTP = min($tongSoCayCan, $tonKhoKhaDungBTP);
        $canSanXuatCay = $tongSoCayCan - $soCayLayTuKhoBTP;

        if ($soCayLayTuKhoBTP > 0) {
             $this->allocations[$btpVariantId] = ($this->allocations[$btpVariantId] ?? 0) + $soCayLayTuKhoBTP;
        }

        $btp_data_for_summary = [
            'MaBTP' => $final_btp_sku, 'TenBTP' => $btp_info_db['variant_name'], 'SoCayCat' => $tongSoCayCan,
            'SoLuongCan' => $canSanXuatCay, 'TonKhoSnapshot' => $tonKhoCay, 'DaGanSnapshot' => $daGanCay, 'DonViTinh' => 'Cây'
        ];
        
        $this->btp_requirements[$final_btp_sku] = $btp_data_for_summary;
        
        return $btp_data_for_summary;
    }

    private function handleUlaProduct(array $item, int $donhang_id, int $cbh_id): void {
        $this->hasEcuItems = true; // Giả định ULA luôn đi kèm ECU
        if ($item['SoLuongCanSX'] > 0) $this->needsUlaProduction = true;

        $kichThuocRen = $item['KichThuocRen'];
        if (empty($kichThuocRen)) {
             $this->errors[] = "Sản phẩm ULA `{$item['MaHang']}` không có thuộc tính 'Kích thước ren'.";
             return;
        }

        $ecu_details = $this->getEcuInfoByThreadSize($kichThuocRen);
        if (!$ecu_details) {
            $this->errors[] = "Sản phẩm ULA `{$item['MaHang']}`: Không tìm thấy ECU cho ren `{$kichThuocRen}`.";
            return;
        }

        $ecuVariantId = $ecu_details['variant_id'];
        $soLuongEcuCan = (int)$item['SoLuong'] * 2;
        
        $tonKhoEcu = (int)($ecu_details['quantity'] ?? 0);
        $daGanEcu = $this->getAllocatedQuantity($ecuVariantId, $cbh_id);
        $tonKhoKhaDungEcu = max(0, $tonKhoEcu - $daGanEcu);
        $soLuongPhanBoEcu = min($soLuongEcuCan, $tonKhoKhaDungEcu);

        if ($soLuongEcuCan > $soLuongPhanBoEcu) {
            $this->needsEcuImport = true;
        }

        if ($soLuongPhanBoEcu > 0) {
            $this->allocations[$ecuVariantId] = ($this->allocations[$ecuVariantId] ?? 0) + $soLuongPhanBoEcu;
        }

        if (!isset($this->ecu_requirements[$ecuVariantId])) {
            $this->ecu_requirements[$ecuVariantId] = [
                'TenSanPhamEcu' => $ecu_details['variant_name'], 'SoLuongEcu' => 0, 'SoLuongPhanBo' => 0, 
                'DongGoiEcu' => '', 'SoKgEcu' => 0, 'GhiChuEcu' => '', 
                'TonKhoSnapshot' => $tonKhoEcu, 'DaGanSnapshot' => $daGanEcu
            ];
        }

        $this->ecu_requirements[$ecuVariantId]['SoLuongEcu'] += $soLuongEcuCan;
        $this->ecu_requirements[$ecuVariantId]['SoLuongPhanBo'] += $soLuongPhanBoEcu;
    }
    
    private function commitData(int $cbh_id, int $donhang_id, array $item_updates): void {
        $stmt_update_detail = $this->pdo->prepare("
            UPDATE chitietchuanbihang SET 
                TenNhom = ?, ID_ThongSo = ?, DoDay = ?, BanRong = ?, 
                SoLuongCanSX = ?, SoLuongLayTuKho = ?, TonKho = ?, DaGan = ?, 
                SoCayPhaiCat = ?, CanSanXuatCay = ?,
                TonKhoCV = ?, DaGanCV = ?, TonKhoCT = ?, DaGanCT = ?
            WHERE ChiTietCBH_ID = ?
        ");
        
        foreach ($item_updates as $upd) {
            $stmt_update_detail->execute(array_values($upd));
        }

        $stmt_insert_alloc = $this->pdo->prepare("INSERT INTO donhang_phanbo_tonkho (CBH_ID, DonHangID, SanPhamID, SoLuongPhanBo) VALUES (?, ?, ?, ?)");
        foreach ($this->allocations as $sanPhamID => $soLuong) {
            if ($soLuong > 0) {
                $stmt_insert_alloc->execute([$cbh_id, $donhang_id, $sanPhamID, $soLuong]);
            }
        }
        
        $stmt_insert_btp = $this->pdo->prepare("INSERT INTO chitiet_btp_cbh (CBH_ID, MaBTP, TenBTP, SoCayCat, SoLuongCan, TonKhoSnapshot, DaGanSnapshot, DonViTinh) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($this->btp_requirements as $btp) {
             $stmt_insert_btp->execute([$cbh_id, $btp['MaBTP'], $btp['TenBTP'], $btp['SoCayCat'], $btp['SoLuongCan'], $btp['TonKhoSnapshot'], $btp['DaGanSnapshot'], $btp['DonViTinh']]);
        }
        
        $stmt_insert_ecu = $this->pdo->prepare("INSERT INTO chitiet_ecu_cbh (CBH_ID, TenSanPhamEcu, SoLuongEcu, SoLuongPhanBo, DongGoiEcu, SoKgEcu, GhiChuEcu, TonKhoSnapshot, DaGanSnapshot) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($this->ecu_requirements as $ecu) {
            $stmt_insert_ecu->execute([$cbh_id, ...array_values($ecu)]);
        }
    }
    
    private function updateFinalStatuses(int $cbh_id): void {
        // Trạng thái PUR & BTP
        $trangThaiPUR = 'Không cần';
        $trangThaiNhapBTP = 'Không cần';
        $trangThaiXuatBTP = 'Không cần';
        $trangThaiNhapTP_PUR = 'Không cần';
        
        if ($this->hasPurItems) {
            if ($this->needsBtpProduction) {
                $trangThaiPUR = 'Chờ BTP';
                $trangThaiNhapBTP = 'Chờ nhập';
                $trangThaiXuatBTP = 'Chưa xuất';
                $trangThaiNhapTP_PUR = 'Chưa nhập';
            } elseif ($this->needsCutting) {
                $trangThaiPUR = 'Chờ cắt';
                $trangThaiNhapBTP = 'Không cần';
                $trangThaiXuatBTP = 'Chờ xuất';
                $trangThaiNhapTP_PUR = 'Chưa nhập';
            } else {
                $trangThaiPUR = 'Đủ hàng';
            }
        }

        // Trạng thái ULA
        $trangThaiULA = $this->hasUlaItems ? ($this->needsUlaProduction ? 'Cần nhập' : 'Đủ hàng') : 'Không cần';

        // Trạng thái Đai Treo
        $trangThaiDaiTreo = $this->hasDaiTreoItems ? ($this->needsDaiTreoProduction ? 'Cần nhập' : 'Đủ hàng') : 'Không cần';

        // ✅ SỬA LỖI: Logic đúng cho TrangThaiNhapTP_ULA
        // Cột này quản lý cả ULA và Đai Treo
        $trangThaiNhapTP_ULA = 'Không cần';
        if ($this->hasUlaItems && $this->needsUlaProduction) {
            $trangThaiNhapTP_ULA = 'Chờ nhập';
        } elseif ($this->hasDaiTreoItems && $this->needsDaiTreoProduction) {
            // Chỉ gán "Chờ nhập" khi ULA KHÔNG cần SX
            if (!$this->needsUlaProduction) {
                $trangThaiNhapTP_ULA = 'Chờ nhập';
            }
        }

        // Trạng thái ECU
        $trangThaiECU = $this->hasEcuItems ? ($this->needsEcuImport ? 'Cần nhập' : 'Đủ hàng') : 'Không cần';
        
        // Trạng thái tổng thể
       // Trạng thái tổng thể
$isReady = !$this->needsCutting && !$this->needsBtpProduction && !$this->needsUlaProduction && !$this->needsDaiTreoProduction && !$this->needsEcuImport;
$trangThaiChung = 'Đã chuẩn bị'; // Luôn set là "Đã chuẩn bị" sau khi xử lý xong

        $sql = "UPDATE chuanbihang SET 
                    TrangThai = ?, TrangThaiPUR = ?, TrangThaiNhapBTP = ?, TrangThaiXuatBTP = ?, TrangThaiNhapTP_PUR = ?,
                    TrangThaiULA = ?, TrangThaiNhapTP_ULA = ?, TrangThaiDaiTreo = ?, TrangThaiECU = ?
                WHERE CBH_ID = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $trangThaiChung, $trangThaiPUR, $trangThaiNhapBTP, $trangThaiXuatBTP, $trangThaiNhapTP_PUR,
            $trangThaiULA, $trangThaiNhapTP_ULA, $trangThaiDaiTreo, $trangThaiECU,
            $cbh_id
        ]);
    }

    // --- Các phương thức trợ giúp và truy vấn CSDL ---
    private function parsePurSku(string $maHang): ?array {
        if (preg_match('/^PUR-(S|C)\s*([\d\/]+x\d+|\d+)(?:x\d+)?(?:-([A-Z0-9]+))?/', $maHang, $matches)) {
            return ['type' => $matches[1], 'dimensions' => $matches[2], 'suffix' => $matches[3] ?? ''];
        }
        return null;
    }
    
    private function findBtpVariant(string $prefix, string $dimensions, string $suffix): ?array {
        $skus_to_try = [];
        $suffix_part = !empty($suffix) ? ' ' . $suffix : '';
    
        $skus_to_try[] = trim($prefix . ' ' . $dimensions . $suffix_part);
        
        if (!empty($suffix)) {
            $skus_to_try[] = trim($prefix . ' ' . $dimensions);
        }
        
        $parts = explode('x', $dimensions);
        $first_part_of_dimension = $parts[0];
    
        if (is_numeric($first_part_of_dimension)) {
            $original_first_part = $first_part_of_dimension;
            if (strpos($original_first_part, '/') === false) { 
                $parts[0] = str_pad($original_first_part, 3, '0', STR_PAD_LEFT);
                if ($parts[0] !== $original_first_part) {
                    $padded_dimensions = implode('x', $parts);
                    $skus_to_try[] = trim($prefix . ' ' . $padded_dimensions . $suffix_part);
                    if (!empty($suffix)) {
                        $skus_to_try[] = trim($prefix . ' ' . $padded_dimensions);
                    }
                }
            }
        }
        
        $unique_skus = array_unique(array_filter($skus_to_try));
        foreach ($unique_skus as $sku) {
            $btp_info = $this->getVariantInfoBySku($sku);
            if ($btp_info) {
                return ['info' => $btp_info, 'found_sku' => $sku];
            }
        }
        return null;
    }

    private function generateTriedSkusForError(string $prefix, string $dimensions, string $suffix): array {
        $skus_to_try = [];
        $suffix_part = !empty($suffix) ? ' ' . $suffix : '';
        
        $skus_to_try[] = '`' . trim($prefix . ' ' . $dimensions . $suffix_part) . '`';
        if (!empty($suffix)) {
            $skus_to_try[] = '`' . trim($prefix . ' ' . $dimensions) . '`';
        }
        
        $parts = explode('x', $dimensions);
        $first_part_of_dimension = $parts[0];
    
        if (is_numeric($first_part_of_dimension)) {
             if (strpos($first_part_of_dimension, '/') === false) {
                $original_first_part = $first_part_of_dimension;
                $parts[0] = str_pad($original_first_part, 3, '0', STR_PAD_LEFT);
                if ($parts[0] !== $original_first_part) {
                    $padded_dimensions = implode('x', $parts);
                    $skus_to_try[] = '`' . trim($prefix . ' ' . $padded_dimensions . $suffix_part) . '`';
                    if (!empty($suffix)) {
                        $skus_to_try[] = '`' . trim($prefix . ' ' . $padded_dimensions) . '`';
                    }
                }
            }
        }
        return array_unique(array_filter($skus_to_try));
    }
    
    private function getOrderInfo(int $cbh_id): ?array {
        $stmt = $this->pdo->prepare("SELECT cbh.YCSX_ID, dh.BaoGiaID FROM chuanbihang cbh JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID WHERE cbh.CBH_ID = ?");
        $stmt->execute([$cbh_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function clearOldData(int $cbh_id): void {
        $this->pdo->prepare("DELETE FROM donhang_phanbo_tonkho WHERE CBH_ID = ?")->execute([$cbh_id]);
        $this->pdo->prepare("DELETE FROM chitiet_btp_cbh WHERE CBH_ID = ?")->execute([$cbh_id]);
        $this->pdo->prepare("DELETE FROM chitiet_ecu_cbh WHERE CBH_ID = ?")->execute([$cbh_id]);
    }

    private function getCbhItems(int $cbh_id): array {
        $sql = "SELECT ct.ChiTietCBH_ID, ct.SanPhamID, ct.SoLuong, ct.MaHang, v.variant_name, vi.quantity AS TonKhoVatLy, 
                MAX(CASE WHEN a.name = 'Kích thước ren' THEN ao.value END) AS KichThuocRen, 
                MAX(CASE WHEN a.name = 'Đường kính trong' THEN ao.value END) AS DuongKinhTrong, 
                MAX(CASE WHEN a.name = 'Bản rộng' THEN ao.value END) AS BanRong, 
                MAX(CASE WHEN a.name = 'Độ dày' THEN ao.value END) AS DoDayAttr 
                FROM chitietchuanbihang ct 
                JOIN variants v ON ct.SanPhamID = v.variant_id 
                LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id 
                LEFT JOIN variant_attributes va ON v.variant_id = va.variant_id 
                LEFT JOIN attribute_options ao ON va.option_id = ao.option_id 
                LEFT JOIN attributes a ON ao.attribute_id = a.attribute_id 
                WHERE ct.CBH_ID = ? 
                GROUP BY ct.ChiTietCBH_ID 
                ORDER BY ct.ThuTuHienThi";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cbh_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getQuoteDetails(?int $baoGiaId, string $maHang): ?array {
        if (!$baoGiaId) return null;
        $stmt = $this->pdo->prepare("SELECT TenNhom, ID_ThongSo, DoDay, ChieuRong FROM chitietbaogia WHERE BaoGiaID = ? AND MaHang = ? LIMIT 1");
        $stmt->execute([$baoGiaId, $maHang]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function getAllocatedQuantity(int $sanPhamID, int $current_cbh_id): int {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(SoLuongPhanBo), 0) FROM donhang_phanbo_tonkho WHERE SanPhamID = ? AND (CBH_ID IS NULL OR CBH_ID != ?)");
        $stmt->execute([$sanPhamID, $current_cbh_id]);
        return (int)$stmt->fetchColumn();
    }
    
    private function getEcuInfoByThreadSize(string $threadSize): ?array {
        $sql = "SELECT v.variant_id, v.variant_name, vi.quantity 
                FROM variants v 
                JOIN products p ON v.product_id = p.product_id 
                JOIN product_groups pg ON p.group_id = pg.group_id 
                LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id 
                WHERE pg.name = 'Vật tư' AND v.variant_id IN (
                    SELECT va_filter.variant_id FROM variant_attributes va_filter 
                    JOIN attribute_options ao_filter ON va_filter.option_id = ao_filter.option_id 
                    JOIN attributes a_filter ON ao_filter.attribute_id = a_filter.attribute_id 
                    WHERE a_filter.name = 'Kích thước ren' AND ao_filter.value = ?
                ) 
                GROUP BY v.variant_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$threadSize]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getVariantInfoBySku(string $sku): ?array {
        $stmt = $this->pdo->prepare("SELECT v.variant_id, v.variant_name, vi.quantity FROM variants v LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id WHERE v.variant_sku = ? LIMIT 1");
        $stmt->execute([$sku]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function prepareItemUpdateData(array $item): array {
        return [
            'tenNhom' => $item['TenNhom'] ?? 'N/A', 'idThongSo' => $item['ID_ThongSo'] ?? '', 
            'doDay' => $item['DoDay'] ?? '', 'banRong' => $item['BanRong'] ?? '',
            'soLuongCanSX' => $item['SoLuongCanSX'] ?? 0, 'soLuongLayTuKho' => $item['SoLuongLayTuKho'] ?? 0, 
            'tonKhoVatLy' => $item['TonKho'] ?? 0, 'daGanChoDonKhac' => $item['DaGan'] ?? 0, 
            'soCayPhaiCatString' => $item['SoCayPhaiCat'] ?? '0', 'canSanXuatCay' => $item['CanSanXuatCay'] ?? 0,
            'tonKhoCV' => $item['TonKhoCV'] ?? 0, 'daGanCV' => $item['DaGanCV'] ?? 0,
            'tonKhoCT' => $item['TonKhoCT'] ?? 0, 'daGanCT' => $item['DaGanCT'] ?? 0,
            'chiTietCBH_ID' => $item['ChiTietCBH_ID']
        ];
    }
}
?>
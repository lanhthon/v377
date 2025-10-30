<?php
/**
 * File: api/save_phieu_chuan_bi.php
 * Version: 5.0 - Tích hợp logic tìm kiếm BTP thông minh.
 * Description: API lưu toàn bộ thông tin phiếu.
 * - [NEW] Tích hợp các hàm `parsePurSku`, `findBtpVariant` để tìm kiếm BTP chính xác trong CSDL thay vì định dạng chuỗi cố định.
 * - Điều này giải quyết vấn đề không tìm thấy BTP khi có sự khác biệt nhỏ trong tên gọi hoặc định dạng.
 * - Logic lưu trữ các thông tin khác được giữ nguyên.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

// --- CÁC HÀM HỖ TRỢ TÌM KIẾM BTP ---

function getVariantInfoBySku(PDO $pdo, string $sku): ?array {
    $stmt = $pdo->prepare("SELECT v.variant_id, v.variant_name, vi.quantity FROM variants v LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id WHERE v.variant_sku = ? LIMIT 1");
    $stmt->execute([$sku]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function findBtpVariant(PDO $pdo, string $prefix, string $dimensions, string $suffix): ?array {
    $skus_to_try = [];
    $suffix_part = !empty($suffix) ? ' ' . $suffix : '';

    $skus_to_try[] = trim($prefix . ' ' . $dimensions . $suffix_part);
    if (!empty($suffix)) {
        $skus_to_try[] = trim($prefix . ' ' . $dimensions);
    }
    $parts = explode('x', $dimensions);
    if (count($parts) > 0 && is_numeric($parts[0])) {
        $original_first_part = $parts[0];
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
        $btp_info = getVariantInfoBySku($pdo, $sku);
        if ($btp_info) {
            return ['info' => $btp_info, 'found_sku' => $sku];
        }
    }
    return null;
}

function generateTriedSkusForError(string $prefix, string $dimensions, string $suffix): array {
    $skus_to_try = [];
    $suffix_part = !empty($suffix) ? ' ' . $suffix : '';
    $skus_to_try[] = '`' . trim($prefix . ' ' . $dimensions . $suffix_part) . '`';
    if (!empty($suffix)) {
        $skus_to_try[] = '`' . trim($prefix . ' ' . $dimensions) . '`';
    }
    $parts = explode('x', $dimensions);
    if (count($parts) > 0 && is_numeric($parts[0])) {
         if (strpos($parts[0], '/') === false) {
            $original_first_part = $parts[0];
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

function parsePurSku(string $maHang): ?array {
    if (preg_match('/^PUR-(S|C)\s*(?:\d+\s*\/\s*)?(\d+x\d+|\d+)(?:x\d+)?(?:-([A-Z0-9]+))?/', $maHang, $matches)) {
        return [
            'type'       => $matches[1],
            'dimensions' => $matches[2],
            'suffix'     => $matches[3] ?? ''
        ];
    }
    return null;
}

// --- XỬ LÝ LƯU DỮ LIỆU ---

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['cbhID'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu ID Phiếu Chuẩn Bị Hàng.']);
    exit;
}

$pdo = null; 

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    $cbhId = $data['cbhID'];
    $donHangID = !empty($data['thongTinChung']['maDon']) ? $data['thongTinChung']['maDon'] : null;
    if (empty($donHangID)) {
        // Lấy DonHangID từ CSDL nếu không có trong payload
        $stmt_get_dh = $pdo->prepare("SELECT YCSX_ID FROM chuanbihang WHERE CBH_ID = ?");
        $stmt_get_dh->execute([$cbhId]);
        $donHangID = $stmt_get_dh->fetchColumn();
        if (empty($donHangID)) {
            throw new Exception("Không thể tìm thấy Mã Đơn Hàng cho CBH ID: $cbhId.");
        }
    }
    
    $missingBtpsWarning = [];

   // 1. Cập nhật thông tin chung của phiếu
    $info = $data['thongTinChung'];
    $sql_info = "UPDATE chuanbihang SET
                    BoPhan = ?, NgayGuiYCSX = ?, PhuTrach = ?, NgayGiao = ?, NguoiNhanHang = ?,
                    SdtNguoiNhan = ?,
                    DangKiCongTruong = ?, SoDon = ?, DiaDiemGiaoHang = ?, QuyCachThung = ?,
                    XeGrap = ?, XeTai = ?, SoLaiXe = ?, updated_at = NOW()
                  WHERE CBH_ID = ?";
    $stmt_info = $pdo->prepare($sql_info);
    $stmt_info->execute([
        $info['boPhan'] ?? null, !empty($info['ngayGui']) ? $info['ngayGui'] : null,
        $info['phuTrach'] ?? null, !empty($info['ngayGiao']) ? $info['ngayGiao'] : null,
        $info['nguoiNhan'] ?? null,
        $info['sdtNguoiNhan'] ?? null,
        $info['congTrinh'] ?? null, $info['soDon'] ?? null,
        $info['diaDiem'] ?? null, $info['quyCachThung'] ?? null, $info['xeGrap'] ?? null,
        $info['xeTai'] ?? null, $info['soLaiXe'] ?? null, $cbhId
    ]);
    // 2. Lấy dữ liệu sản phẩm trong phiếu CBH từ CSDL để có thông tin đầy đủ
    $sql_get_items = "SELECT ctcbh.ChiTietCBH_ID, ctcbh.SanPhamID, ctcbh.SoLuong, v.variant_sku AS MaHang
                      FROM chitietchuanbihang ctcbh
                      JOIN variants v ON ctcbh.SanPhamID = v.variant_id
                      WHERE ctcbh.CBH_ID = ?";
    $stmt_get_items = $pdo->prepare($sql_get_items);
    $stmt_get_items->execute([$cbhId]);
    $items_in_db = $stmt_get_items->fetchAll(PDO::FETCH_ASSOC);

    $userItems = $data['items'] ?? [];
    $banThanhPhamList = []; // Khởi tạo mảng để tổng hợp BTP

    // 3. Lặp qua các sản phẩm chính để cập nhật và tính toán BTP
    foreach ($items_in_db as $item) {
        $itemDataFromUser = current(array_filter($userItems, fn($i) => ($i['chiTietCBH_ID'] ?? null) == $item['ChiTietCBH_ID'])) ?: [];
        
        $canSanXuatCay = $itemDataFromUser['canSanXuatCay'] ?? 0;
        $canSanXuatCV = $itemDataFromUser['canSanXuatCV'] ?? 0;
        $canSanXuatCT = $itemDataFromUser['canSanXuatCT'] ?? 0;
        $maHang = $item['MaHang'];

        // --- [NEW] BẮT ĐẦU LOGIC TÌM KIẾM VÀ TỔNG HỢP BTP ---
        if ($canSanXuatCay > 0 && (str_contains($maHang, 'PUR-S') || str_contains($maHang, 'PUR-C'))) {
            $btp_parts = parsePurSku($maHang);
            if ($btp_parts) {
                $required_btp_configs = [];
                if (str_contains($maHang, 'PUR-S')) {
                    $required_btp_configs = [
                        ['prefix' => 'CV', 'quantity' => $canSanXuatCay],
                        ['prefix' => 'CT', 'quantity' => $canSanXuatCay]
                    ];
                } elseif (str_contains($maHang, 'PUR-C')) {
                    $required_btp_configs = [
                        ['prefix' => 'CT', 'quantity' => $canSanXuatCay * 2]
                    ];
                }

                foreach ($required_btp_configs as $btp_config) {
                    $found_btp = findBtpVariant($pdo, $btp_config['prefix'], $btp_parts['dimensions'], $btp_parts['suffix']);
                    if ($found_btp) {
                        $ma_btp = $found_btp['found_sku'];
                        if (!isset($banThanhPhamList[$ma_btp])) {
                            $banThanhPhamList[$ma_btp] = ['so_luong_can' => 0, 'so_cay_cat' => 0, 'ten_btp' => $ma_btp, 'don_vi_tinh' => 'Cây'];
                        }
                        $banThanhPhamList[$ma_btp]['so_luong_can'] += $btp_config['quantity'];
                        $banThanhPhamList[$ma_btp]['so_cay_cat'] += $btp_config['quantity'];
                    } else {
                        $skus_tried = generateTriedSkusForError($btp_config['prefix'], $btp_parts['dimensions'], $btp_parts['suffix']);
                        $missingBtpsWarning[] = "Không tìm thấy BTP '{$btp_config['prefix']}' cho `{$maHang}`. Đã thử: " . implode(', ', $skus_tried);
                    }
                }
            }
        }
        // --- KẾT THÚC LOGIC TÌM KIẾM VÀ TỔNG HỢP BTP ---

        // Cập nhật chi tiết sản phẩm (chỉ các trường người dùng có thể sửa)
        $sql_update_item = "UPDATE chitietchuanbihang SET 
                                CanSanXuatCay = ?, CanSanXuatCV = ?, CanSanXuatCT = ?,
                                SoThung = ?, DongGoi = ?, GhiChu = ? 
                            WHERE ChiTietCBH_ID = ?";
        $stmt_update_item = $pdo->prepare($sql_update_item);
        $stmt_update_item->execute([
            $canSanXuatCay,
            $canSanXuatCV,
            $canSanXuatCT,
            $itemDataFromUser['soThung'] ?? null,
            $itemDataFromUser['dongGoi'] ?? null,
            $itemDataFromUser['ghiChu'] ?? null,
            $item['ChiTietCBH_ID']
        ]);
    }

    // 4. Lưu snapshot Bán Thành Phẩm
    $pdo->prepare("DELETE FROM chitiet_btp_cbh WHERE CBH_ID = ?")->execute([$cbhId]);

    if (!empty($banThanhPhamList)) {
        $sql_insert_btp = "INSERT INTO chitiet_btp_cbh (CBH_ID, MaBTP, TenBTP, SoLuongCan, SoCayCat, TonKhoSnapshot, DaGanSnapshot, DonViTinh) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_btp = $pdo->prepare($sql_insert_btp);

        foreach ($banThanhPhamList as $ma_btp => $btp_data) {
            $stmt_btp_variant = $pdo->prepare("SELECT variant_id FROM variants WHERE variant_sku = ?");
            $stmt_btp_variant->execute([$ma_btp]);
            $btp_variant_id = $stmt_btp_variant->fetchColumn();

            $tonKhoBTP = 0; $daGanBTP = 0;
            if ($btp_variant_id) {
                $stmt_inv_btp = $pdo->prepare("SELECT quantity FROM variant_inventory WHERE variant_id = ?");
                $stmt_inv_btp->execute([$btp_variant_id]);
                $tonKhoBTP = (int)($stmt_inv_btp->fetchColumn() ?? 0);

                $stmt_allocated_btp = $pdo->prepare("SELECT COALESCE(SUM(SoLuongPhanBo), 0) FROM donhang_phanbo_tonkho WHERE SanPhamID = ? AND CBH_ID != ?");
                $stmt_allocated_btp->execute([$btp_variant_id, $cbhId]);
                $daGanBTP = (int)($stmt_allocated_btp->fetchColumn() ?? 0);
            }

            $stmt_insert_btp->execute([
                $cbhId, $ma_btp, $btp_data['ten_btp'],
                $btp_data['so_luong_can'], $btp_data['so_cay_cat'],
                $tonKhoBTP, $daGanBTP, $btp_data['don_vi_tinh']
            ]);
        }
    }
    
    // 5. Cập nhật Vật Tư Đi Kèm (ECU)
    if (isset($data['itemsEcuKemTheo']) && is_array($data['itemsEcuKemTheo'])) {
        $sql_update_ecu = "UPDATE chitiet_ecu_cbh SET DongGoiEcu = ?, GhiChuEcu = ? WHERE ChiTietEcuCBH_ID = ?";
        $stmt_update_ecu = $pdo->prepare($sql_update_ecu);
        
        foreach($data['itemsEcuKemTheo'] as $ecu_item) {
            if (!empty($ecu_item['chiTietEcuCBH_ID'])) {
                $stmt_update_ecu->execute([
                    $ecu_item['dongGoiEcu'] ?? null,
                    $ecu_item['ghiChuEcu'] ?? null,
                    $ecu_item['chiTietEcuCBH_ID']
                ]);
            }
        }
    }
    
    // 6. Hoàn tất giao dịch
    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Lưu toàn bộ dữ liệu phiếu thành công!', 
        'warnings' => $missingBtpsWarning
    ]);

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode([
        'success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage(),
        'error_details' => [ 'file' => $e->getFile(), 'line' => $e->getLine() ]
    ]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode([
        'success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
        'error_details' => [ 'file' => $e->getFile(), 'line' => $e->getLine() ]
    ]);
} finally {
    $pdo = null;
}
?>

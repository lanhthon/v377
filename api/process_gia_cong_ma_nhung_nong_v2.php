<?php
/**
 * File: api/process_gia_cong_ma_nhung_nong_v2.php
 * Version: 2.0
 * Description: API xử lý quy trình gia công mạ nhúng nóng với logic mới
 * 
 * QUY TRÌNH MỚI:
 * 1. Kiểm tra tồn kho mạ điện phân (MĐP)
 * 2a. Nếu ĐỦ MĐP: Xuất kho MĐP → Gửi gia công → Nhập kho MNN
 * 2b. Nếu KHÔNG ĐỦ MĐP: 
 *     - Tạo yêu cầu sản xuất ULA (MĐP)
 *     - Sau khi sản xuất xong → Nhập kho MĐP
 *     - Kiểm tra lại tồn kho
 *     - Xuất kho MĐP → Gửi gia công → Nhập kho MNN
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

/**
 * Lấy mã ULA cơ bản (không bao gồm hậu tố)
 */
function getUlaBaseSku($maHang) {
    $maHang = trim($maHang);
    // Loại bỏ các hậu tố như -HDG, -MNN, -PVC
    if (preg_match('/^(ULA\s+\d+(?:\/\d+)?x\d+(?:x[A-Z]\d+)?(?:-[A-Z]\d+)?)/', $maHang, $matches)) {
        return $matches[1];
    }
    return $maHang;
}

// Nhận dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    $pdo = get_db_connection();
    
    switch ($action) {
        case 'check_status':
            // Kiểm tra trạng thái có thể xuất gia công không
            handleCheckStatus($pdo, $input);
            break;
            
        case 'create_production_request':
            // Tạo yêu cầu sản xuất MĐP
            handleCreateProductionRequest($pdo, $input);
            break;
            
        case 'export_for_processing':
            // Xuất kho MĐP đi gia công
            handleExportForProcessing($pdo, $input);
            break;
            
        case 'import_after_processing':
            // Nhập kho MNN sau khi gia công xong
            handleImportAfterProcessing($pdo, $input);
            break;
            
        default:
            throw new Exception('Action không hợp lệ: ' . $action);
    }
    
} catch (Exception $e) {
    error_log("ERROR in process_gia_cong_ma_nhung_nong_v2.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}

/**
 * BƯỚC 1: Kiểm tra trạng thái và đề xuất hành động
 */
function handleCheckStatus($pdo, $input) {
    if (!isset($input['cbh_id']) || !isset($input['chi_tiet_cbh_id'])) {
        throw new Exception('Thiếu thông tin: cbh_id, chi_tiet_cbh_id');
    }
    
    $cbhId = intval($input['cbh_id']);
    $chiTietCbhId = intval($input['chi_tiet_cbh_id']);
    
    // 1. Lấy thông tin sản phẩm mạ nhúng nóng
    $sql = "
        SELECT ctcbh.*, v.sku, v.variant_name,
            (SELECT ao.value 
             FROM variant_attributes va 
             JOIN attribute_options ao ON va.option_id = ao.option_id
             JOIN attributes a ON ao.attribute_id = a.attribute_id
             WHERE va.variant_id = v.variant_id AND a.name = 'Xử lý bề mặt'
             LIMIT 1) AS xu_ly_be_mat
        FROM chitietchuanbihang ctcbh
        LEFT JOIN variants v ON ctcbh.SanPhamID = v.variant_id
        WHERE ctcbh.ChiTietCBH_ID = :chiTietCbhId AND ctcbh.CBH_ID = :cbhId
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':chiTietCbhId' => $chiTietCbhId, ':cbhId' => $cbhId]);
    $itemNhungNong = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$itemNhungNong) {
        throw new Exception('Không tìm thấy sản phẩm trong phiếu chuẩn bị hàng');
    }
    
    if ($itemNhungNong['xu_ly_be_mat'] !== 'Mạ nhúng nóng') {
        throw new Exception('Sản phẩm này không phải loại mạ nhúng nóng');
    }
    
    $soLuongCanThiet = (int)$itemNhungNong['SoLuongCanSX'];
    
    // 2. Tìm sản phẩm MĐP tương ứng
    $baseSku = getUlaBaseSku($itemNhungNong['MaHang']);
    
    $sql = "
        SELECT v.variant_id, v.sku, v.variant_name, vi.quantity AS TonKho
        FROM variants v
        LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
        WHERE v.sku LIKE :baseSku
        AND v.variant_id != :excludeId
        AND EXISTS (
            SELECT 1 FROM variant_attributes va 
            JOIN attribute_options ao ON va.option_id = ao.option_id
            JOIN attributes a ON ao.attribute_id = a.attribute_id
            WHERE va.variant_id = v.variant_id 
            AND a.name = 'Xử lý bề mặt' 
            AND ao.value = 'Mạ điện phân'
        )
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':baseSku' => $baseSku . '%',
        ':excludeId' => $itemNhungNong['SanPhamID']
    ]);
    $itemDienPhan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$itemDienPhan) {
        throw new Exception('Không tìm thấy sản phẩm ULA mạ điện phân tương ứng: ' . $baseSku);
    }
    
    // 3. Kiểm tra tồn kho khả dụng (trừ đi đã gán cho đơn khác)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(SoLuongPhanBo), 0) 
        FROM donhang_phanbo_tonkho 
        WHERE SanPhamID = :variantId AND CBH_ID != :cbhId
    ");
    $stmt->execute([
        ':variantId' => $itemDienPhan['variant_id'],
        ':cbhId' => $cbhId
    ]);
    $daGan = (int)$stmt->fetchColumn();
    
    $tonKhoVatLy = (int)($itemDienPhan['TonKho'] ?? 0);
    $tonKhoKhaDung = max(0, $tonKhoVatLy - $daGan);
    
    // 4. Kiểm tra xem đã có phiếu xuất gia công chưa
    $stmt = $pdo->prepare("
        SELECT PhieuXuatID, MaPhieu, SoLuongXuat, TrangThai
        FROM phieu_xuat_gia_cong
        WHERE CBH_ID = :cbhId 
        AND ChiTietCBH_ID = :chiTietCbhId
        AND LoaiGiaCong = 'Mạ nhúng nóng'
        ORDER BY NgayXuat DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':cbhId' => $cbhId,
        ':chiTietCbhId' => $chiTietCbhId
    ]);
    $phieuXuat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 5. Kiểm tra xem có yêu cầu sản xuất đang chờ không
    $stmt = $pdo->prepare("
        SELECT LSX_ID, SoLenhSX, TrangThai, SoLuongYeuCau
        FROM lenhsanxuat
        WHERE CBH_ID = :cbhId 
        AND SanPhamID = :variantId
        AND LoaiLenhSX = 'ULA'
        AND TrangThai IN ('Chờ duyệt', 'Đã duyệt (đang sx)', 'Đang SX')
        ORDER BY NgayTao DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':cbhId' => $cbhId,
        ':variantId' => $itemDienPhan['variant_id']
    ]);
    $lenhSanXuat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 6. Xác định trạng thái và hành động tiếp theo
    $canXuatGiaCong = $tonKhoKhaDung >= $soLuongCanThiet;
    $needsProduction = !$canXuatGiaCong && !$lenhSanXuat;
    $hasActiveProductionOrder = (bool)$lenhSanXuat;
    $hasExportedForProcessing = (bool)$phieuXuat;
    
    $nextAction = null;
    $statusMessage = '';
    
    if ($hasExportedForProcessing) {
        $nextAction = 'import_after_processing';
        $statusMessage = 'Đã xuất đi gia công. Chờ nhập kho sau khi gia công xong.';
    } elseif ($canXuatGiaCong) {
        $nextAction = 'export_for_processing';
        $statusMessage = 'Đủ hàng mạ điện phân. Có thể xuất đi gia công.';
    } elseif ($hasActiveProductionOrder) {
        $nextAction = 'wait_for_production';
        $statusMessage = 'Đã có yêu cầu sản xuất. Chờ hoàn thành sản xuất và nhập kho.';
    } else {
        $nextAction = 'create_production_request';
        $statusMessage = 'Không đủ hàng mạ điện phân. Cần tạo yêu cầu sản xuất.';
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'san_pham_nhung_nong' => [
                'id' => $itemNhungNong['SanPhamID'],
                'ma' => $itemNhungNong['MaHang'],
                'ten' => $itemNhungNong['TenSanPham'],
                'so_luong_can' => $soLuongCanThiet
            ],
            'san_pham_dien_phan' => [
                'id' => $itemDienPhan['variant_id'],
                'ma' => $itemDienPhan['sku'],
                'ten' => $itemDienPhan['variant_name'],
                'ton_kho_vat_ly' => $tonKhoVatLy,
                'da_gan' => $daGan,
                'ton_kho_kha_dung' => $tonKhoKhaDung
            ],
            'can_xuat_gia_cong' => $canXuatGiaCong,
            'needs_production' => $needsProduction,
            'has_active_production_order' => $hasActiveProductionOrder,
            'has_exported_for_processing' => $hasExportedForProcessing,
            'next_action' => $nextAction,
            'status_message' => $statusMessage,
            'phieu_xuat' => $phieuXuat,
            'lenh_san_xuat' => $lenhSanXuat
        ]
    ]);
}

/**
 * BƯỚC 2A: Tạo yêu cầu sản xuất MĐP (nếu không đủ hàng)
 */
function handleCreateProductionRequest($pdo, $input) {
    if (!isset($input['cbh_id']) || !isset($input['chi_tiet_cbh_id']) || !isset($input['so_luong_can_sx'])) {
        throw new Exception('Thiếu thông tin: cbh_id, chi_tiet_cbh_id, so_luong_can_sx');
    }
    
    $cbhId = intval($input['cbh_id']);
    $chiTietCbhId = intval($input['chi_tiet_cbh_id']);
    $soLuongCanSX = intval($input['so_luong_can_sx']);
    $nguoiTao = $input['nguoi_tao'] ?? 'Hệ thống';
    
    $pdo->beginTransaction();
    
    try {
        // 1. Lấy thông tin sản phẩm MNN
        $sql = "
            SELECT ctcbh.*, v.sku, v.variant_name, cbh.YCSX_ID
            FROM chitietchuanbihang ctcbh
            LEFT JOIN variants v ON ctcbh.SanPhamID = v.variant_id
            LEFT JOIN chuanbihang cbh ON ctcbh.CBH_ID = cbh.CBH_ID
            WHERE ctcbh.ChiTietCBH_ID = :chiTietCbhId
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':chiTietCbhId' => $chiTietCbhId]);
        $itemNhungNong = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$itemNhungNong) {
            throw new Exception('Không tìm thấy sản phẩm');
        }
        
        // 2. Tìm sản phẩm MĐP
        $baseSku = getUlaBaseSku($itemNhungNong['MaHang']);
        
        $sql = "
            SELECT v.variant_id, v.sku, v.variant_name
            FROM variants v
            WHERE v.sku LIKE :baseSku
            AND v.variant_id != :excludeId
            AND EXISTS (
                SELECT 1 FROM variant_attributes va 
                JOIN attribute_options ao ON va.option_id = ao.option_id
                JOIN attributes a ON ao.attribute_id = a.attribute_id
                WHERE va.variant_id = v.variant_id 
                AND a.name = 'Xử lý bề mặt' 
                AND ao.value = 'Mạ điện phân'
            )
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':baseSku' => $baseSku . '%',
            ':excludeId' => $itemNhungNong['SanPhamID']
        ]);
        $itemDienPhan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$itemDienPhan) {
            throw new Exception('Không tìm thấy sản phẩm mạ điện phân: ' . $baseSku);
        }
        
        // 3. Tạo lệnh sản xuất
        $soLenhSX = 'ULA-MDP-' . $cbhId . '-' . time();
        
        $sql = "
            INSERT INTO lenhsanxuat (
                SoLenhSX, YCSX_ID, CBH_ID, ChiTietCBH_ID,
                SanPhamID, MaSanPham, TenSanPham,
                SoLuongYeuCau, LoaiLenhSX, TrangThai,
                NguoiTao, NgayTao, GhiChu
            ) VALUES (
                :soLenhSX, :ycsxId, :cbhId, :chiTietCbhId,
                :sanPhamId, :maSanPham, :tenSanPham,
                :soLuong, 'ULA', 'Chờ duyệt',
                :nguoiTao, NOW(), :ghiChu
            )
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':soLenhSX' => $soLenhSX,
            ':ycsxId' => $itemNhungNong['YCSX_ID'],
            ':cbhId' => $cbhId,
            ':chiTietCbhId' => $chiTietCbhId,
            ':sanPhamId' => $itemDienPhan['variant_id'],
            ':maSanPham' => $itemDienPhan['sku'],
            ':tenSanPham' => $itemDienPhan['variant_name'],
            ':soLuong' => $soLuongCanSX,
            ':nguoiTao' => $nguoiTao,
            ':ghiChu' => "Sản xuất MĐP để gia công mạ nhúng nóng thành {$itemNhungNong['MaHang']}"
        ]);
        
        $lsxId = $pdo->lastInsertId();
        
        // 4. Cập nhật ghi chú trong chitietchuanbihang
        $ghiChuMoi = "[YC-SX-MDP] Đã tạo LSX {$soLenhSX} để sản xuất {$soLuongCanSX} {$itemDienPhan['sku']}";
        
        $sql = "UPDATE chitietchuanbihang SET GhiChu = CONCAT(COALESCE(GhiChu, ''), '\n', :ghiChu) WHERE ChiTietCBH_ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ghiChu' => $ghiChuMoi, ':id' => $chiTietCbhId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Tạo yêu cầu sản xuất thành công',
            'data' => [
                'lsx_id' => $lsxId,
                'so_lenh_sx' => $soLenhSX,
                'san_pham' => [
                    'id' => $itemDienPhan['variant_id'],
                    'ma' => $itemDienPhan['sku'],
                    'ten' => $itemDienPhan['variant_name']
                ],
                'so_luong' => $soLuongCanSX,
                'trang_thai' => 'Chờ duyệt'
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * BƯỚC 3: Xuất kho MĐP đi gia công (khi đã đủ hàng)
 */
function handleExportForProcessing($pdo, $input) {
    if (!isset($input['cbh_id']) || !isset($input['chi_tiet_cbh_id']) || !isset($input['so_luong_xuat'])) {
        throw new Exception('Thiếu thông tin: cbh_id, chi_tiet_cbh_id, so_luong_xuat');
    }
    
    $cbhId = intval($input['cbh_id']);
    $chiTietCbhId = intval($input['chi_tiet_cbh_id']);
    $soLuongXuat = intval($input['so_luong_xuat']);
    $nguoiXuat = $input['nguoi_xuat'] ?? 'Hệ thống';
    $ghiChu = $input['ghi_chu'] ?? '';
    
    if ($soLuongXuat <= 0) {
        throw new Exception('Số lượng xuất phải lớn hơn 0');
    }
    
    $pdo->beginTransaction();
    
    try {
        // 1. Lấy thông tin sản phẩm MNN
        $sql = "
            SELECT ctcbh.*, v.sku, v.variant_name
            FROM chitietchuanbihang ctcbh
            LEFT JOIN variants v ON ctcbh.SanPhamID = v.variant_id
            WHERE ctcbh.ChiTietCBH_ID = :chiTietCbhId
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':chiTietCbhId' => $chiTietCbhId]);
        $itemNhungNong = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 2. Tìm sản phẩm MĐP
        $baseSku = getUlaBaseSku($itemNhungNong['MaHang']);
        
        $sql = "
            SELECT v.variant_id, v.sku, v.variant_name, vi.quantity AS TonKho
            FROM variants v
            LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
            WHERE v.sku LIKE :baseSku
            AND v.variant_id != :excludeId
            AND EXISTS (
                SELECT 1 FROM variant_attributes va 
                JOIN attribute_options ao ON va.option_id = ao.option_id
                JOIN attributes a ON ao.attribute_id = a.attribute_id
                WHERE va.variant_id = v.variant_id 
                AND a.name = 'Xử lý bề mặt' 
                AND ao.value = 'Mạ điện phân'
            )
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':baseSku' => $baseSku . '%',
            ':excludeId' => $itemNhungNong['SanPhamID']
        ]);
        $itemDienPhan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$itemDienPhan) {
            throw new Exception('Không tìm thấy sản phẩm mạ điện phân');
        }
        
        // 3. Kiểm tra tồn kho
        $tonKhoVatLy = (int)($itemDienPhan['TonKho'] ?? 0);
        
        if ($tonKhoVatLy < $soLuongXuat) {
            throw new Exception("Không đủ tồn kho. Cần: {$soLuongXuat}, Tồn: {$tonKhoVatLy}");
        }
        
        // 4. Tạo phiếu xuất kho gia công
        $maPhieuXuat = 'GC-MNN-' . $cbhId . '-' . time();
        
        $sql = "
            INSERT INTO phieu_xuat_gia_cong (
                MaPhieu, CBH_ID, ChiTietCBH_ID,
                SanPhamXuatID, MaSanPhamXuat, TenSanPhamXuat,
                SanPhamNhanID, MaSanPhamNhan, TenSanPhamNhan,
                SoLuongXuat, LoaiGiaCong, TrangThai,
                NguoiXuat, NgayXuat, GhiChu
            ) VALUES (
                :maPhieu, :cbhId, :chiTietCbhId,
                :sanPhamXuatId, :maSanPhamXuat, :tenSanPhamXuat,
                :sanPhamNhanId, :maSanPhamNhan, :tenSanPhamNhan,
                :soLuongXuat, 'Mạ nhúng nóng', 'Đã xuất',
                :nguoiXuat, NOW(), :ghiChu
            )
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':maPhieu' => $maPhieuXuat,
            ':cbhId' => $cbhId,
            ':chiTietCbhId' => $chiTietCbhId,
            ':sanPhamXuatId' => $itemDienPhan['variant_id'],
            ':maSanPhamXuat' => $itemDienPhan['sku'],
            ':tenSanPhamXuat' => $itemDienPhan['variant_name'],
            ':sanPhamNhanId' => $itemNhungNong['SanPhamID'],
            ':maSanPhamNhan' => $itemNhungNong['MaHang'],
            ':tenSanPhamNhan' => $itemNhungNong['TenSanPham'],
            ':soLuongXuat' => $soLuongXuat,
            ':nguoiXuat' => $nguoiXuat,
            ':ghiChu' => $ghiChu ?: "Xuất gia công mạ nhúng nóng từ CBH-{$cbhId}"
        ]);
        
        $phieuXuatId = $pdo->lastInsertId();
        
        // 5. Trừ tồn kho MĐP
        $sql = "UPDATE variant_inventory SET quantity = quantity - :soLuong WHERE variant_id = :variantId";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':soLuong' => $soLuongXuat, ':variantId' => $itemDienPhan['variant_id']]);
        
        // 6. Ghi log xuất kho
        $sql = "
            INSERT INTO inventory_logs (
                variant_id, change_type, quantity_change,
                quantity_before, quantity_after,
                reference_type, reference_id, notes, created_by
            ) VALUES (
                :variantId, 'XUAT_GIA_CONG', :quantityChange,
                :quantityBefore, :quantityAfter,
                'PHIEU_XUAT_GIA_CONG', :referenceId, :notes, :createdBy
            )
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':variantId' => $itemDienPhan['variant_id'],
            ':quantityChange' => -$soLuongXuat,
            ':quantityBefore' => $tonKhoVatLy,
            ':quantityAfter' => $tonKhoVatLy - $soLuongXuat,
            ':referenceId' => $phieuXuatId,
            ':notes' => "Xuất {$soLuongXuat} {$itemDienPhan['sku']} đi gia công mạ nhúng nóng",
            ':createdBy' => $nguoiXuat
        ]);
        
        // 7. Cập nhật ghi chú
        $ghiChuMoi = "[GC-MNN] Đã xuất {$soLuongXuat} {$itemDienPhan['sku']} đi gia công. Phiếu: {$maPhieuXuat}";
        $sql = "UPDATE chitietchuanbihang SET GhiChu = CONCAT(COALESCE(GhiChu, ''), '\n', :ghiChu) WHERE ChiTietCBH_ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ghiChu' => $ghiChuMoi, ':id' => $chiTietCbhId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Xuất kho gia công thành công',
            'data' => [
                'ma_phieu_xuat' => $maPhieuXuat,
                'phieu_xuat_id' => $phieuXuatId,
                'so_luong_xuat' => $soLuongXuat,
                'ton_kho_con_lai' => $tonKhoVatLy - $soLuongXuat
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * BƯỚC 4: Nhập kho MNN sau khi gia công xong
 */
function handleImportAfterProcessing($pdo, $input) {
    if (!isset($input['phieu_xuat_id']) || !isset($input['so_luong_nhap'])) {
        throw new Exception('Thiếu thông tin: phieu_xuat_id, so_luong_nhap');
    }
    
    $phieuXuatId = intval($input['phieu_xuat_id']);
    $soLuongNhap = intval($input['so_luong_nhap']);
    $nguoiNhap = $input['nguoi_nhap'] ?? 'Hệ thống';
    $ghiChu = $input['ghi_chu'] ?? '';
    
    if ($soLuongNhap <= 0) {
        throw new Exception('Số lượng nhập phải lớn hơn 0');
    }
    
    $pdo->beginTransaction();
    
    try {
        // 1. Lấy thông tin phiếu xuất
        $sql = "
            SELECT * FROM phieu_xuat_gia_cong 
            WHERE PhieuXuatID = :phieuXuatId
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':phieuXuatId' => $phieuXuatId]);
        $phieuXuat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$phieuXuat) {
            throw new Exception('Không tìm thấy phiếu xuất gia công');
        }
        
        if ($soLuongNhap > $phieuXuat['SoLuongXuat']) {
            throw new Exception("Số lượng nhập ({$soLuongNhap}) vượt quá số lượng xuất ({$phieuXuat['SoLuongXuat']})");
        }
        
        // 2. Tạo phiếu nhập kho MNN
        $maPhieuNhap = 'NK-MNN-' . $phieuXuat['CBH_ID'] . '-' . time();
        
        $sql = "
            INSERT INTO phieu_nhap_kho (
                MaPhieu, CBH_ID, ChiTietCBH_ID,
                SanPhamID, MaSanPham, TenSanPham,
                SoLuongNhap, LoaiPhieu, TrangThai,
                NguoiNhap, NgayNhap, GhiChu,
                PhieuXuatGiaCongID
            ) VALUES (
                :maPhieu, :cbhId, :chiTietCbhId,
                :sanPhamId, :maSanPham, :tenSanPham,
                :soLuongNhap, 'Nhập từ gia công', 'Đã nhập',
                :nguoiNhap, NOW(), :ghiChu,
                :phieuXuatId
            )
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':maPhieu' => $maPhieuNhap,
            ':cbhId' => $phieuXuat['CBH_ID'],
            ':chiTietCbhId' => $phieuXuat['ChiTietCBH_ID'],
            ':sanPhamId' => $phieuXuat['SanPhamNhanID'],
            ':maSanPham' => $phieuXuat['MaSanPhamNhan'],
            ':tenSanPham' => $phieuXuat['TenSanPhamNhan'],
            ':soLuongNhap' => $soLuongNhap,
            ':nguoiNhap' => $nguoiNhap,
            ':ghiChu' => $ghiChu ?: "Nhập kho sau gia công mạ nhúng nóng. Phiếu xuất: {$phieuXuat['MaPhieu']}",
            ':phieuXuatId' => $phieuXuatId
        ]);
        
        $phieuNhapId = $pdo->lastInsertId();
        
        // 3. Lấy tồn kho hiện tại của MNN
        $stmt = $pdo->prepare("SELECT quantity FROM variant_inventory WHERE variant_id = :variantId");
        $stmt->execute([':variantId' => $phieuXuat['SanPhamNhanID']]);
        $tonKhoHienTai = (int)($stmt->fetchColumn() ?? 0);
        
        // 4. Cộng tồn kho MNN
        $sql = "
            INSERT INTO variant_inventory (variant_id, quantity, updated_at)
            VALUES (:variantId, :quantity, NOW())
            ON DUPLICATE KEY UPDATE 
                quantity = quantity + :quantity,
                updated_at = NOW()
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':variantId' => $phieuXuat['SanPhamNhanID'],
            ':quantity' => $soLuongNhap
        ]);
        
        // 5. Ghi log nhập kho
        $sql = "
            INSERT INTO inventory_logs (
                variant_id, change_type, quantity_change,
                quantity_before, quantity_after,
                reference_type, reference_id, notes, created_by
            ) VALUES (
                :variantId, 'NHAP_TU_GIA_CONG', :quantityChange,
                :quantityBefore, :quantityAfter,
                'PHIEU_NHAP_KHO', :referenceId, :notes, :createdBy
            )
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':variantId' => $phieuXuat['SanPhamNhanID'],
            ':quantityChange' => $soLuongNhap,
            ':quantityBefore' => $tonKhoHienTai,
            ':quantityAfter' => $tonKhoHienTai + $soLuongNhap,
            ':referenceId' => $phieuNhapId,
            ':notes' => "Nhập {$soLuongNhap} {$phieuXuat['MaSanPhamNhan']} sau gia công mạ nhúng nóng",
            ':createdBy' => $nguoiNhap
        ]);
        
        // 6. Cập nhật trạng thái phiếu xuất
        $sql = "UPDATE phieu_xuat_gia_cong SET TrangThai = 'Đã nhập kho' WHERE PhieuXuatID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $phieuXuatId]);
        
        // 7. Cập nhật ghi chú
        $ghiChuMoi = "[NK-MNN] Đã nhập {$soLuongNhap} {$phieuXuat['MaSanPhamNhan']} sau gia công. Phiếu: {$maPhieuNhap}";
        $sql = "UPDATE chitietchuanbihang SET GhiChu = CONCAT(COALESCE(GhiChu, ''), '\n', :ghiChu) WHERE ChiTietCBH_ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ghiChu' => $ghiChuMoi, ':id' => $phieuXuat['ChiTietCBH_ID']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Nhập kho sau gia công thành công',
            'data' => [
                'ma_phieu_nhap' => $maPhieuNhap,
                'phieu_nhap_id' => $phieuNhapId,
                'so_luong_nhap' => $soLuongNhap,
                'ton_kho_moi' => $tonKhoHienTai + $soLuongNhap
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>
<?php
/**
 * File: api/save_phieunhapkho_tp.php
 * Version: 5.0 - [COMPLETE REWRITE] Sửa toàn bộ logic cập nhật trạng thái
 * Description: Lưu phiếu nhập kho thành phẩm
 * - ULA: Từ LSX → Cập nhật SoLuongDaNhap trong chitiet_lenh_san_xuat
 * - PUR: Từ CBH → Cập nhật SoLuongDaNhapTP trong chitietchuanbihang
 * - Cập nhật đúng tất cả các cột trạng thái
 * * CHỈNH SỬA: Loại bỏ TrangThai = 'Hoàn thành' khi nhập đủ PUR.
 */

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

// --- HÀM PHỤ TRỢ ---

/**
 * Tạo số phiếu nhập kho tự động
 */
function generatePNKNumber(PDO $pdo, string $loai): string {
    $prefix = $loai === 'ULA' ? 'PNK-ULA-' : 'PNK-PUR-';
    $today = date('ymd');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM phieunhapkho WHERE SoPhieuNhapKho LIKE ?");
    $stmt->execute(["{$prefix}{$today}%"]);
    $count = $stmt->fetchColumn() + 1;
    return $prefix . $today . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Validate LSX cho ULA
 */
function validateLSXForULA(PDO $pdo, int $cbh_id): array {
    $stmt = $pdo->prepare("
        SELECT LenhSX_ID, TrangThai, SoLenhSX
        FROM lenh_san_xuat
        WHERE CBH_ID = ? AND LoaiLSX = 'ULA'
        ORDER BY NgayTao DESC
        LIMIT 1
    ");
    $stmt->execute([$cbh_id]);
    $lsx = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lsx) {
        throw new Exception("Không tìm thấy lệnh sản xuất ULA cho phiếu CBH này.");
    }
    
    if ($lsx['TrangThai'] !== 'Hoàn thành') {
        throw new Exception("Lệnh sản xuất {$lsx['SoLenhSX']} chưa hoàn thành. Không thể nhập kho.");
    }
    
    return $lsx;
}

/**
 * Cập nhật số lượng đã nhập ULA trong chi tiết LSX
 */
function updateLSXProgressULA(PDO $pdo, int $lenhSX_ID, array $items): void {
    $stmt = $pdo->prepare("
        UPDATE chitiet_lenh_san_xuat
        SET SoLuongDaNhap = SoLuongDaNhap + ?
        WHERE LenhSX_ID = ? AND SanPhamID = ?
    ");
    
    foreach ($items as $item) {
        $stmt->execute([
            $item['soLuongThucNhap'],
            $lenhSX_ID,
            $item['variant_id']
        ]);
    }
}

/**
 * Cập nhật số lượng đã nhập PUR trong chitietchuanbihang
 */
function updateCBHProgressPUR(PDO $pdo, int $cbh_id, array $items): void {
    $stmt = $pdo->prepare("
        UPDATE chitietchuanbihang
        SET SoLuongDaNhapTP = COALESCE(SoLuongDaNhapTP, 0) + ?
        WHERE CBH_ID = ? AND SanPhamID = ?
    ");
    
    foreach ($items as $item) {
        $stmt->execute([
            $item['soLuongThucNhap'],
            $cbh_id,
            $item['variant_id']
        ]);
    }
}

/**
 * Kiểm tra và cập nhật trạng thái CBH sau khi nhập kho
 */
function syncCBHStatusAfterReceipt(PDO $pdo, int $cbh_id, string $loai, ?int $lenhSX_ID = null): void {
    if ($loai === 'ULA') {
        // Kiểm tra LSX ULA đã nhập đủ chưa
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN SoLuongDaNhap >= SoLuongBoCanSX THEN 1 ELSE 0 END) as completed
            FROM chitiet_lenh_san_xuat
            WHERE LenhSX_ID = ?
        ");
        $stmt->execute([$lenhSX_ID]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $allCompleted = ($progress['total'] > 0 && $progress['completed'] == $progress['total']);
        
        if ($allCompleted) {
            // ✅ Đã nhập đủ ULA
            $pdo->prepare("
                UPDATE chuanbihang 
                SET TrangThaiULA = 'Đã nhập ULA', 
                    TrangThaiNhapTP_ULA = 'Đã nhập'
                WHERE CBH_ID = ?
            ")->execute([$cbh_id]);
        } else {
            // ⏳ Đang nhập dần
            $pdo->prepare("
                UPDATE chuanbihang 
                SET TrangThaiNhapTP_ULA = 'Đang nhập'
                WHERE CBH_ID = ?
            ")->execute([$cbh_id]);
        }
        
    } elseif ($loai === 'PUR') {
        // Kiểm tra các sản phẩm PUR trong CBH đã nhập đủ chưa
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN COALESCE(SoLuongDaNhapTP, 0) >= SoLuongCanSX THEN 1 ELSE 0 END) as completed
            FROM chitietchuanbihang ct
            JOIN variants v ON ct.SanPhamID = v.variant_id
            WHERE ct.CBH_ID = ? 
              AND ct.SoLuongCanSX > 0 
              AND UPPER(v.variant_sku) LIKE 'PUR%'
        ");
        $stmt->execute([$cbh_id]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $allCompleted = ($progress['total'] > 0 && $progress['completed'] == $progress['total']);
        
        if ($allCompleted) {
            // ✅ Đã nhập đủ PUR
            // CHỈNH SỬA: Bỏ TrangThai = 'Hoàn thành' ở đây
            $pdo->prepare("
                UPDATE chuanbihang 
                SET TrangThaiPUR = 'Đã nhập TP',
                    TrangThaiNhapTP_PUR = 'Đã nhập'
                WHERE CBH_ID = ?
            ")->execute([$cbh_id]);
        } else {
            // ⏳ Đang nhập dần
            $pdo->prepare("
                UPDATE chuanbihang 
                SET TrangThaiNhapTP_PUR = 'Đang nhập'
                WHERE CBH_ID = ?
            ")->execute([$cbh_id]);
        }
    }
}

/**
 * Kiểm tra và cập nhật trạng thái tổng thể của CBH
 */
function checkAndUpdateOverallStatus(PDO $pdo, int $cbh_id): void {
    // Lấy thông tin trạng thái hiện tại
    $stmt = $pdo->prepare("
        SELECT 
            TrangThai,
            TrangThaiPUR,
            TrangThaiULA,
            TrangThaiDaiTreo,
            TrangThaiECU,
            TrangThaiNhapBTP,
            TrangThaiXuatBTP,
            TrangThaiNhapTP_PUR,
            TrangThaiNhapTP_ULA
        FROM chuanbihang
        WHERE CBH_ID = ?
    ");
    $stmt->execute([$cbh_id]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$status) return;
    
    // Kiểm tra tất cả các quy trình đã hoàn thành chưa
    $purComplete = in_array($status['TrangThaiPUR'], ['Đã nhập TP', 'Không cần', 'Đủ hàng']);
    $ulaComplete = in_array($status['TrangThaiULA'], ['Đã nhập ULA', 'Không cần', 'Đủ hàng']);
    $daiTreoComplete = in_array($status['TrangThaiDaiTreo'], ['Đủ hàng', 'Không cần']);
    $ecuComplete = in_array($status['TrangThaiECU'], ['Đủ hàng', 'Không cần', 'Đã nhập kho VT']);
    
    // Nếu tất cả đã xong → Chuyển sang "Chờ xuất kho"
    if ($purComplete && $ulaComplete && $daiTreoComplete && $ecuComplete) {
        $pdo->prepare("
            UPDATE chuanbihang 
            SET TrangThai = 'Chờ xuất kho'
            WHERE CBH_ID = ?
        ")->execute([$cbh_id]);
    }
}

// --- LUỒNG XỬ LÝ CHÍNH ---

$pdo = null;

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Bạn chưa đăng nhập.');
    }
    $nguoiTaoID = $_SESSION['user_id'];

    $input = json_decode(file_get_contents('php://input'), true);
    
    $cbh_id = isset($input['cbh_id']) ? intval($input['cbh_id']) : 0;
    $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];
    
    if ($cbh_id <= 0 || empty($items)) {
        throw new Exception('Dữ liệu không hợp lệ (thiếu cbh_id hoặc items).');
    }

    $pdo = get_db_connection();
    
    if (!$pdo) {
        throw new Exception('Không thể kết nối database.');
    }
    
    $pdo->beginTransaction();

    // 1. Lấy thông tin CBH
    $stmt_cbh = $pdo->prepare("
        SELECT cbh.YCSX_ID, dh.SoYCSX, cbh.TrangThai, cbh.TrangThaiULA
        FROM chuanbihang cbh
        JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
        WHERE cbh.CBH_ID = ?
    ");
    $stmt_cbh->execute([$cbh_id]);
    $cbhInfo = $stmt_cbh->fetch(PDO::FETCH_ASSOC);
    
    if (!$cbhInfo) {
        throw new Exception('Không tìm thấy phiếu chuẩn bị hàng.');
    }
    
    // 2. Xác định loại phiếu dựa trên mã hàng đầu tiên
    $firstItem = $items[0];
    $stmt_check = $pdo->prepare("SELECT variant_sku FROM variants WHERE variant_id = ?");
    $stmt_check->execute([$firstItem['variant_id']]);
    $sku = $stmt_check->fetchColumn();
    
    if (!$sku) {
        throw new Exception('Không tìm thấy sản phẩm với ID: ' . $firstItem['variant_id']);
    }
    
    $isULA = str_starts_with(strtoupper($sku), 'ULA');
    $loai = $isULA ? 'ULA' : 'PUR';

    // 3. Validate và lấy thông tin LSX (chỉ với ULA)
    $lsxInfo = null;
    if ($loai === 'ULA') {
        $lsxInfo = validateLSXForULA($pdo, $cbh_id);
    }

    // 4. Tạo phiếu nhập kho
    $soPhieuNhap = generatePNKNumber($pdo, $loai);
    $lyDoNhap = $loai === 'ULA' 
        ? "Nhập kho thành phẩm ULA từ Lệnh SX {$lsxInfo['SoLenhSX']}"
        : "Nhập kho thành phẩm PUR sau cắt từ BTP (ĐH: {$cbhInfo['SoYCSX']})";
    
    $loaiPhieu = $loai === 'ULA' ? 'TP_ULA' : 'TP_PUR';
    
    $stmt_pnk = $pdo->prepare("
        INSERT INTO phieunhapkho 
        (SoPhieuNhapKho, LoaiPhieu, NgayNhap, LenhSX_ID, YCSX_ID, CBH_ID, LyDoNhap, NguoiTaoID, TongTien)
        VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, 0)
    ");
    $stmt_pnk->execute([
        $soPhieuNhap,
        $loaiPhieu,
        $lsxInfo ? $lsxInfo['LenhSX_ID'] : null,
        $cbhInfo['YCSX_ID'],
        $cbh_id,
        $lyDoNhap,
        $nguoiTaoID
    ]);
    $pnkID = $pdo->lastInsertId();

    // 5. Lưu chi tiết phiếu nhập
    $stmt_detail = $pdo->prepare("
        INSERT INTO chitietphieunhapkho 
        (PhieuNhapKhoID, SanPhamID, SoLuongTheoDonHang, SoLuong, DonGiaNhap, ThanhTien, GhiChu)
        VALUES (?, ?, 0, ?, 0, 0, ?)
    ");
    
    foreach ($items as $item) {
        $stmt_detail->execute([
            $pnkID,
            $item['variant_id'],
            $item['soLuongThucNhap'],
            $item['ghiChu'] ?? null
        ]);
    }

    // 6. Cập nhật tồn kho
    $stmt_update_inventory = $pdo->prepare("
        INSERT INTO variant_inventory (variant_id, quantity)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
    ");
    
    foreach ($items as $item) {
        $stmt_update_inventory->execute([$item['variant_id'], $item['soLuongThucNhap']]);
    }

    // 7. Ghi log lịch sử
    $stmt_log = $pdo->prepare("
        INSERT INTO lichsunhapxuat 
        (SanPhamID, NgayGiaoDich, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu, GhiChu)
        SELECT ?, NOW(), 'NHAP_KHO', ?, quantity, ?, ?
        FROM variant_inventory WHERE variant_id = ?
    ");
    
    foreach ($items as $item) {
        $stmt_log->execute([
            $item['variant_id'],
            $item['soLuongThucNhap'],
            $soPhieuNhap,
            "Nhập TP {$loai}",
            $item['variant_id']
        ]);
    }

    // 8. Cập nhật số lượng đã nhập và trạng thái CBH
    if ($loai === 'ULA') {
        updateLSXProgressULA($pdo, $lsxInfo['LenhSX_ID'], $items);
        syncCBHStatusAfterReceipt($pdo, $cbh_id, 'ULA', $lsxInfo['LenhSX_ID']);
    } else {
        updateCBHProgressPUR($pdo, $cbh_id, $items);
        syncCBHStatusAfterReceipt($pdo, $cbh_id, 'PUR');
    }

    // 9. Kiểm tra và cập nhật trạng thái tổng thể
    checkAndUpdateOverallStatus($pdo, $cbh_id);

    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "✅ Nhập kho thành phẩm {$loai} thành công! Phiếu: {$soPhieuNhap}",
        'pnk_id' => $pnkID,
        'so_phieu' => $soPhieuNhap
    ]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("ERROR save_phieunhapkho_tp.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
<?php
/**
 * File: api/create_production_order.php
 * Version: 4.0 - Gộp BTP và ULA vào cùng một Lệnh Sản Xuất.
 * Description: API tạo lệnh sản xuất cho cả BÁN THÀNH PHẨM (BTP) và ULA cần thiết từ một YCSX.
 * - Lấy danh sách sản phẩm từ cả `chitiet_btp_cbh` (cho BTP) và `chitietchuanbihang` (cho ULA).
 * - Đưa tất cả vào các dòng chi tiết của cùng một Lệnh Sản Xuất.
 * - Tái cấu trúc sang PDO và chia thành các hàm nhỏ để dễ quản lý.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php'; // Thay đổi nếu cần để dùng PDO

$ycsx_id = isset($_POST['ycsx_id']) ? intval($_POST['ycsx_id']) : 0;

if ($ycsx_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Yêu cầu sản xuất không hợp lệ.']);
    exit;
}

try {
    $pdo = get_db_connection(); // Hàm này nên trả về một đối tượng PDO
    $pdo->beginTransaction();

    // --- Bước 1: Kiểm tra đơn hàng và đảm bảo chưa có LSX ---
    $stmt_check = $pdo->prepare("
        SELECT dh.YCSX_ID, dh.SoYCSX, dh.NgayTao 
        FROM donhang dh
        LEFT JOIN lenh_san_xuat lsx ON dh.YCSX_ID = lsx.YCSX_ID
        WHERE dh.YCSX_ID = ? AND lsx.LenhSX_ID IS NULL
    ");
    $stmt_check->execute([$ycsx_id]);
    $demand = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$demand) {
        throw new Exception('Đơn hàng không tồn tại hoặc đã có lệnh sản xuất.');
    }
    $soYCSX = $demand['SoYCSX'];

    // --- Bước 2: Lấy danh sách TẤT CẢ sản phẩm cần sản xuất (BTP và ULA) ---
    
    // Lấy BTP cần sản xuất
    $stmt_btp = $pdo->prepare("
        SELECT 
            v.variant_id AS SanPhamID,
            cbtp.SoLuongCan AS SoLuong,
            'BTP' AS LoaiSanPham
        FROM chitiet_btp_cbh cbtp
        JOIN variants v ON cbtp.MaBTP = v.variant_sku
        JOIN chuanbihang cbh ON cbtp.CBH_ID = cbh.CBH_ID
        WHERE cbh.YCSX_ID = ? AND cbtp.SoLuongCan > 0
    ");
    $stmt_btp->execute([$ycsx_id]);
    $btp_items = $stmt_btp->fetchAll(PDO::FETCH_ASSOC);

    // Lấy ULA cần sản xuất
    $stmt_ula = $pdo->prepare("
        SELECT 
            ctcbh.SanPhamID,
            ctcbh.SoLuongCanSX AS SoLuong,
            'ULA' AS LoaiSanPham
        FROM chitietchuanbihang ctcbh
        JOIN chuanbihang cbh ON ctcbh.CBH_ID = cbh.CBH_ID
        WHERE cbh.YCSX_ID = ? AND ctcbh.MaHang LIKE 'ULA%' AND ctcbh.SoLuongCanSX > 0
    ");
    $stmt_ula->execute([$ycsx_id]);
    $ula_items = $stmt_ula->fetchAll(PDO::FETCH_ASSOC);

    // Gộp hai danh sách lại
    $items_to_produce = array_merge($btp_items, $ula_items);

    if (empty($items_to_produce)) {
        throw new Exception("Không tìm thấy sản phẩm (BTP hoặc ULA) nào cần sản xuất cho đơn hàng này.");
    }

    // --- Bước 3: Tạo mã Lệnh sản xuất mới ---
    $year = date('Y');
    $prefix = "LSX-{$year}-";
    $stmt_last_num = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(SoLenhSX, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) AS last_num FROM lenh_san_xuat WHERE SoLenhSX LIKE ?");
    $stmt_last_num->execute([$prefix . '%']);
    $last_num = $stmt_last_num->fetchColumn() ?? 0;
    $soLenhSX = $prefix . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);

    // --- Bước 4: Chèn bản ghi chính vào `lenh_san_xuat` ---
    $ngayHoanThanhUocTinh = date('Y-m-d', strtotime('+7 days'));
    $stmt_insert_lsx = $pdo->prepare("
        INSERT INTO lenh_san_xuat (YCSX_ID, SoLenhSX, NgayTao, NgayHoanThanhUocTinh, TrangThai, NgayYCSX) 
        VALUES (?, ?, NOW(), ?, 'Chờ duyệt', ?)
    ");
    $stmt_insert_lsx->execute([$ycsx_id, $soLenhSX, $ngayHoanThanhUocTinh, $demand['NgayTao']]);
    $lenhSxId = $pdo->lastInsertId();

    // --- Bước 5: Chèn chi tiết các sản phẩm vào `chitiet_lenh_san_xuat` ---
    $stmt_insert_ctlsx = $pdo->prepare("
        INSERT INTO chitiet_lenh_san_xuat (LenhSX_ID, SanPhamID, SoLuongBoCanSX, SoLuongCayCanSX, SoLuongCayTuongDuong) 
        VALUES (:lenhSxId, :sanPhamId, :soLuongBo, :soLuongCay, :soLuongCayTuongDuong)
    ");

    foreach ($items_to_produce as $item) {
        $soLuongBoCanSX = 0;
        $soLuongCayCanSX = 0;
        
        // Phân loại để gán số lượng vào đúng cột
        if ($item['LoaiSanPham'] === 'ULA') {
            $soLuongBoCanSX = $item['SoLuong'];
        } elseif ($item['LoaiSanPham'] === 'BTP') {
            $soLuongCayCanSX = $item['SoLuong'];
        }

        $stmt_insert_ctlsx->execute([
            ':lenhSxId' => $lenhSxId,
            ':sanPhamId' => $item['SanPhamID'],
            ':soLuongBo' => $soLuongBoCanSX,
            ':soLuongCay' => $soLuongCayCanSX,
            ':soLuongCayTuongDuong' => $soLuongCayCanSX // Giả định SoLuongCayTuongDuong bằng SoLuongCayCanSX cho BTP
        ]);
    }

    // --- Bước 6: Cập nhật trạng thái của `donhang` và `chuanbihang` ---
    $stmt_update_donhang = $pdo->prepare("UPDATE donhang SET TrangThai = 'Đang SX' WHERE YCSX_ID = ?");
    $stmt_update_donhang->execute([$ycsx_id]);

    $stmt_update_cbh = $pdo->prepare("UPDATE chuanbihang SET TrangThai = 'Chờ duyệt' WHERE YCSX_ID = ?");
    $stmt_update_cbh->execute([$ycsx_id]);

    // --- Bước 7: Hoàn tất ---
    $pdo->commit();
    
    $message = sprintf(
        "Đã tạo LSX '%s' cho đơn hàng '%s' thành công.\nBao gồm: %d BTP và %d ULA.",
        $soLenhSX,
        $soYCSX,
        count($btp_items),
        count($ula_items)
    );
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
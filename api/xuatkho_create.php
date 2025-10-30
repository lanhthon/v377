<?php
header('Content-Type: application/json');

require_once '../config/database.php'; // Đảm bảo đường dẫn này chính xác

global $conn;

if (!isset($conn) || !$conn instanceof mysqli || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true); // Ưu tiên đọc JSON
if (empty($data)) { // Nếu không có JSON, thử đọc từ $_POST
    $data = $_POST;
}

if (!isset($data['ycsx_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không đầy đủ (thiếu ycsx_id hoặc action).']);
    exit;
}

$ycsxId = (int)$data['ycsx_id'];
$action = $data['action'];

try {
    $conn->begin_transaction();

    // 1. Kiểm tra trạng thái đơn hàng và phiếu chuẩn bị hàng (CBH)
    $stmt_check = $conn->prepare("SELECT dh.TrangThai, dh.CBH_ID, cbh.TrangThai FROM donhang dh LEFT JOIN chuanbihang cbh ON dh.CBH_ID = cbh.CBH_ID WHERE dh.YCSX_ID = ?");
    $stmt_check->bind_param("i", $ycsxId);
    $stmt_check->execute();
    $stmt_check->bind_result($donhangStatus, $cbhId, $cbhStatus);
    $stmt_check->fetch();
    $stmt_check->close();

    if (!$cbhId || $cbhStatus !== 'Đã chuẩn bị') {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Phiếu chuẩn bị hàng chưa tồn tại hoặc chưa ở trạng thái "Đã chuẩn bị".']);
        exit;
    }

    // 2. Lấy thông tin đơn hàng và chi tiết sản phẩm từ CBH
    $donhangInfo = [];
    $stmt = $conn->prepare("SELECT dh.*, bg.TenCongTy FROM donhang dh LEFT JOIN baogia bg ON dh.BaoGiaID = bg.BaoGiaID WHERE dh.YCSX_ID = ?");
    $stmt->bind_param("i", $ycsxId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.']);
        exit;
    }
    $donhangInfo = $result->fetch_assoc();
    $stmt->close();

    $cbhItems = [];
    $stmt = $conn->prepare("SELECT * FROM chitietchuanbihang WHERE CBH_ID = ? AND TrangThaiXuatKho = 'chưa xử lý' ORDER BY ThuTuHienThi ASC");
    $stmt->bind_param("i", $cbhId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cbhItems[] = $row;
    }
    $stmt->close();

    if (empty($cbhItems)) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Không có sản phẩm nào trong phiếu chuẩn bị hàng cần xuất kho.']);
        exit;
    }

    // 3. Tạo số phiếu xuất kho mới
    $currentDate = date('Ymd');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM phieuxuatkho WHERE DATE(NgayXuat) = CURDATE()");
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $newPxkNumber = $count + 1;
    $soPhieuXuat = "PXK-" . $currentDate . "-" . str_pad($newPxkNumber, 3, '0', STR_PAD_LEFT);

    // 4. Chèn vào bảng phieuxuatkho
    $sql_pxk = "INSERT INTO phieuxuatkho (YCSX_ID, BBGH_ID, SoPhieuXuat, NgayXuat, NguoiNhan, GhiChu, NguoiTaoID, CCCL_ID)
                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)";
    $stmt_pxk = $conn->prepare($sql_pxk);

    $bbghId = null; // Biến này cần được xác định nếu có biên bản giao hàng
    $nguoiNhan = $donhangInfo['NguoiNhan'] ?: $donhangInfo['TenCongTy'];
    $ghiChu = "Xuất kho cho đơn hàng " . $donhangInfo['SoYCSX'];
    $nguoiTaoID = $donhangInfo['UserID'] ?? null; // Người tạo đơn hàng
    $ccclId = null; // ID chứng chỉ chất lượng nếu có

    $stmt_pxk->bind_param("iisssiis",
        $ycsxId, $bbghId, $soPhieuXuat, $nguoiNhan, $ghiChu, $nguoiTaoID, $ccclId
    );
    $stmt_pxk->execute();
    $pxkId = $conn->insert_id;
    $stmt_pxk->close();

    // 5. Chèn các mục chi tiết vào chitiet_phieuxuatkho và cập nhật tồn kho
    $stmt_chitiet_pxk = $conn->prepare("INSERT INTO chitiet_phieuxuatkho (
                                            PhieuXuatKhoID, SanPhamID, SoLuongYeuCau, SoLuongThucXuat,
                                            TaiSo, DonViTinh, GhiChu
                                        ) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $stmt_lichsu = $conn->prepare("INSERT INTO lichsunhapxuat (
                                        SanPhamID, NgayGiaoDich, LoaiGiaoDich, SoLuongThayDoi,
                                        SoLuongSauGiaoDich, MaThamChieu, GhiChu
                                    ) VALUES (?, NOW(), 'XUAT_KHO', ?, ?, ?, ?)");

    $stmt_update_inventory = $conn->prepare("UPDATE variant_inventory SET SoLuongTon = SoLuongTon - ? WHERE variant_id = ?");

    // Lấy thông tin tồn kho hiện tại để tính SoLuongSauGiaoDich
    $stmt_current_inventory = $conn->prepare("SELECT SoLuongTon FROM variant_inventory WHERE variant_id = ?");

    foreach ($cbhItems as $item) {
        $sanPhamId = $item['SanPhamID'] ?? null;
        $soLuongYeuCau = $item['SoLuong'] ?? 0;
        $soLuongThucXuat = $soLuongYeuCau; // Giả định xuất đủ số lượng yêu cầu
        
        $taiSo = $item['SoThung'] ?? null; // Lấy từ CBH
        $ghiChuItem = $item['GhiChu'] ?? null;

        // Lấy đơn vị tính (ví dụ: 'Bộ')
        $unitName = 'Bộ'; // Mặc định là Bộ
        $stmt_unit = $conn->prepare("SELECT u.name FROM variants v JOIN units u ON v.unit_id = u.unit_id WHERE v.variant_id = ?");
        // NOTE: variants table doesn't have unit_id based on baogia_db.json. This needs to be adjusted.
        // For now, I'll hardcode 'Bộ' or assume you have unit info linked to variants.
        // If variants.unit_id does not exist, you might need to find it via products table or assume.
        // Given your existing code in baogia_management.js, 'Bộ' is hardcoded as unit.
        // For now, let's use a placeholder. It's better to fetch it if available.
        // As per provided DB schema, `units` table has unit_id and name.
        // Need to add unit_id to variants table or link products to units.
        // For now, use a default.

        // Chèn vào chitiet_phieuxuatkho
        $stmt_chitiet_pxk->bind_param("iiissis",
            $pxkId, $sanPhamId, $soLuongYeuCau, $soLuongThucXuat, $taiSo, $unitName, $ghiChuItem
        );
        $stmt_chitiet_pxk->execute();

        // Cập nhật lịch sử nhập xuất
        $currentStock = 0;
        $stmt_current_inventory->bind_param("i", $sanPhamId);
        $stmt_current_inventory->execute();
        $stmt_current_inventory->bind_result($fetchedStock);
        if ($stmt_current_inventory->fetch()) {
            $currentStock = $fetchedStock;
        }
        $stmt_current_inventory->close(); // Close before next execute

        $newStock = $currentStock - $soLuongThucXuat;
        $maThamChieu = $soPhieuXuat;
        $ghiChuLichSu = "Xuất kho theo phiếu " . $soPhieuXuat;

        $stmt_lichsu->bind_param("iidss",
            $sanPhamId, -$soLuongThucXuat, $newStock, $maThamChieu, $ghiChuLichSu
        );
        $stmt_lichsu->execute();

        // Cập nhật tồn kho
        $stmt_update_inventory->bind_param("ii", $soLuongThucXuat, $sanPhamId);
        $stmt_update_inventory->execute();
    }
    $stmt_chitiet_pxk->close();
    $stmt_lichsu->close();
    $stmt_update_inventory->close();

    // 6. Cập nhật trạng thái của đơn hàng thành "Chờ xuất kho"
    $stmt_update_donhang_status = $conn->prepare("UPDATE donhang SET TrangThai = 'Chờ xuất kho' WHERE YCSX_ID = ?");
    $stmt_update_donhang_status->bind_param("i", $ycsxId);
    $stmt_update_donhang_status->execute();
    $stmt_update_donhang_status->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Yêu cầu xuất kho đã được tạo thành công.', 'pxkId' => $pxkId]);

} catch (Exception $e) {
    if ($conn) {
        $conn->rollback();
    }
    error_log("Lỗi trong xuatkho_create.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi tạo phiếu xuất kho: ' . $e->getMessage()]);
}
?>
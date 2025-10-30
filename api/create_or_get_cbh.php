<?php
header('Content-Type: application/json');

require_once '../config/database.php'; // Đảm bảo đường dẫn này chính xác

global $conn;

if (!isset($conn) || !$conn instanceof mysqli || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['donhangId'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID đơn hàng.']);
    exit;
}

$donhangId = (int)$data['donhangId'];

try {
    $conn->begin_transaction();

    // 1. Kiểm tra xem đã có phiếu chuẩn bị hàng (CBH) cho đơn hàng này chưa
    $cbhId = null;
    $stmt = $conn->prepare("SELECT CBH_ID FROM chuanbihang WHERE YCSX_ID = ?");
    $stmt->bind_param("i", $donhangId);
    $stmt->execute();
    $stmt->bind_result($existingCbhId);
    if ($stmt->fetch()) {
        $cbhId = $existingCbhId;
    }
    $stmt->close();

    if ($cbhId) {
        // Đã có phiếu CBH, trả về ID và thông báo
        echo json_encode(['success' => true, 'message' => 'Phiếu chuẩn bị hàng đã tồn tại.', 'cbhId' => $cbhId]);
        $conn->commit();
        exit;
    }

    // 2. Nếu chưa có, lấy thông tin đơn hàng để tạo phiếu CBH mới
    $donhangInfo = [];
    $stmt = $conn->prepare("SELECT dh.*, bg.SoBaoGia, bg.TenCongTy FROM donhang dh LEFT JOIN baogia bg ON dh.BaoGiaID = bg.BaoGiaID WHERE dh.YCSX_ID = ?");
    $stmt->bind_param("i", $donhangId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.']);
        exit;
    }
    $donhangInfo = $result->fetch_assoc();
    $stmt->close();

    // 3. Tạo số phiếu CBH mới
    $currentYear = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM chuanbihang WHERE YEAR(NgayTao) = ?");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $newCbhNumber = $count + 1;
    $soCBH = "CBH-" . $currentYear . "-" . str_pad($newCbhNumber, 5, '0', STR_PAD_LEFT);


    // Lấy UserID của người tạo đơn hàng (được lưu trong cột UserID của bảng donhang)
    $userID = $donhangInfo['UserID'] ?? null;
    $userName = 'Sales Person'; // Giá trị mặc định hoặc lấy từ bảng nguoidung nếu có UserID

    if ($userID) {
        $stmt_user = $conn->prepare("SELECT HoTen FROM nguoidung WHERE UserID = ?");
        $stmt_user->bind_param("i", $userID);
        $stmt_user->execute();
        $stmt_user->bind_result($fetchedUserName);
        if ($stmt_user->fetch()) {
            $userName = $fetchedUserName;
        }
        $stmt_user->close();
    }


    // 4. Chèn vào bảng chuanbihang
    $sql_cbh = "INSERT INTO chuanbihang (
                    YCSX_ID, BaoGiaID, SoCBH, NgayTao, TenCongTy, BoPhan, NgayGuiYCSX,
                    NgayGiao, NguoiNhanHang, SoDon, MaDon, PhuTrach, TrangThai
                ) VALUES (?, ?, ?, NOW(), ?, ?, NOW(), ?, ?, ?, ?, ?, ?)";
    $stmt_cbh = $conn->prepare($sql_cbh);
    
    $ngayGiao = $donhangInfo['NgayGiaoDuKien'] ?? null;
    $nguoiNhanHang = $donhangInfo['NguoiNhan'] ?? null; // Lấy từ thông tin đơn hàng
    $boPhan = 'Kho - Logistic'; // Mặc định là Kho - Logistic
    $maDon = $donhangInfo['SoYCSX'] ?? null;
    $soDon = $donhangInfo['SoYCSX'] ?? null;

    $stmt_cbh->bind_param("iisssssssss",
        $donhangId,
        $donhangInfo['BaoGiaID'],
        $soCBH,
        $donhangInfo['TenCongTy'],
        $boPhan,
        $ngayGiao,
        $nguoiNhanHang,
        $soDon,
        $maDon,
        $userName, // Người phụ trách
        'Mới tạo' // Trạng thái ban đầu của CBH
    );
    $stmt_cbh->execute();
    $cbhId = $conn->insert_id;
    $stmt_cbh->close();

    // 5. Chèn các mục chi tiết vào chitietchuanbihang
    $donhangItems = [];
    $stmt = $conn->prepare("SELECT ctdh.*, p.base_sku AS ProductBaseSKU,
                                    a_id.value AS ID_ThongSo, a_d.value AS DoDay, a_br.value AS BanRong
                            FROM chitiet_donhang ctdh
                            LEFT JOIN variants v ON ctdh.SanPhamID = v.variant_id
                            LEFT JOIN products p ON v.product_id = p.product_id
                            LEFT JOIN variant_attributes va_id ON v.variant_id = va_id.variant_id
                            LEFT JOIN attribute_options a_id ON va_id.option_id = a_id.option_id AND a_id.attribute_id = 5
                            LEFT JOIN variant_attributes va_d ON v.variant_id = va_d.variant_id
                            LEFT JOIN attribute_options a_d ON va_d.option_id = a_d.option_id AND a_d.attribute_id = 1
                            LEFT JOIN variant_attributes va_br ON v.variant_id = va_br.variant_id
                            LEFT JOIN attribute_options a_br ON va_br.option_id = a_br.option_id AND a_br.attribute_id = 2
                            WHERE ctdh.DonHangID = ? ORDER BY ctdh.ThuTuHienThi ASC");
    $stmt->bind_param("i", $donhangId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $donhangItems[] = $row;
    }
    $stmt->close();

    $stmt_chitiet_cbh = $conn->prepare("INSERT INTO chitietchuanbihang (
                                            CBH_ID, TenNhom, SanPhamID, MaHang, TenSanPham, SoLuong,
                                            ID_ThongSo, DoDay, BanRong, ThuTuHienThi, TrangThaiXuatKho
                                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'chưa xử lý')"); // TrangThaiXuatKho mặc định

    foreach ($donhangItems as $item) {
        $sanPhamId = $item['SanPhamID'] ?? null;
        $tenNhom = $item['TenNhom'] ?? null;
        $maHang = $item['MaHang'] ?? null;
        $tenSanPham = $item['TenSanPham'] ?? null;
        $soLuong = $item['SoLuong'] ?? 0;
        $idThongSo = $item['ID_ThongSo'] ?? null;
        $doDay = $item['DoDay'] ?? null;
        $banRong = $item['BanRong'] ?? null;
        $thuTuHienThi = $item['ThuTuHienThi'] ?? 0;

        $stmt_chitiet_cbh->bind_param("isisisssii",
            $cbhId, $tenNhom, $sanPhamId, $maHang, $tenSanPham, $soLuong,
            $idThongSo, $doDay, $banRong, $thuTuHienThi
        );
        $stmt_chitiet_cbh->execute();
    }
    $stmt_chitiet_cbh->close();

    // Cập nhật CBH_ID trong bảng donhang
    $stmt_update_donhang = $conn->prepare("UPDATE donhang SET CBH_ID = ? WHERE YCSX_ID = ?");
    $stmt_update_donhang->bind_param("ii", $cbhId, $donhangId);
    $stmt_update_donhang->execute();
    $stmt_update_donhang->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Đã tạo phiếu chuẩn bị hàng mới.', 'cbhId' => $cbhId]);

} catch (Exception $e) {
    if ($conn) {
        $conn->rollback();
    }
    error_log("Lỗi trong create_or_get_cbh.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi xử lý phiếu chuẩn bị hàng: ' . $e->getMessage()]);
}
?>
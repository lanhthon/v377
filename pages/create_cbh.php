<?php
// File: api/create_cbh.php (Cập nhật)
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$ycsxID = isset($data['ycsxID']) ? (int)$data['ycsxID'] : 0;

// ... (phần kiểm tra YCSX đã tồn tại CBH chưa giữ nguyên)

$conn->begin_transaction();
try {
    // Lấy thông tin từ YCSX và Báo giá
    $stmt_info = $conn->prepare("
        SELECT y.BaoGiaID, b.TenCongTy, b.DiaDiemGiaoHang, b.NguoiNhan, u.HoTen AS TenPhuTrach, b.SoDonHang, b.MaDonHang
        FROM yeucausanxuat y 
        JOIN baogia b ON y.BaoGiaID = b.BaoGiaID
        LEFT JOIN NguoiDung u ON y.NguoiTaoID = u.UserID
        WHERE y.YCSX_ID = ?
    ");
    $stmt_info->bind_param("i", $ycsxID);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();
    $stmt_info->close();
    if (!$info) throw new Exception("Không tìm thấy YCSX.");

    // Tạo phiếu CBH với các trường mới
    $ngayTao = date('Y-m-d');
    $soCBH = "CBH-" . date('dmy') . "-" . str_pad($ycsxID, 4, '0', STR_PAD_LEFT);
    $stmt_cbh = $conn->prepare("
        INSERT INTO chuanbihang (YCSX_ID, BaoGiaID, SoCBH, NgayTao, TenCongTy, NguoiPhuTrach, DiaDiemGiaoHang, NguoiNhanHang, SoDon, MaDon, NgayGuiYCSX) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
    ");
    $stmt_cbh->bind_param("iissssssss", 
        $ycsxID, $info['BaoGiaID'], $soCBH, $ngayTao, $info['TenCongTy'], 
        $info['TenPhuTrach'], $info['DiaDiemGiaoHang'], $info['NguoiNhan'], $info['SoDonHang'], $info['MaDonHang']
    );
    $stmt_cbh->execute();
    $cbhID = $conn->insert_id;
    $stmt_cbh->close();

    // Sao chép chi tiết từ YCSX sang CBH, bao gồm cả các thông số kích thước
    $stmt_items = $conn->prepare("
        SELECT c.*, s.ID_ThongSo, s.DoDay, s.BanRong 
        FROM chitiet_ycsx c 
        LEFT JOIN sanpham s ON c.SanPhamID = s.SanPhamID
        WHERE c.YCSX_ID = ? ORDER BY c.ThuTuHienThi ASC
    ");
    $stmt_items->bind_param("i", $ycsxID);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();

    $stmt_detail = $conn->prepare("INSERT INTO chitietchuanbihang (CBH_ID, TenNhom, SanPhamID, MaHang, TenSanPham, SoLuong, ThuTuHienThi, ID_ThongSo, DoDay, BanRong) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    while ($item = $items_result->fetch_assoc()) {
        $stmt_detail->bind_param("isisisssss", $cbhID, $item['TenNhom'], $item['SanPhamID'], $item['MaHang'], $item['TenSanPham'], $item['SoLuong'], $item['ThuTuHienThi'], $item['ID_ThongSo'], $item['DoDay'], $item['BanRong']);
        $stmt_detail->execute();
    }
    $stmt_detail->close();
    $stmt_items->close();

    // Cập nhật YCSX
    $stmt_update = $conn->prepare("UPDATE yeucausanxuat SET CBH_ID = ?, TrangThai = 'Đã chuẩn bị hàng' WHERE YCSX_ID = ?");
    $stmt_update->bind_param("ii", $cbhID, $ycsxID);
    $stmt_update->execute();
    $stmt_update->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Tạo phiếu chuẩn bị hàng thành công!', 'cbhID' => $cbhID]);

} catch (Exception $e) { /* ... xử lý lỗi ... */ }
$conn->close();
?>

<?php
// File: api/save_cbh.php (Cập nhật)
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$cbhID = isset($data['cbhID']) ? (int)$data['cbhID'] : 0;
$headerData = isset($data['headerData']) ? $data['headerData'] : [];
$itemsData = isset($data['itemsData']) ? $data['itemsData'] : [];

if ($cbhID <= 0) { /* ... xử lý lỗi ... */ }

$conn->begin_transaction();
try {
    // Cập nhật thông tin header
    $stmt_header = $conn->prepare("
        UPDATE chuanbihang SET
            BoPhan = ?, NgayGuiYCSX = ?, NgayGiao = ?, DangKiCongTruong = ?, PhuTrach = ?, NguoiNhanHang = ?, 
            SoDon = ?, MaDon = ?, QuyCachThung = ?, LoaiXe = ?, XeGrap = ?, XeTai = ?, SoLaiXe = ?, DiaDiemGiaoHang = ?
        WHERE CBH_ID = ?
    ");
    $stmt_header->bind_param("ssssssssssssssi",
        $headerData['BoPhan'], $headerData['NgayGuiYCSX'], $headerData['NgayGiao'], $headerData['DangKiCongTruong'], 
        $headerData['PhuTrach'], $headerData['NguoiNhanHang'], $headerData['SoDon'], $headerData['MaDon'], 
        $headerData['QuyCachThung'], $headerData['LoaiXe'], $headerData['XeGrap'], $headerData['XeTai'], 
        $headerData['SoLaiXe'], $headerData['DiaDiemGiaoHang'],
        $cbhID
    );
    $stmt_header->execute();
    $stmt_header->close();

    // Cập nhật thông tin chi tiết sản phẩm
    $stmt_item = $conn->prepare("
        UPDATE chitietchuanbihang SET 
            SoLuong = ?, SoThung = ?, TonKho = ?, CayCat = ?, DongGoi = ?, DatThem = ?, SoKg = ?, GhiChu = ?
        WHERE ChiTietCBH_ID = ?
    ");
    foreach ($itemsData as $item) {
        $stmt_item->bind_param("isissidisi",
            $item['SoLuong'], $item['SoThung'], $item['TonKho'], $item['CayCat'], $item['DongGoi'],
            $item['DatThem'], $item['SoKg'], $item['GhiChu'], $item['ChiTietCBH_ID']
        );
        $stmt_item->execute();
    }
    $stmt_item->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Lưu thay đổi thành công!']);
} catch (Exception $e) { /* ... xử lý lỗi ... */ }
$conn->close();
?>

<?php
// File: api/get_cbh_details.php (Không đổi nhiều, vì đã lấy hết dữ liệu)
// Đảm bảo câu lệnh SELECT * FROM chuanbihang đã lấy đủ các trường mới.
?>
<?php
// api/save_phieunhapkho.php

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- KẾT NỐI CƠ SỞ DỮ LIỆU ---
// ***QUAN TRỌNG***: Thay đổi các thông tin này cho phù hợp với cấu hình máy chủ của bạn.
$servername = "127.0.0.1";
$username = "root"; 
$password = ""; 
$dbname = "baogia_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối CSDL: ' . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4");

// --- LẤY DỮ LIỆU TỪ FRONTEND ---
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['items']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ hoặc không có sản phẩm nào.']);
    exit();
}

$soPhieu = $data['soPhieu'];
$ngayNhap = $data['ngayNhap'];
$nhaCungCapID = !empty($data['nhaCungCapID']) ? $data['nhaCungCapID'] : null;
$soHoaDon = $data['soHoaDon'];
$lyDoNhap = $data['lyDoNhap'];
$items = $data['items'];
$tongTien = 0;

foreach ($items as $item) {
    $tongTien += ($item['soLuong'] * $item['donGia']);
}


// --- BẮT ĐẦU TRANSACTION ---
$conn->begin_transaction();

try {
    // 1. THÊM VÀO BẢNG `phieunhapkho`
    $stmt1 = $conn->prepare("INSERT INTO phieunhapkho (SoPhieuNhapKho, NgayNhap, NhaCungCapID, SoHoaDon, LyDoNhap, TongTien) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt1 === false) {
        throw new Exception("Lỗi prepare SQL (phieunhapkho): " . $conn->error);
    }
    $stmt1->bind_param("ssisid", $soPhieu, $ngayNhap, $nhaCungCapID, $soHoaDon, $lyDoNhap, $tongTien);
    $stmt1->execute();
    $phieuNhapKhoID = $conn->insert_id;
    $stmt1->close();

    if (!$phieuNhapKhoID) {
        throw new Exception("Không thể tạo phiếu nhập kho.");
    }

    // 2. LẶP QUA TỪNG SẢN PHẨM
    foreach ($items as $item) {
        $sanPhamID = $item['SanPhamID'];
        $soLuong = $item['soLuong'];
        $donGia = $item['donGia'];
        $thanhTien = $soLuong * $donGia;
        $ghiChu = $item['ghiChu'];

        // 2a. THÊM VÀO `chitietphieunhapkho`
        $stmt2 = $conn->prepare("INSERT INTO chitietphieunhapkho (PhieuNhapKhoID, SanPhamID, SoLuong, DonGiaNhap, ThanhTien, GhiChu) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt2 === false) {
            throw new Exception("Lỗi prepare SQL (chitietphieunhapkho): " . $conn->error);
        }
        $stmt2->bind_param("iiidds", $phieuNhapKhoID, $sanPhamID, $soLuong, $donGia, $thanhTien, $ghiChu);
        $stmt2->execute();
        $stmt2->close();

        // 2b. CẬP NHẬT `SoLuongTonKho` TRONG BẢNG `sanpham`
        $stmt3 = $conn->prepare("UPDATE sanpham SET SoLuongTonKho = SoLuongTonKho + ? WHERE SanPhamID = ?");
        if ($stmt3 === false) {
            throw new Exception("Lỗi prepare SQL (sanpham): " . $conn->error);
        }
        $stmt3->bind_param("ii", $soLuong, $sanPhamID);
        $stmt3->execute();
        $stmt3->close();
        
        // 2c. GHI LẠI `lichsunhapxuat`
        // Lấy số lượng tồn kho mới nhất
        $result = $conn->query("SELECT SoLuongTonKho FROM sanpham WHERE SanPhamID = $sanPhamID");
        $soLuongSauGiaoDich = $result->fetch_assoc()['SoLuongTonKho'];
        
        $loaiGiaoDich = 'NHAP_KHO';
        $stmt4 = $conn->prepare("INSERT INTO lichsunhapxuat (SanPhamID, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu, DonGia, GhiChu) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt4 === false) {
            throw new Exception("Lỗi prepare SQL (lichsunhapxuat): " . $conn->error);
        }
        $stmt4->bind_param("isiisds", $sanPhamID, $loaiGiaoDich, $soLuong, $soLuongSauGiaoDich, $soPhieu, $donGia, $ghiChu);
        $stmt4->execute();
        $stmt4->close();
    }
    
    // --- COMMIT TRANSACTION ---
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Phiếu nhập kho đã được lưu thành công!']);

} catch (Exception $e) {
    // --- ROLLBACK TRANSACTION NẾU CÓ LỖI ---
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
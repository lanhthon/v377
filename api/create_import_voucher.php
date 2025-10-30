<?php
/**
 * File: api/create_import_voucher.php
 * API để xử lý việc tạo phiếu nhập kho và cập nhật tồn kho.
 * Phiên bản đã được cập nhật để tính toán và lưu tổng tiền của phiếu nhập.
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Đảm bảo đường dẫn này đúng

$data = json_decode(file_get_contents('php://input'), true);

// --- Kiểm tra dữ liệu đầu vào ---
if (
    !isset($data['ngayNhap']) || empty($data['ngayNhap']) ||
    !isset($data['items']) || !is_array($data['items']) || empty($data['items'])
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

// --- Lấy dữ liệu từ payload ---
$ngayNhap = $data['ngayNhap'];
$nhaCungCapID = isset($data['nhaCungCapID']) && !empty($data['nhaCungCapID']) ? (int)$data['nhaCungCapID'] : null;
$soPhieuNgoai = $data['soPhieu'] ?? null;
$soHoaDon = $data['soHoaDon'] ?? null;
$lyDoNhap = $data['lyDoNhap'] ?? null;
$items = $data['items'];
$soPhieuNhapKho = 'PNK-' . date('Ymd-His'); // Tạo số phiếu nội bộ tự động

// Tạo một chuỗi ghi chú tổng hợp để lưu vào CSDL
$ghiChuChung = "Lý do: " . ($lyDoNhap ?: 'Không có');
if ($soPhieuNgoai) $ghiChuChung .= " | Số phiếu NCC: " . $soPhieuNgoai;
if ($soHoaDon) $ghiChuChung .= " | HĐ số: " . $soHoaDon;

// Dùng transaction để đảm bảo toàn vẹn dữ liệu
$conn->begin_transaction();

try {
    // --- Bổ sung: Tính toán tổng tiền của phiếu nhập ---
    $tongTien = 0;
    foreach ($items as $item) {
        $soLuong = (int)($item['soLuong'] ?? 0);
        $donGiaNhap = (float)($item['donGia'] ?? 0);
        $tongTien += $soLuong * $donGiaNhap;
    }

    // 1. Thêm vào bảng PhieuNhapKho (cập nhật để thêm TongTien)
    // Giả định bảng PhieuNhapKho của bạn đã có cột TongTien
    $stmt1 = $conn->prepare(
        "INSERT INTO PhieuNhapKho (SoPhieuNhapKho, NgayNhap, NhaCungCapID, GhiChu, TongTien) 
         VALUES (?, ?, ?, ?, ?)"
    );
    // Cập nhật bind_param: ssisd (string, string, integer, string, double)
    $stmt1->bind_param("ssisd", $soPhieuNhapKho, $ngayNhap, $nhaCungCapID, $ghiChuChung, $tongTien);
    $stmt1->execute();
    $phieuNhapKhoID = $stmt1->insert_id;
    $stmt1->close();

    if ($phieuNhapKhoID == 0) {
        throw new Exception("Không thể tạo phiếu nhập kho.");
    }

    // 2. Lặp qua từng sản phẩm để xử lý
    foreach ($items as $item) {
        $sanPhamID = (int)$item['sanPhamID'];
        $soLuong = (int)$item['soLuong'];
        $donGiaNhap = (float)$item['donGia'];
        $thanhTien = $soLuong * $donGiaNhap;
        $ghiChuItem = $item['ghiChu'] ?? null;

        if ($soLuong <= 0) {
            throw new Exception("Số lượng phải là số dương.");
        }

        // Lấy tồn kho hiện tại và khóa dòng để cập nhật
        $stmt_check = $conn->prepare("SELECT SoLuongTonKho FROM sanpham WHERE SanPhamID = ? FOR UPDATE");
        $stmt_check->bind_param("i", $sanPhamID);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows === 0) {
             throw new Exception("Sản phẩm với ID {$sanPhamID} không tồn tại.");
        }
        $current_stock_row = $result_check->fetch_assoc();
        $current_stock = $current_stock_row ? (int)$current_stock_row['SoLuongTonKho'] : 0;
        $stmt_check->close();

        $new_stock = $current_stock + $soLuong;

        // 2a. Thêm vào ChiTietPhieuNhapKho
        // Giả định bảng này có cột GhiChu, nếu không, xóa GhiChu và ", ?" khỏi query, và "s" khỏi bind_param
        $stmt2 = $conn->prepare(
            "INSERT INTO ChiTietPhieuNhapKho (PhieuNhapKhoID, SanPhamID, SoLuong, DonGiaNhap, ThanhTien, GhiChu) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt2->bind_param("iiidds", $phieuNhapKhoID, $sanPhamID, $soLuong, $donGiaNhap, $thanhTien, $ghiChuItem);
        $stmt2->execute();
        $stmt2->close();
        
        // 2b. Cập nhật tồn kho trong bảng sanpham
        $stmt4 = $conn->prepare("UPDATE sanpham SET SoLuongTonKho = ? WHERE SanPhamID = ?");
        $stmt4->bind_param("ii", $new_stock, $sanPhamID);
        $stmt4->execute();
        $stmt4->close();
    }

    // Nếu mọi thứ ổn, commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Nhập kho thành công! Số phiếu: ' . $soPhieuNhapKho,
        'soPhieu' => $soPhieuNhapKho
    ]);

} catch (Exception $e) {
    // Nếu có lỗi, rollback tất cả thay đổi
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}

$conn->close();
?>
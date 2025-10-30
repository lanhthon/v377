<?php
// api/update_product_row.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; 

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$payload = json_decode(file_get_contents('php://input'), true);

if (!$payload || !isset($payload['SanPhamID'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu sản phẩm gửi lên không hợp lệ.']);
    exit;
}

$conn->begin_transaction();

try {
    // --- Dữ liệu từ payload ---
    $sanPhamID = (int)$payload['SanPhamID'];
    $maHang = !empty($payload['MaHang']) ? trim($payload['MaHang']) : null;
    $tenSanPham = $payload['TenSanPham'] ?? null;
    $loaiID = isset($payload['LoaiID']) ? (int)$payload['LoaiID'] : null;
    $nhomID = isset($payload['NhomID']) ? (int)$payload['NhomID'] : null;
    $nguonGoc = $payload['NguonGoc'] ?? null;
    $hinhDang = $payload['HinhDang'] ?? null;
    $idThongSo = $payload['ID_ThongSo'] ?? null;
    $doDay = $payload['DoDay'] ?? null;
    $banRong = $payload['BanRong'] ?? null;
    $giaGoc = isset($payload['GiaGoc']) ? (float)$payload['GiaGoc'] : 0.0;
    $donViTinh = $payload['DonViTinh'] ?? null;
    $soLuongTonKho = isset($payload['SoLuongTonKho']) ? (int)$payload['SoLuongTonKho'] : 0;
    $dinhMucToiThieu = isset($payload['DinhMucToiThieu']) ? (int)$payload['DinhMucToiThieu'] : 0;

    // --- Kiểm tra mã hàng trùng lặp ---
    if ($maHang) {
        $checkStmt = $conn->prepare("SELECT SanPhamID FROM sanpham WHERE MaHang = ? AND SanPhamID != ?");
        $checkStmt->bind_param("si", $maHang, $sanPhamID);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception('Mã hàng "' . htmlspecialchars($maHang) . '" đã tồn tại cho một sản phẩm khác.', 409);
        }
        $checkStmt->close();
    }

    // --- Câu lệnh cập nhật ---
    $stmt = $conn->prepare("
        UPDATE sanpham SET 
            LoaiID = ?, NhomID = ?, NguonGoc = ?, MaHang = ?, TenSanPham = ?, 
            HinhDang = ?, ID_ThongSo = ?, DoDay = ?, BanRong = ?, GiaGoc = ?, 
            DonViTinh = ?, SoLuongTonKho = ?, DinhMucToiThieu = ?
        WHERE SanPhamID = ?
    ");

    $stmt->bind_param("iisssssssdssii", 
        $loaiID, $nhomID, $nguonGoc, $maHang, $tenSanPham, $hinhDang, 
        $idThongSo, $doDay, $banRong, $giaGoc, $donViTinh, 
        $soLuongTonKho, $dinhMucToiThieu, $sanPhamID
    );

    $stmt->execute();
    $stmt->close();
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Đã cập nhật sản phẩm thành công!']);

} catch (Exception $e) {
    $conn->rollback();
    $statusCode = $e->getCode() == 409 ? 409 : 500;
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
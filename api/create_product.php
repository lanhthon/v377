<?php
// File: api/create_product.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Bật chế độ báo lỗi của mysqli thành Exception để dùng try-catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Hàm để gửi phản hồi JSON và kết thúc kịch bản
function send_json_response($success, $message, $statusCode) {
    http_response_code($statusCode);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

$data = json_decode(file_get_contents('php://input'));

if (json_last_error() !== JSON_ERROR_NONE) {
    send_json_response(false, 'Lỗi: Dữ liệu JSON không hợp lệ.', 400);
}

// --- VALIDATION ---
if (empty($data->MaHang) || empty($data->TenSanPham)) {
    send_json_response(false, 'Mã hàng và Tên sản phẩm là bắt buộc.', 400);
}

// --- PREPARE DATA ---
$loaiID = !empty($data->LoaiID) ? (int)$data->LoaiID : null;
$nhomID = !empty($data->NhomID) ? (int)$data->NhomID : null;
$nguonGoc = !empty($data->NguonGoc) ? $data->NguonGoc : 'sản xuất';
$maHang = $data->MaHang;
$tenSanPham = $data->TenSanPham;
$hinhDang = !empty($data->HinhDang) ? $data->HinhDang : 'Vuông';
$idThongSo = !empty($data->ID_ThongSo) ? $data->ID_ThongSo : null;
$doDay = !empty($data->DoDay) ? $data->DoDay : null;
$banRong = !empty($data->BanRong) ? $data->BanRong : null;
$giaGoc = !empty($data->GiaGoc) ? (float)$data->GiaGoc : 0;
$donViTinh = !empty($data->DonViTinh) ? $data->DonViTinh : 'Bộ';
$soLuongTonKho = !empty($data->SoLuongTonKho) ? (int)$data->SoLuongTonKho : 0;
$dinhMucToiThieu = !empty($data->DinhMucToiThieu) ? (int)$data->DinhMucToiThieu : 0;

try {
    // --- DATABASE INTERACTION ---
    $sql = "INSERT INTO sanpham (
                LoaiID, NhomID, NguonGoc, MaHang, TenSanPham, HinhDang, 
                ID_ThongSo, DoDay, BanRong, GiaGoc, DonViTinh, 
                SoLuongTonKho, DinhMucToiThieu
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // 'iisssssssdssii' là chuỗi định dạng kiểu dữ liệu
    $stmt->bind_param(
        'iisssssssdsii',
        $loaiID,
        $nhomID,
        $nguonGoc,
        $maHang,
        $tenSanPham,
        $hinhDang,
        $idThongSo,
        $doDay,
        $banRong,
        $giaGoc,
        $donViTinh,
        $soLuongTonKho,
        $dinhMucToiThieu
    );

    $stmt->execute();

    send_json_response(true, 'Sản phẩm đã được thêm thành công.', 201);

} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) { // Lỗi trùng lặp Mã Hàng
        send_json_response(false, 'Lỗi: Mã hàng "' . htmlspecialchars($maHang) . '" đã tồn tại.', 409);
    } else {
        error_log("SQL Error: " . $e->getMessage());
        send_json_response(false, 'Lỗi phía máy chủ khi thêm sản phẩm.', 500);
    }
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>
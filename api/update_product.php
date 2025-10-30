<?php
// api/update_product.php

require_once '../config/database.php';

// 1. Lấy dữ liệu JSON được gửi từ JavaScript
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Kiểm tra dữ liệu đầu vào cơ bản
if (!$data || !isset($data['SanPhamID']) || !isset($data['field']) || !isset($data['value'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit();
}

$sanPhamID = $data['SanPhamID'];
$fieldToUpdate = $data['field'];
$newValue = $data['value'];

// 2. Tạo một "danh sách trắng" (whitelist) các cột được phép chỉnh sửa
// Điều này RẤT QUAN TRỌNG để tránh lỗi và các vấn đề bảo mật (SQL Injection)
$allowedFields = [
    'MaHang',
    'TenSanPham',
    'LoaiID',
    'NhomID',
    'NguonGoc',
    'HinhDang',
    'GiaGoc',
    'SoLuongTonKho',
    'DonViTinh'
];

if (!in_array($fieldToUpdate, $allowedFields)) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Không được phép cập nhật trường này.']);
    exit();
}

// 3. Chuẩn bị và thực thi câu lệnh SQL UPDATE
// Sử dụng prepared statements để tăng cường bảo mật
try {
    // `{$fieldToUpdate}` được coi là an toàn vì đã được kiểm tra với whitelist ở trên
    $sql = "UPDATE sanpham SET `{$fieldToUpdate}` = :value WHERE SanPhamID = :id";
    $stmt = $pdo->prepare($sql);

    // Gán giá trị vào các tham số
    $stmt->bindParam(':value', $newValue);
    $stmt->bindParam(':id', $sanPhamID, PDO::PARAM_INT);

    $stmt->execute();

    // 4. Trả về kết quả thành công
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật sản phẩm #' . $sanPhamID . ' thành công!'
    ]);

} catch (PDOException $e) {
    // 5. Nếu có lỗi, trả về thông báo lỗi
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi máy chủ khi cập nhật: ' . $e->getMessage()
    ]);
}
?>
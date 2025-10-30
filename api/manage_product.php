<?php
// File: api/manage_product.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action'])) {
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
exit;
}

$action = $data['action'];
$conn->begin_transaction();

try {
switch ($action) {
case 'add':
$sql = "INSERT INTO sanpham (LoaiID, MaHang, TenSanPham, ID_ThongSo, DoDay, BanRong, GiaGoc) VALUES (?, ?, ?, ?, ?, ?,
?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssddd", $data['LoaiID'], $data['MaHang'], $data['TenSanPham'], $data['ID_ThongSo'], $data['DoDay'],
$data['BanRong'], $data['GiaGoc']);
$stmt->execute();
$message = 'Thêm sản phẩm thành công.';
break;

case 'update':
$sql = "UPDATE sanpham SET LoaiID = ?, MaHang = ?, TenSanPham = ?, ID_ThongSo = ?, DoDay = ?, BanRong = ?, GiaGoc = ?
WHERE SanPhamID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssdddi", $data['LoaiID'], $data['MaHang'], $data['TenSanPham'], $data['ID_ThongSo'],
$data['DoDay'], $data['BanRong'], $data['GiaGoc'], $data['SanPhamID']);
$stmt->execute();
$message = 'Cập nhật sản phẩm thành công.';
break;

case 'delete':
$sql = "DELETE FROM sanpham WHERE SanPhamID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $data['SanPhamID']);
$stmt->execute();
if ($stmt->affected_rows === 0) {
throw new Exception('Không tìm thấy sản phẩm để xóa.');
}
$message = 'Xóa sản phẩm thành công.';
break;

default:
throw new Exception('Hành động không được hỗ trợ.');
}

$conn->commit();
echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
$conn->rollback();
$errorMessage = $e->getMessage();
// Bắt lỗi khóa ngoại khi xóa
if ($e->getCode() == 1451) {
$errorMessage = 'Không thể xóa sản phẩm này vì nó đã được sử dụng trong báo giá.';
}
// Bắt lỗi trùng mã hàng
if ($e->getCode() == 1062) {
$errorMessage = 'Mã hàng đã tồn tại. Vui lòng chọn mã hàng khác.';
}
http_response_code(500);
echo json_encode(['success' => false, 'message' => $errorMessage]);
}

$conn->close();
?>
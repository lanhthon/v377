<?php
header('Content-Type: application/json; charset=utf-8');


// Hàm helper để gửi phản hồi JSON
function send_json_response($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Tạo kết nối
require_once '../config/database.php'; // Đảm bảo đường dẫn này đúng
$conn->set_charset("utf8mb4");

try {
    // 1. Lấy danh sách sản phẩm gốc
    $goc_result = $conn->query("SELECT GocID, TenGoc FROM sanpham_goc ORDER BY TenGoc");
    $sanpham_goc = $goc_result->fetch_all(MYSQLI_ASSOC);

    // 2. Lấy danh sách loại sản phẩm (giả sử bạn có bảng loaisanpham)
    $loai_result = $conn->query("SELECT LoaiID, TenLoai FROM loaisanpham ORDER BY TenLoai");
    $loai_san_pham = $loai_result ? $loai_result->fetch_all(MYSQLI_ASSOC) : [];

    // 3. Lấy danh sách nhóm sản phẩm (giả sử bạn có bảng nhomsanpham)
    $nhom_result = $conn->query("SELECT NhomID, TenNhomSanPham FROM nhomsanpham ORDER BY TenNhomSanPham");
    $nhom_san_pham = $nhom_result ? $nhom_result->fetch_all(MYSQLI_ASSOC) : [];
    
    $data = [
        'sanpham_goc' => $sanpham_goc,
        'loaiSanPham' => $loai_san_pham,
        'nhomSanPham' => $nhom_san_pham
    ];

    send_json_response(true, 'Lấy các tùy chọn thành công', $data);

} catch (Exception $e) {
    send_json_response(false, 'Lỗi server: ' . $e->getMessage());
} finally {
    $conn->close();
}
?>
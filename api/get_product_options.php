<?php
// File: api/get_product_options.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    // 1. Lấy danh sách Loại sản phẩm (không đổi)
    $loai_sql = "SELECT LoaiID, TenLoai FROM loaisanpham ORDER BY TenLoai ASC";
    $loai_result = $conn->query($loai_sql);
    $loai_san_pham = $loai_result ? $loai_result->fetch_all(MYSQLI_ASSOC) : [];

    // 2. *** THAY ĐỔI: Lấy danh sách Nhóm sản phẩm từ bảng `nhomsanpham` mới ***
    $nhom_sql = "SELECT NhomID, TenNhomSanPham FROM nhomsanpham ORDER BY TenNhomSanPham ASC";
    $nhom_result = $conn->query($nhom_sql);
    $nhom_san_pham = $nhom_result ? $nhom_result->fetch_all(MYSQLI_ASSOC) : [];

    // 3. Lấy các giá trị duy nhất cho Nguồn gốc (không đổi)
    $nguon_goc_sql = "SELECT DISTINCT NguonGoc FROM sanpham WHERE NguonGoc IS NOT NULL AND NguonGoc != '' ORDER BY NguonGoc ASC";
    $nguon_goc_result = $conn->query($nguon_goc_sql);
    $nguon_goc = $nguon_goc_result ? array_column($nguon_goc_result->fetch_all(MYSQLI_ASSOC), 'NguonGoc') : [];
    
    // 4. Lấy các giá trị duy nhất cho Hình dạng (không đổi)
    $hinh_dang_sql = "SELECT DISTINCT HinhDang FROM sanpham WHERE HinhDang IS NOT NULL AND HinhDang != '' ORDER BY HinhDang ASC";
    $hinh_dang_result = $conn->query($hinh_dang_sql);
    $hinh_dang = $hinh_dang_result ? array_column($hinh_dang_result->fetch_all(MYSQLI_ASSOC), 'HinhDang') : [];

    echo json_encode([
        'success' => true,
        'data' => [
            'loaiSanPham' => $loai_san_pham,
            'nhomSanPham' => $nhom_san_pham, // Trả về mảng đối tượng {NhomID, TenNhomSanPham}
            'nguonGoc' => $nguon_goc,
            'hinhDang' => $hinh_dang
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
}

$conn->close();
?>
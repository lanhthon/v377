<?php
// File: api/get_all_products.php (Updated for Pagination)
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';


// Thiết lập các giá trị mặc định cho phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Mặc định 10 sản phẩm mỗi trang
$offset = ($page - 1) * $limit;

try {
    // 1. Lấy tổng số sản phẩm để tính toán số trang
    $total_result = $conn->query("SELECT COUNT(SanPhamID) as total FROM sanpham");
    $total_products = $total_result->fetch_assoc()['total'];

    // 2. Lấy dữ liệu sản phẩm cho trang hiện tại
    $sql = "SELECT p.*, l.TenLoai 
            FROM sanpham p 
            LEFT JOIN loaisanpham l ON p.LoaiID = l.LoaiID 
            ORDER BY p.MaHang ASC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
// danh sách các cột trong bảng sản phẩm `SanPhamID`, `LoaiID`, `NhomSanPham`, `NguonGoc`, `MaHang`, `TenSanPham`, `HinhDang`, `ID_ThongSo`, `DoDay`, `BanRong`, `DuongKinhTrong`, `DuongKinhRen`, `GiaGoc`, `DonViTinh`, `SoLuongTonKho`, `DinhMucToiThieu`, `NangSuat_BoNgay`SELECT * FROM `sanpham` 

    // 3. Trả về kết quả dưới dạng một đối tượng JSON
    // Bao gồm dữ liệu sản phẩm và tổng số lượng
    echo json_encode([
        'total' => (int)$total_products,
        'data' => $products
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn CSDL: ' . $e->getMessage()]);
}

$conn->close();
?>
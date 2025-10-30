<?php
// File: api/get_products_nested_new.php

header('Content-Type: application/json; charset=utf-8');

// --- THÔNG SỐ KẾT NỐI CSDL ---
require_once '../config/database.php'; // Đảm bảo đường dẫn này đúng
$conn->set_charset("utf8mb4");

try {
    // 1. Lấy tất cả các biến thể và thông tin sản phẩm gốc liên quan trong một lần truy vấn
    $sql = "
        SELECT
            sg.GocID,
            sg.TenGoc,
            bs.BienTheID,
            bs.MaHang,
            bs.TenBienThe,
            bs.GiaGoc,
            bs.SoLuongTonKho
        FROM
            sanpham_goc sg
        JOIN
            bienthe_sanpham bs ON sg.GocID = bs.GocID
        ORDER BY
            sg.TenGoc, bs.MaHang;
    ";
    
    $result = $conn->query($sql);
    
    $base_products = [];
    
    // 2. Xử lý kết quả để gom nhóm các biến thể vào sản phẩm gốc tương ứng
    while ($row = $result->fetch_assoc()) {
        $goc_id = $row['GocID'];
        
        // Nếu sản phẩm gốc chưa có trong mảng, khởi tạo nó
        if (!isset($base_products[$goc_id])) {
            $base_products[$goc_id] = [
                'GocID' => $goc_id,
                'TenGoc' => $row['TenGoc'],
                'TongTonKho' => 0, // Sẽ tính tổng sau
                '_children' => []  // Mảng để chứa các biến thể con
            ];
        }
        
        // Tạo một đối tượng cho biến thể con
        $child_variant = [
            'BienTheID' => $row['BienTheID'],
            'MaHang' => $row['MaHang'],
            'TenBienThe' => $row['TenBienThe'],
            'GiaGoc' => (float)$row['GiaGoc'],
            'SoLuongTonKho' => (int)$row['SoLuongTonKho']
        ];
        
        // Thêm biến thể con vào mảng _children của sản phẩm gốc
        $base_products[$goc_id]['_children'][] = $child_variant;
        
        // Cộng dồn tồn kho vào tổng tồn kho của sản phẩm gốc
        $base_products[$goc_id]['TongTonKho'] += (int)$row['SoLuongTonKho'];
    }
    
    // Chuyển mảng kết hợp về mảng tuần tự để trả về JSON array
    $final_data = array_values($base_products);
    
    // Tabulator mong muốn dữ liệu trả về trực tiếp là một mảng
    echo json_encode($final_data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
<?php
/**
 * API Endpoint để lấy danh sách khách hàng từ bảng congty và nguoilienhe.
 * - Sử dụng LEFT JOIN để bao gồm cả các công ty chưa có người liên hệ.
 * - Trả về một mảng JSON chứa thông tin kết hợp.
 * - Đã thêm MaCongTy vào danh sách các trường trả về.
 */

// Thiết lập header
header('Content-Type: application/json; charset=utf-8');

// Nhúng file cấu hình database
require_once '../config/database.php';

try {
    $data = [];
    
    // Câu lệnh SQL mới để JOIN các bảng
    $sql = "
        SELECT 
            ct.CongTyID,
            ct.MaCongTy, -- <-- THÊM DÒNG NÀY
            ct.TenCongTy,
            ct.DiaChi,
            ct.SoDienThoaiChinh,
            ct.SoFax,
            ct.CoCheGiaID,
            nl.NguoiLienHeID,
            nl.HoTen AS TenNguoiLienHe,
            nl.SoDiDong,
            nl.Email AS EmailNguoiLienHe,
            cg.MaCoChe
        FROM 
            congty AS ct
        LEFT JOIN 
            nguoilienhe AS nl ON ct.CongTyID = nl.CongTyID
        LEFT JOIN 
            cochegia AS cg ON ct.CoCheGiaID = cg.CoCheGiaID
        ORDER BY 
            ct.TenCongTy ASC, nl.HoTen ASC
    ";
    
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Đảm bảo MaCoChe trả về là chữ thường (p0, p1,...) để khớp với giá trị của dropdown.
            if (isset($row['MaCoChe'])) {
                $row['MaCoChe'] = strtolower($row['MaCoChe']);
            }
            $data[] = $row;
        }
    }

    // Trả về kết quả dưới dạng chuỗi JSON.
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Nếu có lỗi, trả về lỗi 500 và thông báo.
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

// Đóng kết nối CSDL.
$conn->close();
?>
<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    // ĐÃ CẬP NHẬT: Thêm cột c.SoNgayThanhToan vào câu lệnh SELECT
    $sql_companies = "
        SELECT 
            c.CongTyID, c.MaCongTy, c.TenCongTy, c.DiaChi, c.Website, c.MaSoThue, c.SoDienThoaiChinh, c.SoFax, c.CoCheGiaID, c.NhomKhachHang, c.SoNgayThanhToan,
            bg_stats.SoBaoGiaDaChot,
            cg.TenCoChe, 
            cg.PhanTramDieuChinh,
            (SELECT GROUP_CONCAT(
                CONCAT(cmt.NguoiBinhLuan, ' (', DATE_FORMAT(cmt.NgayBinhLuan, '%d/%m/%Y %H:%i'), '): ', cmt.NoiDung) 
                SEPARATOR '\n'
             ) 
             FROM CongTy_Comment cmt
             WHERE cmt.CongTyID = c.CongTyID
            ) AS Comments
        FROM 
            congty c
        LEFT JOIN 
            cochegia cg ON c.CoCheGiaID = cg.CoCheGiaID
        LEFT JOIN (
            SELECT
                CongTyID,
                COUNT(BaoGiaID) AS SoBaoGiaDaChot
            FROM
                baogia
            WHERE
                TrangThai = 'Chốt'
            GROUP BY
                CongTyID
        ) AS bg_stats ON c.CongTyID = bg_stats.CongTyID
        ORDER BY 
            c.TenCongTy";
            
    $result_companies = $conn->query($sql_companies);
    if ($result_companies === false) {
        throw new Exception("Lỗi khi truy vấn công ty: " . $conn->error);
    }
    
    $companies = [];
    while ($row = $result_companies->fetch_assoc()) {
        $row['_children'] = []; 
        $companies[$row['CongTyID']] = $row;
    }

    $sql_contacts = "SELECT NguoiLienHeID, HoTen, ChucVu, Email, SoDiDong, CongTyID FROM nguoilienhe ORDER BY HoTen";
    $result_contacts = $conn->query($sql_contacts);
     if ($result_contacts === false) {
        throw new Exception("Lỗi khi truy vấn người liên hệ: " . $conn->error);
    }

    while ($contact = $result_contacts->fetch_assoc()) {
        $companyId = $contact['CongTyID'];
        if (isset($companies[$companyId])) {
            $companies[$companyId]['_children'][] = $contact;
        }
    }

    $response['success'] = true;
    $response['data'] = array_values($companies);

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = "Lỗi CSDL: " . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => []];

try {
    $sql = "
        SELECT 
            c.*, 
            bg_stats.SoBaoGiaDaChot, 
            cg.TenCoChe, 
            cg.PhanTramDieuChinh,
            (SELECT COUNT(*) FROM nguoilienhe nl WHERE nl.CongTyID = c.CongTyID) as contactCount
        FROM 
            congty c
        LEFT JOIN 
            cochegia cg ON c.CoCheGiaID = cg.CoCheGiaID
        LEFT JOIN (
            SELECT CongTyID, COUNT(BaoGiaID) AS SoBaoGiaDaChot
            FROM baogia
            WHERE TrangThai = 'Chốt'
            GROUP BY CongTyID
        ) AS bg_stats ON c.CongTyID = bg_stats.CongTyID
        ORDER BY 
            c.TenCongTy";

    $result = $conn->query($sql);
    if ($result === false) {
        throw new Exception("Lỗi truy vấn công ty: " . $conn->error);
    }

    $companies = [];
    while ($row = $result->fetch_assoc()) {
        // Nếu có người liên hệ, thêm thuộc tính _children:true để Tabulator hiển thị mũi tên
        if ($row['contactCount'] > 0) {
            $row['_children'] = true; 
        }
        $companies[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $companies;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = "Lỗi CSDL: " . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

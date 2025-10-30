<?php
/**
 * API: LẤY DANH SÁCH LỆNH SẢN XUẤT (LK) CHỜ NHẬP KHO
 * * CẬP NHẬT: Sử dụng COLLATE để so sánh không phân biệt chữ hoa/thường,
 * đảm bảo lấy được dữ liệu chính xác ngay cả với ký tự tiếng Việt.
 */
requirse_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $pdo = get_db_connection();
    
    // Cập nhật câu lệnh SQL để sử dụng COLLATE cho việc so sánh case-insensitive
    $sql = "SELECT 
                lsx.LenhSX_ID,
                lsx.SoLenhSX,
                lsx.NgayTao,
                lsx.TrangThai,
                lsx.TrangThaiNhapKho,
                lsx.GhiChu
            FROM lenh_san_xuat lsx
            WHERE 
                TRIM(lsx.LoaiLSX) = 'LK' AND 
                TRIM(lsx.TrangThai) COLLATE utf8mb4_unicode_ci = 'Hoàn thành'
            ORDER BY lsx.NgayTao DESC, lsx.LenhSX_ID DESC";

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $data;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
}

echo json_encode($response);
?>

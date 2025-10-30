<?php
header('Content-Type: application/json');
require_once '../config/db_config.php';

try {
    $pdo = get_db_connection();

    $sql = "
        SELECT
            lsx.LenhSX_ID,
            lsx.SoLenhSX,
            lsx.NgayTao,
            lsx.NgayHoanThanhThucTe,
            dh.SoYCSX -- Sửa: Lấy SoYCSX từ bảng donhang
        FROM
            lenh_san_xuat AS lsx
        INNER JOIN
            donhang AS dh ON lsx.YCSX_ID = dh.YCSX_ID -- Sửa: INNER JOIN với bảng donhang
        WHERE
            lsx.TrangThai = 'Hoàn thành'
            AND lsx.LoaiLSX = 'BTP'
            AND lsx.LenhSX_ID NOT IN (
                SELECT LenhSX_ID FROM phieu_nhap_kho_btp WHERE TrangThai = 'Đã Nhập'
            );
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $results
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage()
    ]);
}
?>
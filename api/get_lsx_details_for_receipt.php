<?php
header('Content-Type: application/json');

require_once '../config/db_connection.php';

if (!isset($_GET['lsx_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu tham số LSX_ID.'
    ]);
    exit();
}

$lsx_id = $_GET['lsx_id'];

try {
    $pdo = connect_db();

    // Lấy thông tin header LSX
    $sql_header = "
        SELECT
            lsx.SoLenhSX
        FROM
            lenh_san_xuat AS lsx
        WHERE
            lsx.LenhSX_ID = :lsx_id;
    ";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute(['lsx_id' => $lsx_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy Lệnh Sản Xuất.'
        ]);
        exit();
    }

    // Lấy danh sách sản phẩm BTP từ LSX
    // Giả định có bảng `lenh_san_xuat_chi_tiet` chứa các BTP cần sản xuất
    $sql_items = "
        SELECT
            lsxct.variant_id,
            btp.MaBTP,
            btp.TenBTP,
            btp.DonViTinh,
            lsxct.SoLuong AS SoLuongTheoLenh
        FROM
            lenh_san_xuat_chi_tiet AS lsxct
        INNER JOIN
            ban_thanh_pham AS btp ON lsxct.variant_id = btp.BTP_ID
        WHERE
            lsxct.LenhSX_ID = :lsx_id;
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute(['lsx_id' => $lsx_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'header' => $header,
        'items' => $items
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage()
    ]);
}
?>
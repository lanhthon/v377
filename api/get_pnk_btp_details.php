<?php
/**
 * File: api/get_pnk_btp_details.php
 * API để lấy chi tiết một Phiếu Nhập Kho Bán Thành Phẩm.
 * PHIÊN BẢN HOÀN CHỈNH - ĐÃ ĐỐI CHIẾU VỚI FILE CSDL NGÀY 08/08/2025
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../config/db_config.php';

// --- Hàm hỗ trợ để trả về JSON ---
function send_json_response($success, $data_or_message, $http_code = 200) {
    http_response_code($http_code);
    if ($success) {
        echo json_encode(['success' => true, 'data' => $data_or_message], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => $data_or_message], JSON_UNESCAPED_UNICODE);
    }
    exit();
}

// Dùng đúng tên tham số 'pnk_btp_id' mà JavaScript gửi lên
if (!isset($_GET['pnk_btp_id'])) {
    send_json_response(false, 'Thiếu tham số pnk_btp_id.', 400);
}

// Lấy và xác thực ID
$pnk_btp_id = intval($_GET['pnk_btp_id']);
if ($pnk_btp_id <= 0) {
    send_json_response(false, 'ID Phiếu Nhập Kho không hợp lệ.', 400);
}

try {
    // Sử dụng hàm kết nối CSDL của bạn
    $pdo = get_db_connection();

    // === TRUY VẤN HEADER - [UPDATE] Thêm cột GhiChu và TongTien ===
    $sql_header = "
        SELECT
            pnk.SoPhieuNhapKhoBTP,
            pnk.NgayNhap,
            pnk.LyDoNhap,
            pnk.GhiChu,
            pnk.TongTien, -- THÊM DÒNG NÀY ĐỂ LẤY TỔNG TIỀN
            lsx.SoLenhSX,
            u.HoTen AS TenNguoiTao
        FROM
            phieunhapkho_btp AS pnk
        LEFT JOIN
            lenh_san_xuat AS lsx ON pnk.LenhSX_ID = lsx.LenhSX_ID
        LEFT JOIN
            nguoidung AS u ON pnk.NguoiTaoID = u.UserID
        WHERE
            pnk.PNK_BTP_ID = :pnk_btp_id;
    ";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute(['pnk_btp_id' => $pnk_btp_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        send_json_response(false, 'Không tìm thấy Phiếu Nhập Kho với ID cung cấp.', 404);
    }

    // === TRUY VẤN ITEMS - (Không thay đổi) ===
    $sql_items = "
        SELECT
            v.variant_sku           AS MaBTP,
            v.variant_name          AS TenBTP,
            u.name                  AS DonViTinh,
            chitiet.SoLuong,
            chitiet.so_luong_theo_lenh_sx AS SoLuongTheoLenhSX,
            chitiet.GhiChu
        FROM
            chitiet_pnk_btp AS chitiet
        JOIN
            variants v ON chitiet.BTP_ID = v.variant_id
        LEFT JOIN
            products p ON v.product_id = p.product_id
        LEFT JOIN
            units u ON p.base_unit_id = u.unit_id
        WHERE
            chitiet.PNK_BTP_ID = :pnk_btp_id;
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute(['pnk_btp_id' => $pnk_btp_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Gửi phản hồi thành công với cấu trúc JSON chính xác
    send_json_response(true, [
        'header' => $header,
        'items' => $items
    ]);

} catch (PDOException $e) {
    // Trả về lỗi server nếu có vấn đề với CSDL
    send_json_response(false, 'Lỗi cơ sở dữ liệu: ' . $e->getMessage(), 500);
}
?>


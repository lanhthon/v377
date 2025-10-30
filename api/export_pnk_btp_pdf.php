<?php
/**
 * File: api/export_pnk_btp_pdf.php
 * Endpoint để tạo và xuất file PDF cho một Phiếu Nhập Kho Bán Thành Phẩm.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Đảm bảo bạn đã cài đặt mPDF qua composer
require_once __DIR__ . '/../vendor/autoload.php'; 
// Thay thế bằng file kết nối CSDL của bạn
require_once __DIR__ . '/../config/db_config.php'; 

try {
    // 1. Lấy ID Phiếu nhập kho từ URL
    if (!isset($_GET['pnk_btp_id']) || !is_numeric($_GET['pnk_btp_id'])) {
        http_response_code(400);
        die("Lỗi: ID Phiếu nhập kho không hợp lệ hoặc bị thiếu.");
    }
    $pnk_btp_id = (int)$_GET['pnk_btp_id'];

    // Sử dụng hàm kết nối CSDL của bạn
    $pdo = get_db_connection();

    // 2. Lấy thông tin Header của Phiếu Nhập Kho
    $sql_header = "
        SELECT
            pnk.SoPhieuNhapKhoBTP,
            pnk.NgayNhap,
            pnk.LyDoNhap,
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
    $pnk_info = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$pnk_info) {
        http_response_code(404);
        die("Lỗi: Không tìm thấy Phiếu Nhập Kho có ID là {$pnk_btp_id}.");
    }

    // 3. Lấy chi tiết các Bán thành phẩm trong phiếu
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
            chitiet.PNK_BTP_ID = :pnk_btp_id
        ORDER BY
            chitiet.ChiTiet_PNKBTP_ID ASC;
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute(['pnk_btp_id' => $pnk_btp_id]);
    $pnk_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 4. Bắt đầu bộ đệm đầu ra để lấy nội dung HTML từ file template
    ob_start();
    
    $data = [
        'info' => $pnk_info,
        'items' => $pnk_items,
    ];
    
    // Gọi file template để render HTML
    include __DIR__ . '/../templates/pnk_btp_pdf_template.php';
    
    $html = ob_get_clean();

    // 5. Khởi tạo mPDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => __DIR__ . '/../tmp', // Đảm bảo thư mục này tồn tại và có quyền ghi
        'default_font' => 'dejavusans'
    ]);

    // 6. Ghi CSS và HTML
    $css = file_get_contents(__DIR__ . '/../css/pnk_pdf_styles.css');
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    
    // 7. Xuất file PDF
    $pdfFileName = "PNK-BTP-" . preg_replace('/[^a-zA-Z0-9_-]/', '', $pnk_info['SoPhieuNhapKhoBTP']) . ".pdf";
    $mpdf->Output($pdfFileName, 'I'); // 'I' để hiển thị trong trình duyệt

} catch (Throwable $e) {
    http_response_code(500);
    // Ghi log lỗi thay vì hiển thị trực tiếp cho người dùng
    error_log($e->getMessage());
    die("Đã có lỗi nghiêm trọng xảy ra. Vui lòng thử lại sau.");
}

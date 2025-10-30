<?php
/**
 * api/export_production_order_pdf.php
 * Endpoint để tạo và xuất file PDF cho một Lệnh Sản Xuất sử dụng mPDF.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

global $conn;

try {
    // 1. Lấy ID Lệnh sản xuất từ URL
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        die("Lỗi: ID Lệnh sản xuất không hợp lệ hoặc bị thiếu.");
    }
    $lenhSX_ID = (int)$_GET['id'];

    // 2. [CẬP NHẬT] Sửa câu lệnh để lấy thêm thông tin Người yêu cầu
    $stmt_lsx = $conn->prepare("
        SELECT 
            lsx.*, 
            dh.SoYCSX, dh.NguoiBaoGia,
            bg.TenCongTy, bg.NguoiNhan,
            -- Thêm join để lấy tên người yêu cầu
            COALESCE(u.HoTen, 'Hệ thống') as NguoiYeuCau
        FROM lenh_san_xuat lsx
        LEFT JOIN donhang dh ON lsx.YCSX_ID = dh.YCSX_ID
        LEFT JOIN baogia bg ON dh.BaoGiaID = bg.BaoGiaID
        -- Thêm LEFT JOIN tới bảng nguoidung
        LEFT JOIN nguoidung u ON lsx.NguoiYeuCau_ID = u.UserID
        WHERE lsx.LenhSX_ID = ?
    ");
    $stmt_lsx->bind_param("i", $lenhSX_ID);
    $stmt_lsx->execute();
    $lsx_info = $stmt_lsx->get_result()->fetch_assoc();
    $stmt_lsx->close();

    if (!$lsx_info) {
        http_response_code(404);
        die("Lỗi: Không tìm thấy lệnh sản xuất có ID là {$lenhSX_ID}.");
    }

    // 3. Lấy chi tiết các Bán thành phẩm trong LSX (Giữ nguyên)
    $stmt_items = $conn->prepare("
        SELECT 
            ct.SoLuongCayCanSX,
            ct.SoLuongBoCanSX,
            ct.GhiChu,
            ct.TrangThai AS TrangThaiChiTiet,
            v.variant_sku AS MaBTP,
            u.name AS DonViTinh
        FROM chitiet_lenh_san_xuat ct
        JOIN variants v ON ct.SanPhamID = v.variant_id
        LEFT JOIN products p ON v.product_id = p.product_id
        LEFT JOIN units u ON p.base_unit_id = u.unit_id
        WHERE ct.LenhSX_ID = ?
        ORDER BY ct.ChiTiet_LSX_ID ASC
    ");
    $stmt_items->bind_param("i", $lenhSX_ID);
    $stmt_items->execute();
    $lsx_items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();
    
    $conn->close();

    // 4. Bắt đầu bộ đệm đầu ra để lấy nội dung HTML từ file template
    ob_start();
    
    $data = [
        'info' => $lsx_info,
        'items' => $lsx_items,
    ];
    
    // Gọi file template để render HTML
    include __DIR__ . '/../templates/production_order_pdf_template.php';
    
    $html = ob_get_clean();

    // 5. Khởi tạo mPDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => __DIR__ . '/../tmp',
        'default_font' => 'dejavusans'
    ]);

    // 6. Ghi CSS và HTML
    $css = file_get_contents(__DIR__ . '/../css/production_pdf_styles.css');
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    
    // 8. Xuất file PDF
    $pdfFileName = "LSX-" . preg_replace('/[^a-zA-Z0-9_-]/', '', $lsx_info['SoLenhSX']) . ".pdf";
    $mpdf->Output($pdfFileName, 'I');

} catch (Throwable $e) {
    // ... (phần xử lý lỗi giữ nguyên)
}
?>
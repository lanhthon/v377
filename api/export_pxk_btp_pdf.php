<?php
/**
 * File: api/export_pxk_btp_pdf.php
 * Cập nhật: Sửa lỗi lấy mã hàng và thông tin cho phiếu xuất ngoài.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_config.php';

try {
    $pxk_id = isset($_GET['pxk_id']) ? intval($_GET['pxk_id']) : 0;
    if ($pxk_id === 0) {
        throw new Exception('ID Phiếu xuất kho không hợp lệ.');
    }

    $pdo = get_db_connection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Cập nhật câu lệnh lấy header để có đủ thông tin cho cả 2 loại phiếu
    $sql_header = "
        SELECT 
            pxk.SoPhieuXuat, 
            pxk.NgayXuat, 
            pxk.NguoiNhan,
            dh.SoYCSX, 
            nd.HoTen AS NguoiLap,
            pxk.GhiChu
        FROM phieuxuatkho pxk
        LEFT JOIN donhang dh   ON pxk.YCSX_ID    = dh.YCSX_ID
        LEFT JOIN nguoidung nd ON pxk.NguoiTaoID = nd.UserID
        WHERE pxk.PhieuXuatKhoID = ?
        LIMIT 1
    ";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$pxk_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        throw new Exception("Không tìm thấy phiếu xuất kho với ID: " . $pxk_id);
    }

    // Cập nhật câu lệnh lấy chi tiết để lấy đúng mã hàng
    $sql_items = "
        SELECT 
            IFNULL(v.variant_sku, ct.MaHang) AS MaHang,
            IFNULL(v.variant_name, ct.TenSanPham) AS TenSanPham,
            ct.SoLuongThucXuat,
            u.name AS DonViTinh
        FROM chitiet_phieuxuatkho ct
        LEFT JOIN variants v ON ct.SanPhamID = v.variant_id
        LEFT JOIN products p ON v.product_id = p.product_id
        LEFT JOIN units u    ON p.base_unit_id = u.unit_id
        WHERE ct.PhieuXuatKhoID = ?
        ORDER BY ct.ChiTietPXK_ID ASC
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$pxk_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Render HTML template
    ob_start();
    $data = ['header' => $header, 'items' => $items];
    include __DIR__ . '/../templates/pxk_btp_pdf_template.php';
    $html = ob_get_clean();

    // Khởi tạo mPDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => __DIR__ . '/../tmp',
        'default_font' => 'dejavusans'
    ]);

    $cssPath = __DIR__ . '/../css/pnk_pdf_styles.css';
    if (file_exists($cssPath)) {
        $css = file_get_contents($cssPath);
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    }

    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

    $safeSoPhieu = trim($header['SoPhieuXuat'] ?? '') ?: 'PXK_' . $pxk_id;
    $fileName = "PXK_BTP_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $safeSoPhieu) . ".pdf";
    $mpdf->Output($fileName, 'I');

} catch (Throwable $e) {
    error_log("Lỗi khi tạo PDF PXK BTP: " . $e->getMessage());
    die("Đã xảy ra lỗi khi tạo file PDF: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

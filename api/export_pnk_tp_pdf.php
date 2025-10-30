<?php
/**
 * File: api/export_pnk_tp_pdf.php (Dompdf Version)
 * Endpoint để tạo và xuất file PDF cho một Phiếu Nhập Kho Thành Phẩm.
 * Sử dụng Dompdf - render HTML/CSS tốt nhất
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php'; 
require_once __DIR__ . '/../config/db_config.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;

try {
    // 1. Lấy ID Phiếu nhập kho từ URL
    if (!isset($_GET['pnk_id']) || !is_numeric($_GET['pnk_id'])) {
        http_response_code(400);
        die("Lỗi: ID Phiếu nhập kho không hợp lệ hoặc bị thiếu.");
    }
    $pnk_id = (int)$_GET['pnk_id'];

    $pdo = get_db_connection();
    
    // 2. Truy vấn header
    $sql_header = "
        SELECT
            pnk.PhieuNhapKhoID,
            pnk.SoPhieuNhapKho,
            pnk.NgayNhap,
            pnk.LyDoNhap,
            pnk.LoaiPhieu,
            dh.SoYCSX,
            u.HoTen AS NguoiLap
        FROM
            phieunhapkho AS pnk
        LEFT JOIN
            donhang AS dh ON pnk.YCSX_ID = dh.YCSX_ID
        LEFT JOIN
            nguoidung AS u ON pnk.NguoiTaoID = u.UserID
        WHERE
            pnk.PhieuNhapKhoID = :pnk_id
            AND (pnk.LoaiPhieu = 'nhap_tp_tu_sx' OR pnk.LoaiPhieu LIKE '%TP%' OR pnk.LoaiPhieu IS NULL);
    ";
    
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute(['pnk_id' => $pnk_id]);
    $pnk_info = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$pnk_info) {
        http_response_code(404);
        die("Lỗi: Không tìm thấy Phiếu Nhập Kho Thành Phẩm có ID là {$pnk_id}.");
    }

    // 3. Lấy chi tiết các Thành phẩm
    $sql_items = "
        SELECT
            v.variant_sku           AS MaHang,
            v.variant_name          AS TenSanPham,
            u.name                  AS DonViTinh,
            chitiet.SoLuong         AS SoLuongThucNhap,
            chitiet.SoLuongTheoDonHang,
            chitiet.GhiChu
        FROM
            chitietphieunhapkho AS chitiet
        JOIN
            variants v ON chitiet.SanPhamID = v.variant_id
        LEFT JOIN 
            products p ON v.product_id = p.product_id
        LEFT JOIN
            units u ON p.base_unit_id = u.unit_id
        WHERE
            chitiet.PhieuNhapKhoID = :pnk_id
        ORDER BY
            chitiet.ChiTietPNK_ID ASC;
    ";
    
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute(['pnk_id' => $pnk_id]);
    $pnk_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 4. Chuẩn hóa dữ liệu
    $header_data = [
        'SoPhieuNhap' => $pnk_info['SoPhieuNhapKho'] ?? 'N/A',
        'NgayNhap' => $pnk_info['NgayNhap'] ?? date('Y-m-d'),
        'LyDoNhap' => $pnk_info['LyDoNhap'] ?? 'Nhập kho thành phẩm từ sản xuất',
        'SoYCSX' => $pnk_info['SoYCSX'] ?? 'N/A',
        'NguoiLap' => $pnk_info['NguoiLap'] ?? 'N/A'
    ];
    
    $ngayNhap = new DateTime($header_data['NgayNhap']);
    $logoPath = __DIR__ . '/../logo.png';
    
    // Convert logo to base64 để embed vào PDF
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    }

    // 5. Tạo HTML với CSS inline
    $html = '
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 15mm;
        }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo {
            width: 100px;
            height: auto;
        }
        h1 {
            font-size: 18pt;
            margin: 0;
            font-weight: bold;
            text-align: right;
        }
        .date-text {
            font-style: italic;
            text-align: right;
            margin: 5px 0 0 0;
        }
        .info-section {
            width: 100%;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            margin: 15px 0;
            padding: 8px 0;
            border-collapse: collapse;
        }
        .info-section td {
            padding: 3px 5px;
            white-space: nowrap;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            width: 15%;
        }
        .info-value {
            width: 35%;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .items-table th {
            background-color: #92D050;
            color: #000;
            font-weight: bold;
            padding: 8px 5px;
            border: 1px solid #000;
            text-align: center;
            white-space: nowrap;
        }
        .items-table td {
            padding: 6px 5px;
            border: 1px solid #666;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .items-table td.allow-wrap {
            white-space: normal;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .signature-section {
            width: 100%;
            margin-top: 30px;
            text-align: center;
        }
        .signature-section table {
            width: 100%;
        }
        .signature-col {
            width: 25%;
            vertical-align: top;
            text-align: center;
        }
        .signature-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .signature-space {
            height: 50px;
        }
        .signature-name {
            font-style: italic;
            font-size: 9pt;
            color: #555;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 30%;">';
    
    if ($logoBase64) {
        $html .= '<img src="' . $logoBase64 . '" class="logo" alt="Logo">';
    }
    
    $html .= '
            </td>
            <td style="width: 70%;">
                <h1>PHIẾU NHẬP KHO THÀNH PHẨM</h1>
                <p class="date-text">Ngày ' . $ngayNhap->format('d') . ' tháng ' . $ngayNhap->format('m') . ' năm ' . $ngayNhap->format('Y') . '</p>
            </td>
        </tr>
    </table>

    <table class="info-section">
        <tr>
            <td class="info-label">Lý do nhập:</td>
            <td class="info-value" colspan="3">' . htmlspecialchars($header_data['LyDoNhap']) . '</td>
        </tr>
        <tr>
            <td class="info-label">Số:</td>
            <td class="info-value font-bold" colspan="3">' . htmlspecialchars($header_data['SoPhieuNhap']) . '</td>
        </tr>
        <tr>
            <td class="info-label">Theo YCSX số:</td>
            <td class="info-value">' . htmlspecialchars($header_data['SoYCSX']) . '</td>
            <td class="info-label">Người lập phiếu:</td>
            <td class="info-value">' . htmlspecialchars($header_data['NguoiLap']) . '</td>
        </tr>
        <tr>
            <td class="info-label">Nhập vào kho:</td>
            <td class="info-value" colspan="3">Kho Thành Phẩm</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">STT</th>
                <th style="width: 13%;">Mã Hàng</th>
                <th style="width: 30%;">Tên Thành Phẩm</th>
                <th style="width: 8%;">ĐVT</th>
                <th style="width: 12%;">SL Theo ĐH</th>
                <th style="width: 12%;">SL Thực Nhập</th>
                <th style="width: 20%;">Ghi Chú</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($pnk_items as $index => $item) {
        $html .= '
            <tr>
                <td class="text-center">' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($item['MaHang'] ?? '') . '</td>
                <td>' . htmlspecialchars($item['TenSanPham'] ?? '') . '</td>
                <td class="text-center">' . htmlspecialchars($item['DonViTinh'] ?? '') . '</td>
                <td class="text-center">' . number_format($item['SoLuongTheoDonHang'] ?? 0) . '</td>
                <td class="text-center font-bold">' . number_format($item['SoLuongThucNhap'] ?? 0) . '</td>
                <td class="allow-wrap">' . htmlspecialchars($item['GhiChu'] ?? '') . '</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>

    <div class="signature-section">
        <table>
            <tr>
                <td class="signature-col">
                    <div class="signature-title">Người lập phiếu</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">(Ký, họ tên)</div>
                </td>
                <td class="signature-col">
                    <div class="signature-title">Người giao hàng</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">(Ký, họ tên)</div>
                </td>
                <td class="signature-col">
                    <div class="signature-title">Thủ kho</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">(Ký, họ tên)</div>
                </td>
                <td class="signature-col">
                    <div class="signature-title">Kế toán trưởng</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">(Ký, họ tên)</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>';

    // 6. Tạo PDF với Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isFontSubsettingEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    
    // Thiết lập khổ giấy - A4 dọc (portrait) hoặc ngang (landscape)
    $dompdf->setPaper('A4', 'portrait'); // Đổi thành 'landscape' nếu cần giấy ngang
    
    $dompdf->render();
    
    // Xuất PDF
    $pdfFileName = "PNK-TP-" . preg_replace('/[^a-zA-Z0-9_-]/', '', $header_data['SoPhieuNhap']) . ".pdf";
    $dompdf->stream($pdfFileName, array("Attachment" => false)); // false = hiển thị trong browser

} catch (Throwable $e) {
    http_response_code(500);
    error_log("Export PDF Error: " . $e->getMessage());
    die("Lỗi: " . $e->getMessage());
}
?>
<?php
// File: api/export_cccl_pdf.php (Tối ưu layout với 3% khoảng cách)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra autoload
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die("⌐ Vendor autoload not found. Run: composer require dompdf/dompdf");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

try {
    $cccl_id = isset($_GET['cccl_id']) ? intval($_GET['cccl_id']) : 0;
    if ($cccl_id === 0) {
        throw new Exception('CCCL ID không hợp lệ.');
    }

    $pdo = get_db_connection();

    // Lấy header data
    $stmt_header = $pdo->prepare("
        SELECT
            c.SoCCCL, c.NgayCap, c.TenCongTyKhach, c.DiaChiKhach, c.TenDuAn, c.DiaChiDuAn, c.SanPham, c.NguoiKiemTra,
            d.SoYCSX,
            ct.TenCongTy AS TenCongTyGoc,
            ct.DiaChi AS DiaChiKhachGoc,
            bg.TenDuAn AS TenDuAnGoc,
            bg.DiaChiGiaoHang AS DiaChiDuAnGoc,
            u.HoTen AS NguoiKiemTraGoc
        FROM chungchi_chatluong c
        LEFT JOIN phieuxuatkho p ON c.PhieuXuatKhoID = p.PhieuXuatKhoID
        LEFT JOIN donhang d ON p.YCSX_ID = d.YCSX_ID
        LEFT JOIN baogia bg ON d.BaoGiaID = bg.BaoGiaID
        LEFT JOIN congty ct ON bg.CongTyID = ct.CongTyID
        LEFT JOIN nguoidung u ON c.NguoiLap = u.UserID
        WHERE c.CCCL_ID = :cccl_id
    ");
    $stmt_header->execute([':cccl_id' => $cccl_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        throw new Exception("Không tìm thấy CCCL với ID: " . $cccl_id);
    }

    // Áp dụng logic ưu tiên
    $header['TenCongTyKhach'] = !empty($header['TenCongTyKhach']) ? $header['TenCongTyKhach'] : $header['TenCongTyGoc'];
    $header['DiaChiKhach'] = !empty($header['DiaChiKhach']) ? $header['DiaChiKhach'] : $header['DiaChiKhachGoc'];
    $header['TenDuAn'] = !empty($header['TenDuAn']) ? $header['TenDuAn'] : $header['TenDuAnGoc'];
    $header['DiaChiDuAn'] = !empty($header['DiaChiDuAn']) ? $header['DiaChiDuAn'] : $header['DiaChiDuAnGoc'];
    $header['NguoiKiemTra'] = !empty($header['NguoiKiemTra']) ? $header['NguoiKiemTra'] : $header['NguoiKiemTraGoc'];

    // Lấy danh sách sản phẩm
    $sql_items = "
        SELECT 
            ct.MaHang, ct.TenSanPham, ct.SoLuong, ct.TieuChuanDatDuoc, ct.GhiChuChiTiet,
            COALESCE(u.name, ct.DonViTinh, 'Bộ') AS DonViTinh 
        FROM chitiet_chungchi_chatluong ct
        LEFT JOIN variants v ON ct.SanPhamID = v.variant_id
        LEFT JOIN products p ON v.product_id = p.product_id
        LEFT JOIN units u ON p.base_unit_id = u.unit_id
        WHERE ct.CCCL_ID = :cccl_id 
        ORDER BY ct.ThuTuHienThi, ct.ChiTietCCCL_ID
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([':cccl_id' => $cccl_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    
    // Sắp xếp sản phẩm như trong JS
    if (!empty($items)) {
        usort($items, function($a, $b) {
            $getRank = function($item) {
                $maHang = $item['MaHang'] ?? '';
                if (str_starts_with($maHang, 'PUR')) return 1;
                if (str_starts_with($maHang, 'ULA')) return 2;
                return 3;
            };
            return $getRank($a) <=> $getRank($b);
        });
    }

    // Format ngày
    $ngayCap = new DateTime($header['NgayCap']);
    $ngayCapFormatted = $ngayCap->format('d') . ' tháng ' . $ngayCap->format('m') . ' năm ' . $ngayCap->format('Y');
    
    // Convert logo to base64
    $logoPath = __DIR__ . '/../logo.png';
    $logoData = '';
    if (file_exists($logoPath)) {
        $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }
    
    // Tạo HTML content với table layout ổn định
    $html = '<!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title>CCCL ' . htmlspecialchars($header['SoCCCL']) . '</title>
        <style>
            @page { 
                margin: 10mm; 
                size: A4; 
            }
            
            body { 
                font-family: DejaVu Sans, sans-serif; 
                font-size: 9pt; 
                color: #333; 
                margin: 0; 
                padding: 15px;
                line-height: 1.3; 
            }
            
            /* Header Table Layout */
            .header-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
                table-layout: fixed;
                border-spacing: 0;
            }
            
            .left-header {
                background-color: #f8f9fa;
                padding: 12px;
                vertical-align: top;
                border: 1px solid #dee2e6;
            }
            
            .right-header {
                background-color: #f8f9fa;
                padding: 12px;
                vertical-align: top;
                border: 1px solid #dee2e6;
                text-align: center;
            }
            
            .info-group {
                margin-bottom: 12px;
            }
            
            .info-group p, .info-group div {
                margin: 2px 0;
                line-height: 1.3;
            }
            
            .customer-title {
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 3px;
            }
            
            .company-name {
                font-weight: bold;
                font-size: 10pt;
                margin-bottom: 3px;
            }
            
            .certificate-title {
                font-size: 13pt;
                font-weight: bold;
                color: #166534;
                text-transform: uppercase;
                margin: 8px 0;
                line-height: 1.2;
            }
            
            .logo-img {
                max-height: 80px;
                max-width: 120px;
                margin: 10px 0;
            }
            
            .manufacturer-info {
                margin-top: 10px;
                font-size: 8pt;
            }
            
            .manufacturer-info p {
                margin: 3px 0;
            }
            
            .manufacturer-address {
                font-size: 7pt;
                line-height: 1.2;
                margin-top: 5px;
            }
            
            /* Products Table */
            .products-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
                font-size: 8pt;
                table-layout: fixed;
            }
            
            .products-table th {
                padding: 6px 4px;
                border: 1px solid #000;
                background-color: #92D050;
                font-weight: bold;
                text-align: center;
                color: #000;
                font-size: 8pt;
            }
            
            .products-table td {
                padding: 4px;
                border: 1px solid #000;
                vertical-align: middle;
                word-wrap: break-word;
            }
            
            /* Column widths */
            .col-stt { width: 6%; }
            .col-mahang { width: 20%; }
            .col-tensanpham { width: 30%; }
            .col-dvt { width: 8%; }
            .col-soluong { width: 10%; }
            .col-tieuchuan { width: 12%; }
            .col-ghichu { width: 14%; }
            
            .text-center {
                text-align: center;
            }
            
            /* Ngăn xuống hàng */
            .no-wrap {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            /* Signature section - Updated for more space */
            .signature-section {
                margin-top: 60px;
                width: 100%;
            }
            
            .signature-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .signature-box {
                width: 40%;
                text-align: center;
                font-size: 9pt;
                vertical-align: top;
            }
            
            .signature-title {
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 3px;
            }
            
            .signature-subtitle {
                font-style: italic;
                font-size: 8pt;
                margin-bottom: 60px; /* Increased from 40px to 60px for more signing space */
            }
            
            .signature-name {
                font-weight: bold;
                margin-top: 80px; /* Increased from 40px to 60px */
            }
        </style>
    </head>
    <body>
        <table class="header-table">
            <tr>
                <td class="left-header" style="width: 48.5%;">
                    <div class="info-group">
                        <div><strong>Số:</strong> ' . htmlspecialchars($header['SoCCCL']) . '</div>
                        <div><strong>Ngày cấp:</strong> ' . $ngayCapFormatted . '</div>
                    </div>
                    
                    <div class="info-group">
                        <p class="customer-title">KHÁCH HÀNG:</p>
                        <p class="company-name">' . htmlspecialchars($header['TenCongTyKhach']) . '</p>
                        <div><strong>Địa chỉ khách hàng:</strong> ' . htmlspecialchars($header['DiaChiKhach']) . '</div>
                    </div>
                    
                    <div class="info-group">
                        <div><strong>Tên dự án:</strong> ' . htmlspecialchars($header['TenDuAn']) . '</div>
                        <div><strong>Địa chỉ dự án:</strong> ' . htmlspecialchars($header['DiaChiDuAn']) . '</div>
                    </div>
                    
                    <div class="info-group">
                        <div><strong>Tên sản phẩm:</strong> ' . htmlspecialchars($header['SanPham'] ?: 'Gối đỡ PU Foam và Cùm Ula 3i-Fix') . '</div>
                        <div><strong>Số YCSX gốc:</strong> ' . htmlspecialchars($header['SoYCSX']) . '</div>
                    </div>
                </td>

                <td style="width: 3%; border: none;"></td>
                
                <td class="right-header" style="width: 48.5%;">
                    <div class="certificate-title">
                        CHỨNG NHẬN XUẤT XƯỞNG<br>
                        CHẤT LƯỢNG
                    </div>';
                    
    if ($logoData) {
        $html .= '<div><img src="' . $logoData . '" alt="Logo 3i-Fix" class="logo-img"></div>';
    } else {
        $html .= '<div style="height: 80px; line-height: 80px; border: 1px dashed #ccc; margin: 10px 0;">Logo 3i-Fix</div>';
    }
    
    $html .= '<div class="manufacturer-info">
                        <p class="customer-title">NHÀ SẢN XUẤT:</p>
                        <p class="company-name">CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 3I</p>
                        <p class="manufacturer-address">
                            Địa chỉ: Số 14 Lô D31 – BT2 Tại Khu D, Khu Đô Thị Mới Hai Bên Đường Lê Trọng Tấn, Phường Dương Nội, TP Hà Nội, Việt Nam
                        </p>
                    </div>
                </td>
            </tr>
        </table>

        <table class="products-table">
            <thead>
                <tr>
                    <th class="col-stt">Stt.</th>
                    <th class="col-mahang">Mã hàng</th>
                    <th class="col-tensanpham">Tên sản phẩm</th>
                    <th class="col-dvt">ĐVT</th>
                    <th class="col-soluong">Số lượng</th>
                    <th class="col-tieuchuan">Tiêu chuẩn</th>
                    <th class="col-ghichu">Ghi chú</th>
                </tr>
            </thead>
            <tbody>';
                
    if (empty($items)) {
        $html .= '<tr><td colspan="7" class="text-center">Không có chi tiết hàng hóa.</td></tr>';
    } else {
        foreach($items as $index => $item) {
            $html .= '<tr>
                <td class="text-center">' . ($index + 1) . '</td>
                <td class="no-wrap">' . htmlspecialchars($item['MaHang']) . '</td>
                <td class="no-wrap">' . htmlspecialchars($item['TenSanPham']) . '</td>
                <td class="text-center">' . htmlspecialchars($item['DonViTinh']) . '</td>
                <td class="text-center">' . htmlspecialchars($item['SoLuong']) . '</td>
                <td class="text-center">' . htmlspecialchars($item['TieuChuanDatDuoc'] ?: 'Đạt') . '</td>
                <td>' . htmlspecialchars($item['GhiChuChiTiet']) . '</td>
            </tr>';
        }
    }
    
    $html .= '</tbody>
        </table>
        
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td style="width: 60%;"></td>
                    <td class="signature-box">
                        <p class="signature-title">TP. QUẢN LÝ CHẤT LƯỢNG</p>
                        <p class="signature-subtitle">(Ký, ghi rõ họ tên)</p>
                        <p class="signature-name">' . htmlspecialchars($header['NguoiKiemTra']) . '</p>
                    </td>
                </tr>
            </table>
        </div>
    </body>
    </html>';

    // Cấu hình và tạo PDF
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', false);
    $options->set('isFontSubsettingEnabled', true);
    $options->set('debugKeepTemp', false);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $fileName = "CCCL_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $header['SoCCCL']) . ".pdf";
    $dompdf->stream($fileName, array("Attachment" => false));

} catch (Exception $e) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
    echo "<h2 style='color: red;'>⌐ LỖI TẠO PDF</h2>";
    echo "<p><strong>Chi tiết:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Dòng:</strong> " . $e->getLine() . "</p>";
    echo "</body></html>";
    error_log("Export PDF Error: " . $e->getMessage());
}
?>
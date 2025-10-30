<?php
// File: api/export_bbgh_pdf.php (Dompdf version - copy chính xác giao diện web)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra autoload
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die("❌ Vendor autoload not found. Run: composer require dompdf/dompdf");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

try {
    $bbgh_id = isset($_GET['bbgh_id']) ? intval($_GET['bbgh_id']) : 0;
    if ($bbgh_id === 0) {
        throw new Exception('ID Biên bản Giao hàng không hợp lệ.');
    }

    $pdo = get_db_connection();

    // Sử dụng câu truy vấn và logic đồng bộ với get_bbgh_details.php
    $stmt_header = $pdo->prepare("
        SELECT
            b.SoBBGH, b.NgayTao, b.GhiChu, b.NgayGiao, b.SanPham,
            b.ChucVuNhanHang,
            
            -- Thông tin bên B (có thể đã được chỉnh sửa và lưu)
            b.TenCongTy, b.DiaChiKhachHang, b.DiaChiGiaoHang, b.NguoiNhanHang, b.SoDienThoaiNhanHang, b.DuAn,
            
            -- Thông tin bên A (có thể đã được chỉnh sửa và lưu)
            b.NguoiGiaoHang AS NguoiGiaoHangDaLuu, 
            b.SdtNguoiGiaoHang AS SdtNguoiGiaoHangDaLuu,
            
            -- Thông tin gốc bên A để fallback
            u.HoTen as TenNguoiLap,
            u.SoDienThoai as SdtNguoiLap,
            
            -- Thông tin gốc của bên B để fallback
            d.SoYCSX, d.NgayGiaoDuKien,
            ct.TenCongTy as TenCongTyGoc,
            ct.DiaChi as DiaChiCongTyGoc,
            nlh.HoTen as NguoiLienHeGoc,
            nlh.SoDiDong as SdtNguoiLienHeGoc,
            bg.TenDuAn as TenDuAnGoc,
            bg.DiaChiGiaoHang as DiaChiGiaoHangGoc,
            bg.NgayGiaoDuKien as NgayGiaoDuKienGoc,
            
            -- Thêm thông tin từ bảng variants để lấy sản phẩm
            (SELECT GROUP_CONCAT(DISTINCT v.variant_name SEPARATOR ', ') 
             FROM chitietbienbangiaohang ctbbgh 
             LEFT JOIN variants v ON ctbbgh.SanPhamID = v.variant_id 
             WHERE ctbbgh.BBGH_ID = b.BBGH_ID) as DanhSachSanPham
            
        FROM bienbangiaohang b
        LEFT JOIN donhang d ON b.YCSX_ID = d.YCSX_ID
        LEFT JOIN phieuxuatkho pxk ON b.PhieuXuatKhoID = pxk.PhieuXuatKhoID
        LEFT JOIN nguoidung u ON pxk.NguoiTaoID = u.UserID
        LEFT JOIN baogia bg ON d.BaoGiaID = bg.BaoGiaID
        LEFT JOIN congty ct ON bg.CongTyID = ct.CongTyID
        LEFT JOIN nguoilienhe nlh ON bg.NguoiLienHeID = nlh.NguoiLienHeID
        WHERE b.BBGH_ID = ?
    ");
    $stmt_header->execute([$bbgh_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        throw new Exception("Không tìm thấy BBGH với ID: " . $bbgh_id);
    }

    // Áp dụng logic ưu tiên dữ liệu đã lưu giống hệt get_bbgh_details.php
    $header['TenCongTy'] = !empty($header['TenCongTy']) ? $header['TenCongTy'] : $header['TenCongTyGoc'];
    $header['DiaChiKhach'] = !empty($header['DiaChiKhachHang']) ? $header['DiaChiKhachHang'] : (!empty($header['DiaChiCongTyGoc']) ? $header['DiaChiCongTyGoc'] : '');
    $header['NguoiNhanHang'] = !empty($header['NguoiNhanHang']) ? $header['NguoiNhanHang'] : $header['NguoiLienHeGoc'];
    $header['SoDienThoaiNhanHang'] = !empty($header['SoDienThoaiNhanHang']) ? $header['SoDienThoaiNhanHang'] : $header['SdtNguoiLienHeGoc'];
    $header['DuAn'] = !empty($header['DuAn']) ? $header['DuAn'] : $header['TenDuAnGoc'];
    $header['DiaChiGiaoHang'] = !empty($header['DiaChiGiaoHang']) ? $header['DiaChiGiaoHang'] : $header['DiaChiGiaoHangGoc'];
    $header['NguoiGiaoHangHienThi'] = !empty($header['NguoiGiaoHangDaLuu']) ? $header['NguoiGiaoHangDaLuu'] : $header['TenNguoiLap'];
    $header['SdtNguoiGiaoHangHienThi'] = !empty($header['SdtNguoiGiaoHangDaLuu']) ? $header['SdtNguoiGiaoHangDaLuu'] : $header['SdtNguoiLap'];
    $header['NgayGiaoHienThi'] = !empty($header['NgayGiao']) ? $header['NgayGiao'] : $header['NgayGiaoDuKienGoc'];
    $header['SanPhamHienThi'] = !empty($header['SanPham']) ? $header['SanPham'] : $header['DanhSachSanPham'];
    $header['ChucVuNhanHang'] = !empty($header['ChucVuNhanHang']) ? $header['ChucVuNhanHang'] : 'QL. Kho';

    // Lấy danh sách sản phẩm
    $sql_items = "
        SELECT 
            ct.MaHang, 
            ct.TenSanPham, 
            ct.SoLuong, 
            ct.SoThung, 
            ct.GhiChu,
            COALESCE(u.name, ct.DonViTinh, 'Bộ') AS DonViTinh 
        FROM chitietbienbangiaohang ct
        LEFT JOIN variants v ON ct.SanPhamID = v.variant_id
        LEFT JOIN products p ON v.product_id = p.product_id
        LEFT JOIN units u ON p.base_unit_id = u.unit_id
        WHERE ct.BBGH_ID = :bbgh_id 
        ORDER BY ct.ThuTuHienThi, ct.ChiTietBBGH_ID
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([':bbgh_id' => $bbgh_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    
    // Sắp xếp sản phẩm như logic trong JS
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
    $ngayGiao = '';
    if (!empty($header['NgayGiaoHienThi'])) {
        $date = new DateTime($header['NgayGiaoHienThi']);
        $ngayGiao = $date->format('d/m/Y');
    }
    
    // Convert logo to base64
    $logoPath = __DIR__ . '/../logo.png';
    $logoData = '';
    if (file_exists($logoPath)) {
        $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }
    
    // Tạo HTML content giống hệt giao diện web
    $html = '<!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title>BBGH ' . htmlspecialchars($header['SoBBGH']) . '</title>
        <style>
            @page { 
                margin: 10mm; 
                size: A4; 
            }
            
            body { 
                font-family: DejaVu Sans, sans-serif; 
                font-size: 8.5pt; 
                color: #333; 
                margin: 0; 
                padding: 12px;
                line-height: 1.3; 
            }
            
            /* Header Section */
            .header-table {
                width: 100%;
                border-collapse: collapse;
                border-bottom: 1px solid #ccc;
                padding-bottom: 8px;
                margin-bottom: 20px;
            }
            
            .header-left {
                width: 47%;
                vertical-align: middle;
            }
            
            .header-spacer {
                width: 6%;
            }
            
            .header-right {
                width: 47%;
                text-align: center;
                vertical-align: middle;
            }
            
            .bbgh-title {
                font-size: 14pt;
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 6px;
            }
            
            .bbgh-number {
                font-size: 10pt;
                font-weight: bold;
                margin-bottom: 3px;
            }
            
            .bbgh-date {
                font-size: 8pt;
                font-style: italic;
                color: #666;
            }
            
            .logo-img {
                max-height: 120px;
                max-width: 200px;
            }
            
            /* Party Blocks */
            .parties-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 16px;
            }
            
            .party-block {
                background-color: #f3f4f6;
                padding: 6px;
                vertical-align: top;
                font-size: 7pt;
            }
            
            .party-left {
                width: 49%;
            }
            
            .party-spacer {
                width: 2%;
            }
            
            .party-right {
                width: 49%;
            }
            
            .party-title {
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 8px;
                font-size: 7pt;
            }
            
            .company-name {
                font-weight: bold;
                margin-bottom: 6px;
                font-size: 7pt;
            }
            
            .party-info-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 8px;
            }
            
            .party-info-table td {
                padding: 2px 0;
                vertical-align: top;
            }
            
            .label-cell {
                width: 70px;
                font-weight: bold;
                font-size: 7pt;
            }
            
            /* Description */
            .description {
                margin: 12px 0;
                font-size: 8pt;
            }
            
            /* Products Table */
            .products-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                font-size: 8pt;
            }
            
            .products-table th {
                padding: 4px 6px;
                border: 2px solid #000;
                background-color: #92D050;
                font-weight: bold;
                text-align: center;
                color: #000;
            }
            
            .products-table td {
                padding: 4px 6px;
                border: 1px solid #000;
                vertical-align: middle;
            }
            
            .text-center {
                text-align: center;
            }
            
            /* No wrap for product columns */
            .no-wrap {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            /* Footer section */
            .footer-note {
                margin: 24px 0;
                font-size: 8pt;
                line-height: 1.3;
            }
            
            .signature-section {
                margin-top: 36px;
            }
            
            .signature-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .signature-box {
                width: 50%;
                text-align: center;
                vertical-align: top;
            }
            
            .signature-title {
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            
            .signature-subtitle {
                font-size: 8pt;
                font-style: italic;
                margin-bottom: 64px;
            }
            
            .signature-name {
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <!-- Header Section -->
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <h1 class="bbgh-title">BIÊN BẢN GIAO HÀNG</h1>
                    <p class="bbgh-number">Số: ' . htmlspecialchars($header['SoBBGH']) . '</p>
                    <div class="bbgh-date">
                        Ngày ' . htmlspecialchars($ngayGiao) . '
                    </div>
                </td>
                <td class="header-spacer"></td>
                <td class="header-right">';
                
    if ($logoData) {
        $html .= '<img src="' . $logoData . '" alt="3i-FIX Logo" class="logo-img">';
    } else {
        $html .= '<div style="height: 120px; line-height: 120px; border: 1px dashed #ccc;">3i-FIX Logo</div>';
    }
    
    $html .= '</td>
            </tr>
        </table>

        <!-- Parties Section -->
        <table class="parties-table">
            <tr>
                <!-- Bên Nhận Hàng (Bên B) -->
                <td class="party-block party-left">
                    <h3 class="party-title">BÊN NHẬN HÀNG (BÊN B):</h3>
                    <div class="company-name">' . htmlspecialchars($header['TenCongTy']) . '</div>
                    
                    <table class="party-info-table">
                        <tr>
                            <td class="label-cell">Địa chỉ:</td>
                            <td>' . htmlspecialchars($header['DiaChiKhach']) . '</td>
                        </tr>
                    </table>
                    
                    <table class="party-info-table">
                        <tr>
                            <td class="label-cell">Đại diện:</td>
                            <td>' . htmlspecialchars($header['NguoiNhanHang']) . '</td>
                            <td style="width: 70px; font-weight: bold; font-size: 7pt;">Điện Thoại:</td>
                            <td>' . htmlspecialchars($header['SoDienThoaiNhanHang']) . '</td>
                        </tr>
                    </table>
                    
                    <table class="party-info-table">
                        <tr>
                            <td class="label-cell">Tên dự án:</td>
                            <td>' . htmlspecialchars($header['DuAn']) . '</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Địa điểm giao hàng:</td>
                            <td>' . htmlspecialchars($header['DiaChiGiaoHang']) . '</td>
                        </tr>
                    </table>
                </td>
                
                <td class="party-spacer"></td>
                
                <!-- Bên Giao Hàng (Bên A) -->
                <td class="party-block party-right">
                    <h3 class="party-title">BÊN GIAO HÀNG (BÊN A):</h3>
                    <div class="company-name">CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 3I</div>
                    
                    <table class="party-info-table">
                        <tr>
                            <td class="label-cell">Địa chỉ:</td>
                            <td>Số 14 Lô D31 – BT2 Tại Khu D, Khu Đô Thị Mới Hai Bên Đường Lê Trọng Tấn, Phường Dương Nội, TP Hà Nội, Việt Nam</td>
                        </tr>
                    </table>
                    
                    <table class="party-info-table">
                        <tr>
                            <td class="label-cell">Đại diện:</td>
                            <td>' . htmlspecialchars($header['NguoiGiaoHangHienThi']) . '</td>
                            <td style="width: 70px; font-weight: bold; font-size: 7pt;">Điện thoại:</td>
                            <td>' . htmlspecialchars($header['SdtNguoiGiaoHangHienThi']) . '</td>
                        </tr>
                    </table>
                    
                    <table class="party-info-table">
                        <tr>
                            <td class="label-cell">Sản phẩm:</td>
                            <td>' . htmlspecialchars($header['SanPhamHienThi'] ?: 'Gối đỡ PU Foam và Cùm Ula 3i-Fix') . '</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Số YCSX gốc:</td>
                            <td>' . htmlspecialchars($header['SoYCSX']) . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        <!-- Description -->
        <div class="description">
            <p>Bên A tiến hành giao cho Bên B các loại hàng hóa có tên và số lượng chi tiết như sau:</p>
        </div>

        <!-- Products Table -->
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width: 6%;">Stt.</th>
                    <th style="width: 18%;">Mã hàng</th>
                    <th style="width: 30%;">Tên sản phẩm</th>
                    <th style="width: 8%;">ĐVT</th>
                    <th style="width: 12%;">Số lượng</th>
                    <th style="width: 12%;">Số thùng/tải</th>
                    <th style="width: 14%;">Ghi chú</th>
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
                <td class="text-center">' . htmlspecialchars($item['SoThung']) . '</td>
                <td>' . htmlspecialchars($item['GhiChu']) . '</td>
            </tr>';
        }
    }
    
    $html .= '</tbody>
        </table>
        
        <!-- Footer Note -->
        <div class="footer-note">
            <p>Hai bên cùng xác nhận hàng hóa được giao đúng số lượng và chất lượng. Biên bản được lập thành 02 bản, mỗi bên giữ 01 bản và có giá trị pháp lý như nhau.</p>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td class="signature-box">
                        <p class="signature-title">ĐẠI DIỆN BÊN GIAO</p>
                        <p class="signature-subtitle">(Ký, ghi rõ họ tên)</p>
                        <p class="signature-name">' . htmlspecialchars($header['NguoiGiaoHangHienThi']) . '</p>
                    </td>
                    <td class="signature-box">
                        <p class="signature-title">ĐẠI DIỆN BÊN NHẬN</p>
                        <p class="signature-subtitle">(Ký, họ tên)</p>
                        <p class="signature-name">' . htmlspecialchars($header['NguoiNhanHang']) . '</p>
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
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $fileName = "BBGH_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $header['SoBBGH']) . ".pdf";
    $dompdf->stream($fileName, array("Attachment" => false));

} catch (Exception $e) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
    echo "<h2 style='color: red;'>❌ LỖI TẠO PDF</h2>";
    echo "<p><strong>Chi tiết:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Dòng:</strong> " . $e->getLine() . "</p>";
    echo "</body></html>";
    error_log("Export BBGH PDF Error: " . $e->getMessage());
}
?>
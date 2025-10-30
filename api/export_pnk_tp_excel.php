<?php
/**
 * File: api/export_pnk_tp_excel.php
 * Endpoint để tạo và xuất file Excel cho một Phiếu Nhập Kho Thành Phẩm.
 * Phiên bản tối ưu cho IN GIẤY A4
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php'; 
require_once __DIR__ . '/../config/db_config.php'; 

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

try {
    // 1. Lấy ID Phiếu nhập kho từ URL
    if (!isset($_GET['pnk_id']) || !is_numeric($_GET['pnk_id'])) {
        http_response_code(400);
        die("Lỗi: ID Phiếu nhập kho không hợp lệ hoặc bị thiếu.");
    }
    $pnk_id = (int)$_GET['pnk_id'];

    $pdo = get_db_connection();
    
    // 2. Truy vấn thông tin phiếu nhập kho
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

    // 3. Truy vấn chi tiết sản phẩm
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
        'SoPhieuNhapKho' => $pnk_info['SoPhieuNhapKho'] ?? 'N/A',
        'NgayNhap' => $pnk_info['NgayNhap'] ?? date('Y-m-d'),
        'LyDoNhap' => $pnk_info['LyDoNhap'] ?? 'Nhập kho thành phẩm từ sản xuất',
        'SoYCSX' => $pnk_info['SoYCSX'] ?? 'N/A',
        'NguoiLap' => $pnk_info['NguoiLap'] ?? 'N/A'
    ];

    // 5. Tạo Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Phiếu Nhập Kho TP');

    // 6. THIẾT LẬP PAGE SETUP CHO IN A4 - TỐI ƯU
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
    $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
    
    // Fit to page - VỪA KHÍT 1 TRANG A4
    $sheet->getPageSetup()->setFitToPage(true);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0); // Không giới hạn chiều cao
    
    // Scale - Tự động thu nhỏ nếu cần
    $sheet->getPageSetup()->setScale(100);
    
    // Margins - TỐI ƯU CHO A4
    $sheet->getPageMargins()->setTop(0.4);
    $sheet->getPageMargins()->setRight(0.3);
    $sheet->getPageMargins()->setLeft(0.3);
    $sheet->getPageMargins()->setBottom(0.4);
    $sheet->getPageMargins()->setHeader(0.2);
    $sheet->getPageMargins()->setFooter(0.2);
    
    // In ở giữa trang
    $sheet->getPageSetup()->setHorizontalCentered(true);
    
    // Hiển thị gridlines khi in (tùy chọn)
    $sheet->setShowGridlines(false);
    $sheet->setPrintGridlines(false);

    // 7. ĐỘ RỘNG CỘT CỐ ĐỊNH - CHUẨN CHO A4
    // Tổng độ rộng các cột = ~180 (vừa khít A4 dọc)
    $sheet->getColumnDimension('A')->setWidth(6);   // STT
    $sheet->getColumnDimension('B')->setWidth(18);  // Mã Hàng
    $sheet->getColumnDimension('C')->setWidth(50);  // Tên Sản Phẩm (rộng nhất)
    $sheet->getColumnDimension('D')->setWidth(12);  // ĐVT
    $sheet->getColumnDimension('E')->setWidth(15);  // SL Theo ĐH
    $sheet->getColumnDimension('F')->setWidth(15);  // SL Thực Nhập
    $sheet->getColumnDimension('G')->setWidth(25);  // Ghi Chú

    // 8. Thêm Logo (nếu có)
    $logoPath = __DIR__ . '/../logo.png';
    $currentRow = 1;
    
    if (file_exists($logoPath)) {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Company Logo');
        $drawing->setPath($logoPath);
        $drawing->setHeight(80); // Giảm kích thước logo để vừa trang
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($sheet);
        
        $sheet->getRowDimension(1)->setRowHeight(60);
        $sheet->getRowDimension(2)->setRowHeight(20);
    }

    // 9. Tiêu đề và ngày tháng
    $titleCol = file_exists($logoPath) ? 'D' : 'A';
    $titleEndCol = 'G';
    
    if (file_exists($logoPath)) {
        // Tiêu đề
        $sheet->mergeCells("{$titleCol}1:{$titleEndCol}1");
        $sheet->setCellValue("{$titleCol}1", 'PHIẾU NHẬP KHO THÀNH PHẨM');
        $sheet->getStyle("{$titleCol}1")->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle("{$titleCol}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("{$titleCol}1")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        
        // Ngày tháng
        $ngayNhap = new DateTime($header_data['NgayNhap']);
        $sheet->mergeCells("{$titleCol}2:{$titleEndCol}2");
        $sheet->setCellValue("{$titleCol}2", 'Ngày ' . $ngayNhap->format('d') . ' tháng ' . $ngayNhap->format('m') . ' năm ' . $ngayNhap->format('Y'));
        $sheet->getStyle("{$titleCol}2")->getFont()->setItalic(true)->setSize(11);
        $sheet->getStyle("{$titleCol}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("{$titleCol}2")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        
        $currentRow = 2;
    } else {
        $sheet->mergeCells("{$titleCol}{$currentRow}:{$titleEndCol}{$currentRow}");
        $sheet->setCellValue("{$titleCol}{$currentRow}", 'PHIẾU NHẬP KHO THÀNH PHẨM');
        $sheet->getStyle("{$titleCol}{$currentRow}")->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle("{$titleCol}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($currentRow)->setRowHeight(20);
        
        $currentRow++;
        $ngayNhap = new DateTime($header_data['NgayNhap']);
        $sheet->mergeCells("{$titleCol}{$currentRow}:{$titleEndCol}{$currentRow}");
        $sheet->setCellValue("{$titleCol}{$currentRow}", 'Ngày ' . $ngayNhap->format('d') . ' tháng ' . $ngayNhap->format('m') . ' năm ' . $ngayNhap->format('Y'));
        $sheet->getStyle("{$titleCol}{$currentRow}")->getFont()->setItalic(true)->setSize(11);
        $sheet->getStyle("{$titleCol}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($currentRow)->setRowHeight(18);
    }

    // 10. Đường kẻ ngang đầu tiên
    $currentRow += 1;
    $hrRow = $currentRow;
    $sheet->mergeCells("A{$hrRow}:G{$hrRow}");
    $sheet->getStyle("A{$hrRow}:G{$hrRow}")->applyFromArray([
        'borders' => [
            'bottom' => [
                'borderStyle' => Border::BORDER_MEDIUM,
                'color' => ['argb' => 'FF000000']
            ]
        ]
    ]);
    $sheet->getRowDimension($hrRow)->setRowHeight(3);

    // 11. Thông tin chi tiết - KHÔNG XUỐNG HÀNG
    $currentRow += 2;
    
    // Hàng 1: Lý do nhập (chiếm full width)
    $sheet->setCellValue("A{$currentRow}", 'Lý do nhập:');
    $sheet->mergeCells("B{$currentRow}:G{$currentRow}");
    $sheet->setCellValue("B{$currentRow}", $header_data['LyDoNhap']);
    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
    $sheet->getStyle("B{$currentRow}")->getAlignment()->setWrapText(false); // KHÔNG XUỐNG HÀNG
    $sheet->getRowDimension($currentRow)->setRowHeight(16);
    
    $currentRow++;
    // Hàng 2: Số (chiếm full width)
    $sheet->setCellValue("A{$currentRow}", 'Số:');
    $sheet->mergeCells("B{$currentRow}:G{$currentRow}");
    $sheet->setCellValue("B{$currentRow}", $header_data['SoPhieuNhapKho']);
    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
    $sheet->getStyle("B{$currentRow}")->getFont()->setBold(true);
    $sheet->getStyle("B{$currentRow}")->getAlignment()->setWrapText(false); // KHÔNG XUỐNG HÀNG
    $sheet->getRowDimension($currentRow)->setRowHeight(16);
    
    $currentRow++;
    // Hàng 3: Theo YCSX số và Người lập phiếu
    $sheet->setCellValue("A{$currentRow}", 'Theo YCSX số:');
    $sheet->mergeCells("B{$currentRow}:C{$currentRow}");
    $sheet->setCellValue("B{$currentRow}", $header_data['SoYCSX']);
    $sheet->setCellValue("E{$currentRow}", 'Người lập phiếu:');
    $sheet->mergeCells("F{$currentRow}:G{$currentRow}");
    $sheet->setCellValue("F{$currentRow}", $header_data['NguoiLap']);
    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
    $sheet->getStyle("E{$currentRow}")->getFont()->setBold(true);
    $sheet->getStyle("B{$currentRow}")->getAlignment()->setWrapText(false);
    $sheet->getStyle("F{$currentRow}")->getAlignment()->setWrapText(false);
    $sheet->getRowDimension($currentRow)->setRowHeight(16);
    
    $currentRow++;
    // Hàng 4: Nhập vào kho
    $sheet->setCellValue("A{$currentRow}", 'Nhập vào kho:');
    $sheet->mergeCells("B{$currentRow}:G{$currentRow}");
    $sheet->setCellValue("B{$currentRow}", 'Kho Thành Phẩm');
    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
    $sheet->getStyle("B{$currentRow}")->getAlignment()->setWrapText(false);
    $sheet->getRowDimension($currentRow)->setRowHeight(16);

    // 12. Đường kẻ ngang thứ hai
    $currentRow += 2;
    $hrHeaderRow = $currentRow;
    $sheet->mergeCells("A{$hrHeaderRow}:G{$hrHeaderRow}");
    $sheet->getStyle("A{$hrHeaderRow}:G{$hrHeaderRow}")->applyFromArray([
        'borders' => [
            'bottom' => [
                'borderStyle' => Border::BORDER_MEDIUM,
                'color' => ['argb' => 'FF000000']
            ]
        ]
    ]);
    $sheet->getRowDimension($hrHeaderRow)->setRowHeight(3);
    
    // 13. Tạo bảng chi tiết sản phẩm
    $currentRow += 2;
    $headerRow = $currentRow;
    
    // Headers
    $headers = ['STT', 'Mã Hàng', 'Tên Thành Phẩm', 'ĐVT', 'SL Theo ĐH', 'SL Thực Nhập', 'Ghi Chú'];
    
    foreach ($headers as $index => $header) {
        $column = chr(65 + $index);
        $sheet->setCellValue("{$column}{$headerRow}", $header);
    }

    $sheet->getRowDimension($headerRow)->setRowHeight(18);

    // Style cho header - KHÔNG XUỐNG HÀNG
    $sheet->getStyle("A{$headerRow}:G{$headerRow}")->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['argb' => 'FF000000'],
            'size' => 10
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FF92D050']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => false // KHÔNG XUỐNG HÀNG
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000']
            ]
        ]
    ]);
    
    // 14. Thêm dữ liệu sản phẩm - KHÔNG XUỐNG HÀNG
    $currentRow++;
    $dataStartRow = $currentRow;
    
    if (!empty($pnk_items)) {
        foreach ($pnk_items as $index => $item) {
            $sheet->setCellValue("A{$currentRow}", $index + 1);
            $sheet->setCellValue("B{$currentRow}", $item['MaHang'] ?? '');
            $sheet->setCellValue("C{$currentRow}", $item['TenSanPham'] ?? '');
            $sheet->setCellValue("D{$currentRow}", $item['DonViTinh'] ?? 'Bộ');
            $sheet->setCellValue("E{$currentRow}", $item['SoLuongTheoDonHang'] ?? 0);
            $sheet->setCellValue("F{$currentRow}", $item['SoLuongThucNhap'] ?? 0);
            $sheet->setCellValue("G{$currentRow}", $item['GhiChu'] ?? '');
            
            // Chiều cao hàng cố định
            $sheet->getRowDimension($currentRow)->setRowHeight(16);
            
            // Căn lề và KHÔNG XUỐNG HÀNG
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(false);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(false);
            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(false);
            $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(false);
            $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(false);
            $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(false);
            $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(false);
            
            // In đậm số lượng thực nhập
            $sheet->getStyle("F{$currentRow}")->getFont()->setBold(true);
            
            $currentRow++;
        }
        
        $dataEndRow = $currentRow - 1;
        
        // Border cho bảng
        $dataRange = "A{$headerRow}:G{$dataEndRow}";
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
    } else {
        $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
        $sheet->setCellValue("A{$currentRow}", 'Không có chi tiết sản phẩm nào.');
        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($currentRow)->setRowHeight(16);
        $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);
        $currentRow++;
    }

    // 15. Phần chữ ký
    $currentRow += 3;
    $signatureRow = $currentRow;
    
    $signatureLabels = ['Người lập phiếu', 'Người giao hàng', 'Thủ kho', 'Kế toán trưởng'];
    $signatureCols = ['B', 'D', 'E', 'G'];
    
    foreach ($signatureLabels as $index => $label) {
        $col = $signatureCols[$index];
        $sheet->setCellValue("{$col}{$signatureRow}", $label);
        $sheet->getStyle("{$col}{$signatureRow}")->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle("{$col}{$signatureRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // "(Ký, họ tên)"
        $sheet->setCellValue("{$col}" . ($signatureRow + 1), '(Ký, họ tên)');
        $sheet->getStyle("{$col}" . ($signatureRow + 1))->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle("{$col}" . ($signatureRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    
    // Chiều cao cho phần chữ ký
    $sheet->getRowDimension($signatureRow)->setRowHeight(18);
    $sheet->getRowDimension($signatureRow + 1)->setRowHeight(14);
    $sheet->getRowDimension($signatureRow + 2)->setRowHeight(40); // Khoảng trống để ký
    $sheet->getRowDimension($signatureRow + 3)->setRowHeight(20);
    
    // 16. Thiết lập print area
    $lastDataRow = $signatureRow + 3;
    $sheet->getPageSetup()->setPrintArea("A1:G{$lastDataRow}");

    // 17. Xuất file Excel
    $writer = new Xlsx($spreadsheet);
    $fileName = "PNK-TP-" . preg_replace('/[^a-zA-Z0-9_-]/', '', $header_data['SoPhieuNhapKho']) . ".xlsx";
    
    // Clear output buffer
    if (ob_get_contents()) {
        ob_end_clean();
    }
    
    // Headers cho download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
    
    $writer->save('php://output');
    exit();

} catch (Throwable $e) {
    if (ob_get_contents()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    error_log("Export Excel Error: " . $e->getMessage());
    die("Lỗi khi tạo file Excel: " . $e->getMessage());
}
?>
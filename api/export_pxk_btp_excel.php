<?php
/**
 * File: api/export_pxk_btp_excel.php
 * Endpoint để xuất dữ liệu Phiếu Xuất Kho BTP ra file Excel.
 * CẬP NHẬT: Sửa lỗi không lấy được mã hàng cho phiếu xuất ngoài.
 */

ob_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    if (!isset($_GET['pxk_id']) || !is_numeric($_GET['pxk_id'])) {
        die("Lỗi: ID Phiếu xuất kho không hợp lệ.");
    }
    $pxk_id = (int)$_GET['pxk_id'];
    $pdo = get_db_connection();

    // Lấy thông tin Header, thêm NguoiNhan
    $sql_header = "SELECT 
                       pxk.SoPhieuXuat, 
                       pxk.NgayXuat, 
                       pxk.GhiChu,
                       pxk.NguoiNhan,
                       dh.SoYCSX, 
                       nd.HoTen AS NguoiLap
                   FROM phieuxuatkho pxk
                   LEFT JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID
                   LEFT JOIN nguoidung nd ON pxk.NguoiTaoID = nd.UserID
                   WHERE pxk.PhieuXuatKhoID = ?";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$pxk_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        die("Lỗi: Không tìm thấy phiếu xuất kho.");
    }

    // CẬP NHẬT: Sử dụng câu lệnh SQL mạnh mẽ hơn để lấy mã hàng
    $sql_items = "SELECT 
                    CASE 
                        WHEN v.variant_sku IS NOT NULL AND v.variant_sku != '' THEN v.variant_sku 
                        ELSE ct.MaHang 
                    END AS MaHang,
                    IFNULL(v.variant_name, ct.TenSanPham) AS TenSanPham, 
                    ct.SoLuongThucXuat, 
                    u.name as DonViTinh
                  FROM chitiet_phieuxuatkho ct
                  LEFT JOIN variants v ON ct.SanPhamID = v.variant_id
                  LEFT JOIN products p ON v.product_id = p.product_id
                  LEFT JOIN units u ON p.base_unit_id = u.unit_id
                  WHERE ct.PhieuXuatKhoID = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$pxk_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Xử lý thông tin động cho 2 loại phiếu
    $isPhieuSanXuat = !empty($header['SoYCSX']);
    $nguoiNhan = $isPhieuSanXuat ? 'Bộ phận cắt' : ($header['NguoiNhan'] ?? 'N/A');
    $lyDoXuat = $isPhieuSanXuat ? 'Xuất BTP để cắt thành phẩm' : ($header['GhiChu'] ?? 'Xuất kho BTP');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('PXK ' . ($header['SoPhieuXuat'] ?? ''));

    $logoPath = __DIR__ . '/../logo.png';
    if (file_exists($logoPath)) {
        $drawing = new Drawing();
        $drawing->setPath($logoPath);
        $drawing->setHeight(50);
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($sheet);
    }
    
    $sheet->mergeCells('B1:F2');
    $sheet->setCellValue('B1', 'PHIẾU XUẤT KHO BÁN THÀNH PHẨM');
    $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

    // Thông tin chung
    $sheet->setCellValue('A4', 'Số phiếu:')->getStyle('A4')->getFont()->setBold(true);
    $sheet->setCellValue('B4', $header['SoPhieuXuat']);
    $sheet->setCellValue('D4', 'Ngày xuất:')->getStyle('D4')->getFont()->setBold(true);
    $sheet->setCellValue('E4', date('d/m/Y', strtotime($header['NgayXuat'])));
    
    $sheet->setCellValue('A5', 'Theo YCSX số:')->getStyle('A5')->getFont()->setBold(true);
    $sheet->setCellValue('B5', $header['SoYCSX'] ?? 'N/A');
    $sheet->setCellValue('D5', 'Người lập:')->getStyle('D5')->getFont()->setBold(true);
    $sheet->setCellValue('E5', $header['NguoiLap']);

    $sheet->setCellValue('A6', 'Người nhận:')->getStyle('A6')->getFont()->setBold(true);
    $sheet->setCellValue('B6', $nguoiNhan);
    $sheet->setCellValue('A7', 'Lý do xuất:')->getStyle('A7')->getFont()->setBold(true);
    $sheet->setCellValue('B7', $lyDoXuat);
    $sheet->mergeCells('B7:F7');

    // Header bảng
    $headerRow = 9; 
    $headers = ['STT', 'Mã Hàng', 'Tên Sản Phẩm', 'ĐVT', 'Số Lượng'];
    $sheet->fromArray($headers, NULL, 'A'.$headerRow);

    $headerStyle = [
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']]
    ];
    $sheet->getStyle('A'.$headerRow.':E'.$headerRow)->applyFromArray($headerStyle);

    // Dữ liệu bảng
    $currentRow = $headerRow + 1;
    $totalQuantity = 0;
    foreach ($items as $index => $item) {
        $sheet->setCellValue('A' . $currentRow, $index + 1);
        $sheet->setCellValue('B' . $currentRow, $item['MaHang']);
        $sheet->setCellValue('C' . $currentRow, $item['TenSanPham']);
        $sheet->setCellValue('D' . $currentRow, $item['DonViTinh']);
        $sheet->setCellValue('E' . $currentRow, $item['SoLuongThucXuat']);
        $totalQuantity += (float)($item['SoLuongThucXuat'] ?? 0);
        $currentRow++;
    }
    
    // Dòng tổng cộng
    $sheet->mergeCells('A'.$currentRow.':D'.$currentRow);
    $sheet->setCellValue('A'.$currentRow, 'Tổng cộng');
    $sheet->setCellValue('E'.$currentRow, $totalQuantity);
    $sheet->getStyle('A'.$currentRow.':E'.$currentRow)->getFont()->setBold(true);
    $sheet->getStyle('A'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Định dạng và kẻ bảng
    $tableRange = 'A'.$headerRow.':E'.$currentRow;
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle('E'.($headerRow + 1).':E'.$currentRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('A'.($headerRow).':A'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D'.($headerRow).':D'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Chữ ký
    $signRow = $currentRow + 3;
    $sheet->setCellValue('A'.$signRow, 'Người lập phiếu');
    $sheet->setCellValue('B'.$signRow, 'Người nhận hàng');
    $sheet->setCellValue('C'.$signRow, 'Thủ kho');
    $sheet->setCellValue('D'.$signRow, 'Kế toán');
    $sheet->getStyle('A'.$signRow.':D'.$signRow)->getFont()->setBold(true);
    $sheet->getStyle('A'.$signRow.':D'.$signRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $sheet->setCellValue('A'.($signRow + 4), $header['NguoiLap']);
    $sheet->getStyle('A'.($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


    // Tự động điều chỉnh độ rộng cột
    foreach(range('A','E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Xuất file
    $fileName = "PXK_BTP_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $header['SoPhieuXuat']) . ".xlsx";
    
    ob_end_clean();
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
    exit();

} catch (Throwable $e) {
    ob_end_clean();
    error_log("Lỗi khi tạo Excel PXK BTP: " . $e->getMessage());
    die("Đã xảy ra lỗi khi tạo file Excel.");
}


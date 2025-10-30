<?php
/**
 * File: api/export_pnk_btp_excel.php
 * Endpoint để xuất dữ liệu Phiếu Nhập Kho BTP ra file Excel.
 * VERSION 2.0 - Cập nhật giao diện theo mẫu mới.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Đảm bảo bạn đã cài đặt PhpSpreadsheet qua composer
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    // 1. Lấy ID và kết nối CSDL
    if (!isset($_GET['pnk_btp_id']) || !is_numeric($_GET['pnk_btp_id'])) {
        die("Lỗi: ID Phiếu nhập kho không hợp lệ.");
    }
    $pnk_btp_id = (int)$_GET['pnk_btp_id'];
    $pdo = get_db_connection();

    // 2. Lấy thông tin Header
    $stmt_header = $pdo->prepare("SELECT pnk.SoPhieuNhapKhoBTP, pnk.NgayNhap, pnk.LyDoNhap, lsx.SoLenhSX, u.HoTen AS TenNguoiTao FROM phieunhapkho_btp AS pnk LEFT JOIN lenh_san_xuat AS lsx ON pnk.LenhSX_ID = lsx.LenhSX_ID LEFT JOIN nguoidung AS u ON pnk.NguoiTaoID = u.UserID WHERE pnk.PNK_BTP_ID = :id");
    $stmt_header->execute(['id' => $pnk_btp_id]);
    $pnk_info = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$pnk_info) {
        die("Lỗi: Không tìm thấy phiếu nhập kho.");
    }

    // 3. Lấy thông tin chi tiết
    $stmt_items = $pdo->prepare("SELECT v.variant_sku AS MaBTP, v.variant_name AS TenBTP, u.name AS DonViTinh, chitiet.SoLuong, chitiet.so_luong_theo_lenh_sx AS SoLuongTheoLenhSX, chitiet.GhiChu FROM chitiet_pnk_btp AS chitiet JOIN variants v ON chitiet.BTP_ID = v.variant_id LEFT JOIN products p ON v.product_id = p.product_id LEFT JOIN units u ON p.base_unit_id = u.unit_id WHERE chitiet.PNK_BTP_ID = :id ORDER BY chitiet.ChiTiet_PNKBTP_ID ASC");
    $stmt_items->execute(['id' => $pnk_btp_id]);
    $pnk_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 4. Khởi tạo Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('PNK BTP');

    // 5. Thiết lập thông tin chung và tiêu đề theo mẫu mới
    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1', 'PHIẾU NHẬP KHO BÁN THÀNH PHẨM');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells('A2:G2');
    $sheet->setCellValue('A2', 'Số: ' . $pnk_info['SoPhieuNhapKhoBTP']);
    $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('D9534F');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Thông tin chi tiết
    $sheet->setCellValue('A4', 'Người lập phiếu:');
    $sheet->setCellValue('B4', $pnk_info['TenNguoiTao']);
    $sheet->setCellValue('E4', 'Ngày nhập:');
    $sheet->setCellValue('F4', date('d/m/Y', strtotime($pnk_info['NgayNhap'])));

    $sheet->setCellValue('A5', 'Lệnh SX gốc:');
    $sheet->setCellValue('B5', $pnk_info['SoLenhSX']);
    $sheet->setCellValue('E5', 'Lý do nhập:');
    $sheet->setCellValue('F5', $pnk_info['LyDoNhap'] ?? 'Nhập kho BTP từ sản xuất');
    
    $sheet->getStyle('A4:A5')->getFont()->setBold(true);
    $sheet->getStyle('E4:E5')->getFont()->setBold(true);

    // 6. Tạo header cho bảng dữ liệu với màu nền xanh
    $headerRow = 7;
    $sheet->setCellValue('A'.$headerRow, 'STT');
    $sheet->setCellValue('B'.$headerRow, 'Mã BTP');
    $sheet->setCellValue('C'.$headerRow, 'Tên Bán Thành Phẩm');
    $sheet->setCellValue('D'.$headerRow, 'SL theo LSX');
    $sheet->setCellValue('E'.$headerRow, 'SL Thực Nhập');
    $sheet->setCellValue('F'.$headerRow, 'ĐVT');
    $sheet->setCellValue('G'.$headerRow, 'Ghi Chú');
    
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => '000000']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '92D050']]
    ];
    $sheet->getStyle('A'.$headerRow.':G'.$headerRow)->applyFromArray($headerStyle);
    $sheet->getRowDimension($headerRow)->setRowHeight(25);


    // 7. Đổ dữ liệu vào các dòng
    $row = $headerRow + 1;
    foreach ($pnk_items as $index => $item) {
        $sheet->setCellValue('A'.$row, $index + 1);
        $sheet->setCellValue('B'.$row, $item['MaBTP']);
        $sheet->setCellValue('C'.$row, $item['TenBTP']);
        $sheet->setCellValue('D'.$row, $item['SoLuongTheoLenhSX']);
        $sheet->setCellValue('E'.$row, $item['SoLuong']);
        $sheet->setCellValue('F'.$row, $item['DonViTinh']);
        $sheet->setCellValue('G'.$row, $item['GhiChu']);
        $sheet->getRowDimension($row)->setRowHeight(20);
        $row++;
    }

    // 8. Định dạng bảng dữ liệu
    $lastRow = $row - 1;
    if ($lastRow >= $headerRow + 1) {
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ];
        $sheet->getStyle('A'.($headerRow + 1).':G'.$lastRow)->applyFromArray($dataStyle);
        $sheet->getStyle('D'.($headerRow+1).':E'.$lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A'.($headerRow+1).':A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D'.($headerRow+1).':F'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    
    // 9. LÀM LẠI PHẦN KÝ TÊN
    $signatureRowStart = $lastRow + 3;
    $sheet->setCellValue('A'.$signatureRowStart, 'Người lập phiếu');
    $sheet->setCellValue('C'.$signatureRowStart, 'Người giao hàng');
    $sheet->setCellValue('E'.$signatureRowStart, 'Thủ kho');
    $sheet->setCellValue('G'.$signatureRowStart, 'Kế toán');
    $sheet->getStyle('A'.$signatureRowStart.':G'.$signatureRowStart)->getFont()->setBold(true);
    $sheet->getStyle('A'.$signatureRowStart.':G'.$signatureRowStart)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $signatureHintRow = $signatureRowStart + 1;
    $sheet->setCellValue('A'.$signatureHintRow, '(Ký, họ tên)');
    $sheet->setCellValue('C'.$signatureHintRow, '(Ký, họ tên)');
    $sheet->setCellValue('E'.$signatureHintRow, '(Ký, họ tên)');
    $sheet->setCellValue('G'.$signatureHintRow, '(Ký, họ tên)');
    $sheet->getStyle('A'.$signatureHintRow.':G'.$signatureHintRow)->getFont()->setItalic(true)->setSize(9);
    $sheet->getStyle('A'.$signatureHintRow.':G'.$signatureHintRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $signatureNameRow = $signatureRowStart + 5;
    $sheet->setCellValue('A'.$signatureNameRow, $pnk_info['TenNguoiTao']);
    $sheet->getStyle('A'.$signatureNameRow)->getFont()->setBold(true);
    $sheet->getStyle('A'.$signatureNameRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


    // 10. Tự động điều chỉnh độ rộng cột
    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(40);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(15);
    $sheet->getColumnDimension('F')->setWidth(10);
    $sheet->getColumnDimension('G')->setWidth(30);
    $sheet->getStyle('C'.($headerRow + 1).':C'.$lastRow)->getAlignment()->setWrapText(true);
    $sheet->getStyle('G'.($headerRow + 1).':G'.$lastRow)->getAlignment()->setWrapText(true);


    // 11. Xuất file
    $fileName = "PNK-BTP-" . preg_replace('/[^a-zA-Z0-9_-]/', '', $pnk_info['SoPhieuNhapKhoBTP']) . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Throwable $e) {
    error_log($e->getMessage());
    die("Có lỗi xảy ra khi tạo file Excel.");
}

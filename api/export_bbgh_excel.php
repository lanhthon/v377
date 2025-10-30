<?php
// File: api/export_bbgh_excel.php - ĐÃ SỬA LỖI: Bỏ gộp ô để AutoFit hoạt động chính xác
// Đã điều chỉnh để tối ưu in A4 và đặt logo sang trái hơn.
ob_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

function getProductSortRank($item) {
    $maHang = $item['MaHang'] ?? '';
    if (strpos($maHang, 'PUR') === 0) return 1;
    if (strpos($maHang, 'ULA') === 0) return 2;
    return 3;
}

try {
    $bbgh_id = isset($_GET['bbgh_id']) ? intval($_GET['bbgh_id']) : 0;
    if ($bbgh_id === 0) throw new Exception('ID không hợp lệ.');

    $pdo = get_db_connection();
    
    // Dữ liệu không thay đổi
    $stmt_header = $pdo->prepare("
        SELECT
            b.SoBBGH, b.NgayTao, b.GhiChu, b.NgayGiao, b.SanPham,
            b.TenCongTy, b.DiaChiKhachHang, b.DiaChiGiaoHang, b.NguoiNhanHang, b.SoDienThoaiNhanHang, b.DuAn,
            b.NguoiGiaoHang AS NguoiGiaoHangDaLuu, b.SdtNguoiGiaoHang AS SdtNguoiGiaoHangDaLuu,
            u.HoTen as TenNguoiLap, u.SoDienThoai as SdtNguoiLap,
            d.SoYCSX,
            ct.TenCongTy as TenCongTyGoc, ct.DiaChi as DiaChiCongTyGoc,
            nlh.HoTen as NguoiLienHeGoc, nlh.SoDiDong as SdtNguoiLienHeGoc,
            bg.TenDuAn as TenDuAnGoc, bg.DiaChiGiaoHang as DiaChiGiaoHangGoc
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
    if (!$header) throw new Exception("Không tìm thấy BBGH.");

    $header['TenCongTy'] = !empty($header['TenCongTy']) ? $header['TenCongTy'] : $header['TenCongTyGoc'];
    $header['DiaChiKhach'] = !empty($header['DiaChiKhachHang']) ? $header['DiaChiKhachHang'] : ($header['DiaChiCongTyGoc'] ?? '');
    $header['NguoiNhanHang'] = !empty($header['NguoiNhanHang']) ? $header['NguoiNhanHang'] : $header['NguoiLienHeGoc'];
    $header['SoDienThoaiNhanHang'] = !empty($header['SoDienThoaiNhanHang']) ? $header['SoDienThoaiNhanHang'] : $header['SdtNguoiLienHeGoc'];
    $header['NguoiGiaoHangHienThi'] = !empty($header['NguoiGiaoHangDaLuu']) ? $header['NguoiGiaoHangDaLuu'] : $header['TenNguoiLap'];
    $header['SdtNguoiGiaoHangHienThi'] = !empty($header['SdtNguoiGiaoHangDaLuu']) ? $header['SdtNguoiGiaoHangDaLuu'] : $header['SdtNguoiLap'];
    $header['DuAn'] = !empty($header['DuAn']) ? $header['DuAn'] : $header['TenDuAnGoc'];
    $header['DiaChiGiaoHang'] = !empty($header['DiaChiGiaoHang']) ? $header['DiaChiGiaoHang'] : $header['DiaChiGiaoHangGoc'];

    $sql_items = "
        SELECT ct.MaHang, ct.TenSanPham, ct.SoLuong, ct.SoThung, ct.GhiChu,
               COALESCE(u.name, ct.DonViTinh, 'Bộ') AS DonViTinh 
        FROM chitietbienbangiaohang ct
        LEFT JOIN variants v ON ct.SanPhamID = v.variant_id
        LEFT JOIN products p ON v.product_id = p.product_id
        LEFT JOIN units u ON p.base_unit_id = u.unit_id
        WHERE ct.BBGH_ID = :bbgh_id ORDER BY ct.ThuTuHienThi, ct.ChiTietBBGH_ID
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([':bbgh_id' => $bbgh_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($items)) {
        usort($items, function($a, $b) {
            return getProductSortRank($a) <=> getProductSortRank($b);
        });
    }

    $ngayTao = new DateTime($header['NgayTao']);
    $ngayTaoFormatted = 'Ngày ' . $ngayTao->format('d') . ' tháng ' . $ngayTao->format('m') . ' năm ' . $ngayTao->format('Y');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("BBGH " . $header['SoBBGH']);
    
    $sheet->getStyle('A1:G200')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('A1:G200')->getFont()->setSize(12);

    // Bố cục Header mới (7 cột A-G)
    $sheet->mergeCells('A1:D2')->setCellValue('A1', 'BIÊN BẢN GIAO HÀNG');
    $sheet->getStyle('A1:D2')->getFont()->setBold(true)->setSize(16);
    $sheet->mergeCells('A3:D3')->setCellValue('A3', 'Số: ' . $header['SoBBGH']);
    $sheet->getStyle('A3:D3')->getFont()->setBold(true);
    $sheet->mergeCells('A4:D4')->setCellValue('A4', $ngayTaoFormatted)->getStyle('A4:D4')->getFont()->setItalic(true);
    
    try {
        $logoPath = __DIR__ . '/../logo.png';
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(80);
            $drawing->setCoordinates('F1');
            $sheet->mergeCells('F1:G4');
            
            // --- [ĐIỀU CHỈNH: DỊCH LOGO SANG TRÁI] ---
            // Giảm giá trị này để dịch chuyển logo sang trái.
            $drawing->setOffsetX(10); 
            
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
        }
    } catch (Exception $e) {}

    $currentRow = 6;
    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    $currentRow += 1;
    
    // Khối thông tin mới
    $partyStartRow = $currentRow;
    
    // Tiêu đề
    $sheet->mergeCells("A{$currentRow}:C{$currentRow}")->setCellValue("A{$currentRow}", 'BÊN NHẬN HÀNG (BÊN B):');
    $sheet->mergeCells("E{$currentRow}:G{$currentRow}")->setCellValue("E{$currentRow}", 'BÊN GIAO HÀNG (BÊN A):');
    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->getFont()->setBold(true);
    $currentRow++;

    // Tên công ty
    $sheet->mergeCells("A{$currentRow}:C{$currentRow}")->setCellValue("A{$currentRow}", $header['TenCongTy'] ?? '');
    $sheet->mergeCells("E{$currentRow}:G{$currentRow}")->setCellValue("E{$currentRow}", 'CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 3I');
    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->getFont()->setBold(true);
    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->getAlignment()->setWrapText(true);
    $currentRow++;
    
    $createRichText = function($label, $value) {
        $richText = new RichText();
        $textRunLabel = $richText->createTextRun($label);
        $textRunLabel->getFont()->setBold(true)->setSize(12);
        $textRunValue = $richText->createTextRun($value);
        $textRunValue->getFont()->setSize(12);
        return $richText;
    };
    
    // Địa chỉ
    $sheet->mergeCells("A{$currentRow}:C{$currentRow}")->setCellValue("A{$currentRow}", $createRichText('Địa chỉ: ', $header['DiaChiKhach']));
    $sheet->mergeCells("E{$currentRow}:G{$currentRow}")->setCellValue("E{$currentRow}", $createRichText('Địa chỉ: ', 'Số 14 Lô D31 – BT2 Tại Khu D, KĐT Mới Hai Bên Đường Lê Trọng Tấn, P. Dương Nội, TP Hà Nội, Việt Nam'));
    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->getAlignment()->setWrapText(true);
    $currentRow++;

    // Đại diện
    $daiDienA = ($header['NguoiGiaoHangHienThi'] ?? '') . (!empty($header['SdtNguoiGiaoHangHienThi']) ? ' - ĐT: ' . $header['SdtNguoiGiaoHangHienThi'] : '');
    $daiDienB = ($header['NguoiNhanHang'] ?? '') . (!empty($header['SoDienThoaiNhanHang']) ? ' - ĐT: ' . $header['SoDienThoaiNhanHang'] : '');
    $sheet->mergeCells("A{$currentRow}:C{$currentRow}")->setCellValue("A{$currentRow}", $createRichText('Đại diện: ', $daiDienB));
    $sheet->mergeCells("E{$currentRow}:G{$currentRow}")->setCellValue("E{$currentRow}", $createRichText('Đại diện: ', $daiDienA));
    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->getAlignment()->setWrapText(true);
    $currentRow++;

    // Thông tin còn lại
    $sheet->mergeCells("A{$currentRow}:C{$currentRow}")->setCellValue("A{$currentRow}", $createRichText('Tên dự án: ', $header['DuAn'] ?? ''));
    $sheet->mergeCells("E{$currentRow}:G{$currentRow}")->setCellValue("E{$currentRow}", $createRichText('Số YCSX gốc: ', $header['SoYCSX'] ?? ''));
    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->getAlignment()->setWrapText(true);
    $currentRow++;
    
    $sheet->mergeCells("A{$currentRow}:C{$currentRow}")->setCellValue("A{$currentRow}", $createRichText('Địa điểm GH: ', $header['DiaChiGiaoHang'] ?? ''));
    $sheet->getStyle("A{$currentRow}:C{$currentRow}")->getAlignment()->setWrapText(true);

    $partyEndRow = $currentRow;
    $sheet->getStyle("A{$partyStartRow}:C{$partyEndRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
    $sheet->getStyle("E{$partyStartRow}:G{$partyEndRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
    $currentRow += 2;
    
    $sheet->mergeCells("A{$currentRow}:G{$currentRow}")->setCellValue("A{$currentRow}", 'Bên A tiến hành giao cho Bên B các loại hàng hóa có tên và số lượng chi tiết như sau:');
    $currentRow += 2;

    // Bảng sản phẩm
    $tableHeaderRow = $currentRow;
    $sheet->setCellValue('A' . $tableHeaderRow, 'Stt.');
    $sheet->setCellValue('B' . $tableHeaderRow, 'Tên sản phẩm');
    $sheet->setCellValue('C' . $tableHeaderRow, 'Mã hàng');
    $sheet->setCellValue('D' . $tableHeaderRow, 'ĐVT');
    $sheet->setCellValue('E' . $tableHeaderRow, 'Số lượng');
    $sheet->setCellValue('F' . $tableHeaderRow, 'Số thùng/tải');
    $sheet->setCellValue('G' . $tableHeaderRow, 'Ghi chú');
    $sheet->getStyle("A{$tableHeaderRow}:G{$tableHeaderRow}")->applyFromArray([
        'font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF92D050']] ]);
    
    $currentRow++;
    $itemStyle = [ 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]] ];
    foreach ($items as $index => $item) {
        $sheet->setCellValue('A' . $currentRow, $index + 1);
        $sheet->setCellValue('B' . $currentRow, $item['TenSanPham']);
        $sheet->setCellValue('C' . $currentRow, $item['MaHang']);
        $sheet->setCellValue('D' . $currentRow, $item['DonViTinh']);
        $sheet->setCellValue('E' . $currentRow, $item['SoLuong']);
        $sheet->setCellValue('F' . $currentRow, $item['SoThung'] ?? '');
        $sheet->setCellValue('G' . $currentRow, $item['GhiChu'] ?? '');
        $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray($itemStyle);
        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$currentRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("G{$currentRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension($currentRow)->setRowHeight(-1);
        $currentRow++;
    }

    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $currentRow++;
    $sheet->mergeCells("A{$currentRow}:G{$currentRow}")->setCellValue("A{$currentRow}", 'Hai bên cùng xác nhận hàng hóa được giao đúng số lượng và chất lượng. Biên bản được lập thành 02 bản, mỗi bên giữ 01 bản và có giá trị pháp lý như nhau.');
    $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);
    $currentRow++;
    
    // Phần ký tên
    $currentRow += 2;
    $sigRow1 = $currentRow;
    $sheet->mergeCells("A{$sigRow1}:C{$sigRow1}")->setCellValue("A{$sigRow1}", 'ĐẠI DIỆN BÊN NHẬN');
    $sheet->mergeCells("E{$sigRow1}:G{$sigRow1}")->setCellValue("E{$sigRow1}", 'ĐẠI DIỆN BÊN GIAO');
    $sheet->getStyle("A{$sigRow1}:G{$sigRow1}")->getFont()->setBold(true);
    $sheet->getStyle("A{$sigRow1}:G{$sigRow1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $currentRow++;
    $sigRow2 = $currentRow;
    $sheet->mergeCells("A{$sigRow2}:C{$sigRow2}")->setCellValue("A{$sigRow2}", '(Ký, họ tên)');
    $sheet->mergeCells("E{$sigRow2}:G{$sigRow2}")->setCellValue("E{$sigRow2}", '(Ký, ghi rõ họ tên)');
    $sheet->getStyle("A{$sigRow2}:G{$sigRow2}")->getFont()->setItalic(true);
    $sheet->getStyle("A{$sigRow2}:G{$sigRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $currentRow += 4;
    $sigRow3 = $currentRow;
    $sheet->mergeCells("A{$sigRow3}:C{$sigRow3}")->setCellValue("A{$sigRow3}", $header['NguoiNhanHang'] ?? '');
    $sheet->mergeCells("E{$sigRow3}:G{$sigRow3}")->setCellValue("E{$sigRow3}", $header['NguoiGiaoHangHienThi'] ?? '');
    $sheet->getStyle("A{$sigRow3}:G{$sigRow3}")->getFont()->setBold(true);
    $sheet->getStyle("A{$sigRow3}:G{$sigRow3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $lastRow = $sigRow3;

    // Cài đặt in A4
    $sheet->getPageSetup()
          ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
          ->setPaperSize(PageSetup::PAPERSIZE_A4);
    
    $sheet->getPageSetup()
          ->setFitToPage(true)
          ->setFitToWidth(1)
          ->setFitToHeight(0);
    
    $sheet->getPageMargins()
          ->setTop(0.75)
          ->setBottom(0.75)
          ->setLeft(0.7)
          ->setRight(0.7);
          
    $sheet->getPageSetup()->setHorizontalCentered(true);
    $sheet->getPageSetup()->setPrintArea('A1:G' . $lastRow);
    
    $fileName = "BBGH_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $header['SoBBGH']) . ".xlsx";
    ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Throwable $e) {
    ob_end_clean();
    ini_set('display_errors', 1); error_reporting(E_ALL);
    echo "Lỗi khi tạo file Excel: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>"; echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    error_log("Export BBGH Excel Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
}
?>
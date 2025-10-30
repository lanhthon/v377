<?php
/**
 * api/export_excel.php
 * Cập nhật để áp dụng các thay đổi về định dạng:
 * - Sửa lỗi mất chữ ở tên công ty và các cột số.
 * - Căn lề lại cột Mã hàng.
 * - Tăng chiều rộng cột B.
 * - Bỏ phần thập phân .00 ở các số.
 * - Tự động xuống hàng cho tên công ty dài.
 * - Tăng font size cho dễ đọc.
 * - Xử lý logic cho mã hàng 'DT' không kèm ghi chú.
 * - THÊM: Đổi màu viền bảng sang màu xám nhạt.
 * - CẬP NHẬT: Tối ưu cho in ấn, fit to page và gộp ô thông tin để tránh mất chữ
 * - CẬP NHẬT: Dùng cột F làm khoảng trống, khối phải bắt đầu từ G.
 * - CẬP NHẬT: Cân bằng lại độ rộng cột để khối A-E và G-J có chiều rộng tương đương.
 * - CẬP NHẬT: Quay lại sử dụng RichText, dùng dấu cách để tạo khoảng trống.
 * - THÊM: Chức năng gom nhóm theo Khu Vực.
 * - HOÀN TÁC: Quay lại giao diện header và khối thông tin cũ theo yêu cầu.
 * - CẬP NHẬT: In đậm QUOTATION, căn giữa logo H-I, điều chỉnh vị trí thông tin công ty
 * - CẬP NHẬT: Thu nhỏ ảnh sản phẩm, đổi tên file thành "Số: [số báo giá]"
 * - SỬA: Khôi phục "Đại diện bên bán hàng" bị mất
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

// === HÀM HỖ TRỢ ĐỌC SỐ ===
function numberToWords($number) {
    if ($number === null || !is_numeric($number) || $number == 0) return "Không đồng chẵn";
    $number = round($number);
    $mangso = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
    $dochangchuc = function($so, $daydu) use ($mangso) {
        $chuoi = ""; $chuc = floor($so / 10); $donvi = $so % 10;
        if ($chuc > 1) { $chuoi = " " . $mangso[$chuc] . " mươi"; if ($donvi == 1) { $chuoi .= " mốt"; } }
        else if ($chuc == 1) { $chuoi = " mười"; if ($donvi == 1) { $chuoi .= " một"; } }
        else if ($daydu && $donvi > 0) { $chuoi = " lẻ"; }
        if ($donvi == 5 && $chuc > 1) { $chuoi .= " lăm"; }
        else if ($donvi > 1 || ($donvi == 1 && $chuc == 0)) { $chuoi .= " " . $mangso[$donvi]; }
        return $chuoi;
    };
    $docblock = function($so, $daydu) use ($mangso, $dochangchuc) {
        $chuoi = ""; $tram = floor($so / 100); $so = $so % 100;
        if ($daydu || $tram > 0) { $chuoi = " " . $mangso[$tram] . " trăm"; $chuoi .= $dochangchuc($so, true); }
        else { $chuoi = $dochangchuc($so, false); }
        return $chuoi;
    };
    $dochangtrieu = function($so, $daydu) use ($docblock) {
        $chuoi = ""; $trieu = floor($so / 1000000); $so = $so % 1000000;
        if ($trieu > 0) { $chuoi = $docblock($trieu, $daydu) . " triệu"; $daydu = true; }
        $nghin = floor($so / 1000); $so = $so % 1000;
        if ($nghin > 0) { $chuoi .= $docblock($nghin, $daydu) . " nghìn"; $daydu = true; }
        if ($so > 0) { $chuoi .= $docblock($so, $daydu); }
        return $chuoi;
    };
    $chuoi = ""; $hauto = "";
    do {
        $ty = $number % 1000000000; $number = floor($number / 1000000000);
        if ($number > 0) { $chuoi = $dochangtrieu($ty, true) . $hauto . $chuoi; }
        else { $chuoi = $dochangtrieu($ty, false) . $hauto . $chuoi; }
        $hauto = " tỷ";
    } while ($number > 0);
    $chuoi = trim($chuoi);
    if (strlen($chuoi) > 0) { $chuoi = mb_strtoupper(mb_substr($chuoi, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($chuoi, 1, null, 'UTF-8'); }
    return $chuoi . " đồng chẵn";
}

function calculateRowHeight($text, $font_size, $col_width_pixels) {
    if (empty($text)) return 15;
    $char_width_pixels = ($font_size / 10) * 6;
    if ($char_width_pixels <= 0) return 15;
    $chars_per_line = floor($col_width_pixels / $char_width_pixels);
    if ($chars_per_line <= 0) return 15;
    $lines = 0;
    $text_lines = explode("\n", $text);
    foreach ($text_lines as $text_line) {
        if (empty($text_line)) { $lines++; continue; }
        $wrapped_text = wordwrap($text_line, $chars_per_line, "\n", true);
        $lines += substr_count($wrapped_text, "\n") + 1;
    }
    return 15 + (($lines - 1) * 12);
}

try {
    // 1. LẤY DỮ LIỆU
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { http_response_code(400); echo "Lỗi: ID báo giá không hợp lệ."; exit(); }
    $baoGiaID = (int)$_GET['id'];
    $conn->set_charset("utf8mb4");

    // LẤY DỮ LIỆU NHÃN
    $stmt_labels = $conn->prepare("SELECT label_key, label_vi FROM quotation_labels");
    $stmt_labels->execute();
    $result_labels = $stmt_labels->get_result();
    $labels_raw = $result_labels->fetch_all(MYSQLI_ASSOC);
    $stmt_labels->close();
    $labels = [];
    foreach ($labels_raw as $row) { $labels[$row['label_key']] = $row['label_vi']; }
    $e = function($key) use ($labels) {
        $value = $labels[$key] ?? "[$key]"; return str_ireplace('<br>', "\n", $value);
    };

    // LẤY THÔNG TIN BÁO GIÁ
    $stmt_quote = $conn->prepare("SELECT * FROM baogia WHERE BaoGiaID = ?");
    $stmt_quote->bind_param("i", $baoGiaID);
    $stmt_quote->execute();
    $result_quote = $stmt_quote->get_result();
    $quote_info = $result_quote->fetch_assoc();
    $stmt_quote->close();
    if (!$quote_info) { http_response_code(404); echo "Lỗi: Không tìm thấy báo giá."; exit(); }

    // LẤY CHI TIẾT BÁO GIÁ
    $stmt_items = $conn->prepare("SELECT * FROM chitietbaogia WHERE BaoGiaID = ? ORDER BY KhuVuc, ThuTuHienThi ASC");
    $stmt_items->bind_param("i", $baoGiaID);
    $stmt_items->execute();
    $items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();
    $conn->close();

    // Phân nhóm sản phẩm theo KHU VỰC và theo LOẠI (PUR/ULA)
    $itemsByArea = [];
    foreach ($items as $item) {
        $areaName = !empty($item['KhuVuc']) ? $item['KhuVuc'] : 'Chung';
        if (!isset($itemsByArea[$areaName])) {
            $itemsByArea[$areaName] = ['pur' => [], 'ula' => []];
        }
        if (strpos(strtoupper($item['MaHang'] ?? ''), 'PUR') === 0) {
            $itemsByArea[$areaName]['pur'][] = $item;
        } else {
            $itemsByArea[$areaName]['ula'][] = $item;
        }
    }

    // 2. KHỞI TẠO VÀ CẤU HÌNH EXCEL
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("BaoGia");
    $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(12);
    
    $pageSetup = $sheet->getPageSetup();
    $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
        ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
        ->setFitToWidth(1)
        ->setFitToHeight(0); // Allow multiple pages vertically
    
    $sheet->getPageMargins()->setTop(0.2)->setRight(0.2)->setLeft(0.2)->setBottom(0.2);

    // Bố cục cột
    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setAutoSize(true);
    $sheet->getColumnDimension('C')->setWidth(9);
    $sheet->getColumnDimension('D')->setWidth(9);
    $sheet->getColumnDimension('E')->setWidth(12);
    $sheet->getColumnDimension('F')->setWidth(9);
    $sheet->getColumnDimension('G')->setWidth(13);
    $sheet->getColumnDimension('H')->setWidth(13);
    $sheet->getColumnDimension('I')->setWidth(13);
    $sheet->getColumnDimension('J')->setAutoSize(true);

    $col_widths_pixels = [
        'A' => 37, 'B' => 135, 'C' => 67, 'D' => 67, 'E' => 90,
        'F' => 67, 'G' => 97, 'H' => 97, 'I' => 97, 'J' => 150
    ];

    // 3. ĐỊNH NGHĨA STYLES
    $styles = [
        'bold' => ['font' => ['bold' => true]],
        'italic' => ['font' => ['italic' => true, 'size' => 12]],
        'blue_text_bold' => ['font' => ['color' => ['rgb' => '4387CA'], 'bold' => true, 'size' => 12]],
        'border_thin_ccc' => ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]],
        'info_box' => ['font' => ['size' => 12], 'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBE5F1']]],
        'table_header' => ['font' => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 12], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]],
        'area_header' => ['font' => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 12], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFD966']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]],
        'group_header' => ['font' => ['bold' => true, 'size' => 12], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]],
        'shipping_header' => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2F0D9']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]],
        'shipping_row' => ['font' => ['size' => 12], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2F0D9']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]],
        'totals_box' => ['font' => ['size' => 12], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']]],
        'notes_box' => ['font' => ['size' => 11], 'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP, 'horizontal' => Alignment::HORIZONTAL_LEFT], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']]],
    ];
    
    // 4. VẼ HEADER
    $sheet->getRowDimension(1)->setRowHeight(30); 
    $sheet->getRowDimension(2)->setRowHeight(25); 
    $sheet->getRowDimension(3)->setRowHeight(25);
    $sheet->getRowDimension(4)->setRowHeight(22); 
    $sheet->getRowDimension(5)->setRowHeight(22);
    
    // IN ĐẬM QUOTATION
    $sheet->mergeCells('A1:E1')->setCellValue('A1', 'QUOTATION');
    $quotationStyle = $sheet->getStyle('A1');
    $quotationStyle->getFont()->setSize(20)->setBold(true);
    $quotationStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $sheet->mergeCells('A2:E2')->setCellValue('A2', $e('document_title'))->getStyle('A2')->applyFromArray($styles['italic'])->getFont()->setSize(16);
    $sheet->mergeCells('A3:E3')->setCellValue('A3', $e('sales_confirmation'))->getStyle('A3')->applyFromArray($styles['italic'])->getFont()->setSize(16);
    $sheet->getStyle('A2:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A4', $e('quote_no') . ':')->getStyle('A4')->applyFromArray($styles['bold']);
    $sheet->setCellValue('A5', $e('quote_date') . ':')->getStyle('A5')->applyFromArray($styles['bold']);
    $sheet->setCellValue('B5', date('d/m/Y', strtotime($quote_info['NgayBaoGia'] ?? 'now')));
    $sheet->getStyle('A4:B5')->getFont()->setSize(12);
    
    $sheet->setCellValue('B4', $quote_info['SoBaoGia'] ?? '');
    $styleB4 = $sheet->getStyle('B4');
    $styleB4->getFont()->setBold(true);
    $styleB4->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEE00');

    // CĂNG GIỮA LOGO Ở CỘT H VÀ I
    $sheet->mergeCells('H1:I5');
    $logoPath = __DIR__ . '/../logo.png';
    if (file_exists($logoPath)) {
        $drawing = new Drawing();
        $drawing->setPath($logoPath);
        $drawing->setCoordinates('H1');
        $drawing->setWidth(180);
        $drawing->setOffsetX(35);  // Điều chỉnh để căn giữa cột H và I
        $drawing->setOffsetY(5);
        $drawing->setWorksheet($sheet);
    }

    // 5. VẼ KHỐI THÔNG TIN
    $currentRow = 7;
    $pad_length_left = 25;
    $pad_length_right = 20;

    // Khối thông tin khách hàng (A-E)
    $sheet->getStyle("A{$currentRow}:E" . ($currentRow + 5))->applyFromArray($styles['info_box']);
    $sheet->mergeCells("A".($currentRow).":E".($currentRow + 5));
    $customerText = new RichText();
    $customerText->createTextRun(str_pad($e('customer_to') . ":", $pad_length_left, ' '))->getFont()->setBold(true);
    $customerText->createTextRun(($quote_info['TenCongTy'] ?? '') . "\n")->getFont()->setBold(true);
    $customerText->createTextRun(str_pad($e('customer_address') . ":", $pad_length_left, ' '))->getFont()->setBold(true);
    $customerText->createTextRun(($quote_info['DiaChiKhach'] ?? '') . "\n");
    $customerText->createTextRun(str_pad($e('customer_contact_person') . ":", $pad_length_left, ' '))->getFont()->setBold(true);
    $customerText->createTextRun(($quote_info['NguoiNhan'] ?? '') . "      ");
    $customerText->createTextRun($e('supplier_phone') . ": ")->getFont()->setBold(true);
    $customerText->createTextRun(($quote_info['SoDiDongKhach'] ?? '') . "\n");
    $customerText->createTextRun(str_pad($e('item_category') . ":", $pad_length_left, ' '))->getFont()->setBold(true);
    $customerText->createTextRun(($quote_info['HangMuc'] ?? '') . "\n");
    $customerText->createTextRun(str_pad($e('project_name') . ":", $pad_length_left, ' '))->getFont()->setBold(true);
    $customerText->createTextRun($quote_info['TenDuAn'] ?? '');
    $sheet->setCellValue("A{$currentRow}", $customerText);

    // Khối thông tin nhà cung cấp (G-J)
    $sheet->getStyle("G{$currentRow}:J" . ($currentRow + 5))->applyFromArray($styles['info_box']);
    $sheet->mergeCells("G{$currentRow}:J{$currentRow}");
    $sheet->setCellValue("G{$currentRow}", $e('supplier_name'));
    $styleHeaderRight = $sheet->getStyle("G{$currentRow}");
    $styleHeaderRight->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $styleHeaderRight->applyFromArray(['font' => ['bold' => true, 'size' => 16]]);
    
    $sheet->mergeCells("G".($currentRow+1).":I".($currentRow + 5));
    $supplierText = new RichText();
    $supplierText->createTextRun(str_pad($e('supplier_contact_person') . ':', $pad_length_right, ' '))->getFont()->setBold(true);
    $supplierText->createTextRun(($quote_info['NguoiBaoGia'] ?? '') . "\n");
    $supplierText->createTextRun(str_pad($e('supplier_position') . ':', $pad_length_right, ' '))->getFont()->setBold(true);
    $supplierText->createTextRun(($quote_info['ChucVuNguoiBaoGia'] ?? '') . "\n");
    $supplierText->createTextRun(str_pad($e('supplier_phone') . ':', $pad_length_right, ' '))->getFont()->setBold(true);
    $supplierText->createTextRun(($quote_info['DiDongNguoiBaoGia'] ?? '') . "\n");
    $supplierText->createTextRun(str_pad($e('quote_validity') . ':', $pad_length_right, ' '))->getFont()->setBold(true);
    $supplierText->createTextRun($quote_info['HieuLucBaoGia'] ?? '');
    $sheet->setCellValue('G'.($currentRow+1), $supplierText);
    
    $dbQrPath = !empty($quote_info['HinhAnh2']) ? strtolower($quote_info['HinhAnh2']) : 'uploads/qr.png';
    $qrPath = __DIR__ . '/../' . $dbQrPath;
    if(file_exists($qrPath)){
        $drawingQR = new Drawing(); 
        $drawingQR->setPath($qrPath); 
        $drawingQR->setCoordinates('J'.($currentRow+1)); 
        $drawingQR->setHeight(80); 
        $drawingQR->setOffsetX(5); 
        $drawingQR->setOffsetY(1); 
        $drawingQR->setWorksheet($sheet);
    }
    
    // Đặt chiều cao cho hàng tiêu đề của ô thông tin
    $sheet->getRowDimension($currentRow)->setRowHeight(25);
    // Phân phối chiều cao còn lại cho các hàng khác trong ô được gộp
    for ($i = 1; $i < 6; $i++) {
        $sheet->getRowDimension($currentRow + $i)->setRowHeight(18);
    }
    
    $currentRow += 7;

    // 6. VẼ BẢNG SẢN PHẨM
    $drawTableHeader = function($sheet, &$currentRow, $title1, $colId, $colThickness, $colWidth) use ($styles, $e) {
        $startRow = $currentRow;
        $sheet->getRowDimension($startRow)->setRowHeight(32); 
        $sheet->getRowDimension($startRow + 1)->setRowHeight(32);
        $sheet->mergeCells("A{$startRow}:A".($startRow+1))->setCellValue("A{$startRow}", $e('col_stt'));
        $sheet->mergeCells("B{$startRow}:B".($startRow+1))->setCellValue("B{$startRow}", $e('col_product_code'));
        $sheet->mergeCells("C{$startRow}:E{$startRow}")->setCellValue("C{$startRow}", $title1);
        $sheet->setCellValue("C".($startRow+1), $e($colId)); 
        $sheet->setCellValue("D".($startRow+1), $e($colThickness));
        $sheet->setCellValue("E".($startRow+1), $e($colWidth));
        $sheet->mergeCells("F{$startRow}:F".($startRow+1))->setCellValue("F{$startRow}", $e('col_unit'));
        $sheet->mergeCells("G{$startRow}:G".($startRow+1))->setCellValue("G{$startRow}", $e('col_quantity'));
        $sheet->setCellValue("H{$startRow}", $e('col_unit_price'))->setCellValue("H".($startRow+1), $e('currency_unit_short'));
        $sheet->setCellValue("I{$startRow}", $e('col_line_total'))->setCellValue("I".($startRow+1), $e('currency_unit_short'));
        $sheet->mergeCells("J{$startRow}:J".($startRow+1))->setCellValue("J{$startRow}", $e('col_notes'));
        $sheet->getStyle("A{$startRow}:J".($startRow+1))->applyFromArray($styles['table_header']);
        $currentRow += 2;
    };

    $writeProductGroup = function($sheet, &$currentRow, $items, $noteSuffix, $col_widths_pixels) use ($styles, $e) {
        if(empty($items)) return;
        $current_group_name = null; $stt = 1;
        foreach($items as $item) {
            if(($item['TenNhom'] ?? '') !== $current_group_name) {
                $current_group_name = $item['TenNhom'] ?? ''; $stt = 1;
                $finalNoteSuffix = $noteSuffix; 
                $maHangPrefix_2_chars = isset($item['MaHang']) ? strtoupper(substr($item['MaHang'], 0, 2)) : '';
                if ($maHangPrefix_2_chars === 'DT') { $finalNoteSuffix = ''; }
                $sheet->mergeCells("A{$currentRow}:J{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", htmlspecialchars($current_group_name) . $finalNoteSuffix);
                $sheet->getStyle("A{$currentRow}")->applyFromArray($styles['group_header']);
                $sheet->getRowDimension($currentRow)->setRowHeight(24); 
                $currentRow++;
            }
            $sheet->setCellValue('A'.$currentRow, $stt++);
            $sheet->setCellValue('B'.$currentRow, $item['MaHang'] ?? '');
            $sheet->setCellValue('C'.$currentRow, $item['ID_ThongSo'] ?? '');
            $sheet->setCellValue('D'.$currentRow, $item['DoDay'] ?? '');
            $sheet->setCellValue('E'.$currentRow, $item['ChieuRong'] ?? '');
            $sheet->setCellValue('F'.$currentRow, $item['DonViTinh'] ?? $e('unit_set'));
            $sheet->setCellValue('G'.$currentRow, $item['SoLuong'] ?? 0);
            $sheet->setCellValue('H'.$currentRow, $item['DonGia'] ?? 0);
            $sheet->setCellValue('I'.$currentRow, $item['ThanhTien'] ?? 0);
            $sheet->setCellValue('J'.$currentRow, $item['GhiChu'] ?? '');
            $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray($styles['border_thin_ccc']);
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$currentRow}:G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H'.$currentRow.':I'.$currentRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('J'.$currentRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($currentRow)->setRowHeight(max(20, calculateRowHeight($item['GhiChu'] ?? '', 12, $col_widths_pixels['J'])));
            $currentRow++;
        }
    };

    foreach ($itemsByArea as $areaName => $areaGroup) {
        if ($areaName !== 'Chung' || count($itemsByArea) > 1) {
            $sheet->mergeCells("A{$currentRow}:J{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", $areaName);
            $sheet->getStyle("A{$currentRow}")->applyFromArray($styles['area_header']);
            $sheet->getRowDimension($currentRow)->setRowHeight(24);
            $currentRow++;
        }

        $pur_items = $areaGroup['pur'];
        $ula_items = $areaGroup['ula'];

        if (!empty($pur_items)) {
            $drawTableHeader($sheet, $currentRow, $e('col_pur_dimensions'), 'col_pur_id', 'col_pur_thickness', 'col_pur_width');
            $writeProductGroup($sheet, $currentRow, $pur_items, '', $col_widths_pixels);
        }
        if (!empty($ula_items)) {
            if (!empty($pur_items)) {
                $sheet->getRowDimension($currentRow)->setRowHeight(10);
                $currentRow++;
            }
            $drawTableHeader($sheet, $currentRow, $e('col_ula_dimensions'), 'col_ula_id', 'col_ula_thickness', 'col_ula_width');
            $writeProductGroup($sheet, $currentRow, $ula_items, ' ' . $e('includes_two_nuts'), $col_widths_pixels);
        }
    }

    $sheet->mergeCells("A{$currentRow}:J{$currentRow}")->setCellValue("A{$currentRow}", $e('shipping_fee_header'));
    $sheet->getStyle("A{$currentRow}")->applyFromArray($styles['shipping_header']);
    $sheet->getRowDimension($currentRow)->setRowHeight(24); $currentRow++;
    
    $sheet->setCellValue('A'.$currentRow, '1');
    $sheet->setCellValue('B'.$currentRow, $e('shipping_unit_trip'));
    $sheet->setCellValue('G'.$currentRow, $quote_info['SoLuongVanChuyen'] ?? 0);
    $sheet->setCellValue('H'.$currentRow, $quote_info['DonGiaVanChuyen'] ?? 0);
    $sheet->setCellValue('I'.$currentRow, ($quote_info['SoLuongVanChuyen'] ?? 0) * ($quote_info['DonGiaVanChuyen'] ?? 0));
    $sheet->setCellValue('J'.$currentRow, $quote_info['GhiChuVanChuyen'] ?? '');
    $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray($styles['shipping_row']);
    $sheet->getStyle('A'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('G'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('H'.$currentRow.':I'.$currentRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('J'.$currentRow)->getAlignment()->setWrapText(true);
    $sheet->getRowDimension($currentRow)->setRowHeight(max(22, calculateRowHeight($quote_info['GhiChuVanChuyen'] ?? '', 12, $col_widths_pixels['J'])));
    $currentRow += 2;

    // 7. VẼ FOOTER
    $footerStartRow = $currentRow;
    
    $sheet->getStyle("G{$footerStartRow}:J".($footerStartRow+4))->applyFromArray($styles['totals_box']);
    $sheet->mergeCells("G{$footerStartRow}:H{$footerStartRow}");
    $sheet->setCellValue("G{$footerStartRow}", $e('total_pre_tax') . ':')->getStyle("G{$footerStartRow}")->applyFromArray($styles['blue_text_bold'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    
    $sheet->setCellValue("I{$footerStartRow}", $quote_info['TongTienTruocThue'] ?? 0);
    $styleTotalPreTax = $sheet->getStyle("I{$footerStartRow}");
    $styleTotalPreTax->getFont()->setBold(true);
    $styleTotalPreTax->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    $sheet->mergeCells("G".($footerStartRow+1).":H".($footerStartRow+1));
    $tax_percentage_text = rtrim(rtrim(number_format((float)($quote_info['ThuePhanTram'] ?? 8), 2, '.', ''), '0'), '.');
    $sheet->setCellValue("G".($footerStartRow+1), $e('total_vat') . ' (' . $tax_percentage_text . '%):')->getStyle("G".($footerStartRow+1))->applyFromArray($styles['blue_text_bold'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->setCellValue("I".($footerStartRow+1), $quote_info['ThueVAT'] ?? 0)->getStyle("I".($footerStartRow+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $sheet->getStyle("G".($footerStartRow+2).":J".($footerStartRow+2))->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
    $sheet->mergeCells("G".($footerStartRow+3).":H".($footerStartRow+3));
    $sheet->setCellValue("G".($footerStartRow+3), $e('total_after_tax') . ':')->getStyle("G".($footerStartRow+3))->applyFromArray($styles['blue_text_bold'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    
    $sheet->setCellValue("I".($footerStartRow+3), $quote_info['TongTienSauThue'] ?? 0);
    $styleTotalAfterTax = $sheet->getStyle("I".($footerStartRow+3));
    $styleTotalAfterTax->getFont()->setBold(true);
    $styleTotalAfterTax->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $sheet->getStyle("I{$footerStartRow}:I".($footerStartRow+3))->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle("G".($footerStartRow+3).":J".($footerStartRow+3))->getFont()->setSize(12);
    
    $sheet->mergeCells("G".($footerStartRow+4).":J".($footerStartRow+4));
    $amountInWordsText = new RichText();
    $blueLabel = $amountInWordsText->createTextRun($e('amount_in_words') . ': ');
    $blueLabel->getFont()->setBold(true)->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('4387CA'));
    $blackValue = $amountInWordsText->createTextRun(numberToWords($quote_info['TongTienSauThue'] ?? 0));
    $blackValue->getFont()->setBold(true)->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK));
    $sheet->setCellValue("G".($footerStartRow+4), $amountInWordsText);
    $sheet->getStyle("G".($footerStartRow+4))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT);

    $sheet->getRowDimension($footerStartRow)->setRowHeight(22);
    $sheet->getRowDimension($footerStartRow+1)->setRowHeight(22);
    $sheet->getRowDimension($footerStartRow+2)->setRowHeight(10);
    $sheet->getRowDimension($footerStartRow+3)->setRowHeight(25);
    $sheet->getRowDimension($footerStartRow+4)->setRowHeight(calculateRowHeight($e('amount_in_words') . ': ' . numberToWords($quote_info['TongTienSauThue'] ?? 0), 12, $col_widths_pixels['G']+$col_widths_pixels['H']+$col_widths_pixels['I']+$col_widths_pixels['J']));

    // ẢNH SẢN PHẨM NHỎ XÍU
    $dbProdPath = !empty($quote_info['HinhAnh1']) ? strtolower($quote_info['HinhAnh1']) : 'uploads/default_image.png';
    $productImagePath = __DIR__ . '/../' . $dbProdPath;
    if(file_exists($productImagePath)){
        $drawingProd = new Drawing(); 
        $drawingProd->setPath($productImagePath); 
        $drawingProd->setCoordinates('B'.$footerStartRow);
        $drawingProd->setHeight(250); // Thu nhỏ ảnh sản phẩm từ 280 xuống 80
        $drawingProd->setOffsetX(5); 
        $drawingProd->setOffsetY(5); 
        $drawingProd->setWorksheet($sheet);
    }
    
    // ĐIỀU CHỈNH VỊ TRÍ THÔNG TIN CÔNG TY VÀ XUẤT XỨ ĐỂ KẾT THÚC CÙNG HÀNG
    $notesEndRow = $footerStartRow + 15; // Hàng kết thúc chung cho cả hai khối
    
    // Phần thông tin xuất xứ, giao hàng, thanh toán (bên trái) - 5 hàng
    $notesRowLeft = $notesEndRow - 4; // Bắt đầu từ hàng để kết thúc tại notesEndRow
    $sheet->mergeCells("A{$notesRowLeft}:E{$notesEndRow}")->getStyle("A{$notesRowLeft}:E{$notesEndRow}")->applyFromArray($styles['notes_box']);
    $notesRichText = new RichText();
    $notesRichText->createTextRun("- Xuất xứ: ")->getFont()->setBold(true)->setSize(11);
    $notesRichText->createTextRun("3iGreen\n")->getFont()->setSize(11);
    $notesRichText->createTextRun("- T.gian giao hàng: ")->getFont()->setBold(true)->setSize(11);
    $notesRichText->createTextRun("3-5 ngày sau khi nhận được xác nhận đặt hàng.\n")->getFont()->setSize(11);
    $notesRichText->createTextRun("- Điều kiện thanh toán: ")->getFont()->setBold(true)->setSize(11);
    $notesRichText->createTextRun("Theo thỏa thuận\n")->getFont()->setSize(11);
    $notesRichText->createTextRun("- Địa điểm giao hàng: ")->getFont()->setBold(true)->setSize(11);
    $notesRichText->createTextRun(($quote_info['DiaChiGiaoHang'] ?? ''))->getFont()->setSize(11);
    $sheet->setCellValue("A{$notesRowLeft}", $notesRichText);

    // Thông tin công ty (bên phải) - 10 hàng nhưng kết thúc cùng hàng với bên trái
    $notesRowRight = $notesEndRow - 9; // Bắt đầu từ hàng để kết thúc tại notesEndRow
    $sheet->mergeCells("G{$notesRowRight}:J{$notesEndRow}")->getStyle("G{$notesRowRight}:J{$notesEndRow}")->applyFromArray($styles['notes_box']);
    $companyInfoRichText = new RichText();
    $companyInfoRichText->createTextRun("CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 3I\n")->getFont()->setBold(true)->setSize(12);
    $companyInfoRichText->createTextRun("Địa chỉ: Số 14 Lô D31 – BT2 Tại Khu D, Khu Đô Thị Mới Hai Bên Đường Lê Trọng Tấn, Phường Dương Nội, TP Hà Nội, Việt Nam\n")->getFont()->setSize(11);
    $companyInfoRichText->createTextRun("MST: 0110886479\n")->getFont()->setBold(true)->setSize(11);
    $companyInfoRichText->createTextRun("Thông tin chuyển khoản:\n")->getFont()->setBold(true)->setSize(11);
    $companyInfoRichText->createTextRun("Chủ tài khoản: Công ty TNHH sản xuất và ứng dụng vật liệu xanh 3i\n")->getFont()->setSize(11);
    $companyInfoRichText->createTextRun("Số tài khoản: 46668888, Ngân hàng TMCP Hàng Hải Việt Nam (MSB) - chi nhánh Thanh Xuân")->getFont()->setSize(11);
    $sheet->setCellValue("G{$notesRowRight}", $companyInfoRichText);
    
    // CHỮ KÝ - SỬA LỖI "ĐẠI DIỆN BÊN BÁN HÀNG" BỊ MẤT
    $signatureRow = $notesEndRow + 2; // Chữ ký xuất hiện sau khi cả hai khối kết thúc
    $sheet->getRowDimension($signatureRow)->setRowHeight(24);
    $sheet->mergeCells("B{$signatureRow}:D{$signatureRow}");
    $sheet->setCellValue('B'.$signatureRow, 'Đại diện bên mua hàng');
    $sheet->getStyle('B'.$signatureRow)->getFont()->setBold(true);
    $sheet->getStyle('B'.$signatureRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells("H{$signatureRow}:J{$signatureRow}");
    $sheet->setCellValue("H{$signatureRow}", 'Đại diện bên bán hàng');
    $sheet->getStyle("H{$signatureRow}")->getFont()->setBold(true);
    $sheet->getStyle("H{$signatureRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // 8. XUẤT FILE VỚI TÊN "SỐ: [SỐ BÁO GIÁ]"
    $fileName = "Số " . ($quote_info['SoBaoGia'] ?? 'test') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Throwable $e) {
    http_response_code(500);
    error_log("Lỗi khi tạo Excel: " . $e->getMessage() . " tại " . $e->getFile() . " dòng " . $e->getLine());
    echo "Đã xảy ra lỗi trong quá trình tạo file Excel: " . htmlspecialchars($e->getMessage()) . ". Vui lòng thử lại sau.";
}
?>
<?php
/**
 * api/export_excel_TQ.php (v43 - Dynamic Tax & Hide Empty Tables)
 * - Cập nhật để hiển thị phần trăm thuế VAT động từ CSDL cho cả hai ngôn ngữ.
 * - Đảm bảo các bảng sản phẩm (PUR/ULA) được ẩn đi nếu không có dữ liệu.
 */

// Bật hiển thị lỗi để gỡ lỗi (nên tắt ở môi trường production)
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
use PhpOffice\PhpSpreadsheet\Style\Color;

// === HÀM HỖ TRỢ ĐỌC SỐ TIẾNG VIỆT ===
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

// === HÀM HỖ TRỢ ĐỌC SỐ TIẾNG TRUNG ===
function numberToWords_zh($number) {
    if ($number === null || !is_numeric($number) || $number == 0) return "零元整";
    $number = round($number);
    $chi_num = ['零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖'];
    $chi_uni = ['', '拾', '佰', '仟'];
    $chi_uni_s = ['', '万', '亿'];
    $str = '';
    $num_str = (string)$number;
    $count = strlen($num_str);
    $zero = false;
    $unit_idx = 0;
    for ($i = $count - 1; $i >= 0; $i--) {
        $p = ($count - 1) - $i;
        if ($p % 4 == 0) {
            $unit_idx = $p / 4;
            $str = $chi_uni_s[$unit_idx] . $str;
            $zero = false;
        }
        $n = $num_str[$i];
        if ($n == '0') {
            $zero = true;
        } else {
            if ($zero) {
                $str = $chi_num[0] . $str;
                $zero = false;
            }
            $str = $chi_num[$n] . $chi_uni[$p % 4] . $str;
        }
    }
    $str = preg_replace('/(零万|零亿)/', '', $str);
    $str = preg_replace('/(亿)万/', '$1', $str);
    $str = preg_replace('/零{2,}/', '零', $str);
    $str = preg_replace('/零$/', '', $str);
    if (strpos($str, '壹拾') === 0) {
        $str = substr($str, 3);
    }
    return $str . '元整';
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

    // LẤY DỮ LIỆU NHÃN SONG NGỮ
    $stmt_labels = $conn->prepare("SELECT label_key, label_vi, label_zh FROM quotation_labels");
    $stmt_labels->execute();
    $result_labels = $stmt_labels->get_result();
    $labels_raw = $result_labels->fetch_all(MYSQLI_ASSOC);
    $stmt_labels->close();
    $labels = [];
    foreach ($labels_raw as $row) {
        $labels[$row['label_key']] = [
            'vi' => $row['label_vi'],
            'zh' => $row['label_zh']
        ];
    }
    
    // HÀM HELPER HIỂN THỊ SONG NGỮ
    $e_bilingual = function($key, $separator = "\n") use ($labels) {
        $vi = $labels[$key]['vi'] ?? "[$key_vi]";
        $zh = $labels[$key]['zh'] ?? "[$key_zh]";
        $vi = str_ireplace('<br>', "\n", $vi);
        $zh = str_ireplace('<br>', "\n", $zh);
        return "{$vi}{$separator}{$zh}";
    };
    
    // HÀM HELPER TẠO RICHTEXT SONG NGỮ
    $createBilingualRichText = function($key) use ($labels) {
        $vi = $labels[$key]['vi'] ?? "[$key_vi]";
        $zh = $labels[$key]['zh'] ?? "[$key_zh]";
        $vi = str_ireplace('<br>', "\n", $vi);
        $zh = str_ireplace('<br>', "\n", $zh);

        $richText = new RichText();
        $rt_vi = $richText->createTextRun($vi);
        
        $rt_zh = $richText->createTextRun("\n" . $zh);
        $rt_zh->getFont()->setSize(9)->setItalic(true)->setColor(new Color('FF696969'));
        
        return $richText;
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
    $stmt_items = $conn->prepare("SELECT * FROM chitietbaogia WHERE BaoGiaID = ? ORDER BY ThuTuHienThi ASC");
    $stmt_items->bind_param("i", $baoGiaID);
    $stmt_items->execute();
    $items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();
    $conn->close();

    // Phân loại sản phẩm
    $pur_items = []; $ula_items = [];
    foreach ($items as $item) { (strtoupper(substr($item['MaHang'] ?? '', 0, 3)) === 'PUR') ? $pur_items[] = $item : $ula_items[] = $item; }

    // 2. KHỞI TẠO VÀ CẤU HÌNH EXCEL
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("BaoGia");
    $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
        ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
        ->setFitToWidth(1)->setFitToHeight(0);
    $sheet->getPageMargins()->setTop(0.5)->setRight(0.5)->setLeft(0.5)->setBottom(0.5);

    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setWidth(22);
    $sheet->getColumnDimension('C')->setWidth(11);
    $sheet->getColumnDimension('D')->setWidth(11);
    $sheet->getColumnDimension('E')->setWidth(11);
    $sheet->getColumnDimension('F')->setWidth(8);
    $sheet->getColumnDimension('G')->setAutoSize(true);
    $sheet->getColumnDimension('H')->setWidth(12);
    $sheet->getColumnDimension('I')->setWidth(15);
    $sheet->getColumnDimension('J')->setWidth(20);

    $col_widths_pixels = [
        'A' => 38, 'B' => 165, 'C' => 82, 'D' => 82, 'E' => 82,
        'F' => 60, 'G' => 75, 'H' => 90, 'I' => 110, 'J' => 150
    ];

    // 3. ĐỊNH NGHĨA STYLES
    $styles = [
        'bold' => ['font' => ['bold' => true]], 'italic' => ['font' => ['italic' => true]],
        'blue_text_bold' => ['font' => ['color' => ['rgb' => '4387CA'], 'bold' => true]],
        'border_thin_ccc' => ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]],
        'info_box' => ['font' => ['size' => 10], 'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBE5F1']]],
        'table_header' => ['font' => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 10], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]],
        'shipping_header' => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2F0D9']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]],
        'shipping_row' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2F0D9']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]],
        'totals_box' => ['font' => ['size' => 10], 'alignment' => ['wrapText' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']]],
        'notes_box' => ['font' => ['size' => 9], 'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP, 'horizontal' => Alignment::HORIZONTAL_LEFT], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']]],
    ];
    
    // 4. VẼ HEADER
    $sheet->getRowDimension(1)->setRowHeight(35); $sheet->getRowDimension(2)->setRowHeight(30); $sheet->getRowDimension(3)->setRowHeight(30);
    $sheet->getRowDimension(4)->setRowHeight(30); $sheet->getRowDimension(5)->setRowHeight(18);
    
    $sheet->mergeCells('A1:E1')->setCellValue('A1', $e_bilingual('document_title', ' / '))->getStyle('A1')->applyFromArray($styles['bold'])->getFont()->setSize(18);
    $sheet->mergeCells('A2:E2')->setCellValue('A2', $e_bilingual('document_title', ' / '))->getStyle('A2')->applyFromArray($styles['italic'])->getFont()->setSize(14);
    $sheet->mergeCells('A3:E3')->setCellValue('A3', $e_bilingual('sales_confirmation', ' / '))->getStyle('A3')->applyFromArray($styles['italic'])->getFont()->setSize(14);
    $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

    $sheet->setCellValue('A4', $createBilingualRichText('quote_no'))->getStyle('A4')->applyFromArray($styles['bold'])->getAlignment()->setWrapText(true);
    $sheet->setCellValue('B4', $quote_info['SoBaoGia'] ?? '')->getStyle('B4')->applyFromArray($styles['bold'])->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEE00');
    $sheet->setCellValue('A5', $e_bilingual('quote_date', ' / '))->getStyle('A5')->applyFromArray($styles['bold']);
    $sheet->setCellValue('B5', date('d/m/Y', strtotime($quote_info['NgayBaoGia'] ?? 'now')));
    $sheet->getStyle('A4:B5')->getFont()->setSize(11);
    $sheet->getStyle('A4:A5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('H1:I5');
    $logoPath = __DIR__ . '/../logo.png';
    if (file_exists($logoPath)) {
        $drawing = new Drawing();
        $drawing->setPath($logoPath);
        $drawing->setCoordinates('H1');
        $drawing->setWidth(160);
        $drawing->setOffsetX(15);
        $drawing->setOffsetY(5);
        $drawing->setWorksheet($sheet);
    }

    // 5. VẼ KHỐI THÔNG TIN
    $currentRow = 7;
    $sheet->getRowDimension($currentRow)->setRowHeight(22);
    for($i = 1; $i <= 5; $i++) { $sheet->getRowDimension($currentRow + $i)->setRowHeight(30); }

    // KHỐI BÊN TRÁI (A-E)
    $sheet->getStyle("A{$currentRow}:E" . ($currentRow + 5))->applyFromArray($styles['info_box']);
    $sheet->setCellValue('A'.($currentRow+1), $createBilingualRichText('customer_to'))->getStyle('A'.($currentRow+1))->applyFromArray($styles['bold']);
    $sheet->mergeCells("B".($currentRow+1).":E".($currentRow+1));
    $sheet->setCellValue('B'.($currentRow+1), $quote_info['TenCongTy'] ?? '')->getStyle('B'.($currentRow+1))->applyFromArray($styles['bold']);
    $sheet->setCellValue('A'.($currentRow+2), $createBilingualRichText('customer_address'))->getStyle('A'.($currentRow+2))->applyFromArray($styles['bold']);
    $sheet->mergeCells("B".($currentRow+2).":E".($currentRow+2));
    $sheet->setCellValue('B'.($currentRow+2), $quote_info['DiaChiKhach'] ?? '');
    
    $contactPersonRichText = new RichText();
    $contactPersonRichText->createTextRun($e_bilingual('customer_contact_person', " / ") . ': ')->getFont()->setBold(true);
    $contactPersonRichText->createTextRun($quote_info['NguoiNhan'] ?? '');
    $sheet->mergeCells("A".($currentRow+3).":B".($currentRow+3));
    $sheet->setCellValue('A'.($currentRow+3), $contactPersonRichText);
    
    $phoneRichText = new RichText();
    $phoneRichText->createTextRun($e_bilingual('supplier_phone', " / ") . ': ')->getFont()->setBold(true);
    $phoneRichText->createTextRun($quote_info['SoDiDongKhach'] ?? '');
    $sheet->mergeCells("C".($currentRow+3).":E".($currentRow+3));
    $sheet->setCellValue('C'.($currentRow+3), $phoneRichText);

    $sheet->setCellValue('A'.($currentRow+4), $createBilingualRichText('item_category'))->getStyle('A'.($currentRow+4))->applyFromArray($styles['bold']);
    $sheet->mergeCells("B".($currentRow+4).":E".($currentRow+4));
    $sheet->setCellValue('B'.($currentRow+4), $quote_info['HangMuc'] ?? '');
    $sheet->setCellValue('A'.($currentRow+5), $createBilingualRichText('project_name'))->getStyle('A'.($currentRow+5))->applyFromArray($styles['bold']);
    $sheet->mergeCells("B".($currentRow+5).":E".($currentRow+5));
    $sheet->setCellValue('B'.($currentRow+5), $quote_info['TenDuAn'] ?? '');

    // KHỐI BÊN PHẢI (G-J)
    $sheet->getStyle("G{$currentRow}:J" . ($currentRow + 5))->applyFromArray($styles['info_box']);
    $sheet->mergeCells("G{$currentRow}:J{$currentRow}");
    $sheet->setCellValue("G{$currentRow}", $labels['supplier_name']['zh'] ?? '[supplier_name_zh]');
    $styleHeaderRight = $sheet->getStyle("G{$currentRow}");
    $styleHeaderRight->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(true);
    $styleHeaderRight->applyFromArray($styles['bold']);
    $styleHeaderRight->getFont()->setSize(14);
    
    $sheet->setCellValue('G'.($currentRow+1), $createBilingualRichText('supplier_contact_person'))->getStyle('G'.($currentRow+1))->applyFromArray($styles['bold']);
    $sheet->mergeCells("H".($currentRow+1).":I".($currentRow+1));
    $sheet->setCellValue('H'.($currentRow+1), $quote_info['NguoiBaoGia'] ?? '');
    $sheet->setCellValue('G'.($currentRow+2), $createBilingualRichText('supplier_position'))->getStyle('G'.($currentRow+2))->applyFromArray($styles['bold']);
    $sheet->mergeCells("H".($currentRow+2).":I".($currentRow+2));
    $sheet->setCellValue('H'.($currentRow+2), $quote_info['ChucVuNguoiBaoGia'] ?? '');
    $sheet->setCellValue('G'.($currentRow+3), $createBilingualRichText('supplier_phone'))->getStyle('G'.($currentRow+3))->applyFromArray($styles['bold']);
    $sheet->mergeCells("H".($currentRow+3).":I".($currentRow+3));
    $sheet->setCellValue('H'.($currentRow+3), $quote_info['DiDongNguoiBaoGia'] ?? '');
    $sheet->setCellValue('G'.($currentRow+4), $createBilingualRichText('quote_validity'))->getStyle('G'.($currentRow+4))->applyFromArray($styles['bold']);
    $sheet->mergeCells("H".($currentRow+4).":I".($currentRow+4));
    $sheet->setCellValue('H'.($currentRow+4), $quote_info['HieuLucBaoGia'] ?? '');
    
    $dbQrPath = !empty($quote_info['HinhAnh2']) ? strtolower($quote_info['HinhAnh2']) : 'uploads/qr.png';
    $qrPath = __DIR__ . '/../' . $dbQrPath;
    if(file_exists($qrPath)){
        $drawingQR = new Drawing(); $drawingQR->setPath($qrPath); $drawingQR->setCoordinates('J'.($currentRow+1)); $drawingQR->setHeight(70);
        $drawingQR->setOffsetX(10); $drawingQR->setOffsetY(5); $drawingQR->setWorksheet($sheet);
    }
    
    $sheet->getRowDimension($currentRow+2)->setRowHeight(max(30, calculateRowHeight($quote_info['DiaChiKhach'] ?? '', 10, $col_widths_pixels['B'] + $col_widths_pixels['C'] + $col_widths_pixels['D'] + $col_widths_pixels['E'])));
    $sheet->getRowDimension($currentRow+4)->setRowHeight(max(30, calculateRowHeight($quote_info['HangMuc'] ?? '', 10, $col_widths_pixels['B'] + $col_widths_pixels['C'] + $col_widths_pixels['D'] + $col_widths_pixels['E'])));
    $sheet->getRowDimension($currentRow+5)->setRowHeight(max(30, calculateRowHeight($quote_info['TenDuAn'] ?? '', 10, $col_widths_pixels['B'] + $col_widths_pixels['C'] + $col_widths_pixels['D'] + $col_widths_pixels['E'])));
    $currentRow += 7;

    // 6. VẼ BẢNG SẢN PHẨM
    $drawTableHeader = function($sheet, &$currentRow, $title1_key, $colId_key, $colThickness_key, $colWidth_key) use ($styles, $e_bilingual, $labels) {
        $startRow = $currentRow;
        $sheet->getRowDimension($startRow)->setRowHeight(45); $sheet->getRowDimension($startRow + 1)->setRowHeight(18);
        $sheet->mergeCells("A{$startRow}:A".($startRow+1))->setCellValue("A{$startRow}", $e_bilingual('col_stt'));
        $sheet->mergeCells("B{$startRow}:B".($startRow+1))->setCellValue("B{$startRow}", $e_bilingual('col_product_code'));
        
        $sheet->mergeCells("C{$startRow}:E{$startRow}")->setCellValue("C{$startRow}", $e_bilingual($title1_key));
        $sheet->getStyle("C{$startRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        $sheet->setCellValue("C".($startRow+1), $e_bilingual($colId_key));
        $sheet->setCellValue("D".($startRow+1), $e_bilingual($colThickness_key));
        $sheet->setCellValue("E".($startRow+1), $e_bilingual($colWidth_key));
        $sheet->mergeCells("F{$startRow}:F".($startRow+1))->setCellValue("F{$startRow}", $e_bilingual('col_unit'));
        $sheet->mergeCells("G{$startRow}:G".($startRow+1))->setCellValue("G{$startRow}", $e_bilingual('col_quantity'));
        $sheet->setCellValue("H{$startRow}", $e_bilingual('col_unit_price'))->setCellValue("H".($startRow+1), $labels['currency_unit_short']['vi'] ?? 'VND');
        $sheet->setCellValue("I{$startRow}", $e_bilingual('col_line_total'))->setCellValue("I".($startRow+1), $labels['currency_unit_short']['vi'] ?? 'VND');
        $sheet->mergeCells("J{$startRow}:J".($startRow+1))->setCellValue("J{$startRow}", $e_bilingual('col_notes'));
        $sheet->getStyle("A{$startRow}:J".($startRow+1))->applyFromArray($styles['table_header']);
        $currentRow += 2;
    };

    $writeProductGroup = function($sheet, &$currentRow, $items, $noteSuffixKey, $col_widths_pixels) use ($styles, $e_bilingual) {
        if(empty($items)) return;
        $current_group_name = null; $stt = 1;
        foreach($items as $item) {
            if(($item['TenNhom'] ?? '') !== $current_group_name) {
                $current_group_name = $item['TenNhom'] ?? ''; $stt = 1;
                $sheet->mergeCells("A{$currentRow}:J{$currentRow}");
                $group_text = htmlspecialchars($current_group_name);
                if (!empty($noteSuffixKey)) {
                    $group_text .= ' (' . $e_bilingual($noteSuffixKey, ' - ') . ')';
                }
                $sheet->setCellValue("A{$currentRow}", $group_text);
                $styleGroupRow = $sheet->getStyle("A{$currentRow}:J{$currentRow}");
                $styleGroupRow->applyFromArray($styles['bold']);
                $styleGroupRow->applyFromArray($styles['border_thin_ccc']);
                $sheet->getRowDimension($currentRow)->setRowHeight(20); $currentRow++;
            }
            $sheet->setCellValue('A'.$currentRow, $stt++);
            $sheet->setCellValue('B'.$currentRow, $item['MaHang'] ?? '');
            $sheet->setCellValue('C'.$currentRow, $item['ID_ThongSo'] ?? '');
            $sheet->setCellValue('D'.$currentRow, $item['DoDay'] ?? '');
            $sheet->setCellValue('E'.$currentRow, $item['ChieuRong'] ?? '');
            $sheet->setCellValue('F'.$currentRow, $e_bilingual('unit_set', '/'));
            $sheet->setCellValue('G'.$currentRow, $item['SoLuong'] ?? 0);
            $sheet->setCellValue('H'.$currentRow, $item['DonGia'] ?? 0);
            $sheet->setCellValue('I'.$currentRow, $item['ThanhTien'] ?? 0);
            $sheet->setCellValue('J'.$currentRow, $item['GhiChu'] ?? '');
            $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray($styles['border_thin_ccc']);
            $sheet->getStyle("A{$currentRow}:A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G'.$currentRow.':I'.$currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $sheet->getStyle('I'.$currentRow)->applyFromArray($styles['bold']);
            $sheet->getStyle('J'.$currentRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($currentRow)->setRowHeight(calculateRowHeight($item['GhiChu'] ?? '', 10, $col_widths_pixels['J']));
            $currentRow++;
        }
    };

    if (!empty($pur_items)) {
        $drawTableHeader($sheet, $currentRow, 'col_pur_dimensions', 'col_pur_id', 'col_pur_thickness', 'col_pur_width');
        $writeProductGroup($sheet, $currentRow, $pur_items, '', $col_widths_pixels);
    }
    if (!empty($ula_items)) {
        $drawTableHeader($sheet, $currentRow, 'col_ula_dimensions', 'col_ula_id', 'col_ula_thickness', 'col_ula_width');
        $writeProductGroup($sheet, $currentRow, $ula_items, 'includes_two_nuts', $col_widths_pixels);
    }

    // Phí vận chuyển
    $sheet->mergeCells("A{$currentRow}:J{$currentRow}")->setCellValue("A{$currentRow}", $e_bilingual('shipping_fee_header'));
    $sheet->getStyle("A{$currentRow}")->applyFromArray($styles['shipping_header'])->getAlignment()->setWrapText(true);
    $sheet->getRowDimension($currentRow)->setRowHeight(35); $currentRow++;
    
    $sheet->setCellValue('A'.$currentRow, '1');
    $sheet->setCellValue('B'.$currentRow, $e_bilingual('shipping_unit_trip', '/'));
    $sheet->setCellValue('G'.$currentRow, $quote_info['SoLuongVanChuyen'] ?? 0);
    $sheet->setCellValue('H'.$currentRow, $quote_info['DonGiaVanChuyen'] ?? 0);
    $sheet->setCellValue('I'.$currentRow, ($quote_info['SoLuongVanChuyen'] ?? 0) * ($quote_info['DonGiaVanChuyen'] ?? 0));
    $sheet->setCellValue('J'.$currentRow, $quote_info['GhiChuVanChuyen'] ?? '');
    $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray($styles['shipping_row']);
    $sheet->getStyle('A'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B'.$currentRow)->getAlignment()->setWrapText(true);
    $sheet->getStyle('G'.$currentRow.':I'.$currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    $sheet->getStyle('I'.$currentRow)->applyFromArray($styles['bold']);
    $sheet->getStyle('J'.$currentRow)->getAlignment()->setWrapText(true);
    $sheet->getRowDimension($currentRow)->setRowHeight(max(18, calculateRowHeight($quote_info['GhiChuVanChuyen'] ?? '', 10, $col_widths_pixels['J'])));
    $currentRow += 2;

    // 7. VẼ FOOTER
    $footerStartRow = $currentRow;
    
    // Khối tổng tiền (G-J)
    $sheet->getStyle("G{$footerStartRow}:J".($footerStartRow+4))->applyFromArray($styles['totals_box']);
    $sheet->mergeCells("G{$footerStartRow}:H{$footerStartRow}");
    $sheet->setCellValue("G{$footerStartRow}", $createBilingualRichText('total_pre_tax'))->getStyle("G{$footerStartRow}")->applyFromArray(['font' => ['bold' => true]])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->setCellValue("I{$footerStartRow}", $quote_info['TongTienTruocThue'] ?? 0)->getStyle("I{$footerStartRow}")->applyFromArray($styles['bold'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // === BẮT ĐẦU THAY ĐỔI: HIỂN THỊ THUẾ ĐỘNG SONG NGỮ ===
    $sheet->mergeCells("G".($footerStartRow+1).":H".($footerStartRow+1));
    $tax_percentage_text = rtrim(rtrim(number_format((float)($quote_info['ThuePhanTram'] ?? 8), 2, '.', ''), '0'), '.');
    
    $vatRichText = new RichText();
    $vat_vi_text = ($labels['total_vat']['vi'] ?? '[total_vat_vi]') . " ({$tax_percentage_text}%)";
    $vatRichText->createTextRun($vat_vi_text)->getFont()->setBold(true);
    
    $vat_zh_text = "\n" . ($labels['total_vat']['zh'] ?? '[total_vat_zh]') . " ({$tax_percentage_text}%)";
    $rt_zh_vat = $vatRichText->createTextRun($vat_zh_text);
    $rt_zh_vat->getFont()->setSize(9)->setItalic(true)->setColor(new Color('FF696969'));

    $sheet->setCellValue("G".($footerStartRow+1), $vatRichText);
    $sheet->getStyle("G".($footerStartRow+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
    // === KẾT THÚC THAY ĐỔI ===

    $sheet->setCellValue("I".($footerStartRow+1), $quote_info['ThueVAT'] ?? 0)->getStyle("I".($footerStartRow+1))->applyFromArray($styles['bold'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    $sheet->getStyle("G".($footerStartRow+2).":J".($footerStartRow+2))->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
    
    $sheet->mergeCells("G".($footerStartRow+3).":H".($footerStartRow+3));
    $sheet->setCellValue("G".($footerStartRow+3), $createBilingualRichText('total_after_tax'))->getStyle("G".($footerStartRow+3))->applyFromArray(['font' => ['bold' => true]])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->setCellValue("I".($footerStartRow+3), $quote_info['TongTienSauThue'] ?? 0)->getStyle("I".($footerStartRow+3))->applyFromArray($styles['bold'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("I{$footerStartRow}:I".($footerStartRow+3))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    $sheet->getStyle("G".($footerStartRow+3).":J".($footerStartRow+3))->getFont()->setSize(11);
    
    $sheet->mergeCells("G".($footerStartRow+4).":J".($footerStartRow+4));
    $amountInWordsText = new RichText();
    $amountInWordsText->createTextRun($e_bilingual('amount_in_words', ' / ') . ":\n")->getFont()->setBold(true)->setItalic(true);
    $amountInWordsText->createTextRun(numberToWords($quote_info['TongTienSauThue'] ?? 0) . "\n")->getFont()->setBold(true)->setItalic(true);
    $amountInWordsText->createTextRun(numberToWords_zh($quote_info['TongTienSauThue'] ?? 0))->getFont()->setBold(true)->setItalic(true);
    $sheet->setCellValue("G".($footerStartRow+4), $amountInWordsText);
    $sheet->getStyle("G".($footerStartRow+4))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

    $sheet->getRowDimension($footerStartRow)->setRowHeight(30);
    $sheet->getRowDimension($footerStartRow+1)->setRowHeight(30);
    $sheet->getRowDimension($footerStartRow+2)->setRowHeight(8);
    $sheet->getRowDimension($footerStartRow+3)->setRowHeight(30);
    $sheet->getRowDimension($footerStartRow+4)->setRowHeight(45);

    // === TỐI ƯU LAYOUT: Vẽ ảnh trước, sau đó đặt ghi chú bên dưới ===
    
    // 1. Vẽ hình ảnh sản phẩm (A-E)
    $dbProdPath = !empty($quote_info['HinhAnh1']) ? strtolower($quote_info['HinhAnh1']) : 'uploads/default_image.png';
    $productImagePath = __DIR__ . '/../' . $dbProdPath;
    if(file_exists($productImagePath)){
        $drawingProd = new Drawing();
        $drawingProd->setPath($productImagePath);
        $drawingProd->setCoordinates('B'.$footerStartRow); // Bắt đầu cùng hàng với khối tổng tiền
        $drawingProd->setHeight(150); // Giảm chiều cao ảnh một chút cho cân đối
        $drawingProd->setOffsetX(5);
        $drawingProd->setOffsetY(5);
        $drawingProd->setWorksheet($sheet);
    }

    // 2. Đặt khối ghi chú bên dưới ảnh
    $notesStartRowLeft = $footerStartRow + 11; // 150px height ~ 10-11 hàng, đặt ở đây cho an toàn
    
    $sheet->mergeCells("A{$notesStartRowLeft}:E".($notesStartRowLeft+5))->getStyle("A{$notesStartRowLeft}:E".($notesStartRowLeft+5))->applyFromArray($styles['notes_box']);
    $notesRichText = new RichText();
    $notesRichText->createTextRun($e_bilingual('origin', ' / ') . ": ")->getFont()->setBold(true);
    $notesRichText->createTextRun(($quote_info['XuatXu'] ?? $labels['supplier_name']['vi']) . "\n");
    $notesRichText->createTextRun("- " . $e_bilingual('delivery_time', ' / ') . ": ")->getFont()->setBold(true);
    $notesRichText->createTextRun(($quote_info['ThoiGianGiaoHang'] ?? '...') . "\n");
    $notesRichText->createTextRun("- " . $e_bilingual('payment_terms', ' / ') . ": ")->getFont()->setBold(true);
    $notesRichText->createTextRun(($quote_info['DieuKienThanhToan'] ?? '...') . "\n");
    $notesRichText->createTextRun("- " . $e_bilingual('delivery_address', ' / ') . ": ")->getFont()->setBold(true);
    $notesRichText->createTextRun(($quote_info['DiaChiGiaoHang'] ?? ''));
    $sheet->setCellValue("A{$notesStartRowLeft}", $notesRichText);

    // 3. Khối thông tin công ty bên phải (vẫn giữ nguyên vị trí)
    $notesStartRowRight = $footerStartRow + 6;
    $sheet->mergeCells("G{$notesStartRowRight}:J".($notesStartRowRight+14))->getStyle("G{$notesStartRowRight}:J".($notesStartRowRight+14))->applyFromArray($styles['notes_box']);
    $companyInfoRichText = new RichText();
    $companyInfoRichText->createTextRun($e_bilingual('company_name') . "\n")->getFont()->setBold(true)->setSize(10);
    $companyInfoRichText->createTextRun($e_bilingual('company_address', ': ') . $e_bilingual('company_full_address_value') . "\n");
    $companyInfoRichText->createTextRun($e_bilingual('tax_code', ' / ') . ": " . ($quote_info['MST'] ?? $labels['tax_code_value']['vi']) . "\n")->getFont()->setBold(true);
    $companyInfoRichText->createTextRun($e_bilingual('bank_info', ' / ') . ":\n")->getFont()->setBold(true);
    $companyInfoRichText->createTextRun($e_bilingual('bank_account_holder', ': ') . $e_bilingual('bank_holder_value') . "\n");
    $companyInfoRichText->createTextRun($e_bilingual('bank_account_number', ': ') . $e_bilingual('bank_account_details_value'));
    $sheet->setCellValue("G{$notesStartRowRight}", $companyInfoRichText);
    
    // 4. Tính toán động vị trí hàng chữ ký
    $endRowLeft = $notesStartRowLeft + 5; // Dòng cuối của khối ghi chú trái
    $endRowRight = $notesStartRowRight + 14; // Dòng cuối của khối thông tin công ty
    $signatureRow = max($endRowLeft, $endRowRight) + 2; // Đặt chữ ký dưới khối cao nhất + 2 dòng trống
    
    $sheet->getRowDimension($signatureRow)->setRowHeight(35);
    $sheet->setCellValue('B'.$signatureRow, $e_bilingual('signature_buyer'))->getStyle('B'.$signatureRow)->applyFromArray($styles['bold'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
    
    $sheet->mergeCells("I{$signatureRow}:J{$signatureRow}")->setCellValue('I'.$signatureRow, $e_bilingual('signature_seller'));
    $sheet->getStyle("I{$signatureRow}")->applyFromArray($styles['bold'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
    
    // 8. XUẤT FILE
    $fileName = "BaoGia-CN" . preg_replace('/[^a-zA-Z0-9_-]/', '', $quote_info['SoBaoGia'] ?? 'test') . ".xlsx";
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

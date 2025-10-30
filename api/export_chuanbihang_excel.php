<?php
/**
 * api/export_chuanbihang_excel.php
 * Xuất dữ liệu phiếu chuẩn bị hàng ra file Excel.
 * Version: 5.9 (Tối ưu hóa cho việc in ấn)
 * - [UPDATE] Thêm cài đặt Page Setup để khi in file Excel sẽ tự động:
 * - Xoay ngang (Landscape) khổ giấy A4.
 * - Co dãn các cột để vừa chiều rộng một trang.
 * - Căn lề và căn giữa bảng trên trang in.
 */

ini_set('display_errors', 0); // Tắt hiển thị lỗi trên production
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Color;
// Thêm namespace cho PageSetup
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;


// === CÁC HÀM HỖ TRỢ ===
function parsePurSku($maHang) { if (preg_match('/^PUR-(S|C)\s*(?:\d+\s*\/\s*)?(\d+x\d+|\d+)(?:x\d+)?(?:-([A-Z0-9]+))?/', $maHang, $matches)) { return ['type' => $matches[1], 'dimensions' => $matches[2], 'suffix' => $matches[3] ?? '']; } return null; }
function format_number($num) { return $num ? number_format(floatval($num), 0, ',', '.') : '0'; }
function format_number_excel($num) { return $num ? floatval($num) : 0; }
function format_decimal_excel($num) { return $num ? floatval($num) : 0.00; }

try {
    // ===================================================================
    // 1. LẤY VÀ XỬ LÝ DỮ LIỆU
    // ===================================================================
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { http_response_code(400); die("Lỗi: ID phiếu không hợp lệ."); }
    $cbhId = (int)$_GET['id'];
    $pdo = get_db_connection();

    $stmt_info = $pdo->prepare("SELECT cbh.*, dh.SoYCSX, dh.NgayGiaoDuKien, dh.TenDuAn, COALESCE(cbh.DiaDiemGiaoHang, dh.DiaChiGiaoHang) AS DiaDiemGiaoHang, COALESCE(cbh.NguoiNhanHang, dh.NguoiNhan) AS NguoiNhanHang FROM chuanbihang cbh JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID WHERE cbh.CBH_ID = :cbhId");
    $stmt_info->execute([':cbhId' => $cbhId]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    if (!$info) { throw new Exception('Không tìm thấy phiếu.'); }

    $stmt_items = $pdo->prepare("SELECT ctcbh.*, v.variant_name, p.base_sku, vi.quantity AS TonKhoVatLy FROM chitietchuanbihang ctcbh LEFT JOIN variants v ON ctcbh.SanPhamID = v.variant_id LEFT JOIN products p ON v.product_id = p.product_id LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id WHERE ctcbh.CBH_ID = :cbhId ORDER BY ctcbh.ThuTuHienThi");
    $stmt_items->execute([':cbhId' => $cbhId]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $stmt_alloc_by_id = $pdo->prepare("SELECT COALESCE(SUM(SoLuongPhanBo), 0) FROM donhang_phanbo_tonkho WHERE SanPhamID = ? AND CBH_ID != ?");

    $hangSanXuat = []; $hangChuanBi_ULA = []; $hangDeoTreo = [];
    foreach ($items as &$item) {
        $stmt_alloc_by_id->execute([$item['SanPhamID'], $cbhId]);
        $item['DaGan'] = (int)$stmt_alloc_by_id->fetchColumn();
        $item['TonKho'] = (int)($item['TonKhoVatLy'] ?? 0);
        $item['SoLuongCanSX'] = (int)$item['SoLuong'] - min((int)$item['SoLuong'], max(0, $item['TonKho'] - $item['DaGan']));
        $maHangUpper = strtoupper($item['MaHang'] ?? '');
        if (str_starts_with($maHangUpper, 'PUR')) {
            $hangSanXuat[] = $item;
        } elseif (str_starts_with($maHangUpper, 'ULA')) {
            $hangChuanBi_ULA[] = $item;
        } elseif (str_starts_with($maHangUpper, 'DT')) {
            $hangDeoTreo[] = $item;
        }
    }
    unset($item);
    
    foreach ($hangSanXuat as &$item) {
        $tonKhoCV = (int)($item['TonKhoCV'] ?? 0); $daGanCV = (int)($item['DaGanCV'] ?? 0); $khaDungCV = max(0, $tonKhoCV - $daGanCV);
        $item['tonKhoDisplayCV'] = sprintf('%s/%s/%s', format_number($tonKhoCV), format_number($daGanCV), format_number($khaDungCV));
        
        $tonKhoCT = (int)($item['TonKhoCT'] ?? 0); $daGanCT = (int)($item['DaGanCT'] ?? 0); $khaDungCT = max(0, $tonKhoCT - $daGanCT);
        $item['tonKhoDisplayCT'] = sprintf('%s/%s/%s', format_number($tonKhoCT), format_number($daGanCT), format_number($khaDungCT));
        
        $item['canSanXuatDisplayCV'] = (int)($item['CanSanXuatCV'] ?? 0);
        $item['canSanXuatDisplayCT'] = (int)($item['CanSanXuatCT'] ?? 0);
    }
    unset($item);
    
    $stmt_ecu = $pdo->prepare("SELECT *, TonKhoSnapshot as TonKho, DaGanSnapshot as DaGan FROM chitiet_ecu_cbh WHERE CBH_ID = :cbhId"); $stmt_ecu->execute([':cbhId' => $cbhId]); $vatTuKem_ECU = $stmt_ecu->fetchAll(PDO::FETCH_ASSOC);

    // ===================================================================
    // 2. TẠO GIAO DIỆN EXCEL
    // ===================================================================
    $spreadsheet = new Spreadsheet(); $sheet = $spreadsheet->getActiveSheet(); $sheet->setTitle("PhieuChuanBiHang");
    $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
    
    foreach (range('A', 'L') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

    $styles = [ 
        'bold' => ['font' => ['bold' => true]], 
        'doc_title' => ['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]], 
        'thin_border_all' => ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]], 
        'info_label' => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']]], 
        'info_highlight' => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']]], 
        'group_title' => ['font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '000000']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']], 'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT], 'protection' => ['indent' => 1]], 
        'table_header' => ['font' => ['bold' => true, 'color' => ['rgb' => '000000']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]], 
        'wrap_text_vcenter' => ['alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]], 
        'align_left' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER]], 
        'cell_center' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]] 
    ];
    $styles['table_header_bordered'] = array_merge($styles['table_header'], $styles['thin_border_all']);
    
    // ... (Toàn bộ phần điền dữ liệu vào các ô giữ nguyên như cũ) ...
    // Bắt đầu phần điền dữ liệu
    $currentRow = 1;

    $logoPath = __DIR__ . '/../logo.png'; if (file_exists($logoPath)) { $drawing = new Drawing(); $drawing->setPath($logoPath); $drawing->setCoordinates('A' . $currentRow); $drawing->setHeight(120); $drawing->setWorksheet($sheet); }
    $sheet->mergeCells("E{$currentRow}:L" . ($currentRow + 2)); $sheet->setCellValue("E{$currentRow}", "CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 3I\nOffice: Số 14 Lô D31 – BT2 Tại Khu D, KĐT Mới Hai Bên Đường L, P. Dương Nội, TP Hà Nội, Việt Nam\nHotline: 0973098338 - MST: 0110886479"); $sheet->getStyle("E{$currentRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER); $sheet->getRowDimension($currentRow)->setRowHeight(60);
    $currentRow += 3;
    $sheet->mergeCells("A{$currentRow}:L{$currentRow}"); $sheet->setCellValue("A{$currentRow}", "YCSX - PHIẾU CHUẨN BỊ HÀNG"); $sheet->getStyle("A{$currentRow}")->applyFromArray($styles['doc_title']); $sheet->getRowDimension($currentRow)->setRowHeight(22);
    $currentRow++;
    
    $startInfoRow = $currentRow;
    $sheet->setCellValue('A'.$currentRow, 'Bộ phận:')->getStyle('A'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('B'.$currentRow, $info['BoPhan'] ?? 'Kho - Logistic')->getStyle('B'.$currentRow)->applyFromArray($styles['align_left']);
    $sheet->setCellValue('C'.$currentRow, 'Ngày gửi YCSX:')->getStyle('C'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('D'.$currentRow, isset($info['NgayTao']) ? date('d/m/Y', strtotime($info['NgayTao'])) : '')->getStyle('D'.$currentRow)->applyFromArray($styles['align_left']);
    $sheet->setCellValue('E'.$currentRow, 'Ngày giao:')->getStyle('E'.$currentRow)->applyFromArray(array_merge($styles['info_label'], $styles['info_highlight'])); 
    $sheet->setCellValue('F'.$currentRow, isset($info['NgayGiao']) ? date('d/m/Y', strtotime($info['NgayGiao'])) : '')->getStyle('F'.$currentRow)->applyFromArray($styles['align_left']);
    $sheet->setCellValue('G'.$currentRow, 'Đăng ký CT:')->getStyle('G'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('H'.$currentRow, $info['DangKiCongTruong'] ?? '')->getStyle('H'.$currentRow)->applyFromArray($styles['align_left']); 
    $sheet->mergeCells("H{$currentRow}:L{$currentRow}");
    $currentRow++;
    $sheet->setCellValue('A'.$currentRow, 'Phụ trách:')->getStyle('A'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('B'.$currentRow, $info['PhuTrach'] ?? '')->getStyle('B'.$currentRow)->applyFromArray($styles['align_left']);
    $sheet->setCellValue('C'.$currentRow, 'Người nhận:')->getStyle('C'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('D'.$currentRow, $info['NguoiNhanHang'] ?? '')->getStyle('D'.$currentRow)->applyFromArray($styles['align_left']);
    $sheet->setCellValue('E'.$currentRow, 'SĐT:')->getStyle('E'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('F'.$currentRow, $info['SdtNguoiNhan'] ?? '')->getStyle('F'.$currentRow)->applyFromArray($styles['align_left']); 
    $sheet->mergeCells("F{$currentRow}:L{$currentRow}");
    $currentRow++;
    $sheet->setCellValue('A'.$currentRow, 'Địa điểm giao hàng:')->getStyle('A'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('B'.$currentRow, $info['DiaDiemGiaoHang'] ?? '')->getStyle('B'.$currentRow)->applyFromArray($styles['align_left']); 
    $sheet->mergeCells("B{$currentRow}:L{$currentRow}");
    $currentRow++;
    $sheet->setCellValue('A'.$currentRow, 'Số đơn:')->getStyle('A'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('B'.$currentRow, $info['SoYCSX'] ?? $info['SoDon'] ?? '')->getStyle('B'.$currentRow)->applyFromArray(array_merge($styles['bold'], $styles['align_left'])); 
    $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
    $sheet->setCellValue('G'.$currentRow, 'Mã đơn:')->getStyle('G'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('H'.$currentRow, $info['MaDon'] ?? $info['DonHangID'] ?? '')->getStyle('H'.$currentRow)->applyFromArray($styles['align_left']); 
    $sheet->mergeCells("H{$currentRow}:L{$currentRow}");
    $currentRow++;
    $sheet->setCellValue('A'.$currentRow, 'Số lái xe:')->getStyle('A'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('B'.$currentRow, $info['SoLaiXe'] ?? '')->getStyle('B'.$currentRow)->applyFromArray($styles['align_left']); 
    $sheet->mergeCells("B{$currentRow}:D{$currentRow}");
    $sheet->setCellValue('E'.$currentRow, 'Xe grap:')->getStyle('E'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('F'.$currentRow, $info['XeGrap'] ?? $info['LoaiXe'] ?? '')->getStyle('F'.$currentRow)->applyFromArray($styles['align_left']); 
    $sheet->mergeCells("F{$currentRow}:H{$currentRow}");
    $sheet->setCellValue('I'.$currentRow, 'Xe tải (tấn):')->getStyle('I'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('J'.$currentRow, $info['XeTai'] ?? $info['XeTaiTan'] ?? '')->getStyle('J'.$currentRow)->applyFromArray($styles['align_left']); 
    $sheet->mergeCells("J{$currentRow}:L{$currentRow}");
    $currentRow++;
    $sheet->setCellValue('A'.$currentRow, 'Quy cách thùng:')->getStyle('A'.$currentRow)->applyFromArray($styles['info_label']); 
    $sheet->setCellValue('B'.$currentRow, $info['QuyCachThung'] ?? '')->getStyle('B'.$currentRow)->applyFromArray($styles['align_left']); 
    $sheet->mergeCells("B{$currentRow}:L{$currentRow}");
    $endInfoRow = $currentRow;
    $sheet->getStyle("A{$startInfoRow}:L{$endInfoRow}")->applyFromArray($styles['thin_border_all']);
    $currentRow += 2;

    // ... (Toàn bộ phần lặp qua các mảng sản phẩm và điền dữ liệu vào bảng giữ nguyên)
    // ...
    // Ví dụ một phần:
    if (!empty($hangSanXuat)) {
        $sheet->mergeCells("A{$currentRow}:L{$currentRow}")->setCellValue("A{$currentRow}", "GỐI (PUR)")->getStyle("A{$currentRow}")->applyFromArray($styles['group_title']); $currentRow++;
        $headerRow1 = $currentRow; $headerRow2 = $currentRow + 1;
        $sheet->mergeCells("A{$headerRow1}:A{$headerRow2}")->setCellValue("A{$headerRow1}", "Stt");
        $sheet->mergeCells("B{$headerRow1}:B{$headerRow2}")->setCellValue("B{$headerRow1}", "Mã hàng");
        $sheet->mergeCells("C{$headerRow1}:E{$headerRow1}")->setCellValue("C{$headerRow1}", "Kích thước (mm)");
        $sheet->setCellValue("C{$headerRow2}", "ID"); $sheet->setCellValue("D{$headerRow2}", "Đ.Dày"); $sheet->setCellValue("E{$headerRow2}", "B.Rộng");
        $sheet->mergeCells("F{$headerRow1}:F{$headerRow2}")->setCellValue("F{$headerRow1}", "SL YC\n(Bộ)");
        $sheet->setCellValue("G{$headerRow1}", "Tình trạng (Bộ)");
        $richHeaderTinhTrang = new RichText(); $richHeaderTinhTrang->createText("Tồn/Gán/KD\n"); $csxRun = $richHeaderTinhTrang->createTextRun('CSX'); $csxRun->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
        $sheet->getCell("G{$headerRow2}")->setValue($richHeaderTinhTrang);
        $sheet->mergeCells("H{$headerRow1}:H{$headerRow2}")->setCellValue("H{$headerRow1}", "Số cây\ncắt");
        $sheet->setCellValue("I{$headerRow1}", "CV (cây)"); $sheet->setCellValue("J{$headerRow1}", "CT (cây)");
        $richSubHeader = new RichText(); $richSubHeader->createText("T/G/KD\n"); $csxRun = $richSubHeader->createTextRun('CSX'); $csxRun->getFont()->setColor(new Color(Color::COLOR_RED))->setBold(true);
        $sheet->getCell("I{$headerRow2}")->setValue($richSubHeader); $sheet->getCell("J{$headerRow2}")->setValue(clone $richSubHeader);
        $sheet->mergeCells("K{$headerRow1}:K{$headerRow2}")->setCellValue("K{$headerRow1}", "Số Thùng");
        $sheet->mergeCells("L{$headerRow1}:L{$headerRow2}")->setCellValue("L{$headerRow1}", "Ghi chú");
        $sheet->getStyle("A{$headerRow1}:L{$headerRow2}")->applyFromArray($styles['table_header_bordered']);
        $sheet->getStyle("H{$headerRow1}")->getFont()->setColor(new Color(Color::COLOR_BLUE));
        $currentRow += 2;
        $stt = 1;
        foreach ($hangSanXuat as $item) {
            $sheet->setCellValue("A{$currentRow}", $stt++);
            $sheet->setCellValue("B{$currentRow}", $item['MaHang'] ?? '');
            $sheet->setCellValue("C{$currentRow}", $item['ID_ThongSo'] ?? '');
            $sheet->setCellValue("D{$currentRow}", $item['DoDay'] ?? '');
            $sheet->setCellValue("E{$currentRow}", $item['BanRong'] ?? '');
            $sheet->setCellValue("F{$currentRow}", format_number_excel($item['SoLuong'] ?? 0));
            $ton = (int)($item['TonKho'] ?? 0); $gan = (int)($item['DaGan'] ?? 0); $kd = max(0, $ton - $gan); $csx = (int)($item['SoLuongCanSX'] ?? 0);
            $richTextTinhTrang = new RichText();
            $richTextTinhTrang->createText(format_number($ton) . ' / ' . format_number($gan) . ' / ' . format_number($kd) . "\n");
            $csxRun = $richTextTinhTrang->createTextRun(format_number($csx)); $csxRun->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
            $sheet->getCell("G{$currentRow}")->setValue($richTextTinhTrang);
            $sheet->setCellValue("H{$currentRow}", format_number_excel($item['SoCayPhaiCat'] ?? 0));
            $cvNeed = (int)($item['canSanXuatDisplayCV'] ?? 0);
            $richTextCV = new RichText(); $richTextCV->createText(($item['tonKhoDisplayCV'] ?? '0/0/0') . "\n");
            $cvRun = $richTextCV->createTextRun(format_number($cvNeed)); $cvRun->getFont()->setColor(new Color(Color::COLOR_RED))->setBold(true);
            $sheet->getCell("I{$currentRow}")->setValue($richTextCV);
            $ctNeed = (int)($item['canSanXuatDisplayCT'] ?? 0);
            $richTextCT = new RichText(); $richTextCT->createText(($item['tonKhoDisplayCT'] ?? '0/0/0') . "\n");
            $ctRun = $richTextCT->createTextRun(format_number($ctNeed)); $ctRun->getFont()->setColor(new Color(Color::COLOR_RED))->setBold(true);
            $sheet->getCell("J{$currentRow}")->setValue($richTextCT);
            $sheet->setCellValue("K{$currentRow}", $item['SoThung'] ?? '');
            $sheet->setCellValue("L{$currentRow}", $item['GhiChu'] ?? '');
            $sheet->getStyle("A{$currentRow}:L{$currentRow}")->applyFromArray($styles['thin_border_all']);
            $sheet->getStyle("G{$currentRow}:J{$currentRow}")->applyFromArray($styles['wrap_text_vcenter']);
            $sheet->getStyle("A{$currentRow}")->applyFromArray($styles['cell_center']);
            $sheet->getStyle("C{$currentRow}:L{$currentRow}")->applyFromArray($styles['cell_center']);
            $sheet->getStyle("F{$currentRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("H{$currentRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("H{$currentRow}")->getFont()->setColor(new Color(Color::COLOR_BLUE))->setBold(true);
            $sheet->getStyle("B{$currentRow}")->applyFromArray($styles['align_left']);
            $sheet->getStyle("L{$currentRow}")->applyFromArray($styles['align_left']);
            $sheet->getRowDimension($currentRow)->setRowHeight(-1);
            $currentRow++;
        }
        $currentRow++;
    }
    // ... Tương tự cho các bảng ULA, ĐEO TREO, ECU ...
    if (!empty($hangChuanBi_ULA)) {
        $sheet->mergeCells("A{$currentRow}:L{$currentRow}")->setCellValue("A{$currentRow}", "CÙM (ULA)")->getStyle("A{$currentRow}")->applyFromArray($styles['group_title']); $currentRow++;
        $headerRow1 = $currentRow; $headerRow2 = $currentRow+1;
        $sheet->mergeCells("A{$headerRow1}:A{$headerRow2}")->setCellValue("A{$headerRow1}", "Stt");
        $sheet->mergeCells("B{$headerRow1}:B{$headerRow2}")->setCellValue("B{$headerRow1}", "Mã hàng");
        $sheet->mergeCells("C{$headerRow1}:E{$headerRow1}")->setCellValue("C{$headerRow1}", "Kích thước (mm)");
        $sheet->setCellValue("C{$headerRow2}", "ID"); $sheet->setCellValue("D{$headerRow2}", "Đ.Dày"); $sheet->setCellValue("E{$headerRow2}", "B.Rộng");
        $sheet->mergeCells("F{$headerRow1}:F{$headerRow2}")->setCellValue("F{$headerRow1}", "SL YC\n(Bộ)");
        $sheet->setCellValue("G{$headerRow1}", "Tình trạng (Bộ)");
        $richHeaderTinhTrang = new RichText(); $richHeaderTinhTrang->createText("Tồn/Gán/KD\n"); $csxRun = $richHeaderTinhTrang->createTextRun('CSX'); $csxRun->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
        $sheet->getCell("G{$headerRow2}")->setValue($richHeaderTinhTrang);
        $sheet->mergeCells("H{$headerRow1}:J{$headerRow2}")->setCellValue("H{$headerRow1}", "");
        $sheet->mergeCells("K{$headerRow1}:K{$headerRow2}")->setCellValue("K{$headerRow1}", "Đóng tải");
        $sheet->mergeCells("L{$headerRow1}:L{$headerRow2}")->setCellValue("L{$headerRow1}", "Ghi chú");
        $sheet->getStyle("A{$headerRow1}:L{$headerRow2}")->applyFromArray($styles['table_header_bordered']);
        $currentRow += 2;
        $stt = 1;
        foreach($hangChuanBi_ULA as $item) {
            $sheet->setCellValue("A{$currentRow}", $stt++);
            $sheet->setCellValue("B{$currentRow}", $item['MaHang'] ?? '');
            $sheet->setCellValue("C{$currentRow}", $item['ID_ThongSo'] ?? '');
            $sheet->setCellValue("D{$currentRow}", $item['DoDay'] ?? '');
            $sheet->setCellValue("E{$currentRow}", $item['BanRong'] ?? '');
            $sheet->setCellValue("F{$currentRow}", format_number_excel($item['SoLuong'] ?? 0));
            $ton = (int)($item['TonKho'] ?? 0); $gan = (int)($item['DaGan'] ?? 0); $kd = max(0, $ton - $gan); $csx = (int)($item['SoLuongCanSX'] ?? 0);
            $richTextTinhTrang = new RichText();
            $richTextTinhTrang->createText(format_number($ton) . ' / ' . format_number($gan) . ' / ' . format_number($kd) . "\n");
            $csxRun = $richTextTinhTrang->createTextRun(format_number($csx)); $csxRun->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
            $sheet->getCell("G{$currentRow}")->setValue($richTextTinhTrang);
            $sheet->setCellValue("K{$currentRow}", $item['DongGoi'] ?? '');
            $sheet->setCellValue("L{$currentRow}", $item['GhiChu'] ?? '');
            $sheet->getStyle("A{$currentRow}:L{$currentRow}")->applyFromArray($styles['thin_border_all'])->applyFromArray($styles['cell_center']);
            $sheet->getStyle("G{$currentRow}")->applyFromArray($styles['wrap_text_vcenter']);
            $sheet->getStyle("B{$currentRow}")->applyFromArray($styles['align_left']);
            $sheet->getStyle("L{$currentRow}")->applyFromArray($styles['align_left']);
            $currentRow++;
        }
        $currentRow++;
    }
    if (!empty($hangDeoTreo)) {
        $sheet->mergeCells("A{$currentRow}:L{$currentRow}")->setCellValue("A{$currentRow}", "ĐEO TREO")->getStyle("A{$currentRow}")->applyFromArray($styles['group_title']); $currentRow++;
        $headerRow1 = $currentRow; $headerRow2 = $currentRow+1;
        $sheet->mergeCells("A{$headerRow1}:A{$headerRow2}")->setCellValue("A{$headerRow1}", "Stt");
        $sheet->mergeCells("B{$headerRow1}:B{$headerRow2}")->setCellValue("B{$headerRow1}", "Mã hàng");
        $sheet->mergeCells("C{$headerRow1}:E{$headerRow1}")->setCellValue("C{$headerRow1}", "Kích thước (mm)");
        $sheet->setCellValue("C{$headerRow2}", "ID"); $sheet->setCellValue("D{$headerRow2}", "Đ.Dày"); $sheet->setCellValue("E{$headerRow2}", "B.Rộng");
        $sheet->mergeCells("F{$headerRow1}:F{$headerRow2}")->setCellValue("F{$headerRow1}", "SL YC\n(Bộ)");
        $sheet->setCellValue("G{$headerRow1}", "Tình trạng (Bộ)");
        $richHeaderTinhTrang = new RichText(); $richHeaderTinhTrang->createText("Tồn/Gán/KD\n"); $csxRun = $richHeaderTinhTrang->createTextRun('CSX'); $csxRun->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
        $sheet->getCell("G{$headerRow2}")->setValue($richHeaderTinhTrang);
        $sheet->mergeCells("H{$headerRow1}:J{$headerRow2}")->setCellValue("H{$headerRow1}", "");
        $sheet->mergeCells("K{$headerRow1}:K{$headerRow2}")->setCellValue("K{$headerRow1}", "Đóng tải");
        $sheet->mergeCells("L{$headerRow1}:L{$headerRow2}")->setCellValue("L{$headerRow1}", "Ghi chú");
        $sheet->getStyle("A{$headerRow1}:L{$headerRow2}")->applyFromArray($styles['table_header_bordered']);
        $currentRow += 2;
        $stt = 1;
        foreach($hangDeoTreo as $item) {
            $sheet->setCellValue("A{$currentRow}", $stt++);
            $sheet->setCellValue("B{$currentRow}", $item['MaHang'] ?? '');
            $sheet->setCellValue("C{$currentRow}", $item['ID_ThongSo'] ?? '');
            $sheet->setCellValue("D{$currentRow}", $item['DoDay'] ?? '');
            $sheet->setCellValue("E{$currentRow}", $item['BanRong'] ?? '');
            $sheet->setCellValue("F{$currentRow}", format_number_excel($item['SoLuong'] ?? 0));
            $ton = (int)($item['TonKho'] ?? 0); $gan = (int)($item['DaGan'] ?? 0); $kd = max(0, $ton - $gan); $csx = (int)($item['SoLuongCanSX'] ?? 0);
            $richTextTinhTrang = new RichText();
            $richTextTinhTrang->createText(format_number($ton) . ' / ' . format_number($gan) . ' / ' . format_number($kd) . "\n");
            $csxRun = $richTextTinhTrang->createTextRun(format_number($csx)); $csxRun->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
            $sheet->getCell("G{$currentRow}")->setValue($richTextTinhTrang);
            $sheet->setCellValue("K{$currentRow}", $item['DongGoi'] ?? '');
            $sheet->setCellValue("L{$currentRow}", $item['GhiChu'] ?? '');
            $sheet->getStyle("A{$currentRow}:L{$currentRow}")->applyFromArray($styles['thin_border_all'])->applyFromArray($styles['cell_center']);
            $sheet->getStyle("G{$currentRow}")->applyFromArray($styles['wrap_text_vcenter']);
            $sheet->getStyle("B{$currentRow}")->applyFromArray($styles['align_left']);
            $sheet->getStyle("L{$currentRow}")->applyFromArray($styles['align_left']);
            $currentRow++;
        }
        $currentRow++;
    }
    if (!empty($vatTuKem_ECU)) {
        $sheet->mergeCells("A{$currentRow}:L{$currentRow}")->setCellValue("A{$currentRow}", "VẬT TƯ KÈM (ECU)")->getStyle("A{$currentRow}")->applyFromArray($styles['group_title']); $currentRow++;
        $headerRow1 = $currentRow; $headerRow2 = $currentRow+1;
        $sheet->mergeCells("A{$headerRow1}:A{$headerRow2}")->setCellValue("A{$headerRow1}", "Stt");
        $sheet->mergeCells("B{$headerRow1}:E{$headerRow2}")->setCellValue("B{$headerRow1}", "Tên vật tư");
        $sheet->mergeCells("F{$headerRow1}:F{$headerRow2}")->setCellValue("F{$headerRow1}", "SL YC\n(Cái)");
        $sheet->setCellValue("G{$headerRow1}", "Tình trạng (Cái)");
        $richHeaderTinhTrang = new RichText(); $richHeaderTinhTrang->createText("Tồn/Gán/KD\n"); $csxRun = $richHeaderTinhTrang->createTextRun('CSX'); $csxRun->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
        $sheet->getCell("G{$headerRow2}")->setValue($richHeaderTinhTrang);
        $sheet->mergeCells("H{$headerRow1}:J{$headerRow2}")->setCellValue("H{$headerRow1}", "Kg");
        $sheet->mergeCells("K{$headerRow1}:K{$headerRow2}")->setCellValue("K{$headerRow1}", "Đóng tải");
        $sheet->mergeCells("L{$headerRow1}:L{$headerRow2}")->setCellValue("L{$headerRow1}", "Ghi chú");
        $sheet->getStyle("A{$headerRow1}:L{$headerRow2}")->applyFromArray($styles['table_header_bordered']);
        $currentRow += 2;
        $stt = 1;
        foreach($vatTuKem_ECU as $item) {
            $ton = (int)($item['TonKho'] ?? 0); $gan = (int)($item['DaGan'] ?? 0); $kd = max(0, $ton - $gan); $csx = max(0, ($item['SoLuongEcu'] ?? 0) - $kd);
            $sheet->setCellValue("A{$currentRow}", $stt++);
            $sheet->mergeCells("B{$currentRow}:E{$currentRow}")->setCellValue("B{$currentRow}", $item['TenSanPhamEcu'] ?? '');
            $sheet->setCellValue("F{$currentRow}", format_number_excel($item['SoLuongEcu'] ?? 0));
            $richTextTinhTrangEcu = new RichText();
            $richTextTinhTrangEcu->createText(format_number($ton) . ' / ' . format_number($gan) . ' / ' . format_number($kd) . "\n");
            $csxRun = $richTextTinhTrangEcu->createTextRun(format_number($csx));
            $csxRun->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
            $sheet->getCell("G{$currentRow}")->setValue($richTextTinhTrangEcu);
            $sheet->mergeCells("H{$currentRow}:J{$currentRow}")->setCellValue("H{$currentRow}", format_decimal_excel($item['SoKgEcu'] ?? 0));
            $sheet->setCellValue("K{$currentRow}", $item['DongGoiEcu'] ?? '');
            $sheet->setCellValue("L{$currentRow}", $item['GhiChuEcu'] ?? '');
            $sheet->getStyle("A{$currentRow}:L{$currentRow}")->applyFromArray($styles['thin_border_all'])->applyFromArray($styles['cell_center']);
            $sheet->getStyle("G{$currentRow}")->applyFromArray($styles['wrap_text_vcenter']);
            $sheet->getStyle("B{$currentRow}")->applyFromArray($styles['align_left']);
            $sheet->getStyle("L{$currentRow}")->applyFromArray($styles['align_left']);
            $currentRow++;
        }
        $currentRow++;
    }
    
    $currentRow += 2;
    $sheet->setCellValue("B{$currentRow}", "Quản lý đơn"); $sheet->setCellValue("E{$currentRow}", "Thủ kho");
    $sheet->setCellValue("H{$currentRow}", "Kế toán"); $sheet->setCellValue("K{$currentRow}", "Giám đốc");
    $sheet->getStyle("A{$currentRow}:L{$currentRow}")->applyFromArray($styles['bold'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


    // --- [UPDATE] TỐI ƯU HÓA CHO VIỆC IN ẤN ---
    $pageSetup = $sheet->getPageSetup();
    // 1. Đặt hướng giấy là Ngang (Landscape)
    $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
    // 2. Đặt khổ giấy là A4
    $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
    // 3. Cài đặt để co dãn vừa với chiều rộng của 1 trang giấy
    $pageSetup->setFitToWidth(1);
    $pageSetup->setFitToHeight(0);
    // 4. Căn lề (tính bằng inch, 0.5 inch ~ 1.27 cm)
    $sheet->getPageMargins()->setTop(0.5)->setRight(0.5)->setLeft(0.5)->setBottom(0.5);
    // 5. Căn giữa bảng theo chiều ngang trên trang in
    $pageSetup->setHorizontalCentered(true);
    // --- KẾT THÚC PHẦN TỐI ƯU IN ---


    $fileName = "PhieuChuanBiHang-" . ($info['SoCBH'] ?? $cbhId) . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Throwable $e) {
    if (headers_sent()) { header_remove(); }
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>GẶP LỖI NGHIÊM TRỌNG KHI TẠO FILE EXCEL</h1><p>Vui lòng sao chép toàn bộ thông báo lỗi dưới đây và gửi lại để được hỗ trợ.</p><hr>";
    echo "<p><strong>Thông báo lỗi:</strong> <span style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</span></p>";
    echo "<p><strong>Tệp xảy ra lỗi:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Dòng số:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
    echo "<hr><h3>Thông tin truy vết (Stack Trace):</h3><pre style='background-color: #f5f5f5; border: 1px solid #ccc; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    error_log("Lỗi khi tạo Excel: " . $e->getMessage() . " tại " . $e->getFile() . " dòng " . $e->getLine());
}
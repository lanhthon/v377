<?php
// File: api/export_pxk_excel.php (Đã cập nhật logic sắp xếp nhóm và tối ưu in ấn)
ob_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

try {
    $pxk_id = isset($_GET['pxk_id']) ? intval($_GET['pxk_id']) : 0;
    if ($pxk_id === 0) throw new Exception('ID không hợp lệ.');

    $pdo = get_db_connection();

    // 1. Truy vấn dữ liệu header, lấy cả dữ liệu đã lưu và dữ liệu gốc
    $sql_header = "
        SELECT 
            pxk.SoPhieuXuat,
            pxk.NgayXuat,
            pxk.TenCongTy AS TenCongTyDaLuu,
            pxk.DiaChiCongTy AS DiaChiCongTyDaLuu,
            pxk.NguoiNhan AS NguoiNhanDaLuu,
            pxk.DiaChiGiaoHang AS DiaChiGiaoHangDaLuu,
            pxk.LyDoXuatKho AS LyDoXuatKhoDaLuu,
            pxk.NguoiLapPhieu,
            pxk.ThuKho,
            pxk.NguoiGiaoHang,
            pxk.NguoiNhanHang,
            bg.TenCongTy AS TenCongTyGoc,
            bg.DiaChiKhach AS DiaChiCongTyGoc,
            dh.NguoiNhan AS NguoiNhanGoc,
            bg.DiaChiGiaoHang AS DiaChiGiaoHangGoc,
            CONCAT('Theo đơn hàng số: ', IFNULL(dh.SoYCSX, '...')) AS LyDoXuatKhoGoc,
            nguoi_tao.HoTen AS TenNguoiLapGoc,
            dh.YCSX_ID
        FROM phieuxuatkho pxk
        LEFT JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID
        LEFT JOIN baogia bg ON dh.BaoGiaID = bg.BaoGiaID
        LEFT JOIN nguoidung nguoi_tao ON pxk.NguoiTaoID = nguoi_tao.UserID
        WHERE pxk.PhieuXuatKhoID = :pxk_id";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([':pxk_id' => $pxk_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);
    if (!$header) throw new Exception("Không tìm thấy phiếu xuất kho.");

    // 2. Ưu tiên dữ liệu đã chỉnh sửa
    $header['TenCongTyHienThi'] = !empty($header['TenCongTyDaLuu']) ? $header['TenCongTyDaLuu'] : $header['TenCongTyGoc'];
    $header['DiaChiCongTyHienThi'] = !empty($header['DiaChiCongTyDaLuu']) ? $header['DiaChiCongTyDaLuu'] : $header['DiaChiCongTyGoc'];
    $header['NguoiNhanHienThi'] = !empty($header['NguoiNhanDaLuu']) ? $header['NguoiNhanDaLuu'] : $header['NguoiNhanGoc'];
    $header['DiaChiGiaoHangHienThi'] = !empty($header['DiaChiGiaoHangDaLuu']) ? $header['DiaChiGiaoHangDaLuu'] : $header['DiaChiGiaoHangGoc'];
    $header['LyDoXuatKhoHienThi'] = !empty($header['LyDoXuatKhoDaLuu']) ? $header['LyDoXuatKhoDaLuu'] : $header['LyDoXuatKhoGoc'];
    $header['NguoiLapPhieuHienThi'] = !empty($header['NguoiLapPhieu']) ? $header['NguoiLapPhieu'] : $header['TenNguoiLapGoc'];

    // 3. Lấy chi tiết sản phẩm và nhóm
    $sql_items = "
        SELECT ct.MaHang, ct.TenSanPham, ct.SoLuongThucXuat, ct.TaiSo, ct.GhiChu, ct.SanPhamID as variant_id 
        FROM chitiet_phieuxuatkho ct WHERE ct.PhieuXuatKhoID = :pxk_id ORDER BY ct.ChiTietPXK_ID";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([':pxk_id' => $pxk_id]);
    $items_result = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $ycsx_id = $header['YCSX_ID'];
    if ($ycsx_id && count($items_result) > 0) {
        $product_ids = array_map(fn($item) => $item['variant_id'], $items_result);
        if(!empty($product_ids)){
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $sql_groups = "SELECT SanPhamID, TenNhom FROM chitiet_donhang WHERE DonHangID = ? AND SanPhamID IN ($placeholders)";
            $stmt_groups = $pdo->prepare($sql_groups);
            $params = array_merge([$ycsx_id], $product_ids);
            $stmt_groups->execute($params);
            $groups_map = $stmt_groups->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($items_result as &$item) {
                $item['TenNhom'] = $groups_map[$item['variant_id']] ?? null;
            }
        }
    }
    
    $item_groups = [];
    foreach ($items_result as $item) {
        $group_name = ($item['MaHang'] === 'VT-ECU') ? 'Ecu cho Cùm Ula' : ($item['TenNhom'] ?? 'Sản phẩm khác');
        if (!isset($item_groups[$group_name])) {
            $item_groups[$group_name] = ['items' => [], 'ghiChu' => ($group_name === 'Ecu cho Cùm Ula' ? '' : '(+/-)5%')];
        }
        $item_groups[$group_name]['items'][] = $item;
    }

    uksort($item_groups, function ($a, $b) {
        $priorityA = 3;
        if (strpos($a, 'Gối đỡ PU Foam') !== false) $priorityA = 1;
        elseif (strpos($a, 'Cùm Ula') !== false) $priorityA = 2;

        $priorityB = 3;
        if (strpos($b, 'Gối đỡ PU Foam') !== false) $priorityB = 1;
        elseif (strpos($b, 'Cùm Ula') !== false) $priorityB = 2;

        if ($priorityA != $priorityB) return $priorityA <=> $priorityB;
        return $a <=> $b;
    });
    
    // 4. Bắt đầu tạo file Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("PXK " . preg_replace('/[^a-zA-Z0-9_-]/', '', $header['SoPhieuXuat']));

    // --- THAY ĐỔI: Điều chỉnh độ rộng từng cột để vừa chữ ---
    $sheet->getColumnDimension('A')->setWidth(6);  // Stt
    $sheet->getColumnDimension('B')->setWidth(10); // Nội dung
    $sheet->getColumnDimension('C')->setWidth(10); // Nội dung
    $sheet->getColumnDimension('D')->setWidth(10); // Nội dung
    $sheet->getColumnDimension('E')->setWidth(10); // Nội dung
    $sheet->getColumnDimension('F')->setWidth(11); // Mã hàng
    $sheet->getColumnDimension('G')->setWidth(11); // Mã hàng
    $sheet->getColumnDimension('H')->setWidth(8);  // Khối lượng
    $sheet->getColumnDimension('I')->setWidth(8);  // Khối lượng
    $sheet->getColumnDimension('J')->setWidth(12); // Thùng/Tải số
    $sheet->getColumnDimension('K')->setWidth(10); // Ghi chú
    $sheet->getColumnDimension('L')->setWidth(10); // Ghi chú

    // --- THAY ĐỔI: Xóa thiết lập font chữ, giữ font mặc định ---
    $spreadsheet->getDefaultStyle()->getFont()->setSize(11);

    // Bố cục Excel
    $sheet->mergeCells('A1:E1')->setCellValue('A1', 'CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG');
    $sheet->getStyle('A1:E1')->getFont()->setBold(true)->setSize(11);
    $sheet->mergeCells('A2:E2')->setCellValue('A2', 'VẬT LIỆU XANH 3I');
    $sheet->getStyle('A2:E2')->getFont()->setBold(true)->setSize(11);
    $sheet->mergeCells('A3:F3')->setCellValue('A3', 'Địa chỉ: Số 14 Lô D31 – BT2 Tại Khu D, KĐT Mới Hai Bên Đường Lê Trọng Tấn,');
    $sheet->getStyle('A3:F3')->getFont()->setSize(10);
    $sheet->mergeCells('A4:F4')->setCellValue('A4', 'Phường Dương Nội, TP Hà Nội, Việt Nam');
    $sheet->getStyle('A4:F4')->getFont()->setSize(10);

    $sheet->mergeCells('H1:L1')->setCellValue('H1', 'Mẫu số 02 - VT');
    $sheet->getStyle('H1:L1')->getFont()->setBold(true);
    $sheet->getStyle('H1:L1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->mergeCells('H2:L4')->setCellValue('H2', '(Ban hành theo TT số 133/2016/TT-BTC ngày 26/8/2016 của Bộ Tài chính)');
    $sheet->getStyle('H2:L4')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('H2:L4')->getFont()->setSize(10);
    
    $currentRow = 6;
    $ngayXuat = new DateTime($header['NgayXuat']);
    $sheet->mergeCells('A'.$currentRow.':L'.$currentRow)->setCellValue('A'.$currentRow, 'PHIẾU XUẤT KHO');
    $sheet->getStyle('A'.$currentRow)->applyFromArray(['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
    $currentRow++;
    $sheet->mergeCells('A'.$currentRow.':L'.$currentRow)->setCellValue('A'.$currentRow, 'Ngày ' . $ngayXuat->format('d') . ' tháng ' . $ngayXuat->format('m') . ' năm ' . $ngayXuat->format('Y'));
    $sheet->getStyle('A'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $currentRow++;
    $sheet->mergeCells('A'.$currentRow.':L'.$currentRow)->setCellValue('A'.$currentRow, 'Số: ' . $header['SoPhieuXuat']);
    $sheet->getStyle('A'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $currentRow = 10;
    $sheet->mergeCells('A'.$currentRow.':B'.$currentRow)->setCellValue('A'.$currentRow, 'Tên công ty:')->getStyle('A'.$currentRow)->getFont()->setBold(true);
    $sheet->mergeCells('C'.$currentRow.':L'.$currentRow)->setCellValue('C'.$currentRow, $header['TenCongTyHienThi']);
    $currentRow++;
    $sheet->mergeCells('A'.$currentRow.':B'.$currentRow)->setCellValue('A'.$currentRow, 'Địa chỉ:')->getStyle('A'.$currentRow)->getFont()->setBold(true);
    $sheet->mergeCells('C'.$currentRow.':L'.$currentRow)->setCellValue('C'.$currentRow, $header['DiaChiCongTyHienThi']);
    $currentRow++;
    $sheet->mergeCells('A'.$currentRow.':B'.$currentRow)->setCellValue('A'.$currentRow, 'Người nhận hàng:')->getStyle('A'.$currentRow)->getFont()->setBold(true);
    $sheet->mergeCells('C'.$currentRow.':L'.$currentRow)->setCellValue('C'.$currentRow, $header['NguoiNhanHienThi']);
    $currentRow++;
    $sheet->mergeCells('A'.$currentRow.':B'.$currentRow)->setCellValue('A'.$currentRow, 'Địa chỉ giao hàng:')->getStyle('A'.$currentRow)->getFont()->setBold(true);
    $sheet->mergeCells('C'.$currentRow.':L'.$currentRow)->setCellValue('C'.$currentRow, $header['DiaChiGiaoHangHienThi']);
    $currentRow++;
    $sheet->mergeCells('A'.$currentRow.':B'.$currentRow)->setCellValue('A'.$currentRow, 'Lý do xuất kho:')->getStyle('A'.$currentRow)->getFont()->setBold(true);
    $sheet->mergeCells('C'.$currentRow.':L'.$currentRow)->setCellValue('C'.$currentRow, $header['LyDoXuatKhoHienThi']);
    
    // Bảng sản phẩm
    $headerRow = $currentRow + 2;
    $sheet->setCellValue('A' . $headerRow, 'Stt.');
    $sheet->mergeCells('B' . $headerRow . ':E' . $headerRow)->setCellValue('B' . $headerRow, 'Nội dung');
    $sheet->mergeCells('F' . $headerRow . ':G' . $headerRow)->setCellValue('F' . $headerRow, 'Mã hàng');
    $sheet->mergeCells('H' . $headerRow . ':I' . $headerRow)->setCellValue('H' . $headerRow, 'Khối lượng (bộ)');
    $sheet->setCellValue('J' . $headerRow, 'Thùng/Tải số');
    $sheet->mergeCells('K' . $headerRow . ':L' . $headerRow)->setCellValue('K' . $headerRow, 'Ghi chú');

    $sheet->getStyle("A{$headerRow}:L{$headerRow}")->applyFromArray([
        'font' => ['bold' => true],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9E1F2']]
    ]);
    $sheet->getRowDimension($headerRow)->setRowHeight(25);

    $currentRow = $headerRow + 1;
    foreach ($item_groups as $groupName => $groupData) {
        $sheet->mergeCells("A{$currentRow}:L{$currentRow}");
        $sheet->setCellValue("A{$currentRow}", $groupName);
        $sheet->getStyle("A{$currentRow}:L{$currentRow}")->applyFromArray([
            'font' => ['bold' => true], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']] ]);
        $currentRow++;

        $subSttCounter = 1;
        foreach ($groupData['items'] as $item) {
            $sheet->setCellValue("A{$currentRow}", $subSttCounter++);
            $sheet->mergeCells("B{$currentRow}:E{$currentRow}")->setCellValue("B{$currentRow}", $item['TenSanPham']);
            $sheet->mergeCells("F{$currentRow}:G{$currentRow}")->setCellValue("F{$currentRow}", $item['MaHang']);
            $sheet->mergeCells("H{$currentRow}:I{$currentRow}")->setCellValue("H{$currentRow}", $item['SoLuongThucXuat']);
            $sheet->setCellValue("J{$currentRow}", $item['TaiSo']);
            $sheet->mergeCells("K{$currentRow}:L{$currentRow}")->setCellValue("K{$currentRow}", $item['GhiChu']);
            
            $sheet->getStyle("A{$currentRow}:L{$currentRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("F{$currentRow}:J{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $currentRow++;
        }
    }
    
    // Phần ký tên
    $currentRow += 2;
    $titleRow = $currentRow;
    $instructionRow = $titleRow + 1;
    $nameRow = $instructionRow + 4;

    $sheet->mergeCells("A{$titleRow}:C{$titleRow}")->setCellValue("A{$titleRow}", "Người lập phiếu");
    $sheet->mergeCells("D{$titleRow}:F{$titleRow}")->setCellValue("D{$titleRow}", "Thủ kho");
    $sheet->mergeCells("G{$titleRow}:I{$titleRow}")->setCellValue("G{$titleRow}", "Người giao hàng");
    $sheet->mergeCells("J{$titleRow}:L{$titleRow}")->setCellValue("J{$titleRow}", "Người nhận hàng");
    $sheet->getStyle("A{$titleRow}:L{$titleRow}")->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
    
    $sheet->mergeCells("A{$instructionRow}:C{$instructionRow}")->setCellValue("A{$instructionRow}", "(Ký, họ tên)");
    $sheet->mergeCells("D{$instructionRow}:F{$instructionRow}")->setCellValue("D{$instructionRow}", "(Ký, họ tên)");
    $sheet->mergeCells("G{$instructionRow}:I{$instructionRow}")->setCellValue("G{$instructionRow}", "(Ký, họ tên)");
    $sheet->mergeCells("J{$instructionRow}:L{$instructionRow}")->setCellValue("J{$instructionRow}", "(Ký, họ tên)");
    $sheet->getStyle("A{$instructionRow}:L{$instructionRow}")->applyFromArray(['font' => ['italic' => true, 'size' => 10], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
    
    $sheet->getRowDimension($instructionRow + 1)->setRowHeight(50);
    
    $sheet->mergeCells("A{$nameRow}:C{$nameRow}")->setCellValue("A{$nameRow}", $header['NguoiLapPhieuHienThi']);
    $sheet->mergeCells("D{$nameRow}:F{$nameRow}")->setCellValue("D{$nameRow}", $header['ThuKho']);
    $sheet->mergeCells("G{$nameRow}:I{$nameRow}")->setCellValue("G{$nameRow}", $header['NguoiGiaoHang']);
    $sheet->mergeCells("J{$nameRow}:L{$nameRow}")->setCellValue("J{$nameRow}", $header['NguoiNhanHang']);
    $sheet->getStyle("A{$nameRow}:L{$nameRow}")->getAlignment()->applyFromArray(['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_BOTTOM]);

    // 5. Tối ưu cho in ấn
    $sheet->getPageSetup()
        ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
        ->setPaperSize(PageSetup::PAPERSIZE_A4)
        ->setFitToWidth(1) // Vừa với chiều rộng 1 trang
        ->setFitToHeight(0);

    $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);

    $sheet->getPageMargins()->setTop(0.5);
    $sheet->getPageMargins()->setRight(0.25);
    $sheet->getPageMargins()->setLeft(0.25);
    $sheet->getPageMargins()->setBottom(0.5);
    $sheet->getPageSetup()->setHorizontalCentered(true);
    
    // 6. Xuất file
    $fileName = "PXK_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $header['SoPhieuXuat']) . ".xlsx";
    ob_end_clean(); 
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Throwable $e) {
    ob_end_clean();
    error_log("Excel Export Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    die("Lỗi khi tạo file Excel: " . $e->getMessage());
}
?>


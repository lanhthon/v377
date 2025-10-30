<?php
// File: api/export_cccl_excel.php - Cập nhật vị trí logo bắt đầu từ cột H
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
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

// Hàm trợ giúp sắp xếp sản phẩm
function getProductSortRank($item) {
    $maHang = $item['MaHang'] ?? '';
    if (strpos($maHang, 'PUR') === 0) return 1;
    if (strpos($maHang, 'ULA') === 0) return 2;
    return 3;
}

try {
    $cccl_id = isset($_GET['cccl_id']) ? intval($_GET['cccl_id']) : 0;
    if ($cccl_id === 0) throw new Exception('ID CCCL không hợp lệ.');

    $pdo = get_db_connection();

    // Lấy dữ liệu header
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
    if (!$header) throw new Exception("Không tìm thấy dữ liệu CCCL.");

    // Áp dụng logic ưu tiên dữ liệu
    $header['TenCongTyKhach'] = !empty($header['TenCongTyKhach']) ? $header['TenCongTyKhach'] : $header['TenCongTyGoc'];
    $header['DiaChiKhach'] = !empty($header['DiaChiKhach']) ? $header['DiaChiKhach'] : $header['DiaChiKhachGoc'];
    $header['TenDuAn'] = !empty($header['TenDuAn']) ? $header['TenDuAn'] : $header['TenDuAnGoc'];
    $header['DiaChiDuAn'] = !empty($header['DiaChiDuAn']) ? $header['DiaChiDuAn'] : $header['DiaChiDuAnGoc'];
    $header['NguoiKiemTra'] = !empty($header['NguoiKiemTra']) ? $header['NguoiKiemTra'] : $header['NguoiKiemTraGoc'];

    // Lấy danh sách sản phẩm
    $sql_items = "
        SELECT 
            ct.MaHang, ct.TenSanPham, ct.SoLuong, ct.TieuChuanDatDuoc, 
            ct.GhiChuChiTiet, COALESCE(u.name, ct.DonViTinh, 'Bộ') AS DonViTinh 
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

    // Sắp xếp sản phẩm
    if (!empty($items)) {
        usort($items, function($a, $b) {
            return getProductSortRank($a) <=> getProductSortRank($b);
        });
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("CCCL " . ($header['SoCCCL'] ?? ''));

    // --- CÀI ĐẶT CHUNG ---
    $sheet->getDefaultRowDimension()->setRowHeight(15);
    $sheet->getStyle('A1:J100')->getFont()->setName('Arial')->setSize(10);
    $sheet->getStyle('A1:J100')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

    // --- BỐ CỤC HEADER ---
    $headerFillStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8F9FA']]];
    $sheet->getStyle('A1:D12')->applyFromArray($headerFillStyle);
    $sheet->getStyle('F1:J12')->applyFromArray($headerFillStyle);
    
    // --- KHỐI BÊN TRÁI (CỘT A-D) ---
    $ngayCap = new DateTime($header['NgayCap']);
    $ngayCapFormatted = 'Ngày ' . $ngayCap->format('d') . ' tháng ' . $ngayCap->format('m') . ' năm ' . $ngayCap->format('Y');

    $richTextSo = new RichText();
    $richTextSo->createTextRun('Số: ')->getFont()->setBold(true);
    $richTextSo->createText(htmlspecialchars($header['SoCCCL'] ?? ''));
    $sheet->mergeCells("A1:D1")->setCellValue('A1', $richTextSo);

    $richTextNgay = new RichText();
    $richTextNgay->createTextRun('Ngày cấp: ')->getFont()->setBold(true);
    $richTextNgay->createText($ngayCapFormatted);
    $sheet->mergeCells("A2:D2")->setCellValue('A2', $richTextNgay);

    $sheet->mergeCells("A4:D4")->setCellValue('A4', 'KHÁCH HÀNG:')->getStyle('A4')->getFont()->setBold(true)->setUnderline(true);
    $sheet->mergeCells("A5:D5")->setCellValue('A5', htmlspecialchars($header['TenCongTyKhach'] ?? ''))->getStyle('A5')->getFont()->setBold(true);
    
    $richTextDiaChiKhach = new RichText();
    $richTextDiaChiKhach->createTextRun('Địa chỉ khách hàng: ')->getFont()->setBold(true);
    $richTextDiaChiKhach->createText(htmlspecialchars($header['DiaChiKhach'] ?? ''));
    $sheet->mergeCells("A6:D6")->setCellValue('A6', $richTextDiaChiKhach)->getStyle('A6')->getAlignment()->setWrapText(true);
    $sheet->getRowDimension(6)->setRowHeight(30);

    $richTextDuAn = new RichText();
    $richTextDuAn->createTextRun('Tên dự án: ')->getFont()->setBold(true);
    $richTextDuAn->createText(htmlspecialchars($header['TenDuAn'] ?? ''));
    $sheet->mergeCells("A8:D8")->setCellValue('A8', $richTextDuAn);

    $richTextDiaChiDuAn = new RichText();
    $richTextDiaChiDuAn->createTextRun('Địa chỉ dự án: ')->getFont()->setBold(true);
    $richTextDiaChiDuAn->createText(htmlspecialchars($header['DiaChiDuAn'] ?? ''));
    $sheet->mergeCells("A9:D9")->setCellValue('A9', $richTextDiaChiDuAn)->getStyle('A9')->getAlignment()->setWrapText(true);
    $sheet->getRowDimension(9)->setRowHeight(30);
    
    $richTextSanPham = new RichText();
    $richTextSanPham->createTextRun('Tên sản phẩm: ')->getFont()->setBold(true);
    $richTextSanPham->createText(htmlspecialchars($header['SanPham'] ?? 'Gối đỡ PU Foam và Cùm Ula 3i-Fix'));
    $sheet->mergeCells("A11:D11")->setCellValue('A11', $richTextSanPham);

    $richTextYCSX = new RichText();
    $richTextYCSX->createTextRun('Số YCSX gốc: ')->getFont()->setBold(true);
    $richTextYCSX->createText(htmlspecialchars($header['SoYCSX'] ?? ''));
    $sheet->mergeCells("A12:D12")->setCellValue('A12', $richTextYCSX);

    // --- KHỐI BÊN PHẢI (CỘT F-J) ---
    $sheet->mergeCells("F1:J2")->setCellValue('F1', "CHỨNG NHẬN XUẤT XƯỞNG\nCHẤT LƯỢNG");
    $sheet->getStyle('F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getStyle('F1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF166534'));
    $sheet->getRowDimension(1)->setRowHeight(18);
    $sheet->getRowDimension(2)->setRowHeight(18);

    $sheet->mergeCells("F3:J6");
    if (file_exists('../logo.png')) {
        $drawing = new Drawing();
        $drawing->setName('Logo')->setPath('../logo.png');
        $drawing->setHeight(80);
        $drawing->setCoordinates('H3');
        $drawing->setOffsetX(40); // <-- ĐÃ THAY ĐỔI GIÁ TRỊ TẠI ĐÂY
        $drawing->setOffsetY(10);       
        $drawing->setWorksheet($sheet);
    }
    
    $sheet->mergeCells("F8:J8")->setCellValue('F8', 'NHÀ SẢN XUẤT:')->getStyle('F8')->getFont()->setBold(true)->setUnderline(true);
    $sheet->getStyle('F8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->mergeCells("F9:J9")->setCellValue('F9', 'CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 3I')->getStyle('F9')->getFont()->setBold(true)->setSize(11);
    $sheet->getStyle('F9')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension(9)->setRowHeight(30);

    $sheet->mergeCells("F10:J12")->setCellValue('F10', 'Địa chỉ: Số 14 Lô D31 – BT2 Tại Khu D, Khu Đô Thị Mới Hai Bên Đường Lê Trọng Tấn, Phường Dương Nội, TP Hà Nội, Việt Nam');
    $sheet->getStyle('F10')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F10')->getFont()->setSize(9);

    // --- BẢNG SẢN PHẨM (A-J) ---
    $currentRow = 14;
    $tableHeaderRow = $currentRow;
    // Tiêu đề cho 10 cột, một số để trống để gộp ô
    $tableHeaders = ['Stt.', 'Mã hàng', 'Tên sản phẩm', '', '', 'ĐVT', 'Số lượng', 'Tiêu chuẩn', 'Ghi chú', ''];
    $sheet->fromArray($tableHeaders, NULL, 'A' . $currentRow);

    // Gộp ô cho tiêu đề
    $sheet->mergeCells("C{$currentRow}:E{$currentRow}"); // Gộp cho 'Tên sản phẩm'
    $sheet->mergeCells("I{$currentRow}:J{$currentRow}"); // Gộp cho 'Ghi chú'

    $headerStyleArray = [
        'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF92D050']]
    ];
    // Áp dụng style cho toàn bộ dải ô A-J
    $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray($headerStyleArray);
    $sheet->getRowDimension($currentRow)->setRowHeight(22);
    $currentRow++;
    
    foreach ($items as $index => $item) {
        // Dữ liệu cho 10 cột, một số để trống
        $rowData = [
            $index + 1, 
            $item['MaHang'], 
            $item['TenSanPham'], 
            '', '', // Để trống cho Tên sản phẩm
            $item['DonViTinh'], 
            $item['SoLuong'], 
            $item['TieuChuanDatDuoc'] ?: 'Đạt', 
            $item['GhiChuChiTiet'],
            '' // Để trống cho Ghi chú
        ];
        $sheet->fromArray($rowData, NULL, 'A' . $currentRow);

        // Gộp ô cho từng dòng dữ liệu
        $sheet->mergeCells("C{$currentRow}:E{$currentRow}");
        $sheet->mergeCells("I{$currentRow}:J{$currentRow}");

        // Áp dụng style và căn chỉnh
        $sheet->getStyle("A{$currentRow}:J{$currentRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A{$currentRow}:J{$currentRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        
        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $sheet->getRowDimension($currentRow)->setRowHeight(-1); // Tự động điều chỉnh chiều cao
        $currentRow++;
    }
    
    // --- CHỮ KÝ (DI CHUYỂN VỀ BÊN PHẢI, BÊN DƯỚI BẢNG) ---
    $currentRow += 2;
    $sheet->mergeCells("H{$currentRow}:J{$currentRow}")->setCellValue("H{$currentRow}", 'TP. QUẢN LÝ CHẤT LƯỢNG');
    $styleSignatureTitle = $sheet->getStyle("H{$currentRow}");
    $styleSignatureTitle->getFont()->setBold(true);
    $styleSignatureTitle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $currentRow++;
    $sheet->mergeCells("H{$currentRow}:J{$currentRow}")->setCellValue("H{$currentRow}", '(Ký, ghi rõ họ tên)');
    $styleSignatureSubtitle = $sheet->getStyle("H{$currentRow}");
    $styleSignatureSubtitle->getFont()->setItalic(true)->setSize(9);
    $styleSignatureSubtitle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $currentRow += 4;
    $sheet->mergeCells("H{$currentRow}:J{$currentRow}")->setCellValue("H{$currentRow}", htmlspecialchars($header['NguoiKiemTra']));
    $styleSignatureName = $sheet->getStyle("H{$currentRow}");
    $styleSignatureName->getFont()->setBold(true);
    $styleSignatureName->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // --- ĐỊNH DẠNG CHIỀU RỘNG CỘT (A-J) ---
    $sheet->getColumnDimension('A')->setWidth(5);   // Stt
    $sheet->getColumnDimension('B')->setWidth(18);  // Mã hàng
    $sheet->getColumnDimension('C')->setWidth(20);  // Tên sản phẩm (phần 1)
    $sheet->getColumnDimension('D')->setWidth(20);  // Tên sản phẩm (phần 2)
    $sheet->getColumnDimension('E')->setWidth(15);  // Tên sản phẩm (phần 3)
    $sheet->getColumnDimension('F')->setWidth(8);   // ĐVT
    $sheet->getColumnDimension('G')->setWidth(10);  // Số lượng
    $sheet->getColumnDimension('H')->setWidth(15);  // Tiêu chuẩn
    $sheet->getColumnDimension('I')->setWidth(15);  // Ghi chú (phần 1)
    $sheet->getColumnDimension('J')->setWidth(15);  // Ghi chú (phần 2)

    // --- TỐI ƯU HÓA IN ẤN ---
    $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
    $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0); 
    $sheet->getPageMargins()->setTop(0.75)->setRight(0.2)->setLeft(0.2)->setBottom(0.75);
    $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($tableHeaderRow, $tableHeaderRow);

    // --- XUẤT FILE ---
    $fileName = "CCCL_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $header['SoCCCL']) . ".xlsx";
    ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Throwable $e) {
    ob_end_clean();
    error_log("Export Excel Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    die("Lỗi nghiêm trọng khi tạo file Excel. Vui lòng kiểm tra log để biết chi tiết. Message: " . $e->getMessage());
}
?>
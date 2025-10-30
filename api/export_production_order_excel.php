<?php
/**
 * api/export_production_order_excel.php
 * Endpoint để tạo và xuất file Excel cho một Lệnh Sản Xuất.
 * Phiên bản hoàn chỉnh: Tối ưu dữ liệu, layout và cài đặt in ấn.
 */

// Bắt buộc phải có để tránh lỗi "headers already sent"
ob_start();

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

try {
    // =================================================================
    // 1. LẤY DỮ LIỆU TỪ DATABASE
    // =================================================================
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID Lệnh sản xuất không hợp lệ.');
    }
    $lenhSX_ID = (int)$_GET['id'];
    $conn->set_charset("utf8mb4");

    // Lấy thông tin chính của LSX, đã sửa để hoạt động với mọi loại LSX
    $stmt_lsx = $conn->prepare("
        SELECT 
            lsx.*, 
            dh.SoYCSX,
            COALESCE(u.HoTen, 'Hệ thống') as NguoiYeuCau
        FROM lenh_san_xuat lsx
        LEFT JOIN donhang dh ON lsx.YCSX_ID = dh.YCSX_ID
        LEFT JOIN nguoidung u ON lsx.NguoiYeuCau_ID = u.UserID
        WHERE lsx.LenhSX_ID = ?
    ");
    $stmt_lsx->bind_param("i", $lenhSX_ID);
    $stmt_lsx->execute();
    $lsx_info = $stmt_lsx->get_result()->fetch_assoc();
    $stmt_lsx->close();

    if (!$lsx_info) {
        throw new Exception("Không tìm thấy lệnh sản xuất.");
    }

    // Lấy thông tin chi tiết các sản phẩm cần sản xuất
    $stmt_items = $conn->prepare("
        SELECT
            ct.SoLuongCayCanSX, ct.SoLuongBoCanSX, ct.GhiChu,
            ct.TrangThai AS TrangThaiChiTiet, v.variant_sku AS MaBTP, u.name AS DonViTinh
        FROM chitiet_lenh_san_xuat ct
        JOIN variants v ON ct.SanPhamID = v.variant_id
        LEFT JOIN products p ON v.product_id = p.product_id
        LEFT JOIN units u ON p.base_unit_id = u.unit_id
        WHERE ct.LenhSX_ID = ? ORDER BY ct.ChiTiet_LSX_ID ASC
    ");
    $stmt_items->bind_param("i", $lenhSX_ID);
    $stmt_items->execute();
    $lsx_items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();
    $conn->close();

    // =================================================================
    // 2. KHỞI TẠO VÀ CẤU HÌNH EXCEL
    // =================================================================
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("LenhSanXuat");
    $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);
    
    // --- [TỐI ƯU IN ẤN] Cài đặt trang in ---
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
    $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
    $sheet->getPageMargins()->setTop(0.75)->setBottom(0.75)->setLeft(0.25)->setRight(0.25);
    // Tự động co giãn để vừa chiều ngang 1 trang A4
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0); // Cho phép kéo dài nhiều trang nếu cần

    // --- [TỐI ƯU CỘT] Cài đặt độ rộng cột ---
    $sheet->getColumnDimension('A')->setAutoSize(true);   // Stt
    $sheet->getColumnDimension('B')->setWidth(25);        // Mã hàng
    $sheet->getColumnDimension('C')->setAutoSize(true);   // Khối lượng
    $sheet->getColumnDimension('D')->setAutoSize(true);   // Đơn vị
    $sheet->getColumnDimension('E')->setAutoSize(true);   // Mục đích
    $sheet->getColumnDimension('F')->setAutoSize(true);   // Trạng thái
    $sheet->getColumnDimension('G')->setWidth(35);        // Ghi chú (sẽ tự xuống dòng)

    // =================================================================
    // 3. VẼ HEADER VÀ THÔNG TIN CHUNG
    // =================================================================
    $logoPath = __DIR__ . '/../logo.png';
    if (file_exists($logoPath)) {
        $drawing = new Drawing();
        $drawing->setPath($logoPath);
        $drawing->setCoordinates('A1');
        $drawing->setHeight(70);
        $drawing->setWorksheet($sheet);
    }
    
    $sheet->mergeCells('C1:G1');
    $sheet->setCellValue('C1', 'LỆNH SẢN XUẤT - LSX');
    $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(20);
    $sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    
    $sheet->mergeCells('C2:G2');
    $sheet->setCellValue('C2', 'Số: ' . htmlspecialchars($lsx_info['SoLenhSX'] ?? ''));
    $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $currentRow = 4;
    $sheet->setCellValue('A'.$currentRow, 'Người nhận:')->getStyle('A'.$currentRow)->getFont()->setBold(true);
    $sheet->setCellValue('B'.$currentRow, $lsx_info['NguoiNhanSX'] ?? 'Mr. Thiết');
    $sheet->setCellValue('D'.$currentRow, 'Đơn vị:')->getStyle('D'.$currentRow)->getFont()->setBold(true);
    $sheet->setCellValue('E'.$currentRow, $lsx_info['BoPhanSX'] ?? 'Đội trưởng SX');

    $currentRow++;
    $sheet->setCellValue('A'.$currentRow, 'Đơn hàng gốc:')->getStyle('A'.$currentRow)->getFont()->setBold(true);
    $sheet->setCellValue('B'.$currentRow, $lsx_info['SoYCSX'] ?? 'Lưu kho');
    $sheet->setCellValue('D'.$currentRow, 'Người yêu cầu:')->getStyle('D'.$currentRow)->getFont()->setBold(true);
    $sheet->setCellValue('E'.$currentRow, $lsx_info['NguoiYeuCau'] ?? 'Hệ thống');

    $currentRow++;
    $sheet->setCellValue('A'.$currentRow, 'Ngày yêu cầu:')->getStyle('A'.$currentRow)->getFont()->setBold(true);
    $sheet->setCellValue('B'.$currentRow, date('d/m/Y', strtotime($lsx_info['NgayTao'])));
    $sheet->getStyle('B'.$currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');
    $sheet->getStyle('B'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('D'.$currentRow, 'Ngày hoàn thành:')->getStyle('D'.$currentRow)->getFont()->setBold(true);
    $sheet->setCellValue('E'.$currentRow, date('d/m/Y', strtotime($lsx_info['NgayHoanThanhUocTinh'])));
    $diffDays = '';
    if($lsx_info['NgayHoanThanhUocTinh'] && $lsx_info['NgayTao']) {
        $diffDays = '(Tổng ngày: ' . (new DateTime($lsx_info['NgayTao']))->diff(new DateTime($lsx_info['NgayHoanThanhUocTinh']))->days . ' ngày)';
    }
    $sheet->setCellValue('F'.$currentRow, $diffDays);
    $sheet->getStyle('E'.$currentRow.':F'.$currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');
    
    $headerRowNumber = $currentRow + 2; // Dòng tiêu đề bảng sản phẩm
    $currentRow = $headerRowNumber;

    // =================================================================
    // 4. VẼ BẢNG CHI TIẾT SẢN PHẨM
    // =================================================================
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
    ];
    $sheet->getStyle('A'.$headerRowNumber.':G'.$headerRowNumber)->applyFromArray($headerStyle);
    $sheet->setCellValue('A'.$headerRowNumber, 'Stt.');
    $sheet->setCellValue('B'.$headerRowNumber, 'Mã hàng');
    $sheet->setCellValue('C'.$headerRowNumber, 'Khối lượng sản xuất');
    $sheet->setCellValue('D'.$headerRowNumber, 'Đơn vị');
    $sheet->setCellValue('E'.$headerRowNumber, 'Mục đích');
    $sheet->setCellValue('F'.$headerRowNumber, 'Trạng thái');
    $sheet->setCellValue('G'.$headerRowNumber, 'Ghi chú');
    
    $currentRow++;
    $stt = 1;
    $rowStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        'alignment' => [ 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true ] // Bật wrap text cho tất cả các dòng
    ];
    
    //$isUlaType = ($lsx_info['LoaiLSX'] === 'ULA');
    $mucDich = !empty($lsx_info['SoYCSX']) ? 'Đơn hàng' : 'Lưu kho';

   foreach ($lsx_items as $item) {
        $sheet->getStyle('A'.$currentRow.':G'.$currentRow)->applyFromArray($rowStyle);
        
        // [CẬP NHẬT] Logic chọn số lượng dựa trên MaBTP của từng item
        $maBTP = $item['MaBTP'] ?? '';
        $isUlaItem = str_starts_with($maBTP, 'ULA');
        
        $soLuongHienThi = $isUlaItem 
            ? ($item['SoLuongBoCanSX'] ?? 0) 
            : ($item['SoLuongCayCanSX'] ?? 0);
            
        $donViTinh = $item['DonViTinh'] ?? '';

        $sheet->setCellValue('A'.$currentRow, $stt++);
        $sheet->setCellValue('B'.$currentRow, $item['MaBTP']);
        $sheet->setCellValue('C'.$currentRow, $soLuongHienThi)->getStyle('C'.$currentRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->setCellValue('D'.$currentRow, $donViTinh);
        $sheet->setCellValue('E'.$currentRow, $mucDich);
        $sheet->setCellValue('F'.$currentRow, $item['TrangThaiChiTiet']);
        $sheet->setCellValue('G'.$currentRow, $item['GhiChu']);
        
        // Căn giữa cho các cột cần thiết
        $sheet->getStyle('A'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->getRowDimension($currentRow)->setRowHeight(-1); // Tự động điều chỉnh chiều cao dòng
        $currentRow++;
    }

    // =================================================================
    // 5. HOÀN TẤT VÀ XUẤT FILE
    // =================================================================

    // [TỐI ƯU IN ẤN] Đặt vùng in và lặp lại tiêu đề khi in nhiều trang
    $lastRow = $currentRow - 1;
    $sheet->getPageSetup()->setPrintArea('A1:G'.$lastRow);
    $sheet->getPageSetup()->setRowsToRepeatAtTop([$headerRowNumber, $headerRowNumber]);

    $fileName = "LSX-" . preg_replace('/[^a-zA-Z0-9_-]/', '', $lsx_info['SoLenhSX']) . ".xlsx";
    
    ob_end_clean(); // Xóa bộ đệm đầu ra trước khi gửi header

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    error_log("Lỗi khi tạo Excel: " . $e->getMessage());
    echo "Đã xảy ra lỗi trong quá trình tạo file Excel: " . htmlspecialchars($e->getMessage());
}
?>
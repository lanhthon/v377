<?php
// File: api/export_list_pxk_btp_excel.php
require_once '../config/db_config.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    $pdo = get_db_connection();

    // Lấy tham số bộ lọc
    $soPhieu = $_GET['soPhieu'] ?? '';
    $soYCSX = $_GET['soYCSX'] ?? '';
    $startDate = $_GET['startDate'] ?? '';
    $endDate = $_GET['endDate'] ?? '';
    $ghiChu = $_GET['ghiChu'] ?? '';

    // Xây dựng điều kiện WHERE động
    $whereClauses = ["pxk.LoaiPhieu IN ('xuat_btp_cat', 'xuat_btp_khac')"];
    $params = [];

    if (!empty($soPhieu)) {
        $whereClauses[] = "pxk.SoPhieuXuat LIKE :soPhieu";
        $params[':soPhieu'] = '%' . $soPhieu . '%';
    }
    if (!empty($soYCSX)) {
        $whereClauses[] = "dh.SoYCSX LIKE :soYCSX";
        $params[':soYCSX'] = '%' . $soYCSX . '%';
    }
    if (!empty($startDate)) {
        $whereClauses[] = "pxk.NgayXuat >= :startDate";
        $params[':startDate'] = $startDate;
    }
    if (!empty($endDate)) {
        $whereClauses[] = "pxk.NgayXuat <= :endDate";
        $params[':endDate'] = $endDate;
    }
    if (!empty($ghiChu)) {
        $whereClauses[] = "pxk.GhiChu LIKE :ghiChu";
        $params[':ghiChu'] = '%' . $ghiChu . '%';
    }
    
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

    // Lấy toàn bộ dữ liệu (không phân trang)
    $sql = "SELECT 
                pxk.SoPhieuXuat,
                dh.SoYCSX,
                pxk.NgayXuat,
                nd.HoTen AS NguoiTao,
                pxk.GhiChu
            FROM phieuxuatkho pxk
            LEFT JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID 
            LEFT JOIN nguoidung nd ON pxk.NguoiTaoID = nd.UserID
            $whereSql
            ORDER BY pxk.PhieuXuatKhoID DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Bắt đầu tạo file Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Thiết lập tiêu đề
    $sheet->setCellValue('A1', 'DANH SÁCH PHIẾU XUẤT KHO BÁN THÀNH PHẨM');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Thêm thông tin bộ lọc
    $row = 3;
    if (!empty($startDate) || !empty($endDate)) {
        $dateFilter = 'Từ ngày: ' . ($startDate ? date('d/m/Y', strtotime($startDate)) : '...') . ' đến ngày: ' . ($endDate ? date('d/m/Y', strtotime($endDate)) : '...');
        $sheet->setCellValue('A' . $row, $dateFilter);
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $row++;
    }

    // Tiêu đề cột
    $headers = ['STT', 'Số Phiếu Xuất', 'Số YCSX Gốc', 'Ngày Xuất', 'Người Tạo', 'Ghi Chú'];
    $headerRow = $row + 1;
    $sheet->fromArray($headers, NULL, 'A' . $headerRow);

    // Định dạng tiêu đề
    $headerRange = 'A' . $headerRow . ':F' . $headerRow;
    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);

    // Thêm dữ liệu
    $dataRow = $headerRow + 1;
    $stt = 1;
    foreach ($data as $item) {
        $sheet->setCellValue('A' . $dataRow, $stt);
        $sheet->setCellValue('B' . $dataRow, $item['SoPhieuXuat']);
        $sheet->setCellValue('C' . $dataRow, $item['SoYCSX'] ?: 'N/A');
        $sheet->setCellValue('D' . $dataRow, date('d/m/Y', strtotime($item['NgayXuat'])));
        $sheet->setCellValue('E' . $dataRow, $item['NguoiTao'] ?: 'N/A');
        $sheet->setCellValue('F' . $dataRow, $item['GhiChu'] ?: '');
        $dataRow++;
        $stt++;
    }

    // Thêm viền
    if ($dataRow > $headerRow + 1) {
        $dataRange = 'A' . ($headerRow + 1) . ':F' . ($dataRow - 1);
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    // Tự động điều chỉnh độ rộng
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Thêm dòng tổng kết
    if (count($data) > 0) {
        $summaryRow = $dataRow + 1;
        $sheet->setCellValue('A' . $summaryRow, 'Tổng cộng: ' . count($data) . ' phiếu');
        $sheet->mergeCells('A' . $summaryRow . ':F' . $summaryRow);
        $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
    }

    // Xuất file
    $filename = 'DS_PXK_BTP_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    error_log("Excel Export Error (PXK BTP): " . $e->getMessage());
    die("Lỗi khi xuất file Excel: " . $e->getMessage());
}
?>

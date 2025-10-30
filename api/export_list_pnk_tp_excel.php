<?php
// File: api/export_list_pnk_tp_excel.php
// Xuất danh sách phiếu nhập kho thành phẩm ra file Excel

require_once '../config/db_config.php';
require_once '../vendor/autoload.php'; // Assuming PhpSpreadsheet is installed via Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    $pdo = get_db_connection();
    
    // Get filter parameters
    $so_phieu = isset($_GET['so_phieu']) ? trim($_GET['so_phieu']) : '';
    $so_ycsx = isset($_GET['so_ycsx']) ? trim($_GET['so_ycsx']) : '';
    $tu_ngay = isset($_GET['tu_ngay']) ? $_GET['tu_ngay'] : '';
    $den_ngay = isset($_GET['den_ngay']) ? $_GET['den_ngay'] : '';
    $ly_do = isset($_GET['ly_do']) ? trim($_GET['ly_do']) : '';
    $nguoi_tao = isset($_GET['nguoi_tao']) ? trim($_GET['nguoi_tao']) : '';
    
    // Build WHERE conditions
    $where = " WHERE pnk.LoaiPhieu IN ('nhap_tp_tu_sx', 'nhap_tp_khac')";
    $params = [];
    
    if (!empty($so_phieu)) {
        $where .= " AND pnk.SoPhieuNhapKho LIKE :so_phieu";
        $params[':so_phieu'] = '%' . $so_phieu . '%';
    }
    
    if (!empty($so_ycsx)) {
        $where .= " AND dh.SoYCSX LIKE :so_ycsx";
        $params[':so_ycsx'] = '%' . $so_ycsx . '%';
    }
    
    if (!empty($tu_ngay)) {
        $where .= " AND pnk.NgayNhap >= :tu_ngay";
        $params[':tu_ngay'] = $tu_ngay;
    }
    
    if (!empty($den_ngay)) {
        $where .= " AND pnk.NgayNhap <= :den_ngay";
        $params[':den_ngay'] = $den_ngay;
    }
    
    if (!empty($ly_do)) {
        $where .= " AND pnk.LyDoNhap = :ly_do";
        $params[':ly_do'] = $ly_do;
    }
    
    if (!empty($nguoi_tao)) {
        $where .= " AND nd.HoTen LIKE :nguoi_tao";
        $params[':nguoi_tao'] = '%' . $nguoi_tao . '%';
    }
    
    // Get data from database
    $sql = "SELECT 
                pnk.PhieuNhapKhoID,
                pnk.SoPhieuNhapKho,
                pnk.NgayNhap,
                pnk.LyDoNhap,
                dh.SoYCSX,
                nd.HoTen AS TenNguoiTao,
                pnk.TongTien,
                pnk.GhiChu,
                dh.TenCongTy,
                dh.TenDuAn
            FROM phieunhapkho pnk
            LEFT JOIN donhang dh ON pnk.YCSX_ID = dh.YCSX_ID
            LEFT JOIN nguoidung nd ON pnk.NguoiTaoID = nd.UserID
            $where
            ORDER BY pnk.PhieuNhapKhoID DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create new Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set title
    $sheet->setCellValue('A1', 'DANH SÁCH PHIẾU NHẬP KHO THÀNH PHẨM');
    $sheet->mergeCells('A1:J1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Add filter info if any
    $row = 3;
    if (!empty($tu_ngay) || !empty($den_ngay)) {
        $dateFilter = 'Từ ngày: ' . ($tu_ngay ?: '...') . ' đến ngày: ' . ($den_ngay ?: '...');
        $sheet->setCellValue('A' . $row, $dateFilter);
        $sheet->mergeCells('A' . $row . ':J' . $row);
        $row++;
    }
    
    // Headers
    $headers = [
        'STT',
        'Số Phiếu Nhập Kho',
        'Ngày Nhập',
        'Số YCSX',
        'Tên Công Ty',
        'Tên Dự Án',
        'Người Tạo',
        'Lý Do Nhập',
        'Tổng Tiền',
        'Ghi Chú'
    ];
    
    $headerRow = $row + 1;
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $headerRow, $header);
        $col++;
    }
    
    // Style headers
    $headerRange = 'A' . $headerRow . ':J' . $headerRow;
    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E0E0E0']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN
            ]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);
    
    // Add data
    $dataRow = $headerRow + 1;
    $stt = 1;
    foreach ($data as $item) {
        $sheet->setCellValue('A' . $dataRow, $stt);
        $sheet->setCellValue('B' . $dataRow, $item['SoPhieuNhapKho']);
        $sheet->setCellValue('C' . $dataRow, date('d/m/Y', strtotime($item['NgayNhap'])));
        $sheet->setCellValue('D' . $dataRow, $item['SoYCSX'] ?: 'N/A');
        $sheet->setCellValue('E' . $dataRow, $item['TenCongTy'] ?: 'N/A');
        $sheet->setCellValue('F' . $dataRow, $item['TenDuAn'] ?: 'N/A');
        $sheet->setCellValue('G' . $dataRow, $item['TenNguoiTao'] ?: 'N/A');
        $sheet->setCellValue('H' . $dataRow, $item['LyDoNhap']);
        $sheet->setCellValue('I' . $dataRow, number_format($item['TongTien'], 0, ',', '.'));
        $sheet->setCellValue('J' . $dataRow, $item['GhiChu'] ?: '');
        
        $dataRow++;
        $stt++;
    }
    
    // Apply borders to data
    $dataRange = 'A' . ($headerRow + 1) . ':J' . ($dataRow - 1);
    if ($dataRow > $headerRow + 1) {
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);
    }
    
    // Auto-size columns
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Add summary row
    if (count($data) > 0) {
        $sheet->setCellValue('A' . ($dataRow + 1), 'Tổng cộng: ' . count($data) . ' phiếu');
        $sheet->mergeCells('A' . ($dataRow + 1) . ':H' . ($dataRow + 1));
        $sheet->getStyle('A' . ($dataRow + 1))->getFont()->setBold(true);
        
        // Calculate total amount
        $totalAmount = array_sum(array_column($data, 'TongTien'));
        $sheet->setCellValue('I' . ($dataRow + 1), number_format($totalAmount, 0, ',', '.'));
        $sheet->getStyle('I' . ($dataRow + 1))->getFont()->setBold(true);
    }
    
    // Set filename
    $filename = 'DS_PNK_TP_' . date('Ymd_His') . '.xlsx';
    
    // Output headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi xuất Excel: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
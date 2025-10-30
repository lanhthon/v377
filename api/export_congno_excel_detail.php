<?php
// File: api/export_congno_excel_detail.php

// IMPORTANT: This script requires the PhpSpreadsheet library.
// Please install it via Composer: composer require phpoffice/phpoffsheet
require '../vendor/autoload.php';
require_once '../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// --- 1. Get Filters (same as get_congno_data.php) ---
global $conn;

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';
$filter_type = $_GET['filter_type'] ?? 'all';

$base_query = "
    FROM donhang dh
    LEFT JOIN quanly_congno qlcn ON dh.YCSX_ID = qlcn.YCSX_ID
    LEFT JOIN congty ct ON dh.CongTyID = ct.CongTyID
    LEFT JOIN phieuxuatkho pxk ON dh.YCSX_ID = pxk.YCSX_ID AND pxk.LoaiPhieu = 'xuat_thanh_pham'
    LEFT JOIN chuanbihang cbh ON pxk.CBH_ID = cbh.CBH_ID
";
$conditions = ["cbh.TrangThai = 'Đã giao hàng'"];
$params = [];
$types = '';

// Build conditions and params based on filters
if (!empty($search)) {
    $conditions[] = "(dh.SoYCSX LIKE ? OR ct.MaCongTy LIKE ? OR dh.TenDuAn LIKE ?)";
    $search_param = "%{$search}%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= 'sss';
}
if (!empty($status)) {
    $conditions[] = "IFNULL(qlcn.TrangThaiThanhToan, 'Chưa thanh toán') = ?";
    $params[] = $status;
    $types .= 's';
}
if (!empty($startDate)) {
    $conditions[] = "pxk.NgayXuat >= ?";
    $params[] = $startDate;
    $types .= 's';
}
if (!empty($endDate)) {
    $conditions[] = "pxk.NgayXuat <= ?";
    $params[] = $endDate;
    $types .= 's';
}
if ($filter_type === 'overdue') {
    $conditions[] = "IFNULL(qlcn.ThoiHanThanhToan, DATE_ADD(pxk.NgayXuat, INTERVAL IFNULL(ct.SoNgayThanhToan, 30) DAY)) < CURDATE() AND IFNULL(qlcn.TrangThaiThanhToan, 'Chưa thanh toán') != 'Đã thanh toán'";
}

$where_clause = " WHERE " . implode(" AND ", $conditions);

// --- 2. Fetch Main Data (NO LIMIT/OFFSET) ---
$data_query_sql = "
    SELECT
        dh.YCSX_ID, dh.SoYCSX, ct.MaCongTy, ct.TenCongTy, qlcn.DonViTra, dh.TenDuAn, pxk.NgayXuat AS NgayGiaoHang,
        IFNULL(qlcn.ThoiHanThanhToan, DATE_ADD(pxk.NgayXuat, INTERVAL IFNULL(ct.SoNgayThanhToan, 30) DAY)) AS ThoiHanThanhToan,
        qlcn.NgayXuatHoaDon, dh.TongTien AS TongGiaTri, IFNULL(qlcn.SoTienTamUng, 0) AS SoTienTamUng,
        IFNULL(qlcn.GiaTriConLai, dh.TongTien - IFNULL(qlcn.SoTienTamUng, 0)) AS GiaTriConLai,
        IFNULL(qlcn.TrangThaiThanhToan, 'Chưa thanh toán') AS TrangThaiThanhToan
    " . $base_query . $where_clause . "
    GROUP BY dh.YCSX_ID ORDER BY pxk.NgayXuat DESC, dh.YCSX_ID DESC";

$stmt = $conn->prepare($data_query_sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$congno_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// --- 3. Create Spreadsheet and Set Styles ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Bao Cao Cong No Chi Tiet');

// Styles
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4A90E2']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];
$masterRowStyle = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
];
$totalRowStyle = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1E7DD']],
];

// --- 4. Populate Headers ---
$sheet->setCellValue('A1', 'BÁO CÁO CÔNG NỢ CHI TIẾT');
$sheet->mergeCells('A1:M1');
$sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
$sheet->setCellValue('A2', 'Ngày xuất: ' . date('d/m/Y H:i:s'));
$sheet->mergeCells('A2:M2');
$sheet->getStyle('A2')->applyFromArray(['font' => ['italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

$headers = [
    'Số YCSX', 'Khách Hàng', 'Đơn Vị Trả', 'Ngày Giao Hàng', 'Hạn TT', 'Ngày Xuất HĐ', 
    'Mã Hàng', 'Tên Sản Phẩm', 'Số Lượng', 'Đơn Giá', 'Thành Tiền',
    'Tổng Đã Trả', 'Tổng Còn Lại'
];
$sheet->fromArray($headers, null, 'A4');
$sheet->getStyle('A4:M4')->applyFromArray($headerStyle);

// --- 5. Populate Data ---
$currentRow = 5;
$grandTotalValue = 0;
$grandTotalPaid = 0;
$grandTotalRemaining = 0;

foreach ($congno_data as $master) {
    // Fetch detail data for this order
    $detail_stmt = $conn->prepare("SELECT MaHang, TenSanPham, SoLuong, DonGia, ThanhTien FROM chitiet_donhang WHERE DonHangID = ? ORDER BY ThuTuHienThi");
    $detail_stmt->bind_param("i", $master['YCSX_ID']);
    $detail_stmt->execute();
    $details = $detail_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $detail_stmt->close();
    
    $detailRowCount = count($details) > 0 ? count($details) : 1;
    $startMergeRow = $currentRow;
    $endMergeRow = $currentRow + $detailRowCount - 1;

    // Merge cells for master data
    if ($detailRowCount > 1) {
        $sheet->mergeCells("A{$startMergeRow}:A{$endMergeRow}");
        $sheet->mergeCells("B{$startMergeRow}:B{$endMergeRow}");
        $sheet->mergeCells("C{$startMergeRow}:C{$endMergeRow}");
        $sheet->mergeCells("D{$startMergeRow}:D{$endMergeRow}");
        $sheet->mergeCells("E{$startMergeRow}:E{$endMergeRow}");
        $sheet->mergeCells("F{$startMergeRow}:F{$endMergeRow}");
        $sheet->mergeCells("L{$startMergeRow}:L{$endMergeRow}");
        $sheet->mergeCells("M{$startMergeRow}:M{$endMergeRow}");
    }
    
    // Write master data
    $sheet->setCellValue("A{$currentRow}", $master['SoYCSX']);
    $sheet->setCellValue("B{$currentRow}", $master['MaCongTy'] . "\n" . $master['TenCongTy']);
    $sheet->setCellValue("C{$currentRow}", $master['DonViTra']);
    $sheet->setCellValue("D{$currentRow}", $master['NgayGiaoHang'] ? date('d/m/Y', strtotime($master['NgayGiaoHang'])) : '');
    $sheet->setCellValue("E{$currentRow}", $master['ThoiHanThanhToan'] ? date('d/m/Y', strtotime($master['ThoiHanThanhToan'])) : '');
    $sheet->setCellValue("F{$currentRow}", $master['NgayXuatHoaDon'] ? date('d/m/Y', strtotime($master['NgayXuatHoaDon'])) : '');
    $sheet->setCellValue("L{$currentRow}", $master['SoTienTamUng']);
    $sheet->setCellValue("M{$currentRow}", $master['GiaTriConLai']);

    // Write detail data
    if (!empty($details)) {
        foreach ($details as $detail) {
            $sheet->setCellValue("G{$currentRow}", $detail['MaHang']);
            $sheet->setCellValue("H{$currentRow}", $detail['TenSanPham']);
            $sheet->setCellValue("I{$currentRow}", $detail['SoLuong']);
            $sheet->setCellValue("J{$currentRow}", $detail['DonGia']);
            $sheet->setCellValue("K{$currentRow}", $detail['ThanhTien']);
            $currentRow++;
        }
    } else {
        // If there are no details, still advance the row counter
        $currentRow++;
    }

    // Apply styles and update totals
    $sheet->getStyle("A{$startMergeRow}:M{$endMergeRow}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    $sheet->getStyle("A{$startMergeRow}:F{$endMergeRow}")->applyFromArray($masterRowStyle);
    $grandTotalValue += $master['TongGiaTri'];
    $grandTotalPaid += $master['SoTienTamUng'];
    $grandTotalRemaining += $master['GiaTriConLai'];
}

// --- 6. Add Totals Row ---
$currentRow++;
$sheet->setCellValue("J{$currentRow}", 'TỔNG CỘNG');
$sheet->setCellValue("K{$currentRow}", $grandTotalValue);
$sheet->setCellValue("L{$currentRow}", $grandTotalPaid);
$sheet->setCellValue("M{$currentRow}", $grandTotalRemaining);
$sheet->getStyle("J{$currentRow}:M{$currentRow}")->applyFromArray($totalRowStyle);


// --- 7. Final Formatting and Output ---
// Set column widths
foreach (range('A', 'M') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
// Format numbers
$sheet->getStyle('I:M')->getNumberFormat()->setFormatCode('#,##0');

// Set headers for download
$filename = "BaoCao_CongNo_ChiTiet_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$conn->close();
exit;
?>


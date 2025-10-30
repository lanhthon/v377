<?php
// File: api/export_quotes_list_excel.php

header('Content-Type: application/json'); // Mặc định là JSON, sẽ thay đổi sau khi tạo Excel

require_once __DIR__ . '/../vendor/autoload.php'; // Đường dẫn đến autoload của Composer
require_once '../config/database.php'; // Kết nối cơ sở dữ liệu

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Hàm để lấy mã khách hàng từ số báo giá
function getCustomerCodeFromQuoteNumber($quoteNumber) {
    if (empty($quoteNumber)) {
        return '';
    }
    $parts = explode('/', $quoteNumber);
    return end($parts); // Lấy phần tử cuối cùng
}

try {
    // Lấy các tham số lọc từ request GET
    $startDate = $_GET['startDate'] ?? '';
    $endDate = $_GET['endDate'] ?? '';
    $companyName = $_GET['companyName'] ?? '';
    $status = $_GET['status'] ?? '';

    // Xây dựng câu lệnh SQL có bộ lọc
    $sql = "SELECT BaoGiaID, SoBaoGia, TenCongTy, NgayBaoGia, TongTienSauThue, TrangThai
            FROM baogia
            WHERE 1=1"; // Điều kiện ban đầu luôn đúng

    $params = [];
    $types = "";

    if (!empty($startDate)) {
        $sql .= " AND NgayBaoGia >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    if (!empty($endDate)) {
        $sql .= " AND NgayBaoGia <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    if (!empty($companyName)) {
        $sql .= " AND TenCongTy LIKE ?";
        $params[] = '%' . $companyName . '%';
        $types .= "s";
    }
    if (!empty($status)) {
        $sql .= " AND TrangThai = ?";
        $params[] = $status;
        $types .= "s";
    }

    $sql .= " ORDER BY NgayBaoGia DESC, BaoGiaID DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $quotes = [];
    while ($row = $result->fetch_assoc()) {
        $quotes[] = $row;
    }
    $stmt->close();
    $conn->close();

    // --- Bắt đầu tạo file Excel ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Danh sách báo giá');

    // Headers - ĐÃ HOÁN ĐỔI THỨ TỰ
    $headers = ['STT', 'Số Báo Giá', 'Mã Khách Hàng', 'Khách Hàng', 'Ngày Tạo', 'Tổng Tiền', 'Trạng Thái'];
    $sheet->fromArray($headers, NULL, 'A1');

    // Apply header style
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4CAF50']], // Green background
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];
    $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);


    // Data rows
    $rowNum = 2;
    foreach ($quotes as $index => $quote) {
        $customerCode = getCustomerCodeFromQuoteNumber($quote['SoBaoGia']);
        $sheet->setCellValue('A' . $rowNum, $index + 1);
        $sheet->setCellValue('B' . $rowNum, $quote['SoBaoGia']);
        $sheet->setCellValue('C' . $rowNum, $customerCode); // Vị trí mới cho Mã Khách Hàng
        $sheet->setCellValue('D' . $rowNum, $quote['TenCongTy']); // Vị trí mới cho Tên Công Ty
        $sheet->setCellValue('E' . $rowNum, $quote['NgayBaoGia']);
        $sheet->setCellValue('F' . $rowNum, $quote['TongTienSauThue']);
        $sheet->setCellValue('G' . $rowNum, $quote['TrangThai']);

        // Format currency column (column F)
        $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');

        // Apply border to all cells
        $sheet->getStyle('A' . $rowNum . ':G' . $rowNum)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCCCCCC'], // Light gray border
                ],
            ],
        ]);

        $rowNum++;
    }

    // Auto-size columns
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator("3iGreen Quote System")
        ->setLastModifiedBy("3iGreen Quote System")
        ->setTitle("Danh Sach Bao Gia")
        ->setSubject("Danh Sach Bao Gia")
        ->setDescription("Danh sach bao gia xuat tu he thong 3iGreen")
        ->setKeywords("bao gia, danh sach, excel")
        ->setCategory("Bao Gia");

    // Output to browser
    $fileName = 'Danh_sach_bao_gia_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    // If an error occurs, return a JSON error response
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi khi tạo file Excel: ' . $e->getMessage()]);
}
?>
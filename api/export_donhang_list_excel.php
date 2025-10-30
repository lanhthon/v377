<?php
// File: api/export_donhang_list_excel.php

header('Content-Type: application/json'); // Mặc định là JSON, sẽ thay đổi sau khi tạo Excel

require_once __DIR__ . '/../vendor/autoload.php'; // Đường dẫn đến autoload của Composer
require_once '../config/database.php'; // Kết nối cơ sở dữ liệu

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    // Lấy các tham số lọc từ request GET
    $startDate = $_GET['startDate'] ?? '';
    $endDate = $_GET['endDate'] ?? '';
    $companyName = $_GET['companyName'] ?? '';
    $status = $_GET['status'] ?? '';

    // <<< THAY ĐỔI: Cập nhật câu lệnh SQL để JOIN với bảng 'congty'
    $sql = "SELECT
                dh.YCSX_ID,
                dh.SoYCSX,
                dh.NguoiBaoGia,
                dh.NgayTao,
                dh.NgayHoanThanhDuKien,
                dh.NgayGiaoDuKien,
                dh.TongTien,
                dh.TrangThai,
                ct.TenCongTy,
                ct.MaCongTy
            FROM 
                donhang AS dh
            LEFT JOIN 
                congty AS ct ON dh.CongTyID = ct.CongTyID
            WHERE 1=1";

    $params = [];
    $types = "";

    if (!empty($startDate)) {
        $sql .= " AND dh.NgayTao >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    if (!empty($endDate)) {
        $sql .= " AND dh.NgayTao <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    if (!empty($companyName)) {
        // <<< THAY ĐỔI: Lọc theo TenCongTy từ bảng 'congty'
        $sql .= " AND ct.TenCongTy LIKE ?";
        $params[] = '%' . $companyName . '%';
        $types .= "s";
    }
    if (!empty($status)) {
        $sql .= " AND dh.TrangThai = ?";
        $params[] = $status;
        $types .= "s";
    }

    $sql .= " ORDER BY dh.NgayTao DESC, dh.YCSX_ID DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $donhang = [];
    while ($row = $result->fetch_assoc()) {
        $donhang[] = $row;
    }
    $stmt->close();
    $conn->close();

    // --- Bắt đầu tạo file Excel ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Danh sách đơn hàng');

    // <<< THAY ĐỔI: Thêm cột 'Mã Khách Hàng' và cập nhật header
    $headers = ['STT', 'Số Đơn Hàng', 'Mã Khách Hàng', 'Tên Khách Hàng', 'Người Báo Giá', 'Ngày Đặt', 'Ngày HT DK', 'Ngày Giao Khách', 'Tổng Tiền', 'Trạng Thái'];
    $sheet->fromArray($headers, NULL, 'A1');

    // Apply header style
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4CAF50']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    ];
    // <<< THAY ĐỔI: Cập nhật phạm vi header
    $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);

    // Data rows
    $rowNum = 2;
    foreach ($donhang as $index => $order) {
        $ngayDat = (new DateTime($order['NgayTao']))->format('d/m/Y');
        $ngayHTDK = $order['NgayHoanThanhDuKien'] ? (new DateTime($order['NgayHoanThanhDuKien']))->format('d/m/Y') : '';
        $ngayGiao = $order['NgayGiaoDuKien'] ? (new DateTime($order['NgayGiaoDuKien']))->format('d/m/Y') : '';

        // <<< THAY ĐỔI: Thêm cột Mã Khách Hàng và dịch chuyển các cột khác
        $sheet->setCellValue('A' . $rowNum, $index + 1);
        $sheet->setCellValue('B' . $rowNum, $order['SoYCSX']);
        $sheet->setCellValue('C' . $rowNum, $order['MaCongTy']); // Cột mới
        $sheet->setCellValue('D' . $rowNum, $order['TenCongTy']);
        $sheet->setCellValue('E' . $rowNum, $order['NguoiBaoGia']);
        $sheet->setCellValue('F' . $rowNum, $ngayDat);
        $sheet->setCellValue('G' . $rowNum, $ngayHTDK);
        $sheet->setCellValue('H' . $rowNum, $ngayGiao);
        $sheet->setCellValue('I' . $rowNum, $order['TongTien']);
        $sheet->setCellValue('J' . $rowNum, $order['TrangThai']);

        // <<< THAY ĐỔI: Cập nhật cột định dạng tiền tệ (từ H sang I)
        $sheet->getStyle('I' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');

        // <<< THAY ĐỔI: Cập nhật phạm vi style cho border
        $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']],
            ],
        ]);

        $rowNum++;
    }

    // <<< THAY ĐỔI: Cập nhật phạm vi auto-size
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator("3iGreen Order System")
        ->setLastModifiedBy("3iGreen Order System")
        ->setTitle("Danh Sach Don Hang")
        ->setSubject("Danh Sach Don Hang")
        ->setDescription("Danh sach don hang xuat tu he thong 3iGreen")
        ->setKeywords("don hang, danh sach, excel")
        ->setCategory("Don Hang");

    // Output to browser
    $fileName = 'Danh_sach_don_hang_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi khi tạo file Excel: ' . $e->getMessage()]);
}
?>
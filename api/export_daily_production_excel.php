<?php
// api/export_daily_production_excel.php

// 1. NẠP CÁC THƯ VIỆN CẦN THIẾT
// Nạp autoloader của Composer
require '../vendor/autoload.php';
// Nạp file kết nối CSDL
require_once '../config/database.php';

// Sử dụng các lớp của PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// 2. LẤY DỮ LIỆU TỪ CSDL (Tương tự file get_daily_production_report.php)
$data = [];
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

try {
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Lỗi kết nối CSDL.");
    }

    $sql = "
        SELECT 
            nk.NgayBaoCao,
            lsx.SoLenhSX,
            v.variant_sku AS MaBTP,
            v.variant_name AS TenBTP,
            nk.SoLuongHoanThanh,
            u.HoTen AS NguoiThucHien,
            nk.GhiChu AS GhiChuNhatKy
        FROM nhat_ky_san_xuat nk
        JOIN chitiet_lenh_san_xuat clsx ON nk.ChiTiet_LSX_ID = clsx.ChiTiet_LSX_ID
        JOIN lenh_san_xuat lsx ON clsx.LenhSX_ID = lsx.LenhSX_ID
        JOIN variants v ON clsx.SanPhamID = v.variant_id
        LEFT JOIN nguoidung u ON nk.NguoiThucHien_ID = u.UserID
        WHERE nk.NgayBaoCao BETWEEN ? AND ?
        ORDER BY nk.NgayBaoCao ASC, lsx.SoLenhSX ASC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Nếu có lỗi, dừng và báo lỗi
    die("Đã xảy ra lỗi khi truy xuất dữ liệu: " . $e->getMessage());
}

// 3. TẠO FILE EXCEL

// Tạo một đối tượng Spreadsheet mới
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Đặt tên cho sheet
$sheet->setTitle('Bao Cao San Luong');

// Ghi tiêu đề chính
$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'BÁO CÁO SẢN LƯỢNG SẢN XUẤT HÀNG NGÀY');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Ghi khoảng thời gian
$sheet->mergeCells('A2:G2');
$sheet->setCellValue('A2', "Từ ngày " . date("d/m/Y", strtotime($startDate)) . " đến ngày " . date("d/m/Y", strtotime($endDate)));
$sheet->getStyle('A2')->getFont()->setItalic(true);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


// Ghi tiêu đề của bảng dữ liệu vào dòng 4
$headers = [
    'STT', 
    'Ngày Báo Cáo', 
    'Số LSX', 
    'Mã Sản Phẩm', 
    'Tên Sản Phẩm', 
    'Sản Lượng', 
    'Người Thực Hiện', 
    'Ghi Chú'
];
$sheet->fromArray($headers, NULL, 'A4');

// 4. ÁP DỤNG STYLE CHO TIÊU ĐỀ (Yêu cầu chính)
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFFFF'], // Màu chữ trắng
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'argb' => 'FF92D050', // Màu nền #92D050 (FF ở đầu là Alpha channel)
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];

// Áp dụng style cho dòng tiêu đề (A4 đến H4)
$sheet->getStyle('A4:H4')->applyFromArray($headerStyle);
$sheet->getRowDimension('4')->setRowHeight(25); // Tăng chiều cao dòng tiêu đề


// 5. GHI DỮ LIỆU VÀO CÁC DÒNG
$rowIndex = 5; // Bắt đầu ghi dữ liệu từ dòng 5
$stt = 1;
foreach ($data as $row) {
    $sheet->setCellValue('A' . $rowIndex, $stt++);
    $sheet->setCellValue('B' . $rowIndex, date("d/m/Y", strtotime($row['NgayBaoCao'])));
    $sheet->setCellValue('C' . $rowIndex, $row['SoLenhSX']);
    $sheet->setCellValue('D' . $rowIndex, $row['MaBTP']);
    $sheet->setCellValue('E' . $rowIndex, $row['TenBTP']);
    $sheet->setCellValue('F' . $rowIndex, $row['SoLuongHoanThanh']);
    $sheet->setCellValue('G' . $rowIndex, $row['NguoiThucHien']);
    $sheet->setCellValue('H' . $rowIndex, $row['GhiChuNhatKy']);
    $rowIndex++;
}

// Style cho phần dữ liệu
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FFBFBFBF'],
        ],
    ]
];
$sheet->getStyle('A5:H' . ($rowIndex - 1))->applyFromArray($dataStyle);


// 6. TỰ ĐỘNG CĂN CHỈNH ĐỘ RỘNG CÁC CỘT
foreach (range('A', 'H') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}
// Căn chỉnh một số cột cụ thể
$sheet->getStyle('F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


// 7. XUẤT FILE RA TRÌNH DUYỆT
// Tạo tên file động
$fileName = "BaoCaoSanLuong_" . date('Ymd', strtotime($startDate)) . "_to_" . date('Ymd', strtotime($endDate)) . ".xlsx";

// Thiết lập HTTP headers để trình duyệt hiểu đây là file Excel cần tải về
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

// Tạo đối tượng Writer và xuất file
$writer = new Xlsx($spreadsheet);
ob_end_clean(); // Xóa bộ đệm đầu ra để tránh lỗi
$writer->save('php://output');
exit();
?>
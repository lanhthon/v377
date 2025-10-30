<?php
// api/export_list_pnk_btp_excel.php
require_once '../config/db_config.php';
// Đảm bảo bạn đã cài đặt PhpSpreadsheet qua Composer
require_once '../vendor/autoload.php'; 

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    $pdo = get_db_connection();

    // Lấy tham số bộ lọc
    $startDate = $_GET['startDate'] ?? '';
    $endDate = $_GET['endDate'] ?? '';
    $soPhieu = $_GET['soPhieu'] ?? '';
    $soLSX = $_GET['soLSX'] ?? '';
    $ghiChu = $_GET['ghiChu'] ?? '';

    // Xây dựng điều kiện WHERE
    $whereClauses = [];
    $params = [];

    if (!empty($startDate)) {
        $whereClauses[] = "pnk.NgayNhap >= :startDate";
        $params[':startDate'] = $startDate;
    }
    if (!empty($endDate)) {
        $whereClauses[] = "pnk.NgayNhap <= :endDate";
        $params[':endDate'] = $endDate;
    }
    if (!empty($soPhieu)) {
        $whereClauses[] = "pnk.SoPhieuNhapKhoBTP LIKE :soPhieu";
        $params[':soPhieu'] = '%' . $soPhieu . '%';
    }
    if (!empty($soLSX)) {
        $whereClauses[] = "lsx.SoLenhSX LIKE :soLSX";
        $params[':soLSX'] = '%' . $soLSX . '%';
    }
    if (!empty($ghiChu)) {
        $whereClauses[] = "pnk.GhiChu LIKE :ghiChu";
        $params[':ghiChu'] = '%' . $ghiChu . '%';
    }

    $whereSql = '';
    if (!empty($whereClauses)) {
        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
    }

    // Lấy dữ liệu từ database
    $sql = "SELECT 
                pnk.SoPhieuNhapKhoBTP,
                pnk.NgayNhap,
                pnk.LyDoNhap,
                lsx.SoLenhSX,
                pnk.GhiChu,
                nd.HoTen AS TenNguoiTao
            FROM phieunhapkho_btp pnk
            LEFT JOIN lenh_san_xuat lsx ON pnk.LenhSX_ID = lsx.LenhSX_ID
            LEFT JOIN nguoidung nd ON pnk.NguoiTaoID = nd.UserID
            $whereSql
            ORDER BY pnk.PNK_BTP_ID DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Bắt đầu tạo file Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Thiết lập tiêu đề
    $sheet->setCellValue('A1', 'DANH SÁCH PHIẾU NHẬP KHO BÁN THÀNH PHẨM');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Thêm thông tin bộ lọc
    $row = 3;
    if (!empty($startDate) || !empty($endDate)) {
        $dateFilter = 'Từ ngày: ' . ($startDate ? date('d/m/Y', strtotime($startDate)) : '...') . ' đến ngày: ' . ($endDate ? date('d/m/Y', strtotime($endDate)) : '...');
        $sheet->setCellValue('A' . $row, $dateFilter);
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $row++;
    }

    // Tiêu đề cột
    $headers = [
        'STT',
        'Số Phiếu',
        'Ngày Nhập',
        'Lý Do Nhập',
        'Số Lệnh SX',
        'Ghi Chú Chung',
        'Người Lập'
    ];

    $headerRow = $row + 1;
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $headerRow, $header);
        $col++;
    }

    // Định dạng tiêu đề
    $headerRange = 'A' . $headerRow . ':G' . $headerRow;
    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);

    // Thêm dữ liệu vào file
    $dataRow = $headerRow + 1;
    $stt = 1;
    foreach ($data as $item) {
        $sheet->setCellValue('A' . $dataRow, $stt);
        $sheet->setCellValue('B' . $dataRow, $item['SoPhieuNhapKhoBTP']);
        $sheet->setCellValue('C' . $dataRow, date('d/m/Y', strtotime($item['NgayNhap'])));
        $sheet->setCellValue('D' . $dataRow, $item['LyDoNhap']);
        $sheet->setCellValue('E' . $dataRow, $item['SoLenhSX'] ?: 'N/A');
        $sheet->setCellValue('F' . $dataRow, $item['GhiChu'] ?: '');
        $sheet->setCellValue('G' . $dataRow, $item['TenNguoiTao'] ?: 'N/A');
        $dataRow++;
        $stt++;
    }

    // Thêm viền cho vùng dữ liệu
    $dataRange = 'A' . ($headerRow + 1) . ':G' . ($dataRow - 1);
    if ($dataRow > $headerRow + 1) {
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    }
    
    // Tự động điều chỉnh độ rộng cột
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Thêm dòng tổng kết
    if (count($data) > 0) {
        $sheet->setCellValue('A' . ($dataRow + 1), 'Tổng cộng: ' . count($data) . ' phiếu');
        $sheet->mergeCells('A' . ($dataRow + 1) . ':G' . ($dataRow + 1));
        $sheet->getStyle('A' . ($dataRow + 1))->getFont()->setBold(true);
    }

    // Thiết lập tên file và gửi headers để tải xuống
    $filename = 'DS_PNK_BTP_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi xuất file Excel: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>


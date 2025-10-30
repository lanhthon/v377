<?php
// api/includes/export_production_list_excel.php

// File này chứa logic tạo Excel, được gọi bởi get_production_order_list.php
// Đảm bảo bạn đã cài đặt PhpSpreadsheet: composer require phpoffice/phpspreadsheet

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function generateProductionListExcel($conn) {
    try {
        $startDate = $_GET['startDate'] ?? null;
        $endDate = $_GET['endDate'] ?? null;

        $sql = "SELECT 
                    lsx.SoLenhSX, lsx.LoaiLSX, dh.SoYCSX, lsx.NgayTao, 
                    COALESCE(u.HoTen, 'Hệ thống') as NguoiYeuCau, lsx.TrangThai
                FROM lenh_san_xuat lsx
                LEFT JOIN donhang dh ON lsx.YCSX_ID = dh.YCSX_ID
                LEFT JOIN nguoidung u ON lsx.NguoiYeuCau_ID = u.UserID
                WHERE 1=1";
        
        $params = [];
        $types = '';

        if ($startDate) {
            $sql .= " AND lsx.NgayTao >= ?";
            $params[] = $startDate . " 00:00:00";
            $types .= 's';
        }
        if ($endDate) {
            $sql .= " AND lsx.NgayTao <= ?";
            $params[] = $endDate . " 23:59:59";
            $types .= 's';
        }

        $sql .= " ORDER BY lsx.NgayTao DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh sách LSX');

        // Ghi tiêu đề
        $headers = ['Số Lệnh SX', 'Loại', 'YCSX Gốc', 'Ngày tạo', 'Người yêu cầu', 'Trạng thái'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        // Ghi dữ liệu
        $rowNum = 2;
        foreach ($results as $row) {
             $sheet->fromArray([
                $row['SoLenhSX'],
                $row['LoaiLSX'],
                $row['SoYCSX'] ?? 'Lưu kho',
                (new DateTime($row['NgayTao']))->format('d/m/Y H:i'),
                $row['NguoiYeuCau'],
                $row['TrangThai']
            ], NULL, 'A' . $rowNum++);
        }
        
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "DanhSach_LenhSanXuat_" . date('Y-m-d') . ".xlsx";
        
        ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

    } catch(Exception $e) {
        // Xử lý lỗi
        ob_end_clean();
        die("Lỗi khi tạo file Excel: " . $e->getMessage());
    }
}
?>
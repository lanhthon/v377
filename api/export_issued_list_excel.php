<?php
// File: api/export_issued_list_excel.php
// Version: 4.0 - Đồng bộ logic truy vấn với get_issued_slips.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Thiết lập header để trình duyệt hiểu đây là file Excel cần tải về
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="DanhSachPhieuXuatKho.xlsx"');
header('Cache-Control: max-age=0');

try {
    $pdo = get_db_connection();

    // === BƯỚC 1: LẤY CÁC THAM SỐ LỌC (LOGIC TỪ GET_ISSUED_SLIPS.PHP) ===
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $startDate = isset($_GET['startDate']) ? trim($_GET['startDate']) : '';
    $endDate = isset($_GET['endDate']) ? trim($_GET['endDate']) : '';

    // === BƯỚC 2: XÂY DỰNG CÂU TRUY VẤN (LOGIC TỪ GET_ISSUED_SLIPS.PHP) ===
    $base_query = "
        FROM phieuxuatkho pxk
        LEFT JOIN chuanbihang cbh ON pxk.CBH_ID = cbh.CBH_ID
        LEFT JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
        LEFT JOIN congty ct ON dh.CongTyID = ct.CongTyID
    ";

    $conditions = [];
    $params = [];

    // Luôn lọc theo loại phiếu thành phẩm
    $conditions[] = "pxk.LoaiPhieu = ?";
    $params[] = 'xuat_thanh_pham';

    if (!empty($search)) {
        $conditions[] = "(pxk.SoPhieuXuat LIKE ? OR dh.SoYCSX LIKE ? OR dh.TenCongTy LIKE ? OR ct.MaCongTy LIKE ? OR pxk.NguoiNhan LIKE ?)";
        $search_param = "%{$search}%";
        array_push($params, $search_param, $search_param, $search_param, $search_param, $search_param);
    }

    if (!empty($status)) {
        // Lưu ý: file get_issued_slips.php đang lọc trạng thái trên bảng chuanbihang
        $conditions[] = "cbh.TrangThai = ?";
        $params[] = $status;
    }

    if (!empty($startDate)) {
        $conditions[] = "pxk.NgayXuat >= ?";
        $params[] = $startDate;
    }
    
    if (!empty($endDate)) {
        $conditions[] = "pxk.NgayXuat <= ?";
        $params[] = $endDate;
    }

    $where_clause = "";
    if (count($conditions) > 0) {
        $where_clause = " WHERE " . implode(" AND ", $conditions);
    }

    // Câu truy vấn cuối cùng để lấy dữ liệu
    $data_query = "
        SELECT
            pxk.SoPhieuXuat,
            dh.SoYCSX,
            ct.MaCongTy AS MaKhachHang,
            dh.TenCongTy AS KhachHang,
            pxk.NguoiNhan,
            pxk.NgayXuat,
            cbh.TrangThai
        " . $base_query . $where_clause . "
        ORDER BY pxk.NgayXuat DESC, pxk.PhieuXuatKhoID DESC
    ";

    // === BƯỚC 3: THỰC THI TRUY VẤN ===
    $stmt = $pdo->prepare($data_query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // === BƯỚC 4: TẠO FILE EXCEL ===
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('DS Phiếu Xuất Kho');

    $headers = [
        'Số Phiếu Xuất', 'Đơn Hàng Gốc', 'Mã KH', 'Khách Hàng', 'Người Nhận', 'Ngày Xuất', 'Trạng thái ĐH'
    ];
    $sheet->fromArray($headers, NULL, 'A1');
    $sheet->getStyle('A1:G1')->getFont()->setBold(true);

    if (!empty($results)) {
        $rowNumber = 2;
        foreach ($results as $row) {
            $ngayXuatFormatted = !empty($row['NgayXuat']) ? date('d/m/Y', strtotime($row['NgayXuat'])) : '';
            $rowData = [
                $row['SoPhieuXuat'], $row['SoYCSX'], $row['MaKhachHang'],
                $row['KhachHang'], $row['NguoiNhan'], $ngayXuatFormatted, $row['TrangThai']
            ];
            $sheet->fromArray($rowData, NULL, 'A' . $rowNumber);
            $sheet->getCell('C' . $rowNumber)->setValueExplicit($row['MaKhachHang'] ?? '', DataType::TYPE_STRING);
            $rowNumber++;
        }
    } else {
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Không tìm thấy dữ liệu phù hợp với bộ lọc.');
    }

    foreach (range('A', 'G') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
    
    // === BƯỚC 5: XUẤT FILE ===
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Throwable $e) {
    error_log("Lỗi tạo file Excel danh sách PXK: " . $e->getMessage());
    header("Content-Type: text/plain; charset=utf-8");
    die("Đã có lỗi xảy ra trong quá trình tạo file Excel. Lỗi: " . $e->getMessage());
}

exit;
<?php
// File: api/export_customer_list_excel.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

try {
    // --- 1. LẤY DỮ LIỆU CÔNG TY THEO BỘ LỌC ---
    $groupFilter = $_GET['group'] ?? 'Tất cả';
    $searchValue = $_GET['search'] ?? '';

    $sqlCompanies = "
        SELECT DISTINCT 
            c.*, 
            ccg.TenCoChe, 
            ccg.PhanTramDieuChinh,
            bg_stats.SoBaoGiaDaChot
        FROM 
            congty c
        LEFT JOIN 
            cochegia ccg ON c.CoCheGiaID = ccg.CoCheGiaID
        LEFT JOIN (
            SELECT CongTyID, COUNT(BaoGiaID) AS SoBaoGiaDaChot
            FROM baogia WHERE TrangThai = 'Chốt' GROUP BY CongTyID
        ) AS bg_stats ON c.CongTyID = bg_stats.CongTyID
    ";
    
    $conditions = [];
    $params = [];
    $types = "";

    if ($groupFilter !== 'Tất cả') {
        $conditions[] = "c.NhomKhachHang = ?";
        $params[] = $groupFilter;
        $types .= "s";
    }

    if (!empty($searchValue)) {
        $searchPattern = '%' . $searchValue . '%';
        // THÊM MỚI: Bổ sung c.Website LIKE ? vào điều kiện tìm kiếm
        $searchCondition = "(c.MaCongTy LIKE ? OR c.TenCongTy LIKE ? OR c.DiaChi LIKE ? OR c.Website LIKE ? OR c.MaSoThue LIKE ? OR c.SoDienThoaiChinh LIKE ? OR c.CongTyID IN (SELECT DISTINCT nl.CongTyID FROM nguoilienhe nl WHERE nl.HoTen LIKE ? OR nl.Email LIKE ? OR nl.SoDiDong LIKE ?))";
        $conditions[] = $searchCondition;
        // THAY ĐỔI: Tăng vòng lặp từ 8 lên 9 để khớp với số lượng '?'
        for ($i = 0; $i < 9; $i++) {
            $params[] = $searchPattern;
            $types .= "s";
        }
    }

    if (!empty($conditions)) {
        $sqlCompanies .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sqlCompanies .= " ORDER BY c.TenCongTy ASC";

    $stmtCompanies = $conn->prepare($sqlCompanies);
    if (!empty($params)) {
        $stmtCompanies->bind_param($types, ...$params);
    }
    $stmtCompanies->execute();
    $resultCompanies = $stmtCompanies->get_result();
    
    $companies = [];
    $companyIds = [];
    while ($row = $resultCompanies->fetch_assoc()) {
        $companies[] = $row;
        $companyIds[] = $row['CongTyID'];
    }
    $stmtCompanies->close();

    // --- 2. LẤY DỮ LIỆU LIÊN QUAN (LIÊN HỆ & BÌNH LUẬN) ---
    $contactsByCompanyId = [];
    $commentsByCompanyId = [];
    if (!empty($companyIds)) {
        $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
        $typesContacts = str_repeat('i', count($companyIds));

        // Lấy người liên hệ
        $sqlContacts = "SELECT * FROM nguoilienhe WHERE CongTyID IN ($placeholders)";
        $stmtContacts = $conn->prepare($sqlContacts);
        $stmtContacts->bind_param($typesContacts, ...$companyIds);
        $stmtContacts->execute();
        $resultContacts = $stmtContacts->get_result();
        while ($row = $resultContacts->fetch_assoc()) {
            $contactsByCompanyId[$row['CongTyID']][] = $row;
        }
        $stmtContacts->close();

        // Lấy lịch sử làm việc
        $sqlComments = "SELECT * FROM CongTy_Comment WHERE CongTyID IN ($placeholders) ORDER BY NgayBinhLuan DESC";
        $stmtComments = $conn->prepare($sqlComments);
        $stmtComments->bind_param($typesContacts, ...$companyIds);
        $stmtComments->execute();
        $resultComments = $stmtComments->get_result();
        while ($row = $resultComments->fetch_assoc()) {
            $commentsByCompanyId[$row['CongTyID']][] = $row;
        }
        $stmtComments->close();
    }
    $conn->close();

    // --- 3. BẮT ĐẦU TẠO FILE EXCEL ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Danh sách Khách hàng');

    // THAY ĐỔI: Thêm cột 'Website' vào tiêu đề
    $headers = ['STT', 'Mã Cty', 'Tên Công Ty / Người Liên Hệ', 'Nhóm KH', 'Số BG Chốt', 'Địa Chỉ / Email', 'Điện Thoại', 'Mã Số Thuế', 'Website', 'Cơ Chế Giá'];
    $sheet->fromArray($headers, NULL, 'A1');

    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1D6F42']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    ];
    // THAY ĐỔI: Mở rộng vùng style cho tiêu đề đến cột J
    $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);
    
    $companyStyle = ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEFEFEF']]];
    $contactStyle = [ 'alignment' => ['indent' => 1] ];

    $rowNum = 2;
    $stt = 1;
    foreach ($companies as $company) {
        $coCheGiaText = $company['TenCoChe'] ? $company['TenCoChe'] . ' (' . $company['PhanTramDieuChinh'] . '%)' : '';
        // THAY ĐỔI: Thêm dữ liệu $company['Website'] vào mảng
        $sheet->fromArray([
            $stt, $company['MaCongTy'], $company['TenCongTy'], $company['NhomKhachHang'], $company['SoBaoGiaDaChot'] ?? 0,
            $company['DiaChi'], $company['SoDienThoaiChinh'], $company['MaSoThue'], $company['Website'], $coCheGiaText
        ], NULL, 'A' . $rowNum);
        // THAY ĐỔI: Mở rộng vùng style cho dòng công ty đến cột J
        $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->applyFromArray($companyStyle);
        $rowNum++;

        if (isset($contactsByCompanyId[$company['CongTyID']])) {
            foreach ($contactsByCompanyId[$company['CongTyID']] as $contact) {
                $sheet->fromArray(['', '', $contact['HoTen'], $contact['ChucVu'], '', $contact['Email'], $contact['SoDiDong']], NULL, 'A' . $rowNum);
                $sheet->getStyle('C' . $rowNum)->applyFromArray($contactStyle);
                $rowNum++;
            }
        }
        $stt++;
    }
    // THAY ĐỔI: Mở rộng vùng tự động giãn cột đến J
    foreach (range('A', 'J') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

    // --- 4. TẠO SHEET LỊCH SỬ LÀM VIỆC ---
    if (!empty($commentsByCompanyId)) {
        $commentSheet = $spreadsheet->createSheet();
        $commentSheet->setTitle('LichSuLamViec');
        $commentHeaders = ['Mã Cty', 'Tên Công Ty', 'Người Bình Luận', 'Ngày Bình Luận', 'Nội Dung'];
        $commentSheet->fromArray($commentHeaders, NULL, 'A1');
        $commentSheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        $commentRowNum = 2;
        foreach ($companies as $company) {
            if (isset($commentsByCompanyId[$company['CongTyID']])) {
                foreach ($commentsByCompanyId[$company['CongTyID']] as $comment) {
                    $commentSheet->fromArray([
                        $company['MaCongTy'], $company['TenCongTy'], $comment['NguoiBinhLuan'], $comment['NgayBinhLuan'], $comment['NoiDung']
                    ], NULL, 'A' . $commentRowNum);
                    $commentRowNum++;
                }
            }
        }
        foreach (range('A', 'E') as $col) $commentSheet->getColumnDimension($col)->setAutoSize(true);
    }
    $spreadsheet->setActiveSheetIndex(0);

    // --- 5. XUẤT FILE RA TRÌNH DUYỆT ---
    $fileName = 'Danh_sach_khach_hang_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    http_response_code(500);
    error_log('Lỗi khi tạo file Excel: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra trong quá trình tạo file Excel.']);
}
?>

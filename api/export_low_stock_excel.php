<?php
/**
 * File: api/export_low_stock_excel.php
 * Chức năng: Xuất báo cáo tồn kho tối thiểu ra file Excel, có hỗ trợ lọc.
 */
ob_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// ...

try {
    global $conn;

    // Lấy các tham số lọc từ URL
    $filter_group = $_GET['group'] ?? '';
    $filter_thickness = $_GET['thickness'] ?? '';

    // Xây dựng câu truy vấn SQL
    // Bắt đầu với câu truy vấn gốc
    $sql = "SELECT
                v.variant_sku AS code,
                v.variant_name AS name,
                pg.name AS group_name,
                COALESCE(vi.quantity, 0) AS currentStock,
                COALESCE(vi.minimum_stock_level, 0) AS minimum_stock_level,
                MAX(CASE WHEN a.name = 'Độ dày' THEN ao.value ELSE NULL END) AS thickness
            FROM variants v
            LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
            LEFT JOIN products p ON v.product_id = p.product_id
            LEFT JOIN product_groups pg ON p.group_id = pg.group_id
            LEFT JOIN variant_attributes va ON v.variant_id = va.variant_id
            LEFT JOIN attribute_options ao ON va.option_id = ao.option_id
            LEFT JOIN attributes a ON ao.attribute_id = a.attribute_id
            GROUP BY v.variant_id, v.variant_sku, v.variant_name, pg.name, vi.quantity, vi.minimum_stock_level
            HAVING currentStock <= minimum_stock_level"; // Điều kiện cốt lõi của báo cáo

    $params = [];
    $types = '';

    // Thêm điều kiện lọc nếu có
    if (!empty($filter_group)) {
        $sql .= " AND group_name = ?";
        $params[] = $filter_group;
        $types .= 's';
    }
    if (!empty($filter_thickness)) {
        $sql .= " AND thickness = ?";
        $params[] = $filter_thickness;
        $types .= 's';
    }
    
    $sql .= " ORDER BY v.variant_sku ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    // Tạo file Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('BaoCaoTonKhoThap');
    
    $sheet->setCellValue('A1', 'BÁO CÁO SẢN PHẨM DƯỚI ĐỊNH MỨC TỒN KHO');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->setCellValue('A2', 'Ngày xuất: ' . date('d/m/Y H:i:s'));

    // Tiêu đề bảng
    $headerRow = 4;
    $sheet->fromArray(['Mã Hàng', 'Tên Sản Phẩm', 'Nhóm SP', 'Tồn Kho', 'Mức Tối Thiểu', 'Tình Trạng'], NULL, 'A'.$headerRow);
    $sheet->getStyle('A'.$headerRow.':F'.$headerRow)->getFont()->setBold(true);
    
    // Đổ dữ liệu
    $currentRow = $headerRow + 1;
    foreach ($data as $row) {
        $tinhTrang = (intval($row['currentStock']) <= 0) ? 'Hết hàng' : 'Dưới định mức';
        $sheet->fromArray([
            $row['code'],
            $row['name'],
            $row['group_name'],
            $row['currentStock'],
            $row['minimum_stock_level'],
            $tinhTrang
        ], NULL, 'A'.$currentRow);
        $currentRow++;
    }

    // Xuất file
    $fileName = "BaoCaoTonKhoThap_" . date('Ymd_His') . ".xlsx";
    ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    die("Lỗi khi tạo Excel: " . $e->getMessage());
}
?>
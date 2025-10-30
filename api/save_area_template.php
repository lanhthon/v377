<?php
// api/save_area_template.php - Lưu template khu vực

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/db_config.php';
    $pdo = get_db_connection();
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()]);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ.']);
    exit();
}

$templateName = $data['name'] ?? '';
$areas = $data['areas'] ?? [];

if (empty($templateName)) {
    echo json_encode(['success' => false, 'message' => 'Tên template không được để trống.']);
    exit();
}

if (empty($areas)) {
    echo json_encode(['success' => false, 'message' => 'Danh sách khu vực không được để trống.']);
    exit();
}

try {
    // Kiểm tra tên template đã tồn tại
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM area_templates WHERE TenTemplate = ?");
    $checkStmt->execute([$templateName]);
    
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Tên template đã tồn tại.']);
        exit();
    }

    // Lưu template
    $sql = "INSERT INTO area_templates (TenTemplate, DanhSachKhuVuc, NguoiTao) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $templateName,
        json_encode($areas),
        1 // TODO: Lấy từ session user ID thực tế
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Template đã được lưu thành công!',
        'templateId' => $pdo->lastInsertId()
    ]);
} catch (\Exception $e) {
    error_log("Lỗi khi lưu template khu vực: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu template: ' . $e->getMessage()]);
}

?>

<?php
// api/get_area_templates.php - Lấy danh sách template khu vực

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/db_config.php';
    $pdo = get_db_connection();
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()]);
    exit();
}

try {
    $sql = "SELECT TemplateID, TenTemplate, DanhSachKhuVuc, NgayTao 
            FROM area_templates 
            WHERE TrangThai = 'active'
            ORDER BY NgayTao DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'templates' => $templates
    ]);
} catch (\Exception $e) {
    error_log("Lỗi khi lấy danh sách template: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách template: ' . $e->getMessage()]);
}

?>

<?php
// api/get_quote_details.php - Cập nhật để lấy thông tin khu vực

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/db_config.php';
    $pdo = get_db_connection();
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()]);
    exit();
}

$quoteId = $_GET['id'] ?? 0;

if (!$quoteId) {
    echo json_encode(['success' => false, 'message' => 'ID báo giá không hợp lệ.']);
    exit();
}

try {
    // Lấy thông tin báo giá
    $sqlInfo = "SELECT * FROM baogia WHERE BaoGiaID = ?";
    $stmtInfo = $pdo->prepare($sqlInfo);
    $stmtInfo->execute([$quoteId]);
    $quoteInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);
    
    if (!$quoteInfo) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy báo giá.']);
        exit();
    }
    
    // Lấy chi tiết sản phẩm (CẬP NHẬT VỚI KHU VỰC)
    $sqlItems = "SELECT * FROM chitietbaogia WHERE BaoGiaID = ? ORDER BY ThuTuHienThi";
    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute([$quoteId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'quote' => [
            'info' => $quoteInfo,
            'items' => $items
        ]
    ]);
} catch (\Exception $e) {
    error_log("Lỗi khi lấy chi tiết báo giá: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy chi tiết báo giá: ' . $e->getMessage()]);
}

?>

<?php
// api/export_pdf.php - Cập nhật để hỗ trợ khu vực (ví dụ cơ bản)

// ... existing PDF export code ...

// Thêm logic hiển thị khu vực trong PDF
function renderProductWithArea($item, $pdf) {
    // Nếu sản phẩm có khu vực, thêm thông tin khu vực vào PDF
    if (!empty($item['KhuVuc'])) {
        // Vẽ một ô màu nhỏ bên cạnh tên sản phẩm để thể hiện khu vực
        $pdf->SetFillColor(146, 208, 80); // Màu mặc định
        $pdf->Rect($pdf->GetX() - 5, $pdf->GetY(), 3, 5, 'F');
        
        // Thêm text khu vực
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 4, '[' . $item['KhuVuc'] . ']', 0, 1);
    }
}

// Hàm tạo báo cáo thống kê theo khu vực
function generateAreaStatisticsReport($items) {
    $areaStats = [];
    $totalValue = 0;
    
    foreach ($items as $item) {
        if (!empty($item['KhuVuc'])) {
            $area = $item['KhuVuc'];
            if (!isset($areaStats[$area])) {
                $areaStats[$area] = [
                    'products' => 0,
                    'totalValue' => 0
                ];
            }
            $areaStats[$area]['products']++;
            $areaStats[$area]['totalValue'] += $item['ThanhTien'];
        }
        $totalValue += $item['ThanhTien'];
    }
    
    return [
        'areaStats' => $areaStats,
        'totalValue' => $totalValue
    ];
}

?>

<?php
// api/export_excel.php - Cập nhật để hỗ trợ xuất Excel với khu vực

require_once '../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ... existing Excel export code ...

// Thêm cột khu vực vào Excel
function addAreaColumnToExcel($worksheet, $items, $startRow = 2) {
    // Thêm header cho cột khu vực
    $worksheet->setCellValue('J1', 'Khu vực');
    
    $row = $startRow;
    foreach ($items as $item) {
        if (!empty($item['KhuVuc'])) {
            $worksheet->setCellValue('J' . $row, $item['KhuVuc']);
            
            // Tô màu ô theo khu vực (có thể customize)
            $worksheet->getStyle('J' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('92D050');
        }
        $row++;
    }
}

// Tạo sheet thống kê riêng cho khu vực
function createAreaStatisticsSheet($spreadsheet, $items) {
    $worksheet = $spreadsheet->createSheet();
    $worksheet->setTitle('Thống kê khu vực');
    
    // Headers
    $worksheet->setCellValue('A1', 'Khu vực');
    $worksheet->setCellValue('B1', 'Số sản phẩm');
    $worksheet->setCellValue('C1', 'Tổng giá trị');
    $worksheet->setCellValue('D1', 'Tỷ lệ %');
    
    // Tính toán và điền dữ liệu
    $areaStats = [];
    $totalValue = 0;
    
    foreach ($items as $item) {
        if (!empty($item['KhuVuc'])) {
            $area = $item['KhuVuc'];
            if (!isset($areaStats[$area])) {
                $areaStats[$area] = ['products' => 0, 'totalValue' => 0];
            }
            $areaStats[$area]['products']++;
            $areaStats[$area]['totalValue'] += $item['ThanhTien'];
        }
        $totalValue += $item['ThanhTien'];
    }
    
    $row = 2;
    foreach ($areaStats as $area => $stats) {
        $percentage = $totalValue > 0 ? ($stats['totalValue'] / $totalValue) * 100 : 0;
        
        $worksheet->setCellValue('A' . $row, $area);
        $worksheet->setCellValue('B' . $row, $stats['products']);
        $worksheet->setCellValue('C' . $row, number_format($stats['totalValue'], 0, '.', ','));
        $worksheet->setCellValue('D' . $row, number_format($percentage, 1) . '%');
        
        $row++;
    }
    
    // Format bảng
    $worksheet->getStyle('A1:D1')->getFont()->setBold(true);
    $worksheet->getStyle('A1:D' . ($row - 1))->getBorders()->getAllBorders()
        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
}

?>
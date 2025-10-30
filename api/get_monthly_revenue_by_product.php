<?php
// File: api/get_monthly_revenue_by_product.php
// API để lấy doanh thu theo tháng cho PUR và ULA

// Báo cáo lỗi để gỡ lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Sử dụng kết nối CSDL chung từ tệp cấu hình
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Lấy năm hiện tại hoặc từ parameter
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    
    // SQL để lấy doanh thu theo tháng cho PUR và ULA
    $sql = "
        SELECT 
            MONTH(bg.NgayBaoGia) as month,
            YEAR(bg.NgayBaoGia) as year,
            SUM(CASE 
                WHEN ct.MaHang LIKE 'PUR%' THEN ct.ThanhTien 
                ELSE 0 
            END) as pur_revenue,
            SUM(CASE 
                WHEN ct.MaHang LIKE 'ULA%' THEN ct.ThanhTien 
                ELSE 0 
            END) as ula_revenue,
            SUM(ct.ThanhTien) as total_revenue
        FROM baogia bg
        INNER JOIN chitietbaogia ct ON bg.BaoGiaID = ct.BaoGiaID
        WHERE bg.TrangThai = 'Chốt' 
        AND YEAR(bg.NgayBaoGia) = ?
        AND ct.MaHang IS NOT NULL 
        AND TRIM(ct.MaHang) <> ''
        GROUP BY YEAR(bg.NgayBaoGia), MONTH(bg.NgayBaoGia)
        ORDER BY month ASC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh: " . $conn->error);
    }
    
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();

    // Lấy tất cả dữ liệu
    $monthly_data = [];
    while ($row = $result->fetch_assoc()) {
        $monthly_data[] = [
            'month' => (int)$row['month'],
            'year' => (int)$row['year'],
            'pur_revenue' => (float)$row['pur_revenue'],
            'ula_revenue' => (float)$row['ula_revenue'],
            'total_revenue' => (float)$row['total_revenue']
        ];
    }

    // Đảm bảo có đủ 12 tháng (điền 0 cho các tháng không có dữ liệu)
    $complete_data = [];
    for ($month = 1; $month <= 12; $month++) {
        $found = false;
        foreach ($monthly_data as $data) {
            if ($data['month'] == $month) {
                $complete_data[] = $data;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $complete_data[] = [
                'month' => $month,
                'year' => $year,
                'pur_revenue' => 0,
                'ula_revenue' => 0,
                'total_revenue' => 0
            ];
        }
    }

    $response['success'] = true;
    $response['data'] = $complete_data;
    $response['year'] = $year;

    $stmt->close();

} catch (Exception $e) {
    // Trả về mã lỗi 500 khi có lỗi server
    http_response_code(500);
    $response['message'] = 'Lỗi server: ' . $e->getMessage();
}

// Đóng kết nối CSDL
$conn->close();

// Trả về kết quả dưới dạng JSON
echo json_encode($response);
?>
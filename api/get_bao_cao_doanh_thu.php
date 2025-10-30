<!-- ========================================
     FILE: api/get_bao_cao_doanh_thu.php
     ======================================== -->
<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$response = ['success' => false, 'data' => [], 'summary' => [], 'chartData' => [], 'message' => ''];

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        $response['message'] = 'Chưa đăng nhập';
        echo json_encode($response);
        exit;
    }

    $year = intval($_GET['year'] ?? date('Y'));
    $period = $_GET['period'] ?? 'monthly';

    // Tính doanh thu theo tháng
    if ($period === 'monthly') {
        $sql = "SELECT 
                    MONTH(dh.NgayTao) as Thang,
                    SUM(dh.TongTien) as DoanhThu
                FROM donhang dh
                WHERE YEAR(dh.NgayTao) = ? AND dh.TrangThai != 'Đã hủy'
                GROUP BY MONTH(dh.NgayTao)
                ORDER BY Thang";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $labels = [];
        $values = [];
        $monthlyData = array_fill(1, 12, 0);
        
        while ($row = $result->fetch_assoc()) {
            $monthlyData[$row['Thang']] = floatval($row['DoanhThu']);
        }
        
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = "Tháng " . $i;
            $values[] = $monthlyData[$i];
        }
        
        $stmt->close();
    }

    // Tính các chỉ số tổng hợp
    $currentMonth = date('n');
    $doanhThuThang = $monthlyData[$currentMonth] ?? 0;
    $doanhThuNam = array_sum($monthlyData);
    $trungBinhThang = $doanhThuNam / 12;
    $thangCaoNhat = max($monthlyData);

    $response['success'] = true;
    $response['summary'] = [
        'doanhThuThang' => $doanhThuThang,
        'doanhThuNam' => $doanhThuNam,
        'trungBinhThang' => $trungBinhThang,
        'thangCaoNhat' => $thangCaoNhat
    ];
    $response['chartData'] = [
        'labels' => $labels,
        'values' => $values
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
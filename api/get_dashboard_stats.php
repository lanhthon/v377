<?php
// File: api/get_dashboard_stats.php (Updated)
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $stats = [];
    $currentYear = date('Y');
    $currentMonth = date('n'); // Tháng hiện tại (1-12)
    
    // Tổng doanh thu từ các đơn hàng đã chốt
    $result = $conn->query("SELECT SUM(TongTienSauThue) as total FROM baogia WHERE TrangThai = 'Chốt'");
    $stats['total_revenue'] = floatval($result->fetch_assoc()['total'] ?? 0);
    
    // Đơn hàng mới trong tháng này
    $first_day_of_month = date('Y-m-01');
    $last_day_of_month = date('Y-m-t');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donhang WHERE NgayTao BETWEEN ? AND ?");
    $stmt->bind_param('ss', $first_day_of_month, $last_day_of_month);
    $stmt->execute();
    $stats['new_orders_this_month'] = intval($stmt->get_result()->fetch_assoc()['count'] ?? 0);
    
    // Lệnh sản xuất đang chờ
    $result = $conn->query("SELECT COUNT(*) as count FROM lenh_san_xuat WHERE TrangThai != 'Hoàn thành'");
    $stats['pending_production_orders'] = intval($result->fetch_assoc()['count'] ?? 0);
    
    // Sản phẩm tồn kho thấp
    $result = $conn->query("SELECT COUNT(*) as count FROM variant_inventory WHERE quantity <= minimum_stock_level AND minimum_stock_level > 0");
    $stats['low_stock_items'] = intval($result->fetch_assoc()['count'] ?? 0);

    // Lấy kế hoạch doanh thu năm hiện tại
    $revenuePlanStmt = $conn->prepare("SELECT MucTieuDoanhthu, MucTieuThang1, MucTieuThang2, MucTieuThang3, MucTieuThang4, MucTieuThang5, MucTieuThang6, MucTieuThang7, MucTieuThang8, MucTieuThang9, MucTieuThang10, MucTieuThang11, MucTieuThang12 FROM ke_hoach_doanh_thu WHERE Nam = ?");
    $revenuePlanStmt->bind_param('i', $currentYear);
    $revenuePlanStmt->execute();
    $revenuePlan = $revenuePlanStmt->get_result()->fetch_assoc();
    
    if ($revenuePlan) {
        $stats['annual_revenue_target'] = floatval($revenuePlan['MucTieuDoanhthu']);
        
        // Tính mục tiêu tích lũy đến tháng hiện tại
        $cumulativeTarget = 0;
        for ($month = 1; $month <= $currentMonth; $month++) {
            $cumulativeTarget += floatval($revenuePlan["MucTieuThang$month"]);
        }
        $stats['cumulative_target_to_current_month'] = $cumulativeTarget;
        
        // Mục tiêu tháng hiện tại
        $stats['current_month_target'] = floatval($revenuePlan["MucTieuThang$currentMonth"]);
        
        // Doanh thu theo từng tháng trong năm (để so sánh với kế hoạch)
        $monthlyRevenueStmt = $conn->prepare("
            SELECT 
                MONTH(NgayBaoGia) as month,
                SUM(TongTienSauThue) as revenue
            FROM baogia 
            WHERE YEAR(NgayBaoGia) = ? AND TrangThai = 'Chốt'
            GROUP BY MONTH(NgayBaoGia)
            ORDER BY MONTH(NgayBaoGia)
        ");
        $monthlyRevenueStmt->bind_param('i', $currentYear);
        $monthlyRevenueStmt->execute();
        $monthlyRevenueResult = $monthlyRevenueStmt->get_result();
        
        $monthlyRevenue = [];
        while ($row = $monthlyRevenueResult->fetch_assoc()) {
            $monthlyRevenue[intval($row['month'])] = floatval($row['revenue']);
        }
        
        // Tính doanh thu tích lũy thực tế đến tháng hiện tại
        $actualCumulativeRevenue = 0;
        for ($month = 1; $month <= $currentMonth; $month++) {
            $actualCumulativeRevenue += $monthlyRevenue[$month] ?? 0;
        }
        $stats['actual_cumulative_revenue'] = $actualCumulativeRevenue;
        
        // Tính % hoàn thành kế hoạch tích lũy
        $stats['cumulative_completion_rate'] = $cumulativeTarget > 0 ? 
            round(($actualCumulativeRevenue / $cumulativeTarget) * 100, 1) : 0;
            
        // Tính % hoàn thành kế hoạch năm
        $stats['annual_completion_rate'] = $stats['annual_revenue_target'] > 0 ? 
            round(($stats['total_revenue'] / $stats['annual_revenue_target']) * 100, 1) : 0;
            
        // Doanh thu cần đạt trung bình mỗi tháng còn lại
        $remainingMonths = 12 - $currentMonth;
        $remainingTarget = $stats['annual_revenue_target'] - $stats['total_revenue'];
        $stats['avg_monthly_target_remaining'] = $remainingMonths > 0 ? 
            ($remainingTarget / $remainingMonths) : 0;
            
        // Chi tiết kế hoạch và thực tế từng tháng
        $monthlyComparison = [];
        for ($month = 1; $month <= 12; $month++) {
            $planned = floatval($revenuePlan["MucTieuThang$month"]);
            $actual = $monthlyRevenue[$month] ?? 0;
            $monthlyComparison[] = [
                'month' => $month,
                'planned' => $planned,
                'actual' => $actual,
                'completion_rate' => $planned > 0 ? round(($actual / $planned) * 100, 1) : 0
            ];
        }
        $stats['monthly_comparison'] = $monthlyComparison;
        
    } else {
        // Nếu chưa có kế hoạch, sử dụng giá trị mặc định
        $stats['annual_revenue_target'] = 10000000000; // 10 tỷ mặc định
        $stats['cumulative_target_to_current_month'] = ($stats['annual_revenue_target'] / 12) * $currentMonth;
        $stats['current_month_target'] = $stats['annual_revenue_target'] / 12;
        $stats['actual_cumulative_revenue'] = $stats['total_revenue'];
        $stats['cumulative_completion_rate'] = $stats['cumulative_target_to_current_month'] > 0 ? 
            round(($stats['total_revenue'] / $stats['cumulative_target_to_current_month']) * 100, 1) : 0;
        $stats['annual_completion_rate'] = $stats['annual_revenue_target'] > 0 ? 
            round(($stats['total_revenue'] / $stats['annual_revenue_target']) * 100, 1) : 0;
        $stats['avg_monthly_target_remaining'] = ($stats['annual_revenue_target'] - $stats['total_revenue']) / (12 - $currentMonth + 1);
        $stats['has_revenue_plan'] = false;
    }
    
    $stats['current_year'] = $currentYear;
    $stats['current_month'] = $currentMonth;
    $stats['has_revenue_plan'] = isset($revenuePlan);

    echo json_encode(['success' => true, 'data' => $stats]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}

$conn->close();
?>
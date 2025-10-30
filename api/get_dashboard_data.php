<?php
// Thiết lập header để trình duyệt hiểu đây là một phản hồi JSON
header('Content-Type: application/json; charset=utf-8');

// Nhúng tệp kết nối CSDL
require_once '../config/database.php'; // Đảm bảo đường dẫn này đúng

// Mảng để chứa tất cả dữ liệu trả về
$dashboard_data = [
    'kpis' => [],
    'revenueLast6Months' => [],
    'orderStatus' => [],
    'recentOrders' => [],
    'lowStockItems' => [],
    'quotes' => [],
    'allOrders' => [],
    'inventoryIn' => [],
    'inventoryOut' => []
];

// --- 1. TRUY VẤN CÁC CHỈ SỐ KPI ---
// Gộp các truy vấn KPI thành 1 để tối ưu
$sql_kpis = "
    SELECT
        (SELECT SUM(TongTien) FROM donhang WHERE MONTH(NgayTao) = MONTH(CURDATE()) AND YEAR(NgayTao) = YEAR(CURDATE())) AS monthlyRevenue,
        (SELECT COUNT(BaoGiaID) FROM baogia WHERE MONTH(NgayBaoGia) = MONTH(CURDATE()) AND YEAR(NgayBaoGia) = YEAR(CURDATE())) AS newQuotes,
        (SELECT COUNT(YCSX_ID) FROM donhang WHERE MONTH(NgayTao) = MONTH(CURDATE()) AND YEAR(NgayTao) = YEAR(CURDATE())) AS newOrders,
        (SELECT COUNT(YCSX_ID) FROM donhang WHERE TrangThaiCBH = 'Đã chuẩn bị' OR TrangThai = 'Đang sản xuất') AS pendingDelivery,
        (SELECT COUNT(vi.variant_id) FROM variant_inventory vi WHERE vi.quantity < vi.minimum_stock_level AND vi.minimum_stock_level > 0) AS lowStockCount
";

$result_kpis = $conn->query($sql_kpis);
if ($result_kpis) {
    $kpi_row = $result_kpis->fetch_assoc();
    $dashboard_data['kpis'] = [
        'monthlyRevenue' => (float) ($kpi_row['monthlyRevenue'] ?? 0),
        'newQuotes' => (int) ($kpi_row['newQuotes'] ?? 0),
        'newOrders' => (int) ($kpi_row['newOrders'] ?? 0),
        'pendingDelivery' => (int) ($kpi_row['pendingDelivery'] ?? 0),
        'lowStockCount' => (int) ($kpi_row['lowStockCount'] ?? 0)
    ];
}

// --- 2. TRUY VẤN DOANH THU 6 THÁNG GẦN NHẤT ---
$sql_revenue = "
    SELECT
        DATE_FORMAT(NgayTao, '%Y-%m') AS month,
        SUM(TongTien) AS revenue
    FROM donhang
    WHERE NgayTao >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
    ORDER BY month ASC;
";
$result_revenue = $conn->query($sql_revenue);
if ($result_revenue) {
    while($row = $result_revenue->fetch_assoc()) {
        $dashboard_data['revenueLast6Months'][] = $row;
    }
}

// --- 3. TRUY VẤN TRẠNG THÁI ĐƠN HÀNG ---
$sql_status = "
    SELECT
        TrangThai AS label,
        COUNT(YCSX_ID) AS data
    FROM donhang
    WHERE TrangThai IS NOT NULL AND TrangThai != ''
    GROUP BY TrangThai;
";
$result_status = $conn->query($sql_status);
if ($result_status) {
    while($row = $result_status->fetch_assoc()) {
        $dashboard_data['orderStatus'][] = $row;
    }
}

// --- 4. TRUY VẤN CÁC ĐƠN HÀNG GẦN ĐÂY ---
$sql_orders_recent = "
    SELECT
        SoYCSX,
        TenCongTy,
        TongTien,
        TrangThai
    FROM donhang
    ORDER BY NgayTao DESC
    LIMIT 5;
";
$result_orders_recent = $conn->query($sql_orders_recent);
if ($result_orders_recent) {
    while($row = $result_orders_recent->fetch_assoc()) {
        $dashboard_data['recentOrders'][] = $row;
    }
}

// --- 5. TRUY VẤN HÀNG TỒN KHO THẤP ---
$sql_low_stock = "
    SELECT
        v.variant_sku,
        v.variant_name,
        vi.quantity,
        vi.minimum_stock_level
    FROM variant_inventory AS vi
    JOIN variants AS v ON vi.variant_id = v.variant_id
    WHERE vi.quantity < vi.minimum_stock_level AND vi.minimum_stock_level > 0
    ORDER BY (vi.minimum_stock_level - vi.quantity) DESC
    LIMIT 10;
";
$result_low_stock = $conn->query($sql_low_stock);
if ($result_low_stock) {
    while($row = $result_low_stock->fetch_assoc()) {
        $dashboard_data['lowStockItems'][] = $row;
    }
}

// --- 6. TRUY VẤN DANH SÁCH BÁO GIÁ ---
// Sử dụng TongTienSauThue từ bảng baogia
$sql_quotes = "
    SELECT
        BaoGiaID,
        SoBaoGia,
        DATE_FORMAT(NgayBaoGia, '%Y-%m-%d') AS NgayBaoGia,
        TenCongTy,
        TongTienSauThue AS TongTien,
        TrangThai AS TrangThaiBaoGia
    FROM baogia
    ORDER BY NgayBaoGia DESC
    LIMIT 10;
";
$result_quotes = $conn->query($sql_quotes);
if ($result_quotes) {
    while($row = $result_quotes->fetch_assoc()) {
        $dashboard_data['quotes'][] = $row;
    }
}

// --- 7. TRUY VẤN TẤT CẢ ĐƠN HÀNG ---
// Có thể tăng LIMIT nếu muốn hiển thị nhiều hơn trong trang "Đơn hàng"
$sql_all_orders = "
    SELECT
        SoYCSX,
        TenCongTy,
        DATE_FORMAT(NgayTao, '%Y-%m-%d') AS NgayTao,
        TongTien,
        TrangThai
    FROM donhang
    ORDER BY NgayTao DESC
    LIMIT 20;
";
$result_all_orders = $conn->query($sql_all_orders);
if ($result_all_orders) {
    while($row = $result_all_orders->fetch_assoc()) {
        $dashboard_data['allOrders'][] = $row;
    }
}


// --- 8. TRUY VẤN LỊCH SỬ NHẬP KHO ---
$sql_inventory_in = "
    SELECT
        ls.LichSuID AS transaction_id,
        v.variant_sku,
        v.variant_name,
        ls.SoLuongThayDoi AS quantity_changed,
        DATE_FORMAT(ls.NgayGiaoDich, '%Y-%m-%d %H:%i:%s') AS transaction_date
    FROM lichsunhapxuat AS ls
    JOIN variants AS v ON ls.SanPhamID = v.variant_id
    WHERE ls.LoaiGiaoDich = 'NHAP_KHO'
    ORDER BY ls.NgayGiaoDich DESC
    LIMIT 10;
";
$result_inventory_in = $conn->query($sql_inventory_in);
if ($result_inventory_in) {
    while($row = $result_inventory_in->fetch_assoc()) {
        $dashboard_data['inventoryIn'][] = $row;
    }
}

// --- 9. TRUY VẤN LỊCH SỬ XUẤT KHO ---
$sql_inventory_out = "
    SELECT
        ls.LichSuID AS transaction_id,
        v.variant_sku,
        v.variant_name,
        ABS(ls.SoLuongThayDoi) AS quantity_changed, -- Lấy giá trị tuyệt đối vì SoLuongThayDoi là âm cho xuất kho
        DATE_FORMAT(ls.NgayGiaoDich, '%Y-%m-%d %H:%i:%s') AS transaction_date
    FROM lichsunhapxuat AS ls
    JOIN variants AS v ON ls.SanPhamID = v.variant_id
    WHERE ls.LoaiGiaoDich = 'XUAT_KHO'
    ORDER BY ls.NgayGiaoDich DESC
    LIMIT 10;
";
$result_inventory_out = $conn->query($sql_inventory_out);
if ($result_inventory_out) {
    while($row = $result_inventory_out->fetch_assoc()) {
        $dashboard_data['inventoryOut'][] = $row;
    }
}


// Đóng kết nối CSDL
$conn->close();

// Trả về dữ liệu dưới dạng JSON
echo json_encode($dashboard_data);

?>
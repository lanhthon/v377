<?php
/**
 * File: api/get_report_analytics.php
 * API để lấy dữ liệu tổng hợp cho biểu đồ và các tùy chọn cho bộ lọc.
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $conn->begin_transaction();

    // 1. Lấy dữ liệu cho biểu đồ biến động nhập xuất (30 ngày gần nhất)
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
    $sql_daily = "
        SELECT
            DATE(NgayGiaoDich) as transaction_date,
            SUM(CASE WHEN LoaiGiaoDich LIKE 'NHAP%' THEN SoLuongThayDoi ELSE 0 END) as total_imports,
            SUM(CASE WHEN LoaiGiaoDich LIKE 'XUAT%' THEN ABS(SoLuongThayDoi) ELSE 0 END) as total_exports
        FROM LichSuNhapXuat
        WHERE NgayGiaoDich >= ?
        GROUP BY transaction_date
        ORDER BY transaction_date ASC
    ";
    $stmt_daily = $conn->prepare($sql_daily);
    $stmt_daily->bind_param('s', $thirty_days_ago);
    $stmt_daily->execute();
    $result_daily = $stmt_daily->get_result();
    
    $daily_trends = ['labels' => [], 'imports' => [], 'exports' => []];
    while($row = $result_daily->fetch_assoc()){
        $daily_trends['labels'][] = $row['transaction_date'];
        $daily_trends['imports'][] = (int)$row['total_imports'];
        $daily_trends['exports'][] = (int)$row['total_exports'];
    }
    $stmt_daily->close();

    // 2. Lấy dữ liệu cho biểu đồ cơ cấu nhóm sản phẩm (dựa trên số lượng tồn kho)
    $sql_group = "
        SELECT l.TenLoai, SUM(p.SoLuongTonKho) as total_stock
        FROM sanpham p
        JOIN loaisanpham l ON p.LoaiID = l.LoaiID
        WHERE p.SoLuongTonKho > 0
        GROUP BY l.TenLoai
        ORDER BY total_stock DESC
    ";
    $result_group = $conn->query($sql_group);
    $group_structure = ['labels' => [], 'values' => []];
    while($row = $result_group->fetch_assoc()){
        $group_structure['labels'][] = $row['TenLoai'];
        $group_structure['values'][] = (int)$row['total_stock'];
    }

    // 3. Lấy dữ liệu cho các bộ lọc
    // Lấy các nhóm sản phẩm
    $sql_filters_group = "SELECT DISTINCT TenLoai FROM loaisanpham ORDER BY TenLoai ASC";
    $result_filters_group = $conn->query($sql_filters_group);
    $groups = array_map(function($row) { return $row['TenLoai']; }, $result_filters_group->fetch_all(MYSQLI_ASSOC));

    // Lấy các độ dày
    $sql_filters_thickness = "SELECT DISTINCT DoDay FROM sanpham WHERE DoDay IS NOT NULL ORDER BY DoDay ASC";
    $result_filters_thickness = $conn->query($sql_filters_thickness);
    $thicknesses = array_map(function($row) { return $row['DoDay']; }, $result_filters_thickness->fetch_all(MYSQLI_ASSOC));

    $conn->commit();
    
    // --- Trả về kết quả JSON ---
    echo json_encode([
        'charts' => [
            'daily_trends' => $daily_trends,
            'group_structure' => $group_structure
        ],
        'filters' => [
            'groups' => $groups,
            'thicknesses' => $thicknesses
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}

$conn->close();
?>
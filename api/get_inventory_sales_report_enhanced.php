<?php
// File: api/get_inventory_sales_report_enhanced.php
// API để lấy dữ liệu báo cáo bán hàng, bao gồm cả báo giá chưa chốt và đơn hàng đã chốt

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'summary' => null, 'message' => ''];

try {
    // Lấy tham số từ client
    $startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : null;
    $customerId = isset($_GET['customer_id']) && !empty($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
    $deliveryStatus = isset($_GET['delivery_status']) ? $_GET['delivery_status'] : 'all'; // Renamed for clarity
    $quoteStatus = isset($_GET['quote_status']) ? $_GET['quote_status'] : 'all'; // New filter
    $productCode = isset($_GET['product_code']) && !empty($_GET['product_code']) ? $_GET['product_code'] : null;
    $projectName = isset($_GET['project_name']) && !empty($_GET['project_name']) ? $_GET['project_name'] : null;

    $params = [];
    $types = '';

    // SQL chính để lấy tất cả chi tiết sản phẩm thuộc các báo giá
    $sql = "
        SELECT 
            bg.BaoGiaID, bg.SoBaoGia, bg.NgayBaoGia,
            c.TenCongTy,
            dh.YCSX_ID, dh.SoYCSX, 
            COALESCE(dh.TenDuAn, bg.TenDuAn) as TenDuAn,
            dh.TrangThai as TrangThaiDonHang,
            ctbg.ChiTietID, ctbg.MaHang, ctbg.TenSanPham,
            ctbg.SoLuong AS SoLuongBaoGia,
            CASE
                WHEN dh.YCSX_ID IS NOT NULL THEN COALESCE(
                    (SELECT SUM(ctbb.SoLuong)
                     FROM bienbangiaohang bbgh
                     JOIN chitietbienbangiaohang ctbb ON bbgh.BBGH_ID = ctbb.BBGH_ID
                     WHERE bbgh.BaoGiaID = bg.BaoGiaID
                       AND ctbb.MaHang COLLATE utf8mb4_unicode_ci = ctbg.MaHang
                       AND bbgh.TrangThai != 'Đã hủy'),
                    0
                )
                ELSE 0
            END AS TongSLDaGiao
        FROM baogia bg
        JOIN chitietbaogia ctbg ON bg.BaoGiaID = ctbg.BaoGiaID
        JOIN congty c ON bg.CongTyID = c.CongTyID
        LEFT JOIN donhang dh ON bg.BaoGiaID = dh.BaoGiaID
        WHERE 1=1
    ";

    // Thêm các điều kiện lọc
    if ($startDate) { $sql .= " AND bg.NgayBaoGia >= ?"; $params[] = $startDate; $types .= 's'; }
    if ($endDate) { $sql .= " AND bg.NgayBaoGia <= ?"; $params[] = $endDate; $types .= 's'; }
    if ($customerId) { $sql .= " AND bg.CongTyID = ?"; $params[] = $customerId; $types .= 'i'; }
    if ($productCode) {
        $sql .= " AND bg.BaoGiaID IN (SELECT BaoGiaID FROM chitietbaogia WHERE MaHang LIKE ?)";
        $params[] = "%{$productCode}%"; $types .= 's';
    }
    if ($projectName) { 
        $sql .= " AND (dh.TenDuAn LIKE ? OR bg.TenDuAn LIKE ?)"; 
        $params[] = "%{$projectName}%"; $types .= 's';
        $params[] = "%{$projectName}%"; $types .= 's';
    }

    // Lọc theo trạng thái báo giá
    if ($quoteStatus === 'da_chot') {
        $sql .= " AND dh.YCSX_ID IS NOT NULL";
    } elseif ($quoteStatus === 'chua_chot') {
        $sql .= " AND dh.YCSX_ID IS NULL";
    }


    $sql .= " ORDER BY bg.NgayBaoGia DESC, bg.BaoGiaID";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) throw new Exception("Lỗi khi chuẩn bị câu lệnh: " . $conn->error);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $allQuotesData = [];

    // Gom nhóm tất cả dữ liệu theo BaoGiaID
    while ($row = $result->fetch_assoc()) {
        $baoGiaID = $row['BaoGiaID'];
        
        if (!isset($allQuotesData[$baoGiaID])) {
            $allQuotesData[$baoGiaID] = [
                'BaoGiaID' => $baoGiaID, 'SoBaoGia' => $row['SoBaoGia'], 'NgayBaoGia' => $row['NgayBaoGia'],
                'TenCongTy' => $row['TenCongTy'], 'TenDuAn' => $row['TenDuAn'], 'YCSX_ID' => $row['YCSX_ID'],
                'status' => $row['YCSX_ID'] !== null ? 'Đã chốt' : 'Chưa chốt',
                'TotalSoLuongBaoGia' => 0, 'TotalSLDaGiao' => 0, 'products' => []
            ];
        }
        
        $allQuotesData[$baoGiaID]['TotalSoLuongBaoGia'] += (int)$row['SoLuongBaoGia'];
        if ($row['YCSX_ID'] !== null) {
            $allQuotesData[$baoGiaID]['TotalSLDaGiao'] += (int)$row['TongSLDaGiao'];
        }
        $allQuotesData[$baoGiaID]['products'][] = $row;
    }
    $stmt->close();
    
    // Tính toán dữ liệu tổng hợp TRƯỚC KHI LỌC chi tiết
    $summaryData = [
        'totalBaoGiaChuaChot' => 0, 'totalSoLuongChuaChot' => 0,
        'totalDonHangDaChot' => 0, 'totalSoLuongDaChot' => 0, 'totalSoLuongDaGiao' => 0
    ];
    foreach ($allQuotesData as $quote) {
        if ($quote['status'] === 'Đã chốt') {
            $summaryData['totalDonHangDaChot']++;
            $summaryData['totalSoLuongDaChot'] += $quote['TotalSoLuongBaoGia'];
            $summaryData['totalSoLuongDaGiao'] += $quote['TotalSLDaGiao'];
        } else {
            $summaryData['totalBaoGiaChuaChot']++;
            $summaryData['totalSoLuongChuaChot'] += $quote['TotalSoLuongBaoGia'];
        }
    }

    // Lọc và xử lý chi tiết giao hàng cho các đơn đã chốt
    $finalData = [];
    $deliverySql = "
        SELECT bbgh.SoBBGH, bbgh.NgayGiao, ctbb.SoLuong as SoLuongGiao, pxk.SoPhieuXuat
        FROM bienbangiaohang bbgh JOIN chitietbienbangiaohang ctbb ON bbgh.BBGH_ID = ctbb.BBGH_ID
        LEFT JOIN phieuxuatkho pxk ON bbgh.PhieuXuatKhoID = pxk.PhieuXuatKhoID
        WHERE bbgh.BaoGiaID = ? AND ctbb.MaHang COLLATE utf8mb4_unicode_ci = ? AND bbgh.TrangThai != 'Đã hủy'
        ORDER BY bbgh.NgayGiao ASC";
    $deliveryStmt = $conn->prepare($deliverySql);

    foreach ($allQuotesData as $baoGiaID => &$quote) {
        $productDetails = [];
        if ($quote['status'] === 'Đã chốt') {
            $quote['OverallCompletion'] = $quote['TotalSoLuongBaoGia'] > 0 ? round(($quote['TotalSLDaGiao'] / $quote['TotalSoLuongBaoGia']) * 100, 0) : 0;
            
            if ($deliveryStatus === 'completed' && $quote['OverallCompletion'] < 100) continue;
            if ($deliveryStatus === 'partial' && ($quote['OverallCompletion'] == 0 || $quote['OverallCompletion'] >= 100)) continue;
            if ($deliveryStatus === 'pending' && $quote['OverallCompletion'] > 0) continue;

            foreach($quote['products'] as $productRow) {
                $deliveries = [];
                $deliveryStmt->bind_param('is', $baoGiaID, $productRow['MaHang']);
                $deliveryStmt->execute();
                $deliveryResult = $deliveryStmt->get_result();
                while($delivery = $deliveryResult->fetch_assoc()) { $deliveries[] = $delivery; }
                $soLuongBaoGia = (int)$productRow['SoLuongBaoGia'];
                $tongSLDaGiao = (int)$productRow['TongSLDaGiao'];
                $productDetails[] = [
                    'MaHang' => $productRow['MaHang'], 'TenSanPham' => $productRow['TenSanPham'],
                    'SoLuongBaoGia' => $soLuongBaoGia, 'TongSLDaGiao' => $tongSLDaGiao,
                    'SoLuongConLai' => $soLuongBaoGia - $tongSLDaGiao,
                    'PhanTramHoanThanh' => $soLuongBaoGia > 0 ? round(($tongSLDaGiao / $soLuongBaoGia) * 100, 0) : 0,
                    'deliveries' => $deliveries
                ];
            }
        } else { // Báo giá chưa chốt
            if ($deliveryStatus !== 'all') continue; // Ẩn báo giá chưa chốt nếu có lọc trạng thái giao hàng
            foreach($quote['products'] as $productRow) {
                 $productDetails[] = [
                    'MaHang' => $productRow['MaHang'], 'TenSanPham' => $productRow['TenSanPham'],
                    'SoLuongBaoGia' => (int)$productRow['SoLuongBaoGia'], 'TongSLDaGiao' => 0,
                    'SoLuongConLai' => (int)$productRow['SoLuongBaoGia'], 'PhanTramHoanThanh' => 0, 'deliveries' => []
                ];
            }
        }
        $quote['products'] = $productDetails;
        $finalData[] = $quote;
    }
    $deliveryStmt->close();

    $response['success'] = true;
    $response['data'] = array_values($finalData);
    $response['summary'] = $summaryData;
    $response['total_records'] = count($finalData);

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi server: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

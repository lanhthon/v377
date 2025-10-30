<?php
// report_mapping_status.php
// Tạo báo cáo chi tiết về tình trạng mapping

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = [
    'success' => false,
    'summary' => [],
    'by_transaction_type' => [],
    'unmapped_details' => [],
    'recommendations' => [],
    'message' => ''
];

try {
    // 1. Tổng quan
    $sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN MaSanPham_Temp IS NOT NULL THEN 1 ELSE 0 END) as mapped,
            SUM(CASE WHEN MaSanPham_Temp IS NULL THEN 1 ELSE 0 END) as unmapped
        FROM lichsunhapxuat
    ";
    $result = $conn->query($sql);
    $summary = $result->fetch_assoc();
    $coverage = round(($summary['mapped'] / $summary['total']) * 100, 2);
    
    $response['summary'] = [
        'total_records' => $summary['total'],
        'mapped_records' => $summary['mapped'],
        'unmapped_records' => $summary['unmapped'],
        'coverage_percent' => $coverage,
        'status' => $coverage >= 80 ? '✅ Tốt' : ($coverage >= 60 ? '⚠️ Trung bình' : '❌ Cần cải thiện')
    ];
    
    // 2. Chi tiết theo loại giao dịch
    $sql = "
        SELECT 
            LoaiGiaoDich,
            COUNT(*) as Total,
            SUM(CASE WHEN MaSanPham_Temp IS NOT NULL THEN 1 ELSE 0 END) as Mapped,
            SUM(CASE WHEN MaSanPham_Temp IS NULL THEN 1 ELSE 0 END) as Unmapped,
            ROUND(SUM(CASE WHEN MaSanPham_Temp IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as Percent
        FROM lichsunhapxuat
        GROUP BY LoaiGiaoDich
        ORDER BY Unmapped DESC
    ";
    $result = $conn->query($sql);
    $by_type = [];
    while ($row = $result->fetch_assoc()) {
        $row['status'] = $row['Percent'] >= 80 ? '✅' : ($row['Percent'] >= 50 ? '⚠️' : '❌');
        $by_type[] = $row;
    }
    $response['by_transaction_type'] = $by_type;
    
    // 3. Chi tiết các ID chưa map được
    $sql = "
        SELECT 
            ls.SanPhamID as UnmappedID,
            GROUP_CONCAT(DISTINCT ls.LoaiGiaoDich) as UsedInTypes,
            COUNT(*) as UsageCount,
            MIN(ls.NgayGiaoDich) as FirstUsed,
            MAX(ls.NgayGiaoDich) as LastUsed
        FROM lichsunhapxuat ls
        WHERE ls.MaSanPham_Temp IS NULL
        GROUP BY ls.SanPhamID
        ORDER BY UsageCount DESC
    ";
    $result = $conn->query($sql);
    $unmapped = [];
    while ($row = $result->fetch_assoc()) {
        // Thử tìm thông tin về ID này trong các bảng khác
        $id = $row['UnmappedID'];
        
        // Tìm trong chitiet_donhang
        $sql2 = "SELECT MaHang, TenSanPham FROM chitiet_donhang WHERE SanPhamID = ? LIMIT 1";
        $stmt = $conn->prepare($sql2);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $info = $res->fetch_assoc();
            $row['info_mahang'] = $info['MaHang'];
            $row['info_tensanpham'] = $info['TenSanPham'];
            $row['info_source'] = 'chitiet_donhang';
        }
        $stmt->close();
        
        // Nếu chưa có, tìm trong chitietphieunhapkho
        if (!isset($row['info_mahang'])) {
            $sql2 = "
                SELECT ct.SoLuong, pnk.SoPhieuNhapKho 
                FROM chitietphieunhapkho ct
                INNER JOIN phieunhapkho pnk ON ct.PhieuNhapKhoID = pnk.PhieuNhapKhoID
                WHERE ct.SanPhamID = ? 
                LIMIT 1
            ";
            $stmt = $conn->prepare($sql2);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $info = $res->fetch_assoc();
                $row['info_sophieu'] = $info['SoPhieuNhapKho'];
                $row['info_source'] = 'chitietphieunhapkho';
            }
            $stmt->close();
        }
        
        $unmapped[] = $row;
    }
    $response['unmapped_details'] = $unmapped;
    
    // 4. Khuyến nghị
    $recommendations = [];
    
    if ($coverage < 60) {
        $recommendations[] = [
            'priority' => 'Cao',
            'title' => 'Coverage thấp - Cần backup database cũ',
            'description' => "Chỉ {$coverage}% được mapping. Có thể cần backup database trước khi đổi ID để tạo bảng mapping đầy đủ.",
            'action' => 'Tìm backup database hoặc xem xét xóa dữ liệu cũ không cần thiết'
        ];
    }
    
    if (count($unmapped) > 0) {
        $recommendations[] = [
            'priority' => 'Trung bình',
            'title' => 'Còn ' . count($unmapped) . ' ID cũ không map được',
            'description' => 'Các ID này không tồn tại trong variants và không tìm được thông tin mapping.',
            'action' => 'Xem chi tiết trong unmapped_details để quyết định xử lý thủ công hoặc xóa'
        ];
    }
    
    // Tìm các loại giao dịch coverage thấp
    foreach ($by_type as $type) {
        if ($type['Percent'] < 50 && $type['Total'] > 10) {
            $recommendations[] = [
                'priority' => 'Thấp',
                'title' => "Loại giao dịch '{$type['LoaiGiaoDich']}' coverage thấp",
                'description' => "Chỉ {$type['Percent']}% được mapping ({$type['Unmapped']}/{$type['Total']} bản ghi).",
                'action' => 'Xem xét tầm quan trọng của loại giao dịch này'
            ];
        }
    }
    
    if (count($recommendations) == 0) {
        $recommendations[] = [
            'priority' => 'Info',
            'title' => '✅ Mapping hoàn tất tốt',
            'description' => "Coverage đạt {$coverage}% - chấp nhận được.",
            'action' => 'Có thể xử lý thủ công các bản ghi còn lại nếu quan trọng'
        ];
    }
    
    $response['recommendations'] = $recommendations;
    
    // 5. Tạo CSV export cho các ID chưa map
    if (count($unmapped) > 0) {
        $csv_data = "UnmappedID,UsageCount,UsedInTypes,FirstUsed,LastUsed,InfoMaHang,InfoTenSanPham,InfoSource\n";
        foreach ($unmapped as $item) {
            $csv_data .= $item['UnmappedID'] . ',';
            $csv_data .= $item['UsageCount'] . ',';
            $csv_data .= '"' . $item['UsedInTypes'] . '",';
            $csv_data .= $item['FirstUsed'] . ',';
            $csv_data .= $item['LastUsed'] . ',';
            $csv_data .= '"' . ($item['info_mahang'] ?? '') . '",';
            $csv_data .= '"' . ($item['info_tensanpham'] ?? '') . '",';
            $csv_data .= ($item['info_source'] ?? '') . "\n";
        }
        $response['csv_export'] = base64_encode($csv_data);
    }
    
    $response['success'] = true;
    $response['message'] = "Báo cáo hoàn tất. Coverage: {$coverage}%";

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
<?php
// File: api/get_inventory_sales_report_enhanced.php
// API để lấy dữ liệu chi tiết về báo giá và giao hàng

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'summary' => [], 'message' => ''];

try {
    // Lấy tham số từ client
    $startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : null;
    $customerId = isset($_GET['customer_id']) && !empty($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $productCode = isset($_GET['product_code']) && !empty($_GET['product_code']) ? $_GET['product_code'] : null;
    $projectName = isset($_GET['project_name']) && !empty($_GET['project_name']) ? $_GET['project_name'] : null;

    $params = [];
    $types = '';

    // SQL lấy thông tin báo giá và tổng hợp giao hàng
    $sql = "
        SELECT 
            bg.BaoGiaID,
            bg.SoBaoGia,
            bg.NgayBaoGia,
            bg.NgayGiaoDuKien,
            c.TenCongTy,
            dh.TenDuAn,
            dh.YCSX_ID,
            dh.SoYCSX,
            dh.TrangThai as TrangThaiDonHang,
            ctbg.ChiTietID,
            ctbg.MaHang,
            ctbg.TenSanPham,
            ctbg.DoDay,
            ctbg.ChieuRong as BanRong,
            ctbg.DonViTinh,
            ctbg.SoLuong AS SoLuongBaoGia,
            ctbg.variant_id,
            
            -- Lấy tồn kho hiện tại
            COALESCE(vi.quantity, 0) AS TonKhoHienTai,
            
            -- Tính tổng số lượng đã giao qua các đợt
            COALESCE(
                (SELECT SUM(ctbb.SoLuong)
                 FROM bienbangiaohang bbgh
                 JOIN chitietbienbangiaohang ctbb ON bbgh.BBGH_ID = ctbb.BBGH_ID
                 WHERE bbgh.BaoGiaID = bg.BaoGiaID
                   AND ctbb.MaHang COLLATE utf8mb4_unicode_ci = ctbg.MaHang
                   AND bbgh.TrangThai != 'Đã hủy'),
                0
            ) AS TongSLDaGiao,
            
            -- Tính tổng số lượng đã xuất kho
            COALESCE(
                (SELECT SUM(ctpxk.SoLuongThucXuat)
                 FROM phieuxuatkho pxk
                 JOIN chitiet_phieuxuatkho ctpxk ON pxk.PhieuXuatKhoID = ctpxk.PhieuXuatKhoID
                 WHERE pxk.YCSX_ID = dh.YCSX_ID
                   AND ctpxk.MaHang COLLATE utf8mb4_unicode_ci = ctbg.MaHang),
                0
            ) AS TongSLXuatKho
            
        FROM baogia bg
        JOIN chitietbaogia ctbg ON bg.BaoGiaID = ctbg.BaoGiaID
        JOIN congty c ON bg.CongTyID = c.CongTyID
        JOIN donhang dh ON bg.BaoGiaID = dh.BaoGiaID
        LEFT JOIN variant_inventory vi ON ctbg.variant_id = vi.variant_id
        WHERE dh.TrangThai IN ('Hoàn thành', 'Đang giao hàng', 'Đã xuất kho')
    ";

    // Thêm điều kiện lọc
    if ($startDate) {
        $sql .= " AND bg.NgayBaoGia >= ?";
        $params[] = $startDate;
        $types .= 's';
    }
    if ($endDate) {
        $sql .= " AND bg.NgayBaoGia <= ?";
        $params[] = $endDate;
        $types .= 's';
    }
    if ($customerId) {
        $sql .= " AND bg.CongTyID = ?";
        $params[] = $customerId;
        $types .= 'i';
    }
    if ($productCode) {
        $sql .= " AND ctbg.MaHang LIKE ?";
        $params[] = "%{$productCode}%";
        $types .= 's';
    }
    if ($projectName) {
        $sql .= " AND dh.TenDuAn LIKE ?";
        $params[] = "%{$projectName}%";
        $types .= 's';
    }

    $sql .= " ORDER BY bg.NgayBaoGia DESC, c.TenCongTy, ctbg.TenSanPham";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $productSummary = [];
    
    while ($row = $result->fetch_assoc()) {
        $ycsxId = $row['YCSX_ID'];
        $maHang = $row['MaHang'];
        $chiTietId = $row['ChiTietID'];
        
        // Tính toán
        $soLuongBaoGia = (int)$row['SoLuongBaoGia'];
        $tongSLDaGiao = (int)$row['TongSLDaGiao'];
        $soLuongConLai = $soLuongBaoGia - $tongSLDaGiao;
        $phanTramHoanThanh = $soLuongBaoGia > 0 ? round(($tongSLDaGiao / $soLuongBaoGia) * 100, 2) : 0;
        
        // Tính số ngày trễ
        $ngayGiaoDuKien = strtotime($row['NgayGiaoDuKien']);
        $today = time();
        $soNgayTre = 0;
        
        if ($phanTramHoanThanh < 100 && $ngayGiaoDuKien < $today) {
            $soNgayTre = floor(($today - $ngayGiaoDuKien) / (60 * 60 * 24));
        }
        
        // Lọc theo trạng thái nếu cần
        if ($status === 'completed' && $phanTramHoanThanh != 100) continue;
        if ($status === 'partial' && ($phanTramHoanThanh == 0 || $phanTramHoanThanh == 100)) continue;
        if ($status === 'pending' && $phanTramHoanThanh > 0) continue;
        
        // Lấy chi tiết các đợt giao hàng
        $sqlDeliveries = "
            SELECT 
                bbgh.BBGH_ID,
                bbgh.SoBBGH,
                bbgh.NgayGiao,
                bbgh.TrangThai,
                pxk.SoPhieuXuat,
                pxk.NgayXuat,
                pxk.PhieuXuatKhoID,
                cccl.SoCCCL,
                cccl.NgayCap as NgayCapCCCL,
                ctbb.SoLuong as SoLuongGiao
            FROM bienbangiaohang bbgh
            LEFT JOIN chitietbienbangiaohang ctbb ON bbgh.BBGH_ID = ctbb.BBGH_ID
            LEFT JOIN phieuxuatkho pxk ON bbgh.PhieuXuatKhoID = pxk.PhieuXuatKhoID
            LEFT JOIN chungchi_chatluong cccl ON pxk.PhieuXuatKhoID = cccl.PhieuXuatKhoID
            WHERE bbgh.BaoGiaID = ?
              AND ctbb.MaHang COLLATE utf8mb4_unicode_ci = ?
              AND bbgh.TrangThai != 'Đã hủy'
            ORDER BY bbgh.NgayGiao ASC
        ";
        
        $stmtDelivery = $conn->prepare($sqlDeliveries);
        $stmtDelivery->bind_param('is', $row['BaoGiaID'], $maHang);
        $stmtDelivery->execute();
        $deliveryResult = $stmtDelivery->get_result();
        
        $deliveries = [];
        while ($delivery = $deliveryResult->fetch_assoc()) {
            $deliveries[] = [
                'BBGH_ID' => $delivery['BBGH_ID'],
                'SoBBGH' => $delivery['SoBBGH'],
                'NgayGiao' => $delivery['NgayGiao'],
                'TrangThai' => $delivery['TrangThai'],
                'SoPhieuXuat' => $delivery['SoPhieuXuat'],
                'NgayXuat' => $delivery['NgayXuat'],
                'SoCCCL' => $delivery['SoCCCL'],
                'NgayCapCCCL' => $delivery['NgayCapCCCL'],
                'SoLuongGiao' => (int)$delivery['SoLuongGiao']
            ];
        }
        $stmtDelivery->close();
        
        // Thêm vào data
        $data[] = [
            'BaoGiaID' => $row['BaoGiaID'],
            'SoBaoGia' => $row['SoBaoGia'],
            'NgayBaoGia' => $row['NgayBaoGia'],
            'NgayGiaoDuKien' => $row['NgayGiaoDuKien'],
            'TenCongTy' => $row['TenCongTy'],
            'TenDuAn' => $row['TenDuAn'],
            'YCSX_ID' => $row['YCSX_ID'],
            'SoYCSX' => $row['SoYCSX'],
            'TrangThaiDonHang' => $row['TrangThaiDonHang'],
            'MaHang' => $maHang,
            'TenSanPham' => $row['TenSanPham'],
            'DoDay' => $row['DoDay'],
            'BanRong' => $row['BanRong'],
            'DonViTinh' => $row['DonViTinh'],
            'SoLuongBaoGia' => $soLuongBaoGia,
            'TongSLDaGiao' => $tongSLDaGiao,
            'TongSLXuatKho' => (int)$row['TongSLXuatKho'],
            'SoLuongConLai' => $soLuongConLai,
            'TonKhoHienTai' => (int)$row['TonKhoHienTai'],
            'PhanTramHoanThanh' => $phanTramHoanThanh,
            'SoNgayTre' => $soNgayTre,
            'deliveries' => $deliveries
        ];
        
        // Tổng hợp theo sản phẩm
        if (!isset($productSummary[$maHang])) {
            $productSummary[$maHang] = [
                'MaHang' => $maHang,
                'TenSanPham' => $row['TenSanPham'],
                'TongSLBaoGia' => 0,
                'TongSLDaGiao' => 0,
                'TongSLConLai' => 0,
                'TonKhoHienTai' => (int)$row['TonKhoHienTai'],
                'SoDonHang' => 0
            ];
        }
        
        $productSummary[$maHang]['TongSLBaoGia'] += $soLuongBaoGia;
        $productSummary[$maHang]['TongSLDaGiao'] += $tongSLDaGiao;
        $productSummary[$maHang]['TongSLConLai'] += $soLuongConLai;
        $productSummary[$maHang]['SoDonHang'] += 1;
    }
    
    // Sắp xếp summary theo số lượng đã giao giảm dần
    usort($productSummary, function($a, $b) {
        return $b['TongSLDaGiao'] - $a['TongSLDaGiao'];
    });
    
    $response['success'] = true;
    $response['data'] = $data;
    $response['summary'] = array_values($productSummary);
    $response['total_records'] = count($data);
    
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi server: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
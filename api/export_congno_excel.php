<?php
// api/export_congno_excel.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// Logic lấy dữ liệu tương tự get_congno_data.php nhưng không phân trang
try {
    global $conn;

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $startDate = isset($_GET['startDate']) ? trim($_GET['startDate']) : '';
    $endDate = isset($_GET['endDate']) ? trim($_GET['endDate']) : '';

    $base_query = "
        FROM donhang dh
        JOIN chuanbihang cbh ON dh.YCSX_ID = cbh.YCSX_ID
        JOIN phieuxuatkho pxk ON cbh.CBH_ID = pxk.CBH_ID
        LEFT JOIN congty ct ON dh.CongTyID = ct.CongTyID
        LEFT JOIN quanly_congno qcn ON dh.YCSX_ID = qcn.YCSX_ID
    ";

    $conditions = ["cbh.TrangThai = 'Đã giao hàng'"];
    $params = [];
    $types = '';

    if (!empty($search)) {
        $conditions[] = "(dh.SoYCSX LIKE ? OR dh.TenDuAn LIKE ? OR ct.TenCongTy LIKE ?)";
        $search_param = "%{$search}%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
        $types .= 'sss';
    }
     if (!empty($startDate)) {
        $conditions[] = "pxk.NgayXuat >= ?";
        $params[] = $startDate;
        $types .= 's';
    }

    if (!empty($endDate)) {
        $conditions[] = "pxk.NgayXuat <= ?";
        $params[] = $endDate;
        $types .= 's';
    }
    
    $today = date('Y-m-d');
    if (!empty($status)) {
         switch ($status) {
            case 'chuathanhtoan':
                $conditions[] = "(qcn.TrangThaiThanhToan IS NULL OR qcn.TrangThaiThanhToan = 'Chưa thanh toán')";
                break;
            case 'dathanhtoan':
                $conditions[] = "qcn.TrangThaiThanhToan = 'Đã thanh toán'";
                break;
            case 'quahan':
                 $conditions[] = "qcn.TrangThaiThanhToan = 'Chưa thanh toán' AND qcn.ThoiHanThanhToan < ?";
                 $params[] = $today;
                 $types .= 's';
                break;
             case 'noxau':
                $conditions[] = "qcn.TrangThaiThanhToan = 'Chưa thanh toán' AND qcn.ThoiHanThanhToan < DATE_SUB(?, INTERVAL 1 MONTH)";
                $params[] = $today;
                $types .= 's';
                break;
        }
    }

    $where_clause = " WHERE " . implode(" AND ", $conditions);

    $data_query = "
        SELECT
            dh.SoYCSX AS 'Số YCSX',
            dh.TenDuAn AS 'Tên Dự Án',
            ct.TenCongTy AS 'Tên Công Ty',
            pxk.NgayXuat AS 'Ngày Giao Hàng',
            dh.TongTien AS 'Tổng Giá Trị',
            qcn.SoTienTamUng AS 'Tạm Ứng',
            (dh.TongTien - COALESCE(qcn.SoTienTamUng, 0)) AS 'Giá Trị Còn Lại',
            qcn.NgayXuatHoaDon AS 'Ngày Xuất Hóa Đơn',
            qcn.ThoiHanThanhToan AS 'Hạn Thanh Toán',
            COALESCE(qcn.TrangThaiThanhToan, 'Chưa thanh toán') AS 'Trạng Thái Thanh Toán',
            CASE 
                WHEN qcn.TrangThaiThanhToan = 'Chưa thanh toán' AND qcn.ThoiHanThanhToan < CURDATE() 
                THEN DATEDIFF(CURDATE(), qcn.ThoiHanThanhToan) 
                ELSE 0 
            END AS SoNgayQuaHan
        " . $base_query . $where_clause . "
        GROUP BY dh.YCSX_ID
        ORDER BY pxk.NgayXuat DESC, dh.YCSX_ID DESC
    ";
    
    $stmt_data = $conn->prepare($data_query);
    if (!empty($types)) {
        $stmt_data->bind_param($types, ...$params);
    }
    $stmt_data->execute();
    $result = $stmt_data->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt_data->close();
    $conn->close();

    // Xuất file Excel
    $filename = "BaoCaoCongNo_" . date('Ymd') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Bổ sung BOM cho UTF-8 để Excel đọc tiếng Việt đúng
    echo "\xEF\xBB\xBF";

    if (!empty($data)) {
        // In header
        echo implode("\t", array_keys($data[0])) . "\n";
        
        // In dữ liệu
        foreach ($data as $row) {
            // Xử lý trạng thái cuối cùng
            if ($row['Trạng Thái Thanh Toán'] === 'Chưa thanh toán' && $row['SoNgayQuaHan'] > 30) {
                 $row['Trạng Thái Thanh Toán'] = 'Nợ xấu';
            } elseif ($row['Trạng Thái Thanh Toán'] === 'Chưa thanh toán' && $row['SoNgayQuaHan'] > 0) {
                 $row['Trạng Thái Thanh Toán'] = 'Quá hạn (' . $row['SoNgayQuaHan'] . ' ngày)';
            }
            unset($row['SoNgayQuaHan']); // Bỏ cột phụ

            // Làm sạch dữ liệu trước khi xuất
            array_walk($row, function(&$str) {
                $str = preg_replace("/\t/", "\\t", $str);
                $str = preg_replace("/\r?\n/", "\\n", $str);
                if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
            });
            echo implode("\t", array_values($row)) . "\n";
        }
    } else {
        echo "Không có dữ liệu để xuất.";
    }

} catch (Exception $e) {
    // Ghi lỗi ra file log thay vì echo ra màn hình
     error_log('Export Error: ' . $e->getMessage());
     // Trả về thông báo lỗi thân thiện
     die("Có lỗi xảy ra trong quá trình xuất file. Vui lòng thử lại sau.");
}
?>

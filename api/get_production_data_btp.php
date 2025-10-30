<?php
/**
 * File: api/get_production_data_btp.php
 * Version: 3.7 - Sửa lỗi xóa bộ lọc ngày.
 * Description: API lấy danh sách lệnh sản xuất và số lượng cho từng tab.
 * - Sửa lỗi: Cải thiện logic để đảm bảo bộ lọc ngày được xóa hoàn toàn khi người dùng nhấn nút "Xóa bộ lọc".
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

global $conn;

// --- Lấy tham số từ request ---
$status_type = $_GET['status_type'] ?? 'inprogress';
// [SỬA ĐỔI] - Xử lý tham số ngày chặt chẽ hơn để đảm bảo giá trị rỗng được coi là null
$start_date = (isset($_GET['start_date']) && !empty($_GET['start_date'])) ? $_GET['start_date'] : null;
$end_date = (isset($_GET['end_date']) && !empty($_GET['end_date'])) ? $_GET['end_date'] : null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$response = ['success' => false, 'orders' => [], 'message' => ''];

try {
    if ($conn->connect_error) {
        throw new Exception('Lỗi kết nối CSDL: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // --- Lấy số lượng cho các tab (không bị ảnh hưởng bởi bộ lọc) ---
    $counts = [];
    $count_inprogress_query = "SELECT COUNT(*) as total FROM lenh_san_xuat WHERE TrangThai IN ('Chờ duyệt', 'Đã duyệt (đang sx)')";
    $counts['inprogress'] = $conn->query($count_inprogress_query)->fetch_assoc()['total'];

    $count_completed_query = "SELECT COUNT(*) as total FROM lenh_san_xuat WHERE TrangThai IN ('Hoàn thành', 'Hủy')";
    $counts['completed'] = $conn->query($count_completed_query)->fetch_assoc()['total'];

    $count_overdue_query = "SELECT COUNT(*) as total FROM lenh_san_xuat WHERE DATEDIFF(NgayHoanThanhUocTinh, CURDATE()) < 0 AND TrangThai NOT IN ('Hoàn thành', 'Hủy')";
    $counts['overdue'] = $conn->query($count_overdue_query)->fetch_assoc()['total'];
    $response['tab_counts'] = $counts;

    // --- Xây dựng điều kiện truy vấn cho danh sách chính ---
    $where_clauses = [];
    $params = [];
    $types = '';
    
    // Luôn lọc theo ngày tạo (NgayTao)
    $date_filter_field = 'lsx.NgayTao'; 

    if ($status_type === 'inprogress') {
        $where_clauses[] = "lsx.TrangThai IN ('Chờ duyệt', 'Đã duyệt (đang sx)')";
    } elseif ($status_type === 'completed') {
        $where_clauses[] = "lsx.TrangThai IN ('Hoàn thành', 'Hủy')";
    } elseif ($status_type === 'overdue') {
        $where_clauses[] = "DATEDIFF(lsx.NgayHoanThanhUocTinh, CURDATE()) < 0";
        $where_clauses[] = "lsx.TrangThai NOT IN ('Hoàn thành', 'Hủy')";
    } else {
        throw new Exception("Loại trạng thái không hợp lệ.");
    }
    
    // [SỬA ĐỔI] - Áp dụng bộ lọc ngày cho trường NgayTao chỉ khi có giá trị hợp lệ
    if ($start_date) {
        $where_clauses[] = "DATE({$date_filter_field}) >= ?";
        $params[] = $start_date;
        $types .= 's';
    }
    if ($end_date) {
        $where_clauses[] = "DATE({$date_filter_field}) <= ?";
        $params[] = $end_date;
        $types .= 's';
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

    // --- Truy vấn đếm tổng số bản ghi (có áp dụng bộ lọc) ---
    $total_rows = 0;
    $count_query = "SELECT COUNT(lsx.LenhSX_ID) FROM lenh_san_xuat lsx {$where_sql}";
    $count_stmt = $conn->prepare($count_query);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $count_stmt->bind_result($total_rows);
    $count_stmt->fetch();
    $count_stmt->close();

    $total_pages = ceil($total_rows / $limit);


    // --- Truy vấn chính lấy dữ liệu LSX (có phân trang và bộ lọc) ---
    $main_query = "
        SELECT 
            lsx.LenhSX_ID, lsx.SoLenhSX, lsx.TrangThai, lsx.NgayTao, lsx.NgayYCSX,
            lsx.NgayHoanThanhUocTinh, lsx.NgayHoanThanhThucTe,
            lsx.NguoiNhanSX, lsx.BoPhanSX,
            lsx.LoaiLSX, lsx.CBH_ID, dh.SoYCSX,
            COALESCE(dh.SoYCSX, 'Sản xuất lưu kho') AS GroupingKey,
            COALESCE(dh.TenCongTy, 'Sản xuất nội bộ') AS TenCongTyDisplay,
            COALESCE(dh.NguoiBaoGia, nd.HoTen) AS NguoiYeuCauDisplay,
            dh.NgayGiaoDuKien, dh.NguoiNhan AS NguoiNhanDonHang,
            cbh.TrangThai AS TrangThaiChuanBiHang,
            cbh.TrangThaiULA AS TrangThaiChuanBiHangULA,
            CASE
                WHEN lsx.TrangThai IN ('Hoàn thành', 'Hủy') THEN 'Đã kết thúc'
                WHEN lsx.NgayHoanThanhUocTinh IS NULL THEN 'Chưa có kế hoạch'
                WHEN DATEDIFF(lsx.NgayHoanThanhUocTinh, CURDATE()) < 0 THEN 'Quá hạn'
                WHEN DATEDIFF(lsx.NgayHoanThanhUocTinh, CURDATE()) <= 3 THEN 'Sắp tới hạn'
                ELSE 'Trong hạn'
            END AS TinhTrangQuaHan,
            DATEDIFF(lsx.NgayHoanThanhUocTinh, CURDATE()) as SoNgayConLai
        FROM lenh_san_xuat lsx
        LEFT JOIN donhang dh ON lsx.YCSX_ID = dh.YCSX_ID
        LEFT JOIN nguoidung nd ON lsx.NguoiYeuCau_ID = nd.UserID 
        LEFT JOIN chuanbihang cbh ON lsx.CBH_ID = cbh.CBH_ID
        {$where_sql}
        ORDER BY lsx.NgayTao DESC
        LIMIT ? OFFSET ?
    ";
    
    $main_stmt = $conn->prepare($main_query);
    $all_params = array_merge($params, [$limit, $offset]);
    $all_types = $types . 'ii';
    $main_stmt->bind_param($all_types, ...$all_params);
    $main_stmt->execute();
    $main_result = $main_stmt->get_result();
    
    $orders_data = [];
    while ($lsx_row = $main_result->fetch_assoc()) {
        $lenhSX_ID = $lsx_row['LenhSX_ID'];
        
        $details_stmt = $conn->prepare("
            SELECT 
                ct.ChiTiet_LSX_ID, ct.SoLuongCayCanSX, ct.SoLuongBoCanSX, ct.GhiChu,
                ct.TrangThai AS TrangThaiChiTiet, v.variant_sku AS MaBTP, u.name AS DonViTinh,
                COALESCE(nk.TongDaSanXuat, 0) AS SoLuongDaSanXuat
            FROM chitiet_lenh_san_xuat ct
            JOIN variants v ON ct.SanPhamID = v.variant_id
            LEFT JOIN products p ON v.product_id = p.product_id
            LEFT JOIN units u ON p.base_unit_id = u.unit_id
            LEFT JOIN (
                SELECT ChiTiet_LSX_ID, SUM(SoLuongHoanThanh) AS TongDaSanXuat
                FROM nhat_ky_san_xuat GROUP BY ChiTiet_LSX_ID
            ) nk ON ct.ChiTiet_LSX_ID = nk.ChiTiet_LSX_ID
            WHERE ct.LenhSX_ID = ? ORDER BY ct.ChiTiet_LSX_ID ASC
        ");
        $details_stmt->bind_param("i", $lenhSX_ID);
        $details_stmt->execute();
        $details_result = $details_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $details_stmt->close();

        $orders_data[] = ['info'  => $lsx_row, 'items' => $details_result];
    }
    $main_stmt->close();

    $response['success'] = true;
    $response['orders'] = $orders_data;
    $response['pagination'] = [
        'page' => $page,
        'limit' => $limit,
        'totalRows' => $total_rows,
        'totalPages' => $total_pages
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
    error_log("Lỗi trong get_production_data_btp.php: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>


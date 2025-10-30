<?php
// File: api/get_issued_slips.php
// Version: 3.4 - Thay thế cột Tên Khách Hàng bằng Tên Dự Án

// Báo cáo tất cả lỗi PHP để dễ dàng gỡ lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Thiết lập header để trả về JSON và charset UTF-8
header('Content-Type: application/json; charset=utf-8');

// Bao gồm tệp cấu hình cơ sở dữ liệu
require_once '../config/database.php'; // Đảm bảo đường dẫn này chính xác

try {
    global $conn;

    // --- 1. Thiết lập các tham số phân trang và bộ lọc ---
    $items_per_page = 15; // Số lượng mục trên mỗi trang

    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $items_per_page;

    // Lấy các giá trị bộ lọc từ request
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $startDate = isset($_GET['startDate']) ? trim($_GET['startDate']) : '';
    $endDate = isset($_GET['endDate']) ? trim($_GET['endDate']) : '';

    // --- 2. Xây dựng câu truy vấn SQL động ---
    $base_query = "
        FROM phieuxuatkho pxk
        LEFT JOIN chuanbihang cbh ON pxk.CBH_ID = cbh.CBH_ID
        LEFT JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
        LEFT JOIN congty ct ON dh.CongTyID = ct.CongTyID
    ";

    $conditions = [];
    $params = [];
    $types = ''; // Chuỗi kiểu dữ liệu cho prepared statement

    $conditions[] = "pxk.LoaiPhieu = ?";
    $params[] = 'xuat_thanh_pham';
    $types .= 's';

    // Thêm điều kiện tìm kiếm (search)
    if (!empty($search)) {
        $conditions[] = "(pxk.SoPhieuXuat LIKE ? OR dh.SoYCSX LIKE ? OR dh.TenDuAn LIKE ? OR ct.MaCongTy LIKE ? OR pxk.NguoiNhan LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param; // Cho TenDuAn
        $params[] = $search_param; // Cho MaCongTy
        $params[] = $search_param; // Cho NguoiNhan
        $types .= 'sssss';
    }

    // Thêm điều kiện lọc theo trạng thái (status)
    if (!empty($status)) {
        $conditions[] = "cbh.TrangThai = ?";
        $params[] = $status;
        $types .= 's';
    }

    // Thêm điều kiện lọc theo ngày bắt đầu (startDate)
    if (!empty($startDate)) {
        $conditions[] = "pxk.NgayXuat >= ?";
        $params[] = $startDate;
        $types .= 's';
    }
    
    // Thêm điều kiện lọc theo ngày kết thúc (endDate)
    if (!empty($endDate)) {
        $conditions[] = "pxk.NgayXuat <= ?";
        $params[] = $endDate;
        $types .= 's';
    }

    $where_clause = "";
    if (count($conditions) > 0) {
        $where_clause = " WHERE " . implode(" AND ", $conditions);
    }

    // --- 3. Truy vấn đếm tổng số bản ghi (để tính toán phân trang) ---
    $count_query = "SELECT COUNT(pxk.PhieuXuatKhoID)" . $base_query . $where_clause;
    $stmt_count = $conn->prepare($count_query);
    if (count($params) > 0) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_records = 0;
    $stmt_count->bind_result($total_records);
    $stmt_count->fetch();
    $stmt_count->close();

    $total_pages = ceil($total_records / $items_per_page);

    // --- 4. Truy vấn lấy dữ liệu cho trang hiện tại ---
    $data_query = "
        SELECT
            pxk.PhieuXuatKhoID,
            pxk.SoPhieuXuat,
            pxk.NgayXuat,
            pxk.NguoiNhan,
            cbh.TrangThai,
            dh.SoYCSX,
            ct.MaCongTy AS MaKhachHang,
            dh.TenDuAn
        " . $base_query . $where_clause . "
        ORDER BY pxk.NgayXuat DESC, pxk.PhieuXuatKhoID DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt_data = $conn->prepare($data_query);
    
    $data_params = $params;
    $data_types = $types . 'ii';
    $data_params[] = $items_per_page;
    $data_params[] = $offset;

    if (!empty($data_types)) {
        $stmt_data->bind_param($data_types, ...$data_params);
    }
    
    $stmt_data->execute();
    $result = $stmt_data->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt_data->close();

    // --- 5. Trả về kết quả dưới dạng JSON theo cấu trúc chuẩn ---
    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => (int)$total_pages,
            'total_records' => (int)$total_records
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Xử lý các lỗi có thể xảy ra và trả về thông báo lỗi
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi máy chủ: ' . $e->getMessage(),
        'data' => [],
        'pagination' => null
    ], JSON_UNESCAPED_UNICODE);
}

// Đóng kết nối cơ sở dữ liệu
$conn->close();
?>

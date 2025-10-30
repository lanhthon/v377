<?php
// api/get_inventory_history.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Điều chỉnh đường dẫn nếu cần

$response = ['success' => false, 'data' => [], 'total_items' => 0, 'message' => ''];

try {
    // THÊM MỚI: Kiểm tra xem đây có phải là yêu cầu xuất file Excel không
    $is_export = isset($_GET['export']) && $_GET['export'] === 'true';

    // Lấy tham số lọc
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;
    $type = $_GET['type'] ?? ''; // Loại giao dịch

    $where_clauses = [];
    $params = [];
    $param_types = "";

    // Thêm điều kiện lọc theo ngày bắt đầu
    if (!empty($start_date)) {
        $where_clauses[] = "ls.NgayGiaoDich >= ?";
        $params[] = $start_date . " 00:00:00";
        $param_types .= "s";
    }
    // Thêm điều kiện lọc theo ngày kết thúc
    if (!empty($end_date)) {
        $where_clauses[] = "ls.NgayGiaoDich <= ?";
        $params[] = $end_date . " 23:59:59";
        $param_types .= "s";
    }
    // Thêm điều kiện lọc theo loại giao dịch
    if (!empty($type)) {
        $where_clauses[] = "ls.LoaiGiaoDich = ?";
        $params[] = $type;
        $param_types .= "s";
    }

    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // THAY ĐỔI: Chỉ thực hiện phân trang và đếm tổng số mục nếu không phải là yêu cầu xuất Excel
    if (!$is_export) {
        // Lấy tham số phân trang
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
        $offset = ($page - 1) * $limit;

        // 1. Truy vấn tổng số mục để tính toán phân trang
        $sql_count = "SELECT COUNT(ls.LichSuID) AS total FROM lichsunhapxuat AS ls " . $where_sql;
        $stmt_count = $conn->prepare($sql_count);
        if ($stmt_count === false) {
            throw new Exception("Lỗi chuẩn bị câu lệnh đếm: " . $conn->error);
        }
        if (!empty($param_types)) {
            $stmt_count->bind_param($param_types, ...$params);
        }
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $total_items = $result_count->fetch_assoc()['total'];
        $stmt_count->close();
        $response['total_items'] = (int)$total_items;
    }

    // 2. Xây dựng câu truy vấn dữ liệu chính
    $sql = "
        SELECT
            ls.LichSuID,
            IFNULL(v.variant_sku, CONCAT('ID Cũ: ', ls.SanPhamID)) as variant_sku,
            IFNULL(v.variant_name, CONCAT('SẢN PHẨM (ID: ', ls.SanPhamID, ')')) as variant_name,
            ls.NgayGiaoDich,
            ls.LoaiGiaoDich,
            ls.SoLuongThayDoi,
            ls.SoLuongSauGiaoDich,
            ls.MaThamChieu,
            ls.GhiChu
        FROM lichsunhapxuat AS ls
        LEFT JOIN variants AS v ON ls.SanPhamID = v.variant_id
        " . $where_sql . "
        ORDER BY ls.NgayGiaoDich DESC
    ";

    // THAY ĐỔI: Chỉ thêm LIMIT và OFFSET nếu không phải là xuất Excel
    if (!$is_export) {
        $sql .= " LIMIT ? OFFSET ?";
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh chính: " . $conn->error);
    }

    // THAY ĐỔI: Điều chỉnh việc bind tham số cho phù hợp
    $current_params = $params;
    $current_param_types = $param_types;
    if (!$is_export) {
        $current_params[] = $limit; // đã khai báo ở trên
        $current_params[] = $offset; // đã khai báo ở trên
        $current_param_types .= "ii";
    }
    
    if (!empty($current_param_types)) {
        $stmt->bind_param($current_param_types, ...$current_params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();

    $response['success'] = true;
    $response['data'] = $data;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi truy vấn CSDL: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
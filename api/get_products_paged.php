<?php
// File: api/get_products_paged.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$size = isset($_GET['size']) ? (int)$_GET['size'] : 50;
$offset = ($page - 1) * $size;

$filters = isset($_GET['filter']) ? $_GET['filter'] : [];
$where_clauses = [];
$params = [];
$types = "";

$text_search_clauses = [];
$text_value = null;

// Lặp qua các bộ lọc từ Tabulator
foreach ($filters as $filter) {
    if (isset($filter['value']) && $filter['value'] !== '') {
        $field = $filter['field'];
        $value = $filter['value'];

        // Xử lý tìm kiếm theo Mã hoặc Tên (gom vào một nhóm OR)
        if ($field === 'MaHang' || $field === 'TenSanPham') {
            if ($text_value === null) {
                $text_value = "%" . $value . "%";
            }
        } 
        // Xử lý lọc theo Nhóm sản phẩm
        elseif ($field === 'NhomID') {
            $where_clauses[] = "`NhomID` = ?";
            $params[] = $value;
            $types .= 'i';
        }
    }
}

// Nếu có giá trị tìm kiếm văn bản, thêm vào điều kiện WHERE
if ($text_value !== null) {
    $where_clauses[] = "(`MaHang` LIKE ? OR `TenSanPham` LIKE ?)";
    // Thêm tham số cho cả MaHang và TenSanPham
    array_unshift($params, $text_value, $text_value);
    $types = "ss" . $types;
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

try {
    // Câu lệnh đếm tổng số dòng
    $total_sql = "SELECT COUNT(SanPhamID) as total FROM sanpham" . $where_sql;
    $stmt_total = $conn->prepare($total_sql);
    if (!empty($params)) {
        $stmt_total->bind_param($types, ...$params);
    }
    $stmt_total->execute();
    $total_rows = $stmt_total->get_result()->fetch_assoc()['total'];
    $last_page = $total_rows > 0 ? ceil($total_rows / $size) : 1;
    $stmt_total->close();

    // Câu lệnh lấy dữ liệu đã phân trang
    $data_sql = "SELECT 
                    SanPhamID, LoaiID, NhomID, NguonGoc, MaHang, TenSanPham, 
                    HinhDang, ID_ThongSo, DoDay, BanRong, GiaGoc, DonViTinh, 
                    SoLuongTonKho, DinhMucToiThieu
                 FROM sanpham" . $where_sql . " ORDER BY SanPhamID DESC LIMIT ? OFFSET ?";

    $stmt_data = $conn->prepare($data_sql);

    $data_params = $params;
    $data_params[] = $size;
    $data_params[] = $offset;
    $data_types = $types . "ii";

    $stmt_data->bind_param($data_types, ...$data_params);
    $stmt_data->execute();
    $result = $stmt_data->get_result();
    $products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt_data->close();

    echo json_encode([
        'last_page' => $last_page,
        'data' => $products
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn CSDL: ' . $e->getMessage()]);
}

$conn->close();
?>
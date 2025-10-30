<?php
// api/get_products_with_stock.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Đảm bảo đường dẫn này chính xác

$response = ['success' => false, 'data' => [], 'filters' => [], 'message' => ''];

try {
    // Câu lệnh SQL đã được cập nhật để lấy thêm 2 định mức
    $sql = "SELECT
                v.variant_id AS productId,
                v.variant_sku AS code,
                v.variant_name AS name,
                COALESCE(vi.quantity, 0) AS currentStock,
                COALESCE(vi.minimum_stock_level, 0) AS minimum_stock_level,
                MAX(CASE WHEN a.name = 'ID Thông Số' THEN ao.value ELSE NULL END) AS id_thongso,
                MAX(CASE WHEN a.name = 'Độ dày' THEN ao.value ELSE NULL END) AS thickness,
                MAX(CASE WHEN a.name = 'Bản rộng' THEN ao.value ELSE NULL END) AS width,
                -- BẮT ĐẦU THÊM MỚI --
                MAX(CASE WHEN a.name = 'Định mức đóng thùng/tải' THEN ao.value ELSE NULL END) AS dinh_muc_dong_thung,
                MAX(CASE WHEN a.name = 'Định mức kg/ bộ' THEN ao.value ELSE NULL END) AS dinh_muc_kg_bo,
                -- KẾT THÚC THÊM MỚI --
                pg.name AS group_name,
                lsp.TenLoai
            FROM variants v
            LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
            LEFT JOIN variant_attributes va ON v.variant_id = va.variant_id
            LEFT JOIN attribute_options ao ON va.option_id = ao.option_id
            LEFT JOIN attributes a ON ao.attribute_id = a.attribute_id
            LEFT JOIN products p ON v.product_id = p.product_id
            LEFT JOIN product_groups pg ON p.group_id = pg.group_id
            LEFT JOIN loaisanpham lsp ON v.LoaiID = lsp.LoaiID
            GROUP BY v.variant_id, v.variant_sku, v.variant_name, vi.quantity, vi.minimum_stock_level, pg.name, lsp.TenLoai
            ORDER BY v.variant_sku ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    $productGroups = []; 
    $thicknesses = []; 
    $widths = [];
    $productTypes = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        // Thu thập các giá trị duy nhất cho các bộ lọc
        if (!empty($row['group_name']) && !in_array($row['group_name'], $productGroups)) {
            $productGroups[] = $row['group_name'];
        }
        if (!empty($row['thickness']) && !in_array($row['thickness'], $thicknesses)) {
            $thicknesses[] = $row['thickness'];
        }
        if (!empty($row['width']) && !in_array($row['width'], $widths)) {
            $widths[] = $row['width'];
        }
        if (!empty($row['TenLoai']) && !in_array($row['TenLoai'], $productTypes)) {
            $productTypes[] = $row['TenLoai'];
        }
    }
    sort($productGroups);
    sort($thicknesses);
    sort($widths);
    sort($productTypes);

    $stmt->close();

    $response['success'] = true;
    $response['data'] = $data;
    $response['filters'] = [
        'productGroups' => $productGroups,
        'thicknesses' => $thicknesses,
        'widths' => $widths,
        'productTypes' => $productTypes 
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi truy vấn CSDL: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
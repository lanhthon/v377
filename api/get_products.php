<?php
/**
 * API Endpoint để lấy danh sách sản phẩm.
 *
 * File này truy vấn CSDL để lấy danh sách các sản phẩm thuộc nhóm "Thành phẩm" (group_id = 2).
 * === CẬP NHẬT QUAN TRỌNG (v6.1) ===
 * - SỬA LỖI: Lấy trực tiếp cột `sku_suffix` từ bảng `variants` thay vì tìm kiếm qua bảng thuộc tính.
 * Điều này đảm bảo giá trị hậu tố (PPR, PVC, TQ...) được trả về chính xác.
 * - Giữ nguyên các trường 'inner_diameter' và 'thickness_pur' để phục vụ logic tìm ULA.
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Kết nối cơ sở dữ liệu

try {
    // 1. Lấy thông tin các cơ chế giá để tính toán
    $price_schemas_query = $conn->query("SELECT MaCoChe, PhanTramDieuChinh FROM cochegia");
    $price_adjustments = [];
    while ($schema = $price_schemas_query->fetch_assoc()) {
        $price_adjustments[strtolower($schema['MaCoChe'])] = (float)$schema['PhanTramDieuChinh'];
    }

    // 2. Câu lệnh SQL chính để lấy dữ liệu sản phẩm (ĐÃ SỬA LỖI)
    $sql = "
        SELECT
            v.variant_id AS productId,
            v.variant_sku AS code,
            v.variant_name AS name,
            v.price AS base_price,
            v.sku_suffix, -- SỬA LỖI: Lấy trực tiếp cột sku_suffix từ bảng variants
            ls.LoaiID AS categoryId,
            ls.TenLoai AS categoryName,

            -- Kỹ thuật PIVOT: chuyển đổi dữ liệu từ hàng thành cột để lấy các thuộc tính
            MAX(CASE WHEN a.name = 'ID Thông Số' THEN ao.value END) AS id_thongso,
            MAX(CASE WHEN a.name = 'Độ dày' THEN ao.value END) AS thickness,
            MAX(CASE WHEN a.name = 'Bản rộng' THEN ao.value END) AS width,
            MAX(CASE WHEN a.name = 'Đường kính trong' THEN ao.value END) AS inner_diameter,
            MAX(CASE WHEN a.name = 'Độ dày theo PUR' THEN ao.value END) AS thickness_pur

        FROM
            variants AS v
        INNER JOIN
            products AS p ON v.product_id = p.product_id
        LEFT JOIN
            loaisanpham AS ls ON CAST(v.LoaiID AS UNSIGNED) = ls.LoaiID
        LEFT JOIN
            variant_attributes AS va ON v.variant_id = va.variant_id
        LEFT JOIN
            attribute_options AS ao ON va.option_id = ao.option_id
        LEFT JOIN
            attributes AS a ON ao.attribute_id = a.attribute_id
        WHERE
            v.variant_sku IS NOT NULL AND v.variant_sku != ''
            AND p.group_id = 2
        GROUP BY
            v.variant_id, v.variant_sku, v.variant_name, v.price, v.sku_suffix, ls.LoaiID, ls.TenLoai -- SỬA LỖI: Thêm v.sku_suffix vào GROUP BY
        ORDER BY
            v.variant_sku ASC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Lỗi truy vấn SQL: " . $conn->error);
    }

    $products = [];
    while ($row = $result->fetch_assoc()) {
        // 3. Tính toán các mức giá p0, p1, p2... cho từng sản phẩm
        $calculated_prices = [];
        $base_price = (float)($row['base_price'] ?? 0);

        foreach ($price_adjustments as $schema_code => $adjustment) {
            $calculated_prices[$schema_code] = $base_price * (1 + $adjustment / 100);
        }

        // 4. Cấu trúc lại dữ liệu trả về cho frontend
        $products[] = [
            'productId'      => $row['productId'],
            'code'           => $row['code'],
            'name'           => $row['name'],
            'id_thongso'     => $row['id_thongso'],
            'thickness'      => $row['thickness'],
            'width'          => $row['width'],
            'categoryId'     => (int)$row['categoryId'],
            'categoryName'   => $row['categoryName'],
            'basePrice'      => $base_price,
            'price'          => $calculated_prices,
            'inner_diameter' => $row['inner_diameter'],
            'thickness_pur'  => $row['thickness_pur'],
            'sku_suffix'     => $row['sku_suffix']
        ];
    }

    // Trả về danh sách sản phẩm dưới dạng JSON
    echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Ghi lại lỗi và trả về thông báo lỗi 500
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}

// Đóng kết nối cơ sở dữ liệu
$conn->close();
?>

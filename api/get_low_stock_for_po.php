<?php
// api/get_low_stock_for_po.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'filters' => [], 'message' => ''];

try {
    global $conn;

    // Lấy các tham số lọc từ request
    $filterGroup = $_GET['group'] ?? '';
    $filterType = $_GET['type'] ?? '';
    $filterThickness = $_GET['thickness'] ?? '';
    $filterWidth = $_GET['width'] ?? '';

    // Câu lệnh SQL để lấy TẤT CẢ sản phẩm BTP/ULA cùng với các thuộc tính
    $sqlAllProducts = "
        SELECT
            v.variant_id, v.variant_sku, v.variant_name,
            COALESCE(vi.quantity, 0) AS currentStock,
            COALESCE(vi.minimum_stock_level, 0) AS minimumStockLevel,
            pg.name AS group_name,
            lsp.TenLoai,
            MAX(CASE WHEN a.name = 'Độ dày' THEN ao.value ELSE NULL END) AS thickness,
            MAX(CASE WHEN a.name = 'Bản rộng' THEN ao.value ELSE NULL END) AS width
        FROM variants v
        LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
        LEFT JOIN products p ON v.product_id = p.product_id
        LEFT JOIN product_groups pg ON p.group_id = pg.group_id
        LEFT JOIN loaisanpham lsp ON v.LoaiID = lsp.LoaiID
        LEFT JOIN variant_attributes va ON v.variant_id = va.variant_id
        LEFT JOIN attribute_options ao ON va.option_id = ao.option_id
        LEFT JOIN attributes a ON ao.attribute_id = a.attribute_id
        WHERE (pg.name = 'Bán thành phẩm' OR v.variant_sku LIKE 'ULA%')
        GROUP BY v.variant_id, pg.name, lsp.TenLoai
        ORDER BY v.variant_sku ASC
    ";
    
    $resultAll = $conn->query($sqlAllProducts);
    if (!$resultAll) {
        throw new Exception("Lỗi truy vấn SQL: " . $conn->error);
    }
    
    $allProducts = $resultAll->fetch_all(MYSQLI_ASSOC);

    // Xây dựng danh sách các giá trị bộ lọc duy nhất
    $productGroups = array_unique(array_column($allProducts, 'group_name'));
    $productTypes = array_unique(array_column($allProducts, 'TenLoai'));
    $thicknesses = array_unique(array_column($allProducts, 'thickness'));
    $widths = array_unique(array_column($allProducts, 'width'));
    
    // Loại bỏ các giá trị rỗng và sắp xếp
    sort($productGroups);
    $productTypes = array_values(array_filter($productTypes)); sort($productTypes);
    $thicknesses = array_values(array_filter($thicknesses)); sort($thicknesses);
    $widths = array_values(array_filter($widths)); sort($widths);

    // Lọc danh sách sản phẩm theo các tham số đã nhận
    $filteredData = array_filter($allProducts, function($product) use ($filterGroup, $filterType, $filterThickness, $filterWidth) {
        $isLowStock = (int)$product['currentStock'] <= (int)$product['minimumStockLevel'];
        if (!$isLowStock) return false;

        if ($filterGroup && $product['group_name'] !== $filterGroup) return false;
        if ($filterType && $product['TenLoai'] !== $filterType) return false;
        if ($filterThickness && $product['thickness'] !== $filterThickness) return false;
        if ($filterWidth && $product['width'] !== $filterWidth) return false;

        return true;
    });

    // Định dạng lại dữ liệu trả về
    $data = [];
    foreach($filteredData as $item) {
        $data[] = [
            'productId' => $item['variant_id'],
            'code' => $item['variant_sku'],
            'name' => $item['variant_name'],
            'currentStock' => $item['currentStock'],
            'minimumStockLevel' => $item['minimumStockLevel']
        ];
    }

    $conn->close();

    $response['success'] = true;
    $response['data'] = array_values($data); // Reset keys
    $response['filters'] = [
        'productGroups' => $productGroups,
        'productTypes' => $productTypes,
        'thicknesses' => $thicknesses,
        'widths' => $widths,
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>

<?php
header('Content-Type: application/json; charset=utf-8');

// Bao gồm tệp cấu hình cơ sở dữ liệu
require_once '../config/database.php';

// Lấy ID đơn hàng từ tham số URL
$donhangId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($donhangId === 0) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ.']);
    exit();
}

try {
    $final_results = [];

    // --- BƯỚC 1: TÌM VÀ CỘNG TỔNG SỐ LƯỢNG SẢN PHẨM THEO TỪNG SIZE REN ---
    // Truy vấn này chỉ làm một việc: lấy tổng số lượng cùm cho mỗi kích thước ren.
    $sql_get_totals = "
        SELECT
            ao.value AS ThreadSize,
            SUM(ctdh.SoLuong) AS TotalProductQuantity
        FROM chitiet_donhang ctdh
        JOIN variants v ON ctdh.SanPhamID = v.variant_id
        JOIN products p ON v.product_id = p.product_id
        JOIN variant_attributes va ON v.variant_id = va.variant_id
        JOIN attribute_options ao ON va.option_id = ao.option_id
        JOIN attributes a ON ao.attribute_id = a.attribute_id
        WHERE
            ctdh.DonHangID = ?
            AND a.name = 'Kích thước ren'
            AND p.base_sku LIKE '%ULA%' -- Chỉ tính tổng cho các sản phẩm là Cùm Ula
        GROUP BY
            ao.value
    ";

    $stmt_totals = $conn->prepare($sql_get_totals);
    $stmt_totals->bind_param("i", $donhangId);
    $stmt_totals->execute();
    $result_totals = $stmt_totals->get_result();

    $product_groups = $result_totals->fetch_all(MYSQLI_ASSOC);
    $stmt_totals->close();

    if (empty($product_groups)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit();
    }

    // --- BƯỚC 2: VỚI MỖI SIZE REN, TÌM ECU TƯƠNG ỨNG VÀ TÍNH TOÁN ---
    // Chuẩn bị câu lệnh tìm Ecu để dùng nhiều lần trong vòng lặp
    $sql_find_ecu = "
        SELECT
            v.variant_id,
            v.variant_name,
            COALESCE(vi.quantity, 0) AS quantity
        FROM variants v
        JOIN products p ON v.product_id = p.product_id
        JOIN variant_attributes va ON v.variant_id = va.variant_id
        JOIN attribute_options ao ON va.option_id = ao.option_id
        JOIN attributes a ON ao.attribute_id = a.attribute_id
        LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
        WHERE
            a.name = 'Kích thước ren'
            AND ao.value = ?
            AND p.base_sku = 'ECU' -- Chỉ tìm sản phẩm là Ecu
        LIMIT 1
    ";
    $stmt_ecu = $conn->prepare($sql_find_ecu);

    // Lặp qua từng nhóm sản phẩm đã tổng hợp ở bước 1
    foreach ($product_groups as $group) {
        $threadSize = $group['ThreadSize'];
        $totalProductQuantity = (int)$group['TotalProductQuantity'];

        // Tìm mã vật tư Ecu tương ứng với size ren
        $stmt_ecu->bind_param("s", $threadSize);
        $stmt_ecu->execute();
        $result_ecu = $stmt_ecu->get_result();

        if ($ecu = $result_ecu->fetch_assoc()) {
            // Sau khi tìm thấy Ecu, mới thực hiện phép tính
            $soLuongEcuCan = $totalProductQuantity * 2;
            $soKgUocTinh = ceil(($soLuongEcuCan * 0.0065) * 100) / 100; // Giữ nguyên cách tính kg

            $final_results[] = [
                'EcuVariantID' => $ecu['variant_id'],
                'TenEcu' => $ecu['variant_name'],
                'TonKhoEcu' => (int)$ecu['quantity'],
                'SoLuongEcuTong' => $soLuongEcuCan,
                'SoKgUocTinh' => $soKgUocTinh
            ];
        }
    }
    $stmt_ecu->close();

    echo json_encode(['success' => true, 'data' => $final_results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
<?php
// api/get_ecu_to_purchase.php

header('Content-Type: application/json');
// Sử dụng đường dẫn tương đối từ vị trí file api đến file config
// Giả sử thư mục gốc của bạn là public_html, file api nằm trong public_html/api/
// và file config nằm trong public_html/config/
require_once '../config/db_config.php'; 

// --- Hàm trả về phản hồi JSON và thoát
function json_response($success, $data = null, $message = '') {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// --- Lấy CBH_ID từ request
$cbh_id = isset($_GET['cbh_id']) ? intval($_GET['cbh_id']) : 0;
if ($cbh_id === 0) {
    json_response(false, null, 'ID Phiếu chuẩn bị hàng không hợp lệ.');
}

try {
    // Sử dụng hàm get_db_connection() từ file config của bạn
    $pdo = get_db_connection();

    // [MODIFIED] Sửa lại câu lệnh SQL cho đúng với cấu trúc CSDL
    // Join bảng chitiet_ecu_cbh với bảng variants qua tên sản phẩm
    $sql = "
        SELECT
            v.variant_id,
            v.variant_sku,
            ce.TenSanPhamEcu AS variant_name,
            GREATEST(0,
                COALESCE(ce.SoLuongEcu, 0) - (COALESCE(ce.TonKhoSnapshot, 0) - COALESCE(ce.DaGanSnapshot, 0))
            ) AS quantity,
            0 AS price -- Mặc định đơn giá là 0, sẽ nhập sau
        FROM
            chitiet_ecu_cbh ce
        LEFT JOIN
            variants v ON ce.TenSanPhamEcu = v.variant_name -- Join bằng tên sản phẩm
        WHERE
            ce.CBH_ID = :cbh_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':cbh_id', $cbh_id, PDO::PARAM_INT);
    $stmt->execute();

    $items_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lọc ra những sản phẩm có số lượng cần mua > 0
    $items_to_purchase = array_filter($items_raw, function($item) {
        // Đảm bảo có variant_id (tránh trường hợp tên không khớp) và số lượng > 0
        return isset($item['variant_id']) && $item['quantity'] > 0;
    });

    // Reset lại index của mảng sau khi filter để JSON output là một array
    $items_to_purchase = array_values($items_to_purchase);

    if (empty($items_to_purchase)) {
        json_response(true, [], 'Không tìm thấy vật tư cần nhập thêm.');
    } else {
        json_response(true, $items_to_purchase, 'Lấy danh sách vật tư cần nhập thành công.');
    }

} catch (PDOException $e) {
    // Ghi lại lỗi nếu cần: error_log($e->getMessage());
    json_response(false, null, 'Lỗi cơ sở dữ liệu: ' . $e->getMessage());
}
?>
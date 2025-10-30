<?php
// migrate_data.php (Phiên bản 3 - Chống trùng lặp SKU)

set_time_limit(600);
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre style='font-family: monospace; line-height: 1.5;'>";

// --- KẾT NỐI CSDL ---
require_once '../config/database.php'; // Đảm bảo đường dẫn này đúng
$conn->set_charset("utf8mb4");

// --- CÁC HÀM TRỢ GIÚP ---
$cache = [];
function get_or_create_id($conn, $table, $id_column, $value_column, $value, $extra_column = null, $extra_value = null) {
    global $cache;
    $cache_key = "{$table}_{$value_column}_{$value}" . ($extra_value ? "_{$extra_value}" : "");
    if (isset($cache[$cache_key])) return $cache[$cache_key];
    
    $sql = "SELECT `{$id_column}` FROM `{$table}` WHERE `{$value_column}` = ?";
    if ($extra_column) $sql .= " AND `{$extra_column}` = ?";
    
    $stmt = $conn->prepare($sql);
    if ($extra_column) $stmt->bind_param("si", $value, $extra_value);
    else $stmt->bind_param("s", $value);
    
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $id = $row[$id_column];
        $cache[$cache_key] = $id;
        $stmt->close();
        return $id;
    }
    $stmt->close();
    
    $sql_insert = "INSERT INTO `{$table}` (`{$value_column}`" . ($extra_column ? ", `{$extra_column}`" : "") . ") VALUES (? " . ($extra_column ? ", ?" : "") . ")";
    $stmt_insert = $conn->prepare($sql_insert);
    if ($extra_column) $stmt_insert->bind_param("si", $value, $extra_value);
    else $stmt_insert->bind_param("s", $value);
    
    $stmt_insert->execute();
    $id = $conn->insert_id;
    $stmt_insert->close();
    $cache[$cache_key] = $id;
    echo "    -> Đã tạo mới '{$value}' trong bảng '{$table}' với ID: {$id}\n";
    return $id;
}

try {
    echo "<b>BẮT ĐẦU QUÁ TRÌNH CHUYỂN ĐỔI DỮ LIỆU...</b>\n\n";

    // --- DỌN DẸP BẢNG MỚI ---
    $conn->query("SET FOREIGN_KEY_CHECKS = 0;");
    $conn->query("TRUNCATE TABLE variant_inventory;");
    $conn->query("TRUNCATE TABLE variant_attributes;");
    $conn->query("TRUNCATE TABLE variants;");
    $conn->query("TRUNCATE TABLE products;");
    $conn->query("TRUNCATE TABLE attribute_options;");
    $conn->query("TRUNCATE TABLE attributes;");
    $conn->query("TRUNCATE TABLE units;");
    $conn->query("TRUNCATE TABLE product_groups;");
    $conn->query("TRUNCATE TABLE product_types;");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1;");
    echo "=> Đã dọn dẹp các bảng mới.\n\n";

    // --- KIỂM TRA BẢNG DỮ LIỆU GỐC ---
    $check_table = $conn->query("SHOW TABLES LIKE 'sanpham'");
    if($check_table->num_rows == 0) {
        die("LỖI: Không tìm thấy bảng `sanpham` gốc. Vui lòng import lại dữ liệu từ file .sql của bạn vào CSDL `baogia_db` trước khi chạy script này.");
    }
    
    // --- CHUYỂN ĐỔI NHÓM VÀ LOẠI SẢN PHẨM ---
    $group_map = []; $type_map = [];
    $result_groups = $conn->query("SELECT NhomID, TenNhomSanPham FROM nhomsanpham");
    while($row = $result_groups->fetch_assoc()) {
        $group_map[$row['NhomID']] = get_or_create_id($conn, 'product_groups', 'group_id', 'name', $row['TenNhomSanPham']);
    }
    $result_types = $conn->query("SELECT LoaiID, TenLoai FROM loaisanpham");
    while($row = $result_types->fetch_assoc()) {
        $type_map[$row['LoaiID']] = get_or_create_id($conn, 'product_types', 'type_id', 'name', $row['TenLoai']);
    }
    echo "=> Đã chuyển đổi Groups và Types.\n\n";

    // --- TẠO CÁC LOẠI THUỘC TÍNH ---
    $attribute_map = [
        'DoDay' => 'Độ dày', 'BanRong' => 'Bản rộng', 'KichThuocRen' => 'Kích thước ren',
        'HinhDang' => 'Hình dạng', 'ID_ThongSo' => 'ID Thông Số', 'NguonGoc' => 'Nguồn gốc'
    ];
    $attribute_id_map = [];
    foreach ($attribute_map as $key => $name) {
        $attribute_id_map[$key] = get_or_create_id($conn, 'attributes', 'attribute_id', 'name', $name);
    }
    echo "=> Đã tạo các loại thuộc tính chính.\n\n";

    // --- XỬ LÝ VÀ CHUYỂN ĐỔI SẢN PHẨM ---
    $result = $conn->query("SELECT * FROM sanpham ORDER BY SanPhamID");
    $old_products = $result->fetch_all(MYSQLI_ASSOC);
    $total_products = count($old_products);
    
    $processed_skus = []; // Mảng để theo dõi các SKU đã được sử dụng

    $conn->begin_transaction();
    
    foreach ($old_products as $index => $row) {
        echo "<b>[".($index + 1)."/{$total_products}] Xử lý SKU: {$row['MaHang']}</b>\n";

        // Logic thông minh để xác định sản phẩm gốc
        $base_sku = 'KHAC'; $product_name = 'Sản phẩm khác';
        $maHang = strtoupper($row['MaHang']);
        if (strpos($maHang, 'PUR-S') === 0) { $base_sku = 'PUR-S'; $product_name = 'Gối đỡ PU đế vuông'; }
        elseif (strpos($maHang, 'PUR-C') === 0) { $base_sku = 'PUR-C'; $product_name = 'Gối đỡ PU đế tròn'; }
        elseif (strpos($maHang, 'ULA') === 0) { $base_sku = 'ULA'; $product_name = 'Cùm Ula'; }
        elseif (strpos($maHang, 'CV') === 0) { $base_sku = 'CV'; $product_name = 'Cách nhiệt Tấm Vàng'; }
        elseif (strpos($maHang, 'CT') === 0) { $base_sku = 'CT'; $product_name = 'Cách nhiệt Tấm Trắng'; }
        elseif (strpos($maHang, 'ECU') === 0) { $base_sku = 'ECU'; $product_name = 'Ecu các loại'; }

        $new_group_id = isset($row['NhomID']) && isset($group_map[$row['NhomID']]) ? $group_map[$row['NhomID']] : null;
        $new_type_id = isset($row['LoaiID']) && isset($type_map[$row['LoaiID']]) ? $type_map[$row['LoaiID']] : null;

        $product_id = get_or_create_id($conn, 'products', 'product_id', 'base_sku', $base_sku);
        $conn->query("UPDATE products SET name = '{$product_name}', group_id = " . ($new_group_id ?? 'NULL') . ", type_id = " . ($new_type_id ?? 'NULL') . " WHERE product_id = {$product_id}");

        // =================================================================
        // PHẦN NÂNG CẤP ĐỂ XỬ LÝ SKU TRÙNG LẶP
        // =================================================================
        $original_sku = trim($row['MaHang']);
        $current_sku = $original_sku;
        $counter = 1;
        while (in_array($current_sku, $processed_skus)) {
            $current_sku = $original_sku . '-DUP-' . $counter;
            $counter++;
        }
        if ($current_sku !== $original_sku) {
            echo "    <b style='color:orange;'>-> Cảnh báo: SKU '{$original_sku}' bị trùng lặp. Đổi thành '{$current_sku}'.</b>\n";
        }
        $processed_skus[] = $current_sku;
        // =================================================================

        // Tạo biến thể với SKU đã được làm sạch
        $stmt_variant = $conn->prepare("INSERT INTO variants (product_id, variant_sku, price) VALUES (?, ?, ?)");
        $stmt_variant->bind_param("isd", $product_id, $current_sku, $row['GiaGoc']);
        $stmt_variant->execute();
        $variant_id = $conn->insert_id;
        $stmt_variant->close();

        // Gán thuộc tính cho biến thể
        $stmt_attr = $conn->prepare("INSERT INTO variant_attributes (variant_id, option_id) VALUES (?, ?)");
        foreach ($attribute_map as $col => $name) {
            $value = trim($row[$col] ?? '');
            if ($value !== '' && $value !== null) {
                $attr_id = $attribute_id_map[$col];
                $opt_id = get_or_create_id($conn, 'attribute_options', 'option_id', 'value', $value, 'attribute_id', $attr_id);
                $stmt_attr->bind_param("ii", $variant_id, $opt_id);
                $stmt_attr->execute();
            }
        }
        $stmt_attr->close();

        // Cập nhật tồn kho
        if ($row['DonViTinh']) {
            $unit_id = get_or_create_id($conn, 'units', 'unit_id', 'name', $row['DonViTinh']);
            $stmt_inv = $conn->prepare("INSERT INTO variant_inventory (variant_id, unit_id, quantity, minimum_stock_level) VALUES (?, ?, ?, ?)");
            $stmt_inv->bind_param("iiii", $variant_id, $unit_id, $row['SoLuongTonKho'], $row['DinhMucToiThieu']);
            $stmt_inv->execute();
            $stmt_inv->close();
        }
        echo "  -> Thành công!\n\n";
    }

    $conn->commit();
    echo "<b style='color: green;'>QUÁ TRÌNH CHUYỂN ĐỔI HOÀN TẤT!</b>\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "<b style='color: red;'>ĐÃ XẢY RA LỖI: " . $e->getMessage() . "</b>";
}

$conn->close();
echo "</pre>";
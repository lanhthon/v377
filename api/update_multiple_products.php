<?php
// File: api/update_multiple_products.php
// API này nhận một mảng các sản phẩm và cập nhật chúng trong một giao dịch (transaction).

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Nhận mảng các đối tượng sản phẩm được gửi lên
$products = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào
if (empty($products) || !is_array($products)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ hoặc rỗng.']);
    exit;
}

// Danh sách các cột được phép cập nhật để bảo mật
$allowed_columns = [
    'LoaiID', 'NhomSanPham', 'NguonGoc', 'MaHang', 'TenSanPham', 'HinhDang', 'ID_ThongSo', 
    'DoDay', 'BanRong', 'DuongKinhTrong', 'DuongKinhRen', 'GiaGoc', 'DonViTinh', 
    'SoLuongTonKho', 'DinhMucToiThieu', 'NangSuat_BoNgay'
];

// Bắt đầu một transaction
$conn->begin_transaction();

try {
    $updated_count = 0;

    // Lặp qua mỗi sản phẩm trong mảng
    foreach ($products as $product) {
        if (!isset($product['SanPhamID']) || !is_numeric($product['SanPhamID'])) {
            continue; 
        }

        $sanPhamID = (int)$product['SanPhamID'];
        $set_parts = [];
        $params = [];
        $types = "";

        // Xây dựng câu lệnh SET động cho từng sản phẩm
        foreach ($product as $field => $value) {
            if (in_array($field, $allowed_columns)) {
                $set_parts[] = "`{$field}` = ?";
                $params[] = $value;
                
                if (is_int($value)) $types .= "i";
                elseif (is_float($value) || is_double($value)) $types .= "d";
                else $types .= "s";
            }
        }

        if (empty($set_parts)) {
            continue;
        }

        // Hoàn thành câu lệnh SQL
        $sql = "UPDATE sanpham SET " . implode(", ", $set_parts) . " WHERE SanPhamID = ?";
        $params[] = $sanPhamID;
        $types .= "i";

        // Chuẩn bị và thực thi
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if (!$stmt->execute()) {
            // Nếu một câu lệnh thất bại, ném ra một ngoại lệ để hủy toàn bộ transaction
            throw new Exception("Cập nhật thất bại cho ID: {$sanPhamID}. Lỗi: " . $stmt->error);
        }
        $updated_count++;
    }

    // Nếu tất cả đều thành công, commit transaction
    $conn->commit();
    echo json_encode([
        'success' => true, 
        'message' => "Đã cập nhật thành công {$updated_count} sản phẩm."
    ]);

} catch (Exception $e) {
    // Nếu có bất kỳ lỗi nào, rollback transaction
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Giao dịch thất bại: ' . $e->getMessage()]);
}

$conn->close();
?>
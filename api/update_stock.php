<?php
// File: api/update_stock.php
// API này nhận ID sản phẩm và số lượng để cập nhật tồn kho (có thể là số âm để xuất kho).

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Nhận dữ liệu từ body của request (dạng JSON)
$data = json_decode(file_get_contents('php://input'), true);

// --- Validation ---
if (
    !isset($data['product_id']) || !is_numeric($data['product_id']) ||
    !isset($data['quantity']) || !is_numeric($data['quantity'])
) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ. Vui lòng cung cấp product_id và quantity.']);
    exit;
}
if ((int)$data['quantity'] == 0) {
     http_response_code(400);
     echo json_encode(['success' => false, 'message' => 'Số lượng phải khác 0.']);
     exit;
}


$productId = (int)$data['product_id'];
$quantity = (int)$data['quantity']; // Số lượng có thể âm

// Dùng transaction để đảm bảo an toàn dữ liệu
$conn->begin_transaction();

try {
    // Lấy số lượng tồn kho hiện tại để kiểm tra
    $stmt_check = $conn->prepare("SELECT SoLuongTonKho FROM sanpham WHERE SanPhamID = ? FOR UPDATE");
    $stmt_check->bind_param("i", $productId);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
         throw new Exception("Không tìm thấy sản phẩm với ID được cung cấp.");
    }
    
    $currentStock = $result_check->fetch_assoc()['SoLuongTonKho'];
    $stmt_check->close();

    // Kiểm tra nếu xuất kho thì số lượng có đủ không
    if ($quantity < 0 && $currentStock < abs($quantity)) {
        throw new Exception("Không đủ hàng trong kho để xuất. Tồn kho hiện tại: " . $currentStock);
    }
    
    // Chuẩn bị câu lệnh UPDATE
    $stmt = $conn->prepare("UPDATE sanpham SET SoLuongTonKho = SoLuongTonKho + ? WHERE SanPhamID = ?");
    $stmt->bind_param("ii", $quantity, $productId);
    $stmt->execute();
    $stmt->close();
    
    // Lấy số lượng tồn kho mới để trả về cho client
    $newStock = $currentStock + $quantity;
    
    // Commit transaction nếu mọi thứ ổn
    $conn->commit();

    // Trả về kết quả thành công cùng với số tồn kho mới
    echo json_encode([
        'success' => true, 
        'message' => 'Cập nhật kho thành công!', 
        'newStock' => (int)$newStock
    ]);

} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $conn->rollback();
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
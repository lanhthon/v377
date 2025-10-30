<?php
// api/adjust_stock.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// Get data from JSON body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    $response['message'] = 'Dữ liệu không hợp lệ.';
    echo json_encode($response);
    exit;
}

// Assign variables and validate
$variant_id = $data['variant_id'] ?? null;
$new_stock = $data['new_stock'] ?? null;
$transaction_type = $data['transaction_type'] ?? null;
$notes = $data['notes'] ?? '';
$userID = $_SESSION['user']['UserID'] ?? 0; // Get userID from session, ensure your session structure is correct

if ($variant_id === null || $new_stock === null || !is_numeric($new_stock) || $transaction_type === null) {
    http_response_code(400);
    $response['message'] = 'Vui lòng cung cấp đầy đủ thông tin bắt buộc (variant_id, new_stock, transaction_type).';
    echo json_encode($response);
    exit;
}

$new_stock = (int)$new_stock;
if ($new_stock < 0) {
    http_response_code(400);
    $response['message'] = 'Số lượng tồn kho mới không thể là số âm.';
    echo json_encode($response);
    exit;
}


$conn->begin_transaction();

try {
    // 1. Get current stock from variant_inventory table
    $stmt_get = $conn->prepare("SELECT quantity FROM variant_inventory WHERE variant_id = ? FOR UPDATE");
    $stmt_get->bind_param("i", $variant_id);
    $stmt_get->execute();
    $result_get = $stmt_get->get_result();
    
    $currentStock = 0;
    if ($result_get->num_rows > 0) {
        $currentStock = (int)$result_get->fetch_assoc()['quantity'];
    }
    $stmt_get->close();

    $changeAmount = $new_stock - $currentStock;

    if ($changeAmount == 0) {
        $conn->rollback(); // No need to proceed if there's no change
        $response = ['success' => true, 'message' => 'Không có thay đổi về số lượng, không thực hiện giao dịch.'];
        echo json_encode($response);
        exit;
    }

    // 2. Update or Insert into variant_inventory table
    // Use INSERT...ON DUPLICATE KEY UPDATE to handle products that might not have an inventory record yet
    $stmt_update = $conn->prepare("
        INSERT INTO variant_inventory (variant_id, quantity) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE quantity = ?
    ");
    $stmt_update->bind_param("iii", $variant_id, $new_stock, $new_stock);
    if (!$stmt_update->execute()) {
        throw new Exception("Lỗi khi cập nhật tồn kho: " . $stmt_update->error);
    }
    $stmt_update->close();

    // 3. Log the transaction in lichsunhapxuat
    $maThamChieu = "ADJ-" . $userID . "-" . time();
    $stmt_log = $conn->prepare("
        INSERT INTO lichsunhapxuat (SanPhamID, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, GhiChu, MaThamChieu) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    // Note: SanPhamID in lichsunhapxuat corresponds to variant_id
    $stmt_log->bind_param("isddss", $variant_id, $transaction_type, $changeAmount, $new_stock, $notes, $maThamChieu);
    if (!$stmt_log->execute()) {
        throw new Exception("Lỗi khi ghi lịch sử kho: " . $stmt_log->error);
    }
    $stmt_log->close();

    // 4. Commit transaction
    $conn->commit();
    $response = ['success' => true, 'message' => 'Điều chỉnh tồn kho thành công!'];
    echo json_encode($response);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    $response['message'] = 'Lỗi giao dịch: ' . $e->getMessage();
    echo json_encode($response);
}

$conn->close();
?>

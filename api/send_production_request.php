<?php
/**
 * API: Gửi Yêu Cầu Sản Xuất
 * TỐI ƯU V10: Cập nhật trạng thái phiếu CBH, kế hoạch giao hàng và đơn hàng chính.
 * - Cập nhật `chuanbihang`.TrangThai -> 'Mới tạo'
 * - Cập nhật `kehoach_giaohang`.TrangThai -> 'Đã gửi YCSX'
 * - Cập nhật `donhang`.TrangThai với logic đếm số lượng.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Đảm bảo đường dẫn này đúng

$khghId = isset($_POST['khgh_id']) ? intval($_POST['khgh_id']) : 0;

if ($khghId === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Kế hoạch giao hàng không hợp lệ.']);
    exit;
}

// Sử dụng $conn->begin_transaction() cho MySQLi
$conn->begin_transaction();

try {
    // --- BƯỚC 1: LẤY THÔNG TIN KẾ HOẠCH GIAO HÀNG ---
    $check_stmt = $conn->prepare("SELECT TrangThai, DonHangID, CBH_ID FROM kehoach_giaohang WHERE KHGH_ID = ?");
    $check_stmt->bind_param("i", $khghId);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Không tìm thấy kế hoạch giao hàng với ID được cung cấp.");
    }
    
    $currentPlan = $result->fetch_assoc();
    $donHangId = $currentPlan['DonHangID'];
    $cbhId = $currentPlan['CBH_ID'];
    $check_stmt->close();
    
    if (empty($donHangId)) {
        throw new Exception("Kế hoạch giao hàng này không được liên kết với đơn hàng nào.");
    }
    
    if ($currentPlan['TrangThai'] !== 'Đã tạo phiếu chuẩn bị hàng') {
        throw new Exception("Kế hoạch này phải ở trạng thái 'Đã tạo phiếu chuẩn bị hàng' mới có thể gửi YCSX.");
    }

    if (empty($cbhId)) {
        throw new Exception("Không tìm thấy ID phiếu chuẩn bị hàng liên kết. Vui lòng đảm bảo phiếu đã được tạo.");
    }

    // --- BƯỚC 2: CẬP NHẬT TRẠNG THÁI CỦA PHIẾU CHUẨN BỊ HÀNG ---
    $newCbhStatus = 'Mới tạo'; // Theo yêu cầu của người dùng
    $update_cbh_stmt = $conn->prepare("UPDATE chuanbihang SET TrangThai = ? WHERE CBH_ID = ?");
    $update_cbh_stmt->bind_param("si", $newCbhStatus, $cbhId);
    if (!$update_cbh_stmt->execute()) {
        throw new Exception("Lỗi khi cập nhật trạng thái phiếu chuẩn bị hàng: " . $update_cbh_stmt->error);
    }
    $update_cbh_stmt->close();


    // --- BƯỚC 3: CẬP NHẬT TRẠNG THÁI CỦA ĐỢT GIAO HÀNG HIỆN TẠI ---
    $newKhghStatus = 'Đã gửi YCSX';
    $update_khgh_stmt = $conn->prepare("UPDATE kehoach_giaohang SET TrangThai = ? WHERE KHGH_ID = ?");
    $update_khgh_stmt->bind_param("si", $newKhghStatus, $khghId);
    if (!$update_khgh_stmt->execute()) {
        throw new Exception("Lỗi khi cập nhật trạng thái kế hoạch giao hàng: " . $update_khgh_stmt->error);
    }
    $update_khgh_stmt->close();

    // --- BƯỚC 4: TÍNH TOÁN VÀ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG CHÍNH ---
    
    // 4.1. Đếm tổng số đợt giao hàng của đơn hàng này (Loại trừ 'Kiểm tra tiến độ' và 'Ẩn')
    $stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM kehoach_giaohang WHERE DonHangID = ? AND TrangThai NOT IN ('Kiểm tra tiến độ', 'Ẩn')");
    $stmt_total->bind_param("i", $donHangId);
    $stmt_total->execute();
    $total_result = $stmt_total->get_result()->fetch_assoc();
    $total_batches = $total_result['total'];
    $stmt_total->close();

    // 4.2. Đếm số đợt CHƯA gửi (chỉ tính 'Chờ xử lý' hoặc 'Đã tạo phiếu chuẩn bị hàng')
    $stmt_not_sent = $conn->prepare("SELECT COUNT(*) as not_sent_count FROM kehoach_giaohang WHERE DonHangID = ? AND (TrangThai = 'Chờ xử lý' OR TrangThai = 'Đã tạo phiếu chuẩn bị hàng')");
    $stmt_not_sent->bind_param("i", $donHangId);
    $stmt_not_sent->execute();
    $not_sent_result = $stmt_not_sent->get_result()->fetch_assoc();
    $not_sent_batches = $not_sent_result['not_sent_count'];
    $stmt_not_sent->close();

    // 4.3. Số đợt đã gửi là tổng số trừ đi số chưa gửi
    $sent_batches = $total_batches - $not_sent_batches;

    $newDonHangStatus = "Chưa có kế hoạch giao hàng"; // Trạng thái mặc định

    // 4.4. Tạo chuỗi trạng thái đơn giản
    if ($total_batches > 0) {
        $newDonHangStatus = "Đã gửi YCSX ({$sent_batches}/{$total_batches})";
    }

    // 4.5. Cập nhật trạng thái tóm tắt vào đơn hàng chính
    $update_donhang_stmt = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE YCSX_ID = ?");
    $update_donhang_stmt->bind_param("si", $newDonHangStatus, $donHangId);

    if (!$update_donhang_stmt->execute()) {
        throw new Exception("Lỗi khi cập nhật trạng thái đơn hàng: " . $update_donhang_stmt->error);
    }
    $update_donhang_stmt->close();

    $conn->commit();
    echo json_encode([
        'success' => true, 
        'message' => "Đã gửi YCSX thành công. Các trạng thái đã được cập nhật."
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>


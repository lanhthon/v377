<?php
header('Content-Type: application/json');
require_once '../config/database.php'; // Đảm bảo đường dẫn này chính xác

global $conn;

// Kiểm tra kết nối cơ sở dữ liệu
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

try {
    // Cập nhật câu lệnh SQL để lấy đúng cột ID và tổng tiền
    $sql = "SELECT 
                d.YCSX_ID AS DonHangID, 
                d.SoYCSX, 
                d.TenCongTy, 
                d.NguoiBaoGia, 
                d.NgayTao, 
                d.NgayHoanThanhDuKien, 
                d.NgayGiaoDuKien, 
                b.TongTienSauThue AS TongTien, 
                d.TrangThai 
            FROM donhang AS d
            LEFT JOIN baogia AS b ON d.BaoGiaID = b.BaoGiaID
            ORDER BY d.NgayTao DESC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Lỗi truy vấn SQL: " . $conn->error);
    }

    $donhang_list = [];
    while ($row = $result->fetch_assoc()) {
        $donhang_list[] = $row;
    }

    // Trả về dữ liệu thành công dưới dạng JSON
    echo json_encode(['success' => true, 'donhang' => $donhang_list]);

} catch (Exception $e) {
    http_response_code(500);
    // Ghi lại lỗi để dễ dàng gỡ lỗi sau này
    error_log("Lỗi trong get_donhang.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi phía máy chủ: ' . $e->getMessage()]);
}

// Đóng kết nối
$conn->close();
?>
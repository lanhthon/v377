<?php
header('Content-Type: application/json');

require_once '../config/database.php'; // Đảm bảo đường dẫn này chính xác

global $conn;

if (!isset($conn) || !$conn instanceof mysqli || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

try {
    $sql = "SELECT
                dh.YCSX_ID,
                dh.SoYCSX,
                dh.NgayTao,
                dh.NgayGiaoDuKien,
                dh.NgayHoanThanhDuKien,
                dh.TrangThai,
                dh.TenCongTy,
                dh.TenDuAn,
                dh.NguoiBaoGia,
                bg.SoBaoGia,
                bg.TongTienSauThue,
                bg.BaoGiaID
            FROM
                donhang dh
            LEFT JOIN
                baogia bg ON dh.BaoGiaID = bg.BaoGiaID
            ORDER BY
                dh.NgayTao DESC, dh.YCSX_ID DESC";

    $result = $conn->query($sql);

    $donhang_list = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $donhang_list[] = $row;
        }
    }

    echo json_encode(['success' => true, 'data' => $donhang_list]);

} catch (Exception $e) {
    error_log("Lỗi trong get_donhang_list.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi lấy danh sách đơn hàng: ' . $e->getMessage()]);
}
?>
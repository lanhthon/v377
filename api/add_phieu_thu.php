
/* ========================================
   FILE: api/add_phieu_thu.php
   ======================================== */
<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        $response['message'] = 'Chưa đăng nhập';
        echo json_encode($response);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    $ngayThu = $input['NgayThu'] ?? date('Y-m-d');
    $loaiDoiTuong = $input['LoaiDoiTuong'] ?? 'khachhang';
    $tenDoiTuong = $input['TenDoiTuong'] ?? '';
    $diaChiDoiTuong = $input['DiaChiDoiTuong'] ?? null;
    $lyDoThu = $input['LyDoThu'] ?? '';
    $soTien = floatval($input['SoTien'] ?? 0);
    $hinhThucTT = $input['HinhThucThanhToan'] ?? 'tien_mat';
    $soTaiKhoan = $input['SoTaiKhoan'] ?? null;
    $nganHang = $input['NganHang'] ?? null;
    $nguoiNop = $input['NguoiNop'] ?? null;
    $dienThoaiNguoiNop = $input['DienThoaiNguoiNop'] ?? null;
    $ghiChu = $input['GhiChu'] ?? null;
    $nguoiLap = $_SESSION['user_id'];

    if (empty($tenDoiTuong) || empty($lyDoThu) || $soTien <= 0) {
        $response['message'] = 'Dữ liệu không hợp lệ';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();

    // Tạo số phiếu thu tự động
    $dateStr = date('Ymd', strtotime($ngayThu));
    $sqlCount = "SELECT COUNT(*) as total FROM phieu_thu WHERE DATE(NgayThu) = ?";
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->bind_param('s', $ngayThu);
    $stmtCount->execute();
    $resultCount = $stmtCount->get_result();
    $rowCount = $resultCount->fetch_assoc();
    $nextNumber = intval($rowCount['total']) + 1;
    
    $soPhieuThu = sprintf("PT-%s-%04d", $dateStr, $nextNumber);
    $stmtCount->close();

    // Insert phiếu thu
    $sql = "INSERT INTO phieu_thu (
                SoPhieuThu, NgayThu, LoaiDoiTuong, TenDoiTuong, DiaChiDoiTuong,
                LyDoThu, SoTien, HinhThucThanhToan, SoTaiKhoan, NganHang,
                NguoiNop, DienThoaiNguoiNop, NguoiLap, GhiChu
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'ssssssdsssssis',
        $soPhieuThu, $ngayThu, $loaiDoiTuong, $tenDoiTuong, $diaChiDoiTuong,
        $lyDoThu, $soTien, $hinhThucTT, $soTaiKhoan, $nganHang,
        $nguoiNop, $dienThoaiNguoiNop, $nguoiLap, $ghiChu
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi thêm phiếu thu: ' . $stmt->error);
    }

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Thêm phiếu thu thành công';

    $stmt->close();

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

if (isset($conn)) {
    $conn->close();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

?>

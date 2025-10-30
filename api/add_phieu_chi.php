<?php
/* ========================================
   FILE: api/add_phieu_chi.php
   ======================================== */
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
    
    $ngayChi = $input['NgayChi'] ?? date('Y-m-d');
    $loaiDoiTuong = $input['LoaiDoiTuong'] ?? 'nhacungcap';
    $tenDoiTuong = $input['TenDoiTuong'] ?? '';
    $diaChiDoiTuong = $input['DiaChiDoiTuong'] ?? null;
    $lyDoChi = $input['LyDoChi'] ?? '';
    $loaiChiPhi = $input['LoaiChiPhi'] ?? 'KHAC';
    $soTien = floatval($input['SoTien'] ?? 0);
    $hinhThucTT = $input['HinhThucThanhToan'] ?? 'tien_mat';
    $soTaiKhoan = $input['SoTaiKhoan'] ?? null;
    $nganHang = $input['NganHang'] ?? null;
    $nguoiNhan = $input['NguoiNhan'] ?? null;
    $dienThoaiNguoiNhan = $input['DienThoaiNguoiNhan'] ?? null;
    $ghiChu = $input['GhiChu'] ?? null;
    $nguoiLap = $_SESSION['user_id'];

    if (empty($tenDoiTuong) || empty($lyDoChi) || $soTien <= 0) {
        $response['message'] = 'Dữ liệu không hợp lệ';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();

    // Tạo số phiếu chi
    $dateStr = date('Ymd', strtotime($ngayChi));
    $sqlCount = "SELECT COUNT(*) as total FROM phieu_chi WHERE DATE(NgayChi) = ?";
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->bind_param('s', $ngayChi);
    $stmtCount->execute();
    $resultCount = $stmtCount->get_result();
    $rowCount = $resultCount->fetch_assoc();
    $nextNumber = intval($rowCount['total']) + 1;
    
    $soPhieuChi = sprintf("PC-%s-%04d", $dateStr, $nextNumber);
    $stmtCount->close();

    $sql = "INSERT INTO phieu_chi (
                SoPhieuChi, NgayChi, LoaiDoiTuong, TenDoiTuong, DiaChiDoiTuong,
                LyDoChi, LoaiChiPhi, SoTien, HinhThucThanhToan, SoTaiKhoan, NganHang,
                NguoiNhan, DienThoaiNguoiNhan, NguoiLap, GhiChu
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'sssssssdssssis',
        $soPhieuChi, $ngayChi, $loaiDoiTuong, $tenDoiTuong, $diaChiDoiTuong,
        $lyDoChi, $loaiChiPhi, $soTien, $hinhThucTT, $soTaiKhoan, $nganHang,
        $nguoiNhan, $dienThoaiNguoiNhan, $nguoiLap, $ghiChu
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi thêm phiếu chi: ' . $stmt->error);
    }

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Thêm phiếu chi thành công';

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
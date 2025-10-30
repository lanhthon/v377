<?php
/**
 * File: api/add_so_quy.php
 * Description: Thêm giao dịch vào sổ quỹ
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        $response['message'] = 'Chưa đăng nhập';
        echo json_encode($response);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    $loaiGiaoDich = $input['LoaiGiaoDich'] ?? '';
    $ngayGhiSo = $input['NgayGhiSo'] ?? date('Y-m-d');
    $noiDung = $input['NoiDung'] ?? '';
    $doiTuong = $input['DoiTuong'] ?? null;
    $loaiDoiTuong = $input['LoaiDoiTuong'] ?? 'khac';
    $soTien = floatval($input['SoTien'] ?? 0);
    $ghiChu = $input['GhiChu'] ?? null;
    $nguoiLap = $_SESSION['user_id'];

    if (empty($loaiGiaoDich) || empty($noiDung) || $soTien <= 0) {
        $response['message'] = 'Dữ liệu không hợp lệ';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();

    // Lấy số dư hiện tại
    $sqlSoDu = "SELECT SoDu FROM so_quy ORDER BY NgayGhiSo DESC, SoQuyID DESC LIMIT 1";
    $resultSoDu = $conn->query($sqlSoDu);
    $soDuHienTai = 0;
    
    if ($resultSoDu && $resultSoDu->num_rows > 0) {
        $rowSoDu = $resultSoDu->fetch_assoc();
        $soDuHienTai = floatval($rowSoDu['SoDu']);
    }

    // Tính số dư mới
    $soTienThu = ($loaiGiaoDich === 'thu') ? $soTien : 0;
    $soTienChi = ($loaiGiaoDich === 'chi') ? $soTien : 0;
    $soDuMoi = $soDuHienTai + $soTienThu - $soTienChi;

    // Tạo số chứng từ tự động
    $prefix = ($loaiGiaoDich === 'thu') ? 'PT' : 'PC';
    $dateStr = date('Ymd', strtotime($ngayGhiSo));
    
    $sqlCount = "SELECT COUNT(*) as total FROM so_quy 
                 WHERE LoaiGiaoDich = ? AND DATE(NgayGhiSo) = ?";
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->bind_param('ss', $loaiGiaoDich, $ngayGhiSo);
    $stmtCount->execute();
    $resultCount = $stmtCount->get_result();
    $rowCount = $resultCount->fetch_assoc();
    $nextNumber = intval($rowCount['total']) + 1;
    
    $soChungTu = sprintf("%s-%s-%04d", $prefix, $dateStr, $nextNumber);
    $stmtCount->close();

    // Insert giao dịch
    $sql = "INSERT INTO so_quy (
                NgayGhiSo, LoaiGiaoDich, SoChungTu, NoiDung, 
                DoiTuong, LoaiDoiTuong, SoTienThu, SoTienChi, 
                SoDu, NguoiLap, GhiChu
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'ssssssdddis',
        $ngayGhiSo, $loaiGiaoDich, $soChungTu, $noiDung,
        $doiTuong, $loaiDoiTuong, $soTienThu, $soTienChi,
        $soDuMoi, $nguoiLap, $ghiChu
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi thêm giao dịch: ' . $stmt->error);
    }

    $newId = $conn->insert_id;

    // Lấy dữ liệu vừa thêm
    $sqlGet = "SELECT * FROM so_quy WHERE SoQuyID = ?";
    $stmtGet = $conn->prepare($sqlGet);
    $stmtGet->bind_param('i', $newId);
    $stmtGet->execute();
    $resultGet = $stmtGet->get_result();
    $newData = $resultGet->fetch_assoc();

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Thêm giao dịch thành công';
    $response['data'] = $newData;

    $stmt->close();
    $stmtGet->close();

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

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
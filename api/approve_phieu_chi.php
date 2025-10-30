
<?php
/* ========================================
   FILE: api/approve_phieu_chi.php
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
    $id = intval($input['id'] ?? 0);
    $nguoiDuyet = $_SESSION['user_id'];

    if ($id <= 0) {
        $response['message'] = 'ID không hợp lệ';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();

    $sql = "UPDATE phieu_chi 
            SET TrangThai = 'da_duyet', 
                NguoiDuyet = ?, 
                NgayDuyet = NOW() 
            WHERE PhieuChiID = ? AND TrangThai = 'cho_duyet'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $nguoiDuyet, $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi duyệt: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('Không thể duyệt phiếu chi này');
    }

    // Lấy thông tin phiếu chi
    $sqlGet = "SELECT * FROM phieu_chi WHERE PhieuChiID = ?";
    $stmtGet = $conn->prepare($sqlGet);
    $stmtGet->bind_param('i', $id);
    $stmtGet->execute();
    $result = $stmtGet->get_result();
    $phieuChi = $result->fetch_assoc();

    // Tự động ghi vào sổ quỹ
    $sqlSoQuy = "INSERT INTO so_quy (
                    NgayGhiSo, LoaiGiaoDich, SoChungTu, NoiDung,
                    DoiTuong, LoaiDoiTuong, SoTienThu, SoTienChi, SoDu, NguoiLap
                ) 
                SELECT 
                    ?, 'chi', ?, ?,
                    ?, ?, 0, ?,
                    (SELECT COALESCE(SoDu, 0) FROM so_quy ORDER BY NgayGhiSo DESC, SoQuyID DESC LIMIT 1) - ?,
                    ?";
    
    $stmtSQ = $conn->prepare($sqlSoQuy);
    $stmtSQ->bind_param(
        'sssssdddi',
        $phieuChi['NgayChi'],
        $phieuChi['SoPhieuChi'],
        $phieuChi['LyDoChi'],
        $phieuChi['TenDoiTuong'],
        $phieuChi['LoaiDoiTuong'],
        $phieuChi['SoTien'],
        $phieuChi['SoTien'],
        $nguoiDuyet
    );
    $stmtSQ->execute();

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Duyệt phiếu chi thành công';

    $stmt->close();
    $stmtGet->close();
    $stmtSQ->close();

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
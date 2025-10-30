<?php
/* ========================================
   FILE: api/get_loai_chi_phi.php
   ======================================== */
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        $response['message'] = 'Chưa đăng nhập';
        echo json_encode($response);
        exit;
    }

    // Kiểm tra bảng có tồn tại không
    $checkTable = $conn->query("SHOW TABLES LIKE 'loai_chi_phi'");
    
    if ($checkTable->num_rows === 0) {
        // Nếu bảng không tồn tại, lấy từ phiếu chi
        $sql = "SELECT DISTINCT LoaiChiPhi 
                FROM phieu_chi 
                WHERE LoaiChiPhi IS NOT NULL 
                AND LoaiChiPhi != '' 
                ORDER BY LoaiChiPhi ASC";

        $result = $conn->query($sql);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'MaLoaiCP' => $row['LoaiChiPhi'],
                'TenLoaiCP' => $row['LoaiChiPhi']
            ];
        }
    } else {
        // Nếu bảng tồn tại, lấy từ bảng loai_chi_phi
        $sql = "SELECT 
                    LoaiChiPhiID,
                    MaLoaiCP,
                    TenLoaiCP,
                    MoTa,
                    TrangThai
                FROM loai_chi_phi
                WHERE TrangThai = 1
                ORDER BY TenLoaiCP ASC";

        $result = $conn->query($sql);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    $response['success'] = true;
    $response['data'] = $data;
    $response['total'] = count($data);

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
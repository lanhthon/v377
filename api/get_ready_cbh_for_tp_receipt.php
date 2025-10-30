<?php
/**
 * =================================================================================
 * API: LẤY DANH SÁCH PHIẾU CHUẨN BỊ HÀNG CHỜ NHẬP KHO THÀNH PHẨM
 * =================================================================================
 * - [FIX] Cập nhật logic SQL để chỉ lấy các phiếu có trạng thái chính xác là
 * 'Đã xuất kho BTP' (cho PUR) hoặc trạng thái ULA là 'Chờ nhập ULA'.
 * =================================================================================
 */
require_once '../config/db_config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $pdo = get_db_connection();
    
    // Cập nhật câu lệnh SQL để lấy đúng trạng thái
    $sql = "SELECT 
                cbh.CBH_ID,
                dh.SoYCSX,
                cbh.TenCongTy,
                dh.TenDuAn,
                dh.NgayGiaoDuKien,
                cbh.TrangThai,
                cbh.TrangThaiULA
            FROM chuanbihang cbh
            JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
            WHERE 
                -- Chỉ lấy các phiếu có trạng thái là 'Đã xuất kho BTP' (dành cho PUR)
                -- hoặc có trạng thái ULA là 'Chờ nhập ULA'
                cbh.TrangThai = 'Đã xuất kho BTP' OR 
                cbh.TrangThaiULA = 'Chờ nhập ULA'
            ORDER BY dh.NgayGiaoDuKien ASC, cbh.CBH_ID DESC";

    $stmt = $pdo->query($sql);
    $all_cbh = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $final_data = [];
    foreach ($all_cbh as $cbh) {
        $base_data = [
            'CBH_ID' => $cbh['CBH_ID'],
            'SoYCSX' => $cbh['SoYCSX'],
            'TenCongTy' => $cbh['TenCongTy'],
            'TenDuAn' => $cbh['TenDuAn'],
            'NgayGiaoDuKien' => $cbh['NgayGiaoDuKien']
        ];
        
        // Nếu trạng thái tổng là 'Đã xuất kho BTP', tạo dòng cho PUR
        if ($cbh['TrangThai'] === 'Đã xuất kho BTP') {
            $pur_data = $base_data;
            $pur_data['type'] = 'pur';
            $pur_data['description'] = 'Nhập kho thành phẩm PUR';
            $final_data[] = $pur_data;
        }

        // Nếu trạng thái ULA là 'Chờ nhập ULA', tạo dòng cho ULA
        if ($cbh['TrangThaiULA'] === 'Chờ nhập ULA') {
            $ula_data = $base_data;
            $ula_data['type'] = 'ula';
            $ula_data['description'] = 'Nhập kho thành phẩm ULA & Ecu';
            $final_data[] = $ula_data;
        }
    }

    $response['success'] = true;
    $response['data'] = $final_data;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
}

echo json_encode($response);
?>

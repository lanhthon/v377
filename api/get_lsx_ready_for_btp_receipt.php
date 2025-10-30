<?php
// File: api/get_lsx_ready_for_btp_receipt.php
// CẬP NHẬT: Lọc các lệnh sản xuất BTP đã hoàn thành và chưa được nhập kho.

require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'data' => []];

try {
    $pdo = get_db_connection();
    
    // Câu lệnh SQL được cập nhật với logic lọc mới
    $sql = "SELECT 
                lsx.LenhSX_ID,
                lsx.SoLenhSX,
                lsx.NgayTao,
                lsx.NgayHoanThanhThucTe,
                lsx.CBH_ID,
                dh.SoYCSX
            FROM lenh_san_xuat lsx
            LEFT JOIN donhang dh ON lsx.YCSX_ID = dh.YCSX_ID
            -- THAY ĐỔI 1: LEFT JOIN với bảng phiếu nhập kho để kiểm tra sự tồn tại
            LEFT JOIN phieunhapkho_btp pnk ON lsx.LenhSX_ID = pnk.LenhSX_ID
            WHERE 
                lsx.LoaiLSX = 'BTP'
                -- THAY ĐỔI 2: Chỉ lấy LSX có trạng thái 'Hoàn thành'
                AND lsx.TrangThai = 'Hoàn thành' 
                -- THAY ĐỔI 3: Chỉ lấy những LSX chưa có phiếu nhập kho tương ứng
                AND pnk.PNK_BTP_ID IS NULL 
            ORDER BY lsx.LenhSX_ID DESC";
            
    $stmt = $pdo->query($sql);
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
    error_log("Lỗi trong get_lsx_ready_for_btp_receipt.php: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
<?php
// File: api/get_lsx_id_from_cbh_id.php
require_once '../config/db_config.php';

header('Content-Type: application/json');

$cbh_id = isset($_GET['cbh_id']) ? intval($_GET['cbh_id']) : 0;
$response = ['success' => false, 'lsx_id' => null, 'message' => ''];

if ($cbh_id === 0) {
    $response['message'] = 'ID Phiếu Chuẩn Bị Hàng không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $pdo = get_db_connection();

    // Sửa câu lệnh SQL để tìm kiếm theo CBH_ID
    $sql = "SELECT LenhSX_ID FROM lenh_san_xuat WHERE LoaiLSX = 'BTP' AND CBH_ID = :cbh_id ORDER BY created_at DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cbh_id' => $cbh_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $response['success'] = true;
        $response['lsx_id'] = intval($result['LenhSX_ID']);
    } else {
        $response['message'] = 'Không tìm thấy Lệnh Sản Xuất BTP nào cho phiếu này.';
    }

} catch (PDOException $e) {
    $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
}

echo json_encode($response);
?>
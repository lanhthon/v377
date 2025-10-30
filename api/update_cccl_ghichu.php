<?php
// File: api/update_cccl_ghichu.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db_config.php';

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['cccl_id']) || empty($data['items'])) {
    $response['message'] = 'Dữ liệu không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    $sql = "UPDATE chitiet_chungchi_chatluong SET GhiChuChiTiet = :ghi_chu WHERE ChiTietCCCL_ID = :id";
    $stmt = $pdo->prepare($sql);

    foreach ($data['items'] as $item) {
        $stmt->execute([
            ':ghi_chu' => $item['ghiChu'],
            ':id' => $item['id']
        ]);
    }

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Cập nhật thành công!';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Lỗi server: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
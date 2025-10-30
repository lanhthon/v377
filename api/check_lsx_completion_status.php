<?php
/**
 * File: api/check_lsx_completion_status.php
 * Version: 1.0
 * Description: API kiểm tra trạng thái hoàn thành của các LSX liên quan đến CBH
 * Returns: { btpCompleted: boolean, ulaCompleted: boolean, details: {...} }
 */

require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$cbh_id = isset($_GET['cbh_id']) ? intval($_GET['cbh_id']) : 0;

if ($cbh_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'CBH ID không hợp lệ']);
    exit;
}

try {
    $pdo = get_db_connection();
    
    // Kiểm tra LSX BTP
    $stmt_btp = $pdo->prepare("
        SELECT LenhSX_ID, SoLenhSX, TrangThai, NgayHoanThanhThucTe
        FROM lenh_san_xuat
        WHERE CBH_ID = ? AND LoaiLSX = 'BTP'
        ORDER BY NgayTao DESC
        LIMIT 1
    ");
    $stmt_btp->execute([$cbh_id]);
    $btpLSX = $stmt_btp->fetch(PDO::FETCH_ASSOC);
    
    // Kiểm tra LSX ULA
    $stmt_ula = $pdo->prepare("
        SELECT LenhSX_ID, SoLenhSX, TrangThai, NgayHoanThanhThucTe
        FROM lenh_san_xuat
        WHERE CBH_ID = ? AND LoaiLSX = 'ULA'
        ORDER BY NgayTao DESC
        LIMIT 1
    ");
    $stmt_ula->execute([$cbh_id]);
    $ulaLSX = $stmt_ula->fetch(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'btpCompleted' => $btpLSX ? ($btpLSX['TrangThai'] === 'Hoàn thành') : null,
        'ulaCompleted' => $ulaLSX ? ($ulaLSX['TrangThai'] === 'Hoàn thành') : null,
        'details' => [
            'btp' => $btpLSX ?: ['exists' => false],
            'ula' => $ulaLSX ?: ['exists' => false]
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("ERROR check_lsx_completion_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
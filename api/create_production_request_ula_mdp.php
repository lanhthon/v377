<?php
/**
 * File: api/create_production_request_ula_mdp.php
 * Version: 1.0
 * Description: Tạo yêu cầu sản xuất ULA mạ điện phân khi thiếu hàng để gia công
 *
 * Chức năng:
 * - Tạo lệnh sản xuất ULA mạ điện phân
 * - Lưu thông tin để tracking
 * - Liên kết với phiếu CBH
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

// Nhận dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['cbh_id']) || !isset($input['chi_tiet_cbh_id']) || !isset($input['variant_id_mdp']) || !isset($input['so_luong'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin: cbh_id, chi_tiet_cbh_id, variant_id_mdp, so_luong'
    ]);
    exit;
}

$cbhId = intval($input['cbh_id']);
$chiTietCbhId = intval($input['chi_tiet_cbh_id']);
$variantIdMdp = intval($input['variant_id_mdp']);
$soLuong = intval($input['so_luong']);
$nguoiTao = $input['nguoi_tao'] ?? 'Hệ thống';
$ghiChu = $input['ghi_chu'] ?? '';

if ($soLuong <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Số lượng phải lớn hơn 0'
    ]);
    exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // 1. Lấy thông tin sản phẩm ULA mạ điện phân
    $sql_get_product = "
        SELECT v.variant_id, v.variant_sku, v.variant_name, v.product_id
        FROM variants v
        WHERE v.variant_id = :variantId
    ";

    $stmt = $pdo->prepare($sql_get_product);
    $stmt->execute([':variantId' => $variantIdMdp]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Không tìm thấy sản phẩm ULA mạ điện phân');
    }

    // 2. Kiểm tra xem đã có yêu cầu sản xuất cho phiếu CBH này chưa
    $sql_check_existing = "
        SELECT LenhSX_ID, SoLenhSX, TrangThai
        FROM lenh_san_xuat
        WHERE CBH_ID = :cbhId
        AND LoaiLenh = 'ULA-MDP-GC'
        AND TrangThai NOT IN ('Đã hủy', 'Hoàn thành')
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql_check_existing);
    $stmt->execute([':cbhId' => $cbhId]);
    $existingLenh = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingLenh) {
        throw new Exception(sprintf(
            'Đã tồn tại yêu cầu sản xuất ULA MĐP: %s (Trạng thái: %s)',
            $existingLenh['SoLenhSX'],
            $existingLenh['TrangThai']
        ));
    }

    // 3. Tạo số lệnh sản xuất
    $soLenhSX = 'LSX-ULA-MDP-' . $cbhId . '-' . time();

    // 4. Tạo lệnh sản xuất
    $sql_insert_lenh = "
        INSERT INTO lenh_san_xuat (
            CBH_ID, SoLenhSX, NgayTao, LoaiLenh,
            TrangThai, NguoiTao, GhiChu
        ) VALUES (
            :cbhId, :soLenhSX, NOW(), 'ULA-MDP-GC',
            'Chờ duyệt', :nguoiTao, :ghiChu
        )
    ";

    $stmt = $pdo->prepare($sql_insert_lenh);
    $stmt->execute([
        ':cbhId' => $cbhId,
        ':soLenhSX' => $soLenhSX,
        ':nguoiTao' => $nguoiTao,
        ':ghiChu' => $ghiChu ?: "Yêu cầu sản xuất ULA mạ điện phân để gia công mạ nhúng nóng"
    ]);

    $lenhSxId = $pdo->lastInsertId();

    // 5. Thêm chi tiết lệnh sản xuất
    $sql_insert_chitiet = "
        INSERT INTO chitiet_lenh_san_xuat (
            LenhSX_ID, SanPhamID, SoLuongBoCanSX,
            SoLuongCayCanSX, SoLuongCayTuongDuong,
            TrangThai, GhiChu
        ) VALUES (
            :lenhSxId, :sanPhamId, :soLuong,
            0, 0,
            'Mới', :ghiChu
        )
    ";

    $stmt = $pdo->prepare($sql_insert_chitiet);
    $stmt->execute([
        ':lenhSxId' => $lenhSxId,
        ':sanPhamId' => $variantIdMdp,
        ':soLuong' => $soLuong,
        ':ghiChu' => "Sản xuất để chuẩn bị gia công mạ nhúng nóng"
    ]);

    $chiTietLsxId = $pdo->lastInsertId();

    // 6. Cập nhật ghi chú trong chitietchuanbihang
    $sql_update_cbh = "
        UPDATE chitietchuanbihang
        SET GhiChu = CONCAT(COALESCE(GhiChu, ''), '\n[SX-ULA-MDP] Đã tạo yêu cầu sản xuất: ', :soLenhSX),
            updated_at = NOW()
        WHERE ChiTietCBH_ID = :chiTietCbhId
    ";

    $stmt = $pdo->prepare($sql_update_cbh);
    $stmt->execute([
        ':soLenhSX' => $soLenhSX,
        ':chiTietCbhId' => $chiTietCbhId
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Đã tạo yêu cầu sản xuất ULA mạ điện phân thành công',
        'data' => [
            'lenh_sx_id' => $lenhSxId,
            'so_lenh_sx' => $soLenhSX,
            'chi_tiet_lsx_id' => $chiTietLsxId,
            'san_pham' => [
                'id' => $product['variant_id'],
                'sku' => $product['variant_sku'],
                'ten' => $product['variant_name']
            ],
            'so_luong' => $soLuong,
            'trang_thai' => 'Chờ duyệt',
            'ngay_tao' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ERROR in create_production_request_ula_mdp.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>

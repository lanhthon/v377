<?php
/**
 * File: api/update_phieu_chuan_bi.php
 * Description: Cập nhật thông tin chi tiết cho một Phiếu Chuẩn Bị Hàng đã tồn tại.
 * Version: 2.0 - Thêm xử lý SĐT người nhận.
 */
header('Content-Type: application/json; charset=utf-8');

// Bật hiển thị lỗi để dễ dàng debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Sử dụng file cấu hình PDO
require_once '../config/db_config.php';

// --- Lấy dữ liệu JSON từ request body ---
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ hoặc không phải là JSON.']);
    exit;
}

// Lấy ID của phiếu cần cập nhật
$cbh_id = isset($input['cbhID']) ? intval($input['cbhID']) : 0;
if ($cbh_id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Phiếu Chuẩn Bị Hàng không hợp lệ.']);
    exit;
}

// Lấy dữ liệu cần cập nhật
$thongTinChung = $input['thongTinChung'] ?? [];
$items = $input['items'] ?? [];
$itemsEcuKemTheo = $input['itemsEcuKemTheo'] ?? [];


try {
    // Sử dụng PDO connection từ file config
    $pdo = get_db_connection();

    // Bắt đầu transaction
    $pdo->beginTransaction();

    // 1. Cập nhật thông tin chung trong bảng `chuanbihang` (nếu có)
    if (!empty($thongTinChung)) {
        // Đây là những trường cho phép cập nhật. Bạn có thể thêm/bớt nếu cần.
        $allowed_fields = [
            'BoPhan', 'NgayGuiYCSX', 'PhuTrach', 'NgayGiao', 'NguoiNhanHang', 
            'SdtNguoiNhan', 'DiaDiemGiaoHang', 'QuyCachThung', 'XeGrap', 'XeTai', 'SoLaiXe', 'DangKiCongTruong'
        ];
        
        $sql_parts = [];
        $params = [':CBH_ID' => $cbh_id];

        foreach ($allowed_fields as $field) {
            // Chuyển đổi key từ camelCase (JS) sang PascalCase (PHP/DB)
            // Ví dụ: sdtNguoiNhan (JS) -> SdtNguoiNhan (DB Column)
            $jsKey = lcfirst(str_replace('_', '', ucwords($field, '_')));
             if (array_key_exists($jsKey, $thongTinChung)) {
                 $sql_parts[] = "$field = :$field";
                 // Xử lý giá trị rỗng thành NULL
                 $params[":$field"] = ($thongTinChung[$jsKey] === '') ? null : $thongTinChung[$jsKey];
            }
        }
        
        if (!empty($sql_parts)) {
            $sql_update_cbh = "UPDATE chuanbihang SET " . implode(', ', $sql_parts) . " WHERE CBH_ID = :CBH_ID";
            $stmt_cbh = $pdo->prepare($sql_update_cbh);
            $stmt_cbh->execute($params);
        }
    }

    // 2. Cập nhật chi tiết các sản phẩm (PUR, ULA) trong bảng `chitietchuanbihang`
    if (!empty($items)) {
        $sql_update_item = "
            UPDATE chitietchuanbihang 
            SET SoLuongLayTuKho = :soLuongLayTuKho, CayCat = :cayCat, DongGoi = :dongGoi, GhiChu = :ghiChu
            WHERE ChiTietCBH_ID = :chiTietCBH_ID
        ";
        $stmt_item = $pdo->prepare($sql_update_item);

        foreach ($items as $item) {
            if (!empty($item['chiTietCBH_ID'])) {
                $stmt_item->execute([
                    ':soLuongLayTuKho' => $item['soLuongLayTuKho'] ?? 0,
                    ':cayCat' => $item['cayCat'] ?? null,
                    ':dongGoi' => $item['dongGoi'] ?? null,
                    ':ghiChu' => $item['ghiChu'] ?? null,
                    ':chiTietCBH_ID' => $item['chiTietCBH_ID']
                ]);
            }
        }
    }

    // 3. Cập nhật chi tiết các vật tư kèm theo (ECU) trong bảng `chitiet_ecu_cbh`
    if (!empty($itemsEcuKemTheo)) {
        $sql_update_ecu = "
            UPDATE chitiet_ecu_cbh 
            SET DongGoiEcu = :dongGoiEcu, GhiChuEcu = :ghiChuEcu
            WHERE ChiTietEcuCBH_ID = :chiTietEcuCBH_ID
        ";
        $stmt_ecu = $pdo->prepare($sql_update_ecu);

        foreach ($itemsEcuKemTheo as $itemEcu) {
             if (!empty($itemEcu['chiTietEcuCBH_ID'])) {
                $stmt_ecu->execute([
                    ':dongGoiEcu' => $itemEcu['dongGoiEcu'] ?? null,
                    ':ghiChuEcu' => $itemEcu['ghiChuEcu'] ?? null,
                    ':chiTietEcuCBH_ID' => $itemEcu['chiTietEcuCBH_ID']
                ]);
            }
        }
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Cập nhật phiếu chuẩn bị hàng thành công!']);

} catch (Exception $e) {
    // Nếu có lỗi, rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>

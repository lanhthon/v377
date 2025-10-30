<?php
// File: api/update_bbgh.php
// File này thay thế cho update_bbgh_sothung.php, có khả năng cập nhật cả thông tin chung và chi tiết sản phẩm.

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db_config.php';

$response = ['success' => false, 'message' => ''];

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $bbgh_id = $input['bbgh_id'] ?? 0;
    $header = $input['header'] ?? [];
    $items = $input['items'] ?? [];

    if (empty($bbgh_id) || (empty($header) && empty($items))) {
        throw new Exception('Dữ liệu đầu vào không hợp lệ.');
    }

    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // 1. Cập nhật thông tin chung vào bảng 'bienbangiaohang'
    if (!empty($header)) {
        // Lưu ý: Cần thêm cột NguoiGiaoHang và SdtNguoiGiaoHang vào bảng `bienbangiaohang`
        // ALTER TABLE `bienbangiaohang` ADD `NguoiGiaoHang` VARCHAR(100) NULL, ADD `SdtNguoiGiaoHang` VARCHAR(50) NULL;
        $sql_header = "UPDATE bienbangiaohang SET 
            TenCongTy = :ten_cong_ty,
            DiaChiGiaoHang = :dia_chi_giao_hang,
            NguoiNhanHang = :nguoi_nhan_hang,
            SoDienThoaiNhanHang = :sdt_nhan_hang,
            DuAn = :du_an,
            GhiChu = :ghi_chu,
            NguoiGiaoHang = :nguoi_giao,
            SdtNguoiGiaoHang = :sdt_giao
            WHERE BBGH_ID = :bbgh_id";
        
        $stmt_header = $pdo->prepare($sql_header);
        $stmt_header->execute([
            ':ten_cong_ty' => $header['tenCongTy'] ?? null,
            ':dia_chi_giao_hang' => $header['diaChiGiaoHang'] ?? null,
            ':nguoi_nhan_hang' => $header['nguoiNhan'] ?? null,
            ':sdt_nhan_hang' => $header['sdtNhan'] ?? null,
            ':du_an' => $header['duAn'] ?? null,
            ':ghi_chu' => $header['ghiChuChung'] ?? null,
            ':nguoi_giao' => $header['nguoiGiao'] ?? null,
            ':sdt_giao' => $header['sdtGiao'] ?? null,
            ':bbgh_id' => $bbgh_id
        ]);
    }

    // 2. Cập nhật thông tin chi tiết sản phẩm trong 'chitietbienbangiaohang'
    if (!empty($items)) {
        $sql_items = "UPDATE chitietbienbangiaohang SET SoThung = :so_thung, GhiChu = :ghi_chu WHERE ChiTietBBGH_ID = :id";
        $stmt_items = $pdo->prepare($sql_items);

        foreach ($items as $item) {
            $stmt_items->execute([
                ':so_thung' => $item['soThung'] ?? '',
                ':ghi_chu' => $item['ghiChu'] ?? '',
                ':id' => $item['id']
            ]);
        }
    }
    
    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Cập nhật biên bản giao hàng thành công!';

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = "Lỗi server: " . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

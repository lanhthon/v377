<?php
// File: api/update_cccl.php
// API mới để cập nhật toàn bộ thông tin Chứng chỉ chất lượng

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db_config.php';

$response = ['success' => false, 'message' => ''];

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Dữ liệu JSON không hợp lệ.');
    }

    $cccl_id = $input['cccl_id'] ?? 0;
    if (empty($cccl_id)) {
        throw new Exception('ID Chứng chỉ chất lượng không hợp lệ.');
    }

    // Dữ liệu header từ form
    $tenCongTy = $input['tenCongTy'] ?? '';
    $diaChiKhach = $input['diaChiKhach'] ?? '';
    $tenDuAn = $input['tenDuAn'] ?? '';
    $diaChiDuAn = $input['diaChiDuAn'] ?? '';
    $sanPham = $input['sanPham'] ?? '';
    $nguoiKiemTra = $input['nguoiKiemTra'] ?? '';
    $ngayCap = $input['ngayCap'] ?? '';

    // Dữ liệu chi tiết sản phẩm
    $items = $input['items'] ?? [];

    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // 1. Cập nhật thông tin chung trong bảng 'chungchi_chatluong'
    $sql_header = "UPDATE chungchi_chatluong SET 
        TenCongTyKhach = :ten_cong_ty,
        DiaChiKhach = :dia_chi_khach,
        TenDuAn = :ten_du_an,
        DiaChiDuAn = :dia_chi_du_an,
        SanPham = :san_pham,
        NguoiKiemTra = :nguoi_kiem_tra,
        NgayCap = :ngay_cap
        WHERE CCCL_ID = :cccl_id";
    
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([
        ':ten_cong_ty' => $tenCongTy,
        ':dia_chi_khach' => $diaChiKhach,
        ':ten_du_an' => $tenDuAn,
        ':dia_chi_du_an' => $diaChiDuAn,
        ':san_pham' => $sanPham,
        ':nguoi_kiem_tra' => $nguoiKiemTra,
        ':ngay_cap' => $ngayCap,
        ':cccl_id' => $cccl_id
    ]);

    // 2. Cập nhật chi tiết sản phẩm trong 'chitiet_chungchi_chatluong'
    if (!empty($items)) {
        $sql_items = "UPDATE chitiet_chungchi_chatluong SET 
            TieuChuanDatDuoc = :tieu_chuan, 
            GhiChuChiTiet = :ghi_chu 
            WHERE ChiTietCCCL_ID = :id";
        
        $stmt_items = $pdo->prepare($sql_items);

        foreach ($items as $item) {
            if (isset($item['id']) && !empty($item['id'])) {
                $stmt_items->execute([
                    ':tieu_chuan' => $item['tieuChuan'] ?? 'Đạt',
                    ':ghi_chu' => $item['ghiChu'] ?? '',
                    ':id' => $item['id']
                ]);
            }
        }
    }

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Cập nhật Chứng chỉ chất lượng thành công!';

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = "Lỗi server: " . $e->getMessage();
    error_log("Error in update_cccl.php: " . $e->getMessage());
    http_response_code(500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

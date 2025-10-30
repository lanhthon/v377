<?php
// File: api/update_phieuxuatkho.php
// API để cập nhật thông tin chi tiết của một phiếu xuất kho đã tồn tại.

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db_config.php';

$response = ['success' => false, 'message' => ''];

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Dữ liệu đầu vào không hợp lệ.');
    }

    $pxk_id = $input['pxk_id'] ?? 0;
    $items = $input['items'] ?? [];
    // Biến $signatures không còn cần thiết nữa
    // $signatures = $input['signatures'] ?? []; 
    $header_data = $input['header'] ?? [];

    if (empty($pxk_id)) {
        throw new Exception('ID Phiếu Xuất Kho không được cung cấp.');
    }

    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // 1. Cập nhật thông tin header và chữ ký trong bảng 'phieuxuatkho'
    $sql_header = "UPDATE phieuxuatkho SET 
        NgayXuat = :ngay_xuat,
        TenCongTy = :ten_cong_ty,
        DiaChiCongTy = :dia_chi_cong_ty,
        NguoiNhan = :nguoi_nhan,
        DiaChiGiaoHang = :dia_chi_giao_hang,
        LyDoXuatKho = :ly_do_xuat,
        NguoiLapPhieu = :nguoi_lap,
        ThuKho = :thu_kho,
        NguoiGiaoHang = :nguoi_giao_hang,
        NguoiNhanHang = :nguoi_nhan_hang
        WHERE PhieuXuatKhoID = :pxk_id";
    
    $stmt_header = $pdo->prepare($sql_header);

    /************************************************************/
    /* PHẦN CODE ĐƯỢC THAY ĐỔI                  */
    /************************************************************/
    
    // Bây giờ tất cả dữ liệu đều được lấy từ $header_data
    $stmt_header->execute([
        ':ngay_xuat' => $header_data['NgayXuat'] ?? null,
        ':ten_cong_ty' => $header_data['TenCongTy'] ?? null,
        ':dia_chi_cong_ty' => $header_data['DiaChiCongTy'] ?? null,
        ':nguoi_nhan' => $header_data['NguoiNhan'] ?? null,
        ':dia_chi_giao_hang' => $header_data['DiaChiGiaoHang'] ?? null,
        ':ly_do_xuat' => $header_data['LyDoXuatKho'] ?? null,
        ':nguoi_lap' => $header_data['NguoiLapPhieu'] ?? null,
        ':thu_kho' => $header_data['ThuKho'] ?? null,
        ':nguoi_giao_hang' => $header_data['NguoiGiaoHang'] ?? null,
        ':nguoi_nhan_hang' => $header_data['NguoiNhanHang'] ?? null,
        ':pxk_id' => $pxk_id
    ]);

    /************************************************************/
    /* KẾT THÚC PHẦN THAY ĐỔI                 */
    /************************************************************/


    // 2. Cập nhật thông tin chi tiết sản phẩm trong 'chitiet_phieuxuatkho'
    if (!empty($items)) {
        $sql_items = "UPDATE chitiet_phieuxuatkho SET 
            SoLuongThucXuat = :so_luong, 
            TaiSo = :tai_so, 
            GhiChu = :ghi_chu 
            WHERE ChiTietPXK_ID = :detail_id";
        
        $stmt_items = $pdo->prepare($sql_items);

        foreach ($items as $item) {
            if (isset($item['detail_id']) && !empty($item['detail_id'])) {
                $stmt_items->execute([
                    ':so_luong' => $item['soLuongThucXuat'] ?? 0,
                    ':tai_so' => $item['taiSo'] ?? '',
                    ':ghi_chu' => $item['ghiChu'] ?? '',
                    ':detail_id' => $item['detail_id']
                ]);
            }
        }
    }

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Cập nhật phiếu xuất kho thành công!';

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    $response['message'] = $e->getMessage();
    error_log("Lỗi khi cập nhật phiếu xuất kho: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
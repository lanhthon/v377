<?php
// File: api/update_bbgh.php
// API cập nhật biên bản giao hàng với đầy đủ thông tin theo yêu cầu mới

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db_config.php';

$response = ['success' => false, 'message' => ''];

try {
    // Đọc dữ liệu JSON từ request
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Dữ liệu JSON không hợp lệ.');
    }

    $bbgh_id = $input['bbgh_id'] ?? 0;
    
    // Thông tin từ form
    $tenCongTy = $input['tenCongTy'] ?? '';
    $diaChiKhach = $input['diaChiKhach'] ?? ''; // Thêm trường địa chỉ khách hàng
    $nguoiNhanHang = $input['nguoiNhanHang'] ?? '';
    $sdtNhanHang = $input['sdtNhanHang'] ?? '';
    $duAn = $input['duAn'] ?? '';
    $diaChiGiaoHang = $input['diaChiGiaoHang'] ?? '';
    $nguoiGiaoHang = $input['nguoiGiaoHang'] ?? '';
    $sdtNguoiGiao = $input['sdtNguoiGiao'] ?? '';
    $sanPham = $input['sanPham'] ?? '';
    $ngayGiao = $input['ngayGiao'] ?? '';
    $items = $input['items'] ?? [];

    if (empty($bbgh_id)) {
        throw new Exception('ID biên bản giao hàng không hợp lệ.');
    }

    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // 1. Cập nhật thông tin chung trong bảng 'bienbangiaohang'
    $sql_header = "UPDATE bienbangiaohang SET 
        TenCongTy = :ten_cong_ty,
        DiaChiKhachHang = :dia_chi_khach,
        DiaChiGiaoHang = :dia_chi_giao_hang,
        NguoiNhanHang = :nguoi_nhan_hang,
        SoDienThoaiNhanHang = :sdt_nhan_hang,
        DuAn = :du_an,
        NguoiGiaoHang = :nguoi_giao_hang,
        SdtNguoiGiaoHang = :sdt_nguoi_giao";
    
    // Thêm ngày giao nếu có
    if (!empty($ngayGiao)) {
        $sql_header .= ", NgayGiao = :ngay_giao";
    }
    
    $sql_header .= " WHERE BBGH_ID = :bbgh_id";
    
    $stmt_header = $pdo->prepare($sql_header);
    
    $params_header = [
        ':ten_cong_ty' => $tenCongTy,
        ':dia_chi_khach' => $diaChiKhach,
        ':dia_chi_giao_hang' => $diaChiGiaoHang,
        ':nguoi_nhan_hang' => $nguoiNhanHang,
        ':sdt_nhan_hang' => $sdtNhanHang,
        ':du_an' => $duAn,
        ':nguoi_giao_hang' => $nguoiGiaoHang,
        ':sdt_nguoi_giao' => $sdtNguoiGiao,
        ':bbgh_id' => $bbgh_id
    ];
    
    if (!empty($ngayGiao)) {
        $params_header[':ngay_giao'] = $ngayGiao;
    }
    
    $stmt_header->execute($params_header);

    // 2. Lưu thông tin sản phẩm vào một bảng tùy chọn hoặc cột khác
    // Vì không thấy cột SanPham trong bảng bienbangiaohang, có thể cần thêm cột này:
    // ALTER TABLE bienbangiaohang ADD COLUMN SanPham VARCHAR(255) DEFAULT NULL;
    
    if (!empty($sanPham)) {
        try {
            $sql_sanpham = "UPDATE bienbangiaohang SET SanPham = :san_pham WHERE BBGH_ID = :bbgh_id";
            $stmt_sanpham = $pdo->prepare($sql_sanpham);
            $stmt_sanpham->execute([
                ':san_pham' => $sanPham,
                ':bbgh_id' => $bbgh_id
            ]);
        } catch (Exception $e) {
            // Nếu cột SanPham chưa tồn tại, bỏ qua lỗi này
            error_log("Cột SanPham chưa tồn tại trong bảng bienbangiaohang: " . $e->getMessage());
        }
    }

    // 3. Cập nhật chi tiết sản phẩm trong 'chitietbienbangiaohang'
    if (!empty($items)) {
        $sql_items = "UPDATE chitietbienbangiaohang SET 
            SoThung = :so_thung, 
            GhiChu = :ghi_chu 
            WHERE ChiTietBBGH_ID = :id";
        
        $stmt_items = $pdo->prepare($sql_items);

        foreach ($items as $item) {
            if (isset($item['id']) && !empty($item['id'])) {
                $stmt_items->execute([
                    ':so_thung' => $item['soThung'] ?? '',
                    ':ghi_chu' => $item['ghiChu'] ?? '',
                    ':id' => $item['id']
                ]);
            }
        }
    }

    // 4. Cập nhật ngày giao hàng vào bảng liên quan nếu cần
    // Có thể cập nhật vào bảng donhang hoặc phieuxuatkho tùy theo logic nghiệp vụ
    if (!empty($ngayGiao)) {
        try {
            // Lấy YCSX_ID từ bienbangiaohang
            $sql_get_ycsx = "SELECT YCSX_ID FROM bienbangiaohang WHERE BBGH_ID = :bbgh_id";
            $stmt_get_ycsx = $pdo->prepare($sql_get_ycsx);
            $stmt_get_ycsx->execute([':bbgh_id' => $bbgh_id]);
            $ycsx_result = $stmt_get_ycsx->fetch(PDO::FETCH_ASSOC);
            
            if ($ycsx_result && !empty($ycsx_result['YCSX_ID'])) {
                // Cập nhật ngày giao dự kiến trong bảng donhang
                $sql_update_donhang = "UPDATE donhang SET NgayGiaoDuKien = :ngay_giao WHERE YCSX_ID = :ycsx_id";
                $stmt_update_donhang = $pdo->prepare($sql_update_donhang);
                $stmt_update_donhang->execute([
                    ':ngay_giao' => $ngayGiao,
                    ':ycsx_id' => $ycsx_result['YCSX_ID']
                ]);
            }
        } catch (Exception $e) {
            // Log lỗi nhưng không làm gián đoạn transaction chính
            error_log("Lỗi khi cập nhật ngày giao vào donhang: " . $e->getMessage());
        }
    }

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Cập nhật biên bản giao hàng thành công!';

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = "Lỗi database: " . $e->getMessage();
    error_log("Database error in update_bbgh.php: " . $e->getMessage());
    http_response_code(500);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = "Lỗi server: " . $e->getMessage();
    error_log("General error in update_bbgh.php: " . $e->getMessage());
    http_response_code(500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
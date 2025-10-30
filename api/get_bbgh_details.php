<?php
// File: api/get_bbgh_details.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db_config.php';

$response = ['success' => false, 'header' => null, 'items' => []];
$bbgh_id = $_GET['bbgh_id'] ?? 0;

if ($bbgh_id) {
    try {
        $pdo = get_db_connection();
        
        // --- ĐÃ SỬA LẠI LOGIC JOIN ---
        // Luồng kết nối đúng: bienbangiaohang -> phieuxuatkho -> chuanbihang
        $stmt_header = $pdo->prepare("
            SELECT
                b.SoBBGH, b.NgayTao, b.GhiChu, b.NgayGiao, b.SanPham,
                b.ChucVuNhanHang,
                
                -- Thông tin bên B (có thể đã được chỉnh sửa và lưu)
                b.TenCongTy, b.DiaChiKhachHang, b.DiaChiGiaoHang, b.NguoiNhanHang, b.SoDienThoaiNhanHang, b.DuAn,
                
                -- Thông tin bên A (có thể đã được chỉnh sửa và lưu)
                b.NguoiGiaoHang AS NguoiGiaoHangDaLuu, 
                b.SdtNguoiGiaoHang AS SdtNguoiGiaoHangDaLuu,
                
                -- Thông tin gốc bên A để fallback
                u.HoTen as TenNguoiLap,
                u.SoDienThoai as SdtNguoiLap,
                
                -- Thông tin gốc của bên B để fallback
                d.SoYCSX, d.NgayGiaoDuKien,
                ct.TenCongTy as TenCongTyGoc,
                ct.DiaChi as DiaChiCongTyGoc,
                bg.TenDuAn as TenDuAnGoc,
                bg.DiaChiGiaoHang as DiaChiGiaoHangGoc,
                bg.NgayGiaoDuKien as NgayGiaoDuKienGoc,

                -- Dữ liệu mới từ bảng chuanbihang (lấy qua phieuxuatkho)
                cbh.NguoiNhanHang AS NguoiNhanHang_CBH,
                cbh.SdtNguoiNhan AS SdtNguoiNhan_CBH,
                
                -- Thông tin từ bảng nguoilienhe (đại diện bên B gốc)
                nlh.HoTen AS NguoiLienHe_Goc,
                nlh.SoDiDong AS SdtNguoiLienHe_Goc,
                
                -- Thêm thông tin từ bảng variants để lấy sản phẩm
                (SELECT GROUP_CONCAT(DISTINCT v.variant_name SEPARATOR ', ') 
                 FROM chitietbienbangiaohang ctbbgh 
                 LEFT JOIN variants v ON ctbbgh.SanPhamID = v.variant_id 
                 WHERE ctbbgh.BBGH_ID = b.BBGH_ID) as DanhSachSanPham
                
            FROM bienbangiaohang b
            LEFT JOIN donhang d ON b.YCSX_ID = d.YCSX_ID
            LEFT JOIN phieuxuatkho pxk ON b.PhieuXuatKhoID = pxk.PhieuXuatKhoID
            LEFT JOIN chuanbihang cbh ON pxk.CBH_ID = cbh.CBH_ID
            LEFT JOIN nguoidung u ON pxk.NguoiTaoID = u.UserID
            LEFT JOIN baogia bg ON d.BaoGiaID = bg.BaoGiaID
            LEFT JOIN congty ct ON bg.CongTyID = ct.CongTyID
            LEFT JOIN nguoilienhe nlh ON bg.NguoiLienHeID = nlh.NguoiLienHeID
            WHERE b.BBGH_ID = ?
        ");
        $stmt_header->execute([$bbgh_id]);
        $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

        if ($header) {
            // Logic ưu tiên cho Bên B: Dùng dữ liệu đã lưu trên BBGH trước, nếu không có thì dùng dữ liệu từ CBH
            $header['TenCongTy'] = !empty($header['TenCongTy']) ? $header['TenCongTy'] : $header['TenCongTyGoc'];
            
            $header['DiaChiKhach'] = !empty($header['DiaChiKhachHang']) ? $header['DiaChiKhachHang'] : (!empty($header['DiaChiCongTyGoc']) ? $header['DiaChiCongTyGoc'] : '');

            // DEBUG: Log dữ liệu trước khi xử lý
            error_log("DEBUG - NguoiNhanHang_CBH: " . ($header['NguoiNhanHang_CBH'] ?? 'NULL'));
            error_log("DEBUG - NguoiNhanHang original: " . ($header['NguoiNhanHang'] ?? 'NULL'));
            error_log("DEBUG - NguoiLienHe_Goc: " . ($header['NguoiLienHe_Goc'] ?? 'NULL'));
            
            // Logic lấy thông tin người nhận hàng (ưu tiên theo thứ tự):
            // 1. Dữ liệu từ Phiếu chuẩn bị hàng (cbh.NguoiNhanHang) - LUÔN ƯU TIÊN  
            // 2. Dữ liệu đã sửa trên Biên bản giao hàng (b.NguoiNhanHang)
            // 3. Dữ liệu từ Người liên hệ gốc trong báo giá (nlh.HoTen)
            if (!empty($header['NguoiNhanHang_CBH'])) {
                $header['NguoiNhanHang'] = $header['NguoiNhanHang_CBH'];
                error_log("DEBUG - Chọn từ CBH: " . $header['NguoiNhanHang_CBH']);
            } elseif (!empty($header['NguoiNhanHang'])) {
                error_log("DEBUG - Giữ nguyên BBGH: " . $header['NguoiNhanHang']);
            } else {
                $header['NguoiNhanHang'] = $header['NguoiLienHe_Goc'];
                error_log("DEBUG - Chọn từ NguoiLienHe: " . $header['NguoiLienHe_Goc']);
            }

            // Logic lấy số điện thoại người nhận (ưu tiên theo thứ tự):
            // 1. Số điện thoại từ Phiếu chuẩn bị hàng (cbh.SdtNguoiNhan) - LUÔN ƯU TIÊN
            // 2. Số điện thoại đã sửa trên Biên bản giao hàng (b.SoDienThoaiNhanHang)
            // 3. Số điện thoại từ Người liên hệ gốc trong báo giá (nlh.SoDiDong)
            if (!empty($header['SdtNguoiNhan_CBH'])) {
                $header['SoDienThoaiNhanHang'] = $header['SdtNguoiNhan_CBH'];
                error_log("DEBUG - SDT từ CBH: " . $header['SdtNguoiNhan_CBH']);
            } elseif (!empty($header['SoDienThoaiNhanHang'])) {
                error_log("DEBUG - SDT giữ nguyên BBGH: " . $header['SoDienThoaiNhanHang']);
            } else {
                $header['SoDienThoaiNhanHang'] = $header['SdtNguoiLienHe_Goc'];
                error_log("DEBUG - SDT từ NguoiLienHe: " . $header['SdtNguoiLienHe_Goc']);
            }

            $header['DuAn'] = !empty($header['DuAn']) ? $header['DuAn'] : $header['TenDuAnGoc'];
            $header['DiaChiGiaoHang'] = !empty($header['DiaChiGiaoHang']) ? $header['DiaChiGiaoHang'] : $header['DiaChiGiaoHangGoc'];
            
            // Logic ưu tiên cho Bên A: Dùng dữ liệu đã lưu trên BBGH trước, nếu không có thì dùng dữ liệu gốc
            $header['NguoiGiaoHangHienThi'] = !empty($header['NguoiGiaoHangDaLuu']) ? $header['NguoiGiaoHangDaLuu'] : $header['TenNguoiLap'];
            $header['SdtNguoiGiaoHangHienThi'] = !empty($header['SdtNguoiGiaoHangDaLuu']) ? $header['SdtNguoiGiaoHangDaLuu'] : $header['SdtNguoiLap'];
            
            // Xử lý ngày giao: ưu tiên ngày đã lưu, nếu không có thì lấy ngày dự kiến
            $header['NgayGiaoHienThi'] = !empty($header['NgayGiao']) ? $header['NgayGiao'] : $header['NgayGiaoDuKienGoc'];
            
            // Xử lý sản phẩm: ưu tiên thông tin đã lưu trên BBGH, nếu không có thì lấy HangMuc từ báo giá
            $header['SanPhamHienThi'] = !empty($header['SanPham']) ? $header['SanPham'] : $header['HangMucGoc'];
            
            // Đảm bảo các trường bắt buộc cho form có giá trị mặc định
            $header['ChucVuNhanHang'] = !empty($header['ChucVuNhanHang']) ? $header['ChucVuNhanHang'] : 'QL. Kho';

            $response['header'] = $header;

            // Lấy chi tiết các mục hàng
            $stmt_items = $pdo->prepare("
                SELECT 
                    ct.ChiTietBBGH_ID, ct.MaHang, ct.TenSanPham, ct.SoLuong, ct.SoThung, ct.GhiChu,
                    ct.TenNhom, ct.ID_ThongSo, ct.DoDay, ct.BanRong,
                    COALESCE(u.name, ct.DonViTinh, 'Bộ') AS DonViTinh 
                FROM chitietbienbangiaohang ct
                LEFT JOIN variants v ON ct.SanPhamID = v.variant_id
                LEFT JOIN products p ON v.product_id = p.product_id
                LEFT JOIN units u ON p.base_unit_id = u.unit_id
                WHERE ct.BBGH_ID = ?
                ORDER BY ct.ThuTuHienThi, ct.ChiTietBBGH_ID
            ");
            $stmt_items->execute([$bbgh_id]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($items as &$item) {
                $item['id'] = $item['ChiTietBBGH_ID'];
            }
            
            $response['items'] = $items;
            $response['success'] = true;
        } else {
            $response['message'] = "Không tìm thấy biên bản giao hàng.";
        }

    } catch (Exception $e) {
        $response['message'] = "Lỗi server: " . $e->getMessage();
        error_log("Error in get_bbgh_details.php: " . $e->getMessage());
        http_response_code(500);
    }
} else {
    $response['message'] = "Thiếu ID của Biên bản giao hàng.";
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
<?php
// File: api/get_cccl_details.php (Đã sửa lỗi địa chỉ dự án và tên sản phẩm)

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db_config.php';

$response = ['success' => false, 'header' => null, 'items' => []];
$cccl_id = $_GET['cccl_id'] ?? 0;

if ($cccl_id) {
    try {
        $pdo = get_db_connection();

        $sql_header = "
           SELECT
                c.SoCCCL, c.NgayCap,
                -- Dữ liệu có thể chỉnh sửa từ bảng CCCL
                c.TenCongTyKhach, c.DiaChiKhach, c.TenDuAn, c.DiaChiDuAn, c.SanPham, c.NguoiKiemTra,
                
                -- Dữ liệu gốc để làm fallback
                d.SoYCSX,
                d.TenDuAn AS TenDuAnTuDonHang,
                d.DuAnID AS DuAnIDTuDonHang,
                u.HoTen AS TenNguoiLap,
                ct.TenCongTy AS TenCongTyGoc,
                ct.DiaChi AS DiaChiKhachGoc,
                bg.TenDuAn AS TenDuAnGoc,
                bg.DuAnID AS DuAnIDGoc,
                bg.HangMuc AS HangMucGoc,
                bg.DiaChiGiaoHang AS DiaChiDuAnGoc,
                da.DiaChi AS DiaChiDuAnTuBangDuAn
            FROM chungchi_chatluong c
            LEFT JOIN phieuxuatkho p ON c.PhieuXuatKhoID = p.PhieuXuatKhoID
            LEFT JOIN donhang d ON p.YCSX_ID = d.YCSX_ID
            LEFT JOIN baogia bg ON d.BaoGiaID = bg.BaoGiaID
            LEFT JOIN congty ct ON bg.CongTyID = ct.CongTyID
            LEFT JOIN DuAn da ON bg.DuAnID = da.DuAnID
            LEFT JOIN nguoidung u ON c.NguoiLap = u.UserID
            WHERE c.CCCL_ID = :cccl_id
        ";
        $stmt_header = $pdo->prepare($sql_header);
        $stmt_header->execute([':cccl_id' => $cccl_id]);
        $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

        if (!$header) {
            throw new Exception("Không tìm thấy Chứng chỉ chất lượng.");
        }

        // Logic tìm địa chỉ dự án khi DuAnID NULL
        // Ưu tiên 1: Tìm theo TenDuAn từ bảng donhang
        if (empty($header['DuAnIDTuDonHang']) && empty($header['DiaChiDuAnTuBangDuAn']) && !empty($header['TenDuAnTuDonHang'])) {
            $sql_find_duan = "SELECT DiaChi FROM DuAn WHERE TenDuAn = :ten_duan LIMIT 1";
            $stmt_find = $pdo->prepare($sql_find_duan);
            $stmt_find->execute([':ten_duan' => $header['TenDuAnTuDonHang']]);
            $duan_found = $stmt_find->fetch(PDO::FETCH_ASSOC);
            
            if ($duan_found) {
                $header['DiaChiDuAnTuBangDuAn'] = $duan_found['DiaChi'];
            }
        }
        
        // Ưu tiên 2: Nếu vẫn không tìm thấy, thử tìm theo TenDuAn từ bảng baogia
        if (empty($header['DuAnIDGoc']) && empty($header['DiaChiDuAnTuBangDuAn']) && !empty($header['TenDuAnGoc'])) {
            $sql_find_duan = "SELECT DiaChi FROM DuAn WHERE TenDuAn = :ten_duan LIMIT 1";
            $stmt_find = $pdo->prepare($sql_find_duan);
            $stmt_find->execute([':ten_duan' => $header['TenDuAnGoc']]);
            $duan_found = $stmt_find->fetch(PDO::FETCH_ASSOC);
            
            if ($duan_found) {
                $header['DiaChiDuAnTuBangDuAn'] = $duan_found['DiaChi'];
            }
        }

        // Áp dụng logic fallback với độ ưu tiên rõ ràng
        $header['TenCongTyKhach'] = !empty($header['TenCongTyKhach']) ? $header['TenCongTyKhach'] : $header['TenCongTyGoc'];
        $header['DiaChiKhach'] = !empty($header['DiaChiKhach']) ? $header['DiaChiKhach'] : $header['DiaChiKhachGoc'];
        
        // Ưu tiên TenDuAn từ donhang, sau đó mới đến baogia
        if (!empty($header['TenDuAn'])) {
            // Đã có dữ liệu đã lưu trong CCCL
            $header['TenDuAn'] = $header['TenDuAn'];
        } elseif (!empty($header['TenDuAnTuDonHang'])) {
            // Lấy từ donhang
            $header['TenDuAn'] = $header['TenDuAnTuDonHang'];
        } elseif (!empty($header['TenDuAnGoc'])) {
            // Lấy từ baogia
            $header['TenDuAn'] = $header['TenDuAnGoc'];
        } else {
            $header['TenDuAn'] = '';
        }
        
        // Fix địa chỉ dự án: ưu tiên DiaChiGiaoHang từ báo giá, sau đó mới đến DiaChi từ bảng DuAn
        if (!empty($header['DiaChiDuAn'])) {
            // Đã có dữ liệu đã lưu trong CCCL
            $header['DiaChiDuAn'] = $header['DiaChiDuAn'];
        } elseif (!empty($header['DiaChiDuAnGoc'])) {
            // Lấy từ DiaChiGiaoHang trong báo giá
            $header['DiaChiDuAn'] = $header['DiaChiDuAnGoc'];
        } elseif (!empty($header['DiaChiDuAnTuBangDuAn'])) {
            // Lấy từ bảng DuAn
            $header['DiaChiDuAn'] = $header['DiaChiDuAnTuBangDuAn'];
        } else {
            $header['DiaChiDuAn'] = '';
        }
        
        // Fix tên sản phẩm: ưu tiên HangMuc từ báo giá
        if (!empty($header['SanPham'])) {
            // Đã có dữ liệu đã lưu trong CCCL
            $header['SanPham'] = $header['SanPham'];
        } elseif (!empty($header['HangMucGoc'])) {
            // Lấy từ HangMuc trong báo giá
            $header['SanPham'] = $header['HangMucGoc'];
        } else {
            // Giá trị mặc định đã sửa
            $header['SanPham'] = 'Gối đỡ PU Foam & Cùm Ula 3i-Fix';
        }
        
        $header['NguoiKiemTra'] = !empty($header['NguoiKiemTra']) ? $header['NguoiKiemTra'] : 'Nguyễn Hữu Hạnh';

        $response['header'] = $header;

        // Lấy danh sách sản phẩm
        $sql_items = "
            SELECT 
                ct.ChiTietCCCL_ID, ct.MaHang, ct.TenSanPham, ct.SoLuong, ct.TieuChuanDatDuoc, ct.GhiChuChiTiet,
                COALESCE(u.name, ct.DonViTinh, 'Bộ') AS DonViTinh
            FROM chitiet_chungchi_chatluong ct
            LEFT JOIN variants v ON ct.SanPhamID = v.variant_id
            LEFT JOIN products p ON v.product_id = p.product_id
            LEFT JOIN units u ON p.base_unit_id = u.unit_id
            WHERE ct.CCCL_ID = :cccl_id
            ORDER BY ct.ThuTuHienThi, ct.ChiTietCCCL_ID
        ";
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute([':cccl_id' => $cccl_id]);
        $response['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        http_response_code(500);
    }
} else {
    $response['message'] = "Missing CCCL_ID.";
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
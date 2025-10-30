<?php
/**
 * File: api/save_phieuxuatkho.php
 * Version: 3.0
 * Description: API để lưu Phiếu Xuất Kho Tổng.
 * - **UPDATE V3.0**: Thêm logic dọn dẹp tồn kho đã gán (donhang_phanbo_tonkho)
 * cho CBH_ID liên quan để giải phóng tồn kho ảo và đảm bảo tính nhất quán.
 * - Giữ lại toàn bộ chức năng của phiên bản 2.8.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';
session_start();

$response = ['success' => false, 'message' => '', 'pxk_id' => null];
$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $response['message'] = 'Dữ liệu gửi lên không hợp lệ (JSON sai định dạng).';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$cbh_id = $data['cbh_id'] ?? 0;
$items = $data['items'] ?? [];
$header_data = $data['header'] ?? [];
$signatures = $data['signatures'] ?? [];
$nguoi_tao_id = $_SESSION['user_id'] ?? null;

if (!$cbh_id || empty($items) || !$nguoi_tao_id) {
    http_response_code(400);
    $response['message'] = 'Dữ liệu không đủ để xử lý (thiếu cbh_id, items, hoặc user_id).';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = null;
try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // =================================================================
    // === BẮT ĐẦU PHẦN BỔ SUNG QUAN TRỌNG ===
    // 1. Dọn dẹp tất cả các gán tồn kho cũ cho CBH_ID này.
    // Thao tác này giải phóng tồn kho ảo, đảm bảo số liệu tồn kho khả dụng luôn chính xác.
    $stmt_clear_allocations = $pdo->prepare("DELETE FROM donhang_phanbo_tonkho WHERE CBH_ID = ?");
    $stmt_clear_allocations->execute([$cbh_id]);
    // === KẾT THÚC PHẦN BỔ SUNG ===
    // =================================================================

    // 2. Lấy thông tin cần thiết từ chuanbihang (Chức năng cũ)
    $stmt_info = $pdo->prepare("SELECT YCSX_ID, TenCongTy, DiaDiemGiaoHang, NguoiNhanHang FROM chuanbihang WHERE CBH_ID = ?");
    $stmt_info->execute([$cbh_id]);
    $info_result = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$info_result) {
        throw new Exception("Không tìm thấy Phiếu Chuẩn Bị Hàng ID: " . $cbh_id);
    }
    $ycsx_id = $info_result['YCSX_ID'];

    // 3. Logic tạo số phiếu an toàn (Chức năng cũ)
    $year = date('Y');
    $stmt_max = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(SoPhieuXuat, 10) AS UNSIGNED)) FROM phieuxuatkho WHERE SoPhieuXuat LIKE ? FOR UPDATE");
    $stmt_max->execute(["PXK-$year-%"]);
    $max_num = $stmt_max->fetchColumn();
    $next_num = ($max_num ?? 0) + 1;
    $so_phieu_xuat = sprintf('PXK-%s-%04d', $year, $next_num);

    // 4. Insert vào bảng phieuxuatkho với đầy đủ thông tin header (Chức năng cũ)
    $stmt_pxk = $pdo->prepare("
        INSERT INTO phieuxuatkho (
            YCSX_ID, CBH_ID, SoPhieuXuat, NgayXuat, 
            TenCongTy, DiaChiCongTy, NguoiNhan, DiaChiGiaoHang, 
            LyDoXuatKho, GhiChu, NguoiTaoID,
            NguoiLapPhieu, ThuKho, NguoiGiaoHang, NguoiNhanHang
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $ngay_xuat = !empty($header_data['NgayXuat']) ? $header_data['NgayXuat'] : date('Y-m-d');
    $ten_cong_ty = $header_data['TenCongTy'] ?? $info_result['TenCongTy'] ?? '';
    $dia_chi_cong_ty = $header_data['DiaChiCongTy'] ?? '';
    $nguoi_nhan = $header_data['NguoiNhan'] ?? $info_result['NguoiNhanHang'] ?? '';
    $dia_chi_giao_hang = $header_data['DiaChiGiaoHang'] ?? $info_result['DiaDiemGiaoHang'] ?? '';
    $ly_do_xuat_kho = $header_data['LyDoXuatKho'] ?? 'Xuất kho giao hàng theo YCSX';
    $ghi_chu_chung = "Xuất kho tổng hợp từ phiếu CBH ID: " . $cbh_id;
    
    $nguoi_lap_phieu = $header_data['NguoiLapPhieu'] ?? $signatures['nguoiLapPhieu'] ?? '';
    $thu_kho = $header_data['ThuKho'] ?? $signatures['thuKho'] ?? '';
    $nguoi_giao_hang = $header_data['NguoiGiaoHang'] ?? $signatures['nguoiGiaoHang'] ?? '';
    $nguoi_nhan_hang = $header_data['NguoiNhanHang'] ?? $signatures['nguoiNhanHang'] ?? '';

    $stmt_pxk->execute([
        $ycsx_id, $cbh_id, $so_phieu_xuat, $ngay_xuat,
        $ten_cong_ty, $dia_chi_cong_ty, $nguoi_nhan, $dia_chi_giao_hang,
        $ly_do_xuat_kho, $ghi_chu_chung, $nguoi_tao_id,
        $nguoi_lap_phieu, $thu_kho, $nguoi_giao_hang, $nguoi_nhan_hang
    ]);

    $pxk_id = $pdo->lastInsertId();
    if (!$pxk_id) {
        throw new Exception("Không thể tạo phiếu xuất kho trong cơ sở dữ liệu.");
    }

    // 5. Chuẩn bị các câu lệnh cho vòng lặp (Chức năng cũ)
    $stmt_chitiet = $pdo->prepare("INSERT INTO chitiet_phieuxuatkho (PhieuXuatKhoID, SanPhamID, MaHang, TenSanPham, SoLuongYeuCau, SoLuongThucXuat, TaiSo, GhiChu) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_inventory = $pdo->prepare("UPDATE variant_inventory SET quantity = quantity - ? WHERE variant_id = ?");
    $stmt_history = $pdo->prepare("INSERT INTO lichsunhapxuat (SanPhamID, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu, GhiChu) VALUES (?, 'XUAT_KHO', ?, (SELECT quantity FROM variant_inventory WHERE variant_id = ?), ?, ?)");
    $stmt_get_soluong_goc = $pdo->prepare("SELECT SoLuong FROM chitietchuanbihang WHERE CBH_ID = ? AND SanPhamID = ?");
    $stmt_find_ecu_id = $pdo->prepare("SELECT variant_id FROM variants WHERE variant_name = ? LIMIT 1");

    // 6. Insert chi tiết, cập nhật tồn kho vật lý và ghi lịch sử (Chức năng cũ)
    foreach ($items as $item) {
        $soLuongTrenPhieu = intval($item['soLuongThucXuat']);
        if ($soLuongTrenPhieu <= 0) continue;

        $sanPhamID = !empty($item['variant_id']) ? $item['variant_id'] : null;
        $soLuongYeuCauGoc = $soLuongTrenPhieu;

        if ($sanPhamID) {
            $stmt_get_soluong_goc->execute([$cbh_id, $sanPhamID]);
            $soluong_goc = $stmt_get_soluong_goc->fetchColumn();
            if ($soluong_goc !== false) {
                $soLuongYeuCauGoc = (int)$soluong_goc;
            }
        } else if (isset($item['maHang']) && $item['maHang'] === 'VT-ECU') {
            $stmt_find_ecu_id->execute([$item['tenSanPham']]);
            $sanPhamID = $stmt_find_ecu_id->fetchColumn();
        }

        $stmt_chitiet->execute([
            $pxk_id, $sanPhamID, $item['maHang'], $item['tenSanPham'],
            $soLuongYeuCauGoc, $soLuongTrenPhieu, $item['taiSo'], $item['ghiChu']
        ]);

        if ($sanPhamID && $soLuongTrenPhieu > 0) {
            $stmt_inventory->execute([$soLuongTrenPhieu, $sanPhamID]);
            
            $ghi_chu_ls = "Xuất kho theo phiếu " . $so_phieu_xuat;
            $so_luong_thay_doi = -$soLuongTrenPhieu;
            $stmt_history->execute([$sanPhamID, $so_luong_thay_doi, $sanPhamID, $so_phieu_xuat, $ghi_chu_ls]);
        }
    }

    // 7. Cập nhật trạng thái đơn hàng và phiếu chuẩn bị hàng (Chức năng cũ)
    $pdo->prepare("UPDATE donhang SET TrangThai = 'Đã xuất kho', PXK_ID = ? WHERE YCSX_ID = ?")->execute([$pxk_id, $ycsx_id]);
    $pdo->prepare("UPDATE chuanbihang SET TrangThai = 'Đã xuất kho' WHERE CBH_ID = ?")->execute([$cbh_id]);

    // 8. Hoàn tất giao dịch (Chức năng cũ)
    if ($pdo->commit()) {
        $response['success'] = true;
        $response['message'] = 'Đã hoàn tất xuất kho và cập nhật tồn kho thành công!';
        $response['pxk_id'] = $pxk_id;
    } else {
        throw new Exception("Không thể hoàn tất giao dịch (lỗi khi commit).");
    }

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    $response['message'] = 'Lỗi Server: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
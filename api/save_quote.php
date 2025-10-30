<?php
header('Content-Type: application/json');

// Kết nối CSDL
try {
    require_once __DIR__ . '/../config/db_config.php';

    if (!function_exists('get_db_connection')) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy hàm get_db_connection() trong db_config.php']);
        exit();
    }

    /** @var PDO $pdo */
    $pdo = get_db_connection();
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()]);
    exit();
}

// Nhận dữ liệu JSON từ request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Kiểm tra dữ liệu JSON
if ($data === null) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ.']);
    exit();
}

$baoGiaID  = $data['baoGiaID']  ?? 0;
$quoteInfo = $data['quoteInfo'] ?? [];
$items     = $data['items']     ?? [];
$totals    = $data['totals']    ?? [];

$phanTramDieuChinh = $quoteInfo['phanTramDieuChinh'] ?? 0;
$thuePhanTram = $quoteInfo['thuePhanTram'] ?? 8;

// Bắt đầu transaction
$pdo->beginTransaction();

try {
    // =================================================================
    // MỚI: KIỂM TRA SoBaoGia TRÙNG LẶP TRƯỚC KHI LƯU
    // =================================================================
    $soBaoGia = $quoteInfo['soBaoGia'] ?? null;
    if (!empty($soBaoGia)) {
        // Câu truy vấn cơ bản để tìm SoBaoGia
        $sqlCheck = "SELECT BaoGiaID FROM baogia WHERE SoBaoGia = ?";
        $paramsCheck = [$soBaoGia];

        // Nếu là CẬP NHẬT, phải loại trừ chính báo giá đang sửa
        if ($baoGiaID > 0) {
            $sqlCheck .= " AND BaoGiaID != ?";
            $paramsCheck[] = $baoGiaID;
        }

        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute($paramsCheck);

        // Nếu tìm thấy một bản ghi khác có cùng SoBaoGia
        if ($stmtCheck->fetchColumn()) {
            // Hủy transaction và gửi thông báo lỗi thân thiện cho người dùng
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => "Số báo giá '$soBaoGia' đã tồn tại. Vui lòng sử dụng số khác."]);
            exit();
        }
    }
    // =================================================================
    // KẾT THÚC PHẦN KIỂM TRA
    // =================================================================


    // =========================
    // 1) BẢNG baogia
    // =========================
    if ($baoGiaID > 0) {
        // Cập nhật báo giá hiện có
        $sql = "UPDATE baogia SET
                    SoBaoGia = ?, NgayBaoGia = ?, NgayGiaoDuKien = ?, KhachHangID = ?, CongTyID = ?,
                    NguoiLienHeID = ?, UserID = ?, DuAnID = ?, TenCongTy = ?, DiaChiKhach = ?,
                    NguoiNhan = ?, SoDiDongKhach = ?, HangMuc = ?, TenDuAn = ?, NguoiBaoGia = ?,
                    ChucVuNguoiBaoGia = ?, DiDongNguoiBaoGia = ?, HieuLucBaoGia = ?, XuatXu = ?,
                    ThoiGianGiaoHang = ?, DieuKienThanhToan = ?, DiaChiGiaoHang = ?, HinhAnh1 = ?,
                    HinhAnh2 = ?, SoLuongVanChuyen = ?, DonGiaVanChuyen = ?, GhiChuVanChuyen = ?,
                    CoCheGiaApDung = ?, TrangThai = ?, TongTienTruocThue = ?, ThueVAT = ?, TongTienSauThue = ?,
                    PhanTramDieuChinh = ?, ThuePhanTram = ?
                WHERE BaoGiaID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $quoteInfo['soBaoGia'] ?? null,
            !empty($quoteInfo['ngayBaoGia']) ? date('Y-m-d', strtotime(str_replace('/', '-', $quoteInfo['ngayBaoGia']))) : null,
            (isset($quoteInfo['ngayGiaoDuKien']) && !empty($quoteInfo['ngayGiaoDuKien']))
                ? date('Y-m-d', strtotime(str_replace('/', '-', $quoteInfo['ngayGiaoDuKien']))) : null,
            null, // KhachHangID - không còn sử dụng
            $quoteInfo['congTyID'] ?? null,
            $quoteInfo['nguoiLienHeID'] ?? null,
            $quoteInfo['userID'] ?? null,
            $quoteInfo['duAnID'] ?? null,
            $quoteInfo['tenCongTy'] ?? null,
            $quoteInfo['diaChiKhach'] ?? null,
            $quoteInfo['nguoiNhan'] ?? null,
            $quoteInfo['soDiDongKhach'] ?? null,
            $quoteInfo['hangMuc'] ?? null,
            $quoteInfo['tenDuAn'] ?? null,
            $quoteInfo['nguoiBaoGia'] ?? null,
            $quoteInfo['chucVuNguoiBaoGia'] ?? null,
            $quoteInfo['diDongNguoiBaoGia'] ?? null,
            $quoteInfo['hieuLucBaoGia'] ?? null,
            $quoteInfo['xuatXu'] ?? null,
            $quoteInfo['thoiGianGiaoHang'] ?? null,
            $quoteInfo['dieuKienThanhToan'] ?? null,
            $quoteInfo['diaChiGiaoHang'] ?? null,
            $quoteInfo['hinhAnh1'] ?? null,
            $quoteInfo['hinhAnh2'] ?? null,
            $quoteInfo['soLuongVanChuyen'] ?? 0,
            $quoteInfo['phiVanChuyen'] ?? 0,
            $quoteInfo['ghiChuVanChuyen'] ?? null,
            $quoteInfo['coCheGia'] ?? null,
            $quoteInfo['trangThai'] ?? null,
            $totals['subtotal'] ?? 0,
            $totals['vat'] ?? 0,
            $totals['total'] ?? 0,
            $phanTramDieuChinh,
            $thuePhanTram,
            $baoGiaID
        ]);
    } else {
        // Thêm báo giá mới
        $sql = "INSERT INTO baogia (
                    SoBaoGia, NgayBaoGia, NgayGiaoDuKien, KhachHangID, CongTyID,
                    NguoiLienHeID, UserID, DuAnID, TenCongTy, DiaChiKhach,
                    NguoiNhan, SoDiDongKhach, HangMuc, TenDuAn, NguoiBaoGia,
                    ChucVuNguoiBaoGia, DiDongNguoiBaoGia, HieuLucBaoGia, XuatXu,
                    ThoiGianGiaoHang, DieuKienThanhToan, DiaChiGiaoHang, HinhAnh1,
                    HinhAnh2, SoLuongVanChuyen, DonGiaVanChuyen, GhiChuVanChuyen,
                    CoCheGiaApDung, TrangThai, TongTienTruocThue, ThueVAT, TongTienSauThue, PhanTramDieuChinh, ThuePhanTram
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $quoteInfo['soBaoGia'] ?? null,
            !empty($quoteInfo['ngayBaoGia']) ? date('Y-m-d', strtotime(str_replace('/', '-', $quoteInfo['ngayBaoGia']))) : null,
            (isset($quoteInfo['ngayGiaoDuKien']) && !empty($quoteInfo['ngayGiaoDuKien']))
                ? date('Y-m-d', strtotime(str_replace('/', '-', $quoteInfo['ngayGiaoDuKien']))) : null,
            null,
            $quoteInfo['congTyID'] ?? null,
            $quoteInfo['nguoiLienHeID'] ?? null,
            $quoteInfo['userID'] ?? null,
            $quoteInfo['duAnID'] ?? null,
            $quoteInfo['tenCongTy'] ?? null,
            $quoteInfo['diaChiKhach'] ?? null,
            $quoteInfo['nguoiNhan'] ?? null,
            $quoteInfo['soDiDongKhach'] ?? null,
            $quoteInfo['hangMuc'] ?? null,
            $quoteInfo['tenDuAn'] ?? null,
            $quoteInfo['nguoiBaoGia'] ?? null,
            $quoteInfo['chucVuNguoiBaoGia'] ?? null,
            $quoteInfo['diDongNguoiBaoGia'] ?? null,
            $quoteInfo['hieuLucBaoGia'] ?? null,
            $quoteInfo['xuatXu'] ?? null,
            $quoteInfo['thoiGianGiaoHang'] ?? null,
            $quoteInfo['dieuKienThanhToan'] ?? null,
            $quoteInfo['diaChiGiaoHang'] ?? null,
            $quoteInfo['hinhAnh1'] ?? null,
            $quoteInfo['hinhAnh2'] ?? null,
            $quoteInfo['soLuongVanChuyen'] ?? 0,
            $quoteInfo['phiVanChuyen'] ?? 0,
            $quoteInfo['ghiChuVanChuyen'] ?? null,
            $quoteInfo['coCheGia'] ?? null,
            $quoteInfo['trangThai'] ?? null,
            $totals['subtotal'] ?? 0,
            $totals['vat'] ?? 0,
            $totals['total'] ?? 0,
            $phanTramDieuChinh,
            $thuePhanTram
        ]);
        $baoGiaID = (int)$pdo->lastInsertId();
    }

    // =========================
    // 2) XÓA chi tiết cũ (chỉ khi update)
    // =========================
    if (($data['baoGiaID'] ?? 0) > 0) {
        $stmt = $pdo->prepare("DELETE FROM chitietbaogia WHERE BaoGiaID = ?");
        $stmt->execute([$baoGiaID]);
    }

    // =========================
    // 3) THÊM chi tiết mới
    // =========================
    $sqlCT = "INSERT INTO chitietbaogia (
                BaoGiaID, TenNhom, ID_ThongSo, variant_id, MaHang, TenSanPham,
                DoDay, ChieuRong, DonViTinh, SoLuong, DonGia, ThanhTien, GhiChu, ThuTuHienThi, KhuVuc
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtCT = $pdo->prepare($sqlCT);

    foreach ($items as $item) {
        $stmtCT->execute([
            $baoGiaID,
            $item['groupName']  ?? null,
            $item['id_thongso'] ?? null,
            $item['productId']  ?? null,
            $item['code']       ?? null,
            $item['name']       ?? null,
            $item['thickness']  ?? null,
            $item['width']      ?? null,
            $item['unit']       ?? 'Bộ',
            $item['quantity']   ?? 0,
            $item['unitPrice']  ?? 0,
            $item['lineTotal']  ?? 0,
            $item['note']       ?? null,
            $item['order']      ?? null,
            $item['khuVuc']     ?? null
        ]);
    }

    // Hoàn tất transaction
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Lưu báo giá thành công!', 'baoGiaID' => $baoGiaID]);
} catch (\Exception $e) {
    // Nếu có bất kỳ lỗi nào xảy ra, rollback lại transaction
    $pdo->rollBack();
    error_log("Lỗi khi lưu báo giá: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu báo giá: ' . $e->getMessage()]);
}
?>

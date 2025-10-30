<?php
// File: api/create_bbgh.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once '../config/database.php';

// --- Lấy dữ liệu đầu vào ---
$data = json_decode(file_get_contents('php://input'), true);
$phieu_xuat_kho_id = $data['phieu_xuat_kho_id'] ?? null;
$ycsx_id = $data['ycsx_id'] ?? null; // YCSX_ID cũng được gửi từ frontend
$nguoi_lap_id = $_SESSION['user_id'] ?? null;

// --- Kiểm tra đầu vào ---
if (!$phieu_xuat_kho_id || !$ycsx_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID Phiếu Xuất Kho hoặc ID Yêu Cầu Sản Xuất.']);
    exit;
}

$conn->begin_transaction();

try {
    // --- 1. Kiểm tra xem BBGH đã tồn tại cho Phiếu Xuất Kho này chưa ---
    $stmt_check = $conn->prepare("SELECT BBGH_ID FROM bienbangiaohang WHERE PhieuXuatKhoID = ?");
    $stmt_check->bind_param("i", $phieu_xuat_kho_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $stmt_check->bind_result($existing_bbgh_id);
        $stmt_check->fetch();
        $stmt_check->close();
        echo json_encode(['success' => false, 'message' => 'Biên bản giao hàng đã tồn tại cho phiếu xuất này.', 'bbghID' => $existing_bbgh_id]);
        $conn->rollback();
        exit;
    }
    $stmt_check->close();

    // --- 2. Lấy thông tin cần thiết từ YCSX và Báo giá ---
    $stmt_info = $conn->prepare("
        SELECT
            d.SoYCSX,
            b.BaoGiaID,
            b.TenCongTy,
            b.DiaChiGiaoHang,
            b.TenDuAn,
            b.NguoiNhan,
            b.SoDiDongKhach
        FROM donhang d
        JOIN baogia b ON d.BaoGiaID = b.BaoGiaID
        WHERE d.YCSX_ID = ?
    ");
    $stmt_info->bind_param("i", $ycsx_id);
    $stmt_info->execute();
    $result = $stmt_info->get_result();
    $order_info = $result->fetch_assoc();
    $stmt_info->close();

    if (!$order_info) {
        throw new Exception("Không tìm thấy thông tin đơn hàng (YCSX).");
    }

    // --- 3. Tạo Số BBGH mới ---
    $currentYear = date('y');
    $prefix = "BBGH-3i-{$currentYear}/";
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM bienbangiaohang WHERE SoBBGH LIKE ?");
    $search_prefix = $prefix . '%';
    $stmt_count->bind_param("s", $search_prefix);
    $stmt_count->execute();
    $stmt_count->bind_result($count);
    $stmt_count->fetch();
    $stmt_count->close();
    $newNumber = $count + 1;
    $soBBGH = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

    // --- 4. Insert vào bảng bienbangiaohang ---
    $stmt_bbgh = $conn->prepare("
        INSERT INTO bienbangiaohang (
            YCSX_ID, PhieuXuatKhoID, BaoGiaID, SoBBGH, NgayTao, TenCongTy, DiaChiGiaoHang,
            NguoiNhanHang, SoDienThoaiNhanHang, DuAn, TrangThai
        ) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, 'Mới tạo')
    ");
    $stmt_bbgh->bind_param("iiissssss",
        $ycsx_id,
        $phieu_xuat_kho_id,
        $order_info['BaoGiaID'],
        $soBBGH,
        $order_info['TenCongTy'],
        $order_info['DiaChiGiaoHang'],
        $order_info['NguoiNhan'],
        $order_info['SoDiDongKhach'],
        $order_info['TenDuAn']
    );

    if (!$stmt_bbgh->execute()) {
        throw new Exception("Lỗi khi tạo biên bản giao hàng: " . $stmt_bbgh->error);
    }
    $bbgh_id = $conn->insert_id;
    $stmt_bbgh->close();

    // --- 5. Lấy chi tiết từ ChiTiet_PhieuXuatKho và sao chép vào ChiTiet_BienBanGiaoHang ---
    // Giả định rằng bảng ChiTiet_PhieuXuatKho đã có cột 'TaiSo' và 'GhiChu'
    $stmt_items = $conn->prepare("
        SELECT
            ctpxk.SanPhamID,
            ctpxk.SoLuongThucXuat,
            ctpxk.DonViTinh,
            ctpxk.TaiSo,
            ctpxk.GhiChu,
            sp.MaHang,
            sp.TenSanPham,
            sp.ID_ThongSo,
            sp.DoDay,
            sp.BanRong,
            loai.TenLoai
        FROM
            chitiet_phieuxuatkho ctpxk
        JOIN
            sanpham sp ON ctpxk.SanPhamID = sp.SanPhamID
        LEFT JOIN
            loaisanpham loai ON sp.LoaiID = loai.LoaiID
        WHERE
            ctpxk.PhieuXuatKhoID = ?
        ORDER BY loai.TenLoai, sp.TenSanPham
    ");
    $stmt_items->bind_param("i", $phieu_xuat_kho_id);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();

    $stmt_insert_ctbbgh = $conn->prepare("
        INSERT INTO chitietbienbangiaohang (
            BBGH_ID, TenNhom, SanPhamID, MaHang, TenSanPham, ID_ThongSo,
            DoDay, BanRong, DonViTinh, SoLuong, SoThung, GhiChu, ThuTuHienThi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $item_order = 0;
    while ($item = $items_result->fetch_assoc()) {
        $item_order++;
        $tenNhom = $item['TenLoai'] ?? 'Nhóm chung';
        $soThung = $item['TaiSo'] ?? ''; // Sử dụng cột 'TaiSo' từ PXK cho 'SoThung' trong BBGH

        // =================================================================
        // ĐÂY LÀ DÒNG ĐÃ SỬA LỖI
        // Chuỗi type phải là "isiissssssisi" (13 ký tự) để khớp với 13 biến
        // =================================================================
        $stmt_insert_ctbbgh->bind_param("isiissssssisi",
            $bbgh_id,
            $tenNhom,
            $item['SanPhamID'],
            $item['MaHang'],
            $item['TenSanPham'],
            $item['ID_ThongSo'],
            $item['DoDay'],
            $item['BanRong'],
            $item['DonViTinh'],
            $item['SoLuongThucXuat'],
            $soThung,
            $item['GhiChu'],
            $item_order
        );

        if (!$stmt_insert_ctbbgh->execute()) {
            throw new Exception("Lỗi khi chèn chi tiết BBGH: " . $stmt_insert_ctbbgh->error);
        }
    }
    $stmt_items->close();
    $stmt_insert_ctbbgh->close();

    // --- 6. Cập nhật PhieuXuatKho.BBGH_ID ---
    $stmt_update_pxk = $conn->prepare("UPDATE phieuxuatkho SET BBGH_ID = ? WHERE PhieuXuatKhoID = ?");
    $stmt_update_pxk->bind_param("ii", $bbgh_id, $phieu_xuat_kho_id);
    if (!$stmt_update_pxk->execute()) {
        throw new Exception("Lỗi khi cập nhật PhieuXuatKho.BBGH_ID: " . $stmt_update_pxk->error);
    }
    $stmt_update_pxk->close();

    // --- Hoàn tất ---
    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Tạo biên bản giao hàng thành công!',
        'soBBGH' => $soBBGH,
        'bbghID' => $bbgh_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Lỗi khi tạo BBGH: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

$conn->close();
?>
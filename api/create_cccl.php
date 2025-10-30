<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $phieu_xuat_kho_id = $data['phieu_xuat_kho_id'] ?? null;
    $nguoi_lap_id = $_SESSION['user_id'] ?? null;

    if (!$phieu_xuat_kho_id) {
        echo json_encode(['success' => false, 'message' => 'Missing PhieuXuatKhoID.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Get PXK and related order/customer info
        $stmt = $conn->prepare("
            SELECT
                pxk.SoPhieuXuat,
                dh.YCSX_ID,
                bg.TenCongTy,
                bg.DiaChiKhach,
                bg.TenDuAn
            FROM
                PhieuXuatKho pxk
            JOIN
                DonHang dh ON pxk.YCSX_ID = dh.YCSX_ID
            JOIN
                BaoGia bg ON dh.BaoGiaID = bg.BaoGiaID
            WHERE
                pxk.PhieuXuatKhoID = ?
        ");
        $stmt->bind_param("i", $phieu_xuat_kho_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pxk_info = $result->fetch_assoc();
        $stmt->close();

        if (!$pxk_info) {
            throw new Exception("Phiếu xuất kho không tồn tại.");
        }

        // 2. Check if CCCL already exists for this PhieuXuatKhoID
        $stmt_check = $conn->prepare("SELECT CCCL_ID FROM ChungChi_ChatLuong WHERE PhieuXuatKhoID = ?");
        $stmt_check->bind_param("i", $phieu_xuat_kho_id);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $stmt_check->bind_result($existing_cccl_id);
            $stmt_check->fetch();
            $stmt_check->close();
            echo json_encode(['success' => false, 'message' => 'Chứng chỉ chất lượng đã tồn tại cho phiếu xuất này.', 'ccclID' => $existing_cccl_id]);
            $conn->rollback();
            exit;
        }
        $stmt_check->close();

        // 3. Generate SoCCCL
        $currentYear = date('Y');
        $prefix = "CCCL-{$currentYear}-";
        $stmt_count = $conn->prepare("SELECT COUNT(*) FROM ChungChi_ChatLuong WHERE YEAR(NgayCap) = ?");
        $stmt_count->bind_param("i", $currentYear);
        $stmt_count->execute();
        $stmt_count->bind_result($count);
        $stmt_count->fetch();
        $stmt_count->close();
        $newNumber = $count + 1;
        $soCCCL = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        // 4. Insert into ChungChi_ChatLuong
        $stmt_cccl = $conn->prepare("
            INSERT INTO ChungChi_ChatLuong (
                PhieuXuatKhoID, SoCCCL, NgayCap, TenCongTyKhach, DiaChiKhach, TenDuAn,
                NguoiLap, TieuChuanApDung
            ) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, 'TCVN XXXX')
        ");
        $stmt_cccl->bind_param("issssis",
            $phieu_xuat_kho_id, $soCCCL, $pxk_info['TenCongTy'], $pxk_info['DiaChiKhach'],
            $pxk_info['TenDuAn'], $nguoi_lap_id
        );
        if (!$stmt_cccl->execute()) {
            throw new Exception("Error inserting ChungChi_ChatLuong: " . $stmt_cccl->error);
        }
        $cccl_id = $conn->insert_id;
        $stmt_cccl->close();

        // 5. Copy items from ChiTiet_PhieuXuatKho to ChiTiet_ChungChi_ChatLuong
        $stmt_items = $conn->prepare("
            SELECT
                ctpxk.SanPhamID,
                sp.MaHang,
                sp.TenSanPham,
                ctpxk.SoLuongThucXuat AS SoLuong,
                ctpxk.DonViTinh,
                ctpxk.TaiSo,
                ctpxk.GhiChu
            FROM
                ChiTiet_PhieuXuatKho ctpxk
            JOIN
                SanPham sp ON ctpxk.SanPhamID = sp.SanPhamID
            WHERE
                ctpxk.PhieuXuatKhoID = ?
            ORDER BY sp.TenSanPham
        ");
        $stmt_items->bind_param("i", $phieu_xuat_kho_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        $stmt_insert_ctcccl = $conn->prepare("
            INSERT INTO ChiTiet_ChungChi_ChatLuong (
                CCCL_ID, SanPhamID, MaHang, TenSanPham, SoLuong, DonViTinh, TaiSo,
                TieuChuanDatDuoc, KetQuaKiemTra, GhiChuChiTiet, ThuTuHienThi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Đạt', NULL, ?, ?)
        ");

        $item_order = 0;
        while ($row = $items_result->fetch_assoc()) {
            $sanpham_id = $row['SanPhamID'];
            $ma_hang = $row['MaHang'];
            $ten_san_pham = $row['TenSanPham'];
            $so_luong = $row['SoLuong'];
            $don_vi_tinh = $row['DonViTinh'];
            $tai_so = $row['TaiSo'];
            $ghi_chu_chi_tiet = $row['GhiChu'];
            $item_order++;

            $stmt_insert_ctcccl->bind_param("iisssssssis",
                $cccl_id, $sanpham_id, $ma_hang, $ten_san_pham, $so_luong, $don_vi_tinh, $tai_so,
                $ghi_chu_chi_tiet, $item_order
            );
            if (!$stmt_insert_ctcccl->execute()) {
                throw new Exception("Error inserting ChiTiet_ChungChi_ChatLuong: " . $stmt_insert_ctcccl->error);
            }
        }
        $stmt_items->close();
        $stmt_insert_ctcccl->close();

        // 6. Update PhieuXuatKho.CCCL_ID
        $stmt_update_pxk = $conn->prepare("UPDATE PhieuXuatKho SET CCCL_ID = ? WHERE PhieuXuatKhoID = ?");
        $stmt_update_pxk->bind_param("ii", $cccl_id, $phieu_xuat_kho_id);
        if (!$stmt_update_pxk->execute()) {
            throw new Exception("Error updating PhieuXuatKho CCCL_ID: " . $stmt_update_pxk->error);
        }
        $stmt_update_pxk->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Tạo chứng chỉ chất lượng thành công!', 'soCCCL' => $soCCCL, 'ccclID' => $cccl_id]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error creating CCCL: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
$conn->close();
?>
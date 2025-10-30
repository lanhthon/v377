<?php
// File: api/get_bbgh_list.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => []];

try {
    $sql = "SELECT 
                b.BBGH_ID,
                b.SoBBGH,
                d.SoYCSX,
                b.TenCongTy,
                b.NgayTao,
                b.TrangThai
            FROM bienbangiaohang b
            JOIN donhang d ON b.YCSX_ID = d.YCSX_ID
            ORDER BY b.NgayTao DESC, b.BBGH_ID DESC";
    
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $response['success'] = true;
    $response['data'] = $data;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);

// =======================================================
// File: api/get_bbgh_details.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'header' => null, 'items' => []];
$bbgh_id = $_GET['bbgh_id'] ?? 0;

if ($bbgh_id) {
    try {
        // Get header info
        $stmt_header = $conn->prepare("
            SELECT
                b.SoBBGH, b.NgayTao, b.TenCongTy, b.DiaChiGiaoHang, b.NguoiNhanHang, b.DuAn,
                d.SoYCSX,
                u.HoTen as TenNguoiLap
            FROM bienbangiaohang b
            JOIN phieuxuatkho pxk ON b.PhieuXuatKhoID = pxk.PhieuXuatKhoID
            JOIN donhang d ON b.YCSX_ID = d.YCSX_ID
            LEFT JOIN nguoidung u ON pxk.NguoiTaoID = u.UserID
            WHERE b.BBGH_ID = ?
        ");
        $stmt_header->bind_param("i", $bbgh_id);
        $stmt_header->execute();
        $response['header'] = $stmt_header->get_result()->fetch_assoc();
        $stmt_header->close();

        // Get items info
        $stmt_items = $conn->prepare("
            SELECT MaHang, TenSanPham, DonViTinh, SoLuongThucXuat, TaiSo, GhiChu 
            FROM chitiet_phieuxuatkho 
            WHERE PhieuXuatKhoID = (SELECT PhieuXuatKhoID FROM bienbangiaohang WHERE BBGH_ID = ?)
            ORDER BY ChiTietPXK_ID
        ");
        $stmt_items->bind_param("i", $bbgh_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while($row = $result_items->fetch_assoc()) {
            $response['items'][] = $row;
        }
        $stmt_items->close();

        $response['success'] = true;

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
} else {
    $response['message'] = "ID không hợp lệ.";
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);


// =======================================================
// File: api/get_cccl_details.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'header' => null, 'items' => []];
$cccl_id = $_GET['cccl_id'] ?? 0;

if ($cccl_id) {
    try {
        // Get header info
        $stmt_header = $conn->prepare("
            SELECT 
                c.SoCCCL, c.NgayCap, c.TenCongTyKhach, c.DiaChiKhach, c.TenDuAn, c.TieuChuanApDung,
                p.SoPhieuXuat,
                u.HoTen as TenNguoiLap,
                c.NguoiKiemTra
            FROM chungchi_chatluong c
            JOIN phieuxuatkho p ON c.PhieuXuatKhoID = p.PhieuXuatKhoID
            LEFT JOIN nguoidung u ON c.NguoiLap = u.UserID
            WHERE c.CCCL_ID = ?
        ");
        $stmt_header->bind_param("i", $cccl_id);
        $stmt_header->execute();
        $response['header'] = $stmt_header->get_result()->fetch_assoc();
        $stmt_header->close();

        // Get items info
        $stmt_items = $conn->prepare("
            SELECT MaHang, TenSanPham, SoLuong, DonViTinh, TaiSo, TieuChuanDatDuoc, GhiChuChiTiet 
            FROM chitiet_chungchi_chatluong 
            WHERE CCCL_ID = ? 
            ORDER BY ThuTuHienThi, ChiTietCCCL_ID
        ");
        $stmt_items->bind_param("i", $cccl_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while($row = $result_items->fetch_assoc()) {
            $response['items'][] = $row;
        }
        $stmt_items->close();
        
        $response['success'] = true;

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
} else {
    $response['message'] = "ID không hợp lệ.";
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);


// =======================================================
// File: api/create_or_get_bbgh.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$pxk_id = $_POST['pxk_id'] ?? 0;
$response = ['success' => false, 'bbgh_id' => null, 'message' => ''];

if (!$pxk_id) {
    $response['message'] = 'Thiếu ID Phiếu xuất kho.';
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();
try {
    // Check if BBGH already exists
    $stmt_check = $conn->prepare("SELECT BBGH_ID FROM bienbangiaohang WHERE PhieuXuatKhoID = ?");
    $stmt_check->bind_param("i", $pxk_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $existing = $result_check->fetch_assoc();
        $response['success'] = true;
        $response['bbgh_id'] = $existing['BBGH_ID'];
        $conn->commit();
    } else {
        // Create new BBGH
        $stmt_info = $conn->prepare("
            SELECT dh.YCSX_ID, dh.BaoGiaID, dh.TenCongTy, dh.DiaChiGiaoHang, dh.NguoiNhan, dh.TenDuAn
            FROM phieuxuatkho pxk
            JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID
            WHERE pxk.PhieuXuatKhoID = ?
        ");
        $stmt_info->bind_param("i", $pxk_id);
        $stmt_info->execute();
        $info = $stmt_info->get_result()->fetch_assoc();
        $stmt_info->close();

        $so_bbgh = "BBGH-" . date('ymd') . "-" . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        
        $stmt_create = $conn->prepare("
            INSERT INTO bienbangiaohang (YCSX_ID, PhieuXuatKhoID, BaoGiaID, SoBBGH, NgayTao, TenCongTy, DiaChiGiaoHang, NguoiNhanHang, DuAn)
            VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?)
        ");
        $stmt_create->bind_param("iiisssss", $info['YCSX_ID'], $pxk_id, $info['BaoGiaID'], $so_bbgh, $info['TenCongTy'], $info['DiaChiGiaoHang'], $info['NguoiNhan'], $info['TenDuAn']);
        $stmt_create->execute();
        $new_bbgh_id = $conn->insert_id;
        $stmt_create->close();
        
        $response['success'] = true;
        $response['bbgh_id'] = $new_bbgh_id;
        $conn->commit();
    }
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}
$conn->close();
echo json_encode($response);


// =======================================================
// File: api/create_or_get_cccl.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$pxk_id = $_POST['pxk_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? null;
$response = ['success' => false, 'cccl_id' => null, 'message' => ''];

if (!$pxk_id || !$user_id) {
    $response['message'] = 'Thiếu ID Phiếu xuất kho hoặc thông tin người dùng.';
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();
try {
    // Check if CCCL already exists
    $stmt_check = $conn->prepare("SELECT CCCL_ID FROM chungchi_chatluong WHERE PhieuXuatKhoID = ?");
    $stmt_check->bind_param("i", $pxk_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $existing = $result_check->fetch_assoc();
        $response['success'] = true;
        $response['cccl_id'] = $existing['CCCL_ID'];
        $conn->commit();
    } else {
        // Create new CCCL
        $stmt_info = $conn->prepare("
            SELECT dh.TenCongTy, dh.DiaChiGiaoHang, dh.TenDuAn
            FROM phieuxuatkho pxk
            JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID
            WHERE pxk.PhieuXuatKhoID = ?
        ");
        $stmt_info->bind_param("i", $pxk_id);
        $stmt_info->execute();
        $info = $stmt_info->get_result()->fetch_assoc();
        $stmt_info->close();

        $so_cccl = "CCCL-" . date('ymd') . "-" . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

        $stmt_create_cccl = $conn->prepare("
            INSERT INTO chungchi_chatluong (PhieuXuatKhoID, SoCCCL, NgayCap, TenCongTyKhach, DiaChiKhach, TenDuAn, NguoiLap)
            VALUES (?, ?, CURDATE(), ?, ?, ?, ?)
        ");
        $stmt_create_cccl->bind_param("issssi", $pxk_id, $so_cccl, $info['TenCongTy'], $info['DiaChiGiaoHang'], $info['TenDuAn'], $user_id);
        $stmt_create_cccl->execute();
        $new_cccl_id = $conn->insert_id;
        $stmt_create_cccl->close();

        // Copy items from phieuxuatkho to chungchi_chatluong
        $stmt_copy_items = $conn->prepare("
            INSERT INTO chitiet_chungchi_chatluong (CCCL_ID, SanPhamID, MaHang, TenSanPham, SoLuong, DonViTinh, TaiSo, GhiChuChiTiet)
            SELECT ?, SanPhamID, MaHang, TenSanPham, SoLuongThucXuat, DonViTinh, TaiSo, GhiChu
            FROM chitiet_phieuxuatkho WHERE PhieuXuatKhoID = ?
        ");
        $stmt_copy_items->bind_param("ii", $new_cccl_id, $pxk_id);
        $stmt_copy_items->execute();
        $stmt_copy_items->close();

        $response['success'] = true;
        $response['cccl_id'] = $new_cccl_id;
        $conn->commit();
    }
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}
$conn->close();
echo json_encode($response);
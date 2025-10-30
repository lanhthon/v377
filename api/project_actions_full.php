<?php
// api/project_actions_full.php

// Bật báo cáo lỗi để dễ dàng gỡ lỗi (chỉ nên bật trong môi trường phát triển)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
    exit();
}

$action = $data['action'];

try {
    // Bắt đầu transaction để đảm bảo tất cả các thao tác đều thành công
    $conn->begin_transaction();

    switch ($action) {
        case 'add':
            add_project($conn, $data);
            break;
        case 'update':
            update_project($conn, $data);
            break;
        case 'delete':
            delete_project($conn, $data);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Hành động không xác định.']);
            break;
    }

    // Nếu không có lỗi, commit transaction
    $conn->commit();

} catch (Exception $e) {
    // Nếu có lỗi, rollback lại
    $conn->rollback();
    http_response_code(500);
    // *** CẢI TIẾN: Trả về thông báo lỗi chi tiết hơn ***
    $errorMessage = 'Lỗi: ' . $e->getMessage() . ' tại file ' . $e->getFile() . ' dòng ' . $e->getLine();
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}

$conn->close();

function get_param($data, $key, $default = null) {
    return isset($data[$key]) && $data[$key] !== '' ? $data[$key] : $default;
}

// Hàm xử lý hạng mục
function handle_hang_muc($conn, $duAnID, $hangMucData) {
    // 1. Xóa tất cả các liên kết hạng mục cũ của dự án này
    $stmt_delete = $conn->prepare("DELETE FROM DuAn_HangMuc WHERE DuAnID = ?");
    if (!$stmt_delete) throw new Exception("Prepare delete failed: " . $conn->error);
    $stmt_delete->bind_param("i", $duAnID);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 2. Nếu không có hạng mục nào được gửi lên, dừng lại ở đây
    if (empty($hangMucData) || !is_array($hangMucData)) {
        return;
    }

    // 3. Xử lý các hạng mục mới
    $stmt_find_hm = $conn->prepare("SELECT HangMucID FROM HangMuc WHERE TenHangMuc = ?");
    $stmt_insert_hm = $conn->prepare("INSERT INTO HangMuc (TenHangMuc) VALUES (?)");
    $stmt_link_hm = $conn->prepare("INSERT INTO DuAn_HangMuc (DuAnID, HangMucID) VALUES (?, ?)");

    if (!$stmt_find_hm || !$stmt_insert_hm || !$stmt_link_hm) {
        throw new Exception("Prepare statements for hang muc failed: " . $conn->error);
    }

    foreach ($hangMucData as $tenHangMuc) {
        $tenHangMuc = trim($tenHangMuc);
        if (empty($tenHangMuc)) continue;

        // Tìm ID của hạng mục
        $stmt_find_hm->bind_param("s", $tenHangMuc);
        $stmt_find_hm->execute();
        $result = $stmt_find_hm->get_result();
        $hangMucID = null;

        if ($result->num_rows > 0) {
            $hangMucID = $result->fetch_assoc()['HangMucID'];
        } else {
            // Nếu không tồn tại, thêm mới vào bảng HangMuc
            $stmt_insert_hm->bind_param("s", $tenHangMuc);
            if (!$stmt_insert_hm->execute()) {
                 // Bỏ qua lỗi trùng lặp nếu có (do xử lý đồng thời)
                if ($conn->errno != 1062) {
                    throw new Exception("Insert new hang muc failed: " . $stmt_insert_hm->error);
                }
                // Nếu bị trùng, thử tìm lại ID
                $stmt_find_hm->execute();
                $result = $stmt_find_hm->get_result();
                if($result->num_rows > 0) {
                    $hangMucID = $result->fetch_assoc()['HangMucID'];
                } else {
                     throw new Exception("Could not retrieve new hang muc ID after duplicate error.");
                }
            } else {
                 $hangMucID = $stmt_insert_hm->insert_id;
            }
        }
        
        // Tạo liên kết trong bảng DuAn_HangMuc
        if ($hangMucID) {
            $stmt_link_hm->bind_param("ii", $duAnID, $hangMucID);
            if (!$stmt_link_hm->execute() && $conn->errno != 1062) { // Bỏ qua lỗi trùng lặp
                 throw new Exception("Link hang muc to project failed: " . $stmt_link_hm->error);
            }
        }
    }
    $stmt_find_hm->close();
    $stmt_insert_hm->close();
    $stmt_link_hm->close();
}


function add_project($conn, $data) {
    // Bỏ cột HangMucBaoGia ra khỏi câu lệnh INSERT
    $sql = "INSERT INTO DuAn (MaDuAn, TenDuAn, DiaChi, TinhThanh, LoaiHinh, GiaTriDauTu, NgayKhoiCong, NgayHoanCong, ChuDauTu, TongThau, ThauMEP, DauMoiLienHe, GiaTriDuKien, TienDoLamViec, KetQua, SalePhuTrach) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('Lỗi prepare statement: ' . $conn->error);
    
    // SỬA LỖI: Gán giá trị vào biến trước khi bind để tránh lỗi "pass by reference"
    $maDuAn = get_param($data, 'MaDuAn');
    $tenDuAn = get_param($data, 'TenDuAn');
    $diaChi = get_param($data, 'DiaChi');
    $tinhThanh = get_param($data, 'TinhThanh');
    $loaiHinh = get_param($data, 'LoaiHinh');
    $giaTriDauTu = get_param($data, 'GiaTriDauTu');
    $ngayKhoiCong = get_param($data, 'NgayKhoiCong');
    $ngayHoanCong = get_param($data, 'NgayHoanCong');
    $chuDauTu = get_param($data, 'ChuDauTu');
    $tongThau = get_param($data, 'TongThau');
    $thauMEP = get_param($data, 'ThauMEP');
    $dauMoiLienHe = get_param($data, 'DauMoiLienHe');
    $giaTriDuKien = get_param($data, 'GiaTriDuKien');
    $tienDoLamViec = get_param($data, 'TienDoLamViec');
    $ketQua = get_param($data, 'KetQua');
    $salePhuTrach = get_param($data, 'SalePhuTrach');

    // SỬA LỖI: Chuỗi type phải có 16 ký tự (thêm 1 's') để khớp với 16 tham số
    $stmt->bind_param("ssssssssssssdsss",
        $maDuAn, $tenDuAn, $diaChi, $tinhThanh, $loaiHinh, $giaTriDauTu,
        $ngayKhoiCong, $ngayHoanCong, $chuDauTu, $tongThau, $thauMEP,
        $dauMoiLienHe, $giaTriDuKien, $tienDoLamViec, $ketQua, $salePhuTrach
    );

    if ($stmt->execute()) {
        $duAnID = $stmt->insert_id;
        handle_hang_muc($conn, $duAnID, get_param($data, 'HangMucBaoGia', []));
        echo json_encode(['success' => true, 'message' => 'Thêm dự án thành công!']);
    } else {
        if ($conn->errno == 1062) {
            throw new Exception('Lỗi: Mã dự án "' . get_param($data, 'MaDuAn') . '" đã tồn tại.');
        } else {
            throw new Exception('Lỗi thực thi: ' . $stmt->error);
        }
    }
    $stmt->close();
}

function update_project($conn, $data) {
    // Bỏ cột HangMucBaoGia ra khỏi câu lệnh UPDATE
    $sql = "UPDATE DuAn SET TenDuAn=?, DiaChi=?, TinhThanh=?, LoaiHinh=?, GiaTriDauTu=?, NgayKhoiCong=?, NgayHoanCong=?, ChuDauTu=?, TongThau=?, ThauMEP=?, DauMoiLienHe=?, GiaTriDuKien=?, TienDoLamViec=?, KetQua=?, SalePhuTrach=? WHERE DuAnID=?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('Lỗi prepare statement: ' . $conn->error);

    // SỬA LỖI: Gán giá trị vào biến trước khi bind để tránh lỗi "pass by reference"
    $tenDuAn = get_param($data, 'TenDuAn');
    $diaChi = get_param($data, 'DiaChi');
    $tinhThanh = get_param($data, 'TinhThanh');
    $loaiHinh = get_param($data, 'LoaiHinh');
    $giaTriDauTu = get_param($data, 'GiaTriDauTu');
    $ngayKhoiCong = get_param($data, 'NgayKhoiCong');
    $ngayHoanCong = get_param($data, 'NgayHoanCong');
    $chuDauTu = get_param($data, 'ChuDauTu');
    $tongThau = get_param($data, 'TongThau');
    $thauMEP = get_param($data, 'ThauMEP');
    $dauMoiLienHe = get_param($data, 'DauMoiLienHe');
    $giaTriDuKien = get_param($data, 'GiaTriDuKien');
    $tienDoLamViec = get_param($data, 'TienDoLamViec');
    $ketQua = get_param($data, 'KetQua');
    $salePhuTrach = get_param($data, 'SalePhuTrach');
    $duAnID = get_param($data, 'DuAnID');

    // SỬA LỖI: Chuỗi type phải có 16 ký tự (thêm 1 's') để khớp với 16 tham số
    $stmt->bind_param("sssssssssssdsssi",
        $tenDuAn, $diaChi, $tinhThanh, $loaiHinh, $giaTriDauTu, $ngayKhoiCong,
        $ngayHoanCong, $chuDauTu, $tongThau, $thauMEP, $dauMoiLienHe,
        $giaTriDuKien, $tienDoLamViec, $ketQua, $salePhuTrach, $duAnID
    );

    if ($stmt->execute()) {
        handle_hang_muc($conn, $duAnID, get_param($data, 'HangMucBaoGia', []));
        echo json_encode(['success' => true, 'message' => 'Cập nhật dự án thành công!']);
    } else {
        throw new Exception('Lỗi thực thi: ' . $stmt->error);
    }
    $stmt->close();
}

function delete_project($conn, $data) {
    // Transaction đã được xử lý ở ngoài, không cần xóa DuAn_HangMuc riêng vì đã có ON DELETE CASCADE
    $stmt = $conn->prepare("DELETE FROM DuAn WHERE DuAnID = ?");
    if (!$stmt) throw new Exception('Lỗi prepare statement: ' . $conn->error);

    // SỬA LỖI: Gán giá trị vào biến trước khi bind
    $duAnID = get_param($data, 'DuAnID');
    $stmt->bind_param("i", $duAnID);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Xóa dự án thành công!']);
    } else {
        throw new Exception('Lỗi thực thi: ' . $stmt->error);
    }
    $stmt->close();
}
?>

<?php
// api/get_projects_full.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

// Sửa đổi câu lệnh SQL để JOIN và lấy thêm bình luận
$sql = "SELECT 
            da.DuAnID, da.MaDuAn, da.TenDuAn, da.DiaChi, da.TinhThanh, da.LoaiHinh, da.GiaTriDauTu, 
            da.NgayKhoiCong, da.NgayHoanCong, da.ChuDauTu, da.TongThau, da.ThauMEP, 
            da.DauMoiLienHe, 
            (SELECT GROUP_CONCAT(hm.TenHangMuc SEPARATOR ', ') 
             FROM DuAn_HangMuc dhm
             JOIN HangMuc hm ON dhm.HangMucID = hm.HangMucID
             WHERE dhm.DuAnID = da.DuAnID
            ) AS HangMucBaoGia,
            (SELECT GROUP_CONCAT(
                CONCAT(cmt.NguoiBinhLuan, ' (', DATE_FORMAT(cmt.NgayBinhLuan, '%d/%m/%Y %H:%i'), '): ', cmt.NoiDung) 
                SEPARATOR '\n'
             ) 
             FROM DuAn_Comment cmt
             WHERE cmt.DuAnID = da.DuAnID
            ) AS Comments,
            da.GiaTriDuKien, da.TienDoLamViec, 
            da.KetQua, da.SalePhuTrach 
        FROM DuAn da
        ORDER BY da.DuAnID DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn SQL: ' . $conn->error]);
    $conn->close();
    exit();
}

$projects = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        foreach ($row as $key => $value) {
            if ($value === '') {
                $row[$key] = null;
            }
        }
        $projects[] = $row;
    }
}

echo json_encode($projects);

$conn->close();
?>

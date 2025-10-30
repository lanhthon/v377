<?php
// api/get_danhsach_nhapkho.php

header('Content-Type: application/json');

// --- KẾT NỐI CƠ SỞ DỮ LIỆU ---
$servername = "127.0.0.1";
$username = "root"; 
$password = ""; 
$dbname = "baogia_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối CSDL: ' . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4");

// --- TRUY VẤN DỮ LIỆU ---
try {
    $sql = "
        SELECT 
            pnk.PhieuNhapKhoID,
            pnk.SoPhieuNhapKho,
            pnk.NgayNhap,
            pnk.TongTien,
            ncc.TenNhaCungCap 
        FROM 
            phieunhapkho pnk
        LEFT JOIN 
            nhacungcap ncc ON pnk.NhaCungCapID = ncc.NhaCungCapID
        ORDER BY 
            pnk.NgayNhap DESC, pnk.PhieuNhapKhoID DESC
    ";

    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("Lỗi truy vấn SQL: " . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
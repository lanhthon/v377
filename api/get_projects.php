
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

// Câu lệnh SQL để lấy tất cả các cột từ bảng DuAn
// Đảm bảo có cột DiaChi
$sql = "SELECT 
            DuAnID, MaDuAn, TenDuAn, DiaChi, LoaiHinh, GiaTriDauTu, 
            NgayKhoiCong, NgayHoanCong, ChuDauTu, TongThau, ThauMEP, 
            DauMoiLienHe, HangMucBaoGia, GiaTriDuKien, TienDoLamViec, 
            KetQua, SalePhuTrach 
        FROM DuAn 
        ORDER BY DuAnID DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn SQL: ' . $conn->error]);
    $conn->close();
    exit();
}

$projects = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Gán giá trị null cho các trường rỗng để đảm bảo tính nhất quán trong JSON
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

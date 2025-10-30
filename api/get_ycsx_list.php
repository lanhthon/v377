<?php
// File: api/get_ycsx_list.php (Phiên bản đã sửa)

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$sql = "
    SELECT
        y.YCSX_ID,
        y.SoYCSX,
        y.TrangThai,
        y.CBH_ID,        -- QUAN TRỌNG: Lấy cả CBH_ID
        y.BBGH_ID,       -- QUAN TRỌNG: Và BBGH_ID
        DATE_FORMAT(y.NgayTao, '%d/%m/%Y') AS NgayTaoFormatted,
        b.TenCongTy
    FROM
        yeucausanxuat y
    JOIN
        baogia b ON y.BaoGiaID = b.BaoGiaID
    ORDER BY
        y.YCSX_ID DESC
";

$result = $conn->query($sql);

$list = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Chuyển đổi ID sang kiểu số để đảm bảo JavaScript nhận đúng
        if ($row['CBH_ID'] !== null) {
            $row['CBH_ID'] = (int)$row['CBH_ID'];
        }
        if ($row['BBGH_ID'] !== null) {
            $row['BBGH_ID'] = (int)$row['BBGH_ID'];
        }
        $list[] = $row;
    }
}

$conn->close();

echo json_encode($list);
?>
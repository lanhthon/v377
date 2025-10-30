<?php
// api/get_quote_creators.php
header('Content-Type: application/json');
require_once '../config/database.php';

$conn->set_charset("utf8mb4");

$sql = "SELECT DISTINCT
            nd.UserID,
            nd.HoTen
        FROM
            baogia bg
        JOIN
            nguoidung nd ON bg.UserID = nd.UserID
        WHERE
            nd.HoTen IS NOT NULL AND nd.HoTen != ''
        ORDER BY
            nd.HoTen ASC";

$result = $conn->query($sql);

$creators = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $creators[] = $row;
    }
}

echo json_encode(['success' => true, 'creators' => $creators]);

$conn->close();
?>
<?php
// api/get_hangmuc.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$sql = "SELECT HangMucID, TenHangMuc FROM HangMuc ORDER BY TenHangMuc ASC";
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn SQL: ' . $conn->error]);
    $conn->close();
    exit();
}

$hangmucs = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $hangmucs[] = $row;
    }
}

echo json_encode(['success' => true, 'data' => $hangmucs]);

$conn->close();
?>

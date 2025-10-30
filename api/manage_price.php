<?php
// File: api/manage_price.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'add' || $action === 'update') {
    $maCoChe = $data['MaCoChe'] ?? '';
    $tenCoChe = $data['TenCoChe'] ?? '';
    // Make sure to convert percentage to a proper decimal
    $phanTramDieuChinh = isset($data['PhanTramDieuChinh']) ? floatval($data['PhanTramDieuChinh']) : 0;

    if (empty($maCoChe) || empty($tenCoChe)) {
        echo json_encode(['success' => false, 'message' => 'Mã cơ chế và Tên cơ chế không được để trống.']);
        exit;
    }

    if ($action === 'add') {
        $sql = "INSERT INTO cochegia (MaCoChe, TenCoChe, PhanTramDieuChinh) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssd", $maCoChe, $tenCoChe, $phanTramDieuChinh);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Thêm cơ chế giá thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm: ' . $stmt->error]);
        }
    } else { // update
        $coCheGiaID = $data['CoCheGiaID'] ?? 0;
        if (empty($coCheGiaID)) {
             echo json_encode(['success' => false, 'message' => 'ID Cơ chế giá không hợp lệ.']);
             exit;
        }
        $sql = "UPDATE cochegia SET MaCoChe = ?, TenCoChe = ?, PhanTramDieuChinh = ? WHERE CoCheGiaID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdi", $maCoChe, $tenCoChe, $phanTramDieuChinh, $coCheGiaID);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật cơ chế giá thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . $stmt->error]);
        }
    }
    $stmt->close();

} elseif ($action === 'delete') {
    $coCheGiaID = $data['CoCheGiaID'] ?? 0;
     if (empty($coCheGiaID)) {
        echo json_encode(['success' => false, 'message' => 'ID Cơ chế giá không hợp lệ.']);
        exit;
    }
    $sql = "DELETE FROM cochegia WHERE CoCheGiaID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $coCheGiaID);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Xóa cơ chế giá thành công!']);
    } else {
         echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
}

$conn->close();
?>
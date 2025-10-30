<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Đường dẫn tới file kết nối của bạn

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
    exit();
}

$action = $data['action'];

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

$conn->close();

function add_project($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO DuAn (MaDuAn, TenDuAn, ChuDauTu, DiaChi, GiaTri, NgayBaoGia, TrangThai) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Lỗi prepare statement: ' . $conn->error]);
        exit();
    }
    
    // Gán giá trị null nếu trống
    $giaTri = !empty($data['GiaTri']) ? $data['GiaTri'] : null;
    $ngayBaoGia = !empty($data['NgayBaoGia']) ? $data['NgayBaoGia'] : null;
    
    $stmt->bind_param("ssssdss", 
        $data['MaDuAn'], 
        $data['TenDuAn'], 
        $data['ChuDauTu'], 
        $data['DiaChi'], 
        $giaTri, 
        $ngayBaoGia, 
        $data['TrangThai']
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Thêm dự án thành công!']);
    } else {
        // Kiểm tra lỗi trùng Mã Dự Án
        if ($conn->errno == 1062) {
             echo json_encode(['success' => false, 'message' => 'Lỗi: Mã dự án "' . $data['MaDuAn'] . '" đã tồn tại.']);
        } else {
             echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $stmt->error]);
        }
    }
    $stmt->close();
}

function update_project($conn, $data) {
    $stmt = $conn->prepare("UPDATE DuAn SET TenDuAn = ?, ChuDauTu = ?, DiaChi = ?, GiaTri = ?, NgayBaoGia = ?, TrangThai = ? WHERE DuAnID = ?");
     if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Lỗi prepare statement: ' . $conn->error]);
        exit();
    }

    $giaTri = !empty($data['GiaTri']) ? $data['GiaTri'] : null;
    $ngayBaoGia = !empty($data['NgayBaoGia']) ? $data['NgayBaoGia'] : null;

    $stmt->bind_param("sssdssi", 
        $data['TenDuAn'], 
        $data['ChuDauTu'], 
        $data['DiaChi'], 
        $giaTri, 
        $ngayBaoGia, 
        $data['TrangThai'], 
        $data['DuAnID']
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật dự án thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $stmt->error]);
    }
    $stmt->close();
}

function delete_project($conn, $data) {
    $stmt = $conn->prepare("DELETE FROM DuAn WHERE DuAnID = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Lỗi prepare statement: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("i", $data['DuAnID']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Xóa dự án thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $stmt->error]);
    }
    $stmt->close();
}
?>
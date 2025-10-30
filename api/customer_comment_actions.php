<?php
// api/customer_comment_actions.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Hành động không được cung cấp.']);
    exit();
}

try {
    switch ($action) {
        case 'get_comments':
            get_comments($conn);
            break;
        case 'add_comment':
            add_comment($conn, $data);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Hành động không xác định.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}

$conn->close();

function get_comments($conn) {
    $congTyID = $_GET['CongTyID'] ?? null;
    if (!$congTyID) {
        echo json_encode(['success' => false, 'message' => 'Thiếu ID Công ty.']);
        exit();
    }

    $sql = "SELECT NguoiBinhLuan, NoiDung, NgayBinhLuan FROM CongTy_Comment WHERE CongTyID = ? ORDER BY NgayBinhLuan DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('Lỗi prepare statement: ' . $conn->error);
    
    $stmt->bind_param("i", $congTyID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'data' => $comments]);
}

function add_comment($conn, $data) {
    $congTyID = $data['CongTyID'] ?? null;
    $noiDung = $data['NoiDung'] ?? null;
    $nguoiBinhLuan = $data['NguoiBinhLuan'] ?? 'System';

    if (!$congTyID || !$noiDung) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết (ID Công ty hoặc Nội dung).']);
        exit();
    }

    $sql = "INSERT INTO CongTy_Comment (CongTyID, NguoiBinhLuan, NoiDung) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('Lỗi prepare statement: ' . $conn->error);

    $stmt->bind_param("iss", $congTyID, $nguoiBinhLuan, $noiDung);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Thêm lịch sử làm việc thành công!']);
    } else {
        throw new Exception('Lỗi thực thi: ' . $stmt->error);
    }
    $stmt->close();
}
?>

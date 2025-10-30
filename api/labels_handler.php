<?php
// api/labels_handler.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_REQUEST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'get_all':
        get_all_labels($conn);
        break;
    // --- CASE 'CREATE' ĐÃ BỊ XÓA ---
    case 'update':
        update_label($conn, $input);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();

function get_all_labels($conn) {
    $result = $conn->query("SELECT * FROM quotation_labels ORDER BY id DESC");
    $labels = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $labels]);
}

function update_label($conn, $data) {
    $id = $data['id'] ?? 0;
    $vi = $data['label_vi'] ?? '';
    $zh = $data['label_zh'] ?? '';
    $en = $data['label_en'] ?? '';

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        return;
    }

    $stmt = $conn->prepare("UPDATE quotation_labels SET label_vi = ?, label_zh = ?, label_en = ? WHERE id = ?");
    $stmt->bind_param("sssi", $vi, $zh, $en, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật nhãn thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cập nhật nhãn thất bại: ' . $stmt->error]);
    }
    $stmt->close();
}
?>
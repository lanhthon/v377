<?php
// File: api/revenue_plan.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Kiểm tra method HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRevenuePlan($conn);
            break;
        case 'POST':
            handleCreateOrUpdateRevenuePlan($conn);
            break;
        case 'PUT':
            handleUpdateRevenuePlan($conn);
            break;
        case 'DELETE':
            handleDeleteRevenuePlan($conn);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method không được hỗ trợ']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}

$conn->close();

// Lấy kế hoạch doanh thu
function handleGetRevenuePlan($conn) {
    $year = $_GET['year'] ?? date('Y');
    
    $stmt = $conn->prepare("SELECT * FROM ke_hoach_doanh_thu WHERE Nam = ?");
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        // Nếu chưa có kế hoạch cho năm này, trả về template mặc định
        $defaultPlan = [
            'Nam' => intval($year),
            'MucTieuDoanhthu' => 0,
            'MucTieuThang1' => 0, 'MucTieuThang2' => 0, 'MucTieuThang3' => 0, 'MucTieuThang4' => 0,
            'MucTieuThang5' => 0, 'MucTieuThang6' => 0, 'MucTieuThang7' => 0, 'MucTieuThang8' => 0,
            'MucTieuThang9' => 0, 'MucTieuThang10' => 0, 'MucTieuThang11' => 0, 'MucTieuThang12' => 0,
            'GhiChu' => '',
            'KeHoachID' => null
        ];
        echo json_encode(['success' => true, 'data' => $defaultPlan, 'is_new' => true]);
    }
}

// Tạo mới hoặc cập nhật kế hoạch doanh thu
function handleCreateOrUpdateRevenuePlan($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $year = $input['Nam'] ?? date('Y');
    $mucTieuDoanhthu = floatval($input['MucTieuDoanhthu'] ?? 0);
    $ghiChu = $input['GhiChu'] ?? '';
    $nguoiTao = $_SESSION['UserID'] ?? 1; // Lấy từ session hoặc mặc định
    
    // Mục tiêu từng tháng
    $mucTieuThang = [];
    for ($i = 1; $i <= 12; $i++) {
        $mucTieuThang[$i] = floatval($input["MucTieuThang$i"] ?? 0);
    }
    
    // Kiểm tra xem đã có kế hoạch cho năm này chưa
    $checkStmt = $conn->prepare("SELECT KeHoachID FROM ke_hoach_doanh_thu WHERE Nam = ?");
    $checkStmt->bind_param('i', $year);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    
    if ($existing) {
        // Cập nhật kế hoạch hiện có
        $sql = "UPDATE ke_hoach_doanh_thu SET 
                MucTieuDoanhthu = ?, 
                MucTieuThang1 = ?, MucTieuThang2 = ?, MucTieuThang3 = ?, MucTieuThang4 = ?,
                MucTieuThang5 = ?, MucTieuThang6 = ?, MucTieuThang7 = ?, MucTieuThang8 = ?,
                MucTieuThang9 = ?, MucTieuThang10 = ?, MucTieuThang11 = ?, MucTieuThang12 = ?,
                GhiChu = ?, NgayCapNhat = current_timestamp()
                WHERE Nam = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('dddddddddddddsi', 
            $mucTieuDoanhthu,
            $mucTieuThang[1], $mucTieuThang[2], $mucTieuThang[3], $mucTieuThang[4],
            $mucTieuThang[5], $mucTieuThang[6], $mucTieuThang[7], $mucTieuThang[8],
            $mucTieuThang[9], $mucTieuThang[10], $mucTieuThang[11], $mucTieuThang[12],
            $ghiChu, $year
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật kế hoạch doanh thu thành công', 'id' => $existing['KeHoachID']]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật kế hoạch: ' . $conn->error]);
        }
    } else {
        // Tạo mới kế hoạch
        $sql = "INSERT INTO ke_hoach_doanh_thu (
                    Nam, MucTieuDoanhthu, 
                    MucTieuThang1, MucTieuThang2, MucTieuThang3, MucTieuThang4,
                    MucTieuThang5, MucTieuThang6, MucTieuThang7, MucTieuThang8,
                    MucTieuThang9, MucTieuThang10, MucTieuThang11, MucTieuThang12,
                    GhiChu, NguoiTao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iddddddddddddsi', 
            $year, $mucTieuDoanhthu,
            $mucTieuThang[1], $mucTieuThang[2], $mucTieuThang[3], $mucTieuThang[4],
            $mucTieuThang[5], $mucTieuThang[6], $mucTieuThang[7], $mucTieuThang[8],
            $mucTieuThang[9], $mucTieuThang[10], $mucTieuThang[11], $mucTieuThang[12],
            $ghiChu, $nguoiTao
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Tạo kế hoạch doanh thu thành công', 'id' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi khi tạo kế hoạch: ' . $conn->error]);
        }
    }
}

// Cập nhật kế hoạch doanh thu (PUT method)
function handleUpdateRevenuePlan($conn) {
    handleCreateOrUpdateRevenuePlan($conn); // Sử dụng chung logic với POST
}

// Xóa kế hoạch doanh thu
function handleDeleteRevenuePlan($conn) {
    $year = $_GET['year'] ?? null;
    
    if (!$year) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Thiếu tham số năm']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM ke_hoach_doanh_thu WHERE Nam = ?");
    $stmt->bind_param('i', $year);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Xóa kế hoạch doanh thu thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy kế hoạch để xóa']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa kế hoạch: ' . $conn->error]);
    }
}
?>
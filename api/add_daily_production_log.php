<?php
// api/add_daily_production_log.php

// Bật hiển thị lỗi để debug (chỉ khi phát triển)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Lỗi xác thực: Bạn cần đăng nhập.']);
    exit();
}

// 2. KẾT NỐI CSDL
require_once '../config/database.php';

// Kiểm tra biến kết nối $conn (được tạo từ file database.php)
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500); // Server Error
    $errorMessage = isset($conn) ? $conn->connect_error : 'Biến $conn không được định nghĩa.';
    echo json_encode(['success' => false, 'message' => 'Lỗi cấu hình: Không thể kết nối đến cơ sở dữ liệu.', 'debug_info' => $errorMessage]);
    exit();
}

$user_id = $_SESSION['user_id'];
$request_method = $_SERVER['REQUEST_METHOD'];

// --- XỬ LÝ YÊU CẦU GET: Lấy tổng số lượng đã sản xuất ---
if ($request_method === 'GET') {
    $chiTiet_LSX_ID = filter_input(INPUT_GET, 'chiTiet_LSX_ID', FILTER_VALIDATE_INT);

    // [CẬP NHẬT] - Nếu có ID cụ thể, trả về tổng sản lượng cho ID đó (dùng cho modal)
    if (!empty($chiTiet_LSX_ID)) {
        try {
            $stmt = $conn->prepare("SELECT SUM(SoLuongHoanThanh) as total_produced FROM nhat_ky_san_xuat WHERE ChiTiet_LSX_ID = ?");
            $stmt->bind_param("i", $chiTiet_LSX_ID);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            $total_produced = $row['total_produced'] ?? 0;
            echo json_encode(['success' => true, 'produced' => (float)$total_produced]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn CSDL.', 'debug_info' => $e->getMessage()]);
        }
    } else {
        // [CẬP NHẬT] - Nếu không có ID, trả về một danh sách tất cả sản lượng đã sản xuất (dùng cho bảng chính)
        try {
            $stmt = $conn->prepare("
                SELECT ChiTiet_LSX_ID, SUM(SoLuongHoanThanh) as total_produced 
                FROM nhat_ky_san_xuat 
                GROUP BY ChiTiet_LSX_ID
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $produced_map = [];
            while ($row = $result->fetch_assoc()) {
                $produced_map[$row['ChiTiet_LSX_ID']] = (float)$row['total_produced'];
            }
            $stmt->close();

            echo json_encode(['success' => true, 'produced_map' => $produced_map]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn CSDL khi lấy tất cả dữ liệu sản lượng.', 'debug_info' => $e->getMessage()]);
        }
    }
    $conn->close();
    exit();
}

// --- XỬ LÝ YÊU CẦU POST: Thêm hoặc cập nhật nhật ký sản xuất ---
if ($request_method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lỗi dữ liệu đầu vào: Dữ liệu gửi lên không phải là định dạng JSON hợp lệ.']);
        exit();
    }
    
    $chiTiet_LSX_ID = $data['chiTiet_LSX_ID'] ?? null;
    $ngayBaoCao = $data['ngayBaoCao'] ?? null;
    $soLuong = $data['soLuong'] ?? null;
    $ghiChu = $data['ghiChu'] ?? '';

    $errors = [];
    if (empty($chiTiet_LSX_ID) || !filter_var($chiTiet_LSX_ID, FILTER_VALIDATE_INT)) $errors[] = "ID chi tiết lệnh sản xuất không hợp lệ.";
    if (empty($ngayBaoCao)) $errors[] = "Ngày báo cáo không được để trống.";
    if ($soLuong === null || !is_numeric($soLuong) || $soLuong <= 0) $errors[] = "Số lượng phải là một số lớn hơn 0.";

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.', 'errors' => $errors]);
        exit();
    }

    try {
        $conn->begin_transaction();
        $stmt_check = $conn->prepare("SELECT NhatKyID, GhiChu FROM nhat_ky_san_xuat WHERE ChiTiet_LSX_ID = ? AND NgayBaoCao = ?");
        $stmt_check->bind_param("is", $chiTiet_LSX_ID, $ngayBaoCao);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $existing_log = $result->fetch_assoc();
        $stmt_check->close();

        if ($existing_log) {
            $finalGhiChu = $existing_log['GhiChu'];
            if (!empty($ghiChu)) {
                 $finalGhiChu .= (empty($finalGhiChu) ? '' : "\n") . $ghiChu;
            }
            $stmt = $conn->prepare("UPDATE nhat_ky_san_xuat SET SoLuongHoanThanh = SoLuongHoanThanh + ?, GhiChu = ?, NguoiThucHien_ID = ? WHERE NhatKyID = ?");
            $stmt->bind_param("dsii", $soLuong, $finalGhiChu, $user_id, $existing_log['NhatKyID']);
            $message = 'Đã cập nhật thêm sản lượng cho ngày đã chọn.';
        } else {
            $stmt = $conn->prepare("INSERT INTO nhat_ky_san_xuat (ChiTiet_LSX_ID, NgayBaoCao, SoLuongHoanThanh, NguoiThucHien_ID, GhiChu) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdis", $chiTiet_LSX_ID, $ngayBaoCao, $soLuong, $user_id, $ghiChu);
            $message = 'Đã ghi nhận sản lượng thành công.';
        }

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $stmt->close();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => $message]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: Không thể lưu dữ liệu.', 'debug_info' => $e->getMessage()]);
    }
    $conn->close();
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không được hỗ trợ.']);
?>

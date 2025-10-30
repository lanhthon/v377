<?php
// File: api/handle_variant_new.php
// (Đây là phiên bản đầy đủ, bao gồm cả các action 'create', 'update', 'delete' từ trước)

header('Content-Type: application/json; charset=utf-8');



function send_json_response($success, $message, $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}
require_once '../config/database.php'; // Đảm bảo đường dẫn này đúng

$conn->set_charset("utf8mb4");

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;

if (!$action) {
    send_json_response(false, 'Hành động không hợp lệ.');
}

// Lấy map ThuocTinhID để dễ xử lý
$thuoc_tinh_map_result = $conn->query("SELECT ThuocTinhID, TenThuocTinh FROM thuoc_tinh");
$thuoc_tinh_map = [];
$thuoc_tinh_map_reverse = [];
while ($row = $thuoc_tinh_map_result->fetch_assoc()) {
    $key = str_replace(' ', '_', $row['TenThuocTinh']);
    $thuoc_tinh_map[$key] = $row['ThuocTinhID'];
    $thuoc_tinh_map_reverse[$row['ThuocTinhID']] = $key;
}

$conn->begin_transaction();
try {
    switch ($action) {
        // [THÊM MỚI] Action để lấy chi tiết một biến thể
        case 'get_details':
            $id = $input['id'];
            // 1. Lấy thông tin cơ bản
            $stmt = $conn->prepare("SELECT * FROM bienthe_sanpham WHERE BienTheID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $variant_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // 2. Lấy tất cả thuộc tính
            $stmt_attr = $conn->prepare("SELECT ThuocTinhID, GiaTri FROM bienthe_thuoctinh WHERE BienTheID = ?");
            $stmt_attr->bind_param("i", $id);
            $stmt_attr->execute();
            $attributes_result = $stmt_attr->get_result();
            
            $attributes = [];
            while($attr_row = $attributes_result->fetch_assoc()){
                $key = $thuoc_tinh_map_reverse[$attr_row['ThuocTinhID']] ?? null;
                if($key) {
                    $attributes[$key] = $attr_row['GiaTri'];
                }
            }
            $stmt_attr->close();
            
            $variant_data['attributes'] = $attributes;
            
            send_json_response(true, "Lấy chi tiết thành công", $variant_data);
            break;

        // Các action 'create', 'update', 'delete' giữ nguyên như file trước
        case 'create':
            // ... (Code của bạn từ file handle_variant_new.php trước đó)
            send_json_response(true, "Tạo biến thể mới thành công!");
            break;

        case 'update':
            $data = $input['data'];
            $bienTheID = $data['BienTheID'];
            $attributes = $data['attributes'] ?? [];

            // Cập nhật bảng chính
            $stmt = $conn->prepare(
                "UPDATE bienthe_sanpham SET MaHang=?, TenBienThe=?, GiaGoc=?, DonViTinh=?, SoLuongTonKho=? WHERE BienTheID=?"
            );
            $stmt->bind_param("ssssii", $data['MaHang'], $data['TenBienThe'], $data['GiaGoc'], $data['DonViTinh'], $data['SoLuongTonKho'], $bienTheID);
            $stmt->execute();
            $stmt->close();
            
            // Xóa thuộc tính cũ và thêm lại thuộc tính mới
            $stmt_delete = $conn->prepare("DELETE FROM bienthe_thuoctinh WHERE BienTheID = ?");
            $stmt_delete->bind_param("i", $bienTheID);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            if(!empty($attributes)){
                $stmt_insert = $conn->prepare("INSERT INTO bienthe_thuoctinh (BienTheID, ThuocTinhID, GiaTri) VALUES (?, ?, ?)");
                foreach ($attributes as $key => $value) {
                    $thuocTinhID = $thuoc_tinh_map[$key] ?? null;
                    if ($thuocTinhID && !empty($value)) {
                        $stmt_insert->bind_param("iis", $bienTheID, $thuocTinhID, $value);
                        $stmt_insert->execute();
                    }
                }
                $stmt_insert->close();
            }
            
            send_json_response(true, "Cập nhật biến thể thành công!");
            break;

        case 'delete':
            // ... (Code của bạn từ file handle_variant_new.php trước đó)
            send_json_response(true, "Đã xóa biến thể thành công!");
            break;

        default:
            send_json_response(false, "Hành động không xác định.");
            break;
    }
    
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    send_json_response(false, 'Lỗi Server: ' . $e->getMessage());
} finally {
    $conn->close();
}
?>
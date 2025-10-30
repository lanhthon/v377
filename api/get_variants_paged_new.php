<?php
header('Content-Type: application/json; charset=utf-8');

// --- THÔNG SỐ KẾT NỐI CSDL ---

// Hàm helper để gửi phản hồi JSON
function send_json_response($success, $message, $data = null, $last_page = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) $response['data'] = $data;
    if ($last_page !== null) $response['last_page'] = $last_page;
    echo json_encode($response);
    exit;
}

require_once '../config/database.php';
$conn->set_charset("utf8mb4");

try {
    // Phân trang
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $size = isset($_GET['size']) ? (int)$_GET['size'] : 50;
    $offset = ($page - 1) * $size;

    // Lấy tổng số biến thể để tính số trang
    $total_result = $conn->query("SELECT COUNT(*) as total FROM bienthe_sanpham");
    $total_rows = $total_result->fetch_assoc()['total'];
    $last_page = ceil($total_rows / $size);

    // 1. Lấy danh sách biến thể theo trang
    $stmt = $conn->prepare(
        "SELECT b.*, g.TenGoc, g.NhomID, g.LoaiID FROM bienthe_sanpham b
         JOIN sanpham_goc g ON b.GocID = g.GocID
         ORDER BY b.BienTheID DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("ii", $size, $offset);
    $stmt->execute();
    $variants_result = $stmt->get_result();
    
    $variants = [];
    $variant_ids = [];
    while ($row = $variants_result->fetch_assoc()) {
        $variants[$row['BienTheID']] = $row;
        $variant_ids[] = $row['BienTheID'];
    }
    $stmt->close();
    
    // 2. Lấy tất cả thuộc tính của các biến thể đã lấy ở trên
    if (!empty($variant_ids)) {
        $ids_placeholder = implode(',', array_fill(0, count($variant_ids), '?'));
        $types = str_repeat('i', count($variant_ids));

        $stmt_attr = $conn->prepare(
            "SELECT bt.BienTheID, t.TenThuocTinh, bt.GiaTri 
             FROM bienthe_thuoctinh bt
             JOIN thuoc_tinh t ON bt.ThuocTinhID = t.ThuocTinhID
             WHERE bt.BienTheID IN ($ids_placeholder)"
        );
        $stmt_attr->bind_param($types, ...$variant_ids);
        $stmt_attr->execute();
        $attributes_result = $stmt_attr->get_result();

        // 3. "Xoay" (Pivot) dữ liệu thuộc tính vào mảng biến thể
        while ($attr_row = $attributes_result->fetch_assoc()) {
            $bienTheID = $attr_row['BienTheID'];
            // Chuyển tên thuộc tính thành key hợp lệ (VD: "Kích thước ren" -> "Kich_thuoc_ren")
            $attr_key = str_replace(' ', '_', $attr_row['TenThuocTinh']); 
            if (isset($variants[$bienTheID])) {
                $variants[$bienTheID][$attr_key] = $attr_row['GiaTri'];
            }
        }
        $stmt_attr->close();
    }
    
    // Chuyển từ mảng kết hợp về mảng tuần tự
    $final_data = array_values($variants);

    send_json_response(true, 'Lấy dữ liệu biến thể thành công', $final_data, $last_page);

} catch (Exception $e) {
    send_json_response(false, 'Lỗi server: ' . $e->getMessage());
} finally {
    $conn->close();
}
?>
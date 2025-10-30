<?php
// classify.php - Script tự động phân loại sản phẩm
set_time_limit(600);
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre style='font-family: monospace; line-height: 1.6; font-size: 14px;'>";

// --- KẾT NỐI CSDL ---
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "baogia_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("!!! LỖI KẾT NỐI CSDL: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

try {
    echo "<b>BẮT ĐẦU QUÁ TRÌNH TỰ ĐỘNG PHÂN LOẠI SẢN PHẨM...</b>\n\n";

    // --- BƯỚC 1: LẤY TOÀN BỘ DANH SÁCH "LOẠI SẢN PHẨM" VÀO BỘ NHỚ ---
    $loai_result = $conn->query("SELECT LoaiID, TenLoai FROM loaisanpham");
    $loai_map = [];
    while ($row = $loai_result->fetch_assoc()) {
        $loai_map[$row['LoaiID']] = $row['TenLoai'];
    }
    echo "=> Đã tải " . count($loai_map) . " loại sản phẩm vào bộ nhớ.\n";

    // --- BƯỚC 2: LẤY TOÀN BỘ SẢN PHẨM CHI TIẾT (BIẾN THỂ) CẦN PHÂN LOẠI ---
    $sql = "
        SELECT 
            v.variant_id, 
            p.name AS product_name
        FROM variants v
        JOIN products p ON v.product_id = p.product_id
    ";
    $variants_result = $conn->query($sql);
    $variants_to_classify = $variants_result->fetch_all(MYSQLI_ASSOC);
    $total_variants = count($variants_to_classify);
    echo "=> Tìm thấy {$total_variants} sản phẩm chi tiết cần được phân loại.\n\n";

    // --- BƯỚC 3: BẮT ĐẦU VÒNG LẶP ĐỂ PHÂN LOẠI TỪNG SẢN PHẨM ---
    $classified_count = 0;
    $conn->begin_transaction();
    
    $update_stmt = $conn->prepare("UPDATE variants SET LoaiID = ? WHERE variant_id = ?");

    foreach ($variants_to_classify as $variant) {
        $variant_id = $variant['variant_id'];
        $product_name = mb_strtolower($variant['product_name']);
        $assigned_loai_id = null;
        $reason = "Không có quy tắc phù hợp";

        // --- ĐÂY LÀ NƠI CHỨA LOGIC PHÂN LOẠI CỦA BẠN ---

        // QUY TẮC 1: DÀNH CHO CÙM ULA
        if (strpos($product_name, 'cùm') !== false) {
            foreach ($loai_map as $id => $ten_loai) {
                if (strpos(mb_strtolower($ten_loai), 'cùm ula') !== false) {
                    $assigned_loai_id = $id;
                    $reason = "Phân loại là 'Cùm Ula'";
                    break;
                }
            }
        } 
        // QUY TẮC 2: DÀNH CHO GỐI ĐỠ (Mặc định là loại có tỷ trọng thấp nhất)
        // Bạn có thể thêm các quy tắc phức tạp hơn ở đây nếu cần
        elseif (strpos($product_name, 'gối') !== false) {
             foreach ($loai_map as $id => $ten_loai) {
                if (strpos(mb_strtolower($ten_loai), '130 - 190kg/m3') !== false) {
                    $assigned_loai_id = $id;
                    $reason = "Phân loại mặc định cho 'Gối đỡ'";
                    break;
                }
            }
        }

        // Cập nhật vào CSDL nếu tìm thấy loại phù hợp
        if ($assigned_loai_id !== null) {
            $update_stmt->bind_param("ii", $assigned_loai_id, $variant_id);
            $update_stmt->execute();
            $classified_count++;
            echo "[Sản phẩm ID: {$variant_id}] - {$product_name} -> <b style='color:green;'>Phân loại thành công!</b> (Lý do: {$reason})\n";
        } else {
            echo "[Sản phẩm ID: {$variant_id}] - {$product_name} -> <b style='color:orange;'>Bỏ qua.</b> (Lý do: {$reason})\n";
        }
    }
    
    $update_stmt->close();
    $conn->commit();

    echo "\n<b style='color: blue;'>QUÁ TRÌNH HOÀN TẤT!</b>\n";
    echo "<b>Đã phân loại thành công cho {$classified_count} trên tổng số {$total_variants} sản phẩm.</b>\n";
    echo "Vui lòng tải lại trang quản lý để xem kết quả.\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "<b style='color: red;'>ĐÃ XẢY RA LỖI: " . $e->getMessage() . "</b>";
}

$conn->close();
echo "</pre>";
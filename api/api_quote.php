<?php
// api_quote.php (API xử lý toàn bộ chức năng báo giá)
header('Content-Type: application/json');

// --- KẾT NỐI CSDL ---
require_once '../config/database.php'; // Đảm bảo đường dẫn này đúng
$conn->set_charset("utf8mb4");

// --- API ROUTER ---
$request_body = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $request_body['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
    exit;
}

$response = ['success' => true, 'data' => [], 'message' => ''];

try {
    switch ($action) {
        case 'get_quotes':
            $sql = "SELECT BaoGiaID, SoBaoGia, NgayBaoGia, TenCongTy, TenDuAn, TongTienSauThue, TrangThai FROM baogia ORDER BY NgayBaoGia DESC";
            $result = $conn->query($sql);
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'get_quote_details':
            $quote_id = intval($_GET['id'] ?? 0);
            if ($quote_id > 0) {
                // Lấy thông tin chung của báo giá
                $stmt_main = $conn->prepare("SELECT * FROM baogia WHERE BaoGiaID = ?");
                $stmt_main->bind_param("i", $quote_id);
                $stmt_main->execute();
                $main_data = $stmt_main->get_result()->fetch_assoc();
                $stmt_main->close();
                
                // Lấy chi tiết sản phẩm trong báo giá
                $stmt_details = $conn->prepare("
                    SELECT c.*, v.variant_sku, v.variant_name 
                    FROM chitietbaogia c
                    JOIN variants v ON c.variant_id = v.variant_id
                    WHERE c.BaoGiaID = ? ORDER BY c.ThuTuHienThi
                ");
                $stmt_details->bind_param("i", $quote_id);
                $stmt_details->execute();
                $details_data = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_details->close();

                $response['data'] = [
                    'main' => $main_data,
                    'details' => $details_data
                ];
            } else {
                throw new Exception("ID báo giá không hợp lệ.");
            }
            break;

        case 'search_products':
            $term = $request_body['term'] ?? '';
            $term = "%{$term}%";
            $stmt = $conn->prepare("
                SELECT variant_id, variant_sku, variant_name, price 
                FROM variants 
                WHERE variant_sku LIKE ? OR variant_name LIKE ? 
                LIMIT 20
            ");
            $stmt->bind_param("ss", $term, $term);
            $stmt->execute();
            $result = $stmt->get_result();
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            break;
            
        case 'save_quote':
            $quote_data = $request_body['quote_data'];
            $products = $request_body['products'];
            $baogia_id = intval($quote_data['BaoGiaID'] ?? 0);

            $conn->begin_transaction();

            if ($baogia_id > 0) { // Cập nhật
                $sql = "UPDATE baogia SET SoBaoGia=?, NgayBaoGia=?, TenCongTy=?, TenDuAn=?, TongTienTruocThue=?, ThueVAT=?, TongTienSauThue=?, TrangThai=? WHERE BaoGiaID=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssddssi", $quote_data['SoBaoGia'], $quote_data['NgayBaoGia'], $quote_data['TenCongTy'], $quote_data['TenDuAn'], $quote_data['TongTienTruocThue'], $quote_data['ThueVAT'], $quote_data['TongTienSauThue'], $quote_data['TrangThai'], $baogia_id);
            } else { // Thêm mới
                $sql = "INSERT INTO baogia (SoBaoGia, NgayBaoGia, TenCongTy, TenDuAn, TongTienTruocThue, ThueVAT, TongTienSauThue, TrangThai) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssddss", $quote_data['SoBaoGia'], $quote_data['NgayBaoGia'], $quote_data['TenCongTy'], $quote_data['TenDuAn'], $quote_data['TongTienTruocThue'], $quote_data['ThueVAT'], $quote_data['TongTienSauThue'], $quote_data['TrangThai']);
            }
            $stmt->execute();
            if ($baogia_id === 0) {
                $baogia_id = $conn->insert_id;
            }
            $stmt->close();
            
            // Xóa chi tiết cũ và thêm lại
            $conn->query("DELETE FROM chitietbaogia WHERE BaoGiaID = {$baogia_id}");

            $stmt_detail = $conn->prepare("INSERT INTO chitietbaogia (BaoGiaID, variant_id, MaHang, TenSanPham, SoLuong, DonGia, ThanhTien, GhiChu, ThuTuHienThi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($products as $index => $product) {
                $stmt_detail->bind_param("iisssddsi", $baogia_id, $product['variant_id'], $product['MaHang'], $product['TenSanPham'], $product['SoLuong'], $product['DonGia'], $product['ThanhTien'], $product['GhiChu'], $index + 1);
                $stmt_detail->execute();
            }
            $stmt_detail->close();
            
            $conn->commit();
            $response['message'] = 'Lưu báo giá thành công!';
            $response['data'] = ['baogia_id' => $baogia_id];
            break;

        default:
            throw new Exception("Hành động không hợp lệ.");
    }
} catch (Exception $e) {
    $conn->rollback();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
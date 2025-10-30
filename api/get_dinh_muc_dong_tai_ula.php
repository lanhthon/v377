<?php
/**
 * File: api/get_dinh_muc_dong_tai_ula.php
 * Description: API để lấy dữ liệu định mức đóng tải ULA
 */

// Thiết lập header để trả về dữ liệu dạng JSON với bộ mã UTF-8
header('Content-Type: application/json; charset=utf-8');

// Tải tệp cấu hình và hàm kết nối cơ sở dữ liệu
require_once '../config/db_config.php';

try {
    // Lấy đối tượng kết nối PDO từ hàm đã định nghĩa sẵn
    $pdo = get_db_connection();
    
    // Câu lệnh SQL để lấy tất cả dữ liệu từ bảng `bang_dinh_muc_dong_tai_ula`
    $sql = "SELECT id, ma_sp, kich_thuoc_tai, so_bo_tren_tai, so_coc_tren_tai, tong_kg_tren_tai, ghi_chu 
            FROM bang_dinh_muc_dong_tai_ula";
    
    // Thực thi câu lệnh truy vấn
    $stmt = $pdo->query($sql);
    
    // Lấy tất cả dữ liệu trả về dưới dạng mảng kết hợp (associative array)
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Chuyển đổi kiểu dữ liệu cho các cột số để đảm bảo tính nhất quán
    foreach ($data as &$row) {
        // Chuyển các giá trị có thể là số sang kiểu số nguyên hoặc số thực
        $row['id'] = (int)$row['id'];
        $row['so_bo_tren_tai'] = (int)$row['so_bo_tren_tai'];
        $row['so_coc_tren_tai'] = (int)$row['so_coc_tren_tai'];
        // tong_kg_tren_tai có thể là số thập phân, nên dùng (float)
        $row['tong_kg_tren_tai'] = (float)$row['tong_kg_tren_tai'];
    }
    
    // Trả về kết quả thành công dưới dạng JSON
    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data) // Đếm số lượng bản ghi trả về
    ]);
    
} catch (Exception $e) {
    // Ghi lại lỗi vào error log của máy chủ để dễ dàng gỡ lỗi sau này
    error_log("ERROR in get_dinh_muc_dong_tai_ula.php: " . $e->getMessage());
    
    // Gửi mã lỗi 500 (Internal Server Error)
    http_response_code(500);
    
    // Trả về thông báo lỗi dưới dạng JSON
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lấy dữ liệu định mức đóng tải ULA: ' . $e->getMessage()
    ]);
}
?>